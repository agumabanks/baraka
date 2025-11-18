#!/bin/bash

# ====================================================================
# TRADITIONAL SERVER DEPLOYMENT SCRIPT (NO DOCKER)
# Baraka Branch Management Portal - Server Deployment
# Generated: 2025-11-18T01:45:56Z
# ====================================================================

set -euo pipefail

# Configuration
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$SCRIPT_DIR"
BACKUP_DIR="$PROJECT_ROOT/backups"
LOG_FILE="$PROJECT_ROOT/logs/deployment.log"
ENV_FILE="$PROJECT_ROOT/.env.production"

# Colors for output
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

# Check prerequisites
check_prerequisites() {
    log "Checking prerequisites for traditional server deployment..."
    
    # Check if .env.production exists
    if [[ ! -f "$ENV_FILE" ]]; then
        error "Production environment file not found: $ENV_FILE"
    fi
    
    # Check required commands
    local required_commands=("php" "composer" "mysql" "redis-cli" "git" "nginx" "systemctl")
    for cmd in "${required_commands[@]}"; do
        if ! command -v "$cmd" &> /dev/null; then
            error "Required command not found: $cmd"
        fi
    done
    
    # Check PHP version
    local php_version=$(php -r "echo PHP_VERSION;")
    if [[ $(echo "$php_version 8.0" | awk '{print ($1 >= $2)}') -eq 0 ]]; then
        error "PHP 8.0+ required, found: $php_version"
    fi
    
    log "Prerequisites check completed successfully"
}

# Create necessary directories
setup_directories() {
    log "Setting up directories..."
    
    mkdir -p "$BACKUP_DIR"/{daily,weekly,monthly,archives}
    mkdir -p "$PROJECT_ROOT/logs"
    mkdir -p "$PROJECT_ROOT/storage/framework/{cache,sessions,views}"
    mkdir -p "$PROJECT_ROOT/storage/app/public
    mkdir -p "$PROJECT_ROOT/storage/logs
    
    # Set proper permissions
    chown -R www-data:www-data "$PROJECT_ROOT"
    chmod -R 775 "$PROJECT_ROOT/storage"
    chmod -R 775 "$PROJECT_ROOT/bootstrap/cache"
    chmod +x "$PROJECT_ROOT/artisan"
    
    log "Directories setup completed"
}

# Pre-deployment backup
create_backup() {
    log "Creating pre-deployment backup..."
    
    local backup_name="baraka_backup_$(date +%Y%m%d_%H%M%S)"
    local backup_path="$BACKUP_DIR/daily/$backup_name"
    
    mkdir -p "$backup_path"
    
    # Database backup
    if command -v mysqldump &> /dev/null; then
        local db_name=$(grep "^DB_DATABASE=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')
        local db_user=$(grep "^DB_USERNAME=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')
        local db_pass=$(grep "^DB_PASSWORD=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')
        local db_host=$(grep "^DB_HOST=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')
        
        if [[ -n "$db_name" && -n "$db_user" ]]; then
            mysqldump -h"$db_host" -u"$db_user" -p"$db_pass" "$db_name" > "$backup_path/database.sql"
            gzip "$backup_path/database.sql"
            log "Database backup completed"
        fi
    fi
    
    log "Backup created: $backup_path"
}

# Database preparation
prepare_database() {
    log "Preparing database..."
    
    # Run production optimizations
    local db_name=$(grep "^DB_DATABASE=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')
    local db_user=$(grep "^DB_USERNAME=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')
    local db_pass=$(grep "^DB_PASSWORD=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')
    local db_host=$(grep "^DB_HOST=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')
    
    if [[ -f "$PROJECT_ROOT/database/production_optimization.sql" ]]; then
        mysql -h"$db_host" -u"$db_user" -p"$db_pass" "$db_name" < "$PROJECT_ROOT/database/production_optimization.sql"
        log "Database optimization completed"
    fi
}

# Application deployment
deploy_application() {
    log "Deploying application..."
    
    cd "$PROJECT_ROOT"
    
    # Update from repository if git exists
    if [[ -d ".git" ]]; then
        git pull origin main
    fi
    
    # Install dependencies
    log "Installing composer dependencies..."
    composer install --no-dev --optimize-autoloader
    
    # Generate application key if not set
    if ! grep -q "^APP_KEY=base64:" "$ENV_FILE"; then
        log "Generating application key..."
        php artisan key:generate
    fi
    
    # Cache configuration
    log "Caching configuration..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    
    # Run migrations
    log "Running database migrations..."
    php artisan migrate --force
    
    # Seed database if requested
    if [[ "${1:-}" == "--seed" ]]; then
        log "Seeding database..."
        php artisan db:seed --force
    fi
    
    # Clear caches
    log "Clearing old caches..."
    php artisan cache:clear
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
    
    # Optimize for production
    log "Optimizing application for production..."
    php artisan optimize
    
    log "Application deployment completed"
}

# Redis setup
setup_redis() {
    log "Setting up Redis..."
    
    # Check if Redis is installed
    if ! command -v redis-cli &> /dev/null; then
        warn "Redis CLI not found, skipping Redis setup"
        return
    fi
    
    # Clear Redis cache if accessible
    if redis-cli ping &>/dev/null; then
        redis-cli FLUSHDB || warn "Could not clear Redis cache"
        log "Redis connection successful"
    else
        warn "Redis connection failed - check configuration"
    fi
}

# Nginx configuration
setup_nginx() {
    log "Setting up Nginx configuration..."
    
    local nginx_config="$PROJECT_ROOT/nginx/baraka-production.conf"
    
    if [[ -f "$nginx_config" ]]; then
        # Copy configuration to Nginx
        if [[ -d "/etc/nginx/sites-available" ]]; then
            cp "$nginx_config" /etc/nginx/sites-available/baraka
            ln -sf /etc/nginx/sites-available/baraka /etc/nginx/sites-enabled/baraka
            
            # Test Nginx configuration
            if nginx -t; then
                log "Nginx configuration test passed"
                systemctl reload nginx
                log "Nginx reloaded successfully"
            else
                error "Nginx configuration test failed"
            fi
        else
            warn "Nginx sites-available directory not found, skipping Nginx setup"
        fi
    else
        warn "Nginx configuration file not found: $nginx_config"
    fi
}

# Queue workers setup
setup_queue_workers() {
    log "Setting up queue workers..."
    
    # Create supervisor configuration for queue workers
    local supervisor_conf="/etc/supervisor/conf.d/baraka-worker.conf"
    
    if [[ -d "/etc/supervisor" ]]; then
        cat > "$supervisor_conf" << EOF
[program:baraka-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $PROJECT_ROOT/artisan queue:work redis --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=$PROJECT_ROOT/storage/logs/worker.log
stopwaitsecs=3600
memory_limit=256M
EOF
        
        # Update supervisor
        supervisorctl reread
        supervisorctl update
        supervisorctl start baraka-worker:*
        
        log "Queue workers setup completed"
    else
        warn "Supervisor not found, queue workers will not be managed"
    fi
}

# Scheduler setup
setup_scheduler() {
    log "Setting up scheduler..."
    
    # Add to crontab
    (crontab -l 2>/dev/null | grep -v "schedule:run" || true; echo "* * * * * cd $PROJECT_ROOT && php artisan schedule:run >> /dev/null 2>&1") | crontab -
    
    # Restart cron if running
    if systemctl is-active --quiet cron; then
        systemctl restart cron
        log "Scheduler setup completed"
    else
        warn "Cron service not running, scheduler may not work"
    fi
}

# Asset compilation
compile_assets() {
    log "Compiling assets..."
    
    cd "$PROJECT_ROOT"
    
    # Install Node.js dependencies if package.json exists
    if [[ -f "react-dashboard/package.json" ]]; then
        if command -v npm &> /dev/null; then
            cd react-dashboard
            npm ci --production
            npm run build
            cd ..
            log "React dashboard assets compiled"
        else
            warn "npm not found, skipping asset compilation"
        fi
    fi
}

# Health check
health_check() {
    log "Performing health check..."
    
    local base_url=$(grep "^APP_URL=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')
    local health_url="${base_url}/health"
    
    # Simple health check
    if curl -f -s "$health_url" > /dev/null; then
        log "Health check passed"
        return 0
    else
        warn "Health check failed - application may not be ready"
        return 1
    fi
}

# Post-deployment tasks
post_deployment() {
    log "Running post-deployment tasks..."
    
    # Clear any remaining caches
    php artisan cache:clear
    
    # Warm up caches
    log "Warming up caches..."
    php artisan route:list > /dev/null || warn "Could not warm route cache"
    
    # Restart queue workers
    if systemctl is-active --quiet supervisor; then
        supervisorctl restart baraka-worker:*
        log "Queue workers restarted"
    fi
    
    log "Post-deployment tasks completed"
}

# Main deployment function
main() {
    log "Starting traditional server deployment for Baraka Logistics"
    log "Deployment initiated by: $(whoami)"
    log "Server: $(hostname)"
    log "Environment: Production"
    
    # Confirm deployment
    if [[ "${FORCE_DEPLOY:-0}" != "1" ]]; then
        echo -e "${YELLOW}This will deploy to production. Are you sure? (yes/no)${NC}"
        read -r confirmation
        if [[ "$confirmation" != "yes" ]]; then
            log "Deployment cancelled by user"
            exit 0
        fi
    fi
    
    # Execute deployment steps
    setup_directories
    check_prerequisites
    create_backup
    prepare_database
    deploy_application "$@"
    setup_redis
    setup_nginx
    setup_queue_workers
    setup_scheduler
    compile_assets
    
    # Wait for application to be ready
    sleep 5
    
    # Health check
    if health_check; then
        post_deployment
        log "✅ Traditional server deployment completed successfully!"
        log "Application should be available at: $(grep "^APP_URL=" "$ENV_FILE" | cut -d'=' -f2 | tr -d '"')"
    else
        warn "⚠️ Health check failed - deployment may have issues"
    fi
}

# Error handling
trap 'error "Deployment failed at line $LINENO"' ERR

# Run main function
main "$@"