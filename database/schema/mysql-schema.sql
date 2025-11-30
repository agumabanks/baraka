/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `accessibility_compliance_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accessibility_compliance_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `test_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `wcag_version` json NOT NULL,
  `test_results` json NOT NULL,
  `compliance_score` decimal(5,2) NOT NULL,
  `violations` json NOT NULL,
  `warnings` json NOT NULL,
  `passes` json NOT NULL,
  `tested_by` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tested_at` timestamp NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accessibility_compliance_logs_test_id_unique` (`test_id`),
  KEY `accessibility_compliance_logs_page_url_tested_at_index` (`page_url`,`tested_at`),
  KEY `accessibility_compliance_logs_compliance_score_index` (`compliance_score`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accessibility_test_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accessibility_test_queue` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `page_url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'automated',
  `test_config` json DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `scheduled_at` timestamp NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `priority` int NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `accessibility_test_queue_job_id_unique` (`job_id`),
  KEY `accessibility_test_queue_status_scheduled_at_index` (`status`,`scheduled_at`),
  KEY `accessibility_test_queue_priority_index` (`priority`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accidents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accidents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint DEFAULT NULL,
  `date_of_accident` date DEFAULT NULL,
  `driver_responsible` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cost_of_repair` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `spare_parts` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `multi_documents` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `account_heads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `account_heads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` int DEFAULT NULL COMMENT '1=Income, 2=Expense',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` int DEFAULT NULL COMMENT '1=Admin, 2=User',
  `user_id` bigint unsigned DEFAULT NULL,
  `gateway` tinyint DEFAULT NULL,
  `balance` decimal(16,2) NOT NULL DEFAULT '0.00',
  `account_holder_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank` tinyint DEFAULT NULL,
  `branch_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `opening_balance` decimal(16,2) DEFAULT NULL,
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` tinyint DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `accounts_user_id_index` (`user_id`),
  CONSTRAINT `accounts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `causer_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `causer_id` bigint unsigned DEFAULT NULL,
  `properties` json DEFAULT NULL,
  `batch_uuid` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `addons`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addons` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `unique_identifier` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image` longtext COLLATE utf8mb4_unicode_ci,
  `purchase_code` longtext COLLATE utf8mb4_unicode_ci,
  `activated` tinyint unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `address_books`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `address_books` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `type` enum('shipper','consignee','payer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone_e164` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `address_line` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `tax_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `address_books_customer_id_foreign` (`customer_id`),
  CONSTRAINT `address_books_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `alert_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_data` json NOT NULL,
  `recommended_actions` json DEFAULT NULL,
  `acknowledged` tinyint(1) NOT NULL DEFAULT '0',
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analytics_alerts_branch_id_alert_type_index` (`branch_id`,`alert_type`),
  KEY `analytics_alerts_severity_index` (`severity`),
  KEY `analytics_alerts_acknowledged_index` (`acknowledged`),
  KEY `analytics_alerts_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_job_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_job_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `job_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `job_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_count` int NOT NULL DEFAULT '0',
  `processed_count` int NOT NULL DEFAULT '0',
  `error_count` int NOT NULL DEFAULT '0',
  `errors` json DEFAULT NULL,
  `execution_time_seconds` decimal(10,3) NOT NULL,
  `status` enum('pending','running','completed','completed_with_errors','failed') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `analytics_job_history_job_id_unique` (`job_id`),
  KEY `analytics_job_history_job_type_index` (`job_type`),
  KEY `analytics_job_history_status_index` (`status`),
  KEY `analytics_job_history_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_materialized_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_materialized_snapshots` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `snapshot_date` date NOT NULL,
  `data_period_days` int NOT NULL DEFAULT '30',
  `total_shipments` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivered_shipments` decimal(10,2) NOT NULL DEFAULT '0.00',
  `delivery_success_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `on_time_delivery_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `utilization_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `capacity_efficiency` decimal(5,2) NOT NULL DEFAULT '0.00',
  `performance_score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `profit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `active_workers` int NOT NULL DEFAULT '0',
  `current_workload` int NOT NULL DEFAULT '0',
  `detailed_metrics` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `analytics_materialized_snapshots_branch_id_snapshot_date_unique` (`branch_id`,`snapshot_date`),
  KEY `analytics_materialized_snapshots_branch_id_snapshot_date_index` (`branch_id`,`snapshot_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analytics_performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `analytics_performance_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `operation_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `execution_time_ms` decimal(10,3) NOT NULL,
  `memory_usage_mb` int DEFAULT NULL,
  `records_processed` int NOT NULL DEFAULT '0',
  `cache_hit_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `cache_key_pattern` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `measured_at` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `analytics_performance_metrics_operation_type_measured_at_index` (`operation_type`,`measured_at`),
  KEY `analytics_performance_metrics_branch_id_index` (`branch_id`),
  KEY `analytics_performance_metrics_execution_time_ms_index` (`execution_time_ms`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_gateway_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_gateway_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `request_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_gateway_logs_type_created_at_index` (`type`,`created_at`),
  KEY `api_gateway_logs_request_id_index` (`request_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_gateway_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_gateway_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(15,6) NOT NULL,
  `tags` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_gateway_metrics_metric_created_at_index` (`metric`,`created_at`),
  KEY `api_gateway_metrics_type_metric_index` (`type`,`metric`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_gateway_raw_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_gateway_raw_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_gateway_raw_metrics_type_created_at_index` (`type`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned NOT NULL,
  `permissions` json DEFAULT NULL,
  `rate_limit` int NOT NULL DEFAULT '100',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_keys_key_unique` (`key`),
  KEY `api_keys_key_is_active_index` (`key`,`is_active`),
  KEY `api_keys_user_id_index` (`user_id`),
  CONSTRAINT `api_keys_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_load_balanced_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_load_balanced_routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_service` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_healthy` tinyint(1) NOT NULL DEFAULT '1',
  `weight` int NOT NULL DEFAULT '1',
  `current_load` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_load_balanced_routes_path_target_service_unique` (`path`,`target_service`),
  KEY `api_load_balanced_routes_path_index` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_performance_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_performance_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL,
  `request_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_ip` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alert_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `threshold` int NOT NULL,
  `actual_value` int NOT NULL,
  `processing_time` int NOT NULL,
  `memory_usage` int NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_performance_alerts_alert_type_created_at_index` (`alert_type`,`created_at`),
  KEY `api_performance_alerts_path_index` (`path`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_rate_limit_breaches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_rate_limit_breaches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_ip` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `route` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `limit_exceeded` int NOT NULL,
  `requests_in_window` int NOT NULL,
  `rate_limit_config` json NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_rate_limit_breaches_client_ip_created_at_index` (`client_ip`,`created_at`),
  KEY `api_rate_limit_breaches_route_index` (`route`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_request_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_request_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `endpoint` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `method` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_code` int DEFAULT NULL,
  `response_time_ms` int unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `requested_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_request_logs_endpoint_created_at_index` (`endpoint`,`created_at`),
  KEY `api_request_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `api_request_logs_status_code_created_at_index` (`status_code`,`created_at`),
  KEY `api_request_logs_response_time_ms_index` (`response_time_ms`),
  CONSTRAINT `api_request_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `methods` json NOT NULL,
  `target_service` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version_id` bigint unsigned DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `timeout` int NOT NULL DEFAULT '30',
  `connect_timeout` int NOT NULL DEFAULT '10',
  `auth_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'none',
  `auth_config` json DEFAULT NULL,
  `rate_limit_config` json DEFAULT NULL,
  `transform_config` json DEFAULT NULL,
  `validation_config` json DEFAULT NULL,
  `load_balanced` tinyint(1) NOT NULL DEFAULT '0',
  `target_services` json DEFAULT NULL,
  `load_balancing_strategy` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'round_robin',
  `health_check_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `retry_config` json DEFAULT NULL,
  `cors_config` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `api_routes_version_id_foreign` (`version_id`),
  CONSTRAINT `api_routes_version_id_foreign` FOREIGN KEY (`version_id`) REFERENCES `api_versions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_versions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `is_deprecated` tinyint(1) NOT NULL DEFAULT '0',
  `deprecation_date` timestamp NULL DEFAULT NULL,
  `migrated_to_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_versions_version_unique` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_assigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_assigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint unsigned NOT NULL,
  `driver_id` bigint unsigned NOT NULL,
  `from_date` date DEFAULT NULL,
  `to_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_assigns_asset_id_foreign` (`asset_id`),
  KEY `asset_assigns_driver_id_foreign` (`driver_id`),
  CONSTRAINT `asset_assigns_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `asset_assigns_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assetcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assetcategories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `author` bigint unsigned DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `asset_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_id` bigint DEFAULT NULL,
  `assetcategory_id` tinyint unsigned DEFAULT NULL,
  `hub_id` bigint unsigned DEFAULT NULL,
  `supplyer_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `quantity` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `warranty` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(13,2) DEFAULT NULL,
  `purchase_date` date DEFAULT NULL,
  `registration_documents` int DEFAULT NULL,
  `registration_date` date DEFAULT NULL,
  `registration_expiry_date` date DEFAULT NULL,
  `yearly_depreciation_value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `insurance_registration` date DEFAULT NULL,
  `insurance_status` tinyint NOT NULL DEFAULT '2',
  `insurance_documents` int DEFAULT NULL,
  `insurance_expiry_date` date DEFAULT NULL,
  `insurance_amount` decimal(13,2) DEFAULT NULL,
  `maintenance_schedule` date DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `assets_author_index` (`author`),
  KEY `assets_hub_id_index` (`hub_id`),
  CONSTRAINT `assets_author_foreign` FOREIGN KEY (`author`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `assets_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_report_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_report_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `config_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `included_modules` json NOT NULL,
  `filters` json DEFAULT NULL,
  `recipients` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_generated_at` timestamp NULL DEFAULT NULL,
  `format` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pdf',
  `custom_config` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_report_configs_config_name_unique` (`config_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `audit_trail_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_trail_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `log_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `session_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `action_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resource_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `module` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `changed_fields` json DEFAULT NULL,
  `severity` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `metadata` json DEFAULT NULL,
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `occurred_at` timestamp NOT NULL,
  `is_reversible` tinyint(1) NOT NULL DEFAULT '0',
  `reversal_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `audit_trail_logs_log_id_unique` (`log_id`),
  KEY `audit_trail_logs_user_id_occurred_at_index` (`user_id`,`occurred_at`),
  KEY `audit_trail_logs_resource_type_resource_id_index` (`resource_type`,`resource_id`),
  KEY `audit_trail_logs_action_type_index` (`action_type`),
  KEY `audit_trail_logs_severity_index` (`severity`),
  KEY `audit_trail_logs_module_index` (`module`),
  KEY `audit_trail_logs_transaction_id_index` (`transaction_id`),
  CONSTRAINT `audit_trail_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `awb_stocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `awb_stocks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `carrier_code` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `iata_prefix` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `range_start` bigint unsigned NOT NULL,
  `range_end` bigint unsigned NOT NULL,
  `used_count` int unsigned NOT NULL DEFAULT '0',
  `voided_count` int unsigned NOT NULL DEFAULT '0',
  `hub_id` bigint unsigned DEFAULT NULL,
  `assigned_to_user_id` bigint unsigned DEFAULT NULL,
  `status` enum('active','exhausted','voided') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `awb_stocks_hub_id_foreign` (`hub_id`),
  KEY `awb_stocks_assigned_to_user_id_foreign` (`assigned_to_user_id`),
  CONSTRAINT `awb_stocks_assigned_to_user_id_foreign` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `awb_stocks_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bag_parcel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bag_parcel` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `bag_id` bigint unsigned NOT NULL,
  `sscc` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `added_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bag_parcel_bag_id_sscc_unique` (`bag_id`,`sscc`),
  KEY `bag_parcel_sscc_index` (`sscc`),
  CONSTRAINT `bag_parcel_bag_id_foreign` FOREIGN KEY (`bag_id`) REFERENCES `bags` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_branch_id` bigint unsigned NOT NULL,
  `dest_branch_id` bigint unsigned NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OPEN',
  `leg_id` bigint unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `bags_code_unique` (`code`),
  KEY `bags_dest_branch_id_foreign` (`dest_branch_id`),
  KEY `bags_origin_branch_id_dest_branch_id_index` (`origin_branch_id`,`dest_branch_id`),
  KEY `bags_status_index` (`status`),
  KEY `bags_leg_id_index` (`leg_id`),
  CONSTRAINT `bags_dest_branch_id_foreign` FOREIGN KEY (`dest_branch_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `bags_leg_id_foreign` FOREIGN KEY (`leg_id`) REFERENCES `transport_legs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `bags_origin_branch_id_foreign` FOREIGN KEY (`origin_branch_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `bank_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `bank_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_type` tinyint unsigned DEFAULT '1' COMMENT '1=Admin,5=hub',
  `hub_id` bigint unsigned DEFAULT NULL,
  `expense_id` bigint DEFAULT NULL,
  `fund_transfer_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned NOT NULL,
  `type` tinyint unsigned DEFAULT NULL COMMENT 'income=1, expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `note` longtext COLLATE utf8mb4_unicode_ci,
  `cash_received_dvry` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `income_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `bank_transactions_income_id_foreign` (`income_id`),
  KEY `bank_transactions_user_type_index` (`user_type`),
  KEY `bank_transactions_hub_id_index` (`hub_id`),
  KEY `bank_transactions_expense_id_index` (`expense_id`),
  KEY `bank_transactions_fund_transfer_id_index` (`fund_transfer_id`),
  KEY `bank_transactions_account_id_index` (`account_id`),
  KEY `bank_transactions_type_index` (`type`),
  CONSTRAINT `bank_transactions_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `bank_transactions_fund_transfer_id_foreign` FOREIGN KEY (`fund_transfer_id`) REFERENCES `fund_transfers` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `bank_transactions_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `bank_transactions_income_id_foreign` FOREIGN KEY (`income_id`) REFERENCES `incomes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `banks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `blogs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `blogs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_id` bigint unsigned DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Active, 0= Inactive',
  `created_by` bigint unsigned NOT NULL,
  `views` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `blogs_image_id_foreign` (`image_id`),
  KEY `blogs_created_by_foreign` (`created_by`),
  CONSTRAINT `blogs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `blogs_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_alerts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_alerts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `alert_type` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OPEN',
  `title` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `context` json DEFAULT NULL,
  `triggered_at` timestamp NOT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `branch_alerts_branch_id_status_index` (`branch_id`,`status`),
  KEY `branch_alerts_alert_type_status_index` (`alert_type`,`status`),
  KEY `branch_alerts_triggered_at_index` (`triggered_at`),
  CONSTRAINT `branch_alerts_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_configurations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_configurations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hub_id` bigint unsigned NOT NULL,
  `delivery_charge_rules` json DEFAULT NULL,
  `service_restrictions` json DEFAULT NULL,
  `operating_restrictions` json DEFAULT NULL,
  `max_inventory_capacity` int NOT NULL DEFAULT '10000',
  `current_inventory_count` int NOT NULL DEFAULT '0',
  `inventory_alert_thresholds` json DEFAULT NULL,
  `max_staff_capacity` int NOT NULL DEFAULT '50',
  `current_staff_count` int NOT NULL DEFAULT '0',
  `staff_shift_schedules` json DEFAULT NULL,
  `equipment_inventory` json DEFAULT NULL,
  `vehicle_fleet` json DEFAULT NULL,
  `automated_sorting_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `daily_budget_limit` decimal(12,2) NOT NULL DEFAULT '50000.00',
  `monthly_budget_limit` decimal(15,2) NOT NULL DEFAULT '1000000.00',
  `payment_methods_supported` json DEFAULT NULL,
  `compliance_requirements` json DEFAULT NULL,
  `next_safety_audit` date DEFAULT NULL,
  `next_compliance_review` date DEFAULT NULL,
  `kpi_targets` json DEFAULT NULL,
  `target_on_time_delivery_rate` decimal(5,2) NOT NULL DEFAULT '95.00',
  `target_customer_satisfaction` decimal(5,2) NOT NULL DEFAULT '4.50',
  `notification_preferences` json DEFAULT NULL,
  `branch_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contacts` json DEFAULT NULL,
  `emergency_procedures` text COLLATE utf8mb4_unicode_ci,
  `api_endpoints` json DEFAULT NULL,
  `third_party_integrations` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `branch_configurations_hub_id_index` (`hub_id`),
  KEY `branch_configurations_next_safety_audit_index` (`next_safety_audit`),
  KEY `branch_configurations_next_compliance_review_index` (`next_compliance_review`),
  CONSTRAINT `branch_configurations_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_managers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_managers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'branch_manager',
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `business_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `cod_charges` json DEFAULT NULL,
  `payment_info` json DEFAULT NULL,
  `settlement_config` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_managers_branch_id_unique` (`branch_id`),
  UNIQUE KEY `branch_managers_branch_id_user_id_unique` (`branch_id`,`user_id`),
  KEY `branch_managers_user_id_index` (`user_id`),
  CONSTRAINT `branch_managers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_managers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `snapshot_date` date NOT NULL,
  `window` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'daily',
  `throughput_count` int unsigned NOT NULL DEFAULT '0',
  `capacity_utilization` decimal(6,3) NOT NULL DEFAULT '0.000',
  `exception_rate` decimal(6,3) NOT NULL DEFAULT '0.000',
  `on_time_rate` decimal(6,3) NOT NULL DEFAULT '0.000',
  `average_processing_time_hours` decimal(8,2) DEFAULT NULL,
  `on_time_target` decimal(5,2) DEFAULT NULL,
  `alerts_triggered` int unsigned NOT NULL DEFAULT '0',
  `metadata` json DEFAULT NULL,
  `calculated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_metrics_snapshot_unique` (`branch_id`,`snapshot_date`,`window`),
  KEY `branch_metrics_snapshot_date_window_index` (`snapshot_date`,`window`),
  CONSTRAINT `branch_metrics_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branch_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_workers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `designation` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `employment_status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `contact_phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_number` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` json DEFAULT NULL,
  `work_schedule` json DEFAULT NULL,
  `hourly_rate` decimal(8,2) DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `unassigned_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branch_workers_branch_id_user_id_unique` (`branch_id`,`user_id`),
  KEY `branch_workers_user_id_foreign` (`user_id`),
  KEY `branch_workers_branch_id_role_index` (`branch_id`,`role`),
  KEY `branch_workers_employment_status_index` (`employment_status`),
  CONSTRAINT `branch_workers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_workers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_hub` tinyint(1) NOT NULL DEFAULT '0',
  `parent_branch_id` bigint unsigned DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `country` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `time_zone` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `geo_lat` decimal(10,8) DEFAULT NULL,
  `geo_lng` decimal(11,8) DEFAULT NULL,
  `operating_hours` json DEFAULT NULL,
  `capabilities` json DEFAULT NULL,
  `capacity_parcels_per_day` int unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`),
  KEY `branches_type_status_index` (`type`,`status`),
  KEY `branches_parent_branch_id_index` (`parent_branch_id`),
  KEY `branches_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `branches_country_index` (`country`),
  KEY `branches_city_index` (`city`),
  KEY `branches_time_zone_index` (`time_zone`),
  CONSTRAINT `branches_parent_branch_id_foreign` FOREIGN KEY (`parent_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `capacity_forecasts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `capacity_forecasts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `forecast_date` date NOT NULL,
  `forecast_days` int NOT NULL,
  `predicted_demand` decimal(10,2) NOT NULL,
  `predicted_capacity` decimal(10,2) NOT NULL,
  `capacity_gap` decimal(10,2) NOT NULL,
  `confidence_level` decimal(5,2) NOT NULL,
  `forecast_factors` json NOT NULL,
  `risk_factors` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `capacity_forecasts_branch_id_forecast_date_forecast_days_unique` (`branch_id`,`forecast_date`,`forecast_days`),
  KEY `capacity_forecasts_branch_id_forecast_date_index` (`branch_id`,`forecast_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `carrier_services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carrier_services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `carrier_id` bigint unsigned NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `requires_eawb` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carrier_services_carrier_id_code_unique` (`carrier_id`,`code`),
  CONSTRAINT `carrier_services_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `carriers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `carriers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carriers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` enum('air','road') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `carriers_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_office_days`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_office_days` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `business_date` date NOT NULL,
  `cod_collected` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cash_on_hand` decimal(12,2) NOT NULL DEFAULT '0.00',
  `banked_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `variance` decimal(12,2) NOT NULL DEFAULT '0.00',
  `submitted_by_id` bigint unsigned NOT NULL,
  `submitted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_office_days_branch_id_foreign` (`branch_id`),
  KEY `cash_office_days_submitted_by_id_foreign` (`submitted_by_id`),
  CONSTRAINT `cash_office_days_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hubs` (`id`),
  CONSTRAINT `cash_office_days_submitted_by_id_foreign` FOREIGN KEY (`submitted_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cash_received_from_deliverymen`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cash_received_from_deliverymen` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `hub_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `receipt` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cash_received_from_deliverymen_receipt_foreign` (`receipt`),
  KEY `cash_received_from_deliverymen_user_id_index` (`user_id`),
  KEY `cash_received_from_deliverymen_hub_id_index` (`hub_id`),
  KEY `cash_received_from_deliverymen_account_id_index` (`account_id`),
  KEY `cash_received_from_deliverymen_delivery_man_id_index` (`delivery_man_id`),
  CONSTRAINT `cash_received_from_deliverymen_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cash_received_from_deliverymen_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cash_received_from_deliverymen_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cash_received_from_deliverymen_receipt_foreign` FOREIGN KEY (`receipt`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `cash_received_from_deliverymen_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categorys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `charge_lines`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `charge_lines` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `charge_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `charge_lines_shipment_id_index` (`shipment_id`),
  KEY `charge_lines_charge_type_index` (`charge_type`),
  CONSTRAINT `charge_lines_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `claims`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `claims` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `type` enum('loss','damage','delay') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount_claimed` decimal(12,2) NOT NULL,
  `evidence` json DEFAULT NULL,
  `status` enum('open','approved','rejected','paid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `settled_amount` decimal(12,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `claims_shipment_id_foreign` (`shipment_id`),
  CONSTRAINT `claims_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `primary_branch_id` bigint unsigned DEFAULT NULL,
  `business_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('active','inactive','suspended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `kyc_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clients_primary_branch_id_index` (`primary_branch_id`),
  KEY `clients_status_index` (`status`),
  CONSTRAINT `clients_primary_branch_id_foreign` FOREIGN KEY (`primary_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cod_receipts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cod_receipts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `method` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `receipt_image_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `collected_by` bigint unsigned NOT NULL,
  `collected_at` timestamp NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cod_receipts_shipment_id_index` (`shipment_id`),
  KEY `cod_receipts_collected_by_index` (`collected_by`),
  KEY `cod_receipts_collected_at_index` (`collected_at`),
  CONSTRAINT `cod_receipts_collected_by_foreign` FOREIGN KEY (`collected_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `cod_receipts_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `commodities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commodities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quantity` decimal(8,2) NOT NULL,
  `unit` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `unit_value` decimal(10,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `total_value` decimal(10,2) NOT NULL,
  `hs_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country_of_origin` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customs_info` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `commodities_shipment_id_index` (`shipment_id`),
  KEY `commodities_hs_code_index` (`hs_code`),
  CONSTRAINT `commodities_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `competitor_prices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `competitor_prices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `carrier_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_country` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `destination_country` char(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_level` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `weight_kg` decimal(6,2) DEFAULT NULL,
  `source_type` enum('api','manual','web_scraping') COLLATE utf8mb4_unicode_ci NOT NULL,
  `collected_at` timestamp NOT NULL DEFAULT '2025-11-18 09:41:05',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_competitor_route_service` (`origin_country`,`destination_country`,`service_level`),
  KEY `idx_competitor_collected_at` (`collected_at`),
  KEY `idx_competitor_carrier_source` (`carrier_name`,`source_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compliance_monitoring_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_monitoring_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `compliance_framework` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_definition` json NOT NULL,
  `severity` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notification_settings` json DEFAULT NULL,
  `action_settings` json DEFAULT NULL,
  `last_evaluated_at` timestamp NULL DEFAULT NULL,
  `evaluation_count` int NOT NULL DEFAULT '0',
  `violation_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `compliance_monitoring_rules_rule_name_unique` (`rule_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compliance_violations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compliance_violations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `violation_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `compliance_framework` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `violation_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `affected_records` json DEFAULT NULL,
  `discovered_by` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discovered_by_user_id` bigint unsigned DEFAULT NULL,
  `discovered_at` timestamp NOT NULL,
  `resolved_by_user_id` bigint unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `remediation_steps` json DEFAULT NULL,
  `is_false_positive` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `compliance_violations_violation_id_unique` (`violation_id`),
  KEY `compliance_violations_discovered_by_user_id_foreign` (`discovered_by_user_id`),
  KEY `compliance_violations_resolved_by_user_id_foreign` (`resolved_by_user_id`),
  KEY `compliance_violations_compliance_framework_severity_index` (`compliance_framework`,`severity`),
  KEY `compliance_violations_discovered_at_index` (`discovered_at`),
  KEY `compliance_violations_resolved_at_index` (`resolved_at`),
  CONSTRAINT `compliance_violations_discovered_by_user_id_foreign` FOREIGN KEY (`discovered_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `compliance_violations_resolved_by_user_id_foreign` FOREIGN KEY (`resolved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_amendments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_amendments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `amendment_number` int NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `changes` json DEFAULT NULL,
  `justification` text COLLATE utf8mb4_unicode_ci,
  `status` enum('draft','pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `effective_date` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_amendments_contract_id_amendment_number_unique` (`contract_id`,`amendment_number`),
  KEY `contract_amendments_approved_by_foreign` (`approved_by`),
  KEY `contract_amendments_created_by_foreign` (`created_by`),
  KEY `contract_amendments_contract_id_status_index` (`contract_id`,`status`),
  CONSTRAINT `contract_amendments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contract_amendments_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_amendments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `field_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `old_value` text COLLATE utf8mb4_unicode_ci,
  `new_value` text COLLATE utf8mb4_unicode_ci,
  `additional_data` json DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_audit_logs_contract_id_action_index` (`contract_id`,`action`),
  KEY `contract_audit_logs_user_id_action_index` (`user_id`,`action`),
  CONSTRAINT `contract_audit_logs_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_compliances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_compliances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `requirement_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `compliance_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_value` decimal(10,2) NOT NULL,
  `actual_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `performance_percentage` decimal(5,2) NOT NULL DEFAULT '100.00',
  `compliance_status` enum('met','warning','breached') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'met',
  `is_critical` tinyint(1) NOT NULL DEFAULT '0',
  `consecutive_breaches` int NOT NULL DEFAULT '0',
  `last_checked_at` timestamp NULL DEFAULT NULL,
  `last_breach_at` timestamp NULL DEFAULT NULL,
  `next_check_due` timestamp NULL DEFAULT NULL,
  `resolution_deadline` timestamp NULL DEFAULT NULL,
  `required_actions` json DEFAULT NULL,
  `escalation_level` int NOT NULL DEFAULT '0',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_compliances_contract_id_compliance_type_index` (`contract_id`,`compliance_type`),
  KEY `contract_compliances_compliance_status_next_check_due_index` (`compliance_status`,`next_check_due`),
  CONSTRAINT `contract_compliances_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `notification_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `data` json DEFAULT NULL,
  `channel` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `recipient` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `status` enum('pending','sent','delivered','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `retry_count` int NOT NULL DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_notifications_contract_id_notification_type_index` (`contract_id`,`notification_type`),
  KEY `contract_notifications_customer_id_notification_type_index` (`customer_id`,`notification_type`),
  KEY `contract_notifications_status_scheduled_at_index` (`status`,`scheduled_at`),
  CONSTRAINT `contract_notifications_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `contract_notifications_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_service_levels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_service_levels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `service_level_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `delivery_window_min_hours` int DEFAULT NULL,
  `delivery_window_max_hours` int DEFAULT NULL,
  `reliability_threshold` decimal(5,2) NOT NULL DEFAULT '90.00',
  `sla_claim_ratio` decimal(5,2) NOT NULL DEFAULT '0.05',
  `response_time_hours` int NOT NULL DEFAULT '24',
  `penalty_conditions` json DEFAULT NULL,
  `compensation_rules` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_service_levels_contract_id_service_level_code_index` (`contract_id`,`service_level_code`),
  CONSTRAINT `contract_service_levels_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `template_type` enum('standard','enterprise','government') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'standard',
  `terms_template` json NOT NULL,
  `default_settings` json DEFAULT NULL,
  `approval_required` tinyint(1) NOT NULL DEFAULT '1',
  `auto_renewal_enabled` tinyint(1) NOT NULL DEFAULT '0',
  `created_by_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contract_templates_created_by_id_foreign` (`created_by_id`),
  CONSTRAINT `contract_templates_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contract_volume_discounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contract_volume_discounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `contract_id` bigint unsigned NOT NULL,
  `tier_name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `volume_requirement` int NOT NULL,
  `discount_percentage` decimal(5,2) NOT NULL,
  `benefits` json DEFAULT NULL,
  `sort_order` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `contract_volume_discounts_contract_id_tier_name_unique` (`contract_id`,`tier_name`),
  KEY `contract_volume_discounts_contract_id_volume_requirement_index` (`contract_id`,`volume_requirement`),
  CONSTRAINT `contract_volume_discounts_contract_id_foreign` FOREIGN KEY (`contract_id`) REFERENCES `contracts` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `contracts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `contracts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `rate_card_id` bigint unsigned DEFAULT NULL,
  `template_id` bigint unsigned DEFAULT NULL,
  `contract_type` enum('customer','carrier','3pl') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'customer',
  `volume_commitment` int DEFAULT NULL,
  `volume_commitment_period` enum('monthly','quarterly','annually') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_volume` int NOT NULL DEFAULT '0',
  `discount_tiers` json DEFAULT NULL,
  `service_level_commitments` json DEFAULT NULL,
  `auto_renewal_terms` json DEFAULT NULL,
  `compliance_requirements` json DEFAULT NULL,
  `notification_settings` json DEFAULT NULL,
  `sla_json` json DEFAULT NULL,
  `status` enum('draft','negotiation','active','suspended','ended','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `original_contract_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contracts_template_id_foreign` (`template_id`),
  KEY `contracts_contract_type_status_index` (`contract_type`,`status`),
  KEY `contracts_volume_commitment_current_volume_index` (`volume_commitment`,`current_volume`),
  KEY `contracts_original_contract_id_foreign` (`original_contract_id`),
  KEY `contracts_status_end_date_index` (`status`,`end_date`),
  KEY `contracts_customer_id_status_index` (`customer_id`,`status`),
  KEY `contracts_current_volume_volume_commitment_index` (`current_volume`,`volume_commitment`),
  CONSTRAINT `contracts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `contracts_original_contract_id_foreign` FOREIGN KEY (`original_contract_id`) REFERENCES `contracts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `contracts_template_id_foreign` FOREIGN KEY (`template_id`) REFERENCES `contract_templates` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `courier_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `courier_statements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `income_id` bigint unsigned DEFAULT NULL,
  `expense_id` bigint DEFAULT NULL,
  `parcel_id` bigint unsigned DEFAULT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `type` tinyint unsigned DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `courier_statements_income_id_index` (`income_id`),
  KEY `courier_statements_expense_id_index` (`expense_id`),
  KEY `courier_statements_parcel_id_index` (`parcel_id`),
  KEY `courier_statements_delivery_man_id_index` (`delivery_man_id`),
  CONSTRAINT `courier_statements_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `courier_statements_income_id_foreign` FOREIGN KEY (`income_id`) REFERENCES `incomes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `courier_statements_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `currencies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `currencies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `country` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `symbol` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exchange_rate` decimal(16,2) DEFAULT NULL,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Active, 0= Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_milestone_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_milestone_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `milestone_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `milestone_category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `milestone_threshold` bigint NOT NULL,
  `current_value` bigint NOT NULL,
  `progress_percentage` decimal(5,2) NOT NULL,
  `achieved` tinyint(1) NOT NULL DEFAULT '0',
  `achieved_at` timestamp NULL DEFAULT NULL,
  `reward_details` json DEFAULT NULL,
  `reward_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `notification_sent` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `milestone_history_customer_idx` (`customer_id`,`milestone_type`),
  KEY `milestone_history_category_idx` (`milestone_category`,`achieved`),
  KEY `milestone_history_achieved_idx` (`achieved_at`),
  CONSTRAINT `customer_milestone_history_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_milestone_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_milestone_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `customer_milestone_id` bigint unsigned NOT NULL,
  `notification_type` enum('email','sms','push','in_app','webhook') COLLATE utf8mb4_unicode_ci NOT NULL,
  `notification_data` json DEFAULT NULL,
  `status` enum('pending','sent','delivered','failed','bounced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `retry_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_milestone_notifications_customer_milestone_id_foreign` (`customer_milestone_id`),
  KEY `customer_milestone_notifications_customer_id_status_index` (`customer_id`,`status`),
  KEY `customer_milestone_notifications_status_index` (`status`),
  KEY `customer_milestone_notifications_sent_at_index` (`sent_at`),
  CONSTRAINT `customer_milestone_notifications_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_milestone_notifications_customer_milestone_id_foreign` FOREIGN KEY (`customer_milestone_id`) REFERENCES `customer_milestones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_milestones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `milestone_type` enum('shipment_count','revenue_volume','tenure','tier_upgrade') COLLATE utf8mb4_unicode_ci NOT NULL,
  `milestone_value` int NOT NULL,
  `achieved_at` timestamp NOT NULL,
  `reward_given` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reward_details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_milestones_customer_id_milestone_type_index` (`customer_id`,`milestone_type`),
  KEY `customer_milestones_achieved_at_index` (`achieved_at`),
  CONSTRAINT `customer_milestones_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_promotion_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_promotion_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `preferred_campaign_types` json DEFAULT NULL,
  `preferred_discount_ranges` json DEFAULT NULL,
  `email_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `sms_notifications` tinyint(1) NOT NULL DEFAULT '0',
  `push_notifications` tinyint(1) NOT NULL DEFAULT '1',
  `excluded_categories` json DEFAULT NULL,
  `custom_eligibility_criteria` json DEFAULT NULL,
  `last_updated` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_promotion_preferences_customer_id_unique` (`customer_id`),
  KEY `customer_promo_pref_updated_idx` (`last_updated`),
  CONSTRAINT `customer_promotion_preferences_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_promotion_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_promotion_usage` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `promotional_campaign_id` bigint unsigned NOT NULL,
  `usage_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `discount_amount` decimal(10,2) NOT NULL,
  `order_value` decimal(10,2) NOT NULL,
  `order_details` json DEFAULT NULL,
  `used_at` timestamp NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_channel` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customer_promotion_usage_promotional_campaign_id_foreign` (`promotional_campaign_id`),
  KEY `customer_promo_usage_customer_campaign_idx` (`customer_id`,`promotional_campaign_id`),
  KEY `customer_promo_usage_used_at_idx` (`used_at`),
  KEY `customer_promo_usage_source_idx` (`source_channel`,`used_at`),
  KEY `usage_customer_campaign_date_idx` (`customer_id`,`promotional_campaign_id`,`used_at`),
  CONSTRAINT `customer_promotion_usage_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `customer_promotion_usage_promotional_campaign_id_foreign` FOREIGN KEY (`promotional_campaign_id`) REFERENCES `promotional_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `customer_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `company_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_person` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fax` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `billing_address` text COLLATE utf8mb4_unicode_ci,
  `shipping_address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Saudi Arabia',
  `tax_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `registration_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `industry` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `company_size` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `annual_revenue` decimal(15,2) DEFAULT NULL,
  `credit_limit` decimal(15,2) NOT NULL DEFAULT '0.00',
  `current_balance` decimal(15,2) NOT NULL DEFAULT '0.00',
  `payment_terms` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'net_30',
  `discount_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SAR',
  `customer_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regular',
  `segment` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority_level` int NOT NULL DEFAULT '3',
  `communication_channels` json DEFAULT NULL,
  `notification_preferences` json DEFAULT NULL,
  `preferred_language` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ar',
  `account_manager_id` bigint unsigned DEFAULT NULL,
  `primary_branch_id` bigint unsigned DEFAULT NULL,
  `sales_rep_id` bigint unsigned DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `last_contact_date` timestamp NULL DEFAULT NULL,
  `last_shipment_date` timestamp NULL DEFAULT NULL,
  `customer_since` timestamp NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `total_shipments` int NOT NULL DEFAULT '0',
  `total_spent` decimal(15,2) NOT NULL DEFAULT '0.00',
  `average_order_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `complaints_count` int NOT NULL DEFAULT '0',
  `satisfaction_score` decimal(3,2) DEFAULT NULL,
  `kyc_verified` tinyint(1) NOT NULL DEFAULT '0',
  `kyc_verified_at` timestamp NULL DEFAULT NULL,
  `compliance_flags` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customers_customer_code_unique` (`customer_code`),
  UNIQUE KEY `customers_email_unique` (`email`),
  KEY `customers_sales_rep_id_foreign` (`sales_rep_id`),
  KEY `customers_status_customer_type_index` (`status`,`customer_type`),
  KEY `customers_account_manager_id_index` (`account_manager_id`),
  KEY `customers_primary_branch_id_index` (`primary_branch_id`),
  KEY `customers_last_shipment_date_index` (`last_shipment_date`),
  KEY `customers_total_spent_index` (`total_spent`),
  KEY `customers_customer_code_index` (`customer_code`),
  CONSTRAINT `customers_account_manager_id_foreign` FOREIGN KEY (`account_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `customers_sales_rep_id_foreign` FOREIGN KEY (`sales_rep_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customs_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customs_docs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `doc_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `original_filename` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_size_bytes` int NOT NULL,
  `broker_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `submitted_at` timestamp NULL DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customs_docs_shipment_id_doc_type_index` (`shipment_id`,`doc_type`),
  KEY `customs_docs_status_index` (`status`),
  KEY `customs_docs_submitted_at_index` (`submitted_at`),
  CONSTRAINT `customs_docs_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dangerous_goods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dangerous_goods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned DEFAULT NULL,
  `un_number` varchar(6) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dg_class` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL,
  `packing_group` varchar(2) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proper_shipping_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `net_qty` decimal(8,3) DEFAULT NULL,
  `pkg_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('declared','held','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'declared',
  `docs` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `dangerous_goods_shipment_id_foreign` (`shipment_id`),
  CONSTRAINT `dangerous_goods_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_charges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned DEFAULT NULL,
  `weight` tinyint NOT NULL DEFAULT '0',
  `same_day` decimal(16,2) NOT NULL DEFAULT '0.00',
  `next_day` decimal(16,2) NOT NULL DEFAULT '0.00',
  `sub_city` decimal(16,2) NOT NULL DEFAULT '0.00',
  `outside_city` decimal(16,2) NOT NULL DEFAULT '0.00',
  `position` int DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_charges_category_id_index` (`category_id`),
  CONSTRAINT `delivery_charges_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `deliverycategories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `delivery_man`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `delivery_man` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `delivery_lat` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_long` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_location_lat` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_location_long` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_charge` decimal(13,2) NOT NULL DEFAULT '0.00',
  `pickup_charge` decimal(13,2) NOT NULL DEFAULT '0.00',
  `return_charge` decimal(13,2) NOT NULL DEFAULT '0.00',
  `current_balance` decimal(13,2) NOT NULL DEFAULT '0.00',
  `opening_balance` decimal(13,2) NOT NULL DEFAULT '0.00',
  `driving_license_image_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `delivery_man_driving_license_image_id_foreign` (`driving_license_image_id`),
  KEY `delivery_man_user_id_index` (`user_id`),
  CONSTRAINT `delivery_man_driving_license_image_id_foreign` FOREIGN KEY (`driving_license_image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `delivery_man_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliverycategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliverycategories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `position` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliveryman_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `deliveryman_statements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_id` bigint DEFAULT NULL,
  `parcel_id` bigint unsigned DEFAULT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `hub_id` bigint unsigned DEFAULT NULL,
  `type` tinyint unsigned DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `cash_collection` tinyint unsigned DEFAULT '0' COMMENT 'true=1,false=0',
  `date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deliveryman_statements_expense_id_index` (`expense_id`),
  KEY `deliveryman_statements_parcel_id_index` (`parcel_id`),
  KEY `deliveryman_statements_delivery_man_id_index` (`delivery_man_id`),
  KEY `deliveryman_statements_hub_id_index` (`hub_id`),
  KEY `deliveryman_statements_type_index` (`type`),
  CONSTRAINT `deliveryman_statements_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `deliveryman_statements_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `deliveryman_statements_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `departments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `departments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `designations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `designations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `platform` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Unique device identifier for mobile scanning',
  `device_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Human readable device name',
  `device_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Secure token for device authentication',
  `app_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Mobile app version',
  `fcm_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Firebase Cloud Messaging token',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Whether device is active for scanning',
  `push_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `devices_device_uuid_unique` (`device_uuid`),
  UNIQUE KEY `unique_device_id_for_mobile_scanning` (`device_id`),
  KEY `devices_user_id_foreign` (`user_id`),
  KEY `devices_device_id_index` (`device_id`),
  KEY `devices_device_token_index` (`device_token`),
  KEY `devices_is_active_platform_index` (`is_active`,`platform`),
  KEY `devices_last_seen_at_index` (`last_seen_at`),
  CONSTRAINT `devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dim_branch`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_branch` (
  `branch_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `branch_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_type` enum('HUB','REGIONAL','LOCAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOCAL',
  `is_hub` tinyint(1) NOT NULL DEFAULT '0',
  `parent_branch_key` bigint unsigned DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_province` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity_shipments_per_day` int NOT NULL DEFAULT '1000',
  `operating_hours` json DEFAULT NULL,
  `service_capabilities` json DEFAULT NULL,
  `on_time_delivery_target` decimal(5,2) NOT NULL DEFAULT '95.00',
  `customer_satisfaction_target` decimal(3,2) NOT NULL DEFAULT '4.50',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_date` date NOT NULL DEFAULT '2025-11-18',
  `expiration_date` date NOT NULL DEFAULT '9999-12-31',
  PRIMARY KEY (`branch_key`),
  UNIQUE KEY `dim_branch_branch_code_unique` (`branch_code`),
  KEY `dim_branch_branch_type_index` (`branch_type`),
  KEY `dim_branch_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `dim_branch_is_hub_is_active_index` (`is_hub`,`is_active`),
  KEY `dim_branch_parent_branch_key_foreign` (`parent_branch_key`),
  CONSTRAINT `dim_branch_parent_branch_key_foreign` FOREIGN KEY (`parent_branch_key`) REFERENCES `dim_branch` (`branch_key`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dim_carrier`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_carrier` (
  `carrier_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `carrier_id` bigint NOT NULL,
  `carrier_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carrier_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `carrier_type` enum('INTERNAL','EXTERNAL_PARTNER','THIRD_PARTY') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'INTERNAL',
  `service_modes` json DEFAULT NULL,
  `on_time_performance` decimal(5,2) DEFAULT NULL,
  `cost_per_km` decimal(8,4) DEFAULT NULL,
  `capacity_utilization` decimal(5,2) DEFAULT NULL,
  `contract_rate` decimal(8,4) DEFAULT NULL,
  `fuel_surcharge_rate` decimal(5,4) DEFAULT NULL,
  `minimum_charge` decimal(8,2) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_date` date NOT NULL DEFAULT '2025-11-18',
  `expiration_date` date NOT NULL DEFAULT '9999-12-31',
  PRIMARY KEY (`carrier_key`),
  UNIQUE KEY `dim_carrier_carrier_code_unique` (`carrier_code`),
  KEY `dim_carrier_carrier_type_index` (`carrier_type`),
  KEY `dim_carrier_on_time_performance_cost_per_km_index` (`on_time_performance`,`cost_per_km`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dim_client`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_client` (
  `client_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint NOT NULL,
  `client_code` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `business_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `industry` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client_tier` enum('ENTERPRISE','STANDARD','BASIC') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'STANDARD',
  `contract_start_date` date DEFAULT NULL,
  `contract_end_date` date DEFAULT NULL,
  `primary_contact_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_contact_email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_contact_phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_level_agreement` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority_level` tinyint NOT NULL DEFAULT '3',
  `credit_limit` decimal(12,2) NOT NULL DEFAULT '0.00',
  `payment_terms_days` tinyint NOT NULL DEFAULT '30',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_date` date NOT NULL DEFAULT '2025-11-18',
  `expiration_date` date NOT NULL DEFAULT '9999-12-31',
  `etl_batch_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_system` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_updated_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`client_key`),
  UNIQUE KEY `dim_client_client_code_unique` (`client_code`),
  KEY `dim_client_client_code_index` (`client_code`),
  KEY `dim_client_client_tier_service_level_agreement_index` (`client_tier`,`service_level_agreement`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dim_customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_customer` (
  `customer_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint NOT NULL,
  `customer_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `street_address` text COLLATE utf8mb4_unicode_ci,
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state_province` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `postal_code` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `customer_tier` enum('VIP','PREMIUM','STANDARD','BASIC') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'STANDARD',
  `acquisition_channel` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_since_date` date DEFAULT NULL,
  `total_shipments` int NOT NULL DEFAULT '0',
  `total_spend` decimal(12,2) NOT NULL DEFAULT '0.00',
  `average_order_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `last_shipment_date` date DEFAULT NULL,
  `preferred_delivery_time` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notification_preferences` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_date` date NOT NULL DEFAULT '2025-11-18',
  `expiration_date` date NOT NULL DEFAULT '9999-12-31',
  PRIMARY KEY (`customer_key`),
  UNIQUE KEY `dim_customer_customer_code_unique` (`customer_code`),
  KEY `dim_customer_customer_tier_index` (`customer_tier`),
  KEY `dim_customer_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `dim_customer_total_spend_average_order_value_index` (`total_spend`,`average_order_value`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dim_driver`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_driver` (
  `driver_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `driver_id` bigint unsigned NOT NULL,
  `employee_id` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `first_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `full_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `license_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry_date` date DEFAULT NULL,
  `total_deliveries` int NOT NULL DEFAULT '0',
  `on_time_delivery_rate` decimal(5,2) DEFAULT NULL,
  `customer_rating` decimal(3,2) DEFAULT NULL,
  `accident_count` int NOT NULL DEFAULT '0',
  `primary_branch_key` bigint unsigned DEFAULT NULL,
  `service_areas` json DEFAULT NULL,
  `hire_date` date DEFAULT NULL,
  `employment_status` enum('ACTIVE','INACTIVE','TERMINATED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  PRIMARY KEY (`driver_key`),
  KEY `dim_driver_on_time_delivery_rate_customer_rating_index` (`on_time_delivery_rate`,`customer_rating`),
  KEY `dim_driver_primary_branch_key_index` (`primary_branch_key`),
  CONSTRAINT `dim_driver_primary_branch_key_foreign` FOREIGN KEY (`primary_branch_key`) REFERENCES `dim_branch` (`branch_key`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dim_time`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dim_time` (
  `date_key` int NOT NULL,
  `full_date` date NOT NULL,
  `day_of_week` tinyint NOT NULL,
  `day_name` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `day_of_month` tinyint NOT NULL,
  `day_of_year` smallint NOT NULL,
  `week_of_year` tinyint NOT NULL,
  `month_number` tinyint NOT NULL,
  `month_name` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `quarter_number` tinyint NOT NULL,
  `quarter_name` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `year_number` smallint NOT NULL,
  `is_weekend` tinyint(1) NOT NULL,
  `is_holiday` tinyint(1) NOT NULL,
  `fiscal_year` smallint NOT NULL,
  `fiscal_quarter` tinyint NOT NULL,
  PRIMARY KEY (`date_key`),
  UNIQUE KEY `dim_time_full_date_unique` (`full_date`),
  KEY `dim_time_year_number_quarter_number_index` (`year_number`,`quarter_number`),
  KEY `dim_time_month_number_year_number_index` (`month_number`,`year_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dimension_churn_factors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dimension_churn_factors` (
  `factor_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `factor_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `factor_category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `factor_description` text COLLATE utf8mb4_unicode_ci,
  `weight_in_model` decimal(8,4) NOT NULL,
  `is_predictive` tinyint(1) NOT NULL,
  `is_preventable` tinyint(1) NOT NULL,
  `typical_impact_range` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recommended_intervention` text COLLATE utf8mb4_unicode_ci,
  `monitoring_threshold` decimal(8,4) DEFAULT NULL,
  `factor_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `data_source` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `calculation_method` text COLLATE utf8mb4_unicode_ci,
  `last_updated` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`factor_key`),
  KEY `dimension_churn_factors_factor_category_index` (`factor_category`),
  KEY `dimension_churn_factors_factor_type_index` (`factor_type`),
  KEY `dimension_churn_factors_is_predictive_index` (`is_predictive`),
  KEY `dimension_churn_factors_is_preventable_index` (`is_preventable`),
  KEY `dimension_churn_factors_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dimension_customer_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dimension_customer_segments` (
  `segment_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segment_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segment_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segment_description` text COLLATE utf8mb4_unicode_ci,
  `volume_criteria` json DEFAULT NULL,
  `profitability_criteria` json DEFAULT NULL,
  `behavioral_criteria` json DEFAULT NULL,
  `value_score_range` json DEFAULT NULL,
  `engagement_criteria` json DEFAULT NULL,
  `lifecycle_stage_criteria` json DEFAULT NULL,
  `retention_risk_range` json DEFAULT NULL,
  `growth_potential_score` decimal(8,4) DEFAULT NULL,
  `targeting_criteria` json DEFAULT NULL,
  `marketing_messaging` json DEFAULT NULL,
  `retention_strategies` json DEFAULT NULL,
  `upsell_opportunities` json DEFAULT NULL,
  `cross_sell_opportunities` json DEFAULT NULL,
  `priority_level` int NOT NULL DEFAULT '5',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`segment_key`),
  KEY `dimension_customer_segments_segment_type_index` (`segment_type`),
  KEY `dimension_customer_segments_priority_level_index` (`priority_level`),
  KEY `dimension_customer_segments_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dimension_sentiment_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dimension_sentiment_categories` (
  `category_key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `sentiment_score_range` json DEFAULT NULL,
  `emotion_tags` json DEFAULT NULL,
  `response_priority` int NOT NULL DEFAULT '5',
  `escalation_required` tinyint(1) NOT NULL DEFAULT '0',
  `recommended_actions` json DEFAULT NULL,
  `sla_response_time` int DEFAULT NULL,
  `nps_impact_score` decimal(8,4) NOT NULL DEFAULT '0.0000',
  `category_group` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`category_key`),
  KEY `dimension_sentiment_categories_category_type_index` (`category_type`),
  KEY `dimension_sentiment_categories_category_group_index` (`category_group`),
  KEY `dimension_sentiment_categories_escalation_required_index` (`escalation_required`),
  KEY `dimension_sentiment_categories_response_priority_index` (`response_priority`),
  KEY `dimension_sentiment_categories_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dps_screenings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dps_screenings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `screened_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `screened_id` bigint unsigned NOT NULL,
  `query` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_json` json DEFAULT NULL,
  `result` enum('clear','hit') COLLATE utf8mb4_unicode_ci NOT NULL,
  `list_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `match_score` decimal(5,2) DEFAULT NULL,
  `screened_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `driver_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `driver_id` bigint unsigned NOT NULL,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `timestamp` timestamp NOT NULL,
  `accuracy` decimal(8,2) DEFAULT NULL,
  `speed` decimal(8,2) DEFAULT NULL,
  `heading` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `driver_locations_driver_id_timestamp_index` (`driver_id`,`timestamp`),
  KEY `driver_locations_timestamp_index` (`timestamp`),
  CONSTRAINT `driver_locations_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `driver_rosters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_rosters` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `driver_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `shift_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `start_time` timestamp NOT NULL,
  `end_time` timestamp NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SCHEDULED',
  `planned_hours` int DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `driver_rosters_branch_id_foreign` (`branch_id`),
  KEY `driver_rosters_driver_id_status_index` (`driver_id`,`status`),
  KEY `driver_rosters_start_time_index` (`start_time`),
  CONSTRAINT `driver_rosters_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `driver_rosters_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `driver_time_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `driver_time_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `driver_id` bigint unsigned NOT NULL,
  `roster_id` bigint unsigned DEFAULT NULL,
  `log_type` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `logged_at` timestamp NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `source` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `driver_time_logs_roster_id_foreign` (`roster_id`),
  KEY `driver_time_logs_driver_id_log_type_index` (`driver_id`,`log_type`),
  KEY `driver_time_logs_logged_at_index` (`logged_at`),
  CONSTRAINT `driver_time_logs_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `drivers` (`id`) ON DELETE CASCADE,
  CONSTRAINT `driver_time_logs_roster_id_foreign` FOREIGN KEY (`roster_id`) REFERENCES `driver_rosters` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `drivers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `drivers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `employment_status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `license_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `license_expiry` date DEFAULT NULL,
  `vehicle_id` bigint unsigned DEFAULT NULL,
  `documents` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `onboarded_at` timestamp NULL DEFAULT NULL,
  `offboarded_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `drivers_code_unique` (`code`),
  KEY `drivers_user_id_foreign` (`user_id`),
  KEY `drivers_vehicle_id_foreign` (`vehicle_id`),
  KEY `drivers_branch_id_status_index` (`branch_id`,`status`),
  KEY `drivers_employment_status_index` (`employment_status`),
  CONSTRAINT `drivers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `drivers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `drivers_vehicle_id_foreign` FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ecmrs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ecmrs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cmr_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `road_carrier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_branch_id` bigint unsigned NOT NULL,
  `destination_branch_id` bigint unsigned NOT NULL,
  `doc_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','issued','delivered') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ecmrs_origin_branch_id_foreign` (`origin_branch_id`),
  KEY `ecmrs_destination_branch_id_foreign` (`destination_branch_id`),
  CONSTRAINT `ecmrs_destination_branch_id_foreign` FOREIGN KEY (`destination_branch_id`) REFERENCES `hubs` (`id`),
  CONSTRAINT `ecmrs_origin_branch_id_foreign` FOREIGN KEY (`origin_branch_id`) REFERENCES `hubs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `edi_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edi_mappings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `document_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `field_map` json NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `edi_mappings_document_type_version_unique` (`document_type`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `edi_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edi_providers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `config` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `edi_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edi_transactions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `provider_id` bigint unsigned DEFAULT NULL,
  `document_type` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `direction` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'inbound',
  `document_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'received',
  `external_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `correlation_id` char(36) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json NOT NULL,
  `normalized_payload` json DEFAULT NULL,
  `ack_payload` json DEFAULT NULL,
  `acknowledged_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `edi_transactions_provider_id_foreign` (`provider_id`),
  KEY `edi_transactions_document_type_status_index` (`document_type`,`status`),
  KEY `edi_transactions_document_number_index` (`document_number`),
  KEY `edi_transactions_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `edi_transactions_provider_id_foreign` FOREIGN KEY (`provider_id`) REFERENCES `edi_providers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `epods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `epods` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stop_id` bigint unsigned NOT NULL,
  `signer_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_image_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo_paths` json DEFAULT NULL,
  `gps_point` json DEFAULT NULL,
  `completed_at` timestamp NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `epods_stop_id_index` (`stop_id`),
  KEY `epods_completed_at_index` (`completed_at`),
  CONSTRAINT `epods_stop_id_foreign` FOREIGN KEY (`stop_id`) REFERENCES `stops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etl_anomaly_detection`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etl_anomaly_detection` (
  `anomaly_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` bigint unsigned DEFAULT NULL,
  `anomaly_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `anomaly_category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity_score` decimal(5,3) NOT NULL,
  `detection_method` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `anomaly_data` json DEFAULT NULL,
  `context_data` json DEFAULT NULL,
  `batch_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('DETECTED','INVESTIGATED','CONFIRMED','FALSE_POSITIVE') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DETECTED',
  `investigation_notes` text COLLATE utf8mb4_unicode_ci,
  `investigated_by` bigint unsigned DEFAULT NULL,
  `investigated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`anomaly_id`),
  KEY `etl_anomaly_detection_table_name_anomaly_type_index` (`table_name`,`anomaly_type`),
  KEY `etl_anomaly_detection_severity_score_status_index` (`severity_score`,`status`),
  KEY `etl_anomaly_detection_batch_id_index` (`batch_id`),
  KEY `etl_anomaly_detection_investigated_by_foreign` (`investigated_by`),
  CONSTRAINT `etl_anomaly_detection_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `etl_batches` (`batch_id`) ON DELETE CASCADE,
  CONSTRAINT `etl_anomaly_detection_investigated_by_foreign` FOREIGN KEY (`investigated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etl_audit_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etl_audit_log` (
  `audit_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` bigint unsigned NOT NULL,
  `operation` enum('INSERT','UPDATE','DELETE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `change_type` enum('MANUAL','ETL_BATCH','API_IMPORT','SYSTEM_UPDATE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `before_values` json DEFAULT NULL,
  `after_values` json DEFAULT NULL,
  `changed_fields` json DEFAULT NULL,
  `batch_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_system` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `data_quality_score` decimal(3,2) DEFAULT NULL,
  `validation_errors` json DEFAULT NULL,
  `anomaly_flags` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`audit_id`),
  KEY `etl_audit_log_table_name_record_id_index` (`table_name`,`record_id`),
  KEY `etl_audit_log_batch_id_index` (`batch_id`),
  KEY `etl_audit_log_operation_created_at_index` (`operation`,`created_at`),
  KEY `etl_audit_log_user_id_foreign` (`user_id`),
  CONSTRAINT `etl_audit_log_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `etl_batches` (`batch_id`) ON DELETE SET NULL,
  CONSTRAINT `etl_audit_log_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etl_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etl_batches` (
  `batch_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pipeline_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `records_processed` int NOT NULL DEFAULT '0',
  `records_successful` int NOT NULL DEFAULT '0',
  `records_failed` int NOT NULL DEFAULT '0',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `execution_metrics` json DEFAULT NULL,
  `triggered_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`batch_id`),
  KEY `etl_batches_pipeline_name_status_index` (`pipeline_name`,`status`),
  KEY `etl_batches_started_at_index` (`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etl_data_lineage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etl_data_lineage` (
  `lineage_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source_table` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_record_id` bigint unsigned NOT NULL,
  `target_table` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `target_record_id` bigint unsigned NOT NULL,
  `transformation_rules` json DEFAULT NULL,
  `batch_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`lineage_id`),
  KEY `etl_data_lineage_source_table_source_record_id_index` (`source_table`,`source_record_id`),
  KEY `etl_data_lineage_target_table_target_record_id_index` (`target_table`,`target_record_id`),
  KEY `etl_data_lineage_batch_id_index` (`batch_id`),
  CONSTRAINT `etl_data_lineage_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `etl_batches` (`batch_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etl_data_quality_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etl_data_quality_rules` (
  `rule_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_definition` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MEDIUM',
  `action_on_violation` enum('REJECT','FLAG','LOG','CORRECT') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'FLAG',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `rule_parameters` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`rule_id`),
  KEY `etl_data_quality_rules_table_name_rule_type_index` (`table_name`,`rule_type`),
  KEY `etl_data_quality_rules_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etl_data_quality_violations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etl_data_quality_violations` (
  `violation_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rule_id` bigint unsigned NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `record_id` bigint unsigned NOT NULL,
  `violation_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `violation_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `violation_details` text COLLATE utf8mb4_unicode_ci,
  `severity` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('OPEN','RESOLVED','IGNORED','ESCALATED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'OPEN',
  `batch_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolved_by` bigint unsigned DEFAULT NULL,
  `resolution_notes` text COLLATE utf8mb4_unicode_ci,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`violation_id`),
  KEY `etl_data_quality_violations_rule_id_index` (`rule_id`),
  KEY `etl_data_quality_violations_table_name_record_id_index` (`table_name`,`record_id`),
  KEY `etl_data_quality_violations_status_severity_index` (`status`,`severity`),
  KEY `etl_data_quality_violations_batch_id_index` (`batch_id`),
  KEY `etl_data_quality_violations_resolved_by_foreign` (`resolved_by`),
  CONSTRAINT `etl_data_quality_violations_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `etl_batches` (`batch_id`) ON DELETE CASCADE,
  CONSTRAINT `etl_data_quality_violations_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `etl_data_quality_violations_rule_id_foreign` FOREIGN KEY (`rule_id`) REFERENCES `etl_data_quality_rules` (`rule_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etl_pipeline_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etl_pipeline_configs` (
  `config_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `pipeline_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pipeline_version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_configurations` json NOT NULL,
  `transformation_configurations` json NOT NULL,
  `destination_configurations` json NOT NULL,
  `quality_rules` json NOT NULL,
  `schedule_expression` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `execution_parameters` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`config_id`),
  KEY `etl_pipeline_configs_pipeline_name_index` (`pipeline_name`),
  KEY `etl_pipeline_configs_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etl_report_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etl_report_definitions` (
  `report_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `report_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `report_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sql_query` json NOT NULL,
  `parameters` json DEFAULT NULL,
  `visualization_config` json DEFAULT NULL,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '1.0.0',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'DRAFT',
  `created_by` bigint unsigned NOT NULL,
  `approved_by` bigint unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`report_id`),
  UNIQUE KEY `etl_report_definitions_report_code_unique` (`report_code`),
  KEY `etl_report_definitions_report_type_index` (`report_type`),
  KEY `etl_report_definitions_status_index` (`status`),
  KEY `etl_report_definitions_created_by_index` (`created_by`),
  KEY `etl_report_definitions_approved_by_foreign` (`approved_by`),
  CONSTRAINT `etl_report_definitions_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `etl_report_definitions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `etl_report_version_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `etl_report_version_history` (
  `version_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sql_query` json NOT NULL,
  `parameters` json DEFAULT NULL,
  `change_log` json DEFAULT NULL,
  `change_reason` text COLLATE utf8mb4_unicode_ci,
  `version_created_by` bigint unsigned NOT NULL,
  `version_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`version_id`),
  KEY `etl_report_version_history_report_id_index` (`report_id`),
  KEY `etl_report_version_history_report_id_version_index` (`report_id`,`version`),
  KEY `etl_report_version_history_version_created_by_foreign` (`version_created_by`),
  CONSTRAINT `etl_report_version_history_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `etl_report_definitions` (`report_id`) ON DELETE CASCADE,
  CONSTRAINT `etl_report_version_history_version_created_by_foreign` FOREIGN KEY (`version_created_by`) REFERENCES `users` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `event_streams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `event_streams` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aggregate_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `aggregate_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `actor_id` bigint unsigned DEFAULT NULL,
  `payload` json NOT NULL,
  `metadata` json DEFAULT NULL,
  `timestamp` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `event_streams_actor_id_foreign` (`actor_id`),
  KEY `event_streams_aggregate_type_aggregate_id_timestamp_index` (`aggregate_type`,`aggregate_id`,`timestamp`),
  KEY `event_streams_event_type_index` (`event_type`),
  KEY `event_streams_timestamp_index` (`timestamp`),
  CONSTRAINT `event_streams_actor_id_foreign` FOREIGN KEY (`actor_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `expenses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_head_id` bigint unsigned DEFAULT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `parcel_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `receipt` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `title` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `expenses_receipt_foreign` (`receipt`),
  KEY `expenses_account_head_id_index` (`account_head_id`),
  KEY `expenses_merchant_id_index` (`merchant_id`),
  KEY `expenses_delivery_man_id_index` (`delivery_man_id`),
  KEY `expenses_user_id_index` (`user_id`),
  KEY `expenses_parcel_id_index` (`parcel_id`),
  KEY `expenses_account_id_index` (`account_id`),
  KEY `expenses_date_index` (`date`),
  CONSTRAINT `expenses_account_head_id_foreign` FOREIGN KEY (`account_head_id`) REFERENCES `account_heads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `expenses_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `expenses_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `expenses_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `expenses_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `expenses_receipt_foreign` FOREIGN KEY (`receipt`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `expenses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fact_customer_analytics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_customer_analytics` (
  `analytics_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_key` bigint unsigned NOT NULL,
  `date_key` int NOT NULL,
  `shipments_count` int NOT NULL DEFAULT '0',
  `total_spend` decimal(12,2) NOT NULL DEFAULT '0.00',
  `average_order_value` decimal(10,2) NOT NULL DEFAULT '0.00',
  `average_delivery_time_hours` decimal(8,2) DEFAULT NULL,
  `days_since_last_shipment` int DEFAULT NULL,
  `shipment_frequency_per_month` decimal(4,2) NOT NULL DEFAULT '0.00',
  `preferred_service_type` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `premium_service_usage_rate` decimal(5,2) NOT NULL DEFAULT '0.00',
  `complaint_count` int NOT NULL DEFAULT '0',
  `customer_lifetime_value` decimal(12,2) NOT NULL DEFAULT '0.00',
  `churn_probability` decimal(5,4) NOT NULL DEFAULT '0.0000',
  PRIMARY KEY (`analytics_key`),
  KEY `fact_customer_date_idx` (`customer_key`,`date_key`),
  KEY `fact_customer_value_idx` (`total_spend`,`customer_lifetime_value`),
  KEY `fact_customer_analytics_date_key_foreign` (`date_key`),
  CONSTRAINT `fact_customer_analytics_customer_key_foreign` FOREIGN KEY (`customer_key`) REFERENCES `dim_customer` (`customer_key`) ON DELETE RESTRICT,
  CONSTRAINT `fact_customer_analytics_date_key_foreign` FOREIGN KEY (`date_key`) REFERENCES `dim_time` (`date_key`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fact_customer_churn_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_customer_churn_metrics` (
  `churn_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_key` bigint unsigned NOT NULL,
  `churn_date_key` int NOT NULL,
  `churn_probability` decimal(8,4) NOT NULL,
  `risk_score` decimal(8,4) NOT NULL,
  `retention_score` decimal(8,4) NOT NULL,
  `days_since_last_shipment` int NOT NULL,
  `total_shipments_90_days` int NOT NULL,
  `complaints_count_90_days` int NOT NULL,
  `payment_delays_90_days` int NOT NULL,
  `credit_utilization` decimal(8,4) NOT NULL,
  `churn_indicators` json DEFAULT NULL,
  `primary_churn_factors` json DEFAULT NULL,
  `secondary_churn_factors` json DEFAULT NULL,
  `predicted_churn_date` date DEFAULT NULL,
  `recommended_actions` json DEFAULT NULL,
  `model_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `confidence_level` decimal(8,4) NOT NULL,
  PRIMARY KEY (`churn_key`),
  KEY `fact_customer_churn_metrics_client_key_index` (`client_key`),
  KEY `fact_customer_churn_metrics_churn_date_key_index` (`churn_date_key`),
  KEY `fact_customer_churn_metrics_churn_probability_index` (`churn_probability`),
  KEY `fact_customer_churn_metrics_risk_score_index` (`risk_score`),
  KEY `fact_customer_churn_metrics_client_key_churn_date_key_index` (`client_key`,`churn_date_key`),
  CONSTRAINT `fact_customer_churn_metrics_churn_date_key_foreign` FOREIGN KEY (`churn_date_key`) REFERENCES `dim_time` (`date_key`) ON DELETE CASCADE,
  CONSTRAINT `fact_customer_churn_metrics_client_key_foreign` FOREIGN KEY (`client_key`) REFERENCES `dim_client` (`client_key`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fact_customer_segments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_customer_segments` (
  `segment_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_key` bigint unsigned NOT NULL,
  `segment_date_key` int NOT NULL,
  `primary_segment` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secondary_segments` json DEFAULT NULL,
  `volume_tier` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `profitability_tier` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `behavioral_segment` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lifecycle_stage` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rfm_score` decimal(8,4) NOT NULL,
  `segmentation_criteria` json DEFAULT NULL,
  `value_score` decimal(8,4) NOT NULL,
  `engagement_score` decimal(8,4) NOT NULL,
  `loyalty_score` decimal(8,4) NOT NULL,
  `growth_potential` decimal(8,4) NOT NULL,
  `retention_risk` decimal(8,4) NOT NULL,
  `upsell_opportunities` json DEFAULT NULL,
  `cross_sell_opportunities` json DEFAULT NULL,
  `preferred_communication_channel` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `segment_characteristics` json DEFAULT NULL,
  `segment_changes` json DEFAULT NULL,
  `model_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`segment_key`),
  KEY `fact_customer_segments_client_key_index` (`client_key`),
  KEY `fact_customer_segments_segment_date_key_index` (`segment_date_key`),
  KEY `fact_customer_segments_primary_segment_index` (`primary_segment`),
  KEY `fact_customer_segments_volume_tier_index` (`volume_tier`),
  KEY `fact_customer_segments_profitability_tier_index` (`profitability_tier`),
  KEY `fact_customer_segments_lifecycle_stage_index` (`lifecycle_stage`),
  KEY `fact_customer_segments_rfm_score_index` (`rfm_score`),
  KEY `fact_customer_segments_value_score_index` (`value_score`),
  KEY `fact_customer_segments_engagement_score_index` (`engagement_score`),
  KEY `fact_customer_segments_client_key_segment_date_key_index` (`client_key`,`segment_date_key`),
  CONSTRAINT `fact_customer_segments_client_key_foreign` FOREIGN KEY (`client_key`) REFERENCES `dim_client` (`client_key`) ON DELETE CASCADE,
  CONSTRAINT `fact_customer_segments_segment_date_key_foreign` FOREIGN KEY (`segment_date_key`) REFERENCES `dim_time` (`date_key`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fact_customer_sentiment`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_customer_sentiment` (
  `sentiment_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_key` bigint unsigned NOT NULL,
  `ticket_key` bigint unsigned DEFAULT NULL,
  `sentiment_date_key` int NOT NULL,
  `nps_score` int NOT NULL,
  `sentiment_score` decimal(8,4) NOT NULL,
  `confidence_level` decimal(8,4) NOT NULL,
  `feedback_category` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `primary_emotion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `emotion_intensity` decimal(8,4) NOT NULL,
  `language_detected` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `support_channel` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ticket_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resolution_time_hours` decimal(8,2) DEFAULT NULL,
  `customer_satisfaction_rating` int DEFAULT NULL,
  `sentiment_trend` json DEFAULT NULL,
  `feedback_keywords` json DEFAULT NULL,
  `model_version` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `analysis_metadata` json DEFAULT NULL,
  PRIMARY KEY (`sentiment_key`),
  KEY `fact_customer_sentiment_client_key_index` (`client_key`),
  KEY `fact_customer_sentiment_sentiment_date_key_index` (`sentiment_date_key`),
  KEY `fact_customer_sentiment_nps_score_index` (`nps_score`),
  KEY `fact_customer_sentiment_sentiment_score_index` (`sentiment_score`),
  KEY `fact_customer_sentiment_feedback_category_index` (`feedback_category`),
  KEY `fact_customer_sentiment_client_key_sentiment_date_key_index` (`client_key`,`sentiment_date_key`),
  CONSTRAINT `fact_customer_sentiment_client_key_foreign` FOREIGN KEY (`client_key`) REFERENCES `dim_client` (`client_key`) ON DELETE CASCADE,
  CONSTRAINT `fact_customer_sentiment_sentiment_date_key_foreign` FOREIGN KEY (`sentiment_date_key`) REFERENCES `dim_time` (`date_key`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fact_financial_transactions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_financial_transactions` (
  `transaction_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipment_key` bigint unsigned DEFAULT NULL,
  `client_key` bigint unsigned DEFAULT NULL,
  `customer_key` bigint unsigned DEFAULT NULL,
  `branch_key` bigint unsigned DEFAULT NULL,
  `transaction_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `transaction_category` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `account_key` bigint DEFAULT NULL,
  `debit_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `credit_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `running_balance` decimal(12,2) DEFAULT NULL,
  `transaction_date_key` int NOT NULL,
  `transaction_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `reference_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'POSTED',
  `etl_batch_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_system` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`transaction_key`),
  UNIQUE KEY `fact_financial_transactions_transaction_id_unique` (`transaction_id`),
  KEY `fact_fin_tx_client_date_idx` (`client_key`,`transaction_date_key`),
  KEY `fact_fin_tx_account_date_idx` (`account_key`,`transaction_date_key`),
  KEY `fact_fin_tx_shipment_idx` (`shipment_key`),
  KEY `fact_fin_tx_type_category_idx` (`transaction_type`,`transaction_category`),
  KEY `fact_financial_transactions_customer_key_foreign` (`customer_key`),
  KEY `fact_financial_transactions_branch_key_foreign` (`branch_key`),
  KEY `fact_financial_transactions_transaction_date_key_foreign` (`transaction_date_key`),
  CONSTRAINT `fact_financial_transactions_branch_key_foreign` FOREIGN KEY (`branch_key`) REFERENCES `dim_branch` (`branch_key`) ON DELETE SET NULL,
  CONSTRAINT `fact_financial_transactions_client_key_foreign` FOREIGN KEY (`client_key`) REFERENCES `dim_client` (`client_key`) ON DELETE SET NULL,
  CONSTRAINT `fact_financial_transactions_customer_key_foreign` FOREIGN KEY (`customer_key`) REFERENCES `dim_customer` (`customer_key`) ON DELETE SET NULL,
  CONSTRAINT `fact_financial_transactions_shipment_key_foreign` FOREIGN KEY (`shipment_key`) REFERENCES `fact_shipments` (`shipment_key`) ON DELETE SET NULL,
  CONSTRAINT `fact_financial_transactions_transaction_date_key_foreign` FOREIGN KEY (`transaction_date_key`) REFERENCES `dim_time` (`date_key`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fact_performance_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_performance_metrics` (
  `metric_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_key` bigint unsigned NOT NULL,
  `date_key` int NOT NULL,
  `total_shipments` int NOT NULL DEFAULT '0',
  `delivered_shipments` int NOT NULL DEFAULT '0',
  `returned_shipments` int NOT NULL DEFAULT '0',
  `exception_shipments` int NOT NULL DEFAULT '0',
  `cancelled_shipments` int NOT NULL DEFAULT '0',
  `on_time_delivery_rate` decimal(5,2) DEFAULT NULL,
  `first_attempt_success_rate` decimal(5,2) DEFAULT NULL,
  `average_delivery_time_hours` decimal(8,2) DEFAULT NULL,
  `average_delivery_attempts` decimal(4,2) NOT NULL DEFAULT '0.00',
  `total_revenue` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total_margin` decimal(12,2) NOT NULL DEFAULT '0.00',
  `margin_percentage` decimal(5,2) DEFAULT NULL,
  `cod_collected` decimal(12,2) NOT NULL DEFAULT '0.00',
  `customer_complaints` int NOT NULL DEFAULT '0',
  `customer_satisfaction_score` decimal(3,2) DEFAULT NULL,
  `vehicle_utilization_rate` decimal(5,2) DEFAULT NULL,
  `driver_utilization_rate` decimal(5,2) DEFAULT NULL,
  `total_distance_km` int unsigned NOT NULL DEFAULT '0',
  `fuel_consumption_liters` decimal(8,2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`metric_key`),
  KEY `fact_perf_branch_date_idx` (`branch_key`,`date_key`),
  KEY `fact_perf_rates_idx` (`on_time_delivery_rate`,`margin_percentage`),
  KEY `fact_performance_metrics_date_key_foreign` (`date_key`),
  CONSTRAINT `fact_performance_metrics_branch_key_foreign` FOREIGN KEY (`branch_key`) REFERENCES `dim_branch` (`branch_key`) ON DELETE RESTRICT,
  CONSTRAINT `fact_performance_metrics_date_key_foreign` FOREIGN KEY (`date_key`) REFERENCES `dim_time` (`date_key`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fact_shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fact_shipments` (
  `shipment_key` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `tracking_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `client_key` bigint unsigned NOT NULL,
  `origin_branch_key` bigint unsigned NOT NULL,
  `dest_branch_key` bigint unsigned NOT NULL,
  `carrier_key` bigint unsigned DEFAULT NULL,
  `driver_key` bigint unsigned DEFAULT NULL,
  `customer_key` bigint unsigned NOT NULL,
  `status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `current_status` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CREATED',
  `pickup_date_key` int DEFAULT NULL,
  `delivery_date_key` int DEFAULT NULL,
  `scheduled_delivery_date_key` int DEFAULT NULL,
  `actual_delivery_duration_minutes` int DEFAULT NULL,
  `scheduled_delivery_duration_minutes` int DEFAULT NULL,
  `declared_value` decimal(12,2) DEFAULT NULL,
  `shipping_charge` decimal(10,2) NOT NULL DEFAULT '0.00',
  `cod_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
  `fuel_surcharge` decimal(10,2) NOT NULL DEFAULT '0.00',
  `insurance_cost` decimal(8,2) NOT NULL DEFAULT '0.00',
  `total_cost` decimal(10,2) NOT NULL DEFAULT '0.00',
  `revenue` decimal(10,2) NOT NULL DEFAULT '0.00',
  `margin` decimal(10,2) NOT NULL DEFAULT '0.00',
  `margin_percentage` decimal(5,2) DEFAULT NULL,
  `weight_kg` decimal(8,3) DEFAULT NULL,
  `distance_km` decimal(8,2) DEFAULT NULL,
  `delivery_attempts` int NOT NULL DEFAULT '0',
  `exception_flag` tinyint(1) NOT NULL DEFAULT '0',
  `returned_flag` tinyint(1) NOT NULL DEFAULT '0',
  `exception_reason` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_date_key` int NOT NULL,
  `created_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `etl_batch_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_system` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_quality_score` decimal(3,2) DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  PRIMARY KEY (`shipment_key`),
  KEY `fact_shipments_client_status_idx` (`client_key`,`status`),
  KEY `fact_shipments_delivery_dates_idx` (`pickup_date_key`,`delivery_date_key`),
  KEY `fact_shipments_branch_idx` (`origin_branch_key`,`dest_branch_key`),
  KEY `fact_shipments_margin_attempts_idx` (`margin_percentage`,`delivery_attempts`),
  KEY `fact_shipments_status_idx` (`status`,`current_status`),
  KEY `fact_shipments_dest_branch_key_foreign` (`dest_branch_key`),
  KEY `fact_shipments_carrier_key_foreign` (`carrier_key`),
  KEY `fact_shipments_driver_key_foreign` (`driver_key`),
  KEY `fact_shipments_customer_key_foreign` (`customer_key`),
  KEY `fact_shipments_delivery_date_key_foreign` (`delivery_date_key`),
  KEY `fact_shipments_created_date_key_foreign` (`created_date_key`),
  CONSTRAINT `fact_shipments_carrier_key_foreign` FOREIGN KEY (`carrier_key`) REFERENCES `dim_carrier` (`carrier_key`) ON DELETE SET NULL,
  CONSTRAINT `fact_shipments_client_key_foreign` FOREIGN KEY (`client_key`) REFERENCES `dim_client` (`client_key`) ON DELETE RESTRICT,
  CONSTRAINT `fact_shipments_created_date_key_foreign` FOREIGN KEY (`created_date_key`) REFERENCES `dim_time` (`date_key`) ON DELETE RESTRICT,
  CONSTRAINT `fact_shipments_customer_key_foreign` FOREIGN KEY (`customer_key`) REFERENCES `dim_customer` (`customer_key`) ON DELETE RESTRICT,
  CONSTRAINT `fact_shipments_delivery_date_key_foreign` FOREIGN KEY (`delivery_date_key`) REFERENCES `dim_time` (`date_key`) ON DELETE RESTRICT,
  CONSTRAINT `fact_shipments_dest_branch_key_foreign` FOREIGN KEY (`dest_branch_key`) REFERENCES `dim_branch` (`branch_key`) ON DELETE RESTRICT,
  CONSTRAINT `fact_shipments_driver_key_foreign` FOREIGN KEY (`driver_key`) REFERENCES `dim_driver` (`driver_key`) ON DELETE SET NULL,
  CONSTRAINT `fact_shipments_origin_branch_key_foreign` FOREIGN KEY (`origin_branch_key`) REFERENCES `dim_branch` (`branch_key`) ON DELETE RESTRICT,
  CONSTRAINT `fact_shipments_pickup_date_key_foreign` FOREIGN KEY (`pickup_date_key`) REFERENCES `dim_time` (`date_key`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `faqs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `faqs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `question` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `answer` longtext COLLATE utf8mb4_unicode_ci,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Active, 0= Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `frauds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `frauds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `created_by` bigint unsigned DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `frauds_created_by_index` (`created_by`),
  CONSTRAINT `frauds_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fuel_indices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fuel_indices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source` enum('eia','opec','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `index_value` decimal(6,2) NOT NULL,
  `region` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `effective_date` date NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fuel_indices_source_effective_date_region_unique` (`source`,`effective_date`,`region`),
  KEY `fuel_indices_effective_date_index` (`effective_date`),
  KEY `fuel_indices_source_effective_date_index` (`source`,`effective_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fuels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fuels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint DEFAULT NULL,
  `fuel_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_of_fuel` bigint DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fund_transfers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fund_transfers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `from_account` bigint unsigned DEFAULT NULL,
  `to_account` bigint unsigned DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fund_transfers_from_account_index` (`from_account`),
  KEY `fund_transfers_to_account_index` (`to_account`),
  CONSTRAINT `fund_transfers_from_account_foreign` FOREIGN KEY (`from_account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fund_transfers_to_account_foreign` FOREIGN KEY (`to_account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `fx_rates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `fx_rates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `base` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `counter` char(3) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate` decimal(16,8) NOT NULL,
  `provider` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `effective_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fx_rates_base_counter_effective_at_unique` (`base`,`counter`,`effective_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `general_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `general_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `details` json DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` longtext COLLATE utf8mb4_unicode_ci,
  `currency` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `currency_id` bigint unsigned DEFAULT NULL,
  `copyright` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo` int DEFAULT NULL,
  `light_logo` int DEFAULT NULL,
  `favicon` int DEFAULT NULL,
  `current_version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `par_track_prefix` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_prefix` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `primary_color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT '#7e0095',
  `text_color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT '#ffffff',
  `version` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT '1.4',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `general_settings_currency_id_index` (`currency_id`),
  KEY `general_settings_tracking_id_index` (`tracking_id`),
  CONSTRAINT `general_settings_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `google_map_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `google_map_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `map_key` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hs_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hs_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `duty_rate_percent` decimal(5,2) DEFAULT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `requires_permit` tinyint(1) NOT NULL DEFAULT '0',
  `restrictions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hs_codes_code_unique` (`code`),
  KEY `hs_codes_code_index` (`code`),
  KEY `hs_codes_category_index` (`category`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hub_incharges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hub_incharges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `hub_id` bigint unsigned NOT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hub_incharges_user_id_index` (`user_id`),
  KEY `hub_incharges_hub_id_index` (`hub_id`),
  CONSTRAINT `hub_incharges_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `hub_incharges_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hub_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hub_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `hub_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `from_account` bigint unsigned DEFAULT NULL,
  `reference_file` bigint unsigned DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL COMMENT '1=admin,4=incharge',
  `status` tinyint unsigned NOT NULL DEFAULT '3' COMMENT '1= Reject,2=Approved , 3= Pending,4=Process, ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hub_payments_reference_file_foreign` (`reference_file`),
  KEY `hub_payments_hub_id_index` (`hub_id`),
  KEY `hub_payments_created_by_index` (`created_by`),
  KEY `hub_payments_status_index` (`status`),
  KEY `hub_payments_from_account_index` (`from_account`),
  CONSTRAINT `hub_payments_from_account_foreign` FOREIGN KEY (`from_account`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `hub_payments_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `hub_payments_reference_file_foreign` FOREIGN KEY (`reference_file`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hub_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hub_statements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `hub_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `type` tinyint unsigned DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `hub_statements_user_id_index` (`user_id`),
  KEY `hub_statements_hub_id_index` (`hub_id`),
  KEY `hub_statements_account_id_index` (`account_id`),
  KEY `hub_statements_delivery_man_id_index` (`delivery_man_id`),
  CONSTRAINT `hub_statements_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `hub_statements_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `hub_statements_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `hub_statements_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hubs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'regional',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `manager_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `has_automated_sorting` tinyint(1) NOT NULL DEFAULT '0',
  `has_tracking_system` tinyint(1) NOT NULL DEFAULT '1',
  `has_security_system` tinyint(1) NOT NULL DEFAULT '1',
  `kpi_targets` json DEFAULT NULL,
  `performance_metrics` json DEFAULT NULL,
  `operating_hours_start` time NOT NULL DEFAULT '08:00:00',
  `operating_hours_end` time NOT NULL DEFAULT '18:00:00',
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hub_lat` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hub_long` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `service_areas` json DEFAULT NULL,
  `supported_services` json DEFAULT NULL,
  `current_balance` decimal(16,2) DEFAULT NULL,
  `monthly_budget` decimal(15,2) NOT NULL DEFAULT '0.00',
  `monthly_expenses` decimal(15,2) NOT NULL DEFAULT '0.00',
  `certifications` json DEFAULT NULL,
  `last_audit_date` date DEFAULT NULL,
  `audit_status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `max_daily_capacity` int NOT NULL DEFAULT '1000',
  `current_daily_load` int NOT NULL DEFAULT '0',
  `performance_rating` decimal(3,2) NOT NULL DEFAULT '0.00',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `parent_hub_id` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `hubs_branch_code_unique` (`branch_code`),
  KEY `hubs_parent_hub_id_foreign` (`parent_hub_id`),
  CONSTRAINT `hubs_parent_hub_id_foreign` FOREIGN KEY (`parent_hub_id`) REFERENCES `hubs` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ics2_filings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ics2_filings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned DEFAULT NULL,
  `transport_leg_id` bigint unsigned DEFAULT NULL,
  `mode` enum('air','road','sea','rail') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ens_ref` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('draft','lodged','accepted','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `lodged_at` datetime DEFAULT NULL,
  `response_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ics2_filings_shipment_id_foreign` (`shipment_id`),
  KEY `ics2_filings_transport_leg_id_foreign` (`transport_leg_id`),
  CONSTRAINT `ics2_filings_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`),
  CONSTRAINT `ics2_filings_transport_leg_id_foreign` FOREIGN KEY (`transport_leg_id`) REFERENCES `transport_legs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `impersonation_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `impersonation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `admin_id` bigint unsigned NOT NULL,
  `impersonated_user_id` bigint unsigned NOT NULL,
  `reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'started',
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `ip` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `impersonation_logs_impersonated_user_id_foreign` (`impersonated_user_id`),
  KEY `impersonation_logs_admin_id_impersonated_user_id_index` (`admin_id`,`impersonated_user_id`),
  CONSTRAINT `impersonation_logs_admin_id_foreign` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `impersonation_logs_impersonated_user_id_foreign` FOREIGN KEY (`impersonated_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incomes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incomes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `account_head_id` bigint unsigned DEFAULT NULL,
  `from` tinyint unsigned DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `parcel_id` bigint unsigned DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `hub_id` bigint unsigned DEFAULT NULL,
  `hub_user_id` bigint unsigned DEFAULT NULL,
  `hub_user_account_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `receipt` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `incomes_hub_user_id_foreign` (`hub_user_id`),
  KEY `incomes_hub_user_account_id_foreign` (`hub_user_account_id`),
  KEY `incomes_receipt_foreign` (`receipt`),
  KEY `incomes_account_head_id_index` (`account_head_id`),
  KEY `incomes_user_id_index` (`user_id`),
  KEY `incomes_merchant_id_index` (`merchant_id`),
  KEY `incomes_delivery_man_id_index` (`delivery_man_id`),
  KEY `incomes_parcel_id_index` (`parcel_id`),
  KEY `incomes_account_id_index` (`account_id`),
  KEY `incomes_hub_id_index` (`hub_id`),
  KEY `incomes_date_index` (`date`),
  CONSTRAINT `incomes_account_head_id_foreign` FOREIGN KEY (`account_head_id`) REFERENCES `account_heads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `incomes_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `incomes_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `incomes_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `incomes_hub_user_account_id_foreign` FOREIGN KEY (`hub_user_account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `incomes_hub_user_id_foreign` FOREIGN KEY (`hub_user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `incomes_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `incomes_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `incomes_receipt_foreign` FOREIGN KEY (`receipt`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `incomes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoice_parcels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoice_parcels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `parcel_id` bigint DEFAULT NULL,
  `parcel_status` tinyint unsigned DEFAULT NULL,
  `total_delivery_amount` decimal(16,2) NOT NULL DEFAULT '0.00',
  `collected_amount` decimal(16,2) DEFAULT NULL,
  `return_charge` decimal(16,2) DEFAULT NULL,
  `vat_amount` decimal(16,2) DEFAULT NULL,
  `cod_amount` decimal(16,2) DEFAULT NULL,
  `total_charge_amount` decimal(16,2) DEFAULT NULL,
  `current_payable` decimal(16,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `invoice_parcels_invoice_id_index` (`invoice_id`),
  KEY `invoice_parcels_parcel_id_index` (`parcel_id`),
  KEY `invoice_parcels_parcel_status_index` (`parcel_status`),
  CONSTRAINT `invoice_parcels_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned NOT NULL,
  `invoice_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `invoice_date` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `total_charge` decimal(16,2) DEFAULT NULL,
  `cash_collection` decimal(16,2) DEFAULT NULL,
  `current_payable` decimal(16,2) DEFAULT NULL,
  `parcels_id` longtext COLLATE utf8mb4_unicode_ci,
  `status` tinyint unsigned NOT NULL DEFAULT '2' COMMENT ' Unpaid      = 0, Processing  = 2, Paid        = 3',
  `payment_id` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_id_unique` (`invoice_id`),
  KEY `invoices_merchant_id_index` (`merchant_id`),
  KEY `invoices_status_index` (`status`),
  CONSTRAINT `invoices_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `kyc_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `kyc_records` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `status` enum('pending','approved','rejected') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `documents` json DEFAULT NULL,
  `reviewed_by_id` bigint unsigned DEFAULT NULL,
  `reviewed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `kyc_records_customer_id_foreign` (`customer_id`),
  KEY `kyc_records_reviewed_by_id_foreign` (`reviewed_by_id`),
  CONSTRAINT `kyc_records_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `kyc_records_reviewed_by_id_foreign` FOREIGN KEY (`reviewed_by_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lanes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lanes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `origin_zone_id` bigint unsigned NOT NULL,
  `dest_zone_id` bigint unsigned NOT NULL,
  `mode` enum('air','road') COLLATE utf8mb4_unicode_ci NOT NULL,
  `std_transit_days` int unsigned NOT NULL DEFAULT '0',
  `dim_divisor` int unsigned NOT NULL DEFAULT '5000',
  `eawb_required` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `lanes_origin_zone_id_foreign` (`origin_zone_id`),
  KEY `lanes_dest_zone_id_foreign` (`dest_zone_id`),
  CONSTRAINT `lanes_dest_zone_id_foreign` FOREIGN KEY (`dest_zone_id`) REFERENCES `zones` (`id`),
  CONSTRAINT `lanes_origin_zone_id_foreign` FOREIGN KEY (`origin_zone_id`) REFERENCES `zones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `maintenances` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `repair_details` longtext COLLATE utf8mb4_unicode_ci,
  `spare_parts_purchased_details` longtext COLLATE utf8mb4_unicode_ci,
  `invoice_of_the_purchases` bigint DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `manifests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `manifests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mode` enum('air','road') COLLATE utf8mb4_unicode_ci NOT NULL,
  `carrier_id` bigint unsigned DEFAULT NULL,
  `departure_at` datetime NOT NULL,
  `arrival_at` datetime DEFAULT NULL,
  `origin_branch_id` bigint unsigned NOT NULL,
  `destination_branch_id` bigint unsigned DEFAULT NULL,
  `legs_json` json DEFAULT NULL,
  `bags_json` json DEFAULT NULL,
  `status` enum('open','closed','departed','arrived') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `docs` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `manifests_carrier_id_foreign` (`carrier_id`),
  KEY `manifests_origin_branch_id_foreign` (`origin_branch_id`),
  KEY `manifests_destination_branch_id_foreign` (`destination_branch_id`),
  CONSTRAINT `manifests_carrier_id_foreign` FOREIGN KEY (`carrier_id`) REFERENCES `hubs` (`id`),
  CONSTRAINT `manifests_destination_branch_id_foreign` FOREIGN KEY (`destination_branch_id`) REFERENCES `hubs` (`id`),
  CONSTRAINT `manifests_origin_branch_id_foreign` FOREIGN KEY (`origin_branch_id`) REFERENCES `hubs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merchant_delivery_charges`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_delivery_charges` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `delivery_charge_id` bigint unsigned DEFAULT NULL,
  `weight` bigint DEFAULT NULL,
  `category_id` tinyint unsigned DEFAULT NULL,
  `same_day` decimal(16,2) DEFAULT NULL,
  `next_day` decimal(16,2) DEFAULT NULL,
  `sub_city` decimal(16,2) DEFAULT NULL,
  `outside_city` decimal(16,2) DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_delivery_charges_merchant_id_index` (`merchant_id`),
  KEY `merchant_delivery_charges_delivery_charge_id_index` (`delivery_charge_id`),
  CONSTRAINT `merchant_delivery_charges_delivery_charge_id_foreign` FOREIGN KEY (`delivery_charge_id`) REFERENCES `delivery_charges` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `merchant_delivery_charges_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merchant_online_payment_receiveds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_online_payment_receiveds` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_type` tinyint DEFAULT NULL,
  `account_id` bigint unsigned NOT NULL,
  `merchant_id` bigint unsigned NOT NULL,
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `note` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_online_payment_receiveds_account_id_index` (`account_id`),
  KEY `merchant_online_payment_receiveds_merchant_id_index` (`merchant_id`),
  KEY `merchant_online_payment_receiveds_status_index` (`status`),
  CONSTRAINT `merchant_online_payment_receiveds_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `merchant_online_payment_receiveds_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merchant_online_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_online_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `payment_type` tinyint DEFAULT NULL,
  `account_id` bigint unsigned DEFAULT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(16,2) DEFAULT NULL,
  `note` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_online_payments_merchant_id_index` (`merchant_id`),
  KEY `merchant_online_payments_account_id_index` (`account_id`),
  KEY `merchant_online_payments_status_index` (`status`),
  CONSTRAINT `merchant_online_payments_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `merchant_online_payments_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merchant_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `holder_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `routing_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_company` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_payments_merchant_id_index` (`merchant_id`),
  CONSTRAINT `merchant_payments_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merchant_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned NOT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_settings_merchant_id_index` (`merchant_id`),
  CONSTRAINT `merchant_settings_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merchant_shops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_shops` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant_lat` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant_long` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `default_shop` tinyint unsigned NOT NULL DEFAULT '0' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_shops_merchant_id_index` (`merchant_id`),
  CONSTRAINT `merchant_shops_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merchant_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchant_statements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `expense_id` bigint DEFAULT NULL,
  `parcel_id` bigint unsigned DEFAULT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `type` tinyint unsigned DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchant_statements_expense_id_index` (`expense_id`),
  KEY `merchant_statements_parcel_id_index` (`parcel_id`),
  KEY `merchant_statements_merchant_id_index` (`merchant_id`),
  KEY `merchant_statements_delivery_man_id_index` (`delivery_man_id`),
  KEY `merchant_statements_type_index` (`type`),
  CONSTRAINT `merchant_statements_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `merchant_statements_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `merchant_statements_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `merchants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `merchants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `business_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `merchant_unique_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_balance` decimal(16,2) NOT NULL DEFAULT '0.00',
  `opening_balance` decimal(16,2) NOT NULL DEFAULT '0.00',
  `wallet_balance` decimal(16,2) NOT NULL DEFAULT '0.00',
  `vat` decimal(16,2) NOT NULL DEFAULT '0.00',
  `cod_charges` longtext COLLATE utf8mb4_unicode_ci,
  `nid_id` bigint unsigned DEFAULT NULL,
  `trade_license` bigint unsigned DEFAULT NULL,
  `payment_period` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2' COMMENT '2 = 2days , after every 2days will auto payment invoice generate',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `address` longtext COLLATE utf8mb4_unicode_ci,
  `wallet_use_activation` tinyint unsigned DEFAULT '0',
  `return_charges` decimal(16,2) NOT NULL DEFAULT '100.00' COMMENT '100 = 100%  means full charge will received courier',
  `reference_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reference_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `merchants_nid_id_foreign` (`nid_id`),
  KEY `merchants_trade_license_foreign` (`trade_license`),
  KEY `merchants_user_id_index` (`user_id`),
  CONSTRAINT `merchants_nid_id_foreign` FOREIGN KEY (`nid_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `merchants_trade_license_foreign` FOREIGN KEY (`trade_license`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `merchants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mobile_banks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mobile_banks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `news_offers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `news_offers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `author` bigint unsigned DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `file` bigint unsigned DEFAULT NULL,
  `date` date DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'active       = 1,\n                inactive      = 0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `news_offers_file_foreign` (`file`),
  KEY `news_offers_author_index` (`author`),
  CONSTRAINT `news_offers_author_foreign` FOREIGN KEY (`author`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `news_offers_file_foreign` FOREIGN KEY (`file`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `fcm_secret_key` longtext COLLATE utf8mb4_unicode_ci,
  `fcm_topic` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_merchant_id_foreign` (`merchant_id`),
  KEY `notifications_created_by_foreign` (`created_by`),
  CONSTRAINT `notifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `notifications_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `online_payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `online_payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parcel_id` bigint DEFAULT NULL,
  `source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'parcel,wallet',
  `payer_details` longtext COLLATE utf8mb4_unicode_ci,
  `merchant_id` bigint unsigned NOT NULL,
  `order_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(22,2) DEFAULT NULL,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_paid` tinyint unsigned NOT NULL DEFAULT '0',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT 'pending' COMMENT 'pending,processing,success,fail',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `online_payments_merchant_id_foreign` (`merchant_id`),
  CONSTRAINT `online_payments_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operations_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operations_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operational',
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `severity` enum('low','medium','high','critical') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `priority` enum('1','2','3','4','5') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '3',
  `data` json DEFAULT NULL,
  `action_data` json DEFAULT NULL,
  `status` enum('pending','sent','delivered','read','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `requires_action` tinyint(1) NOT NULL DEFAULT '0',
  `is_dismissed` tinyint(1) NOT NULL DEFAULT '0',
  `channels` json DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `dismissed_at` timestamp NULL DEFAULT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `branch_id` bigint unsigned DEFAULT NULL,
  `recipient_role` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `shipment_id` bigint unsigned DEFAULT NULL,
  `worker_id` bigint unsigned DEFAULT NULL,
  `asset_id` bigint unsigned DEFAULT NULL,
  `related_entity_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `related_entity_id` bigint unsigned DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `operations_notifications_notification_uuid_unique` (`notification_uuid`),
  KEY `operations_notifications_shipment_id_foreign` (`shipment_id`),
  KEY `operations_notifications_worker_id_foreign` (`worker_id`),
  KEY `operations_notifications_asset_id_foreign` (`asset_id`),
  KEY `operations_notifications_created_by_foreign` (`created_by`),
  KEY `ops_notif_user_status_idx` (`user_id`,`status`,`created_at`),
  KEY `ops_notif_branch_status_idx` (`branch_id`,`status`),
  KEY `ops_notif_type_date_idx` (`type`,`created_at`),
  KEY `ops_notif_severity_priority_idx` (`severity`,`priority`),
  KEY `ops_notif_uuid_idx` (`notification_uuid`),
  KEY `ops_notif_read_user_idx` (`read_at`,`user_id`),
  KEY `ops_notif_entity_idx` (`related_entity_type`,`related_entity_id`),
  CONSTRAINT `operations_notifications_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operations_notifications_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operations_notifications_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operations_notifications_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `operations_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `operations_notifications_worker_id_foreign` FOREIGN KEY (`worker_id`) REFERENCES `branch_workers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `otp_codes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `otp_codes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `channel` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `consumed_at` timestamp NULL DEFAULT NULL,
  `attempts` int unsigned NOT NULL DEFAULT '0',
  `locked_until` timestamp NULL DEFAULT NULL,
  `sent_count` int unsigned NOT NULL DEFAULT '0',
  `last_sent_at` timestamp NULL DEFAULT NULL,
  `meta` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `otp_codes_address_channel_index` (`address`,`channel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `packagings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `packagings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price` decimal(16,2) DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `page` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Active, 0= Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pages_page_index` (`page`),
  KEY `pages_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parcel_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcel_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parcel_id` bigint unsigned NOT NULL,
  `delivery_man_id` bigint unsigned DEFAULT NULL,
  `pickup_man_id` bigint unsigned DEFAULT NULL,
  `hub_id` bigint unsigned DEFAULT NULL,
  `transfer_delivery_man_id` bigint unsigned DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `parcel_status` tinyint DEFAULT NULL,
  `delivery_lat` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `delivery_long` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `signature_image` longtext COLLATE utf8mb4_unicode_ci,
  `delivered_image` longtext COLLATE utf8mb4_unicode_ci,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parcel_events_created_by_foreign` (`created_by`),
  KEY `parcel_events_hub_id_index` (`hub_id`),
  KEY `parcel_events_delivery_man_id_index` (`delivery_man_id`),
  KEY `parcel_events_pickup_man_id_index` (`pickup_man_id`),
  KEY `parcel_events_parcel_id_index` (`parcel_id`),
  KEY `parcel_events_parcel_status_index` (`parcel_status`),
  KEY `parcel_events_transfer_delivery_man_id_index` (`transfer_delivery_man_id`),
  CONSTRAINT `parcel_events_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `parcel_events_delivery_man_id_foreign` FOREIGN KEY (`delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `parcel_events_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `parcel_events_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `parcel_events_pickup_man_id_foreign` FOREIGN KEY (`pickup_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `parcel_events_transfer_delivery_man_id_foreign` FOREIGN KEY (`transfer_delivery_man_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `parcels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `parcels` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned NOT NULL,
  `merchant_shop_id` bigint unsigned DEFAULT NULL,
  `pickup_address` longtext COLLATE utf8mb4_unicode_ci,
  `pickup_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_lat` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_long` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_lat` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_long` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority_type_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2',
  `customer_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `customer_address` longtext COLLATE utf8mb4_unicode_ci,
  `invoice_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` tinyint unsigned DEFAULT NULL,
  `weight` decimal(8,2) DEFAULT '0.00',
  `delivery_type_id` tinyint unsigned DEFAULT NULL,
  `packaging_id` bigint DEFAULT NULL,
  `first_hub_id` bigint unsigned DEFAULT NULL,
  `hub_id` bigint unsigned DEFAULT NULL,
  `transfer_hub_id` bigint unsigned DEFAULT NULL,
  `cash_collection` decimal(13,2) DEFAULT NULL,
  `old_cash_collection` decimal(13,2) DEFAULT NULL,
  `selling_price` decimal(13,2) DEFAULT NULL,
  `liquid_fragile_amount` decimal(13,2) DEFAULT NULL,
  `packaging_amount` decimal(13,2) DEFAULT NULL,
  `delivery_charge` decimal(13,2) DEFAULT NULL,
  `cod_charge` bigint DEFAULT NULL,
  `cod_amount` decimal(13,2) DEFAULT NULL,
  `vat` bigint DEFAULT NULL,
  `vat_amount` decimal(13,2) DEFAULT NULL,
  `total_delivery_amount` decimal(13,2) DEFAULT NULL,
  `current_payable` decimal(13,2) DEFAULT NULL,
  `tracking_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` longtext COLLATE utf8mb4_unicode_ci,
  `partial_delivered` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'no=0,yes=1',
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `parcel_bank` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pickup_date` date DEFAULT NULL,
  `delivery_date` date DEFAULT NULL,
  `delivered_date` timestamp NULL DEFAULT NULL,
  `return_charges` decimal(16,2) NOT NULL DEFAULT '0.00' COMMENT 'received by merchant return charges',
  `return_to_courier` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'no=0,yes=1',
  `invoice_id` bigint DEFAULT NULL,
  `parcel_payment_method` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= COD, 2 = Prepaid',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `parcels_merchant_id_index` (`merchant_id`),
  KEY `parcels_merchant_shop_id_index` (`merchant_shop_id`),
  KEY `parcels_hub_id_index` (`hub_id`),
  KEY `parcels_status_index` (`status`),
  KEY `parcels_tracking_id_index` (`tracking_id`),
  KEY `parcels_return_to_courier_index` (`return_to_courier`),
  CONSTRAINT `parcels_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `parcels_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `partners`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `partners` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_id` bigint unsigned DEFAULT NULL,
  `link` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Active, 0= Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `partners_image_id_foreign` (`image_id`),
  CONSTRAINT `partners_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_resets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_resets` (
  `email` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  KEY `password_resets_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_accounts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_accounts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `payment_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `bank_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `holder_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `routing_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_company` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_accounts_merchant_id_index` (`merchant_id`),
  CONSTRAINT `payment_accounts_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_manager_id` bigint unsigned NOT NULL,
  `amount` decimal(14,2) NOT NULL DEFAULT '0.00',
  `status` enum('pending','approved','paid','declined') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `requested_by` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Username or system source that created the request',
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_payment_requests_branch_status` (`branch_manager_id`,`status`),
  CONSTRAINT `payment_requests_branch_manager_id_foreign` FOREIGN KEY (`branch_manager_id`) REFERENCES `branch_managers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned DEFAULT NULL,
  `client_id` bigint unsigned DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_method` enum('stripe','paypal','razorpay','cod','bank_transfer') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('pending','completed','failed','refunded') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_reference` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_shipment_id_index` (`shipment_id`),
  KEY `payments_client_id_index` (`client_id`),
  KEY `payments_status_index` (`status`),
  KEY `payments_payment_method_index` (`payment_method`),
  KEY `payments_transaction_id_index` (`transaction_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `attribute` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keywords` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pickup_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pickup_requests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `request_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'regular = 1,',
  `merchant_id` bigint unsigned NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `note` longtext COLLATE utf8mb4_unicode_ci,
  `parcel_quantity` bigint NOT NULL DEFAULT '0',
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cod_amount` decimal(16,2) DEFAULT '0.00',
  `invoice` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `weight` bigint DEFAULT '0',
  `exchange` tinyint unsigned NOT NULL DEFAULT '0' COMMENT 'yes = 1, no = 0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pickup_requests_merchant_id_index` (`merchant_id`),
  CONSTRAINT `pickup_requests_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pod_proofs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pod_proofs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `driver_id` bigint unsigned NOT NULL,
  `signature` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `photo` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `otp_code` varchar(6) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pod_proofs_shipment_id_driver_id_unique` (`shipment_id`,`driver_id`),
  KEY `pod_proofs_driver_id_foreign` (`driver_id`),
  CONSTRAINT `pod_proofs_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pod_proofs_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pricing_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pricing_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_type` enum('base_rate','fuel_surcharge','tax','surcharge','discount') COLLATE utf8mb4_unicode_ci NOT NULL,
  `conditions` json NOT NULL,
  `calculation_formula` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `priority` int NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_from` timestamp NOT NULL,
  `effective_to` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pricing_rules_rule_type_active_index` (`rule_type`,`active`),
  KEY `pricing_rules_effective_from_effective_to_index` (`effective_from`,`effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_ab_tests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_ab_tests` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `test_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `test_variants` json NOT NULL,
  `traffic_allocation` json DEFAULT NULL,
  `eligibility_criteria` json DEFAULT NULL,
  `success_metric` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sample_size_target` int DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('draft','active','paused','completed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `results` json DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promo_ab_status_idx` (`status`,`start_date`),
  KEY `promo_ab_type_idx` (`test_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_code_generations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_code_generations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotional_campaign_id` bigint unsigned NOT NULL,
  `generated_code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch_id` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generation_template` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `generation_constraints` json DEFAULT NULL,
  `codes_generated` int NOT NULL DEFAULT '0',
  `generated_at` timestamp NOT NULL,
  `generated_by` bigint unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotion_code_generations_generated_code_unique` (`generated_code`),
  KEY `promotion_code_generations_promotional_campaign_id_foreign` (`promotional_campaign_id`),
  KEY `promotion_code_generations_generated_by_foreign` (`generated_by`),
  KEY `promo_code_batch_idx` (`batch_id`,`generated_at`),
  KEY `promo_code_generated_code_idx` (`generated_code`),
  CONSTRAINT `promotion_code_generations_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promotion_code_generations_promotional_campaign_id_foreign` FOREIGN KEY (`promotional_campaign_id`) REFERENCES `promotional_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_effectiveness_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_effectiveness_metrics` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `promotional_campaign_id` bigint unsigned NOT NULL,
  `metric_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `time_period` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `metric_value` decimal(10,4) NOT NULL,
  `baseline_value` decimal(10,4) DEFAULT NULL,
  `improvement_percentage` decimal(5,2) DEFAULT NULL,
  `total_uses` int NOT NULL DEFAULT '0',
  `total_revenue_impact` decimal(10,2) NOT NULL DEFAULT '0.00',
  `segment_breakdown` json DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promo_effectiveness_campaign_idx` (`promotional_campaign_id`,`metric_type`,`period_start`),
  KEY `promo_effectiveness_period_idx` (`time_period`,`period_start`),
  CONSTRAINT `promotion_effectiveness_metrics_promotional_campaign_id_foreign` FOREIGN KEY (`promotional_campaign_id`) REFERENCES `promotional_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_event_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_event_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `promotional_campaign_id` bigint unsigned NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `event_data` json NOT NULL,
  `source` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `event_timestamp` timestamp NOT NULL,
  PRIMARY KEY (`id`),
  KEY `promo_event_type_idx` (`event_type`,`event_timestamp`),
  KEY `promo_event_campaign_idx` (`promotional_campaign_id`,`event_timestamp`),
  KEY `promo_event_customer_idx` (`customer_id`,`event_timestamp`),
  CONSTRAINT `promotion_event_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promotion_event_logs_promotional_campaign_id_foreign` FOREIGN KEY (`promotional_campaign_id`) REFERENCES `promotional_campaigns` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_notifications_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_notifications_log` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `notification_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `promotional_campaign_id` bigint unsigned DEFAULT NULL,
  `notification_data` json NOT NULL,
  `channels_used` json NOT NULL,
  `delivery_status` enum('pending','sent','delivered','failed','bounced') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text COLLATE utf8mb4_unicode_ci,
  `retry_count` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promotion_notifications_log_promotional_campaign_id_foreign` (`promotional_campaign_id`),
  KEY `promotion_notifications_log_customer_id_notification_type_index` (`customer_id`,`notification_type`),
  KEY `promotion_notifications_log_delivery_status_index` (`delivery_status`),
  KEY `promotion_notifications_log_sent_at_index` (`sent_at`),
  CONSTRAINT `promotion_notifications_log_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `promotion_notifications_log_promotional_campaign_id_foreign` FOREIGN KEY (`promotional_campaign_id`) REFERENCES `promotional_campaigns` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotion_stacking_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotion_stacking_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `rule_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `rule_description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `applicable_campaign_types` json DEFAULT NULL,
  `excluded_campaign_types` json DEFAULT NULL,
  `customer_eligibility_rules` json DEFAULT NULL,
  `stacking_conditions` json DEFAULT NULL,
  `maximum_stackable_discount` decimal(5,2) DEFAULT NULL,
  `priority_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `effective_from` timestamp NOT NULL,
  `effective_to` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `promo_stack_rules_active_idx` (`is_active`,`effective_from`,`effective_to`),
  KEY `promo_stack_rules_type_idx` (`rule_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `promotional_campaigns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `promotional_campaigns` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `promo_code` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `campaign_type` enum('percentage','fixed_amount','free_shipping','tier_upgrade') COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` decimal(10,2) NOT NULL,
  `minimum_order_value` decimal(10,2) DEFAULT NULL,
  `maximum_discount_amount` decimal(10,2) DEFAULT NULL,
  `usage_limit` int DEFAULT NULL,
  `usage_count` int NOT NULL DEFAULT '0',
  `customer_eligibility` json DEFAULT NULL,
  `stacking_allowed` tinyint(1) NOT NULL DEFAULT '0',
  `effective_from` timestamp NOT NULL,
  `effective_to` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `promotional_campaigns_promo_code_unique` (`promo_code`),
  KEY `idx_campaign_active_validity` (`is_active`,`effective_from`,`effective_to`),
  KEY `promotional_campaigns_promo_code_index` (`promo_code`),
  KEY `promotional_campaigns_active_period_idx` (`is_active`,`effective_from`,`effective_to`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `push_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `merchant_id` bigint unsigned DEFAULT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `push_notifications_image_id_foreign` (`image_id`),
  KEY `push_notifications_user_id_index` (`user_id`),
  KEY `push_notifications_merchant_id_index` (`merchant_id`),
  CONSTRAINT `push_notifications_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `push_notifications_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `push_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `quotations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint unsigned NOT NULL,
  `origin_branch_id` bigint unsigned DEFAULT NULL,
  `destination_country` varchar(2) COLLATE utf8mb4_unicode_ci NOT NULL,
  `service_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pieces` int unsigned NOT NULL,
  `weight_kg` decimal(8,3) NOT NULL,
  `volume_cm3` int unsigned DEFAULT NULL,
  `dim_factor` int unsigned NOT NULL DEFAULT '5000',
  `base_charge` decimal(12,2) NOT NULL DEFAULT '0.00',
  `surcharges_json` json DEFAULT NULL,
  `total_amount` decimal(12,2) NOT NULL DEFAULT '0.00',
  `currency` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'USD',
  `status` enum('draft','sent','accepted','expired') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'draft',
  `valid_until` date DEFAULT NULL,
  `pdf_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quotations_customer_id_foreign` (`customer_id`),
  KEY `quotations_origin_branch_id_foreign` (`origin_branch_id`),
  KEY `quotations_created_by_id_foreign` (`created_by_id`),
  CONSTRAINT `quotations_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `quotations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `quotations_origin_branch_id_foreign` FOREIGN KEY (`origin_branch_id`) REFERENCES `hubs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rate_cards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_cards` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `origin_country` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `dest_country` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `zone_matrix` json NOT NULL,
  `weight_rules` json NOT NULL,
  `dim_rules` json NOT NULL,
  `fuel_surcharge_percent` decimal(5,2) NOT NULL DEFAULT '0.00',
  `accessorials` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rate_cards_origin_country_dest_country_index` (`origin_country`,`dest_country`),
  KEY `rate_cards_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `rate_limit_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `rate_limit_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `api_route_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `limit` int NOT NULL,
  `window` int NOT NULL,
  `burst_limit` int NOT NULL DEFAULT '0',
  `identifier` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ip',
  `conditions` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `priority` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `rate_limit_rules_api_route_id_foreign` (`api_route_id`),
  CONSTRAINT `rate_limit_rules_api_route_id_foreign` FOREIGN KEY (`api_route_id`) REFERENCES `api_routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `realtime_analytics_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `realtime_analytics_cache` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `metric_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `metric_data` json NOT NULL,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `realtime_analytics_cache_branch_id_metric_type_index` (`branch_id`,`metric_type`),
  KEY `realtime_analytics_cache_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_definitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('dashboard','operational','financial','performance','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parameters` json DEFAULT NULL,
  `query_definition` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `output_format` enum('json','csv','xlsx','pdf') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'json',
  `is_public` tinyint(1) NOT NULL DEFAULT '0',
  `created_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `updated_by` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_version_id` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_definitions_name_index` (`name`),
  KEY `report_definitions_type_index` (`type`),
  KEY `report_definitions_category_index` (`category`),
  KEY `report_definitions_created_by_index` (`created_by`),
  KEY `report_definitions_type_category_index` (`type`,`category`),
  KEY `report_definitions_current_version_id_foreign` (`current_version_id`),
  CONSTRAINT `report_definitions_current_version_id_foreign` FOREIGN KEY (`current_version_id`) REFERENCES `report_definitions_version` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_definitions_version`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_definitions_version` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `version` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `type` enum('dashboard','operational','financial','performance','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `category` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `parameters` json DEFAULT NULL,
  `query_definition` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `output_format` enum('json','csv','xlsx','pdf') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'json',
  `change_log` text COLLATE utf8mb4_unicode_ci,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `updated_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_definitions_version_report_id_version_unique` (`report_id`,`version`),
  KEY `report_definitions_version_report_id_index` (`report_id`),
  KEY `report_definitions_version_is_active_index` (`is_active`),
  KEY `report_definitions_version_report_id_is_active_index` (`report_id`,`is_active`),
  KEY `report_definitions_version_version_index` (`version`),
  CONSTRAINT `report_definitions_version_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `report_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_execution_history`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_execution_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `version_id` bigint unsigned NOT NULL,
  `executed_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `parameters_used` json DEFAULT NULL,
  `query_results` json DEFAULT NULL,
  `status` enum('pending','running','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `execution_time_ms` int DEFAULT NULL,
  `rows_returned` int DEFAULT NULL,
  `output_file_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `report_execution_history_report_id_index` (`report_id`),
  KEY `report_execution_history_version_id_index` (`version_id`),
  KEY `report_execution_history_executed_by_index` (`executed_by`),
  KEY `report_execution_history_status_index` (`status`),
  KEY `report_execution_history_created_at_index` (`created_at`),
  KEY `report_execution_history_report_id_status_index` (`report_id`,`status`),
  CONSTRAINT `report_execution_history_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `report_definitions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `report_execution_history_version_id_foreign` FOREIGN KEY (`version_id`) REFERENCES `report_definitions_version` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_sharing`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_sharing` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `shared_with_type` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shared_with_identifier` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `permission` enum('view','edit','execute','admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `granted_by` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_sharing_acl_unique` (`report_id`,`shared_with_type`,`shared_with_identifier`,`permission`),
  KEY `report_sharing_report_id_index` (`report_id`),
  KEY `report_sharing_shared_with_type_shared_with_identifier_index` (`shared_with_type`,`shared_with_identifier`),
  KEY `report_sharing_permission_index` (`permission`),
  CONSTRAINT `report_sharing_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `report_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `report_tags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `report_tags` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `report_id` bigint unsigned NOT NULL,
  `tag` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `report_tags_report_id_tag_unique` (`report_id`,`tag`),
  KEY `report_tags_tag_index` (`tag`),
  CONSTRAINT `report_tags_report_id_foreign` FOREIGN KEY (`report_id`) REFERENCES `report_definitions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `return_orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `return_orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `reason_code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `initiated_by` enum('customer','ops') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('initiated','in_transit','received','completed','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'initiated',
  `rto_label_path` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `return_orders_shipment_id_foreign` (`shipment_id`),
  CONSTRAINT `return_orders_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `permissions` text COLLATE utf8mb4_unicode_ci,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `routes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `routes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `driver_id` bigint unsigned NOT NULL,
  `planned_at` timestamp NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PLANNED',
  `stops_sequence` json DEFAULT NULL,
  `total_distance_km` decimal(8,2) DEFAULT NULL,
  `estimated_duration_hours` decimal(4,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `routes_branch_id_planned_at_index` (`branch_id`,`planned_at`),
  KEY `routes_status_index` (`status`),
  KEY `routes_driver_id_index` (`driver_id`),
  CONSTRAINT `routes_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `routes_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salaries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `month` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `account_id` bigint unsigned NOT NULL,
  `amount` decimal(16,2) NOT NULL DEFAULT '0.00',
  `date` date DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salaries_user_id_index` (`user_id`),
  KEY `salaries_account_id_index` (`account_id`),
  KEY `salaries_month_index` (`month`),
  CONSTRAINT `salaries_account_id_foreign` FOREIGN KEY (`account_id`) REFERENCES `accounts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `salaries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `salary_generates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `salary_generates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `month` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(16,2) NOT NULL DEFAULT '0.00',
  `status` bigint unsigned NOT NULL DEFAULT '0' COMMENT 'Unpaid=0,Paid=1,Partial Paid=2',
  `due` decimal(16,2) NOT NULL DEFAULT '0.00',
  `advance` decimal(16,2) NOT NULL DEFAULT '0.00',
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `salary_generates_user_id_index` (`user_id`),
  KEY `salary_generates_month_index` (`month`),
  KEY `salary_generates_status_index` (`status`),
  CONSTRAINT `salary_generates_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scan_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scan_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sscc` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `shipment_id` bigint unsigned DEFAULT NULL,
  `bag_id` bigint unsigned DEFAULT NULL,
  `route_id` bigint unsigned DEFAULT NULL,
  `stop_id` bigint unsigned DEFAULT NULL,
  `type` enum('BOOKING_CONFIRMED','PICKUP_CONFIRMED','PICKUP_COMPLETED','ORIGIN_ARRIVAL','BAGGED','LINEHAUL_DEPARTED','LINEHAUL_ARRIVED','DESTINATION_ARRIVAL','CUSTOMS_HOLD','CUSTOMS_CLEARED','OUT_FOR_DELIVERY','DELIVERY_CONFIRMED','RETURN_INITIATED','RETURN_RECEIVED','RETURN_COMPLETED','EXCEPTION') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status_after` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `location_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `location_id` bigint unsigned DEFAULT NULL,
  `leg_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `occurred_at` timestamp NOT NULL,
  `geojson` json DEFAULT NULL,
  `payload` json DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scan_events_user_id_foreign` (`user_id`),
  KEY `scan_events_sscc_occurred_at_index` (`sscc`,`occurred_at`),
  KEY `scan_events_type_index` (`type`),
  KEY `scan_events_branch_id_index` (`branch_id`),
  KEY `scan_events_leg_id_index` (`leg_id`),
  KEY `scan_events_shipment_id_index` (`shipment_id`),
  KEY `scan_events_bag_id_index` (`bag_id`),
  KEY `scan_events_route_id_index` (`route_id`),
  KEY `scan_events_stop_id_index` (`stop_id`),
  KEY `scan_events_status_after_index` (`status_after`),
  KEY `scan_events_location_type_index` (`location_type`),
  KEY `scan_events_location_id_index` (`location_id`),
  CONSTRAINT `scan_events_bag_id_foreign` FOREIGN KEY (`bag_id`) REFERENCES `bags` (`id`) ON DELETE SET NULL,
  CONSTRAINT `scan_events_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scan_events_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `scan_events_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `scan_events_stop_id_foreign` FOREIGN KEY (`stop_id`) REFERENCES `stops` (`id`) ON DELETE SET NULL,
  CONSTRAINT `scan_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `scans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scans` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `tracking_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `timestamp` timestamp NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `offline_sync_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` double DEFAULT NULL,
  `longitude` double DEFAULT NULL,
  `accuracy` decimal(8,2) DEFAULT NULL,
  `barcode_type` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `batch_id` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `app_version` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `scans_offline_sync_key_unique` (`offline_sync_key`),
  KEY `scans_branch_id_foreign` (`branch_id`),
  KEY `scans_shipment_id_timestamp_index` (`shipment_id`,`timestamp`),
  KEY `scans_device_id_synced_at_index` (`device_id`,`synced_at`),
  KEY `scans_batch_id_index` (`batch_id`),
  KEY `scans_tracking_number_index` (`tracking_number`),
  CONSTRAINT `scans_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`),
  CONSTRAINT `scans_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sections` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `type` tinyint DEFAULT NULL,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sections_type_index` (`type`),
  KEY `sections_key_index` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `event_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_category` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'security',
  `severity` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `user_id` bigint unsigned DEFAULT NULL,
  `user_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `session_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resource_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `resource_id` bigint unsigned DEFAULT NULL,
  `action_details` json DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'success',
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_audit_logs_event_type_index` (`event_type`),
  KEY `security_audit_logs_event_category_index` (`event_category`),
  KEY `security_audit_logs_severity_index` (`severity`),
  KEY `security_audit_logs_user_id_index` (`user_id`),
  KEY `security_audit_logs_resource_type_index` (`resource_type`),
  KEY `security_audit_logs_status_index` (`status`),
  KEY `security_audit_logs_created_at_index` (`created_at`),
  KEY `security_audit_logs_user_id_created_at_index` (`user_id`,`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_encryption_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_encryption_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `key_value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `algorithm` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'AES-256-GCM',
  `key_length` bigint unsigned NOT NULL DEFAULT '256',
  `expires_at` timestamp NULL DEFAULT NULL,
  `rotated_at` timestamp NULL DEFAULT NULL,
  `rotated_by` bigint unsigned DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `metadata` json DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `security_encryption_keys_key_name_unique` (`key_name`),
  KEY `security_encryption_keys_key_type_index` (`key_type`),
  KEY `security_encryption_keys_status_index` (`status`),
  KEY `security_encryption_keys_expires_at_index` (`expires_at`),
  KEY `security_encryption_keys_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_mfa_devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_mfa_devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `device_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `device_type` enum('sms','email','totp','hardware','biometric') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'totp',
  `device_identifier` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT '0',
  `is_primary` tinyint(1) NOT NULL DEFAULT '0',
  `secret_key` text COLLATE utf8mb4_unicode_ci,
  `backup_codes` json DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_mfa_devices_user_id_index` (`user_id`),
  KEY `security_mfa_devices_device_type_index` (`device_type`),
  KEY `security_mfa_devices_is_verified_index` (`is_verified`),
  KEY `security_mfa_devices_is_primary_index` (`is_primary`),
  CONSTRAINT `security_mfa_devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `resource` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `conditions` json DEFAULT NULL,
  `data_classification` enum('public','internal','confidential','restricted') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'internal',
  `requires_approval` tinyint(1) NOT NULL DEFAULT '0',
  `approval_role_id` bigint unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `security_permissions_name_unique` (`name`),
  UNIQUE KEY `security_permissions_slug_unique` (`slug`),
  KEY `security_permissions_approval_role_id_foreign` (`approval_role_id`),
  KEY `security_permissions_resource_action_index` (`resource`,`action`),
  KEY `security_permissions_data_classification_index` (`data_classification`),
  KEY `security_permissions_is_active_index` (`is_active`),
  CONSTRAINT `security_permissions_approval_role_id_foreign` FOREIGN KEY (`approval_role_id`) REFERENCES `security_roles` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_privacy_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_privacy_consents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `consent_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `consent_given` tinyint(1) NOT NULL,
  `consent_source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `consent_data` json DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `withdrawn_at` timestamp NULL DEFAULT NULL,
  `withdrawal_method` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `security_privacy_consents_user_id_index` (`user_id`),
  KEY `security_privacy_consents_consent_type_index` (`consent_type`),
  KEY `security_privacy_consents_consent_given_index` (`consent_given`),
  KEY `security_privacy_consents_expires_at_index` (`expires_at`),
  CONSTRAINT `security_privacy_consents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_role_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_role_permissions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `security_role_id` bigint unsigned NOT NULL,
  `security_permission_id` bigint unsigned NOT NULL,
  `conditions` json DEFAULT NULL,
  `granted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `granted_by` bigint unsigned DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `revoked_by` bigint unsigned DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `security_role_permission_unique` (`security_role_id`,`security_permission_id`),
  KEY `security_role_permissions_security_permission_id_foreign` (`security_permission_id`),
  KEY `security_role_permissions_granted_at_index` (`granted_at`),
  KEY `security_role_permissions_revoked_at_index` (`revoked_at`),
  CONSTRAINT `security_role_permissions_security_permission_id_foreign` FOREIGN KEY (`security_permission_id`) REFERENCES `security_permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `security_role_permissions_security_role_id_foreign` FOREIGN KEY (`security_role_id`) REFERENCES `security_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `parent_role_id` bigint unsigned DEFAULT NULL,
  `inherited_permissions` json DEFAULT NULL,
  `role_hierarchy_path` json DEFAULT NULL,
  `level` enum('system','enterprise','department','functional','task') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'functional',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` bigint unsigned DEFAULT NULL,
  `updated_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `security_roles_name_unique` (`name`),
  UNIQUE KEY `security_roles_slug_unique` (`slug`),
  KEY `security_roles_parent_role_id_is_active_index` (`parent_role_id`,`is_active`),
  KEY `security_roles_level_index` (`level`),
  CONSTRAINT `security_roles_parent_role_id_foreign` FOREIGN KEY (`parent_role_id`) REFERENCES `security_roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `security_user_roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `security_user_roles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `security_role_id` bigint unsigned NOT NULL,
  `scope_restrictions` json DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `assigned_by` bigint unsigned DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `notes` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`id`),
  UNIQUE KEY `security_user_roles_user_id_security_role_id_unique` (`user_id`,`security_role_id`),
  KEY `security_user_roles_security_role_id_foreign` (`security_role_id`),
  KEY `security_user_roles_assigned_at_index` (`assigned_at`),
  KEY `security_user_roles_expires_at_index` (`expires_at`),
  KEY `security_user_roles_is_active_index` (`is_active`),
  CONSTRAINT `security_user_roles_security_role_id_foreign` FOREIGN KEY (`security_role_id`) REFERENCES `security_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `security_user_roles_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_level_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_level_definitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `base_multiplier` decimal(4,2) NOT NULL DEFAULT '1.00',
  `min_delivery_hours` int DEFAULT NULL,
  `max_delivery_hours` int DEFAULT NULL,
  `reliability_score` decimal(4,2) NOT NULL DEFAULT '95.00',
  `sla_claims_covered` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `service_level_definitions_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `services`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `services` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_id` bigint unsigned DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Active, 0= Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `services_image_id_foreign` (`image_id`),
  CONSTRAINT `services_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `settlement_cycles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `settlement_cycles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `period` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `total_revenue` decimal(12,2) NOT NULL,
  `total_costs` decimal(12,2) NOT NULL,
  `net_settlement` decimal(12,2) NOT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'EUR',
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `processed_at` timestamp NULL DEFAULT NULL,
  `breakdown` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `settlement_cycles_period_branch_id_unique` (`period`,`branch_id`),
  KEY `settlement_cycles_branch_id_foreign` (`branch_id`),
  KEY `settlement_cycles_status_index` (`status`),
  KEY `settlement_cycles_processed_at_index` (`processed_at`),
  CONSTRAINT `settlement_cycles_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shipment_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipment_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `location` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `logged_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shipment_transitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipment_transitions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `from_status` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `to_status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `source_id` bigint unsigned DEFAULT NULL,
  `performed_by` bigint unsigned DEFAULT NULL,
  `context` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipment_transitions_performed_by_foreign` (`performed_by`),
  KEY `shipment_transitions_shipment_id_created_at_index` (`shipment_id`,`created_at`),
  KEY `shipment_transitions_to_status_index` (`to_status`),
  KEY `shipment_transitions_trigger_index` (`trigger`),
  CONSTRAINT `shipment_transitions_performed_by_foreign` FOREIGN KEY (`performed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipment_transitions_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `shipments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint unsigned DEFAULT NULL,
  `customer_id` bigint unsigned DEFAULT NULL,
  `origin_branch_id` bigint unsigned NOT NULL,
  `dest_branch_id` bigint unsigned NOT NULL,
  `transfer_hub_id` bigint unsigned DEFAULT NULL,
  `assigned_worker_id` bigint unsigned DEFAULT NULL,
  `assigned_driver_id` bigint unsigned DEFAULT NULL,
  `driver_assigned_at` timestamp NULL DEFAULT NULL,
  `delivered_by` bigint unsigned DEFAULT NULL,
  `tracking_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'booked',
  `has_exception` tinyint(1) NOT NULL DEFAULT '0',
  `exception_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exception_severity` enum('low','medium','high') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exception_notes` text COLLATE utf8mb4_unicode_ci,
  `exception_occurred_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `service_level` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `priority` int NOT NULL DEFAULT '1',
  `incoterm` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_status` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BOOKED',
  `current_location_type` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_location_id` bigint unsigned DEFAULT NULL,
  `last_scan_event_id` bigint unsigned DEFAULT NULL,
  `assigned_at` timestamp NULL DEFAULT NULL,
  `hub_processed_at` timestamp NULL DEFAULT NULL,
  `transferred_at` timestamp NULL DEFAULT NULL,
  `picked_up_at` timestamp NULL DEFAULT NULL,
  `origin_hub_arrived_at` timestamp NULL DEFAULT NULL,
  `bagged_at` timestamp NULL DEFAULT NULL,
  `linehaul_departed_at` timestamp NULL DEFAULT NULL,
  `linehaul_arrived_at` timestamp NULL DEFAULT NULL,
  `destination_hub_arrived_at` timestamp NULL DEFAULT NULL,
  `customs_hold_at` timestamp NULL DEFAULT NULL,
  `customs_cleared_at` timestamp NULL DEFAULT NULL,
  `out_for_delivery_at` timestamp NULL DEFAULT NULL,
  `return_initiated_at` timestamp NULL DEFAULT NULL,
  `return_in_transit_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `expected_delivery_date` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `return_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `return_notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `public_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `booked_at` timestamp NULL DEFAULT NULL,
  `pickup_scheduled_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `shipments_tracking_number_unique` (`tracking_number`),
  KEY `shipments_customer_id_foreign` (`customer_id`),
  KEY `shipments_created_by_foreign` (`created_by`),
  KEY `shipments_client_id_status_index` (`client_id`,`status`),
  KEY `shipments_origin_branch_id_dest_branch_id_index` (`origin_branch_id`,`dest_branch_id`),
  KEY `shipments_assigned_worker_id_index` (`assigned_worker_id`),
  KEY `shipments_current_status_index` (`current_status`),
  KEY `shipments_transfer_hub_id_index` (`transfer_hub_id`),
  KEY `shipments_delivered_by_index` (`delivered_by`),
  KEY `shipments_has_exception_index` (`has_exception`),
  KEY `shipments_priority_index` (`priority`),
  KEY `shipments_hub_processed_at_index` (`hub_processed_at`),
  KEY `shipments_exception_occurred_at_index` (`exception_occurred_at`),
  KEY `shipments_assigned_at_index` (`assigned_at`),
  KEY `shipments_delivered_at_index` (`delivered_at`),
  KEY `shipments_last_scan_event_id_foreign` (`last_scan_event_id`),
  KEY `shipments_booked_at_index` (`booked_at`),
  KEY `shipments_pickup_scheduled_at_index` (`pickup_scheduled_at`),
  KEY `shipments_picked_up_at_index` (`picked_up_at`),
  KEY `shipments_origin_hub_arrived_at_index` (`origin_hub_arrived_at`),
  KEY `shipments_bagged_at_index` (`bagged_at`),
  KEY `shipments_linehaul_departed_at_index` (`linehaul_departed_at`),
  KEY `shipments_linehaul_arrived_at_index` (`linehaul_arrived_at`),
  KEY `shipments_destination_hub_arrived_at_index` (`destination_hub_arrived_at`),
  KEY `shipments_customs_hold_at_index` (`customs_hold_at`),
  KEY `shipments_customs_cleared_at_index` (`customs_cleared_at`),
  KEY `shipments_out_for_delivery_at_index` (`out_for_delivery_at`),
  KEY `shipments_return_initiated_at_index` (`return_initiated_at`),
  KEY `shipments_return_in_transit_at_index` (`return_in_transit_at`),
  KEY `shipments_returned_at_index` (`returned_at`),
  KEY `shipments_cancelled_at_index` (`cancelled_at`),
  KEY `shipments_current_location_type_index` (`current_location_type`),
  KEY `shipments_current_location_id_index` (`current_location_id`),
  KEY `shipments_assigned_driver_id_foreign` (`assigned_driver_id`),
  KEY `shipments_mode_index` (`mode`),
  CONSTRAINT `shipments_assigned_driver_id_foreign` FOREIGN KEY (`assigned_driver_id`) REFERENCES `drivers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_delivered_by_foreign` FOREIGN KEY (`delivered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_last_scan_event_id_foreign` FOREIGN KEY (`last_scan_event_id`) REFERENCES `scan_events` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_transfer_hub_id_foreign` FOREIGN KEY (`transfer_hub_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_send_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_send_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sms_send_status` tinyint unsigned NOT NULL COMMENT '1=Parcel Create, 2=Delivered Cancel Customer, 3=Delivered Cancel Merchant',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sms_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sms_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `value` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `social_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `social_links` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Active, 0= Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sortation_bins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sortation_bins` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `lane` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` enum('active','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sortation_bins_branch_id_foreign` (`branch_id`),
  CONSTRAINT `sortation_bins_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hubs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stg_shipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stg_shipments` (
  `stg_id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `stg_batch_id` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `stg_created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `shipment_id` bigint unsigned NOT NULL,
  `tracking_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `source_data` json NOT NULL,
  `source_system` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extraction_timestamp` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `processing_status` enum('PENDING','TRANSFORMED','VALIDATED','LOADED','FAILED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `processing_errors` text COLLATE utf8mb4_unicode_ci,
  `data_quality_score` decimal(3,2) DEFAULT NULL,
  PRIMARY KEY (`stg_id`),
  KEY `stg_shipments_batch_idx` (`stg_batch_id`),
  KEY `stg_shipments_status_idx` (`processing_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `stops`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stops` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `route_id` bigint unsigned NOT NULL,
  `sscc` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `sequence` int NOT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
  `eta_at` timestamp NULL DEFAULT NULL,
  `arrived_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `geo_location` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `stops_route_id_sequence_index` (`route_id`,`sequence`),
  KEY `stops_sscc_index` (`sscc`),
  KEY `stops_status_index` (`status`),
  CONSTRAINT `stops_route_id_foreign` FOREIGN KEY (`route_id`) REFERENCES `routes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscribes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscribes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `support_chats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `support_chats` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `support_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `attached_file` bigint unsigned DEFAULT NULL,
  `message` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `support_chats_support_id_index` (`support_id`),
  KEY `support_chats_user_id_index` (`user_id`),
  CONSTRAINT `support_chats_support_id_foreign` FOREIGN KEY (`support_id`) REFERENCES `supports` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `support_chats_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `supports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `supports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `service` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `date` date DEFAULT NULL,
  `attached_file` bigint unsigned DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Pending,2= Processing,3= Resolved,4= Closed',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `supports_user_id_index` (`user_id`),
  KEY `supports_department_id_index` (`department_id`),
  CONSTRAINT `supports_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `supports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `surcharge_rules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surcharge_rules` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trigger` enum('fuel','security','remote_area','oversize','weekend','dg','re_attempt','custom') COLLATE utf8mb4_unicode_ci NOT NULL,
  `rate_type` enum('flat','percent') COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(10,4) NOT NULL,
  `currency` char(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `applies_to` json DEFAULT NULL,
  `active_from` date NOT NULL,
  `active_to` date DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `surcharge_rules_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `surveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `surveys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned DEFAULT NULL,
  `score` tinyint unsigned NOT NULL COMMENT '0..10',
  `comment` text COLLATE utf8mb4_unicode_ci,
  `channel` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'link',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `surveys_shipment_id_foreign` (`shipment_id`),
  CONSTRAINT `surveys_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `driver_id` bigint unsigned NOT NULL,
  `type` enum('pickup','delivery','return') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'delivery',
  `status` enum('pending','assigned','in_progress','completed','failed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority` enum('low','normal','high','urgent') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_driver_id_status_index` (`driver_id`,`status`),
  KEY `tasks_shipment_id_type_index` (`shipment_id`,`type`),
  CONSTRAINT `tasks_driver_id_foreign` FOREIGN KEY (`driver_id`) REFERENCES `delivery_man` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tasks_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `to_dos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `to_dos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `user_id` bigint unsigned NOT NULL,
  `date` date DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'pending= 1, procesing= 2,complete= 3',
  `note` longtext COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `to_dos_user_id_index` (`user_id`),
  CONSTRAINT `to_dos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `translations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language_code` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `translations_key_language_code_unique` (`key`,`language_code`),
  KEY `translations_language_code_key_index` (`language_code`,`key`),
  KEY `translations_key_index` (`key`),
  KEY `translations_language_code_index` (`language_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `transport_legs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `transport_legs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `shipment_id` bigint unsigned NOT NULL,
  `mode` enum('AIR','ROAD') COLLATE utf8mb4_unicode_ci NOT NULL,
  `carrier` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `flight_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehicle_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `awb` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `cmr` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `depart_at` timestamp NULL DEFAULT NULL,
  `arrive_at` timestamp NULL DEFAULT NULL,
  `status` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PLANNED',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `transport_legs_shipment_id_mode_index` (`shipment_id`,`mode`),
  KEY `transport_legs_status_index` (`status`),
  KEY `transport_legs_depart_at_index` (`depart_at`),
  CONSTRAINT `transport_legs_shipment_id_foreign` FOREIGN KEY (`shipment_id`) REFERENCES `shipments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `uploads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `uploads` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `original` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `one` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `two` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `three` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_accessibility_preferences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_accessibility_preferences` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `high_contrast` tinyint(1) NOT NULL DEFAULT '0',
  `large_text` tinyint(1) NOT NULL DEFAULT '0',
  `reduced_motion` tinyint(1) NOT NULL DEFAULT '0',
  `screen_reader_mode` tinyint(1) NOT NULL DEFAULT '0',
  `keyboard_navigation_only` tinyint(1) NOT NULL DEFAULT '0',
  `font_size` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `color_scheme` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
  `disable_animations` tinyint(1) NOT NULL DEFAULT '0',
  `enable_focus_indicators` tinyint(1) NOT NULL DEFAULT '1',
  `custom_css` json DEFAULT NULL,
  `preferences_data` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_accessibility_preferences_user_id_unique` (`user_id`),
  CONSTRAINT `user_accessibility_preferences_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_consents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user_consents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'privacy',
  `version` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_consents_user_id_foreign` (`user_id`),
  CONSTRAINT `user_consents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone_e164` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nid_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `designation_id` bigint unsigned DEFAULT NULL,
  `department_id` bigint unsigned DEFAULT NULL,
  `hub_id` bigint unsigned DEFAULT NULL,
  `primary_branch_id` bigint unsigned DEFAULT NULL,
  `user_type` tinyint unsigned DEFAULT '1' COMMENT '1=Admin, 2=Merchant, 3=DeliveryMan, 4=In-Charge',
  `image_id` bigint unsigned DEFAULT NULL,
  `joining_date` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role_id` bigint unsigned DEFAULT NULL,
  `permissions` text COLLATE utf8mb4_unicode_ci,
  `notification_prefs` json DEFAULT NULL,
  `otp` int DEFAULT NULL,
  `salary` decimal(16,2) NOT NULL DEFAULT '0.00',
  `device_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `web_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `preferred_language` varchar(5) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `verification_status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1=Active, 0=Inactive',
  `google_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `facebook_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  UNIQUE KEY `users_facebook_id_unique` (`facebook_id`),
  KEY `users_image_id_foreign` (`image_id`),
  KEY `users_designation_id_index` (`designation_id`),
  KEY `users_department_id_index` (`department_id`),
  KEY `users_hub_id_index` (`hub_id`),
  KEY `users_role_id_index` (`role_id`),
  KEY `users_user_type_index` (`user_type`),
  KEY `users_phone_e164_index` (`phone_e164`),
  KEY `users_preferred_language_index` (`preferred_language`),
  KEY `users_primary_branch_id_foreign` (`primary_branch_id`),
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_primary_branch_id_foreign` FOREIGN KEY (`primary_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_preferred_language_check` CHECK ((`preferred_language` in (_utf8mb4'en',_utf8mb4'fr',_utf8mb4'sw')))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vat_statements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vat_statements` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `parcel_id` bigint unsigned NOT NULL,
  `type` tinyint unsigned DEFAULT NULL COMMENT 'income=1,expense=2',
  `amount` decimal(16,2) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vat_statements_parcel_id_index` (`parcel_id`),
  KEY `vat_statements_type_index` (`type`),
  CONSTRAINT `vat_statements_parcel_id_foreign` FOREIGN KEY (`parcel_id`) REFERENCES `parcels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vehicles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vehicles` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `plate_no` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `chasis_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `model` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `year` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `brand` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` longtext COLLATE utf8mb4_unicode_ci,
  `branch_id` bigint unsigned DEFAULT NULL,
  `type` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `capacity_kg` decimal(8,2) DEFAULT NULL,
  `capacity_volume` decimal(8,2) DEFAULT NULL,
  `ownership` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'COMPANY',
  `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ACTIVE',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vehicles_branch_id_foreign` (`branch_id`),
  CONSTRAINT `vehicles_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wallets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wallets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `merchant_id` bigint unsigned NOT NULL,
  `transaction_id` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(22,2) DEFAULT NULL,
  `type` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Income,2= Expense',
  `payment_method` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1 = Offline ',
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1 = Pending , 2= Approved,3= Reject',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wallets_source_index` (`source`),
  KEY `wallets_user_id_index` (`user_id`),
  KEY `wallets_merchant_id_index` (`merchant_id`),
  KEY `wallets_type_index` (`type`),
  KEY `wallets_status_index` (`status`),
  CONSTRAINT `wallets_merchant_id_foreign` FOREIGN KEY (`merchant_id`) REFERENCES `merchants` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `wallets_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `webhook_endpoint_id` bigint unsigned NOT NULL,
  `event` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload` json NOT NULL,
  `response` json DEFAULT NULL,
  `response_status` int DEFAULT NULL,
  `http_status` int DEFAULT NULL,
  `response_body` json DEFAULT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_deliveries_webhook_endpoint_id_event_index` (`webhook_endpoint_id`,`event`),
  KEY `webhook_deliveries_delivered_at_index` (`delivered_at`),
  KEY `webhook_deliveries_failed_at_index` (`failed_at`),
  KEY `webhook_deliveries_retry_index` (`next_retry_at`,`failed_at`),
  CONSTRAINT `webhook_deliveries_webhook_endpoint_id_foreign` FOREIGN KEY (`webhook_endpoint_id`) REFERENCES `webhook_endpoints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_delivery_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_delivery_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `webhook_id` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `event_type` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('success','failed','pending','retry') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `status_code` int DEFAULT NULL,
  `request_payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `response_body` longtext COLLATE utf8mb4_unicode_ci,
  `error_message` text COLLATE utf8mb4_unicode_ci,
  `attempts` int NOT NULL DEFAULT '1',
  `next_retry_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_delivery_logs_webhook_id_status_index` (`webhook_id`,`status`),
  KEY `webhook_delivery_logs_event_type_created_at_index` (`event_type`,`created_at`),
  KEY `webhook_delivery_logs_status_index` (`status`),
  KEY `webhook_delivery_logs_webhook_id_index` (`webhook_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `webhook_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_endpoints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `events` json NOT NULL,
  `retry_policy` json DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `failure_count` int NOT NULL DEFAULT '0',
  `last_delivery_at` timestamp NULL DEFAULT NULL,
  `last_triggered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_endpoints_user_id_is_active_index` (`user_id`,`is_active`),
  CONSTRAINT `webhook_endpoints_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wh_locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `wh_locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('shelf','floor','cage','bin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` int unsigned DEFAULT NULL,
  `status` enum('active','blocked') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wh_locations_branch_id_foreign` (`branch_id`),
  CONSTRAINT `wh_locations_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hubs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `whatsapp_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `whatsapp_templates` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `language` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'en',
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `approved` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `whatsapp_templates_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `why_couriers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `why_couriers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `image_id` bigint unsigned DEFAULT NULL,
  `position` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1' COMMENT '1= Active, 0= Inactive',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `why_couriers_image_id_foreign` (`image_id`),
  CONSTRAINT `why_couriers_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_task_activities`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_task_activities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workflow_task_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `details` json DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `workflow_task_activities_workflow_task_id_foreign` (`workflow_task_id`),
  KEY `workflow_task_activities_user_id_foreign` (`user_id`),
  CONSTRAINT `workflow_task_activities_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_task_activities_workflow_task_id_foreign` FOREIGN KEY (`workflow_task_id`) REFERENCES `workflow_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_task_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_task_comments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `workflow_task_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `body` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_task_comments_user_id_foreign` (`user_id`),
  KEY `workflow_task_comments_workflow_task_id_created_at_index` (`workflow_task_id`,`created_at`),
  CONSTRAINT `workflow_task_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_task_comments_workflow_task_id_foreign` FOREIGN KEY (`workflow_task_id`) REFERENCES `workflow_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `workflow_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `workflow_tasks` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `status` enum('pending','in_progress','testing','awaiting_feedback','completed','delayed') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'medium',
  `creator_id` bigint unsigned DEFAULT NULL,
  `assigned_to` bigint unsigned DEFAULT NULL,
  `project_id` bigint unsigned DEFAULT NULL,
  `project_name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stage` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status_label` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `client` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tracking_number` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `due_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_status_at` timestamp NULL DEFAULT NULL,
  `tags` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `time_tracking` json DEFAULT NULL,
  `dependencies` json DEFAULT NULL,
  `attachments` json DEFAULT NULL,
  `watchers` json DEFAULT NULL,
  `allowed_transitions` json DEFAULT NULL,
  `restricted_roles` json DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `workflow_tasks_creator_id_foreign` (`creator_id`),
  KEY `workflow_tasks_status_priority_index` (`status`,`priority`),
  KEY `workflow_tasks_assigned_to_status_index` (`assigned_to`,`status`),
  KEY `workflow_tasks_tracking_number_index` (`tracking_number`),
  KEY `workflow_tasks_due_at_index` (`due_at`),
  CONSTRAINT `workflow_tasks_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `workflow_tasks_creator_id_foreign` FOREIGN KEY (`creator_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `zones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `zones` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `countries` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zones_code_unique` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'2014_09_12_000000_create_hubs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'2014_09_12_000000_create_uploads_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'2014_10_10_040240_create_roles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2014_10_11_000000_create_deliverycategories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2014_10_11_000000_create_departments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2014_10_11_000000_create_designations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2014_10_11_000000_create_packagings_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2014_10_11_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2014_10_11_000001_create_merchants_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2014_10_12_100000_create_password_resets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2019_08_19_000000_create_failed_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2019_12_14_000001_create_personal_access_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2022_02_15_122629_create_push_notifications_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2022_03_20_060621_create_categories_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2022_03_24_042455_create_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2022_03_24_042456_add_event_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2022_03_24_042457_add_batch_uuid_column_to_activity_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2022_04_04_142330_create_delivery_man_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2022_04_04_142330_create_hub_incharges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2022_04_04_142330_create_parcels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2022_04_09_101126_create_delivery_charges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2022_04_09_101126_create_merchant_delivery_charges_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2022_04_10_050353_create_merchant_shops_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2022_04_13_034848_create_merchant_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2022_04_13_054047_create_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2022_04_14_045839_create_fund_transfers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2022_04_14_063624_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2022_04_17_061311_create_payment_accounts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2022_04_19_035758_create_configs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2022_04_20_053011_create_sessions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2022_04_23_032024_create_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2022_04_24_045606_create_parcel_logs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2022_04_27_123343_create_parcel_events_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2022_05_14_112714_create_account_heads_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2022_05_14_112715_create_expenses_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2022_05_14_112717_create_deliveryman_statements_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2022_05_15_102801_create_merchant_statements_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2022_05_17_124213_create_incomes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2022_05_17_132716_create_courier_statements_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2022_05_18_113259_create_to_dos_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2022_05_23_111055_create_supports_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2022_05_23_122723_create_sms_send_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2022_05_23_122723_create_sms_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2022_05_24_141546_create_vat_statements_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2022_05_26_093710_create_bank_transactions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2022_05_31_094551_create_general_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2022_05_31_094551_create_notification_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2022_05_31_122026_create_assets_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2022_05_31_122655_create_assetcategories_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2022_05_31_150039_create_salaries_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2022_05_6_063624_create_hub_payments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2022_06_01_144229_create_news_offers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2022_06_02_125218_create_support_chats_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2022_06_04_104751_create_hub_statements_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2022_06_05_093107_create_frauds_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2022_06_05_140650_create_cash_received_from_deliverymen_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2022_06_12_111844_create_salary_generates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2022_08_17_145916_create_subscribes_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2022_09_08_102027_create_pickup_requests_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2022_10_11_121745_create_invoices_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2022_10_17_102458_create_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2022_10_30_135339_create_merchant_online_payments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2022_11_02_105821_create_merchant_online_payment_receiveds_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2022_11_02_113430_create_merchant_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2022_12_08_104319_create_addons_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2022_12_08_104319_create_currencies_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2023_06_11_172412_create_social_links_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2023_06_12_144849_create_services_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2023_06_13_111335_create_why_couriers_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2023_06_13_122133_create_faqs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2023_06_13_133544_create_partners_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2023_06_13_154945_create_blogs_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2023_06_13_164933_create_pages_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2023_06_13_180141_create_sections_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2023_10_17_122352_create_wallets_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2023_10_8_094551_create_google_map_settings_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2024_01_01_000000_add_dynamic_pricing_indexes',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2024_06_26_065107_create_invoice_parcels_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2024_10_01_000000_create_contracts_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2024_11_07_000001_create_pricing_rules_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2024_11_07_000002_create_service_level_definitions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2024_11_07_000003_create_contract_templates_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2024_11_07_000004_enhance_contracts_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2024_11_07_000005_create_promotional_campaigns_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2025_10_02_232657_create_customers_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2024_11_07_000006_create_customer_milestones_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2024_11_07_000007_create_fuel_indices_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2024_11_07_000008_create_competitor_prices_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2025_01_01_000000_create_analytics_optimization_tables',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2025_01_06_000000_create_api_gateway_tables',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2025_03_24_091421_create_notifications_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2025_05_19_065351_create_banks_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2025_05_19_094956_create_mobile_banks_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2025_05_20_000001_add_columns_to_assets_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2025_05_20_065306_create_vehicles_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2025_05_20_065340_create_fuels_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2025_05_20_065408_create_maintainances_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2025_05_20_065438_create_accidents_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2025_05_20_065505_create_asset_assigns_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2025_05_24_055308_create_online_payments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2025_05_27_062557_add_deliveryman_current_location_to_delivery_man_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2025_09_01_215819_enhance_hubs_for_multi_branch_support',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2025_09_01_220734_create_branch_configurations_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2025_09_10_173359_create_shipments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2025_09_10_173723_create_scan_events_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2025_09_10_174158_create_transport_legs_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2025_09_10_181042_create_bags_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2025_09_10_194116_create_bag_parcel_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2025_09_10_194603_create_routes_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2025_09_10_194749_create_stops_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2025_09_10_201807_create_epods_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2025_09_10_204228_create_notifications_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2025_09_10_204449_create_rate_cards_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2025_09_10_204632_create_charge_lines_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2025_09_10_204740_create_invoices_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2025_09_10_205437_create_cod_receipts_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2025_09_10_205923_create_settlement_cycles_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2025_09_10_210025_create_commodities_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2025_09_10_210334_create_hs_codes_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (120,'2025_09_10_211711_create_customs_docs_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (121,'2025_09_12_000001_create_otp_codes_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (122,'2025_09_12_000002_add_phone_e164_to_users_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (123,'2025_09_12_000003_create_user_consents_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (124,'2025_09_13_000001_create_dhl_modules_tables',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (125,'2025_09_13_150000_create_zones_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (126,'2025_09_13_150100_create_lanes_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (127,'2025_09_13_150200_create_carriers_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (128,'2025_09_13_150300_create_carrier_services_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (129,'2025_09_13_150400_create_whatsapp_templates_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2025_09_13_150500_create_edi_providers_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2025_09_13_150600_create_surveys_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2025_09_13_170000_create_api_keys_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2025_09_17_000001_create_impersonation_logs_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2025_09_17_000002_add_notification_prefs_to_users',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2025_09_25_190000_rename_deliverd_date_to_delivered_date_in_parcels_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2025_09_25_190100_change_weight_to_decimal_in_parcels_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2025_09_30_003358_create_devices_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2025_09_30_012435_add_public_token_to_shipments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2025_09_30_020000_create_pod_proofs_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (140,'2025_09_30_021000_create_tasks_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2025_09_30_022000_create_webhook_endpoints_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2025_09_30_023000_create_webhook_deliveries_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (143,'2025_09_30_024000_create_driver_locations_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2025_10_02_224758_create_unified_branches_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2025_10_02_224905_create_branch_managers_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2025_10_02_225004_create_branch_workers_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2025_10_03_004509_add_unified_workflow_fields_to_shipments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2025_10_05_000001_create_clients_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2025_10_06_022706_create_shipment_logs_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2025_10_08_120000_create_operations_notifications_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2025_11_06_070000_add_transaction_id_to_payments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2025_11_06_100000_create_workflow_tasks_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2025_11_06_100100_create_workflow_task_comments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2025_11_06_100200_create_workflow_task_activities_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2025_11_06_110000_add_name_to_customers_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2025_11_06_111000_add_shipment_foreign_key_to_payments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2025_11_06_120000_create_dimension_tables',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2025_11_06_121000_create_fact_tables',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2025_11_06_122000_create_etl_audit_tables',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2025_11_06_140200_create_report_version_control_tables',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2025_11_06_150500_update_shipments_lifecycle_columns',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2025_11_06_150900_create_shipment_transitions_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2025_11_06_151000_update_scan_events_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2025_11_06_200000_create_fact_customer_churn_metrics_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2025_11_06_200100_create_fact_customer_sentiment_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2025_11_06_200200_create_fact_customer_segments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2025_11_06_200300_create_dimension_churn_factors_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2025_11_06_200400_create_dimension_sentiment_categories_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2025_11_06_200500_create_dimension_customer_segments_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2025_11_06_205000_create_branches_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2025_11_06_210000_update_branches_table_for_branch_workforce',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2025_11_06_211000_update_branch_workers_table_for_workforce',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2025_11_06_212000_create_branch_metrics_and_alerts_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2025_11_06_213000_create_drivers_and_rosters_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2025_11_06_230000_create_security_roles_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2025_11_06_230100_create_security_permissions_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2025_11_06_230200_create_security_role_permissions_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2025_11_06_230300_create_security_audit_logs_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2025_11_06_230400_create_security_encryption_keys_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2025_11_06_230500_create_security_user_roles_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2025_11_06_240000_create_security_mfa_devices_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2025_11_06_240100_create_security_privacy_consents_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2025_11_07_000010_create_promotion_tracking_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2025_11_07_020000_create_contract_management_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2025_11_07_020000_create_promotion_tracking_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2025_11_07_030000_create_accessibility_compliance_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2025_11_07_120000_add_mode_to_shipments_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2025_11_07_122724_add_currency_id_to_general_settings_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2025_11_09_200000_create_payment_requests_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2025_11_09_210000_create_translations_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2025_11_09_220000_add_translation_permissions',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2025_11_10_000002_create_event_streams_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2025_11_10_120000_create_edi_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2025_11_10_130000_update_webhook_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2025_11_10_135000_create_api_request_logs_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2025_11_11_011432_update_webhook_tables',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2025_11_11_011519_create_edi_mappings_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2025_11_11_011525_create_edi_transactions_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2025_11_11_120000_add_mobile_scanning_to_devices',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2025_11_12_000500_update_existing_edi_transactions_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2025_11_12_150000_add_preferred_language_to_users_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2025_11_12_151500_add_primary_branch_id_to_users_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2025_11_20_201138_add_tracking_id_and_details_to_general_settings_table',6);
