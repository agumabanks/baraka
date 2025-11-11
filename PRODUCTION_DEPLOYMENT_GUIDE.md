# Production Deployment & Operations Guide

## Pre-Deployment Checklist

### 1. Branch Seeding & Configuration
```bash
# Review branch configuration
cat config/seeders.php

# Dry-run seeding
php artisan seed:branches --dry-run

# Execute seeding in production
php artisan seed:branches --force

# Verify branches created
php artisan tinker
>>> App\Models\Backend\Branch::count()
```

### 2. Database & Migrations
```bash
# Review pending migrations
php artisan migrate:status

# Backup database before migration
php artisan backup:database --label=pre-deployment

# Run migrations
php artisan migrate --force

# Verify critical tables
php artisan tinker
>>> DB::table('branches')->count()
>>> DB::table('webhook_endpoints')->count()
```

### 3. Configuration & Environment
```bash
# Essential environment variables
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.sanaa.co

# Monitoring
PROMETHEUS_ENABLED=true
SENTRY_ENABLED=true
SENTRY_LARAVEL_DSN=https://...@sentry.io/...

# CORS configuration
CORS_ALLOWED_ORIGINS=https://branch.sanaa.co,https://client.sanaa.co

# Queue & Cache
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis

# Backup
BACKUP_ENABLED=true
BACKUP_SCHEDULE="0 2 * * *"
```

### 4. Webhook Setup
```bash
# Create webhook endpoints for external partners
php artisan tinker
>>> $webhook = App\Models\WebhookEndpoint::create([
  'name' => 'Partner Integration',
  'url' => 'https://partner.com/webhooks/baraka',
  'events' => ['shipment.created', 'shipment.updated', 'delivery.completed'],
  'active' => true,
])
>>> echo "Secret: " . $webhook->secret_key;
```

### 5. Mobile Scanning APIs
```bash
# Test mobile scanning endpoint
curl -X POST http://api.sanaa.co/api/v1/mobile/scan \
  -H "Authorization: Bearer {token}" \
  -H "X-Device-ID: {device-id}" \
  -d '{
    "tracking_number": "TRK123",
    "action": "inbound",
    "location_id": 1,
    "timestamp": "2025-11-10 15:30:00"
  }'
```

### 6. Monitoring & Alerting
```bash
# Deploy Prometheus rules
kubectl apply -f monitoring/alerting/alert-rules.yml

# Deploy Alertmanager config
kubectl apply -f monitoring/alerting/alertmanager.yml

# Test alert
kubectl port-forward svc/prometheus 9090:9090
# Visit http://localhost:9090/alerts
```

### 7. Disaster Recovery
```bash
# Create initial backup
php artisan backup:database --label=deployment

# Verify backup restore process
php artisan tinker
>>> $backups = app(App\Services\DisasterRecoveryService::class)->listBackups()
>>> echo count($backups) . " backups available"

# Schedule automated backups
# Scheduler runs: php artisan schedule:run
```

## Deployment Strategy

### Rolling Deployment
```bash
# 1. Create new deployment revision
kubectl set image deployment/laravel-app laravel-app=baraka:v1.0.0

# 2. Monitor rollout
kubectl rollout status deployment/laravel-app

# 3. Verify new pods
kubectl get pods -l app=laravel-app

# 4. Check error logs
kubectl logs -l app=laravel-app --tail=100 -f
```

### Rollback Procedure
```bash
# Check rollout history
kubectl rollout history deployment/laravel-app

# Rollback to previous version
kubectl rollout undo deployment/laravel-app

# Verify rollback
kubectl rollout status deployment/laravel-app
```

## Post-Deployment Verification

### 1. Health Checks
```bash
# Check application health
curl https://api.sanaa.co/api/health

# Check database
php artisan db:seed --class=TestDatabaseSeeder

# Check cache
php artisan cache:clear && php artisan cache:prune

# Check queue
php artisan queue:work --timeout=1 --max-jobs=1 --once
```

### 2. Integration Testing
```bash
# Test webhooks
curl -X POST http://api.sanaa.co/api/v1/webhooks/1/test

# Verify webhook delivery
php artisan tinker
>>> App\Models\WebhookDelivery::latest()->first()

# Test mobile scanning
# Use test device or curl command above

# Test EDI processing
curl -X POST http://api.sanaa.co/api/v1/edi/receive \
  -d @test-edi-850.json
```

### 3. Monitoring Setup
```bash
# Verify Prometheus scrape
curl http://prometheus:9090/api/v1/targets | jq '.data.activeTargets'

# Check Alertmanager
curl http://alertmanager:9093/api/v1/alerts | jq '.data'

# Verify Sentry integration
# Trigger test error: php artisan tinker >>> 1/0;
# Check Sentry dashboard
```

## Ongoing Operations

### Daily Tasks
```bash
# Monitor backups
php artisan backup:database

# Check queue health
php artisan queue:work --max-jobs=0 --timeout=60

# Review error logs
tail -f storage/logs/laravel.log | grep ERROR

# Monitor branch capacity
php artisan tinker
>>> App\Models\Backend\Branch::where('capacity_utilization', '>', 80)->get()
```

### Weekly Tasks
```bash
# Review webhook delivery health
php artisan tinker
>>> App\Models\WebhookEndpoint::where('failure_count', '>', 0)->get()

# Audit API usage
SELECT endpoint, COUNT(*) FROM api_logs GROUP BY endpoint ORDER BY COUNT(*) DESC

# Check database performance
SHOW SLOW LOG
```

### Monthly Tasks
```bash
# Database maintenance
OPTIMIZE TABLE shipments, branches, users

# Review monitoring thresholds
# Check if alerts are too noisy or missing important issues

# Test disaster recovery
# Perform backup restoration drill

# Review security
# Check for suspicious authentication patterns
# Review webhook secret rotation
```

## Troubleshooting

### High CPU Usage
```bash
# Identify slow queries
SET PROFILING=1;
SHOW PROFILES;

# Check slow query log
tail -f /var/log/mysql/slow.log

# Find expensive operations
php artisan tinker
>>> DB::enableQueryLog()
>>> // Run problematic request
>>> DB::getQueryLog()
```

### Memory Issues
```bash
# Check PHP memory usage
php -r "echo ini_get('memory_limit');"

# Monitor during request
php -d display_errors=On -r "ini_set('memory_limit', '256M');"

# Check for memory leaks
php artisan tinker
>>> memory_get_usage()
```

### Queue Processing Delays
```bash
# Check queue size
php artisan tinker
>>> DB::table('jobs')->count()

# Monitor processing rate
kubectl logs deployment/queue-worker --tail=50

# Increase workers if needed
kubectl scale deployment queue-worker --replicas=5
```

## Security Considerations

### API Security
- All endpoints require authentication
- Rate limiting enabled per endpoint
- CORS restrictions enforced
- Request signing with Idempotency-Key for mutations

### Data Security
- Database encrypted at rest
- SSL/TLS for all communications
- Webhook payloads signed with HMAC-SHA256
- Secrets rotated quarterly

### Access Control
- Role-based access control (RBAC)
- API keys rotated monthly
- Webhook secrets rotated on demand
- Audit logs for all sensitive operations

## Performance Optimization

### Caching Strategy
```bash
# Configure Redis
CACHE_DRIVER=redis
CACHE_DEFAULT_TTL=3600

# Cache branch data
Cache::remember('branches', 3600, fn() => Branch::all())

# Cache rate cards
Cache::remember('rate-cards', 86400, fn() => RateCard::all())
```

### Database Optimization
```bash
# Key indexes for common queries
CREATE INDEX idx_shipment_tracking ON shipments(tracking_number);
CREATE INDEX idx_shipment_status_date ON shipments(current_status, created_at);
CREATE INDEX idx_branch_capacity ON branches(current_load, capacity);

# Materialized views for analytics
CREATE VIEW branch_daily_stats AS
SELECT branch_id, DATE(created_at) as date, COUNT(*) as shipments
FROM shipments
GROUP BY branch_id, DATE(created_at);
```

### Query Optimization
```bash
# Use eager loading
$shipments = Shipment::with(['customer', 'branch', 'scans'])->paginate();

# Chunked processing
Shipment::chunk(100, function($shipments) {
    // Process each chunk
});
```

## Incident Response

### Severity Levels
- **P1 (Critical)**: Service down, data loss risk, customer impact >100
- **P2 (High)**: Significant degradation, customer impact 10-100
- **P3 (Medium)**: Minor issues, customer workarounds available
- **P4 (Low)**: Cosmetic issues, no customer impact

### Escalation Path
1. On-call engineer resolves
2. If unresolved in 15 min → Platform team lead
3. If unresolved in 30 min → Engineering manager
4. If unresolved in 1 hour → CTO + Customer Success

### Communication Protocol
- Slack: #incidents channel updates every 5 minutes
- Status page: Update if customer impact
- Customers: Proactive notification if > 5 min impact
