# Database Schema Documentation

## Overview

The Analytics Platform uses a star schema design for optimal analytical performance with comprehensive data warehouse architecture supporting multi-dimensional reporting and analytics.

## Architecture

### Data Warehouse Structure

The data warehouse follows a **star schema** design with:
- **Fact Tables**: Central tables containing measurements and business events
- **Dimension Tables**: Descriptive tables providing context for fact data
- **Staging Tables**: Temporary storage for ETL processing

## Fact Tables

### 1. fact_shipments

Central fact table containing all shipment-related metrics and business events.

```sql
CREATE TABLE fact_shipments (
    shipment_key BIGINT PRIMARY KEY,
    tracking_number VARCHAR(50) NOT NULL,
    shipment_id BIGINT NOT NULL,
    
    -- Foreign Keys to Dimensions
    client_key BIGINT NOT NULL,
    origin_branch_key BIGINT NOT NULL,
    dest_branch_key BIGINT NOT NULL,
    customer_key BIGINT NOT NULL,
    pickup_date_key INT NOT NULL,
    delivery_date_key INT,
    scheduled_delivery_date_key INT,
    service_type_key BIGINT NOT NULL,
    
    -- Measures
    declared_value DECIMAL(12,2) DEFAULT 0.00,
    shipping_charge DECIMAL(10,2) DEFAULT 0.00,
    cod_amount DECIMAL(10,2) DEFAULT 0.00,
    fuel_surcharge DECIMAL(10,2) DEFAULT 0.00,
    insurance_cost DECIMAL(10,2) DEFAULT 0.00,
    total_cost DECIMAL(10,2) DEFAULT 0.00,
    revenue DECIMAL(10,2) DEFAULT 0.00,
    margin DECIMAL(10,2) DEFAULT 0.00,
    margin_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Physical Metrics
    weight_kg DECIMAL(8,3) DEFAULT 0.000,
    distance_km DECIMAL(10,2) DEFAULT 0.00,
    delivery_attempts INT DEFAULT 1,
    delivery_duration_minutes INT,
    scheduled_delivery_duration_minutes INT,
    
    -- Flags and Status
    status VARCHAR(20) NOT NULL,
    is_cod BOOLEAN DEFAULT FALSE,
    is_insured BOOLEAN DEFAULT FALSE,
    is_expedited BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    source_system VARCHAR(50) NOT NULL,
    etl_batch_id VARCHAR(100)
);
```

**Indexes:**
- Primary key on `shipment_key`
- Composite index on `(client_key, pickup_date_key)`
- Index on `(origin_branch_key, pickup_date_key)`
- Index on `(dest_branch_key, delivery_date_key)`
- Unique index on `tracking_number`

**Partitioning:**
- Range partition by `pickup_date_key` (monthly partitions)
- Subpartition by `client_key` hash

### 2. fact_financial_transactions

Financial transaction records for revenue, expenses, and margin analysis.

```sql
CREATE TABLE fact_financial_transactions (
    transaction_key BIGINT PRIMARY KEY,
    transaction_id BIGINT NOT NULL,
    
    -- Foreign Keys
    client_key BIGINT NOT NULL,
    branch_key BIGINT NOT NULL,
    shipment_key BIGINT,
    transaction_date_key INT NOT NULL,
    transaction_type_key BIGINT NOT NULL,
    account_key BIGINT NOT NULL,
    
    -- Financial Measures
    debit_amount DECIMAL(12,2) DEFAULT 0.00,
    credit_amount DECIMAL(12,2) DEFAULT 0.00,
    running_balance DECIMAL(15,2) DEFAULT 0.00,
    
    -- Transaction Details
    description TEXT,
    reference_number VARCHAR(50),
    is_reconciled BOOLEAN DEFAULT FALSE,
    reconciliation_date DATE,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    source_system VARCHAR(50) NOT NULL,
    etl_batch_id VARCHAR(100)
);
```

### 3. fact_performance_metrics

Aggregated performance metrics for operational analysis.

```sql
CREATE TABLE fact_performance_metrics (
    metric_key BIGINT PRIMARY KEY,
    
    -- Foreign Keys
    branch_key BIGINT NOT NULL,
    date_key INT NOT NULL,
    service_type_key BIGINT,
    client_tier_key BIGINT,
    
    -- Performance Measures
    total_shipments INT DEFAULT 0,
    delivered_shipments INT DEFAULT 0,
    returned_shipments INT DEFAULT 0,
    exception_shipments INT DEFAULT 0,
    cancelled_shipments INT DEFAULT 0,
    
    -- Time Metrics
    on_time_delivery_rate DECIMAL(5,2) DEFAULT 0.00,
    first_attempt_success_rate DECIMAL(5,2) DEFAULT 0.00,
    average_delivery_time_hours DECIMAL(8,2) DEFAULT 0.00,
    average_pickup_time_hours DECIMAL(8,2) DEFAULT 0.00,
    
    -- Financial Metrics
    total_revenue DECIMAL(12,2) DEFAULT 0.00,
    total_cost DECIMAL(12,2) DEFAULT 0.00,
    total_margin DECIMAL(12,2) DEFAULT 0.00,
    margin_percentage DECIMAL(5,2) DEFAULT 0.00,
    
    -- Efficiency Metrics
    average_weight_per_shipment DECIMAL(8,3) DEFAULT 0.000,
    average_distance_km DECIMAL(10,2) DEFAULT 0.00,
    fuel_cost_per_km DECIMAL(8,2) DEFAULT 0.00,
    
    -- Created/Updated
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

## Dimension Tables

### 1. dim_time

Time dimension for date-based analysis.

```sql
CREATE TABLE dim_time (
    date_key INT PRIMARY KEY,
    full_date DATE NOT NULL,
    year INT NOT NULL,
    quarter INT NOT NULL,
    month INT NOT NULL,
    month_name VARCHAR(20) NOT NULL,
    week_of_year INT NOT NULL,
    week_of_month INT NOT NULL,
    day_of_year INT NOT NULL,
    day_of_month INT NOT NULL,
    day_of_week INT NOT NULL,
    day_name VARCHAR(20) NOT NULL,
    is_weekend BOOLEAN NOT NULL,
    is_holiday BOOLEAN DEFAULT FALSE,
    is_business_day BOOLEAN NOT NULL,
    fiscal_year INT,
    fiscal_quarter INT,
    fiscal_month INT,
    
    -- Seasonal Information
    season VARCHAR(10),
    season_number INT,
    
    -- Special Flags
    is_month_end BOOLEAN,
    is_quarter_end BOOLEAN,
    is_year_end BOOLEAN,
    is_leap_year BOOLEAN
);
```

### 2. dim_branch

Branch/location dimension with geographic and operational data.

```sql
CREATE TABLE dim_branch (
    branch_key BIGINT PRIMARY KEY,
    branch_id BIGINT NOT NULL,
    branch_name VARCHAR(100) NOT NULL,
    branch_code VARCHAR(20) UNIQUE,
    branch_type VARCHAR(30) NOT NULL,
    parent_branch_key BIGINT,
    
    -- Geographic Data
    address_line1 VARCHAR(200),
    address_line2 VARCHAR(200),
    city VARCHAR(100),
    state_province VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    latitude DECIMAL(10, 6),
    longitude DECIMAL(11, 6),
    timezone VARCHAR(50),
    
    -- Operational Data
    capacity INT,
    service_capabilities JSON,
    operating_hours JSON,
    contact_info JSON,
    
    -- Hierarchical Data
    region VARCHAR(50),
    district VARCHAR(50),
    area VARCHAR(50),
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    opened_date DATE,
    closed_date DATE,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    source_system VARCHAR(50) NOT NULL
);
```

### 3. dim_client

Client/customer dimension with segmentation and demographic data.

```sql
CREATE TABLE dim_client (
    client_key BIGINT PRIMARY KEY,
    client_id BIGINT NOT NULL,
    client_name VARCHAR(200) NOT NULL,
    client_code VARCHAR(20) UNIQUE,
    
    -- Client Classification
    client_tier VARCHAR(30),
    client_segment VARCHAR(50),
    industry VARCHAR(100),
    business_size VARCHAR(30),
    
    -- Contact Information
    contact_person VARCHAR(100),
    email VARCHAR(200),
    phone VARCHAR(50),
    website VARCHAR(200),
    
    -- Address Information
    billing_address JSON,
    shipping_addresses JSON,
    
    -- Financial Information
    payment_terms INT,
    credit_limit DECIMAL(12,2),
    currency_code CHAR(3),
    tax_id VARCHAR(50),
    
    -- Service Preferences
    preferred_service_types JSON,
    delivery_instructions TEXT,
    special_requirements TEXT,
    
    -- Status and Dates
    is_active BOOLEAN DEFAULT TRUE,
    client_since DATE,
    last_activity_date DATE,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    source_system VARCHAR(50) NOT NULL
);
```

### 4. dim_customer

End customer dimension (ship-to addresses).

```sql
CREATE TABLE dim_customer (
    customer_key BIGINT PRIMARY KEY,
    customer_id BIGINT,
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    full_name VARCHAR(200),
    
    -- Contact Information
    email VARCHAR(200),
    phone VARCHAR(50),
    alternative_phone VARCHAR(50),
    
    -- Address Information
    address_line1 VARCHAR(200),
    address_line2 VARCHAR(200),
    city VARCHAR(100),
    state_province VARCHAR(100),
    postal_code VARCHAR(20),
    country VARCHAR(100),
    latitude DECIMAL(10, 6),
    longitude DECIMAL(11, 6),
    
    -- Delivery Preferences
    preferred_delivery_time VARCHAR(20),
    delivery_instructions TEXT,
    access_instructions TEXT,
    
    -- Customer Analytics
    customer_type VARCHAR(30),
    total_orders INT DEFAULT 0,
    average_order_value DECIMAL(10,2) DEFAULT 0.00,
    first_order_date DATE,
    last_order_date DATE,
    
    -- Status
    is_active BOOLEAN DEFAULT TRUE,
    is_verified BOOLEAN DEFAULT FALSE,
    
    -- Metadata
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    source_system VARCHAR(50) NOT NULL
);
```

## ETL Metadata Tables

### 1. etl_batches

ETL batch execution tracking.

```sql
CREATE TABLE etl_batches (
    batch_id VARCHAR(100) PRIMARY KEY,
    pipeline_name VARCHAR(100) NOT NULL,
    status ENUM('PENDING', 'RUNNING', 'COMPLETED', 'FAILED', 'RETRY') NOT NULL,
    
    -- Timing
    started_at TIMESTAMP NOT NULL,
    completed_at TIMESTAMP NULL,
    
    -- Processing Metrics
    records_processed INT DEFAULT 0,
    records_successful INT DEFAULT 0,
    records_failed INT DEFAULT 0,
    
    -- Error Information
    error_message TEXT NULL,
    retry_count INT DEFAULT 0,
    
    -- Performance Metrics
    execution_metrics JSON NULL,
    triggered_by VARCHAR(100) NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. etl_audit_log

Detailed audit trail for all data changes.

```sql
CREATE TABLE etl_audit_log (
    audit_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    batch_id VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id VARCHAR(100) NOT NULL,
    
    -- Operation Details
    operation ENUM('INSERT', 'UPDATE', 'DELETE', 'UPSERT') NOT NULL,
    change_type ENUM('DATA', 'STRUCTURE', 'METADATA') NOT NULL,
    
    -- Data Changes
    before_values JSON NULL,
    after_values JSON NULL,
    changed_fields JSON NULL,
    
    -- Context
    source_system VARCHAR(50),
    user_id VARCHAR(100),
    ip_address VARCHAR(45),
    user_agent TEXT,
    
    -- Data Quality
    data_quality_score DECIMAL(3,2),
    validation_errors JSON NULL,
    anomaly_flags JSON NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_batch_table (batch_id, table_name),
    INDEX idx_record (table_name, record_id),
    INDEX idx_created_at (created_at)
);
```

## Data Quality Tables

### 1. etl_data_quality_violations

Data quality rule violations.

```sql
CREATE TABLE etl_data_quality_violations (
    violation_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    rule_id VARCHAR(100) NOT NULL,
    table_name VARCHAR(100) NOT NULL,
    record_id VARCHAR(100) NOT NULL,
    
    -- Violation Details
    violation_type VARCHAR(50) NOT NULL,
    violation_description TEXT NOT NULL,
    violation_details JSON,
    severity ENUM('LOW', 'MEDIUM', 'HIGH', 'CRITICAL') NOT NULL,
    
    -- Status
    status ENUM('DETECTED', 'UNDER_REVIEW', 'RESOLVED', 'IGNORED') NOT NULL,
    batch_id VARCHAR(100) NOT NULL,
    
    -- Resolution
    resolved_by VARCHAR(100) NULL,
    resolution_notes TEXT NULL,
    resolved_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_rule_table (rule_id, table_name),
    INDEX idx_severity (severity, status),
    INDEX idx_batch (batch_id)
);
```

### 2. etl_anomaly_detection

Detected anomalies in data patterns.

```sql
CREATE TABLE etl_anomaly_detection (
    anomaly_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    table_name VARCHAR(100) NOT NULL,
    record_id VARCHAR(100) NOT NULL,
    
    -- Anomaly Classification
    anomaly_type ENUM('STATISTICAL', 'PATTERN', 'BUSINESS_RULE', 'TEMPORAL') NOT NULL,
    anomaly_category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    
    -- Severity and Detection
    severity_score DECIMAL(3,2) NOT NULL,
    detection_method VARCHAR(50) NOT NULL,
    anomaly_data JSON NOT NULL,
    context_data JSON,
    
    -- Status and Investigation
    status ENUM('DETECTED', 'INVESTIGATED', 'CONFIRMED', 'FALSE_POSITIVE') NOT NULL,
    batch_id VARCHAR(100) NOT NULL,
    
    -- Investigation Details
    investigated_by VARCHAR(100) NULL,
    investigation_notes TEXT NULL,
    investigated_at TIMESTAMP NULL,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_type_severity (anomaly_type, severity_score),
    INDEX idx_batch (batch_id),
    INDEX idx_status (status)
);
```

## Data Lineage Table

### 1. etl_data_lineage

Complete data lineage tracking for audit and impact analysis.

```sql
CREATE TABLE etl_data_lineage (
    lineage_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    batch_id VARCHAR(100) NOT NULL,
    
    -- Source Information
    source_table VARCHAR(100) NOT NULL,
    source_record_id VARCHAR(100) NOT NULL,
    source_system VARCHAR(50),
    
    -- Target Information
    target_table VARCHAR(100) NOT NULL,
    target_record_id VARCHAR(100) NOT NULL,
    
    -- Transformation Details
    transformation_rules JSON,
    transformation_steps JSON,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_source (source_table, source_record_id),
    INDEX idx_target (target_table, target_record_id),
    INDEX idx_batch (batch_id)
);
```

## Performance Optimization

### Indexing Strategy

1. **Fact Tables:**
   - Primary keys on surrogate keys
   - Composite indexes on common query patterns
   - Covering indexes for analytical queries
   - Partitioning by date for time-series data

2. **Dimension Tables:**
   - Primary keys on surrogate keys
   - Unique indexes on natural keys
   - Composite indexes for hierarchical queries
   - Full-text indexes on description fields

3. **ETL Metadata Tables:**
   - Indexes on batch_id for quick lookup
   - Composite indexes on common query combinations
   - Date-based indexes for historical analysis

### Partitioning Strategy

1. **Large Fact Tables:**
   - Range partitioning by date (monthly)
   - Subpartitioning by high-cardinality dimensions

2. **Audit Tables:**
   - Range partitioning by creation date (monthly or weekly)
   - Archive old partitions to separate tables

3. **Performance Benefits:**
   - Improved query performance for date-range queries
   - Easier data lifecycle management
   - Reduced index maintenance overhead

### Caching Strategy

1. **Application Level:**
   - Redis for frequently accessed aggregations
   - Memcached for static reference data

2. **Database Level:**
   - Materialized views for complex aggregations
   - Query result caching for dashboard queries

3. **Best Practices:**
   - Cache invalidation on data changes
   - Time-based expiration for dynamic data
   - Cache warming for predictable queries

## Data Lifecycle Management

### Retention Policies

1. **Raw Data:**
   - 90 days in staging tables
   - 7 years in audit logs

2. **Aggregated Data:**
   - Daily aggregations: 2 years
   - Monthly aggregations: 10 years
   - Yearly aggregations: Permanent

3. **Performance Metrics:**
   - Hourly data: 90 days
   - Daily data: 2 years
   - Weekly data: 5 years
   - Monthly data: 10 years

### Archival Strategy

1. **Cold Data Movement:**
   - Move to separate archival database
   - Compress for storage efficiency
   - Maintain data lineage references

2. **Compliance Requirements:**
   - Meet regulatory retention requirements
   - Enable historical reporting
   - Support legal discovery requests

## Security and Access Control

### Database Security

1. **Authentication:**
   - Application-specific database users
   - Role-based access control
   - Connection encryption (SSL/TLS)

2. **Authorization:**
   - Read access for reporting users
   - Read/write for ETL processes
   - Admin access for data architects

3. **Data Protection:**
   - Column-level encryption for sensitive data
   - Data masking for non-production environments
   - Regular security audits and penetration testing

### Access Patterns

1. **Analytical Queries:**
   - Read-only access to fact/dimension tables
   - Optimized for complex aggregations
   - Support for ad-hoc analysis

2. **ETL Processes:**
   - Write access to staging tables
   - Read access to source systems
   - Full access to metadata tables

This database schema provides a robust foundation for the analytics platform, supporting high-performance analytical queries while maintaining data quality, auditability, and compliance requirements.