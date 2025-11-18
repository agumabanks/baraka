-- ====================================================================
-- DHL-GRADE PRODUCTION DATABASE OPTIMIZATION & INDEXING
-- Baraka Branch Management Portal - Enterprise Performance
-- Generated: 2025-11-18T00:51:28Z
-- ====================================================================

-- Enable performance monitoring
SET profiling = 1;

-- ====================================================================
-- CORE TABLES INDEXING - PRODUCTION OPTIMIZED
-- ====================================================================

-- Users table optimization
CREATE INDEX IF NOT EXISTS idx_users_email ON users(email);
CREATE INDEX IF NOT EXISTS idx_users_phone ON users(phone);
CREATE INDEX IF NOT EXISTS idx_users_branch_id ON users(branch_id);
CREATE INDEX IF NOT EXISTS idx_users_role_id ON users(role_id);
CREATE INDEX IF NOT EXISTS idx_users_status ON users(status);
CREATE INDEX IF NOT EXISTS idx_users_created_at ON users(created_at);

-- Unified Branches table optimization
CREATE INDEX IF NOT EXISTS idx_unified_branches_code ON unified_branches(code);
CREATE INDEX IF NOT EXISTS idx_unified_branches_name ON unified_branches(name);
CREATE INDEX IF NOT EXISTS idx_unified_branches_status ON unified_branches(status);
CREATE INDEX IF NOT EXISTS idx_unified_branches_location ON unified_branches(location);
CREATE INDEX IF NOT EXISTS idx_unified_branches_type ON unified_branches(type);
CREATE INDEX IF NOT EXISTS idx_unified_branches_parent_id ON unified_branches(parent_id);

-- Customers table optimization
CREATE INDEX IF NOT EXISTS idx_customers_phone ON customers(phone);
CREATE INDEX IF NOT EXISTS idx_customers_email ON customers(email);
CREATE INDEX IF NOT EXISTS idx_customers_branch_id ON customers(branch_id);
CREATE INDEX IF NOT EXISTS idx_customers_status ON customers(status);
CREATE INDEX IF NOT EXISTS idx_customers_created_at ON customers(created_at);

-- Shipments table optimization
CREATE INDEX IF NOT EXISTS idx_shipments_tracking_number ON shipments(tracking_number);
CREATE INDEX IF NOT EXISTS idx_shipments_status ON shipments(status);
CREATE INDEX IF NOT EXISTS idx_shipments_branch_id ON shipments(branch_id);
CREATE INDEX IF NOT EXISTS idx_shipments_customer_id ON shipments(customer_id);
CREATE INDEX IF NOT EXISTS idx_shipments_created_at ON shipments(created_at);
CREATE INDEX IF NOT EXISTS idx_shipments_updated_at ON shipments(updated_at);
CREATE INDEX IF NOT EXISTS idx_shipments_delivery_date ON shipments(delivery_date);
CREATE INDEX IF NOT EXISTS idx_shipments_priority ON shipments(priority);
CREATE INDEX IF NOT EXISTS idx_shipments_public_token ON shipments(public_token);

-- Contracts table optimization
CREATE INDEX IF NOT EXISTS idx_contracts_branch_id ON contracts(branch_id);
CREATE INDEX IF NOT EXISTS idx_contracts_client_id ON contracts(client_id);
CREATE INDEX IF NOT EXISTS idx_contracts_status ON contracts(status);
CREATE INDEX IF NOT EXISTS idx_contracts_type ON contracts(type);
CREATE INDEX IF NOT EXISTS idx_contracts_start_date ON contracts(start_date);
CREATE INDEX IF NOT EXISTS idx_contracts_end_date ON contracts(end_date);
CREATE INDEX IF NOT EXISTS idx_contracts_renewal_date ON contracts(renewal_date);

-- Delivery man table optimization
CREATE INDEX IF NOT EXISTS idx_delivery_man_branch_id ON delivery_man(branch_id);
CREATE INDEX IF NOT EXISTS idx_delivery_man_status ON delivery_man(status);
CREATE INDEX IF NOT EXISTS idx_delivery_man_phone ON delivery_man(phone);
CREATE INDEX IF NOT EXISTS idx_delivery_man_current_location ON delivery_man(current_location);

-- Merchants table optimization
CREATE INDEX IF NOT EXISTS idx_merchants_phone ON merchants(phone);
CREATE INDEX IF NOT EXISTS idx_merchants_email ON merchants(email);
CREATE INDEX IF NOT EXISTS idx_merchants_branch_id ON merchants(branch_id);
CREATE INDEX IF NOT EXISTS idx_merchants_status ON merchants(status);
CREATE INDEX IF NOT EXISTS idx_merchants_created_at ON merchants(created_at);

-- Vehicles table optimization
CREATE INDEX IF NOT EXISTS idx_vehicles_branch_id ON vehicles(branch_id);
CREATE INDEX IF NOT EXISTS idx_vehicles_type ON vehicles(type);
CREATE INDEX IF NOT EXISTS idx_vehicles_status ON vehicles(status);
CREATE INDEX IF NOT EXISTS idx_vehicles_driver_id ON vehicles(driver_id);

-- Notification Settings table optimization
CREATE INDEX IF NOT EXISTS idx_notification_settings_user_id ON notification_settings(user_id);
CREATE INDEX IF NOT EXISTS idx_notification_settings_type ON notification_settings(type);
CREATE INDEX IF NOT EXISTS idx_notification_settings_enabled ON notification_settings(enabled);

-- General Settings table optimization
CREATE INDEX IF NOT EXISTS idx_general_settings_key ON general_settings(setting_key);
CREATE INDEX IF NOT EXISTS idx_general_settings_category ON general_settings(category);

-- EDI Providers table optimization
CREATE INDEX IF NOT EXISTS idx_edi_providers_status ON edi_providers(status);
CREATE INDEX IF NOT EXISTS idx_edi_providers_type ON edi_providers(type);

-- API Keys table optimization
CREATE INDEX IF NOT EXISTS idx_api_keys_hash ON api_keys(hash);
CREATE INDEX IF NOT EXISTS idx_api_keys_user_id ON api_keys(user_id);
CREATE INDEX IF NOT EXISTS idx_api_keys_active ON api_keys(active);

-- Security Audit Logs table optimization
CREATE INDEX IF NOT EXISTS idx_security_audit_logs_user_id ON security_audit_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_security_audit_logs_action ON security_audit_logs(action);
CREATE INDEX IF NOT EXISTS idx_security_audit_logs_ip_address ON security_audit_logs(ip_address);
CREATE INDEX IF NOT EXISTS idx_security_audit_logs_created_at ON security_audit_logs(created_at);

-- Security User Roles table optimization
CREATE INDEX IF NOT EXISTS idx_security_user_roles_user_id ON security_user_roles(user_id);
CREATE INDEX IF NOT EXISTS idx_security_user_roles_role_name ON security_user_roles(role_name);

-- Webhook Endpoints table optimization
CREATE INDEX IF NOT EXISTS idx_webhook_endpoints_active ON webhook_endpoints(active);
CREATE INDEX IF NOT EXISTS idx_webhook_endpoints_url ON webhook_endpoints(url);
CREATE INDEX IF NOT EXISTS idx_webhook_endpoints_secret ON webhook_endpoints(secret);

-- POD Proofs table optimization
CREATE INDEX IF NOT EXISTS idx_pod_proofs_shipment_id ON pod_proofs(shipment_id);
CREATE INDEX IF NOT EXISTS idx_pod_proofs_type ON pod_proofs(type);

-- Customs Docs table optimization
CREATE INDEX IF NOT EXISTS idx_customs_docs_shipment_id ON customs_docs(shipment_id);
CREATE INDEX IF NOT EXISTS idx_customs_docs_status ON customs_docs(status);

-- HS Codes table optimization
CREATE INDEX IF NOT EXISTS idx_hs_codes_code ON hs_codes(hs_code);
CREATE INDEX IF NOT EXISTS idx_hs_codes_description ON hs_codes(description);

-- Settlement Cycles table optimization
CREATE INDEX IF NOT EXISTS idx_settlement_cycles_branch_id ON settlement_cycles(branch_id);
CREATE INDEX IF NOT EXISTS idx_settlement_cycles_start_date ON settlement_cycles(start_date);
CREATE INDEX IF NOT EXISTS idx_settlement_cycles_end_date ON settlement_cycles(end_date);
CREATE INDEX IF NOT EXISTS idx_settlement_cycles_status ON settlement_cycles(status);

-- Bag Parcel table optimization
CREATE INDEX IF NOT EXISTS idx_bag_parcel_bag_id ON bag_parcel(bag_id);
CREATE INDEX IF NOT EXISTS idx_bag_parcel_parcel_id ON bag_parcel(parcel_id);
CREATE INDEX IF NOT EXISTS idx_bag_parcel_status ON bag_parcel(status);

-- Workflow Tasks table optimization
CREATE INDEX IF NOT EXISTS idx_workflow_tasks_assigned_to ON workflow_tasks(assigned_to);
CREATE INDEX IF NOT EXISTS idx_workflow_tasks_status ON workflow_tasks(status);
CREATE INDEX IF NOT EXISTS idx_workflow_tasks_priority ON workflow_tasks(priority);
CREATE INDEX IF NOT EXISTS idx_workflow_tasks_due_date ON workflow_tasks(due_date);

-- Workflow Task Activities table optimization
CREATE INDEX IF NOT EXISTS idx_workflow_task_activities_task_id ON workflow_task_activities(task_id);
CREATE INDEX IF NOT EXISTS idx_workflow_task_activities_performed_by ON workflow_task_activities(performed_by);
CREATE INDEX IF NOT EXISTS idx_workflow_task_activities_created_at ON workflow_task_activities(created_at);

-- Shipment Logs table optimization
CREATE INDEX IF NOT EXISTS idx_shipment_logs_shipment_id ON shipment_logs(shipment_id);
CREATE INDEX IF NOT EXISTS idx_shipment_logs_action ON shipment_logs(action);
CREATE INDEX IF NOT EXISTS idx_shipment_logs_user_id ON shipment_logs(user_id);
CREATE INDEX IF NOT EXISTS idx_shipment_logs_created_at ON shipment_logs(created_at);

-- Payment Requests table optimization
CREATE INDEX IF NOT EXISTS idx_payment_requests_branch_id ON payment_requests(branch_id);
CREATE INDEX IF NOT EXISTS idx_payment_requests_status ON payment_requests(status);
CREATE INDEX IF NOT EXISTS idx_payment_requests_created_at ON payment_requests(created_at);

-- Event Streams table optimization
CREATE INDEX IF NOT EXISTS idx_event_streams_event_type ON event_streams(event_type);
CREATE INDEX IF NOT EXISTS idx_event_streams_aggregate_type ON event_streams(aggregate_type);
CREATE INDEX IF NOT EXISTS idx_event_streams_aggregate_id ON event_streams(aggregate_id);
CREATE INDEX IF NOT EXISTS idx_event_streams_created_at ON event_streams(created_at);

-- EDI Mappings table optimization
CREATE INDEX IF NOT EXISTS idx_edi_mappings_provider_id ON edi_mappings(provider_id);
CREATE INDEX IF NOT EXISTS idx_edi_mappings_type ON edi_mappings(type);

-- Personal Access Tokens table optimization
CREATE INDEX IF NOT EXISTS idx_personal_access_tokens_tokenable_type ON personal_access_tokens(tokenable_type);
CREATE INDEX IF NOT EXISTS idx_personal_access_tokens_tokenable_id ON personal_access_tokens(tokenable_id);
CREATE INDEX IF NOT EXISTS idx_personal_access_tokens_token ON personal_access_tokens(token);

-- ====================================================================
-- COMPOSITE INDEXES FOR COMPLEX QUERIES
-- ====================================================================

-- Shipment tracking composite indexes
CREATE INDEX IF NOT EXISTS idx_shipments_branch_status ON shipments(branch_id, status);
CREATE INDEX IF NOT EXISTS idx_shipments_status_date ON shipments(status, created_at);
CREATE INDEX IF NOT EXISTS idx_shipments_customer_status ON shipments(customer_id, status);

-- User management composite indexes
CREATE INDEX IF NOT EXISTS idx_users_branch_status ON users(branch_id, status);
CREATE INDEX IF NOT EXISTS idx_users_role_status ON users(role_id, status);

-- Branch operations composite indexes
CREATE INDEX IF NOT EXISTS idx_merchants_branch_status ON merchants(branch_id, status);
CREATE INDEX IF NOT EXISTS idx_customers_branch_status ON customers(branch_id, status);

-- Contract management composite indexes
CREATE INDEX IF NOT EXISTS idx_contracts_branch_status ON contracts(branch_id, status);
CREATE INDEX IF NOT EXISTS idx_contracts_status_dates ON contracts(status, start_date, end_date);

-- Payment tracking composite indexes
CREATE INDEX IF NOT EXISTS idx_payment_requests_branch_status_date ON payment_requests(branch_id, status, created_at);

-- Security monitoring composite indexes
CREATE INDEX IF NOT EXISTS idx_security_audit_logs_user_action ON security_audit_logs(user_id, action);
CREATE INDEX IF NOT EXISTS idx_security_audit_logs_action_date ON security_audit_logs(action, created_at);

-- ====================================================================
-- FULL-TEXT SEARCH INDEXES
-- ====================================================================

-- Full-text search for customer names and addresses
ALTER TABLE customers ADD FULLTEXT(name, address, city, state, country);

-- Full-text search for branch names and descriptions
ALTER TABLE unified_branches ADD FULLTEXT(name, description, location);

-- Full-text search for merchants
ALTER TABLE merchants ADD FULLTEXT(name, address, business_name);

-- Full-text search for contracts
ALTER TABLE contracts ADD FULLTEXT(description, terms);

-- ====================================================================
-- PERFORMANCE OPTIMIZATION QUERIES
-- ====================================================================

-- Analyze tables for query optimization
ANALYZE TABLE users, unified_branches, customers, shipments, contracts, 
              delivery_man, merchants, vehicles, notification_settings, 
              general_settings, edi_providers, api_keys, security_audit_logs;

-- ====================================================================
-- STORAGE ENGINE OPTIMIZATIONS
-- ====================================================================

-- Optimize tables for InnoDB storage engine
ALTER TABLE users ENGINE=InnoDB;
ALTER TABLE unified_branches ENGINE=InnoDB;
ALTER TABLE customers ENGINE=InnoDB;
ALTER TABLE shipments ENGINE=InnoDB;
ALTER TABLE contracts ENGINE=InnoDB;
ALTER TABLE delivery_man ENGINE=InnoDB;
ALTER TABLE merchants ENGINE=InnoDB;
ALTER TABLE vehicles ENGINE=InnoDB;
ALTER TABLE notification_settings ENGINE=InnoDB;
ALTER TABLE general_settings ENGINE=InnoDB;
ALTER TABLE edi_providers ENGINE=InnoDB;
ALTER TABLE api_keys ENGINE=InnoDB;
ALTER TABLE security_audit_logs ENGINE=InnoDB;

-- ====================================================================
-- QUERY PERFORMANCE MONITORING SETUP
-- ====================================================================

-- Show performance statistics
SHOW GLOBAL STATUS WHERE Variable_name IN (
    'Connections', 'Queries', 'Questions', 'Slow_queries',
    'Innodb_buffer_pool_read_requests', 'Innodb_buffer_pool_reads',
    'Key_reads', 'Key_read_requests', 'Key_writes', 'Key_write_requests'
);

-- ====================================================================
-- MAINTENANCE SCHEDULE RECOMMENDATIONS
-- ====================================================================

/*
DAILY:
- Check slow query log
- Monitor table sizes
- Check index usage

WEEKLY:
- Optimize tables: OPTIMIZE TABLE table_name;
- Update table statistics: ANALYZE TABLE table_name;
- Clean up old logs

MONTHLY:
- Review and clean unused indexes
- Analyze query performance patterns
- Update MySQL configuration if needed
*/

-- Query performance monitoring enabled
SELECT 'Database optimization completed successfully' as status;
