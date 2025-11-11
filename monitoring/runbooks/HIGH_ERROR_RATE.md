# High Error Rate Runbook

## Alert Definition
- **Threshold**: Error rate > 1% (5xx responses)
- **Duration**: 5 minutes
- **Severity**: CRITICAL

## Immediate Actions (0-5 minutes)

1. **Check Dashboard**
   ```bash
   # View Prometheus dashboard
   curl http://prometheus:9090/graph?query=rate(http_requests_total{status=%225..%22}[5m])
   ```

2. **Identify Error Source**
   ```bash
   # Check application logs
   tail -f storage/logs/laravel.log | grep -i error
   
   # Check queue status
   php artisan queue:failed
   ```

3. **Get Error Details**
   - Check Sentry: https://sentry.io/
   - Look for common patterns in exceptions
   - Identify affected endpoints/services

## Diagnosis (5-15 minutes)

### Database Issues
```bash
# Check MySQL connection status
mysql -u root -p -e "SHOW PROCESSLIST \G" | head -50

# Check slow queries
SHOW SLOWLOG;

# Check replication status
SHOW SLAVE STATUS\G
```

### Memory/CPU Issues
```bash
# Check system resources
top -b -n 1

# Check Docker container stats
docker stats --no-stream
```

### Queue Backlog
```bash
php artisan queue:work --verbose
php artisan tinker
>>> Queue::size()
```

### Recent Deployments
```bash
git log --oneline -10
# Check if recent changes correlate with alert time
```

## Resolution Steps

### Option 1: Scale Services
```bash
# Increase PHP-FPM workers
kubectl scale deployment laravel-app --replicas=5

# Increase queue workers
kubectl scale deployment queue-worker --replicas=3
```

### Option 2: Clear Queues
```bash
# Purge failed jobs
php artisan queue:flush

# Re-queue priority jobs
php artisan queue:retry all
```

### Option 3: Database Optimization
```bash
# Restart MySQL if hung
sudo systemctl restart mysql

# Check for long-running queries
SHOW FULL PROCESSLIST;
KILL QUERY <process_id>;
```

### Option 4: Rollback Deployment
```bash
kubectl rollout undo deployment/laravel-app
kubectl rollout status deployment/laravel-app
```

## Monitoring Recovery

```bash
# Watch error rate drop
watch -n 5 'curl -s http://prometheus:9090/api/v1/query?query=rate(http_requests_total{status=%225..%22}[5m]) | jq'

# Monitor until error rate < 0.001 for 5+ minutes
```

## Post-Resolution

1. **Document Root Cause**
   - Create incident report in Jira
   - Link to code changes/deployments

2. **Preventive Measures**
   - Add monitoring for new metrics
   - Implement circuit breakers
   - Add rate limiting

3. **Review & Improve**
   - Update alert thresholds if needed
   - Review on-call handoff
   - Schedule post-incident review

## Escalation

- **Level 1**: On-call engineer
- **Level 2**: Platform team lead (if unresolved in 15 min)
- **Level 3**: Engineering manager (if unresolved in 30 min)
- **Level 4**: CTO (customer impact >1 hour)

## Related Alerts
- Service Down
- High Latency
- Queue Backlog Critical
- Database Connection Pool Exhausted
