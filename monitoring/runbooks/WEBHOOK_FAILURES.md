# Webhook Failures Runbook

## Alert Definition
- **Threshold**: Webhook delivery failure rate > 5%
- **Duration**: 15 minutes
- **Severity**: WARNING
- **Team**: integrations

## Immediate Actions (0-5 minutes)

### Check Webhook Dashboard
```bash
# View webhook delivery metrics
curl "http://prometheus:9090/api/v1/query?query=rate(webhook_delivery_failed_total[5m])"

# Check webhook queue status
php artisan tinker
>>> DB::table('webhook_events')->where('status', 'pending')->count()
```

### Identify Failed Webhooks
```bash
# Check recent failed webhooks
tail -f storage/logs/webhooks.log | grep -i "failed\|error"

# Check specific endpoint failures
php artisan tinker
>>> DB::table('webhook_events')
    ->where('created_at', '>', now()->subHour())
    ->where('status', 'failed')
    ->groupBy('endpoint')
    ->selectRaw('endpoint, count(*) as failures')
    ->get()
```

## Diagnosis (5-15 minutes)

### Endpoint Availability
```bash
# Test webhook endpoints
curl -X POST https://webhook-endpoint.com/events \
  -H "Content-Type: application/json" \
  -d '{"event":"test","data":{}}' \
  -w "%{http_code}" \
  -o /dev/null

# Check SSL certificates
openssl s_client -connect webhook-endpoint.com:443 -servername webhook-endpoint.com < /dev/null 2>/dev/null | openssl x509 -noout -dates
```

### Network Connectivity
```bash
# Test from webhook server
nc -zv webhook-endpoint.com 443

# Check DNS resolution
nslookup webhook-endpoint.com

# Test network latency
ping webhook-endpoint.com
```

### Rate Limiting Issues
```bash
# Check if hitting rate limits
grep -i "429\|too.*many.*requests" storage/logs/webhooks.log

# Check rate limit headers in responses
curl -I -X POST https://webhook-endpoint.com/events \
  -H "Content-Type: application/json"
```

## Resolution Steps

### Option 1: Increase Retry Attempts
```php
// Update webhook configuration
php artisan config:clear
php artisan config:cache

// Set higher retry attempts
DB::table('webhook_endpoints')
  ->where('endpoint', 'https://problematic-endpoint.com/webhooks')
  ->update(['max_attempts' => 5, 'retry_delay' => 300]);
```

### Option 2: Adjust Rate Limits
```bash
# Create custom webhook job with exponential backoff
php artisan make:job WebhookRetryJob

# Update webhook retry logic with delays
// resources/views/webhook/retry.blade.php
@if($attempt <= 3)
    <!-- 1st retry: 30s, 2nd: 2m, 3rd: 15m -->
@endif
```

### Option 3: Pause Problematic Endpoints
```bash
# Temporarily disable problematic endpoints
php artisan tinker
>>> DB::table('webhook_endpoints')
    ->where('endpoint', 'https://failing-endpoint.com')
    ->update(['active' => false]);

# Queue manual review of failed events
php artisan queue:work --queue=webhook-review
```

### Option 4: Scale Webhook Processing
```bash
# Increase webhook worker instances
kubectl scale deployment webhook-worker --replicas=5

# Or use Laravel Horizon for better queue management
php artisan horizon:scale --workers=5
```

## Monitoring Recovery

### Verify Resolution
```bash
# Watch failure rate drop
watch -n 5 'curl -s http://prometheus:9090/api/v1/query?query=rate(webhook_delivery_failed_total[5m]) | jq'

# Monitor pending queue reduction
php artisan tinker --execute="echo DB::table(\"webhook_events\")->where('status', 'pending')->count();"

# Check successful deliveries
curl -s http://prometheus:9090/api/v1/query?query=rate(webhook_delivery_success_total[5m]) | jq
```

## Post-Resolution

### Root Cause Analysis
1. **Document the failure pattern**
   - Which endpoints were failing?
   - What was the exact error?
   - How long did the issue persist?

2. **Check for infrastructure issues**
   - DNS problems
   - SSL certificate expiry
   - Network outages
   - Rate limiting by target service

3. **Review webhook payload**
   - Size limits exceeded?
   - Invalid JSON format?
   - Missing required headers?

### Preventive Measures
1. **Implement health checks for webhook endpoints**
2. **Add webhook endpoint monitoring**
3. **Set up circuit breakers for failing endpoints**
4. **Implement webhook delivery tracking dashboard**
5. **Add webhook payload validation**

## Escalation

- **Level 1**: Integration team (if unresolved in 30 min)
- **Level 2**: Platform team lead (if customer impact > 1 hour)
- **Level 3**: Engineering manager (if multiple customers affected)

## Related Alerts
- High Error Rate (may be caused by webhook failures)
- Queue Backlog Critical (may be backlogged webhooks)
- Service Down (webhook processing service down)

## Dashboard Links
- [Webhook Dashboard](https://grafana.baraka.com/d/webhook-overview)
- [Integration Metrics](https://grafana.baraka.com/d/integration-overview)

## Contact Information
- Integration Team: #integrations-alerts
- Platform Team: #platform-critical
- On-call Engineer: [PagerDuty](https://baraka.pagerduty.com)