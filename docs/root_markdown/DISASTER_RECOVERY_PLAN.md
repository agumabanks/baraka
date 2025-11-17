# Baraka Logistics Platform - Disaster Recovery Plan

## Executive Summary
This document outlines the comprehensive disaster recovery plan for the Baraka Logistics Platform, ensuring business continuity in the event of system failures, data corruption, or infrastructure disasters.

## Disaster Recovery Objectives

### Recovery Time Objectives (RTO)
- **Critical Systems (API, Database)**: 30 minutes
- **Branch Operations**: 1 hour
- **Mobile Scanning**: 2 hours
- **Analytics Dashboard**: 4 hours
- **Non-critical Systems**: 8 hours

### Recovery Point Objectives (RPO)
- **Database (Transactions)**: 5 minutes
- **File Storage**: 24 hours
- **Configuration**: 7 days
- **Analytics Data**: 1 hour

## Disaster Scenarios and Response Plans

### Scenario 1: Primary Database Failure
**Impact**: Complete system unavailability
**RTO**: 30 minutes | **RPO**: 5 minutes

#### Immediate Response (0-15 minutes)
1. **Detect failure**
   ```bash
   # Check primary database connectivity
   mysql --user=root --password=${MYSQL_ROOT_PASSWORD} -e "SELECT 1;"
   
   # Check application error logs
   tail -f storage/logs/laravel.log | grep -i "database\|connection"
   ```

2. **Verify replication status**
   ```bash
   # Check MySQL replication on replica
   mysql --user=replica_user -e "SHOW SLAVE STATUS\G"
   
   # Promote replica to primary
   mysql --user=root -e "STOP SLAVE; RESET SLAVE ALL;"
   mysql --user=root -e "FLUSH PRIVILEGES;"
   ```

3. **Update application configuration**
   ```bash
   # Switch application to new primary database
   sed -i "s/DB_HOST=.*/DB_HOST=new-primary-db/" .env
   php artisan config:clear
   php artisan cache:clear
   ```

#### Recovery (15-30 minutes)
1. **Verify application functionality**
   ```bash
   # Test critical endpoints
   curl -f https://api.baraka.com/health
   curl -f https://api.baraka.com/api/branches
   ```

2. **Monitor system health**
   ```bash
   # Check error rates
   curl "http://prometheus:9090/api/v1/query?query=rate(http_requests_total{status=~"5.."}[5m])"
   ```

### Scenario 2: Complete Infrastructure Failure
**Impact**: All services unavailable
**RTO**: 2 hours | **RPO**: 1 hour

#### Immediate Response (0-30 minutes)
1. **Assess scope of damage**
   - Check monitoring dashboards
   - Verify cloud provider status
   - Contact infrastructure team

2. **Activate disaster recovery site**
   ```bash
   # Switch DNS to DR site
   aws route53 change-resource-record-sets --hosted-zone-id Z123456 \
     --change-batch file://dns-failover.json
   
   # Start services on DR site
   kubectl apply -f kubernetes/dr-manifests/
   ```

3. **Verify DR site functionality**
   ```bash
   # Test DR site health
   curl -f https://dr.baraka.com/health
   curl -f https://dr.baraka.com/api/branches
   ```

#### Recovery (30-120 minutes)
1. **Restore data from backups**
   ```bash
   # Restore latest database backup
   mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
     baraka_logistics < /backups/latest/baraka_backup_$(date +%Y%m%d).sql
   
   # Restore file storage
   aws s3 sync s3://baraka-backups/production/latest/ /var/www/baraka.sanaa.co/storage/
   ```

2. **Validate critical data**
   ```bash
   # Verify branch data
   php artisan tinker
   >>> DB::table('branches')->count()  // Should be 16
   >>> DB::table('shipments')->whereDate('created_at', today())->count()
   ```

### Scenario 3: Data Corruption
**Impact**: Data integrity issues
**RTO**: 1 hour | **RPO**: 1 hour

#### Immediate Response (0-15 minutes)
1. **Identify affected data**
   ```bash
   # Check data consistency
   php artisan tinker
   >>> DB::table('shipments')->whereNull('tracking_number')->count()
   >>> DB::table('branches')->whereNull('code')->count()
   ```

2. **Stop all write operations**
   ```bash
   # Enable maintenance mode
   php artisan down
   
   # Disable webhooks temporarily
   php artisan webhook:pause-all
   ```

#### Recovery (15-60 minutes)
1. **Restore from backup**
   ```bash
   # Restore specific table from backup
   mysql --user=root --password=${MYSQL_ROOT_PASSWORD} \
     baraka_logistics < /backups/corrupted_table_backup.sql
   ```

2. **Verify data integrity**
   ```bash
   # Run data integrity checks
   php artisan db:check-integrity
   
   # Validate business rules
   php artisan validate:business-rules
   ```

### Scenario 4: Security Breach
**Impact**: Potential data exposure
**RTO**: 2 hours | **RPO**: 1 hour

#### Immediate Response (0-30 minutes)
1. **Isolate affected systems**
   ```bash
   # Block external access
   iptables -A INPUT -j DROP
   
   # Revoke all API keys
   php artisan api:revoke-all-keys
   ```

2. **Notify stakeholders**
   ```bash
   # Send security alert
   php artisan notify:security-breach
   
   # Document incident
   php artisan incident:create --type=security --severity=critical
   ```

#### Recovery (30-120 minutes)
1. **Audit compromised data**
   ```bash
   # Check for unauthorized access
   php artisan security:audit-access-logs
   
   # Review user permissions
   php artisan security:audit-permissions
   ```

2. **Implement security patches**
   ```bash
   # Update security configurations
   php artisan security:update-configs
   
   # Reset all user sessions
   php artisan security:invalidate-sessions
   ```

## Disaster Recovery Infrastructure

### Primary Site (Production)
- **Location**: AWS us-west-2
- **Database**: MySQL 8.0 with read replicas
- **Application**: Laravel 10.x on Kubernetes
- **Storage**: S3 with lifecycle policies

### Disaster Recovery Site
- **Location**: AWS us-east-1
- **Database**: MySQL 8.0 (warm standby)
- **Application**: Kubernetes cluster (standby)
- **Storage**: S3 cross-region replication

### Data Synchronization
```bash
# Database replication (MySQL)
# Primary -> Replica: Real-time via binlog replication
# Replica -> DR: Hourly via delayed replication (1 hour delay)

# File storage synchronization
aws s3 sync s3://baraka-backups/production/ s3://baraka-backups/dr/ --delete

# Configuration management
git push origin dr-site-updates
```

## Emergency Contacts and Escalation

### Primary Contacts
- **On-call Engineer**: [PagerDuty](https://baraka.pagerduty.com)
- **Platform Team Lead**: platform-lead@baraka.com, +1-555-0123
- **DevOps Lead**: devops-lead@baraka.com, +1-555-0124

### Escalation Path
1. **Level 1**: On-call engineer (0-15 minutes)
2. **Level 2**: Platform team lead (15-30 minutes)
3. **Level 3**: Engineering manager (30-60 minutes)
4. **Level 4**: CTO and CEO (60+ minutes)

### External Vendors
- **AWS Support**: 1-800-AWS-SUPPORT
- **Database Vendor**: db-support@vendor.com, +1-555-0125
- **Security Consultant**: security@consulting.com, +1-555-0126

## Testing and Validation

### Monthly DR Tests
```bash
# Schedule monthly DR drill
# First Monday of each month, 2:00 AM

# 1. Test backup restoration
php artisan backup:verify --latest

# 2. Test DR site activation
./scripts/activate-dr-site.sh

# 3. Test failback procedures
./scripts/test-failback.sh

# 4. Document test results
php artisan dr:test-report --format=markdown
```

### Test Scenarios
1. **Database failover test** (Monthly)
2. **Complete site failover test** (Quarterly)
3. **Data corruption recovery test** (Quarterly)
4. **Security incident response test** (Annually)

## Post-Incident Activities

### Immediate Post-Incident (0-2 hours)
1. **Document incident details**
   - Timeline of events
   - Actions taken
   - Data loss assessment
   - Business impact

2. **Communicate with stakeholders**
   - Customer notifications (if applicable)
   - Internal team updates
   - Management briefings

### Short-term Post-Incident (2-24 hours)
1. **Root cause analysis**
   - Technical investigation
   - Process review
   - Contributing factors

2. **Implement temporary fixes**
   - Monitoring improvements
   - Process adjustments
   - Team training

### Long-term Improvements (1-30 days)
1. **Permanent solutions**
   - Infrastructure improvements
   - Process enhancements
   - Tool upgrades

2. **Update documentation**
   - Runbook updates
   - Training materials
   - Contact information

## Communication Templates

### Internal Stakeholder Notification
```markdown
# Incident Notification

**Incident ID**: INC-20251111-001
**Severity**: Critical
**Status**: Resolved
**Duration**: 45 minutes
**Impact**: All services unavailable

**Timeline**:
- 13:15 UTC: Incident detected
- 13:35 UTC: Database failover completed
- 14:00 UTC: All services restored

**Root Cause**: Primary database server hardware failure
**Resolution**: Automatic failover to replica database

**Next Steps**:
- Hardware replacement scheduled for [date]
- Additional monitoring alerts implemented
- Incident review meeting scheduled for [date]
```

### Customer Communication
```markdown
# Service Interruption Notice

Dear Valued Customer,

We experienced a brief service interruption on [date] from [time] to [time] UTC due to infrastructure issues. All services have been restored and we are implementing additional safeguards to prevent similar occurrences.

We apologize for any inconvenience this may have caused. If you have any questions, please contact our support team at support@baraka.com.

Baraka Logistics Team
```

## Monitoring and Metrics

### DR Health Metrics
- **Backup Success Rate**: 99.9%
- **Recovery Time**: Average 25 minutes
- **Data Loss**: <5 minutes
- **Test Success Rate**: 95%

### Alert Thresholds
- **Backup Failure**: Immediate alert
- **Replication Lag**: >5 minutes alert
- **DR Site Unavailable**: Immediate alert
- **Data Integrity Issues**: Immediate alert

## Compliance and Audit

### Regulatory Requirements
- **Data Retention**: 7 years for financial records
- **Audit Trail**: All system changes logged
- **Access Control**: Role-based access enforcement
- **Encryption**: Data encrypted at rest and in transit

### Audit Procedures
- **Weekly**: Backup verification
- **Monthly**: DR site health check
- **Quarterly**: Full DR drill
- **Annually**: Third-party security audit

## Continuous Improvement

### Review Process
- **Post-incident reviews** within 48 hours
- **Monthly DR metrics review**
- **Quarterly plan updates**
- **Annual comprehensive review**

### Improvement Initiatives
- **Automation enhancements**
- **Monitoring improvements**
- **Training programs**
- **Technology upgrades**