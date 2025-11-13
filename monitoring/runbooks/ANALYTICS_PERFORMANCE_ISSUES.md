# Analytics Performance Issues Runbook

## Alert Definition
- **Threshold**: Analytics query response time > 2 seconds OR cache hit rate < 85%
- **Duration**: 5 minutes (response time) / 15 minutes (cache hit rate)
- **Severity**: WARNING
- **Team**: platform

## Immediate Actions (0-5 minutes)

### Check Analytics Dashboard
```bash
# View analytics response time
curl "http://prometheus:9090/api/v1/query?query=analytics_query_duration_seconds{quantile=\"0.95\"}"

# Check cache hit rate
curl "http://prometheus:9090/api/v1/query?query=analytics_cache_hit_rate"

# Check active analytics queries
php artisan tinker
>>> DB::table('analytics_queries')
    ->where('created_at', '>', now()->subMinutes(5))
    ->where('status', 'running')
    ->count()
```

### Verify Cache Status
```bash
# Check Redis cache status
redis-cli info stats | grep keyspace

# Check analytics cache usage
php artisan analytics:cache-status

# Verify cache keys and their TTL
redis-cli keys "analytics:*" | head -10
redis-cli ttl analytics:dashboard:kpi_summary
```

## Diagnosis (5-15 minutes)

### Database Query Performance
```bash
# Check slow analytics queries
mysql -e "SELECT * FROM mysql.slow_log WHERE start_time > NOW() - INTERVAL 1 HOUR ORDER BY start_time DESC LIMIT 20;"

# Check analytics table indexes
php artisan analytics:check-indexes

# Analyze query performance
php artisan analytics:query-plan --query-id=<query_id>
```

### Cache Analysis
```bash
# Check cache memory usage
redis-cli info memory

# Analyze cache patterns
php artisan analytics:cache-analysis --period=1h

# Check cache warming status
tail -f storage/logs/analytics.log | grep -i "cache.*warm\|cache.*miss"
```

### Data Processing Pipeline
```bash
# Check ETL pipeline status
php artisan etl:status

# Monitor data ingestion rate
curl -s http://prometheus:9090/api/v1/query?query=rate(analytics_data_ingestion_total[5m]) | jq

# Check data freshness
php artisan analytics:data-freshness
```

## Resolution Steps

### Option 1: Clear and Rebuild Cache
```bash
# Clear problematic cache entries
php artisan analytics:clear-cache --type=slow_queries

# Rebuild dashboard cache
php artisan analytics:rebuild-dashboard-cache

# Warm up critical analytics queries
php artisan analytics:warm-cache --dashboards=executive,kpi,operations
```

### Option 2: Scale Analytics Infrastructure
```bash
# Increase analytics workers
kubectl scale deployment analytics-processor --replicas=3

# Scale Redis cluster for cache
kubectl scale statefulset redis-analytics --replicas=3

# Start dedicated cache warming workers
php artisan queue:work --queue=analytics-cache --workers=2
```

### Option 3: Optimize Database Queries
```bash
# Rebuild analytics indexes
php artisan analytics:rebuild-indexes

# Update query optimization
php artisan analytics:optimize-queries --dashboard=executive

# Check for deadlocks or long transactions
mysql -e "SHOW ENGINE INNODB STATUS\G" | grep -A 10 "TRANSACTION"
```

### Option 4: Emergency Data Processing
```bash
# Switch to real-time processing
php artisan analytics:mode --processing=realtime

# Process pending analytics data
php artisan analytics:process-pending --priority=critical

# Generate simplified reports
php artisan analytics:generate-simple-reports --period=last_hour
```

## Specific Analytics System Issues

### Executive Dashboard Performance
```bash
# Check executive dashboard query performance
php artisan analytics:query-timing --dashboard=executive

# Optimize executive KPIs
php artisan analytics:optimize-kpi --type=executive

# Cache executive dashboard data
php artisan analytics:cache-executive --force
```

### Real-time Metrics Issues
```bash
# Check WebSocket analytics connection
curl -I wss://api.baraka.com/analytics-ws

# Monitor real-time data streaming
tail -f storage/logs/realtime.log | grep -i "analytics\|stream"

# Restart real-time analytics service
kubectl rollout restart deployment/realtime-analytics
```

### Branch Analytics Problems
```bash
# Check branch data synchronization
php artisan analytics:branch-sync-status

# Verify branch-specific analytics
php artisan analytics:branch-data --branch=1

# Force branch data refresh
php artisan analytics:refresh-branch --branch=1
```

## Cache-Specific Troubleshooting

### Redis Cache Issues
```bash
# Check Redis cluster health
redis-cli cluster nodes

# Monitor Redis performance
redis-cli --latency-history

# Clear analytics cache completely
php artisan analytics:cache-flush
```

### In-Memory Cache Issues
```bash
# Check PHP OPcache status
php -i | grep opcache

# Clear application cache
php artisan cache:clear

# Check memory usage
free -h
ps aux | grep php
```

## Data Pipeline Issues

### ETL Pipeline Failures
```bash
# Check ETL job status
php artisan etl:job-status

# Retry failed ETL jobs
php artisan etl:retry --since="2 hours ago"

# Check data validation errors
php artisan etl:validation-errors --period=1h
```

### Data Freshness Problems
```bash
# Check last data update timestamps
php artisan analytics:last-updates

# Verify data sources connectivity
php artisan analytics:test-sources

# Manual data refresh
php artisan analytics:refresh-all --source=database
```

## Monitoring Recovery

### Verify Resolution
```bash
# Monitor query response time improvement
watch -n 5 'curl -s http://prometheus:9090/api/v1/query?query=analytics_query_duration_seconds{quantile="0.95"} | jq'

# Check cache hit rate recovery
curl -s http://prometheus:9090/api/v1/query?query=analytics_cache_hit_rate | jq

# Monitor user dashboard load times
php artisan analytics:user-experience-metrics
```

## Post-Resolution

### Root Cause Analysis
1. **Document performance degradation**
   - Which analytics modules were slowest?
   - What data volume caused the issue?
   - How did cache performance change?

2. **Check infrastructure issues**
   - Database query optimization needs
   - Cache memory allocation
   - Network latency to data sources

3. **Review user impact**
   - Executive dashboard accessibility
   - Real-time metrics availability
   - Branch analytics performance

### Preventive Measures
1. **Implement analytics performance monitoring**
2. **Add query optimization alerts**
3. **Set up cache performance tracking**
4. **Create analytics SLA monitoring**
5. **Establish performance baseline testing**

## Escalation

- **Level 1**: Platform team (immediate)
- **Level 2**: Data engineering team (if data issues)
- **Level 3**: Business intelligence team (if reporting affected)
- **Level 4**: CTO (if executive decisions impacted)

## Emergency Contacts
- Data Engineering: data-eng@baraka.com
- Business Intelligence: bi-team@baraka.com
- Executive Support: exec-support@baraka.com

## Dashboard Links
- [Analytics Performance](https://grafana.baraka.com/d/analytics-performance)
- [Cache Performance](https://grafana.baraka.com/d/cache-metrics)
- [Data Pipeline Status](https://grafana.baraka.com/d/etl-pipeline)
- [Executive Dashboard Health](https://grafana.baraka.com/d/executive-health)

## Related Alerts
- High Response Time (affects all analytics)
- Database Query Performance (underlying cause)
- High Memory Usage (cache performance)
- Low Cache Hit Rate (specific analytics issue)

## Performance Test Commands
```bash
# Test complete analytics pipeline
php artisan analytics:test-pipeline --duration=300

# Benchmark cache performance
php artisan analytics:benchmark-cache

# Test query optimization
php artisan analytics:test-queries --type=executive

# Verify real-time analytics
php artisan analytics:test-realtime --duration=60