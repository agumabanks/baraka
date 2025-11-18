#!/bin/bash

# ====================================================================
# DHL-GRADE BACKUP & DISASTER RECOVERY SCRIPT
# Baraka Branch Management Portal - Enterprise Backup System
# Generated: 2025-11-18T00:57:00Z
# ====================================================================

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"
BACKUP_BASE_DIR="/var/backups/baraka"
LOG_FILE="$PROJECT_ROOT/logs/backup.log"
RETENTION_DAYS=30
ENCRYPTION_KEY="${BACKUP_ENCRYPTION_KEY:-}"

# Database configuration
DB_CONFIG_FILE="$PROJECT_ROOT/.env.production"

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Logging functions
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}" | tee -a "$LOG_FILE"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}" | tee -a "$LOG_FILE"
    exit 1
}

warn() {
    echo -e "${YELLOW}[WARNING] $1${NC}" | tee -a "$LOG_FILE"
}

info() {
    echo -e "${BLUE}[INFO] $1${NC}" | tee -a "$LOG_FILE"
}

# Setup backup directories
setup_backup_environment() {
    log "Setting up backup environment..."
    
    mkdir -p "$BACKUP_BASE_DIR"/{daily,weekly,monthly,archives}
    mkdir -p "$PROJECT_ROOT/logs"
    
    # Set proper permissions
    chmod 750 "$BACKUP_BASE_DIR"
    chmod 640 "$LOG_FILE"
    
    log "Backup environment setup completed"
}

# Load database configuration
load_db_config() {
    info "Loading database configuration..."
    
    # Source environment file
    if [[ -f "$DB_CONFIG_FILE" ]]; then
        set -a
        source "$DB_CONFIG_FILE"
        set +a
    else
        error "Database configuration file not found: $DB_CONFIG_FILE"
    fi
    
    # Validate required variables
    local required_vars=("DB_HOST" "DB_PORT" "DB_DATABASE" "DB_USERNAME" "DB_PASSWORD")
    for var in "${required_vars[@]}"; do
        if [[ -z "${!var:-}" ]]; then
            error "Required database variable not set: $var"
        fi
    done
    
    info "Database configuration loaded successfully"
}

# Create database backup
backup_database() {
    local backup_type="$1"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_name="baraka_db_${backup_type}_${timestamp}"
    local backup_path="$BACKUP_BASE_DIR/$backup_type/$backup_name"
    
    log "Creating $backup_type database backup: $backup_name"
    
    # Create backup directory
    mkdir -p "$backup_path"
    
    # Database dump
    local dump_file="$backup_path/database.sql"
    
    # Perform database backup with compression
    mysqldump \
        --host="$DB_HOST" \
        --port="$DB_PORT" \
        --user="$DB_USERNAME" \
        --password="$DB_PASSWORD" \
        --single-transaction \
        --routines \
        --triggers \
        --events \
        --add-drop-database \
        --databases "$DB_DATABASE" \
        | gzip > "${dump_file}.gz"
    
    if [[ $? -eq 0 ]]; then
        log "Database backup completed: ${dump_file}.gz"
        
        # Calculate checksum
        sha256sum "${dump_file}.gz" > "${dump_file}.gz.sha256"
        
        # Get backup size
        local backup_size=$(du -h "${dump_file}.gz" | cut -f1)
        info "Backup size: $backup_size"
        
        # Store metadata
        cat > "$backup_path/backup_info.json" << EOF
{
    "backup_type": "$backup_type",
    "backup_name": "$backup_name",
    "database": "$DB_DATABASE",
    "created_at": "$(date -Iseconds)",
    "size": "$backup_size",
    "checksum_file": "${dump_file}.gz.sha256"
}
EOF
    else
        error "Database backup failed"
    fi
}

# Backup application files
backup_application() {
    local backup_type="$1"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_name="baraka_app_${backup_type}_${timestamp}"
    local backup_path="$BACKUP_BASE_DIR/$backup_type/$backup_name"
    
    log "Creating $backup_type application backup: $backup_name"
    
    # Create backup directory
    mkdir -p "$backup_path"
    
    # Define what to backup
    local backup_items=(
        "app"
        "config"
        "database"
        "resources"
        "routes"
        "storage/app/public"
        "storage/framework"
        ".env.production"
        "composer.json"
        "composer.lock"
        "package.json"
        "package-lock.json"
    )
    
    # Create tar archive
    local archive_file="$backup_path/application.tar.gz"
    
    tar -czf "$archive_file" \
        --exclude='.git' \
        --exclude='node_modules' \
        --exclude='vendor' \
        --exclude='storage/logs' \
        --exclude='storage/framework/cache' \
        --exclude='storage/framework/sessions' \
        --exclude='storage/framework/views' \
        --exclude='storage/app/backups' \
        --exclude='public/js' \
        --exclude='public/css' \
        --exclude='bootstrap/cache' \
        "${backup_items[@]}"
    
    if [[ $? -eq 0 ]]; then
        log "Application backup completed: $archive_file"
        
        # Calculate checksum
        sha256sum "$archive_file" > "$archive_file.sha256"
        
        # Get backup size
        local backup_size=$(du -h "$archive_file" | cut -f1)
        info "Backup size: $backup_size"
        
        # Store metadata
        cat > "$backup_path/backup_info.json" << EOF
{
    "backup_type": "$backup_type",
    "backup_name": "$backup_name",
    "created_at": "$(date -Iseconds)",
    "size": "$backup_size",
    "checksum_file": "$archive_file.sha256",
    "items_included": $(printf '%s\n' "${backup_items[@]}" | jq -R . | jq -s .)
}
EOF
    else
        error "Application backup failed"
    fi
}

# Backup configuration files
backup_configurations() {
    local backup_type="$1"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_name="baraka_config_${backup_type}_${timestamp}"
    local backup_path="$BACKUP_BASE_DIR/$backup_type/$backup_name"
    
    log "Creating $backup_type configuration backup: $backup_name"
    
    # Create backup directory
    mkdir -p "$backup_path"
    
    # Backup configuration files
    local config_files=(
        "/etc/nginx/nginx.conf"
        "/etc/mysql/mysql.conf.d/mysqld.cnf"
        "/etc/redis/redis.conf"
        "/etc/supervisor/conf.d"
    )
    
    for config_file in "${config_files[@]}"; do
        if [[ -f "$config_file" ]]; then
            cp -r "$config_file" "$backup_path/" 2>/dev/null || warn "Could not backup: $config_file"
        fi
    done
    
    # Backup environment files
    cp "$PROJECT_ROOT/.env.production" "$backup_path/" 2>/dev/null || warn "Could not backup .env.production"
    
    # Backup SSL certificates
    if [[ -d "/etc/nginx/ssl" ]]; then
        cp -r "/etc/nginx/ssl" "$backup_path/" 2>/dev/null || warn "Could not backup SSL certificates"
    fi
    
    log "Configuration backup completed: $backup_path"
}

# Backup Redis data
backup_redis() {
    local backup_type="$1"
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_name="baraka_redis_${backup_type}_${timestamp}"
    local backup_path="$BACKUP_BASE_DIR/$backup_type/$backup_name"
    
    log "Creating $backup_type Redis backup: $backup_name"
    
    # Create backup directory
    mkdir -p "$backup_path"
    
    # Dump Redis data
    redis-cli --rdb "$backup_path/dump.rdb"
    
    if [[ $? -eq 0 ]]; then
        log "Redis backup completed: $backup_path/dump.rdb"
        
        # Compress the backup
        gzip "$backup_path/dump.rdb"
        
        # Calculate checksum
        sha256sum "${backup_path}/dump.rdb.gz" > "${backup_path}/dump.rdb.gz.sha256"
    else
        error "Redis backup failed"
    fi
}

# Encrypt backup files
encrypt_backup() {
    local backup_file="$1"
    local encrypted_file="${backup_file}.encrypted"
    
    if [[ -z "$ENCRYPTION_KEY" ]]; then
        warn "Encryption key not provided, skipping encryption"
        return
    fi
    
    log "Encrypting backup: $backup_file"
    
    # Encrypt using GPG
    echo "$ENCRYPTION_KEY" | gpg --batch --yes --passphrase-fd 0 --symmetric --cipher-algo AES256 "$backup_file"
    
    # Remove original file
    rm "$backup_file"
    
    log "Backup encrypted: $encrypted_file"
}

# Verify backup integrity
verify_backup() {
    local backup_path="$1"
    local checksum_file="$2"
    
    if [[ ! -f "$checksum_file" ]]; then
        error "Checksum file not found: $checksum_file"
    fi
    
    log "Verifying backup integrity: $backup_path"
    
    # Verify checksum
    if sha256sum -c "$checksum_file"; then
        log "Backup integrity verified successfully"
        return 0
    else
        error "Backup integrity check failed"
        return 1
    fi
}

# Clean old backups
clean_old_backups() {
    log "Cleaning old backups (retention: $RETENTION_DAYS days)"
    
    # Find and remove old backups
    find "$BACKUP_BASE_DIR" -type f -name "*.gz" -mtime +$RETENTION_DAYS -delete 2>/dev/null || true
    find "$BACKUP_BASE_DIR" -type f -name "*.tar.gz" -mtime +$RETENTION_DAYS -delete 2>/dev/null || true
    find "$BACKUP_BASE_DIR" -type d -empty -mtime +$RETENTION_DAYS -delete 2>/dev/null || true
    
    log "Old backup cleanup completed"
}

# Get backup size
get_backup_size() {
    local backup_path="$1"
    local size=$(du -sh "$backup_path" 2>/dev/null | cut -f1 || echo "0")
    echo "$size"
}

# Full backup function
full_backup() {
    log "Starting full backup process..."
    
    local timestamp=$(date +%Y%m%d_%H%M%S)
    
    # Database backup
    backup_database "daily"
    
    # Application backup
    backup_application "daily"
    
    # Configuration backup
    backup_configurations "daily"
    
    # Redis backup
    backup_redis "daily"
    
    # Clean old backups
    clean_old_backups
    
    log "Full backup process completed"
    log "Backup location: $BACKUP_BASE_DIR/daily/"
}

# Incremental backup function
incremental_backup() {
    log "Starting incremental backup process..."
    
    # Only backup database and critical files
    backup_database "daily"
    
    # Backup only changed application files
    local timestamp=$(date +%Y%m%d_%H%M%S)
    local backup_name="baraka_app_incremental_${timestamp}"
    local backup_path="$BACKUP_BASE_DIR/daily/$backup_name"
    
    mkdir -p "$backup_path"
    
    # Backup only recent changes (last 24 hours)
    find "$PROJECT_ROOT" -type f -mtime -1 -not -path "*/node_modules/*" -not -path "*/vendor/*" | \
        tar -czf "$backup_path/changed_files.tar.gz" -T - 2>/dev/null || true
    
    log "Incremental backup completed"
}

# Recovery function
restore_backup() {
    local backup_path="$1"
    
    if [[ ! -d "$backup_path" ]]; then
        error "Backup path does not exist: $backup_path"
    fi
    
    log "Starting backup restoration from: $backup_path"
    
    # Confirm restoration
    echo -e "${YELLOW}WARNING: This will overwrite current data. Are you sure? (yes/no)${NC}"
    read -r confirmation
    if [[ "$confirmation" != "yes" ]]; then
        log "Restoration cancelled by user"
        exit 0
    fi
    
    # Load backup metadata
    if [[ -f "$backup_path/backup_info.json" ]]; then
        info "Backup metadata found"
        cat "$backup_path/backup_info.json"
    fi
    
    # Restore database
    if [[ -f "$backup_path/database.sql.gz" ]]; then
        log "Restoring database..."
        gunzip -c "$backup_path/database.sql.gz" | mysql \
            --host="$DB_HOST" \
            --port="$DB_PORT" \
            --user="$DB_USERNAME" \
            --password="$DB_PASSWORD"
        
        if [[ $? -eq 0 ]]; then
            log "Database restored successfully"
        else
            error "Database restoration failed"
        fi
    fi
    
    # Restore application files
    if [[ -f "$backup_path/application.tar.gz" ]]; then
        log "Restoring application files..."
        cd "$PROJECT_ROOT"
        tar -xzf "$backup_path/application.tar.gz"
        
        if [[ $? -eq 0 ]]; then
            log "Application files restored successfully"
        else
            error "Application restoration failed"
        fi
    fi
    
    # Restore Redis data
    if [[ -f "$backup_path/dump.rdb.gz" ]]; then
        log "Restoring Redis data..."
        gunzip -c "$backup_path/dump.rdb.gz" > /tmp/dump.rdb
        cp /tmp/dump.rdb /var/lib/redis/dump.rdb
        systemctl restart redis
        rm /tmp/dump.rdb
    fi
    
    log "Backup restoration completed"
}

# Health check for backup system
backup_health_check() {
    log "Running backup system health check..."
    
    local health_status="healthy"
    
    # Check backup directory permissions
    if [[ ! -w "$BACKUP_BASE_DIR" ]]; then
        error "Backup directory is not writable"
        health_status="unhealthy"
    fi
    
    # Check disk space
    local available_space=$(df "$BACKUP_BASE_DIR" | awk 'NR==2 {print $4}')
    if [[ $available_space -lt 1073741824 ]]; then  # Less than 1GB
        warn "Low disk space for backups: $((available_space / 1024 / 1024))MB available"
        health_status="warning"
    fi
    
    # Check database connection
    if ! mysql -h"$DB_HOST" -u"$DB_USERNAME" -p"$DB_PASSWORD" -e "SELECT 1;" >/dev/null 2>&1; then
        error "Database connection failed"
        health_status="unhealthy"
    fi
    
    # Check Redis connection
    if ! redis-cli ping >/dev/null 2>&1; then
        warn "Redis connection failed"
        health_status="warning"
    fi
    
    case $health_status in
        "healthy")
            log "✅ Backup system health check passed"
            ;;
        "warning")
            warn "⚠️  Backup system health check completed with warnings"
            ;;
        "unhealthy")
            error "❌ Backup system health check failed"
            ;;
    esac
    
    return $([[ $health_status == "healthy" ]] && echo 0 || echo 1)
}

# Main function
main() {
    local command="${1:-full}"
    
    log "Baraka Logistics Backup & Recovery System"
    log "Command: $command"
    
    # Setup environment
    setup_backup_environment
    load_db_config
    
    case "$command" in
        "full")
            full_backup
            ;;
        "incremental")
            incremental_backup
            ;;
        "database")
            backup_database "daily"
            ;;
        "application")
            backup_application "daily"
            ;;
        "config")
            backup_configurations "daily"
            ;;
        "redis")
            backup_redis "daily"
            ;;
        "restore")
            if [[ -z "${2:-}" ]]; then
                error "Backup path required for restore command"
            fi
            restore_backup "$2"
            ;;
        "health")
            backup_health_check
            ;;
        "clean")
            clean_old_backups
            ;;
        *)
            echo "Usage: $0 {full|incremental|database|application|config|redis|restore <path>|health|clean}"
            echo ""
            echo "Commands:"
            echo "  full          - Complete backup of database, application, config, and Redis"
            echo "  incremental   - Incremental backup of recent changes"
            echo "  database      - Backup database only"
            echo "  application   - Backup application files only"
            echo "  config        - Backup configuration files only"
            echo "  redis         - Backup Redis data only"
            echo "  restore       - Restore from backup (requires path)"
            echo "  health        - Check backup system health"
            echo "  clean         - Clean old backups"
            exit 1
            ;;
    esac
}

# Error handling
trap 'error "Backup process failed at line $LINENO"' ERR

# Run main function
main "$@"