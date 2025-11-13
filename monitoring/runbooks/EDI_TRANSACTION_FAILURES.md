# EDI Transaction Failures Runbook

## Alert Definition
- **Threshold**: EDI transaction failure rate > 5%
- **Duration**: 10 minutes
- **Severity**: CRITICAL
- **Team**: integrations

## Immediate Actions (0-5 minutes)

### Check EDI Dashboard
```bash
# View EDI transaction metrics
curl "http://prometheus:9090/api/v1/query?query=rate(edi_transaction_failed_total[5m])"

# Check EDI transaction queue status
php artisan tinker
>>> DB::table('edi_transactions')->where('status', 'failed')->count()

# Check recent failed transactions
php artisan tinker
>>> DB::table('edi_transactions')
    ->where('created_at', '>', now()->subHour())
    ->where('status', 'failed')
    ->selectRaw('edi_type, count(*) as failures, MAX(created_at) as last_failure')
    ->groupBy('edi_type')
    ->get()
```

### Identify Transaction Type Failures
```bash
# Check specific EDI types (850, 856, 810, etc.)
tail -f storage/logs/edi.log | grep -i "failed\|error" | tail -20

# Check transaction syntax errors
grep -i "syntax\|validation" storage/logs/edi.log | tail -10

# Monitor real-time EDI processing
tail -f storage/logs/edi.log
```

## Diagnosis (5-15 minutes)

### Check Trading Partner Status
```bash
# Verify trading partner connections
php artisan tinker
>>> DB::table('trading_partners')
    ->where('status', 'active')
    ->select('partner_code', 'connection_status', 'last_successful_edi')
    ->get()

# Test connection to specific partner
php artisan edi:test-connection 12345
```

### Validate EDI Document Structure
```bash
# Check EDI document validation
php artisan edi:validate --document-id=<document_id>

# Validate syntax for specific EDI type
php artisan edi:validate --type=850 --file=edi_documents/received/850_20231111.txt

# Check EDI segment counts
php artisan edi:analyze --type=850 --document-path=edi_documents/processed/
```

### Network and AS2 Connectivity
```bash
# Test AS2 connection
curl -v --cert client.pem --key client.key \
  https://as2.partner.com/receive

# Check AS2 message ID tracking
grep -i "message.*id" storage/logs/edi.log | tail -5

# Test MLLP connection (if using HL7)
telnet mllp.partner.com 2575
```

## Resolution Steps

### Option 1: Retry Failed Transactions
```bash
# Retry specific failed transaction type
php artisan edi:retry --type=850 --status=failed

# Retry all failed transactions from last hour
php artisan edi:retry --since="1 hour ago" --status=failed

# Force retry with new message ID
php artisan edi:retry --force-new-id --type=856
```

### Option 2: Scale EDI Processing
```bash
# Increase EDI worker instances
kubectl scale deployment edi-processor --replicas=3

# Start dedicated workers for specific EDI types
php artisan queue:work --queue=edi-850,edi-856,edi-810 --workers=2
```

### Option 3: Partner Communication
```bash
# Generate partner notification email
php artisan edi:notify-partner --partner=12345 --issue="high_failure_rate"

# Create status report for partners
php artisan edi:status-report --period="last_hour" --format=json

# Send test EDI document
php artisan edi:send-test --type=850 --partner=12345
```

### Option 4: Emergency Batch Processing
```bash
# Switch to batch processing mode
php artisan edi:batch-mode --enable

# Process transactions in bulk
php artisan edi:process-batch --file=edi_documents/queue/batch_20231111.json

# Monitor batch processing status
php artisan edi:batch-status
```

## Critical EDI Types & Priority Resolution

### High Priority (Customer Shipments)
- **850 (Purchase Order)**: Blocks customer orders
- **856 (Ship Notice)**: Blocks shipment confirmations
- **810 (Invoice)**: Blocks payment processing

### Medium Priority (Inventory Updates)
- **846 (Inventory Advice)**: Affects stock levels
- **940 (Warehouse Shipping Order)**: Affects fulfillment

### Low Priority (Administrative)
- **997 (Functional ACK)**: Acknowledgment messages
- **820 (Payment Order)**: Payment processing

## Monitoring Recovery

### Verify Resolution
```bash
# Monitor failure rate drop
watch -n 5 'curl -s http://prometheus:9090/api/v1/query?query=rate(edi_transaction_failed_total[5m]) | jq'

# Check successful transaction processing
php artisan tinker --execute="echo DB::table('edi_transactions')->where('status', 'processed')->whereDate('created_at', today())->count();"

# Monitor transaction processing time
curl -s http://prometheus:9090/api/v1/query?query=edi_transaction_processing_time_seconds | jq
```

## Partner-Specific Troubleshooting

### Partner ID: 12345 (Primary Customer)
```bash
# Specific connection issues
php artisan edi:diagnose --partner=12345 --connection=as2

# Check partner-specific configurations
php artisan edi:partner-config --partner=12345

# Test partner integration
php artisan edi:test-integration --partner=12345 --type=850
```

### Partner ID: 67890 (Secondary Customer)
```bash
# MLLP connection testing
php artisan edi:test-mllp --partner=67890 --host=mltp.partner.com

# Check HL7 transaction status
php artisan edi:status --type=hl7 --partner=67890
```

## Post-Resolution

### Root Cause Analysis
1. **Document failure patterns**
   - Which EDI types failed most?
   - Which partners were affected?
   - What was the exact error message?

2. **Check infrastructure issues**
   - AS2 certificate expiry
   - Network connectivity problems
   - Trading partner system downtime
   - EDI document format changes

3. **Review transaction data**
   - Document structure validation
   - Required field compliance
   - Reference number accuracy

### Preventive Measures
1. **Implement EDI health monitoring**
2. **Add partner connection monitoring**
3. **Set up EDI document validation pipeline**
4. **Create EDI transaction rollback procedures**
5. **Establish partner communication protocols**

## Escalation

- **Level 1**: Integration team (immediate)
- **Level 2**: Platform team lead (if unresolved in 10 min)
- **Level 3**: Customer support (if customer orders blocked)
- **Level 4**: CTO (if multiple major customers affected)

## Emergency Contacts
- Primary Customer (Partner 12345): support@customer.com, +1-555-0123
- AS2 Provider: support@as2provider.com, +1-555-0124
- EDI Network Provider: help@edinetwork.com, +1-555-0125

## Dashboard Links
- [EDI Dashboard](https://grafana.baraka.com/d/edi-overview)
- [Transaction Status](https://grafana.baraka.com/d/edi-transactions)
- [Partner Performance](https://grafana.baraka.com/d/edi-partners)

## Related Alerts
- High Error Rate (EDI errors may cause general application errors)
- Queue Backlog Critical (EDI processing queue backup)
- Service Down (EDI processing service unavailable)