# Mobile Scanning Issues Runbook

## Alert Definition
- **Threshold**: Mobile scanning accuracy < 95% OR scanning service down
- **Duration**: 5 minutes (downtime) / 30 minutes (accuracy)
- **Severity**: CRITICAL
- **Team**: operations

## Immediate Actions (0-5 minutes)

### Check Mobile Scanning Dashboard
```bash
# View scanning accuracy metrics
curl "http://prometheus:9090/api/v1/query?query=scan_accuracy_percentage"

# Check scanning service status
php artisan mobile:status

# Check active mobile sessions
php artisan tinker
>>> DB::table('mobile_sessions')
    ->where('last_activity', '>', now()->subMinutes(5))
    ->count()
```

### Verify Service Health
```bash
# Check PWA service worker status
curl -s https://mobile.baraka.com/service-worker.js | head -10

# Test barcode scanning API
curl -X POST https://api.baraka.com/scan \
  -H "Content-Type: application/json" \
  -d '{"barcode":"1234567890123","branch_id":1}'

# Check WebSocket connections
netstat -an | grep :6001 | wc -l
```

## Diagnosis (5-15 minutes)

### Mobile App Performance
```bash
# Check PWA installation status
curl -s https://mobile.baraka.com/manifest.json

# Test offline functionality
curl -s https://mobile.baraka.com/offline

# Check mobile-specific error logs
tail -f storage/logs/mobile.log | grep -i "scan\|offline\|connection"
```

### Barcode Recognition Issues
```bash
# Check barcode processing metrics
php artisan tinker
>>> DB::table('scan_events')
    ->where('created_at', '>', now()->subHour())
    ->selectRaw('scan_result, count(*) as count, 
                AVG(LENGTH(barcode_data)) as avg_length')
    ->groupBy('scan_result')
    ->get()

# Verify barcode database
php artisan tinker
>>> DB::table('barcodes')
    ->where('barcode', '1234567890123')
    ->first()
```

### Network and Connectivity
```bash
# Check mobile network latency
ping -c 5 mobile.baraka.com

# Test WebSocket connection from mobile
curl -I wss://api.baraka.com/ws

# Check push notification service
curl -X POST https://fcm.googleapis.com/fcm/send \
  -H "Authorization: key=${FCM_SERVER_KEY}" \
  -d '{"to":"/topics/mobile","notification":{"title":"Test"}}'
```

## Resolution Steps

### Option 1: Restart Mobile Services
```bash
# Restart PWA service
kubectl rollout restart deployment/mobile-pwa

# Restart barcode processing service
kubectl rollout restart deployment/barcode-processor

# Clear service worker cache
php artisan mobile:clear-cache
```

### Option 2: Scale Mobile Infrastructure
```bash
# Increase barcode processing workers
kubectl scale deployment barcode-processor --replicas=3

# Scale WebSocket connections
kubectl scale deployment websocket --replicas=5

# Start additional mobile sync workers
php artisan queue:work --queue=mobile-sync --workers=2
```

### Option 3: Fix Barcode Recognition
```bash
# Update barcode recognition model
php artisan mobile:update-barcode-model

# Retrain with recent scan data
php artisan mobile:retrain-scanner --data-days=7

# Test barcode recognition
php artisan mobile:test-scanner --sample-barcodes=barcode_samples.json
```

### Option 4: Offline Mode Issues
```bash
# Clear offline storage
php artisan mobile:clear-offline-data

# Rebuild PWA cache
php artisan mobile:rebuild-pwa-cache

# Test offline functionality
php artisan mobile:test-offline --branch=1
```

## Mobile-Specific Troubleshooting

### iOS Issues
```bash
# Check iOS-specific service worker
curl -s -H "User-Agent: iPhone Safari" https://mobile.baraka.com/manifest.json

# Test iOS camera access
php artisan mobile:test-camera-ios --device-id=<device_id>

# Check iOS background sync
php artisan mobile:ios-background-sync-status
```

### Android Issues
```bash
# Test Android camera permissions
php artisan mobile:test-camera-android --package=com.baraka.mobile

# Check Android PWA install prompt
php artisan mobile:test-pwa-install-android

# Verify Android push notifications
php artisan mobile:test-push-android --token=<fcm_token>
```

## Branch-Specific Issues

### Branch 1 (Main Hub)
```bash
# Check hub scanning operations
php artisan mobile:branch-status --branch=1 --detail

# Verify hub barcode database sync
php artisan mobile:sync-status --branch=1

# Test hub offline operations
php artisan mobile:test-offline --branch=1 --duration=300
```

### Branch 2-16 (Local Branches)
```bash
# Check all branch sync status
php artisan mobile:all-branches-sync-status

# Identify problematic branches
php artisan mobile:problematic-branches --hours=1

# Force sync specific branch
php artisan mobile:force-sync --branch=2
```

## Monitoring Recovery

### Verify Resolution
```bash
# Monitor scanning accuracy recovery
watch -n 5 'curl -s http://prometheus:9090/api/v1/query?query=scan_accuracy_percentage | jq'

# Check mobile service uptime
curl -s http://prometheus:9090/api/v1/query?query=up{job="mobile-pwa"} | jq

# Monitor real-time scanning success
php artisan tinker --execute="echo DB::table('scan_events')->where('scan_result', 'success')->whereDate('created_at', now())->count();"
```

## Post-Resolution

### Root Cause Analysis
1. **Document the issue**
   - Which mobile device types affected?
   - What type of barcodes failed?
   - How many branches impacted?

2. **Check infrastructure issues**
   - Camera permission problems
   - Network connectivity issues
   - PWA cache corruption
   - WebSocket connection problems

3. **Review user experience**
   - App crash rates
   - User feedback and complaints
   - Branch worker reports

### Preventive Measures
1. **Implement mobile health monitoring**
2. **Add barcode recognition model monitoring**
3. **Set up PWA performance tracking**
4. **Create mobile fallback procedures**
5. **Establish mobile user feedback collection**

## Escalation

- **Level 1**: Operations team (immediate)
- **Level 2**: Platform team (if mobile app crashes)
- **Level 3**: Product team (if user experience issues)
- **Level 4**: CTO (if business operations severely impacted)

## Emergency Contacts
- Mobile App Developer: mobile-dev@baraka.com
- PWA Support: pwa-support@baraka.com
- Branch Operations: branch-ops@baraka.com

## Dashboard Links
- [Mobile Scanning Dashboard](https://grafana.baraka.com/d/mobile-scanning)
- [Branch Operations](https://grafana.baraka.com/d/branch-operations)
- [PWA Performance](https://grafana.baraka.com/d/pwa-metrics)

## Related Alerts
- High Error Rate (mobile scanning errors affect overall system)
- Queue Backlog Critical (sync queue backup)
- Service Down (PWA service unavailable)
- Branch Capacity Exceeded (scanning delays affect operations)

## Test Commands for Verification
```bash
# Test complete scanning workflow
php artisan mobile:test-workflow --branch=1 --scan-count=10

# Test offline synchronization
php artisan mobile:test-sync --duration=600

# Test push notifications
php artisan mobile:test-push --branch=1 --message="Test notification"