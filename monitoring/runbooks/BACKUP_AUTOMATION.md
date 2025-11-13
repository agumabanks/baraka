# Backup Automation Runbook

## Overview
This runbook covers the automated backup system for Baraka Logistics Platform, including database backups, file storage backups, and configuration management.

## Backup Schedule
Based on the monitoring configuration, backups are scheduled as follows:
- **Database Backups**: Daily at 2:00 AM (cron: `0 2 * * *`)
- **File Storage Backups**: Daily at 3:00 AM (cron: `0 3 * * *`)
- **Configuration Backups**: Weekly on Sunday at 1:00 AM (cron: `0 1 * * 0`)
- **Full System Backup**: Weekly on Saturday at 1:00 AM (cron: `0 1 * * 6`)

## Backup Locations
- **Primary Storage**: `/var/www/baraka.sanaa.co/backups/`
- **S3/Cloud Storage**: `s3://baraka-backups/production/`
- **Retention**: 30 days for daily backups, 90 days for weekly backups

## Manual Backup Commands

### Database Backup
```bash
# Create full database backup
mysqldump --user=root --password=${MYSQL_ROOT_PASSWORD} \
  --single-transaction --routines --triggers \
  baraka_logistics > /var/www/baraka.sanaa.co/backups/baraka_backup_$(date +%Y%m%d_%H%M%S).sql

# Verify backup integrity
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='baraka_logistics';"
```

### File Storage Backup
```bash
# Backup storage directory
tar -czf /var/www/baraka.sanaa.co/backups/storage_$(date +%Y%m%d_%H%M%S).tar.gz \
  /var/www/baraka.sanaa.co/storage/

# Backup configuration files
tar -czf /var/www/baraka.sanaa.co/backups/config_$(date +%Y%m%d_%H%M%S).tar.gz \
  /var/www/baraka.sanaa.co/.env /var/www/baraka.sanaa.co/config/
```

## Automated Backup Scripts

### Kubernetes CronJobs
```bash
# Apply database backup cron job
kubectl apply -f kubernetes/manifests/backup-cron.yml

# Apply file storage backup cron job
kubectl apply -f kubernetes/manifests/file-backup-cron.yml

# Check backup job status
kubectl get jobs -l app=backup
kubectl logs -l app=backup --tail=50
```

### Laravel Backup Command
```php
// Run via Laravel Artisan
php artisan backup:run --only-db
php artisan backup:run --only-files
php artisan backup:run

// Schedule via Laravel Scheduler
php artisan schedule:run
```

## Monitoring Backup Health

### Check Backup Success
```bash
# Check last backup timestamp
ls -la /var/www/baraka.sanaa.co/backups/ | grep -E "\.sql$|\.tar\.gz$" | tail -5

# Verify backup file sizes
du -sh /var/www/baraka.sanaa.co/backups/*

# Check backup monitoring alert
curl "http://prometheus:9090/api/v1/query?query=backup_last_successful_timestamp"
```

### Automated Monitoring
```bash
# Check backup monitoring configuration
php artisan tinker
>>> config('monitoring.backup_enabled') // Should be true
>>> config('monitoring.backup_schedule') // Should be "0 2 * * *"
>>> config('monitoring.backup_retention_days') // Should be 30
```

## Backup Restoration

### Database Restoration
```bash
# Create backup before restoration
mysqldump --user=root --password=${MYSQL_ROOT_PASSWORD} \
  --single-transaction baraka_logistics > /tmp/emergency_backup_pre_restore.sql

# Restore from backup
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  baraka_logistics < /var/www/baraka.sanaa.co/backups/baraka_backup_20251111_023825.sql

# Verify restoration
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  -e "SELECT COUNT(*) FROM baraka_logistics.shipments;"
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  -e "SELECT COUNT(*) FROM baraka_logistics.branches;"
```

### File Storage Restoration
```bash
# Extract backup
tar -xzf /var/www/baraka.sanaa.co/backups/storage_20251111_020000.tar.gz \
  -C /tmp/storage_restore/

# Copy files to production
cp -r /tmp/storage_restore/var/www/baraka.sanaa.co/storage/* /var/www/baraka.sanaa.co/storage/

# Fix permissions
chown -R www-data:www-data /var/www/baraka.sanaa.co/storage/
chmod -R 755 /var/www/baraka.sanaa.co/storage/
```

## Backup Verification Tests

### Weekly Backup Test
```bash
# Run weekly backup verification
php artisan backup:verify --backup-file=/var/www/baraka.sanaa.co/backups/baraka_backup_20251111_023825.sql

# Test database restoration on staging
mysql --user=staging_user --password=${STAGING_PASSWORD} \
  -h staging-db baraka_logistics_staging < /var/www/baraka.sanaa.co/backups/baraka_backup_20251111_023825.sql
```

### Backup Integrity Checks
```bash
# Verify SQL backup integrity
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  -e "SOURCE /var/www/baraka.sanaa.co/backups/baraka_backup_20251111_023825.sql" 2>/dev/null

# Check tar.gz file integrity
tar -tzf /var/www/baraka.sanaa.co/backups/storage_20251111_020000.tar.gz > /dev/null
echo $?  # Should return 0 for valid archive
```

## Common Issues and Solutions

### Backup Job Failures
```bash
# Check job logs
kubectl logs -l app=backup --tail=100

# Check disk space
df -h /var/www/baraka.sanaa.co/backups/

# Check MySQL connection
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} -e "SELECT 1;"

# Restart backup cron job
kubectl delete job backup-cron
kubectl apply -f kubernetes/manifests/backup-cron.yml
```

### Backup Storage Issues
```bash
# Clean old backups
find /var/www/baraka.sanaa.co/backups/ -name "*.sql" -mtime +30 -delete
find /var/www/baraka.sanaa.co/backups/ -name "*.tar.gz" -mtime +30 -delete

# Check S3 connectivity
aws s3 ls s3://baraka-backups/production/

# Sync to S3
aws s3 sync /var/www/baraka.sanaa.co/backups/ s3://baraka-backups/production/
```

## Escalation and Notifications

### Backup Failure Alerts
- **Alert**: `MissingBackup` - No backup in last 24 hours
- **Severity**: CRITICAL
- **Action**: Check backup job status and storage availability
- **Escalation**: Database team if unresolved in 30 minutes

### Contact Information
- **Database Team**: database@baraka.com
- **Platform Team**: platform@baraka.com
- **On-call Engineer**: [PagerDuty](https://baraka.pagerduty.com)

## Testing Backup Recovery

### Monthly DR Drill
```bash
# Schedule monthly backup restoration test
# 1. Create test database
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  -e "CREATE DATABASE baraka_logistics_test_recovery;"

# 2. Restore backup to test database
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  baraka_logistics_test_recovery < /var/www/baraka.sanaa.co/backups/latest_backup.sql

# 3. Verify critical data
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  -e "SELECT COUNT(*) as branches FROM baraka_logistics_test_recovery.branches;"
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  -e "SELECT COUNT(*) as shipments FROM baraka_logistics_test_recovery.shipments;"

# 4. Cleanup test database
mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
  -e "DROP DATABASE baraka_logistics_test_recovery;"
```

## Performance and Optimization

### Backup Performance
- **Database size**: ~5GB (monitored)
- **Backup duration**: 5-10 minutes
- **Storage growth**: ~150MB per day
- **Compression ratio**: ~60% for SQL, ~40% for files

### Optimization Commands
```bash
# Compress old backups
gzip /var/www/baraka.sanaa.co/backups/*.sql

# Split large databases
mysqldump --user=root --password=${MYSQL_ROOT_PASSWORD} \
  --single-transaction --where="id <= 1000000" \
  baraka_logistics > /var/www/baraka.sanaa.co/backups/baraka_backup_part1.sql

# Check backup performance
time mysqldump --user=root --password=${MYSQL_ROOT_PASSWORD} \
  --single-transaction baraka_logistics > /dev/null