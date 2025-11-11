# Queue Backlog Runbook

## Alert Definition
- **Threshold**: Queue length > 10,000 jobs
- **Duration**: 5 minutes
- **Severity**: CRITICAL

## Quick Diagnosis

```bash
# Check queue size
php artisan tinker
>>> DB::table('jobs')->count()

# Check specific queues
>>> DB::table('jobs')->groupBy('queue')->selectRaw('queue, count(*) as count')->get()

# Check failed jobs
>>> DB::table('failed_jobs')->count()

# Check queue processing rate
>>> DB::table('jobs')->whereDate('created_at', today())->count()
```

## Resolution Steps

### Step 1: Increase Worker Concurrency
```bash
# Kubernetes
kubectl scale deployment queue-worker --replicas=10
kubectl get pods -l app=queue-worker

# Docker Compose
docker-compose scale queue-worker=10

# Laravel Horizon (if using Horizon)
php artisan horizon:scale 20
```

### Step 2: Prioritize Critical Jobs
```bash
# Process high-priority queue first
php artisan queue:work redis --queue=webhooks,notifications,shipment-updates,default

# Split into separate workers
kubectl exec -it queue-worker-pod -- php artisan queue:work --queue=webhooks --timeout=600
kubectl exec -it queue-worker-pod -- php artisan queue:work --queue=notifications --timeout=300
```

### Step 3: Identify Long-Running Jobs
```bash
php artisan tinker
>>> $jobs = DB::table('jobs')->orderBy('created_at')->limit(10)->get();
>>> foreach ($jobs as $job) {
  echo json_decode($job->payload)->displayName . "\n";
}
```

### Step 4: Retry Failed Jobs
```bash
# Check failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job ID
php artisan queue:retry 1 2 3
```

## Prevention

### Monitor Queue Depth Continuously
```bash
# Add to Prometheus scraping
# /monitoring/prometheus/prometheus.yml

scrape_configs:
  - job_name: 'queue-metrics'
    static_configs:
      - targets: ['localhost:9090']
```

### Auto-Scaling Policy
```yaml
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: queue-worker-autoscaler
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: queue-worker
  minReplicas: 3
  maxReplicas: 50
  metrics:
  - type: Pods
    pods:
      metricName: queue_length
      targetAverageValue: 1000
```

## Recovery Verification

```bash
# Monitor backlog reduction
watch -n 5 'php artisan tinker --execute="echo DB::table(\"jobs\")->count();"'

# Check processing rate
php artisan queue:work --verbose

# Alert should clear when queue < 10,000 and stable
```

## Related Alerts
- High Error Rate (may be cause)
- Service Down (may cause)
- Webhook Delivery Failure
