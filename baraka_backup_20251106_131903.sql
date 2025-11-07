-- MySQL dump 10.13  Distrib 8.0.43, for Linux (x86_64)
--
-- Host: localhost    Database: baraka
-- ------------------------------------------------------
-- Server version	8.0.43-0ubuntu0.24.04.2

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accidents`
--

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

--
-- Dumping data for table `accidents`
--

LOCK TABLES `accidents` WRITE;
/*!40000 ALTER TABLE `accidents` DISABLE KEYS */;
/*!40000 ALTER TABLE `accidents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `account_heads`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `account_heads`
--

LOCK TABLES `account_heads` WRITE;
/*!40000 ALTER TABLE `account_heads` DISABLE KEYS */;
INSERT INTO `account_heads` VALUES (1,1,'Payment received from Merchant',1,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(2,1,'Cash received from delivery man',1,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(3,1,'Others',1,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(4,2,'Payment paid to merchant',0,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(5,2,'Commission paid to delivery man',1,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(6,2,'Others',1,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(7,1,'Payment receive from hub',1,'2025-06-29 11:29:46','2025-06-29 11:29:46');
/*!40000 ALTER TABLE `account_heads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `accounts`
--

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

--
-- Dumping data for table `accounts`
--

LOCK TABLES `accounts` WRITE;
/*!40000 ALTER TABLE `accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `activity_log`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=481 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'User','created','App\\Models\\User','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-11-06 05:20:48','2025-11-06 05:20:48'),(2,'User','updated','App\\Models\\User','updated',1,NULL,NULL,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-11-06 05:21:02','2025-11-06 05:21:02'),(3,'User','created','App\\Models\\User','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"sanaa Administrator\", \"email\": \"info@sanaa.co\"}}',NULL,'2025-11-06 05:21:02','2025-11-06 05:21:02'),(4,'Upload','created','App\\Models\\Backend\\Upload','created',4,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user4.png\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(5,'Upload','created','App\\Models\\Backend\\Upload','created',5,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user5.png\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(6,'Upload','created','App\\Models\\Backend\\Upload','created',6,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user6.png\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(7,'Upload','created','App\\Models\\Backend\\Upload','created',7,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user7.png\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(8,'Hub','created','App\\Models\\Backend\\Hub','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Mirpur-10\", \"phone\": \"01000000001\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(9,'Hub','created','App\\Models\\Backend\\Hub','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Uttara\", \"phone\": \"01000000002\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(10,'Hub','created','App\\Models\\Backend\\Hub','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Dhanmundi\", \"phone\": \"01000000003\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(11,'Hub','created','App\\Models\\Backend\\Hub','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Old Dhaka\", \"phone\": \"01000000004\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(12,'Hub','created','App\\Models\\Backend\\Hub','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Jatrabari\", \"phone\": \"01000000005\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(13,'Hub','created','App\\Models\\Backend\\Hub','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Badda\", \"phone\": \"01000000006\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(14,'Department','created','App\\Models\\Backend\\Department','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"General Management\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(15,'Department','created','App\\Models\\Backend\\Department','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Marketing\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(16,'Department','created','App\\Models\\Backend\\Department','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Operations\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(17,'Department','created','App\\Models\\Backend\\Department','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Finance\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(18,'Department','created','App\\Models\\Backend\\Department','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Sales\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(19,'Department','created','App\\Models\\Backend\\Department','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"Human Resource\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(20,'Department','created','App\\Models\\Backend\\Department','created',7,NULL,NULL,'{\"attributes\": {\"title\": \"Purchase\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(21,'Designation','created','App\\Models\\Backend\\Designation','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Executive Officer (CEO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(22,'Designation','created','App\\Models\\Backend\\Designation','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Operating Officer (COO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(23,'Designation','created','App\\Models\\Backend\\Designation','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Financial Officer (CFO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(24,'Designation','created','App\\Models\\Backend\\Designation','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Technology Officer (CTO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(25,'Designation','created','App\\Models\\Backend\\Designation','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Legal Officer (CLO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(26,'Designation','created','App\\Models\\Backend\\Designation','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Marketing Officer (CMO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(27,'Role','created','App\\Models\\Backend\\Role','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Super Admin\", \"permissions\": [\"dashboard_read\", \"calendar_read\", \"total_parcel\", \"total_user\", \"total_merchant\", \"total_delivery_man\", \"total_hubs\", \"total_accounts\", \"total_parcels_pending\", \"total_pickup_assigned\", \"total_received_warehouse\", \"total_deliveryman_assigned\", \"total_partial_deliverd\", \"total_parcels_deliverd\", \"recent_accounts\", \"recent_salary\", \"recent_hub\", \"all_statements\", \"income_expense_charts\", \"merchant_revenue_charts\", \"deliveryman_revenue_charts\", \"courier_revenue_charts\", \"recent_parcels\", \"bank_transaction\", \"log_read\", \"hub_read\", \"hub_create\", \"hub_update\", \"hub_delete\", \"hub_incharge_read\", \"hub_incharge_create\", \"hub_incharge_update\", \"hub_incharge_delete\", \"hub_incharge_assigned\", \"account_read\", \"account_create\", \"account_update\", \"account_delete\", \"income_read\", \"income_create\", \"income_update\", \"income_delete\", \"expense_read\", \"expense_create\", \"expense_update\", \"expense_delete\", \"todo_read\", \"todo_create\", \"todo_update\", \"todo_delete\", \"fund_transfer_read\", \"fund_transfer_create\", \"fund_transfer_update\", \"fund_transfer_delete\", \"role_read\", \"role_create\", \"role_update\", \"role_delete\", \"designation_read\", \"designation_create\", \"designation_update\", \"designation_delete\", \"department_read\", \"department_create\", \"department_update\", \"department_delete\", \"user_read\", \"user_create\", \"user_update\", \"user_delete\", \"permission_update\", \"merchant_read\", \"merchant_create\", \"merchant_update\", \"merchant_delete\", \"merchant_view\", \"merchant_delivery_charge_read\", \"merchant_delivery_charge_create\", \"merchant_delivery_charge_update\", \"merchant_delivery_charge_delete\", \"merchant_shop_read\", \"merchant_shop_create\", \"merchant_shop_update\", \"merchant_shop_delete\", \"merchant_payment_read\", \"merchant_payment_create\", \"merchant_payment_update\", \"merchant_payment_delete\", \"payment_read\", \"payment_create\", \"payment_update\", \"payment_delete\", \"payment_reject\", \"payment_process\", \"hub_payment_read\", \"hub_payment_create\", \"hub_payment_update\", \"hub_payment_delete\", \"hub_payment_reject\", \"hub_payment_process\", \"hub_payment_request_read\", \"hub_payment_request_create\", \"hub_payment_request_update\", \"hub_payment_request_delete\", \"parcel_read\", \"parcel_create\", \"parcel_update\", \"parcel_delete\", \"parcel_status_update\", \"delivery_man_read\", \"delivery_man_create\", \"delivery_man_update\", \"delivery_man_delete\", \"delivery_category_read\", \"delivery_category_create\", \"delivery_category_update\", \"delivery_category_delete\", \"delivery_charge_read\", \"delivery_charge_create\", \"delivery_charge_update\", \"delivery_charge_delete\", \"delivery_type_read\", \"delivery_type_status_change\", \"liquid_fragile_read\", \"liquid_fragile_update\", \"liquid_status_change\", \"packaging_read\", \"packaging_create\", \"packaging_update\", \"packaging_delete\", \"category_read\", \"category_create\", \"category_update\", \"category_delete\", \"account_heads_read\", \"database_backup_read\", \"salary_read\", \"salary_create\", \"salary_update\", \"salary_delete\", \"support_read\", \"support_create\", \"support_update\", \"support_delete\", \"support_reply\", \"support_status_update\", \"sms_settings_read\", \"sms_settings_create\", \"sms_settings_update\", \"sms_settings_delete\", \"sms_send_settings_read\", \"sms_send_settings_create\", \"sms_send_settings_update\", \"sms_send_settings_delete\", \"general_settings_read\", \"general_settings_update\", \"notification_settings_read\", \"notification_settings_update\", \"push_notification_read\", \"push_notification_create\", \"push_notification_update\", \"push_notification_delete\", \"asset_category_read\", \"asset_category_create\", \"asset_category_update\", \"asset_category_delete\", \"news_offer_read\", \"news_offer_create\", \"news_offer_update\", \"news_offer_delete\", \"parcel_status_reports\", \"parcel_wise_profit\", \"parcel_total_summery\", \"salary_reports\", \"merchant_hub_deliveryman\", \"salary_generate_read\", \"salary_generate_create\", \"salary_generate_update\", \"salary_generate_delete\", \"assets_read\", \"assets_create\", \"assets_update\", \"assets_delete\", \"fraud_read\", \"fraud_create\", \"fraud_update\", \"fraud_delete\", \"subscribe_read\", \"pickup_request_regular\", \"pickup_request_express\", \"invoice_read\", \"invoice_status_update\", \"social_login_settings_read\", \"social_login_settings_update\", \"payout_setup_settings_read\", \"payout_setup_settings_update\", \"online_payment_read\", \"payout_read\", \"payout_create\", \"hub_view\", \"paid_invoice_read\", \"invoice_generate_menually\", \"currency_read\", \"currency_create\", \"currency_update\", \"currency_delete\", \"social_link_read\", \"social_link_create\", \"social_link_update\", \"social_link_delete\", \"service_read\", \"service_create\", \"service_update\", \"service_delete\", \"why_courier_read\", \"why_courier_create\", \"why_courier_update\", \"why_courier_delete\", \"faq_read\", \"faq_create\", \"faq_update\", \"faq_delete\", \"partner_read\", \"partner_create\", \"partner_update\", \"partner_delete\", \"blogs_read\", \"blogs_create\", \"blogs_update\", \"blogs_delete\", \"pages_read\", \"pages_update\", \"section_read\", \"section_update\", \"mail_settings_read\", \"mail_settings_update\", \"wallet_request_read\", \"wallet_request_create\", \"wallet_request_delete\", \"wallet_request_approve\", \"wallet_request_reject\"]}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(28,'Role','created','App\\Models\\Backend\\Role','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Admin\", \"permissions\": [\"dashboard_read\", \"calendar_read\", \"total_parcel\", \"total_user\", \"total_merchant\", \"total_delivery_man\", \"total_hubs\", \"total_accounts\", \"total_parcels_pending\", \"total_pickup_assigned\", \"total_received_warehouse\", \"total_deliveryman_assigned\", \"total_partial_deliverd\", \"total_parcels_deliverd\", \"recent_accounts\", \"recent_salary\", \"recent_hub\", \"all_statements\", \"income_expense_charts\", \"merchant_revenue_charts\", \"deliveryman_revenue_charts\", \"courier_revenue_charts\", \"recent_parcels\", \"bank_transaction\", \"log_read\", \"hub_read\", \"hub_incharge_read\", \"account_read\", \"income_read\", \"expense_read\", \"todo_read\", \"sms_settings_read\", \"sms_send_settings_read\", \"general_settings_read\", \"notification_settings_read\", \"push_notification_read\", \"push_notification_create\", \"push_notification_update\", \"push_notification_delete\", \"account_heads_read\", \"salary_read\", \"support_read\", \"fund_transfer_read\", \"role_read\", \"designation_read\", \"department_read\", \"user_read\", \"merchant_read\", \"merchant_delivery_charge_read\", \"merchant_shop_read\", \"merchant_payment_read\", \"payment_read\", \"hub_payment_request_read\", \"hub_payment_read\", \"parcel_read\", \"delivery_man_read\", \"delivery_category_read\", \"delivery_charge_read\", \"delivery_type_read\", \"liquid_fragile_read\", \"packaging_read\", \"category_read\", \"asset_category_read\", \"news_offer_read\", \"sms_settings_status_change\", \"sms_send_settings_status_change\", \"bank_transaction_read\", \"database_backup_read\", \"parcel_status_reports\", \"parcel_wise_profit\", \"parcel_total_summery\", \"salary_reports\", \"merchant_hub_deliveryman\", \"salary_generate_read\", \"assets_read\", \"fraud_read\", \"subscribe_read\", \"pickup_request_regular\", \"pickup_request_express\", \"cash_received_from_delivery_man_read\", \"cash_received_from_delivery_man_create\", \"cash_received_from_delivery_man_update\", \"cash_received_from_delivery_man_delete\", \"invoice_read\", \"invoice_status_update\", \"social_login_settings_read\", \"social_login_settings_update\", \"payout_setup_settings_read\", \"online_payment_read\", \"payout_read\", \"hub_view\", \"paid_invoice_read\", \"invoice_generate_menually\", \"currency_read\"]}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(29,'User','created','App\\Models\\User','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"WemaxDevs\", \"email\": \"admin@wemaxdevs.com\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(30,'User','created','App\\Models\\User','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Branch\", \"email\": \"branch@wemaxdevs.com\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(31,'User','created','App\\Models\\User','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Delivery Man\", \"email\": \"deliveryman@wemaxit.com\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(32,'DeliveryMan','created','App\\Models\\Backend\\DeliveryMan','created',1,NULL,NULL,'{\"attributes\": {\"user.name\": \"Delivery Man\", \"current_balance\": \"0.00\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(33,'InCharges','created','App\\Models\\Backend\\HubInCharge','created',1,NULL,NULL,'{\"attributes\": {\"user.name\": \"Branch\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(34,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"KG\", \"description\": null}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(35,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Mobile\", \"description\": null}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(36,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Laptop\", \"description\": null}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(37,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Tabs\", \"description\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(38,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Gaming Kybord\", \"description\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(39,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"Cosmetices\", \"description\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(40,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',1,NULL,NULL,'{\"attributes\": {\"weight\": 1, \"next_day\": \"60.00\", \"position\": 1, \"same_day\": \"50.00\", \"sub_city\": \"70.00\", \"outside_city\": \"80.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(41,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',2,NULL,NULL,'{\"attributes\": {\"weight\": 2, \"next_day\": \"100.00\", \"position\": 2, \"same_day\": \"90.00\", \"sub_city\": \"110.00\", \"outside_city\": \"120.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(42,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',3,NULL,NULL,'{\"attributes\": {\"weight\": 3, \"next_day\": \"140.00\", \"position\": 3, \"same_day\": \"130.00\", \"sub_city\": \"150.00\", \"outside_city\": \"160.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(43,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',4,NULL,NULL,'{\"attributes\": {\"weight\": 4, \"next_day\": \"180.00\", \"position\": 4, \"same_day\": \"170.00\", \"sub_city\": \"190.00\", \"outside_city\": \"200.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(44,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',5,NULL,NULL,'{\"attributes\": {\"weight\": 5, \"next_day\": \"220.00\", \"position\": 5, \"same_day\": \"210.00\", \"sub_city\": \"230.00\", \"outside_city\": \"240.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(45,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',6,NULL,NULL,'{\"attributes\": {\"weight\": 6, \"next_day\": \"260.00\", \"position\": 6, \"same_day\": \"250.00\", \"sub_city\": \"270.00\", \"outside_city\": \"280.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(46,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',7,NULL,NULL,'{\"attributes\": {\"weight\": 7, \"next_day\": \"300.00\", \"position\": 7, \"same_day\": \"290.00\", \"sub_city\": \"310.00\", \"outside_city\": \"320.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(47,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',8,NULL,NULL,'{\"attributes\": {\"weight\": 8, \"next_day\": \"350.00\", \"position\": 8, \"same_day\": \"340.00\", \"sub_city\": \"360.00\", \"outside_city\": \"370.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(48,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',9,NULL,NULL,'{\"attributes\": {\"weight\": 9, \"next_day\": \"390.00\", \"position\": 9, \"same_day\": \"380.00\", \"sub_city\": \"400.00\", \"outside_city\": \"410.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(49,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',10,NULL,NULL,'{\"attributes\": {\"weight\": 10, \"next_day\": \"430.00\", \"position\": 10, \"same_day\": \"420.00\", \"sub_city\": \"440.00\", \"outside_city\": \"450.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(50,'User','created','App\\Models\\User','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Merchant\", \"email\": \"merchant@wemaxdevs.com\"}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(51,'Merchant','created','App\\Models\\Backend\\Merchant','created',1,NULL,NULL,'{\"attributes\": {\"user.name\": \"Merchant\", \"business_name\": \"WemaxDevs\", \"current_balance\": \"0.00\"}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(52,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',1,NULL,NULL,'{\"attributes\": {\"weight\": 1, \"next_day\": \"60.00\", \"same_day\": \"50.00\", \"sub_city\": \"70.00\", \"outside_city\": \"80.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(53,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',2,NULL,NULL,'{\"attributes\": {\"weight\": 2, \"next_day\": \"100.00\", \"same_day\": \"90.00\", \"sub_city\": \"110.00\", \"outside_city\": \"120.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(54,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',3,NULL,NULL,'{\"attributes\": {\"weight\": 3, \"next_day\": \"140.00\", \"same_day\": \"130.00\", \"sub_city\": \"150.00\", \"outside_city\": \"160.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(55,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',4,NULL,NULL,'{\"attributes\": {\"weight\": 4, \"next_day\": \"180.00\", \"same_day\": \"170.00\", \"sub_city\": \"190.00\", \"outside_city\": \"200.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(56,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',5,NULL,NULL,'{\"attributes\": {\"weight\": 5, \"next_day\": \"220.00\", \"same_day\": \"210.00\", \"sub_city\": \"230.00\", \"outside_city\": \"240.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(57,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',6,NULL,NULL,'{\"attributes\": {\"weight\": 6, \"next_day\": \"260.00\", \"same_day\": \"250.00\", \"sub_city\": \"270.00\", \"outside_city\": \"280.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(58,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',7,NULL,NULL,'{\"attributes\": {\"weight\": 7, \"next_day\": \"300.00\", \"same_day\": \"290.00\", \"sub_city\": \"310.00\", \"outside_city\": \"320.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(59,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',8,NULL,NULL,'{\"attributes\": {\"weight\": 8, \"next_day\": \"350.00\", \"same_day\": \"340.00\", \"sub_city\": \"360.00\", \"outside_city\": \"370.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(60,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',9,NULL,NULL,'{\"attributes\": {\"weight\": 9, \"next_day\": \"390.00\", \"same_day\": \"380.00\", \"sub_city\": \"400.00\", \"outside_city\": \"410.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(61,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',10,NULL,NULL,'{\"attributes\": {\"weight\": 10, \"next_day\": \"430.00\", \"same_day\": \"420.00\", \"sub_city\": \"440.00\", \"outside_city\": \"450.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(62,'MerchantShops','created','App\\Models\\MerchantShops','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 1\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(63,'MerchantShops','created','App\\Models\\MerchantShops','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 2\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(64,'MerchantShops','created','App\\Models\\MerchantShops','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 3\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(65,'MerchantShops','created','App\\Models\\MerchantShops','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 4\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(66,'MerchantShops','created','App\\Models\\MerchantShops','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 5\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(67,'Config','created','App\\Models\\Config','created',1,NULL,NULL,'{\"attributes\": {\"key\": \"fragile_liquid_status\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(68,'Config','created','App\\Models\\Config','created',2,NULL,NULL,'{\"attributes\": {\"key\": \"fragile_liquid_charge\", \"value\": \"20\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(69,'Config','created','App\\Models\\Config','created',3,NULL,NULL,'{\"attributes\": {\"key\": \"same_day\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(70,'Config','created','App\\Models\\Config','created',4,NULL,NULL,'{\"attributes\": {\"key\": \"next_day\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(71,'Config','created','App\\Models\\Config','created',5,NULL,NULL,'{\"attributes\": {\"key\": \"sub_city\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(72,'Config','created','App\\Models\\Config','created',6,NULL,NULL,'{\"attributes\": {\"key\": \"outside_City\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(73,'Packaging','created','App\\Models\\Backend\\Packaging','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Poly\", \"price\": \"10.00\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(74,'Packaging','created','App\\Models\\Backend\\Packaging','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Bubble Poly\", \"price\": \"20.00\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(75,'Packaging','created','App\\Models\\Backend\\Packaging','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Box\", \"price\": \"30.00\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(76,'Packaging','created','App\\Models\\Backend\\Packaging','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Box Poly\", \"price\": \"40.00\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(77,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',1,NULL,NULL,'{\"attributes\": {\"key\": \"reve_api_key\", \"value\": \"Your API key\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(78,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',2,NULL,NULL,'{\"attributes\": {\"key\": \"reve_secret_key\", \"value\": \"Your secret key\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(79,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',3,NULL,NULL,'{\"attributes\": {\"key\": \"reve_api_url\", \"value\": \"http://smpp.ajuratech.com:7788/sendtext\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(80,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',4,NULL,NULL,'{\"attributes\": {\"key\": \"reve_username\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(81,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',5,NULL,NULL,'{\"attributes\": {\"key\": \"reve_user_password\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(82,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',6,NULL,NULL,'{\"attributes\": {\"key\": \"reve_status\", \"value\": \"0\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(83,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',7,NULL,NULL,'{\"attributes\": {\"key\": \"twilio_sid\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(84,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',8,NULL,NULL,'{\"attributes\": {\"key\": \"twilio_token\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(85,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',9,NULL,NULL,'{\"attributes\": {\"key\": \"twilio_from\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(86,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',10,NULL,NULL,'{\"attributes\": {\"key\": \"twilio_status\", \"value\": \"0\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(87,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',11,NULL,NULL,'{\"attributes\": {\"key\": \"nexmo_key\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(88,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',12,NULL,NULL,'{\"attributes\": {\"key\": \"nexmo_secret_key\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(89,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',13,NULL,NULL,'{\"attributes\": {\"key\": \"nexmo_status\", \"value\": \"0\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(90,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',14,NULL,NULL,'{\"attributes\": {\"key\": \"click_send_username\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(91,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',15,NULL,NULL,'{\"attributes\": {\"key\": \"click_send_api_key\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(92,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',16,NULL,NULL,'{\"attributes\": {\"key\": \"click_send_status\", \"value\": \"0\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(93,'SmsSendSetting','created','App\\Models\\Backend\\SmsSendSetting','created',1,NULL,NULL,'{\"attributes\": {\"sms_send_status\": 1}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(94,'SmsSendSetting','created','App\\Models\\Backend\\SmsSendSetting','created',2,NULL,NULL,'{\"attributes\": {\"sms_send_status\": 2}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(95,'SmsSendSetting','created','App\\Models\\Backend\\SmsSendSetting','created',3,NULL,NULL,'{\"attributes\": {\"sms_send_status\": 3}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(96,'Upload','created','App\\Models\\Backend\\Upload','created',8,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user8.png\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(97,'Upload','created','App\\Models\\Backend\\Upload','created',9,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user9.png\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(98,'General Settings','created','App\\Models\\Backend\\GeneralSettings','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(99,'Notification Settings','created','App\\Models\\Backend\\NotificationSettings','created',1,NULL,NULL,'{\"attributes\": {\"fcm_topic\": \"\", \"fcm_secret_key\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(100,'Upload','created','App\\Models\\Backend\\Upload','created',10,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/services/truck.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(101,'Upload','created','App\\Models\\Backend\\Upload','created',11,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/services/pick-drop.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(102,'Upload','created','App\\Models\\Backend\\Upload','created',12,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/services/packageing.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(103,'Upload','created','App\\Models\\Backend\\Upload','created',13,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/services/warehouse.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(104,'Upload','created','App\\Models\\Backend\\Upload','created',14,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/timly-delivery.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(105,'Upload','created','App\\Models\\Backend\\Upload','created',15,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/limitless-pickup.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(106,'Upload','created','App\\Models\\Backend\\Upload','created',16,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/cash-on-delivery.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(107,'Upload','created','App\\Models\\Backend\\Upload','created',17,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/payment.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(108,'Upload','created','App\\Models\\Backend\\Upload','created',18,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/handling.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(109,'Upload','created','App\\Models\\Backend\\Upload','created',19,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/live-tracking.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(110,'Upload','created','App\\Models\\Backend\\Upload','created',20,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/1.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(111,'Upload','created','App\\Models\\Backend\\Upload','created',21,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/atom.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(112,'Upload','created','App\\Models\\Backend\\Upload','created',22,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/digg.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(113,'Upload','created','App\\Models\\Backend\\Upload','created',23,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/2.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(114,'Upload','created','App\\Models\\Backend\\Upload','created',24,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/huawei.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(115,'Upload','created','App\\Models\\Backend\\Upload','created',25,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/ups.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(116,'Upload','created','App\\Models\\Backend\\Upload','created',26,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/1.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(117,'Upload','created','App\\Models\\Backend\\Upload','created',27,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/atom.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(118,'Upload','created','App\\Models\\Backend\\Upload','created',28,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/digg.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(119,'Upload','created','App\\Models\\Backend\\Upload','created',29,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/2.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(120,'Upload','created','App\\Models\\Backend\\Upload','created',30,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/huawei.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(121,'Upload','created','App\\Models\\Backend\\Upload','created',31,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/ups.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(122,'User','updated','App\\Models\\User','updated',1,NULL,NULL,'{\"old\": {\"name\": \"WemaxDevs\", \"email\": \"admin@wemaxdevs.com\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-06-29 11:29:50','2025-06-29 11:29:50'),(123,'User','updated','App\\Models\\User','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-06-29 11:54:32','2025-06-29 11:54:32'),(124,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:13:06','2025-06-29 12:13:06'),(125,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:14:12','2025-06-29 12:14:12'),(126,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:17:52','2025-06-29 12:17:52'),(127,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:20:33','2025-06-29 12:20:33'),(128,'Hub','updated','App\\Models\\Backend\\Hub','updated',6,'App\\Models\\User',1,'{\"old\": {\"name\": \"Badda\", \"phone\": \"01000000006\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Kinshasa\", \"phone\": \"08222254001\", \"address\": \"Haut-Congo 356 / Binza Upn / Ngaliema / Kinshasa\"}}',NULL,'2025-06-29 12:31:33','2025-06-29 12:31:33'),(129,'Hub','updated','App\\Models\\Backend\\Hub','updated',5,'App\\Models\\User',1,'{\"old\": {\"name\": \"Jatrabari\", \"phone\": \"01000000005\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Goma\", \"phone\": \"01000000005\", \"address\": \"Haut-Congo 356 / Binza Upn / Goma /\"}}',NULL,'2025-06-29 12:32:05','2025-06-29 12:32:05'),(130,'Hub','updated','App\\Models\\Backend\\Hub','updated',4,'App\\Models\\User',1,'{\"old\": {\"name\": \"Old Dhaka\", \"phone\": \"01000000004\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Lumubashi\", \"phone\": \"01000000004\", \"address\": \"Haut-Congo 356 / Binza Upn\"}}',NULL,'2025-06-29 12:32:37','2025-06-29 12:32:37'),(131,'Hub','updated','App\\Models\\Backend\\Hub','updated',3,'App\\Models\\User',1,'{\"old\": {\"name\": \"Dhanmundi\", \"phone\": \"01000000003\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Bukavu\", \"phone\": \"01000000003\", \"address\": \"825 Nobel Street\"}}',NULL,'2025-06-29 12:33:05','2025-06-29 12:33:05'),(132,'Hub','updated','App\\Models\\Backend\\Hub','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"Mirpur-10\", \"phone\": \"01000000001\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Kivu\", \"phone\": \"01000000001\", \"address\": \"North Kivu\"}}',NULL,'2025-06-29 12:33:46','2025-06-29 12:33:46'),(133,'Hub','updated','App\\Models\\Backend\\Hub','updated',2,'App\\Models\\User',1,'{\"old\": {\"name\": \"Uttara\", \"phone\": \"01000000002\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Kivu South\", \"phone\": \"01000000002\", \"address\": \"Kivu South\"}}',NULL,'2025-06-29 12:34:17','2025-06-29 12:34:17'),(134,'Upload','updated','App\\Models\\Backend\\Upload','updated',8,'App\\Models\\User',1,'{\"old\": {\"original\": \"uploads/users/user8.png\"}, \"attributes\": {\"original\": \"uploads/settings/202506291450101536.png\"}}',NULL,'2025-06-29 12:50:10','2025-06-29 12:50:10'),(135,'Upload','created','App\\Models\\Backend\\Upload','created',32,'App\\Models\\User',1,'{\"attributes\": {\"original\": \"uploads/settings/202506291459237515.png\"}}',NULL,'2025-06-29 12:59:23','2025-06-29 12:59:23'),(136,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:59:23','2025-06-29 12:59:23'),(137,'Upload','created','App\\Models\\Backend\\Upload','created',33,'App\\Models\\User',1,'{\"attributes\": {\"original\": \"uploads/section/20250629150636.png\"}}',NULL,'2025-06-29 13:06:36','2025-06-29 13:06:36'),(138,'User','created','App\\Models\\User','created',5,'App\\Models\\User',1,'{\"attributes\": {\"name\": \"Dennis Carroll\", \"email\": \"hupazef@mailinator.com\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(139,'Merchant','created','App\\Models\\Backend\\Merchant','created',2,'App\\Models\\User',1,'{\"attributes\": {\"user.name\": \"Dennis Carroll\", \"business_name\": \"Austin Chaney\", \"current_balance\": \"5.00\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(140,'MerchantShops','created','App\\Models\\MerchantShops','created',6,'App\\Models\\User',1,'{\"attributes\": {\"name\": \"Austin Chaney\", \"address\": \"Irure aliquid porro\", \"contact_no\": \"256702568978\", \"merchant.business_name\": \"Austin Chaney\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(141,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',11,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 1, \"next_day\": \"60.00\", \"same_day\": \"50.00\", \"sub_city\": \"70.00\", \"outside_city\": \"80.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(142,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',12,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 2, \"next_day\": \"100.00\", \"same_day\": \"90.00\", \"sub_city\": \"110.00\", \"outside_city\": \"120.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(143,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',13,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 3, \"next_day\": \"140.00\", \"same_day\": \"130.00\", \"sub_city\": \"150.00\", \"outside_city\": \"160.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(144,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',14,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 4, \"next_day\": \"180.00\", \"same_day\": \"170.00\", \"sub_city\": \"190.00\", \"outside_city\": \"200.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(145,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',15,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 5, \"next_day\": \"220.00\", \"same_day\": \"210.00\", \"sub_city\": \"230.00\", \"outside_city\": \"240.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(146,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',16,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 6, \"next_day\": \"260.00\", \"same_day\": \"250.00\", \"sub_city\": \"270.00\", \"outside_city\": \"280.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(147,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',17,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 7, \"next_day\": \"300.00\", \"same_day\": \"290.00\", \"sub_city\": \"310.00\", \"outside_city\": \"320.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(148,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',18,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 8, \"next_day\": \"350.00\", \"same_day\": \"340.00\", \"sub_city\": \"360.00\", \"outside_city\": \"370.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(149,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',19,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 9, \"next_day\": \"390.00\", \"same_day\": \"380.00\", \"sub_city\": \"400.00\", \"outside_city\": \"410.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(150,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',20,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 10, \"next_day\": \"430.00\", \"same_day\": \"420.00\", \"sub_city\": \"440.00\", \"outside_city\": \"450.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(151,'User','created','App\\Models\\User','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Raymond Mccray\", \"email\": null}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(152,'Merchant','created','App\\Models\\Backend\\Merchant','created',3,NULL,NULL,'{\"attributes\": {\"user.name\": \"Raymond Mccray\", \"business_name\": \"India Cote\", \"current_balance\": \"0.00\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(153,'MerchantShops','created','App\\Models\\MerchantShops','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"India Cote\", \"address\": \"Rem porro in delenit\", \"contact_no\": \"2567056567989\", \"merchant.business_name\": \"India Cote\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(154,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',21,NULL,NULL,'{\"attributes\": {\"weight\": 1, \"next_day\": \"60.00\", \"same_day\": \"50.00\", \"sub_city\": \"70.00\", \"outside_city\": \"80.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(155,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',22,NULL,NULL,'{\"attributes\": {\"weight\": 2, \"next_day\": \"100.00\", \"same_day\": \"90.00\", \"sub_city\": \"110.00\", \"outside_city\": \"120.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(156,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',23,NULL,NULL,'{\"attributes\": {\"weight\": 3, \"next_day\": \"140.00\", \"same_day\": \"130.00\", \"sub_city\": \"150.00\", \"outside_city\": \"160.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(157,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',24,NULL,NULL,'{\"attributes\": {\"weight\": 4, \"next_day\": \"180.00\", \"same_day\": \"170.00\", \"sub_city\": \"190.00\", \"outside_city\": \"200.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(158,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',25,NULL,NULL,'{\"attributes\": {\"weight\": 5, \"next_day\": \"220.00\", \"same_day\": \"210.00\", \"sub_city\": \"230.00\", \"outside_city\": \"240.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(159,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',26,NULL,NULL,'{\"attributes\": {\"weight\": 6, \"next_day\": \"260.00\", \"same_day\": \"250.00\", \"sub_city\": \"270.00\", \"outside_city\": \"280.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(160,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',27,NULL,NULL,'{\"attributes\": {\"weight\": 7, \"next_day\": \"300.00\", \"same_day\": \"290.00\", \"sub_city\": \"310.00\", \"outside_city\": \"320.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(161,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',28,NULL,NULL,'{\"attributes\": {\"weight\": 8, \"next_day\": \"350.00\", \"same_day\": \"340.00\", \"sub_city\": \"360.00\", \"outside_city\": \"370.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(162,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',29,NULL,NULL,'{\"attributes\": {\"weight\": 9, \"next_day\": \"390.00\", \"same_day\": \"380.00\", \"sub_city\": \"400.00\", \"outside_city\": \"410.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(163,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',30,NULL,NULL,'{\"attributes\": {\"weight\": 10, \"next_day\": \"430.00\", \"same_day\": \"420.00\", \"sub_city\": \"440.00\", \"outside_city\": \"450.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(164,'User','updated','App\\Models\\User','updated',5,'App\\Models\\User',5,'{\"old\": {\"name\": \"Dennis Carroll\", \"email\": \"hupazef@mailinator.com\"}, \"attributes\": {\"name\": \"Dennis Carroll\", \"email\": \"hupazef@mailinator.com\"}}',NULL,'2025-07-29 06:25:43','2025-07-29 06:25:43'),(165,'User','updated','App\\Models\\User','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-07-30 06:22:06','2025-07-30 06:22:06'),(166,'User','updated','App\\Models\\User','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-07-30 06:22:09','2025-07-30 06:22:09'),(167,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-07-30 06:23:51','2025-07-30 06:23:51'),(168,'User','created','App\\Models\\User','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(169,'User','created','App\\Models\\User','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(170,'User','created','App\\Models\\User','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(171,'User','created','App\\Models\\User','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(172,'Branch','created branch: Central Hub','App\\Models\\Backend\\Branch','created',1,NULL,NULL,'{\"attributes\": {\"code\": \"HUB-001\", \"name\": \"Central Hub\", \"type\": \"HUB\", \"is_hub\": true, \"status\": 1}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(173,'Branch','created branch: Regional Branch','App\\Models\\Backend\\Branch','created',2,NULL,NULL,'{\"attributes\": {\"code\": \"REG-001\", \"name\": \"Regional Branch\", \"type\": \"REGIONAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(174,'Branch','created branch: Local Branch','App\\Models\\Backend\\Branch','created',3,NULL,NULL,'{\"attributes\": {\"code\": \"LOC-001\", \"name\": \"Local Branch\", \"type\": \"LOCAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(175,'BranchManager','created branch manager: ','App\\Models\\Backend\\BranchManager','created',1,NULL,NULL,'{\"attributes\": {\"status\": 1, \"business_name\": null, \"current_balance\": \"0.00\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(176,'BranchWorker','created branch worker: Branch Worker','App\\Models\\Backend\\BranchWorker','created',1,NULL,NULL,'{\"attributes\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(177,'shipment','created shipment','App\\Models\\Shipment','created',1,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 10, \"price_amount\": \"49.99\", \"dest_branch_id\": 2, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(178,'User','updated','App\\Models\\User','updated',7,NULL,NULL,'{\"old\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}, \"attributes\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}}',NULL,'2025-11-06 05:33:25','2025-11-06 05:33:25'),(179,'User','updated','App\\Models\\User','updated',8,NULL,NULL,'{\"old\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}, \"attributes\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}}',NULL,'2025-11-06 05:33:25','2025-11-06 05:33:25'),(180,'User','updated','App\\Models\\User','updated',9,NULL,NULL,'{\"old\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}, \"attributes\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}}',NULL,'2025-11-06 05:33:25','2025-11-06 05:33:25'),(181,'User','updated','App\\Models\\User','updated',10,NULL,NULL,'{\"old\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}, \"attributes\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}}',NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26'),(182,'BranchWorker','updated branch worker: Branch Worker','App\\Models\\Backend\\BranchWorker','updated',1,NULL,NULL,'{\"old\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}, \"attributes\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26'),(183,'shipment','created shipment','App\\Models\\Shipment','created',2,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 10, \"price_amount\": \"49.99\", \"dest_branch_id\": 2, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26'),(184,'User','updated','App\\Models\\User','updated',7,NULL,NULL,'{\"old\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}, \"attributes\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(185,'User','updated','App\\Models\\User','updated',8,NULL,NULL,'{\"old\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}, \"attributes\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(186,'User','updated','App\\Models\\User','updated',9,NULL,NULL,'{\"old\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}, \"attributes\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(187,'User','updated','App\\Models\\User','updated',10,NULL,NULL,'{\"old\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}, \"attributes\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(188,'BranchWorker','updated branch worker: Branch Worker','App\\Models\\Backend\\BranchWorker','updated',1,NULL,NULL,'{\"old\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}, \"attributes\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(189,'shipment','created shipment','App\\Models\\Shipment','created',3,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 10, \"price_amount\": \"49.99\", \"dest_branch_id\": 2, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(347,'User','updated','App\\Models\\User','updated',1,'App\\Models\\User',2,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-11-06 10:13:42','2025-11-06 10:13:42');
/*!40000 ALTER TABLE `activity_log` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `addons`
--

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

--
-- Dumping data for table `addons`
--

LOCK TABLES `addons` WRITE;
/*!40000 ALTER TABLE `addons` DISABLE KEYS */;
/*!40000 ALTER TABLE `addons` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `address_books`
--

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

--
-- Dumping data for table `address_books`
--

LOCK TABLES `address_books` WRITE;
/*!40000 ALTER TABLE `address_books` DISABLE KEYS */;
/*!40000 ALTER TABLE `address_books` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `api_keys`
--

DROP TABLE IF EXISTS `api_keys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_keys` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `scopes` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_keys_token_unique` (`token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `api_keys`
--

LOCK TABLES `api_keys` WRITE;
/*!40000 ALTER TABLE `api_keys` DISABLE KEYS */;
/*!40000 ALTER TABLE `api_keys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `asset_assigns`
--

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

--
-- Dumping data for table `asset_assigns`
--

LOCK TABLES `asset_assigns` WRITE;
/*!40000 ALTER TABLE `asset_assigns` DISABLE KEYS */;
/*!40000 ALTER TABLE `asset_assigns` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assetcategories`
--

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

--
-- Dumping data for table `assetcategories`
--

LOCK TABLES `assetcategories` WRITE;
/*!40000 ALTER TABLE `assetcategories` DISABLE KEYS */;
/*!40000 ALTER TABLE `assetcategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assets`
--

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

--
-- Dumping data for table `assets`
--

LOCK TABLES `assets` WRITE;
/*!40000 ALTER TABLE `assets` DISABLE KEYS */;
/*!40000 ALTER TABLE `assets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `awb_stocks`
--

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

--
-- Dumping data for table `awb_stocks`
--

LOCK TABLES `awb_stocks` WRITE;
/*!40000 ALTER TABLE `awb_stocks` DISABLE KEYS */;
/*!40000 ALTER TABLE `awb_stocks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bag_parcel`
--

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

--
-- Dumping data for table `bag_parcel`
--

LOCK TABLES `bag_parcel` WRITE;
/*!40000 ALTER TABLE `bag_parcel` DISABLE KEYS */;
/*!40000 ALTER TABLE `bag_parcel` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bags`
--

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

--
-- Dumping data for table `bags`
--

LOCK TABLES `bags` WRITE;
/*!40000 ALTER TABLE `bags` DISABLE KEYS */;
/*!40000 ALTER TABLE `bags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `bank_transactions`
--

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

--
-- Dumping data for table `bank_transactions`
--

LOCK TABLES `bank_transactions` WRITE;
/*!40000 ALTER TABLE `bank_transactions` DISABLE KEYS */;
/*!40000 ALTER TABLE `bank_transactions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `banks`
--

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

--
-- Dumping data for table `banks`
--

LOCK TABLES `banks` WRITE;
/*!40000 ALTER TABLE `banks` DISABLE KEYS */;
/*!40000 ALTER TABLE `banks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `blogs`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `blogs`
--

LOCK TABLES `blogs` WRITE;
/*!40000 ALTER TABLE `blogs` DISABLE KEYS */;
INSERT INTO `blogs` VALUES (1,'Et voluptatem maxime minima quasi minus quia.',NULL,'Vel voluptatibus consectetur totam exercitationem qui officiis saepe aperiam ut est consectetur eos quia consequuntur provident itaque eligendi ut consectetur iusto et quidem cumque beatae consequatur ea et debitis ab ipsum fugiat doloremque aut facilis magnam enim earum neque error quo aut ea aut voluptates itaque harum soluta quae porro minima maxime velit est perspiciatis voluptatem perspiciatis consequatur quidem provident iste accusantium odio iusto cum voluptatem sed est harum architecto eveniet ut illum repudiandae reiciendis deserunt nisi sapiente enim ratione perferendis itaque fuga eos modi non rerum molestiae dicta ratione ab voluptatum ad enim aliquam rerum et possimus ipsam qui porro hic sit rerum ab ipsa animi tenetur debitis culpa non deserunt rerum voluptatem ex ullam assumenda dolorum tenetur dignissimos aliquid similique aliquid asperiores ut labore et dolore omnis nostrum.','0',1,1,'1','2025-06-29 11:29:49','2025-08-05 03:46:59'),(2,'Ut rerum et debitis quo itaque qui ducimus tempore.',NULL,'Nihil est neque quos incidunt nulla quis sunt temporibus odio sed ullam nobis similique cum quidem laborum sequi labore nostrum sunt ut inventore nihil laboriosam molestias fuga similique architecto temporibus vitae laudantium dolore soluta voluptatum alias quis et aperiam expedita ex tempore magni autem molestiae porro optio sunt quae aut rerum delectus eum sequi et et voluptatem ducimus beatae cumque vel exercitationem dolorum molestias occaecati rem minus rem quo modi dolores excepturi quas quod ipsa aut fugiat vel est et libero voluptas quas unde eos sequi nostrum dolore dolore explicabo nesciunt sint debitis sit sunt maiores sunt culpa necessitatibus minima facilis autem quibusdam.','1',1,1,'2','2025-06-29 11:29:49','2025-11-06 10:08:28'),(3,'Sequi quaerat non unde blanditiis tempora dolore nihil aut recusandae cupiditate molestiae.',NULL,'Corporis facilis sed voluptatem eos eum sed occaecati aut in ut et neque porro minus perferendis aut sunt similique nihil tenetur et sequi officia possimus laborum maiores sapiente consequuntur neque consequuntur dolore omnis dolor error earum asperiores impedit quam eum rerum provident ipsam aut quis assumenda cumque incidunt ut aut ut est ex nesciunt et aspernatur reprehenderit sint voluptas dolore beatae et ut consequuntur ex molestiae praesentium nihil nostrum fuga iusto cum quam labore cumque voluptates repellendus vel qui atque atque quia.','2',1,1,'1','2025-06-29 11:29:49','2025-08-05 18:35:13'),(4,'Quia laudantium veniam reiciendis quo reiciendis occaecati rerum expedita soluta asperiores.',NULL,'Ut officia enim voluptates sunt vel in ut architecto quidem laboriosam cumque dignissimos cupiditate accusamus voluptates consequatur laborum architecto alias et dignissimos quod explicabo quia temporibus voluptates cupiditate modi eius officiis maxime veniam modi et et at nobis deserunt repellendus alias porro dolorem iure enim nemo natus exercitationem omnis cupiditate dolor vel eum dolor voluptatibus sed excepturi ea nostrum iure sint asperiores eos deserunt ipsum qui cum ea quia architecto et voluptatem hic ut quos iure reiciendis aut unde dicta magnam quia vero voluptatibus quibusdam impedit est omnis distinctio vitae maxime ducimus deleniti repellat temporibus asperiores aut facere sunt labore ipsam illum expedita molestias dicta doloremque pariatur expedita nesciunt laboriosam numquam laborum mollitia dolore exercitationem magnam sed voluptate reiciendis consectetur explicabo quisquam id.','3',1,1,'0','2025-06-29 11:29:49','2025-06-29 11:29:49'),(5,'Ut molestiae dolor est neque eaque est corrupti qui qui pariatur error.',NULL,'Ad repellat consectetur eos omnis earum sed quas omnis qui est ducimus quo voluptatem culpa sed mollitia suscipit id quidem et corrupti sint eaque sed incidunt voluptas quo totam aut aperiam repellat sint quibusdam in quo in harum deserunt optio sed temporibus dolor est adipisci quis fugiat officia similique illum eaque facere est ipsum qui totam blanditiis veritatis excepturi vel adipisci nihil autem perferendis est nesciunt fuga cupiditate accusamus ipsam pariatur qui velit enim et temporibus itaque ducimus exercitationem ut iure omnis sequi numquam dolores nihil dolor odit deserunt repellat earum culpa et hic sit optio quia dolore magnam voluptatem nihil porro minima esse ut distinctio.','4',1,1,'0','2025-06-29 11:29:49','2025-06-29 11:29:49'),(6,'Fuga nam possimus dicta ad iste quaerat architecto cum.',NULL,'Molestiae fugiat minima et eos sequi qui incidunt quae sed voluptatibus adipisci quae nam illo error perferendis praesentium ea asperiores molestias et voluptate perferendis similique blanditiis quod aliquam labore dolores ad atque esse iusto sequi accusamus a et voluptates pariatur reprehenderit omnis minima consequatur nisi hic qui tempore ea voluptatem iure aspernatur quam quam neque et atque aut ipsam ut nostrum qui aut consequatur illo et dolores eos voluptatibus quibusdam eveniet sequi molestias perferendis mollitia consequatur dolores commodi aspernatur qui veniam.','5',1,1,'0','2025-06-29 11:29:50','2025-06-29 11:29:50'),(7,'Possimus rerum architecto sint quia distinctio quia consequatur expedita perspiciatis deserunt.',NULL,'Placeat et qui non omnis neque ut itaque exercitationem est ipsam dolor qui dolorem voluptate omnis modi aut nemo ut sunt consequuntur saepe assumenda quaerat sunt ut minima illum mollitia nesciunt aut similique vel nostrum voluptatem quaerat repellendus et ex blanditiis in dolore consequatur vel quia ea soluta est omnis magni provident nisi nihil aut mollitia dolorem dicta et est et temporibus blanditiis adipisci veniam deleniti quo id et eaque nostrum aut et culpa eius aut ut ut impedit ipsa quos assumenda consequatur et quod labore qui sint consequatur quo at suscipit ducimus maiores fugiat molestiae amet fugit ex.','6',1,1,'0','2025-06-29 11:29:50','2025-06-29 11:29:50'),(8,'Velit hic tempore quae eum nulla quisquam et ut ipsum exercitationem blanditiis cupiditate.',NULL,'Ullam repudiandae consequuntur reiciendis et ex consectetur delectus minima itaque architecto voluptatibus quia possimus perspiciatis ut qui doloribus voluptates harum qui voluptatem ut sed qui consectetur similique a harum soluta illo vero laboriosam est optio iusto quia est aliquam saepe aliquam eaque atque incidunt rem voluptatem inventore et temporibus ullam occaecati sed aspernatur amet esse cum nesciunt provident soluta totam quaerat quam minus.','7',1,1,'0','2025-06-29 11:29:50','2025-06-29 11:29:50'),(9,'Sit corrupti enim autem rerum quis voluptatem cumque iste quisquam amet in tempore.',NULL,'Non id nemo nesciunt molestiae sit id labore temporibus blanditiis rerum sapiente quis nulla ab aut dolorem impedit velit tempore sed exercitationem natus soluta ut eligendi tempora quis aut eos optio quo qui possimus laborum ut ut eveniet est et molestias quam eaque omnis assumenda omnis ea quam consequatur laudantium hic voluptatibus maxime exercitationem qui et rerum consectetur qui minus consequuntur ut provident quas praesentium aut et voluptas corporis dolores vitae nisi quibusdam aliquid fugit dolor sit delectus delectus blanditiis laboriosam voluptas itaque repellat at ipsa facere facere ut ducimus odio placeat et sequi qui rerum maxime excepturi doloremque consequuntur a quod hic velit distinctio aut quos provident nemo eum deleniti illum sed qui et sunt occaecati sequi laborum praesentium aliquid eum deserunt dolorem quo iste deserunt autem non saepe harum atque facilis in voluptate rerum perspiciatis ex eos labore natus.','8',1,1,'0','2025-06-29 11:29:50','2025-06-29 11:29:50'),(10,'Libero aut totam quia magnam non ab.',NULL,'Neque sunt quia tempore quam pariatur voluptatem maxime magnam sint porro voluptatibus aperiam sunt enim iusto perspiciatis sunt occaecati sed delectus ipsa ut ullam quos quia culpa tempore et enim aspernatur suscipit qui minima aut aspernatur beatae quasi qui sit enim fuga laboriosam similique inventore provident corporis quibusdam ut rerum impedit aspernatur velit ipsum ut similique quos aut neque non cupiditate laboriosam voluptatem et eaque provident ut autem qui laboriosam velit consequatur placeat omnis qui reiciendis voluptatem odio dolores vitae error.','9',1,1,'0','2025-06-29 11:29:50','2025-06-29 11:29:50');
/*!40000 ALTER TABLE `blogs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_configurations`
--

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

--
-- Dumping data for table `branch_configurations`
--

LOCK TABLES `branch_configurations` WRITE;
/*!40000 ALTER TABLE `branch_configurations` DISABLE KEYS */;
/*!40000 ALTER TABLE `branch_configurations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_managers`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_managers`
--

LOCK TABLES `branch_managers` WRITE;
/*!40000 ALTER TABLE `branch_managers` DISABLE KEYS */;
INSERT INTO `branch_managers` VALUES (1,2,8,'branch_manager','2025-11-06 06:32:59',NULL,0.00,NULL,NULL,NULL,NULL,1,'2025-11-06 05:32:59','2025-11-06 05:32:59');
/*!40000 ALTER TABLE `branch_managers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branch_workers`
--

DROP TABLE IF EXISTS `branch_workers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branch_workers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `branch_id` bigint unsigned NOT NULL,
  `user_id` bigint unsigned NOT NULL,
  `role` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
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
  CONSTRAINT `branch_workers_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE,
  CONSTRAINT `branch_workers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_workers`
--

LOCK TABLES `branch_workers` WRITE;
/*!40000 ALTER TABLE `branch_workers` DISABLE KEYS */;
INSERT INTO `branch_workers` VALUES (1,3,9,'courier',NULL,NULL,NULL,'2025-11-06 05:40:09',NULL,NULL,NULL,1,'2025-11-06 05:32:59','2025-11-06 05:40:09');
/*!40000 ALTER TABLE `branch_workers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `code` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('HUB','REGIONAL','LOCAL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'LOCAL',
  `is_hub` tinyint(1) NOT NULL DEFAULT '0',
  `parent_branch_id` bigint unsigned DEFAULT NULL,
  `address` text COLLATE utf8mb4_unicode_ci,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `operating_hours` json DEFAULT NULL,
  `capabilities` json DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `status` tinyint unsigned NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `branches_code_unique` (`code`),
  KEY `branches_type_status_index` (`type`,`status`),
  KEY `branches_parent_branch_id_index` (`parent_branch_id`),
  KEY `branches_latitude_longitude_index` (`latitude`,`longitude`),
  CONSTRAINT `branches_parent_branch_id_foreign` FOREIGN KEY (`parent_branch_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=136 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Central Hub','HUB-001','HUB',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"capacity\": \"1000 parcels/day\"}',1,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(2,'Regional Branch','REG-001','REGIONAL',0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"region\": \"North\"}',1,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(3,'Local Branch','LOC-001','LOCAL',0,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"service_area\": \"Downtown\"}',1,'2025-11-06 05:32:59','2025-11-06 05:32:59');
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carrier_services`
--

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

--
-- Dumping data for table `carrier_services`
--

LOCK TABLES `carrier_services` WRITE;
/*!40000 ALTER TABLE `carrier_services` DISABLE KEYS */;
/*!40000 ALTER TABLE `carrier_services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carriers`
--

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

--
-- Dumping data for table `carriers`
--

LOCK TABLES `carriers` WRITE;
/*!40000 ALTER TABLE `carriers` DISABLE KEYS */;
/*!40000 ALTER TABLE `carriers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_office_days`
--

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

--
-- Dumping data for table `cash_office_days`
--

LOCK TABLES `cash_office_days` WRITE;
/*!40000 ALTER TABLE `cash_office_days` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_office_days` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cash_received_from_deliverymen`
--

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

--
-- Dumping data for table `cash_received_from_deliverymen`
--

LOCK TABLES `cash_received_from_deliverymen` WRITE;
/*!40000 ALTER TABLE `cash_received_from_deliverymen` DISABLE KEYS */;
/*!40000 ALTER TABLE `cash_received_from_deliverymen` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categorys`
--

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

--
-- Dumping data for table `categorys`
--

LOCK TABLES `categorys` WRITE;
/*!40000 ALTER TABLE `categorys` DISABLE KEYS */;
/*!40000 ALTER TABLE `categorys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `charge_lines`
--

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

--
-- Dumping data for table `charge_lines`
--

LOCK TABLES `charge_lines` WRITE;
/*!40000 ALTER TABLE `charge_lines` DISABLE KEYS */;
/*!40000 ALTER TABLE `charge_lines` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `claims`
--

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

--
-- Dumping data for table `claims`
--

LOCK TABLES `claims` WRITE;
/*!40000 ALTER TABLE `claims` DISABLE KEYS */;
/*!40000 ALTER TABLE `claims` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `clients`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `clients`
--

LOCK TABLES `clients` WRITE;
/*!40000 ALTER TABLE `clients` DISABLE KEYS */;
INSERT INTO `clients` VALUES (1,3,'Acme Logistics','active','{\"registration_number\": \"ACM123456\"}','2025-11-06 05:32:59','2025-11-06 05:32:59');
/*!40000 ALTER TABLE `clients` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cod_receipts`
--

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

--
-- Dumping data for table `cod_receipts`
--

LOCK TABLES `cod_receipts` WRITE;
/*!40000 ALTER TABLE `cod_receipts` DISABLE KEYS */;
/*!40000 ALTER TABLE `cod_receipts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commodities`
--

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

--
-- Dumping data for table `commodities`
--

LOCK TABLES `commodities` WRITE;
/*!40000 ALTER TABLE `commodities` DISABLE KEYS */;
/*!40000 ALTER TABLE `commodities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `configs`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `configs`
--

LOCK TABLES `configs` WRITE;
/*!40000 ALTER TABLE `configs` DISABLE KEYS */;
INSERT INTO `configs` VALUES (1,'fragile_liquid_status','1','2025-06-29 11:29:45','2025-06-29 11:29:45'),(2,'fragile_liquid_charge','20','2025-06-29 11:29:45','2025-06-29 11:29:45'),(3,'same_day','1','2025-06-29 11:29:45','2025-06-29 11:29:45'),(4,'next_day','1','2025-06-29 11:29:45','2025-06-29 11:29:45'),(5,'sub_city','1','2025-06-29 11:29:45','2025-06-29 11:29:45'),(6,'outside_City','1','2025-06-29 11:29:45','2025-06-29 11:29:45');
/*!40000 ALTER TABLE `configs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contracts`
--

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
  `sla_json` json DEFAULT NULL,
  `status` enum('active','suspended','ended') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `contracts_customer_id_foreign` (`customer_id`),
  KEY `contracts_rate_card_id_foreign` (`rate_card_id`),
  CONSTRAINT `contracts_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `contracts_rate_card_id_foreign` FOREIGN KEY (`rate_card_id`) REFERENCES `rate_cards` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contracts`
--

LOCK TABLES `contracts` WRITE;
/*!40000 ALTER TABLE `contracts` DISABLE KEYS */;
/*!40000 ALTER TABLE `contracts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `courier_statements`
--

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

--
-- Dumping data for table `courier_statements`
--

LOCK TABLES `courier_statements` WRITE;
/*!40000 ALTER TABLE `courier_statements` DISABLE KEYS */;
/*!40000 ALTER TABLE `courier_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `currencies`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=142 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `currencies`
--

LOCK TABLES `currencies` WRITE;
/*!40000 ALTER TABLE `currencies` DISABLE KEYS */;
INSERT INTO `currencies` VALUES (2,'America','Dollars','$','USD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(3,'Afghanistan','Afghanis','؋','AF',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(4,'Argentina','Pesos','$','ARS',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(5,'Aruba','Guilders','ƒ','AWG',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(6,'Australia','Dollars','$','AUD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(7,'Azerbaijan','New Manats','ман','AZ',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(8,'Bahamas','Dollars','$','BSD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(9,'Barbados','Dollars','$','BBD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(10,'Belarus','Rubles','p.','BYR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(11,'Belgium','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(12,'Beliz','Dollars','BZ$','BZD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(13,'Bermuda','Dollars','$','BMD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(14,'Bolivia','Bolivianos','$b','BOB',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(15,'Bosnia and Herzegovina','Convertible Marka','KM','BAM',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(16,'Botswana','Pula\'s','P','BWP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(17,'Bulgaria','Leva','лв','BG',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(18,'Brazil','Reais','R$','BRL',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(19,'Britain [United Kingdom]','Pounds','£','GBP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(20,'Brunei Darussalam','Dollars','$','BND',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(21,'Cambodia','Riels','៛','KHR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(22,'Canada','Dollars','$','CAD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(23,'Cayman Islands','Dollars','$','KYD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(24,'Chile','Pesos','$','CLP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(25,'China','Yuan Renminbi','¥','CNY',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(26,'Colombia','Pesos','$','COP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(27,'Costa Rica','Colón','₡','CRC',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(28,'Croatia','Kuna','kn','HRK',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(29,'Cuba','Pesos','₱','CUP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(30,'Cyprus','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(31,'Czech Republic','Koruny','Kč','CZK',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(32,'Denmark','Kroner','kr','DKK',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(33,'Dominican Republic','Pesos','RD$','DOP ',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(34,'East Caribbean','Dollars','$','XCD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(35,'Egypt','Pounds','£','EGP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(36,'El Salvador','Colones','$','SVC',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(37,'England [United Kingdom]','Pounds','£','GBP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(38,'Euro','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(39,'Falkland Islands','Pounds','£','FKP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(40,'Fiji','Dollars','$','FJD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(41,'France','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(42,'Ghana','Cedis','¢','GHS',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(43,'Gibraltar','Pounds','£','GIP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(44,'Greece','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(45,'Guatemala','Quetzales','Q','GTQ',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(46,'Guernsey','Pounds','£','GGP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(47,'Guyana','Dollars','$','GYD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(48,'Holland [Netherlands]','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(49,'Honduras','Lempiras','L','HNL',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(50,'Hong Kong','Dollars','$','HKD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(51,'Hungary','Forint','Ft','HUF',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(52,'Iceland','Kronur','kr','ISK',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(53,'India','Rupees','₹','INR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(54,'Indonesia','Rupiahs','Rp','IDR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(55,'Iran','Rials','﷼','IRR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(56,'Ireland','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(57,'Isle of Man','Pounds','£','IMP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(58,'Israel','New Shekels','₪','ILS',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(59,'Italy','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(60,'Jamaica','Dollars','J$','JMD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(61,'Japan','Yen','¥','JPY',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(62,'Jersey','Pounds','£','JEP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(63,'Kazakhstan','Tenge','лв','KZT',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(64,'Korea [North]','Won','₩','KPW',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(65,'Korea [South]','Won','₩','KRW',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(66,'Kyrgyzstan','Soms','лв','KGS',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(67,'Laos','Kips','₭','LAK',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(68,'Latvia','Lati','Ls','LVL',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(69,'Lebanon','Pounds','£','LBP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(70,'Liberia','Dollars','$','LRD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(71,'Liechtenstein','Switzerland Francs','CHF','CHF',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(72,'Lithuania','Litai','Lt','LTL',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(73,'Luxembourg','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(74,'Macedonia','Denars','ден','MKD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(75,'Malaysia','Ringgits','RM','MYR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(76,'Malta','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(77,'Mauritius','Rupees','₨','MUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(78,'Mexico','Pesos','$','MXN',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(79,'Mongolia','Tugriks','₮','MNT',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(80,'Mozambique','Meticais','MT','MZ',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(81,'Namibia','Dollars','$','NAD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(82,'Nepal','Rupees','₨','NPR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(83,'Netherlands Antilles','Guilders','ƒ','ANG',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(84,'Netherlands','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(85,'New Zealand','Dollars','$','NZD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(86,'Nicaragua','Cordobas','C$','NIO',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(87,'Nigeria','Nairas','₦','NGN',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(88,'North Korea','Won','₩','KPW',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(89,'Norway','Krone','kr','NOK',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(90,'Oman','Rials','﷼','OMR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(91,'Pakistan','Rupees','₨','PKR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(92,'Panama','Balboa','B/.','PAB',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(93,'Paraguay','Guarani','Gs','PYG',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(94,'Peru','Nuevos Soles','S/.','PE',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(95,'Philippines','Pesos','Php','PHP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(96,'Poland','Zlotych','zł','PL',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(97,'Qatar','Rials','﷼','QAR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(98,'Romania','New Lei','lei','RO',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(99,'Russia','Rubles','руб','RUB',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(100,'Saint Helena','Pounds','£','SHP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(101,'Saudi Arabia','Riyals','﷼','SAR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(102,'Serbia','Dinars','Дин.','RSD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(103,'Seychelles','Rupees','₨','SCR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(104,'Singapore','Dollars','$','SGD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(105,'Slovenia','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(106,'Solomon Islands','Dollars','$','SBD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(107,'Somalia','Shillings','S','SOS',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(108,'South Africa','Rand','R','ZAR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(109,'South Korea','Won','₩','KRW',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(110,'Spain','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(111,'Sri Lanka','Rupees','₨','LKR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(112,'Sweden','Kronor','kr','SEK',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(113,'Switzerland','Francs','CHF','CHF',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(114,'Suriname','Dollars','$','SRD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(115,'Syria','Pounds','£','SYP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(116,'Taiwan','New Dollars','NT$','TWD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(117,'Thailand','Baht','฿','THB',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(118,'Trinidad and Tobago','Dollars','TT$','TTD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(119,'Turkey','Lira','TL','TRY',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(120,'Turkey','Liras','£','TRL',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(121,'Tuvalu','Dollars','$','TVD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(122,'Ukraine','Hryvnia','₴','UAH',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(123,'United Kingdom','Pounds','£','GBP',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(124,'United States of America','Dollars','$','USD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(125,'Uruguay','Pesos','$U','UYU',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(126,'Uzbekistan','Sums','лв','UZS',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(127,'Vatican City','Euro','€','EUR',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(128,'Venezuela','Bolivares Fuertes','Bs','VEF',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(129,'Vietnam','Dong','₫','VND',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(130,'Yemen','Rials','﷼','YER',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(131,'Zimbabwe','Zimbabwe Dollars','Z$','ZWD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(132,'Iraq','Iraqi dinar','د.ع','IQD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(133,'Kenya','Kenyan shilling','KSh','KES',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(134,'Bangladesh','Taka','৳','BDT',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(135,'Algerie','Algerian dinar','د.ج','DZD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(136,'United Arab Emirates','United Arab Emirates dirham','د.إ','AED',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(137,'Uganda','Uganda shillings','USh','UGX',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(138,'Tanzania','Tanzanian shilling','TSh','TZS',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(139,'Angola','Kwanza','Kz','AOA',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(140,'Kuwait','Kuwaiti dinar','KD','KWD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09'),(141,'Bahrain','Bahraini dinar','BD','BHD',NULL,NULL,1,'2022-12-14 07:30:09','2022-12-14 07:30:09');
/*!40000 ALTER TABLE `currencies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

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
  KEY `customers_account_manager_id_foreign` (`account_manager_id`),
  CONSTRAINT `customers_account_manager_id_foreign` FOREIGN KEY (`account_manager_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customs_docs`
--

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

--
-- Dumping data for table `customs_docs`
--

LOCK TABLES `customs_docs` WRITE;
/*!40000 ALTER TABLE `customs_docs` DISABLE KEYS */;
/*!40000 ALTER TABLE `customs_docs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dangerous_goods`
--

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

--
-- Dumping data for table `dangerous_goods`
--

LOCK TABLES `dangerous_goods` WRITE;
/*!40000 ALTER TABLE `dangerous_goods` DISABLE KEYS */;
/*!40000 ALTER TABLE `dangerous_goods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_charges`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_charges`
--

LOCK TABLES `delivery_charges` WRITE;
/*!40000 ALTER TABLE `delivery_charges` DISABLE KEYS */;
INSERT INTO `delivery_charges` VALUES (1,1,1,50.00,60.00,70.00,80.00,1,1,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(2,1,2,90.00,100.00,110.00,120.00,2,1,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(3,1,3,130.00,140.00,150.00,160.00,3,1,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(4,1,4,170.00,180.00,190.00,200.00,4,1,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(5,1,5,210.00,220.00,230.00,240.00,5,1,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(6,1,6,250.00,260.00,270.00,280.00,6,1,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(7,1,7,290.00,300.00,310.00,320.00,7,1,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(8,1,8,340.00,350.00,360.00,370.00,8,1,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(9,1,9,380.00,390.00,400.00,410.00,9,1,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(10,1,10,420.00,430.00,440.00,450.00,10,1,'2025-06-29 11:29:44','2025-06-29 11:29:44');
/*!40000 ALTER TABLE `delivery_charges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `delivery_man`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `delivery_man`
--

LOCK TABLES `delivery_man` WRITE;
/*!40000 ALTER TABLE `delivery_man` DISABLE KEYS */;
INSERT INTO `delivery_man` VALUES (1,3,1,NULL,NULL,NULL,NULL,30.00,20.00,10.00,0.00,0.00,1,'2025-06-29 11:29:43','2025-06-29 11:29:43');
/*!40000 ALTER TABLE `delivery_man` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deliverycategories`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `deliverycategories`
--

LOCK TABLES `deliverycategories` WRITE;
/*!40000 ALTER TABLE `deliverycategories` DISABLE KEYS */;
INSERT INTO `deliverycategories` VALUES (1,'KG',1,1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(2,'Mobile',1,2,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(3,'Laptop',1,3,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(4,'Tabs',1,4,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(5,'Gaming Kybord',1,5,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(6,'Cosmetices',1,6,'2025-06-29 11:29:44','2025-06-29 11:29:44');
/*!40000 ALTER TABLE `deliverycategories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `deliveryman_statements`
--

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

--
-- Dumping data for table `deliveryman_statements`
--

LOCK TABLES `deliveryman_statements` WRITE;
/*!40000 ALTER TABLE `deliveryman_statements` DISABLE KEYS */;
/*!40000 ALTER TABLE `deliveryman_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `departments`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `departments`
--

LOCK TABLES `departments` WRITE;
/*!40000 ALTER TABLE `departments` DISABLE KEYS */;
INSERT INTO `departments` VALUES (1,'General Management',1,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(2,'Marketing',1,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(3,'Operations',1,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(4,'Finance',1,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(5,'Sales',1,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(6,'Human Resource',1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(7,'Purchase',1,'2025-06-29 11:29:43','2025-06-29 11:29:43');
/*!40000 ALTER TABLE `departments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `designations`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `designations`
--

LOCK TABLES `designations` WRITE;
/*!40000 ALTER TABLE `designations` DISABLE KEYS */;
INSERT INTO `designations` VALUES (1,'Chief Executive Officer (CEO)',1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(2,'Chief Operating Officer (COO)',1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(3,'Chief Financial Officer (CFO)',1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(4,'Chief Technology Officer (CTO)',1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(5,'Chief Legal Officer (CLO)',1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(6,'Chief Marketing Officer (CMO)',1,'2025-06-29 11:29:43','2025-06-29 11:29:43');
/*!40000 ALTER TABLE `designations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `devices`
--

DROP TABLE IF EXISTS `devices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `devices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `platform` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `device_uuid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `push_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `devices_device_uuid_unique` (`device_uuid`),
  KEY `devices_user_id_foreign` (`user_id`),
  CONSTRAINT `devices_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=20 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `devices`
--

LOCK TABLES `devices` WRITE;
/*!40000 ALTER TABLE `devices` DISABLE KEYS */;
/*!40000 ALTER TABLE `devices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `dps_screenings`
--

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

--
-- Dumping data for table `dps_screenings`
--

LOCK TABLES `dps_screenings` WRITE;
/*!40000 ALTER TABLE `dps_screenings` DISABLE KEYS */;
/*!40000 ALTER TABLE `dps_screenings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `driver_locations`
--

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

--
-- Dumping data for table `driver_locations`
--

LOCK TABLES `driver_locations` WRITE;
/*!40000 ALTER TABLE `driver_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `driver_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ecmrs`
--

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

--
-- Dumping data for table `ecmrs`
--

LOCK TABLES `ecmrs` WRITE;
/*!40000 ALTER TABLE `ecmrs` DISABLE KEYS */;
/*!40000 ALTER TABLE `ecmrs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `edi_providers`
--

DROP TABLE IF EXISTS `edi_providers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `edi_providers` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('airline','broker','mock') COLLATE utf8mb4_unicode_ci NOT NULL,
  `config` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `edi_providers`
--

LOCK TABLES `edi_providers` WRITE;
/*!40000 ALTER TABLE `edi_providers` DISABLE KEYS */;
/*!40000 ALTER TABLE `edi_providers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `epods`
--

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

--
-- Dumping data for table `epods`
--

LOCK TABLES `epods` WRITE;
/*!40000 ALTER TABLE `epods` DISABLE KEYS */;
/*!40000 ALTER TABLE `epods` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

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

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

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

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `faqs`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `faqs`
--

LOCK TABLES `faqs` WRITE;
/*!40000 ALTER TABLE `faqs` DISABLE KEYS */;
INSERT INTO `faqs` VALUES (1,'What is wecourier Delivery?','Adipisci asperiores nulla autem voluptas quis sed blanditiis rerum cum culpa nihil et voluptas eveniet similique praesentium sit voluptatem pariatur mollitia qui ea et dolorem officiis nostrum distinctio cumque sequi inventore sed ipsum et.','1',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(2,'How do I contact you?','Reiciendis sequi dolorum rerum a laboriosam labore inventore suscipit deserunt nulla error earum et omnis porro ullam repellendus est enim quo sit eveniet perferendis vitae ut.','2',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(3,'How can a merchant track their parcel delivery?','Libero beatae nemo iste minima maiores ea dignissimos dolorum porro sed ea quaerat dicta expedita eos vel mollitia sapiente libero dignissimos et voluptatem tempore eveniet pariatur sunt expedita omnis vel et modi quia dolor fuga.','3',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(4,'How do I send a product/ courier via wecourier Delivery?','Omnis consequuntur fuga qui odio eligendi asperiores ut dolor perspiciatis voluptatem quia molestiae et dolores ipsa et similique id consequuntur nobis cum aliquam quia sunt totam.','4',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(5,'I want to hold a parcel for more than 3 days before home delivery. Is it possible?','Necessitatibus numquam laboriosam cumque architecto omnis hic iste enim consequatur omnis pariatur tenetur quasi cupiditate quia et quidem quod aut rerum reiciendis rerum dolorem ratione labore voluptas atque est.','5',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(6,'Can you do product exchange from customers?','Voluptatem voluptatem similique itaque nesciunt nobis illo eos molestias voluptas repellendus impedit molestiae soluta rerum est perferendis dolores nihil officiis ut dolore distinctio aperiam hic voluptates.','6',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(7,'Can you deliver to addresses inside Cantonment or other restricted areas?','Nulla maxime voluptatem voluptates atque ratione facilis sint earum quia aut nemo sint iusto nostrum molestiae sunt nam enim consequatur voluptas voluptatum id aliquam esse repudiandae amet rem id minima nulla consectetur.','7',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(8,'I do not have a Facebook page, can I register as a merchant?','Sit odio reprehenderit dolorem qui animi aliquid et illo explicabo voluptatibus libero qui aut distinctio rem provident velit perspiciatis hic sit fuga nihil corrupti et voluptatem error rerum similique eum ut et.','8',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(9,'What kind of products does wecourier deliver?','Deleniti eius sint tempora omnis vero id nulla voluptatem voluptas laborum vel provident assumenda sequi quae voluptatem officiis nobis rerum perspiciatis atque et nihil voluptatem cum id deserunt commodi voluptas et quae rerum et sed.','9',1,'2025-06-29 11:29:49','2025-06-29 11:29:49');
/*!40000 ALTER TABLE `faqs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `frauds`
--

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

--
-- Dumping data for table `frauds`
--

LOCK TABLES `frauds` WRITE;
/*!40000 ALTER TABLE `frauds` DISABLE KEYS */;
/*!40000 ALTER TABLE `frauds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fuels`
--

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

--
-- Dumping data for table `fuels`
--

LOCK TABLES `fuels` WRITE;
/*!40000 ALTER TABLE `fuels` DISABLE KEYS */;
/*!40000 ALTER TABLE `fuels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fund_transfers`
--

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

--
-- Dumping data for table `fund_transfers`
--

LOCK TABLES `fund_transfers` WRITE;
/*!40000 ALTER TABLE `fund_transfers` DISABLE KEYS */;
/*!40000 ALTER TABLE `fund_transfers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `fx_rates`
--

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

--
-- Dumping data for table `fx_rates`
--

LOCK TABLES `fx_rates` WRITE;
/*!40000 ALTER TABLE `fx_rates` DISABLE KEYS */;
/*!40000 ALTER TABLE `fx_rates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `general_settings`
--

DROP TABLE IF EXISTS `general_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `general_settings` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `phone` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` longtext COLLATE utf8mb4_unicode_ci,
  `currency` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
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
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_settings`
--

LOCK TABLES `general_settings` WRITE;
/*!40000 ALTER TABLE `general_settings` DISABLE KEYS */;
INSERT INTO `general_settings` VALUES (1,'Baraka','0200903222','info@sanaa.com','Nasser Road Kampala','$','Copyright © All rights reserved. Development by Sanaa Co.',8,32,9,'1','BA','BA','#7e2995','#fcf7f8','1.4','2025-06-29 11:29:47','2025-07-30 06:23:51');
/*!40000 ALTER TABLE `general_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `google_map_settings`
--

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

--
-- Dumping data for table `google_map_settings`
--

LOCK TABLES `google_map_settings` WRITE;
/*!40000 ALTER TABLE `google_map_settings` DISABLE KEYS */;
/*!40000 ALTER TABLE `google_map_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hs_codes`
--

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

--
-- Dumping data for table `hs_codes`
--

LOCK TABLES `hs_codes` WRITE;
/*!40000 ALTER TABLE `hs_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `hs_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hub_incharges`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hub_incharges`
--

LOCK TABLES `hub_incharges` WRITE;
/*!40000 ALTER TABLE `hub_incharges` DISABLE KEYS */;
INSERT INTO `hub_incharges` VALUES (1,2,1,1,'2025-06-29 11:29:43','2025-06-29 11:29:43');
/*!40000 ALTER TABLE `hub_incharges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hub_payments`
--

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

--
-- Dumping data for table `hub_payments`
--

LOCK TABLES `hub_payments` WRITE;
/*!40000 ALTER TABLE `hub_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `hub_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hub_statements`
--

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

--
-- Dumping data for table `hub_statements`
--

LOCK TABLES `hub_statements` WRITE;
/*!40000 ALTER TABLE `hub_statements` DISABLE KEYS */;
/*!40000 ALTER TABLE `hub_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `hubs`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `hubs`
--

LOCK TABLES `hubs` WRITE;
/*!40000 ALTER TABLE `hubs` DISABLE KEYS */;
INSERT INTO `hubs` VALUES (1,'','regional','Kivu',NULL,NULL,NULL,'01000000001',NULL,NULL,0,1,1,NULL,NULL,'08:00:00','18:00:00','North Kivu','','',NULL,NULL,0.00,0.00,0.00,NULL,NULL,'pending',1000,0,0.00,1,'2025-06-29 11:29:42','2025-06-29 12:33:46',NULL);
/*!40000 ALTER TABLE `hubs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `ics2_filings`
--

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

--
-- Dumping data for table `ics2_filings`
--

LOCK TABLES `ics2_filings` WRITE;
/*!40000 ALTER TABLE `ics2_filings` DISABLE KEYS */;
/*!40000 ALTER TABLE `ics2_filings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `impersonation_logs`
--

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

--
-- Dumping data for table `impersonation_logs`
--

LOCK TABLES `impersonation_logs` WRITE;
/*!40000 ALTER TABLE `impersonation_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `impersonation_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `incomes`
--

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

--
-- Dumping data for table `incomes`
--

LOCK TABLES `incomes` WRITE;
/*!40000 ALTER TABLE `incomes` DISABLE KEYS */;
/*!40000 ALTER TABLE `incomes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoice_parcels`
--

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

--
-- Dumping data for table `invoice_parcels`
--

LOCK TABLES `invoice_parcels` WRITE;
/*!40000 ALTER TABLE `invoice_parcels` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoice_parcels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `invoices`
--

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

--
-- Dumping data for table `invoices`
--

LOCK TABLES `invoices` WRITE;
/*!40000 ALTER TABLE `invoices` DISABLE KEYS */;
/*!40000 ALTER TABLE `invoices` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `kyc_records`
--

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

--
-- Dumping data for table `kyc_records`
--

LOCK TABLES `kyc_records` WRITE;
/*!40000 ALTER TABLE `kyc_records` DISABLE KEYS */;
/*!40000 ALTER TABLE `kyc_records` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `lanes`
--

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

--
-- Dumping data for table `lanes`
--

LOCK TABLES `lanes` WRITE;
/*!40000 ALTER TABLE `lanes` DISABLE KEYS */;
/*!40000 ALTER TABLE `lanes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `maintenances`
--

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

--
-- Dumping data for table `maintenances`
--

LOCK TABLES `maintenances` WRITE;
/*!40000 ALTER TABLE `maintenances` DISABLE KEYS */;
/*!40000 ALTER TABLE `maintenances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `manifests`
--

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

--
-- Dumping data for table `manifests`
--

LOCK TABLES `manifests` WRITE;
/*!40000 ALTER TABLE `manifests` DISABLE KEYS */;
/*!40000 ALTER TABLE `manifests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_delivery_charges`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_delivery_charges`
--

LOCK TABLES `merchant_delivery_charges` WRITE;
/*!40000 ALTER TABLE `merchant_delivery_charges` DISABLE KEYS */;
INSERT INTO `merchant_delivery_charges` VALUES (1,1,1,1,1,50.00,60.00,70.00,80.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(2,1,2,2,1,90.00,100.00,110.00,120.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(3,1,3,3,1,130.00,140.00,150.00,160.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(4,1,4,4,1,170.00,180.00,190.00,200.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(5,1,5,5,1,210.00,220.00,230.00,240.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(6,1,6,6,1,250.00,260.00,270.00,280.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(7,1,7,7,1,290.00,300.00,310.00,320.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(8,1,8,8,1,340.00,350.00,360.00,370.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(9,1,9,9,1,380.00,390.00,400.00,410.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(10,1,10,10,1,420.00,430.00,440.00,450.00,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(11,2,1,1,1,50.00,60.00,70.00,80.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(12,2,2,2,1,90.00,100.00,110.00,120.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(13,2,3,3,1,130.00,140.00,150.00,160.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(14,2,4,4,1,170.00,180.00,190.00,200.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(15,2,5,5,1,210.00,220.00,230.00,240.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(16,2,6,6,1,250.00,260.00,270.00,280.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(17,2,7,7,1,290.00,300.00,310.00,320.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(18,2,8,8,1,340.00,350.00,360.00,370.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(19,2,9,9,1,380.00,390.00,400.00,410.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(20,2,10,10,1,420.00,430.00,440.00,450.00,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(21,3,1,1,1,50.00,60.00,70.00,80.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(22,3,2,2,1,90.00,100.00,110.00,120.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(23,3,3,3,1,130.00,140.00,150.00,160.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(24,3,4,4,1,170.00,180.00,190.00,200.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(25,3,5,5,1,210.00,220.00,230.00,240.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(26,3,6,6,1,250.00,260.00,270.00,280.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(27,3,7,7,1,290.00,300.00,310.00,320.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(28,3,8,8,1,340.00,350.00,360.00,370.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(29,3,9,9,1,380.00,390.00,400.00,410.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(30,3,10,10,1,420.00,430.00,440.00,450.00,1,'2025-07-05 11:42:23','2025-07-05 11:42:23');
/*!40000 ALTER TABLE `merchant_delivery_charges` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_online_payment_receiveds`
--

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

--
-- Dumping data for table `merchant_online_payment_receiveds`
--

LOCK TABLES `merchant_online_payment_receiveds` WRITE;
/*!40000 ALTER TABLE `merchant_online_payment_receiveds` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_online_payment_receiveds` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_online_payments`
--

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

--
-- Dumping data for table `merchant_online_payments`
--

LOCK TABLES `merchant_online_payments` WRITE;
/*!40000 ALTER TABLE `merchant_online_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_online_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_payments`
--

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

--
-- Dumping data for table `merchant_payments`
--

LOCK TABLES `merchant_payments` WRITE;
/*!40000 ALTER TABLE `merchant_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_settings`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_settings`
--

LOCK TABLES `merchant_settings` WRITE;
/*!40000 ALTER TABLE `merchant_settings` DISABLE KEYS */;
INSERT INTO `merchant_settings` VALUES (1,1,'stripe_publishable_key','your publishable key','2025-06-29 11:29:48','2025-06-29 11:29:48'),(2,1,'stripe_secret_key','your secret key','2025-06-29 11:29:48','2025-06-29 11:29:48'),(3,1,'stripe_status','1','2025-06-29 11:29:48','2025-06-29 11:29:48'),(4,1,'sslcommerz_store_id','your store id ','2025-06-29 11:29:48','2025-06-29 11:29:48'),(5,1,'sslcommerz_store_password','your store password','2025-06-29 11:29:48','2025-06-29 11:29:48'),(6,1,'sslcommerz_testmode','1','2025-06-29 11:29:48','2025-06-29 11:29:48'),(7,1,'sslcommerz_status','1','2025-06-29 11:29:48','2025-06-29 11:29:48'),(8,1,'paypal_client_id','your client id','2025-06-29 11:29:48','2025-06-29 11:29:48'),(9,1,'paypal_client_secret','your client secret','2025-06-29 11:29:48','2025-06-29 11:29:48'),(10,1,'paypal_mode','sendbox','2025-06-29 11:29:48','2025-06-29 11:29:48'),(11,1,'paypal_status','1','2025-06-29 11:29:48','2025-06-29 11:29:48'),(12,1,'razorpay_key','','2025-06-29 11:29:48','2025-06-29 11:29:48'),(13,1,'razorpay_secret','','2025-06-29 11:29:48','2025-06-29 11:29:48'),(14,1,'razorpay_status','1','2025-06-29 11:29:48','2025-06-29 11:29:48'),(15,1,'skrill_merchant_email','demoqco@sun-fish.com','2025-06-29 11:29:48','2025-06-29 11:29:48'),(16,1,'skrill_status','1','2025-06-29 11:29:48','2025-06-29 11:29:48'),(17,1,'bkash_app_id','application id','2025-06-29 11:29:48','2025-06-29 11:29:48'),(18,1,'bkash_app_secret','application secret key','2025-06-29 11:29:48','2025-06-29 11:29:48'),(19,1,'bkash_username','username','2025-06-29 11:29:48','2025-06-29 11:29:48'),(20,1,'bkash_password','password','2025-06-29 11:29:48','2025-06-29 11:29:48'),(21,1,'bkash_test_mode','1','2025-06-29 11:29:48','2025-06-29 11:29:48'),(22,1,'bkash_status','1','2025-06-29 11:29:48','2025-06-29 11:29:48'),(23,1,'aamarpay_store_id','aamarypay','2025-06-29 11:29:48','2025-06-29 11:29:48'),(24,1,'aamarpay_signature_key','28c78bb1f45112f5d40b956fe104645a','2025-06-29 11:29:48','2025-06-29 11:29:48'),(25,1,'aamarpay_sendbox_mode','1','2025-06-29 11:29:48','2025-06-29 11:29:48'),(26,1,'aamarpay_status','1','2025-06-29 11:29:48','2025-06-29 11:29:48');
/*!40000 ALTER TABLE `merchant_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_shops`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchant_shops`
--

LOCK TABLES `merchant_shops` WRITE;
/*!40000 ALTER TABLE `merchant_shops` DISABLE KEYS */;
INSERT INTO `merchant_shops` VALUES (1,1,'Shop 1','+88013000000','Wemaxdevs,Dhaka',NULL,NULL,1,1,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(2,1,'Shop 2','+88013000000','Wemaxdevs,Dhaka',NULL,NULL,1,0,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(3,1,'Shop 3','+88013000000','Wemaxdevs,Dhaka',NULL,NULL,1,0,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(4,1,'Shop 4','+88013000000','Wemaxdevs,Dhaka',NULL,NULL,1,0,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(5,1,'Shop 5','+88013000000','Wemaxdevs,Dhaka',NULL,NULL,1,0,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(6,2,'Austin Chaney','256702568978','Irure aliquid porro',NULL,NULL,1,1,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(7,3,'India Cote','2567056567989','Rem porro in delenit',NULL,NULL,1,1,'2025-07-05 11:42:23','2025-07-05 11:42:23');
/*!40000 ALTER TABLE `merchant_shops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchant_statements`
--

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

--
-- Dumping data for table `merchant_statements`
--

LOCK TABLES `merchant_statements` WRITE;
/*!40000 ALTER TABLE `merchant_statements` DISABLE KEYS */;
/*!40000 ALTER TABLE `merchant_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `merchants`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `merchants`
--

LOCK TABLES `merchants` WRITE;
/*!40000 ALTER TABLE `merchants` DISABLE KEYS */;
INSERT INTO `merchants` VALUES (1,4,'WemaxDevs','251111',0.00,0.00,0.00,0.00,'{\"inside_city\":\"1\",\"sub_city\":\"2\",\"outside_city\":\"3\"}',4,5,'2',1,'Dhaka',0,100.00,NULL,NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(2,5,'Austin Chaney','727666',5.00,5.00,0.00,91.00,'{\"inside_city\":\"65\",\"sub_city\":\"40\",\"outside_city\":\"71\"}',NULL,NULL,'19',1,'Irure aliquid porro',0,67.00,'Shannon Barnett','61','2025-07-05 11:38:06','2025-07-05 11:38:06'),(3,6,'India Cote','829517',0.00,0.00,0.00,0.00,'{\"inside_city\":\"0\",\"sub_city\":\"0\",\"outside_city\":\"0\"}',NULL,NULL,'2',1,'Rem porro in delenit',0,100.00,NULL,NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23');
/*!40000 ALTER TABLE `merchants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=145 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_09_12_000000_create_hubs_table',1),(2,'2014_09_12_000000_create_uploads_table',1),(3,'2014_10_10_040240_create_roles_table',1),(4,'2014_10_11_000000_create_deliverycategories_table',1),(5,'2014_10_11_000000_create_departments_table',1),(6,'2014_10_11_000000_create_designations_table',1),(7,'2014_10_11_000000_create_packagings_table',1),(8,'2014_10_11_000000_create_users_table',1),(9,'2014_10_11_000001_create_merchants_table',1),(10,'2014_10_12_100000_create_password_resets_table',1),(11,'2019_08_19_000000_create_failed_jobs_table',1),(12,'2019_12_14_000001_create_personal_access_tokens_table',1),(13,'2022_02_15_122629_create_push_notifications_table',1),(14,'2022_03_20_060621_create_categories_table',1),(15,'2022_03_24_042455_create_activity_log_table',1),(16,'2022_03_24_042456_add_event_column_to_activity_log_table',1),(17,'2022_03_24_042457_add_batch_uuid_column_to_activity_log_table',1),(18,'2022_04_04_142330_create_delivery_man_table',1),(19,'2022_04_04_142330_create_hub_incharges_table',1),(20,'2022_04_04_142330_create_parcels_table',1),(21,'2022_04_09_101126_create_delivery_charges_table',1),(22,'2022_04_09_101126_create_merchant_delivery_charges_table',1),(23,'2022_04_10_050353_create_merchant_shops_table',1),(24,'2022_04_13_034848_create_merchant_payments_table',1),(25,'2022_04_13_054047_create_accounts_table',1),(26,'2022_04_14_045839_create_fund_transfers_table',1),(27,'2022_04_14_063624_create_payments_table',1),(28,'2022_04_17_061311_create_payment_accounts_table',1),(29,'2022_04_19_035758_create_configs_table',1),(30,'2022_04_20_053011_create_sessions_table',1),(31,'2022_04_23_032024_create_permissions_table',1),(32,'2022_04_24_045606_create_parcel_logs_table',2),(33,'2022_04_27_123343_create_parcel_events_table',2),(34,'2022_05_14_112714_create_account_heads_table',2),(35,'2022_05_14_112715_create_expenses_table',2),(36,'2022_05_14_112717_create_deliveryman_statements_table',2),(37,'2022_05_15_102801_create_merchant_statements_table',2),(38,'2022_05_17_124213_create_incomes_table',2),(39,'2022_05_17_132716_create_courier_statements_table',2),(40,'2022_05_18_113259_create_to_dos_table',2),(41,'2022_05_23_111055_create_supports_table',2),(42,'2022_05_23_122723_create_sms_send_settings_table',2),(43,'2022_05_23_122723_create_sms_settings_table',2),(44,'2022_05_24_141546_create_vat_statements_table',2),(45,'2022_05_26_093710_create_bank_transactions_table',2),(46,'2022_05_31_094551_create_general_settings_table',2),(47,'2022_05_31_094551_create_notification_settings_table',2),(48,'2022_05_31_122026_create_assets_table',2),(49,'2022_05_31_122655_create_assetcategories_table',2),(50,'2022_05_31_150039_create_salaries_table',2),(51,'2022_05_6_063624_create_hub_payments_table',2),(52,'2022_06_01_144229_create_news_offers_table',2),(53,'2022_06_02_125218_create_support_chats_table',2),(54,'2022_06_04_104751_create_hub_statements_table',2),(55,'2022_06_05_093107_create_frauds_table',2),(56,'2022_06_05_140650_create_cash_received_from_deliverymen_table',2),(57,'2022_06_12_111844_create_salary_generates_table',2),(58,'2022_08_17_145916_create_subscribes_table',2),(59,'2022_09_08_102027_create_pickup_requests_table',2),(60,'2022_10_11_121745_create_invoices_table',2),(61,'2022_10_17_102458_create_settings_table',2),(62,'2022_10_30_135339_create_merchant_online_payments_table',2),(63,'2022_11_02_105821_create_merchant_online_payment_receiveds_table',2),(64,'2022_11_02_113430_create_merchant_settings_table',2),(65,'2022_12_08_104319_create_addons_table',2),(66,'2022_12_08_104319_create_currencies_table',2),(67,'2023_06_11_172412_create_social_links_table',2),(68,'2023_06_12_144849_create_services_table',2),(69,'2023_06_13_111335_create_why_couriers_table',2),(70,'2023_06_13_122133_create_faqs_table',2),(71,'2023_06_13_133544_create_partners_table',2),(72,'2023_06_13_154945_create_blogs_table',2),(73,'2023_06_13_164933_create_pages_table',2),(74,'2023_06_13_180141_create_sections_table',2),(75,'2023_10_17_122352_create_wallets_table',2),(76,'2023_10_8_094551_create_google_map_settings_table',2),(77,'2024_06_26_065107_create_invoice_parcels_table',2),(78,'2025_03_24_091421_create_notifications_table',2),(79,'2025_05_19_065351_create_banks_table',2),(80,'2025_05_19_094956_create_mobile_banks_table',2),(81,'2025_05_20_000001_add_columns_to_assets_table',2),(82,'2025_05_20_065306_create_vehicles_table',2),(83,'2025_05_20_065340_create_fuels_table',2),(84,'2025_05_20_065408_create_maintainances_table',2),(85,'2025_05_20_065438_create_accidents_table',2),(86,'2025_05_20_065505_create_asset_assigns_table',2),(87,'2025_05_24_055308_create_online_payments_table',2),(88,'2025_05_27_062557_add_deliveryman_current_location_to_delivery_man_table',2),(89,'2025_09_01_215819_enhance_hubs_for_multi_branch_support',2),(90,'2025_09_01_220734_create_branch_configurations_table',2),(91,'2025_09_10_173359_create_shipments_table',3),(92,'2025_09_10_173723_create_scan_events_table',4),(93,'2025_09_10_174158_create_transport_legs_table',4),(94,'2025_09_10_181042_create_bags_table',4),(95,'2025_09_10_194116_create_bag_parcel_table',4),(96,'2025_09_10_194603_create_routes_table',4),(97,'2025_09_10_194749_create_stops_table',4),(98,'2025_09_10_201807_create_epods_table',4),(99,'2025_09_10_204228_create_notifications_table',4),(100,'2025_09_10_204449_create_rate_cards_table',4),(101,'2025_09_10_204632_create_charge_lines_table',4),(102,'2025_09_10_204740_create_invoices_table',4),(103,'2025_09_10_205437_create_cod_receipts_table',4),(104,'2025_09_10_205923_create_settlement_cycles_table',4),(105,'2025_09_10_210025_create_commodities_table',4),(106,'2025_09_10_210334_create_hs_codes_table',4),(107,'2025_09_10_211711_create_customs_docs_table',4),(108,'2025_09_12_000001_create_otp_codes_table',4),(109,'2025_09_12_000002_add_phone_e164_to_users_table',4),(110,'2025_09_12_000003_create_user_consents_table',4),(111,'2025_09_13_000001_create_dhl_modules_tables',4),(112,'2025_09_13_150000_create_zones_table',4),(113,'2025_09_13_150100_create_lanes_table',4),(114,'2025_09_13_150200_create_carriers_table',4),(115,'2025_09_13_150300_create_carrier_services_table',4),(116,'2025_09_13_150400_create_whatsapp_templates_table',4),(117,'2025_09_13_150500_create_edi_providers_table',4),(118,'2025_09_13_150600_create_surveys_table',4),(119,'2025_09_13_170000_create_api_keys_table',4),(120,'2025_09_17_000001_create_impersonation_logs_table',4),(121,'2025_09_17_000002_add_notification_prefs_to_users',4),(122,'2025_09_25_190000_rename_deliverd_date_to_delivered_date_in_parcels_table',4),(123,'2025_09_25_190100_change_weight_to_decimal_in_parcels_table',4),(124,'2025_09_30_003358_create_devices_table',4),(125,'2025_09_30_012435_add_public_token_to_shipments_table',4),(126,'2025_09_30_020000_create_pod_proofs_table',4),(127,'2025_09_30_021000_create_tasks_table',4),(128,'2025_09_30_022000_create_webhook_endpoints_table',4),(129,'2025_09_30_023000_create_webhook_deliveries_table',4),(130,'2025_09_30_024000_create_driver_locations_table',4),(131,'2025_10_02_224758_create_unified_branches_table',4),(132,'2025_10_02_224905_create_branch_managers_table',4),(133,'2025_10_02_225004_create_branch_workers_table',4),(134,'2025_10_02_232657_create_customers_table',5),(135,'2025_10_03_004509_add_unified_workflow_fields_to_shipments_table',6),(136,'2025_10_05_000001_create_clients_table',6),(137,'2025_10_06_022706_create_shipment_logs_table',7),(138,'2025_10_08_120000_create_operations_notifications_table',7),(139,'2025_11_06_100000_create_workflow_tasks_table',7),(140,'2025_11_06_100100_create_workflow_task_comments_table',7),(141,'2025_11_06_100200_create_workflow_task_activities_table',7),(142,'2025_11_06_110000_add_name_to_customers_table',7),(143,'2025_11_06_111000_add_shipment_foreign_key_to_payments_table',7),(144,'2025_11_06_070000_add_transaction_id_to_payments_table',8);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `mobile_banks`
--

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

--
-- Dumping data for table `mobile_banks`
--

LOCK TABLES `mobile_banks` WRITE;
/*!40000 ALTER TABLE `mobile_banks` DISABLE KEYS */;
/*!40000 ALTER TABLE `mobile_banks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `news_offers`
--

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

--
-- Dumping data for table `news_offers`
--

LOCK TABLES `news_offers` WRITE;
/*!40000 ALTER TABLE `news_offers` DISABLE KEYS */;
/*!40000 ALTER TABLE `news_offers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notification_settings`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `notification_settings`
--

LOCK TABLES `notification_settings` WRITE;
/*!40000 ALTER TABLE `notification_settings` DISABLE KEYS */;
INSERT INTO `notification_settings` VALUES (1,'','','2025-06-29 11:29:47','2025-06-29 11:29:47');
/*!40000 ALTER TABLE `notification_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `notifications`
--

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

--
-- Dumping data for table `notifications`
--

LOCK TABLES `notifications` WRITE;
/*!40000 ALTER TABLE `notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `online_payments`
--

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

--
-- Dumping data for table `online_payments`
--

LOCK TABLES `online_payments` WRITE;
/*!40000 ALTER TABLE `online_payments` DISABLE KEYS */;
/*!40000 ALTER TABLE `online_payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `operations_notifications`
--

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

--
-- Dumping data for table `operations_notifications`
--

LOCK TABLES `operations_notifications` WRITE;
/*!40000 ALTER TABLE `operations_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `operations_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `otp_codes`
--

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

--
-- Dumping data for table `otp_codes`
--

LOCK TABLES `otp_codes` WRITE;
/*!40000 ALTER TABLE `otp_codes` DISABLE KEYS */;
/*!40000 ALTER TABLE `otp_codes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `packagings`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `packagings`
--

LOCK TABLES `packagings` WRITE;
/*!40000 ALTER TABLE `packagings` DISABLE KEYS */;
INSERT INTO `packagings` VALUES (1,'Poly',10.00,1,'1',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(2,'Bubble Poly',20.00,1,'2',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(3,'Box',30.00,1,'3',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(4,'Box Poly',40.00,1,'4',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46');
/*!40000 ALTER TABLE `packagings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pages`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `pages`
--

LOCK TABLES `pages` WRITE;
/*!40000 ALTER TABLE `pages` DISABLE KEYS */;
INSERT INTO `pages` VALUES (1,'privacy_policy','Privacy & Policy','Deleniti magnam dignissimos similique a dolorem magni culpa vero voluptatem quia nostrum officia itaque velit reiciendis corporis quia officia labore odit maiores dolor aspernatur eum beatae alias sed et tempore dignissimos nesciunt iusto iste facere tenetur quod adipisci natus sint cumque quia cupiditate vitae rerum velit rerum quibusdam consequatur sed sequi aut qui incidunt qui repudiandae mollitia tenetur fuga inventore assumenda qui vitae molestiae consequatur aut et libero similique quidem eligendi vero mollitia dolores quos aut dolores molestias repellat quod tenetur voluptas impedit illo tempora voluptatem enim nulla voluptate qui est quo temporibus aperiam blanditiis commodi et et ut id quisquam ut suscipit cumque atque voluptas veritatis aut eligendi atque ut sunt tempora consequatur et soluta maxime nesciunt molestias est quam atque doloremque in quia aut est quasi qui minima modi explicabo sunt sit ipsa sit ipsum ipsum et quos beatae molestiae voluptas aliquid distinctio aut earum laborum libero omnis dolor voluptatem nobis aut ut ullam voluptatem labore magnam veniam voluptatum blanditiis eum unde laudantium atque aperiam a perspiciatis excepturi aperiam in quo ea ut sequi iste cumque illo iste natus rerum velit labore eum odit non eum voluptate voluptas expedita cupiditate a dolorem nulla rerum facilis illum officiis repellendus sed quis officiis rerum inventore molestias voluptatem expedita quia enim quo neque labore architecto quos vel in omnis saepe optio et et et nobis odio id suscipit vero nisi maxime ab magni est quos voluptatem adipisci illum quaerat delectus explicabo est laboriosam pariatur qui deleniti error aut aliquid et molestias animi facilis et perspiciatis ut ab incidunt incidunt maxime vel deleniti impedit voluptas labore et delectus id ipsam deleniti maiores excepturi esse aut iure ipsam vero officia fuga repellat molestias dolorem molestiae laboriosam repellendus nemo qui pariatur libero nisi recusandae aspernatur neque dolorum voluptatem magni quo quia sequi distinctio itaque cupiditate reiciendis ea quisquam nam ut vero ut non velit eveniet fuga voluptatibus molestias velit tenetur eos ex quam voluptatibus officiis dolorem non sit dolorem temporibus accusamus magni corrupti quia a saepe qui nam rerum culpa dicta quia et veniam cupiditate ut maiores aliquid eaque odio sint quia tempore ut nemo doloremque omnis expedita qui molestias deserunt repudiandae esse beatae et est eum praesentium dolor omnis cum laboriosam ipsa sit eos id sint minima alias molestiae sint voluptate exercitationem excepturi molestiae accusamus quia minima enim sunt nulla accusantium dolor voluptas nostrum et velit veritatis sunt cupiditate possimus quibusdam itaque repellendus eos officiis ipsum iusto qui cupiditate repellendus quod unde accusamus voluptatem sunt soluta eius molestiae neque ipsa molestiae dicta dolor placeat non sunt quidem molestiae aperiam sunt suscipit placeat ut sapiente molestiae suscipit velit aliquam dolores dolorem ex beatae ut aliquid quae repudiandae consequatur laudantium minima ut voluptatem dolorum iusto ea explicabo suscipit et at voluptatem autem mollitia fugiat modi sequi eum reiciendis aut quam suscipit ut aut cupiditate non non dolores dolorem tempore ut dolorem ex fugit est fugiat nesciunt fugit corrupti consequatur ut sit aliquid expedita illum quis quos necessitatibus voluptatem hic dolorum autem aut laborum quam fugiat veritatis mollitia voluptatem culpa ut consequuntur perferendis rerum sit nihil rerum voluptatem voluptatem sit autem hic omnis totam inventore incidunt laborum doloribus cumque esse consequuntur sed tempora vel dolor quisquam qui quaerat voluptas architecto sunt saepe at assumenda quisquam facere et dolorem accusamus quia aliquid dignissimos quis adipisci veniam nemo aut aperiam eum qui molestias voluptates ipsam illum quis accusamus qui illum illum illum possimus odit eius officiis perspiciatis quis soluta ad voluptatem odio sint nulla eum consectetur ut voluptatem veritatis fuga aut iusto repudiandae tempore qui qui sed molestias in aliquam dignissimos sint recusandae ab architecto voluptatem qui mollitia quae sed velit et corporis odit numquam nesciunt quis et et laudantium laboriosam quasi iure animi sit fugit temporibus dolorem alias reiciendis quibusdam nobis quaerat et quam aspernatur ullam laboriosam nesciunt nemo aut tempora consequatur rerum architecto quam maiores itaque necessitatibus molestias quisquam voluptas ut deleniti dolor reprehenderit aut voluptate aut voluptatem porro qui enim beatae minus aperiam qui accusamus cumque aspernatur est dignissimos illum alias quaerat ut earum qui expedita aut placeat hic ut repudiandae earum deleniti ut debitis autem tempora molestiae ea nostrum recusandae aliquid dignissimos qui est ut error laboriosam accusantium enim qui exercitationem illum commodi est quia sunt sit quia dolorem magni repudiandae ratione doloremque blanditiis reiciendis rerum qui fugit repudiandae debitis corrupti omnis maiores vel perspiciatis aliquid vero praesentium et qui ipsam non aliquam dolor incidunt quae eaque nostrum sit doloremque corrupti et nemo eius atque eos enim laborum ea et qui quia quia magni aut odit alias ullam quasi libero quisquam et aut architecto magni sit necessitatibus sint iusto omnis nisi atque voluptas animi natus laborum quia veritatis qui maxime itaque consequatur sapiente quas architecto rerum minus totam est non id quam repellat qui est animi ut omnis consectetur perferendis modi et vel et accusamus similique corporis ea sed est eveniet totam et sunt hic est consequatur repudiandae natus doloribus voluptas recusandae est nisi veniam esse itaque vitae molestiae perferendis fuga dicta nemo cum velit eos at maiores architecto et ducimus consequatur ut aliquid voluptatem nostrum quas exercitationem sed sed atque dolorem vel qui at est in esse et voluptate nemo necessitatibus sunt quia ut accusantium qui odit ex est et autem ex rerum quae sit quasi sit ducimus ipsa est dolores esse pariatur doloribus voluptates quam quaerat et quo accusantium rerum laudantium doloribus voluptatem nulla architecto quibusdam optio non earum a in ipsam adipisci ab aut laboriosam natus alias recusandae quia iure enim expedita in dolorem sint ab rem quia perferendis id soluta voluptatum est modi repudiandae incidunt exercitationem autem fugit itaque voluptatem doloribus aut reprehenderit unde repudiandae quasi aliquam ut impedit dolores in inventore odit deleniti voluptate perferendis quasi cum cumque eligendi corrupti ex quibusdam nulla ut modi modi omnis perferendis numquam qui sit dolorum ipsa sed vel fugiat molestiae architecto ut et voluptatem provident explicabo non autem blanditiis officiis qui occaecati qui in dolor illo voluptas dolore voluptatibus hic sapiente quis quaerat corrupti sequi dolorem illo odio dolore et iusto in sit in aliquam dicta esse sunt a eaque magnam quasi minus quasi a quidem est et excepturi et id nisi dolore ut et laboriosam quasi perspiciatis vitae expedita id quos reiciendis quod est qui numquam hic veniam incidunt qui dolorem aut iure nisi necessitatibus vel quia laudantium et cumque quas tempore totam ex suscipit sunt nulla eos at iusto saepe amet ad unde autem facere ullam nulla sed quidem tempora similique pariatur error ad est ullam voluptatum repudiandae quis aut aut dolorem odit commodi sequi culpa inventore doloribus voluptas molestiae sed aliquam molestias atque nulla animi modi eum ipsam magnam eligendi animi ducimus maxime eius totam et ex eius quis unde corrupti neque sequi eum quia impedit consequatur magni consequatur dolor officia quis praesentium quia veniam earum porro dolores quis quae fuga amet sit enim tenetur vero aut pariatur repellat voluptas consequatur perferendis eum odit vel adipisci explicabo dicta sit enim perferendis minus velit necessitatibus magni deserunt perspiciatis aut quae amet cumque voluptatibus earum doloremque neque esse maiores sed voluptates id magnam ipsa incidunt repellat officiis dolorem nulla mollitia et placeat perspiciatis sed enim harum quis ut nisi facilis architecto nobis et natus fuga dignissimos dignissimos adipisci quisquam molestias ex placeat sint magnam non autem laborum autem nihil quia est architecto tempora earum odit quos hic repellat beatae repudiandae suscipit numquam sapiente maxime laboriosam quia numquam et ea fugit numquam voluptatum sit dicta eum omnis recusandae nobis esse maiores architecto magnam rerum alias et et excepturi cupiditate autem et aut aspernatur reiciendis excepturi iusto sit natus dolores voluptatibus id numquam dolorum provident minima eligendi eum fuga nulla non enim iusto ullam id eum ratione dolores saepe beatae atque voluptatum quis earum perferendis aut accusantium omnis est quibusdam ipsum est voluptas provident.',1,'2023-06-13 08:57:18','2023-06-13 08:57:18'),(2,'terms_conditions','Terms & Conditions','Deleniti magnam dignissimos similique a dolorem magni culpa vero voluptatem quia nostrum officia itaque velit reiciendis corporis quia officia labore odit maiores dolor aspernatur eum beatae alias sed et tempore dignissimos nesciunt iusto iste facere tenetur quod adipisci natus sint cumque quia cupiditate vitae rerum velit rerum quibusdam consequatur sed sequi aut qui incidunt qui repudiandae mollitia tenetur fuga inventore assumenda qui vitae molestiae consequatur aut et libero similique quidem eligendi vero mollitia dolores quos aut dolores molestias repellat quod tenetur voluptas impedit illo tempora voluptatem enim nulla voluptate qui est quo temporibus aperiam blanditiis commodi et et ut id quisquam ut suscipit cumque atque voluptas veritatis aut eligendi atque ut sunt tempora consequatur et soluta maxime nesciunt molestias est quam atque doloremque in quia aut est quasi qui minima modi explicabo sunt sit ipsa sit ipsum ipsum et quos beatae molestiae voluptas aliquid distinctio aut earum laborum libero omnis dolor voluptatem nobis aut ut ullam voluptatem labore magnam veniam voluptatum blanditiis eum unde laudantium atque aperiam a perspiciatis excepturi aperiam in quo ea ut sequi iste cumque illo iste natus rerum velit labore eum odit non eum voluptate voluptas expedita cupiditate a dolorem nulla rerum facilis illum officiis repellendus sed quis officiis rerum inventore molestias voluptatem expedita quia enim quo neque labore architecto quos vel in omnis saepe optio et et et nobis odio id suscipit vero nisi maxime ab magni est quos voluptatem adipisci illum quaerat delectus explicabo est laboriosam pariatur qui deleniti error aut aliquid et molestias animi facilis et perspiciatis ut ab incidunt incidunt maxime vel deleniti impedit voluptas labore et delectus id ipsam deleniti maiores excepturi esse aut iure ipsam vero officia fuga repellat molestias dolorem molestiae laboriosam repellendus nemo qui pariatur libero nisi recusandae aspernatur neque dolorum voluptatem magni quo quia sequi distinctio itaque cupiditate reiciendis ea quisquam nam ut vero ut non velit eveniet fuga voluptatibus molestias velit tenetur eos ex quam voluptatibus officiis dolorem non sit dolorem temporibus accusamus magni corrupti quia a saepe qui nam rerum culpa dicta quia et veniam cupiditate ut maiores aliquid eaque odio sint quia tempore ut nemo doloremque omnis expedita qui molestias deserunt repudiandae esse beatae et est eum praesentium dolor omnis cum laboriosam ipsa sit eos id sint minima alias molestiae sint voluptate exercitationem excepturi molestiae accusamus quia minima enim sunt nulla accusantium dolor voluptas nostrum et velit veritatis sunt cupiditate possimus quibusdam itaque repellendus eos officiis ipsum iusto qui cupiditate repellendus quod unde accusamus voluptatem sunt soluta eius molestiae neque ipsa molestiae dicta dolor placeat non sunt quidem molestiae aperiam sunt suscipit placeat ut sapiente molestiae suscipit velit aliquam dolores dolorem ex beatae ut aliquid quae repudiandae consequatur laudantium minima ut voluptatem dolorum iusto ea explicabo suscipit et at voluptatem autem mollitia fugiat modi sequi eum reiciendis aut quam suscipit ut aut cupiditate non non dolores dolorem tempore ut dolorem ex fugit est fugiat nesciunt fugit corrupti consequatur ut sit aliquid expedita illum quis quos necessitatibus voluptatem hic dolorum autem aut laborum quam fugiat veritatis mollitia voluptatem culpa ut consequuntur perferendis rerum sit nihil rerum voluptatem voluptatem sit autem hic omnis totam inventore incidunt laborum doloribus cumque esse consequuntur sed tempora vel dolor quisquam qui quaerat voluptas architecto sunt saepe at assumenda quisquam facere et dolorem accusamus quia aliquid dignissimos quis adipisci veniam nemo aut aperiam eum qui molestias voluptates ipsam illum quis accusamus qui illum illum illum possimus odit eius officiis perspiciatis quis soluta ad voluptatem odio sint nulla eum consectetur ut voluptatem veritatis fuga aut iusto repudiandae tempore qui qui sed molestias in aliquam dignissimos sint recusandae ab architecto voluptatem qui mollitia quae sed velit et corporis odit numquam nesciunt quis et et laudantium laboriosam quasi iure animi sit fugit temporibus dolorem alias reiciendis quibusdam nobis quaerat et quam aspernatur ullam laboriosam nesciunt nemo aut tempora consequatur rerum architecto quam maiores itaque necessitatibus molestias quisquam voluptas ut deleniti dolor reprehenderit aut voluptate aut voluptatem porro qui enim beatae minus aperiam qui accusamus cumque aspernatur est dignissimos illum alias quaerat ut earum qui expedita aut placeat hic ut repudiandae earum deleniti ut debitis autem tempora molestiae ea nostrum recusandae aliquid dignissimos qui est ut error laboriosam accusantium enim qui exercitationem illum commodi est quia sunt sit quia dolorem magni repudiandae ratione doloremque blanditiis reiciendis rerum qui fugit repudiandae debitis corrupti omnis maiores vel perspiciatis aliquid vero praesentium et qui ipsam non aliquam dolor incidunt quae eaque nostrum sit doloremque corrupti et nemo eius atque eos enim laborum ea et qui quia quia magni aut odit alias ullam quasi libero quisquam et aut architecto magni sit necessitatibus sint iusto omnis nisi atque voluptas animi natus laborum quia veritatis qui maxime itaque consequatur sapiente quas architecto rerum minus totam est non id quam repellat qui est animi ut omnis consectetur perferendis modi et vel et accusamus similique corporis ea sed est eveniet totam et sunt hic est consequatur repudiandae natus doloribus voluptas recusandae est nisi veniam esse itaque vitae molestiae perferendis fuga dicta nemo cum velit eos at maiores architecto et ducimus consequatur ut aliquid voluptatem nostrum quas exercitationem sed sed atque dolorem vel qui at est in esse et voluptate nemo necessitatibus sunt quia ut accusantium qui odit ex est et autem ex rerum quae sit quasi sit ducimus ipsa est dolores esse pariatur doloribus voluptates quam quaerat et quo accusantium rerum laudantium doloribus voluptatem nulla architecto quibusdam optio non earum a in ipsam adipisci ab aut laboriosam natus alias recusandae quia iure enim expedita in dolorem sint ab rem quia perferendis id soluta voluptatum est modi repudiandae incidunt exercitationem autem fugit itaque voluptatem doloribus aut reprehenderit unde repudiandae quasi aliquam ut impedit dolores in inventore odit deleniti voluptate perferendis quasi cum cumque eligendi corrupti ex quibusdam nulla ut modi modi omnis perferendis numquam qui sit dolorum ipsa sed vel fugiat molestiae architecto ut et voluptatem provident explicabo non autem blanditiis officiis qui occaecati qui in dolor illo voluptas dolore voluptatibus hic sapiente quis quaerat corrupti sequi dolorem illo odio dolore et iusto in sit in aliquam dicta esse sunt a eaque magnam quasi minus quasi a quidem est et excepturi et id nisi dolore ut et laboriosam quasi perspiciatis vitae expedita id quos reiciendis quod est qui numquam hic veniam incidunt qui dolorem aut iure nisi necessitatibus vel quia laudantium et cumque quas tempore totam ex suscipit sunt nulla eos at iusto saepe amet ad unde autem facere ullam nulla sed quidem tempora similique pariatur error ad est ullam voluptatum repudiandae quis aut aut dolorem odit commodi sequi culpa inventore doloribus voluptas molestiae sed aliquam molestias atque nulla animi modi eum ipsam magnam eligendi animi ducimus maxime eius totam et ex eius quis unde corrupti neque sequi eum quia impedit consequatur magni consequatur dolor officia quis praesentium quia veniam earum porro dolores quis quae fuga amet sit enim tenetur vero aut pariatur repellat voluptas consequatur perferendis eum odit vel adipisci explicabo dicta sit enim perferendis minus velit necessitatibus magni deserunt perspiciatis aut quae amet cumque voluptatibus earum doloremque neque esse maiores sed voluptates id magnam ipsa incidunt repellat officiis dolorem nulla mollitia et placeat perspiciatis sed enim harum quis ut nisi facilis architecto nobis et natus fuga dignissimos dignissimos adipisci quisquam molestias ex placeat sint magnam non autem laborum autem nihil quia est architecto tempora earum odit quos hic repellat beatae repudiandae suscipit numquam sapiente maxime laboriosam quia numquam et ea fugit numquam voluptatum sit dicta eum omnis recusandae nobis esse maiores architecto magnam rerum alias et et excepturi cupiditate autem et aut aspernatur reiciendis excepturi iusto sit natus dolores voluptatibus id numquam dolorum provident minima eligendi eum fuga nulla non enim iusto ullam id eum ratione dolores saepe beatae atque voluptatum quis earum perferendis aut accusantium omnis est quibusdam ipsum est voluptas provident.',1,'2023-06-13 08:57:18','2023-06-13 08:57:18'),(3,'about_us','About Us','Deleniti magnam dignissimos similique a dolorem magni culpa vero voluptatem quia nostrum officia itaque velit reiciendis corporis quia officia labore odit maiores dolor aspernatur eum beatae alias sed et tempore dignissimos nesciunt iusto iste facere tenetur quod adipisci natus sint cumque quia cupiditate vitae rerum velit rerum quibusdam consequatur sed sequi aut qui incidunt qui repudiandae mollitia tenetur fuga inventore assumenda qui vitae molestiae consequatur aut et libero similique quidem eligendi vero mollitia dolores quos aut dolores molestias repellat quod tenetur voluptas impedit illo tempora voluptatem enim nulla voluptate qui est quo temporibus aperiam blanditiis commodi et et ut id quisquam ut suscipit cumque atque voluptas veritatis aut eligendi atque ut sunt tempora consequatur et soluta maxime nesciunt molestias est quam atque doloremque in quia aut est quasi qui minima modi explicabo sunt sit ipsa sit ipsum ipsum et quos beatae molestiae voluptas aliquid distinctio aut earum laborum libero omnis dolor voluptatem nobis aut ut ullam voluptatem labore magnam veniam voluptatum blanditiis eum unde laudantium atque aperiam a perspiciatis excepturi aperiam in quo ea ut sequi iste cumque illo iste natus rerum velit labore eum odit non eum voluptate voluptas expedita cupiditate a dolorem nulla rerum facilis illum officiis repellendus sed quis officiis rerum inventore molestias voluptatem expedita quia enim quo neque labore architecto quos vel in omnis saepe optio et et et nobis odio id suscipit vero nisi maxime ab magni est quos voluptatem adipisci illum quaerat delectus explicabo est laboriosam pariatur qui deleniti error aut aliquid et molestias animi facilis et perspiciatis ut ab incidunt incidunt maxime vel deleniti impedit voluptas labore et delectus id ipsam deleniti maiores excepturi esse aut iure ipsam vero officia fuga repellat molestias dolorem molestiae laboriosam repellendus nemo qui pariatur libero nisi recusandae aspernatur neque dolorum voluptatem magni quo quia sequi distinctio itaque cupiditate reiciendis ea quisquam nam ut vero ut non velit eveniet fuga voluptatibus molestias velit tenetur eos ex quam voluptatibus officiis dolorem non sit dolorem temporibus accusamus magni corrupti quia a saepe qui nam rerum culpa dicta quia et veniam cupiditate ut maiores aliquid eaque odio sint quia tempore ut nemo doloremque omnis expedita qui molestias deserunt repudiandae esse beatae et est eum praesentium dolor omnis cum laboriosam ipsa sit eos id sint minima alias molestiae sint voluptate exercitationem excepturi molestiae accusamus quia minima enim sunt nulla accusantium dolor voluptas nostrum et velit veritatis sunt cupiditate possimus quibusdam itaque repellendus eos officiis ipsum iusto qui cupiditate repellendus quod unde accusamus voluptatem sunt soluta eius molestiae neque ipsa molestiae dicta dolor placeat non sunt quidem molestiae aperiam sunt suscipit placeat ut sapiente molestiae suscipit velit aliquam dolores dolorem ex beatae ut aliquid quae repudiandae consequatur laudantium minima ut voluptatem dolorum iusto ea explicabo suscipit et at voluptatem autem mollitia fugiat modi sequi eum reiciendis aut quam suscipit ut aut cupiditate non non dolores dolorem tempore ut dolorem ex fugit est fugiat nesciunt fugit corrupti consequatur ut sit aliquid expedita illum quis quos necessitatibus voluptatem hic dolorum autem aut laborum quam fugiat veritatis mollitia voluptatem culpa ut consequuntur perferendis rerum sit nihil rerum voluptatem voluptatem sit autem hic omnis totam inventore incidunt laborum doloribus cumque esse consequuntur sed tempora vel dolor quisquam qui quaerat voluptas architecto sunt saepe at assumenda quisquam facere et dolorem accusamus quia aliquid dignissimos quis adipisci veniam nemo aut aperiam eum qui molestias voluptates ipsam illum quis accusamus qui illum illum illum possimus odit eius officiis perspiciatis quis soluta ad voluptatem odio sint nulla eum consectetur ut voluptatem veritatis fuga aut iusto repudiandae tempore qui qui sed molestias in aliquam dignissimos sint recusandae ab architecto voluptatem qui mollitia quae sed velit et corporis odit numquam nesciunt quis et et laudantium laboriosam quasi iure animi sit fugit temporibus dolorem alias reiciendis quibusdam nobis quaerat et quam aspernatur ullam laboriosam nesciunt nemo aut tempora consequatur rerum architecto quam maiores itaque necessitatibus molestias quisquam voluptas ut deleniti dolor reprehenderit aut voluptate aut voluptatem porro qui enim beatae minus aperiam qui accusamus cumque aspernatur est dignissimos illum alias quaerat ut earum qui expedita aut placeat hic ut repudiandae earum deleniti ut debitis autem tempora molestiae ea nostrum recusandae aliquid dignissimos qui est ut error laboriosam accusantium enim qui exercitationem illum commodi est quia sunt sit quia dolorem magni repudiandae ratione doloremque blanditiis reiciendis rerum qui fugit repudiandae debitis corrupti omnis maiores vel perspiciatis aliquid vero praesentium et qui ipsam non aliquam dolor incidunt quae eaque nostrum sit doloremque corrupti et nemo eius atque eos enim laborum ea et qui quia quia magni aut odit alias ullam quasi libero quisquam et aut architecto magni sit necessitatibus sint iusto omnis nisi atque voluptas animi natus laborum quia veritatis qui maxime itaque consequatur sapiente quas architecto rerum minus totam est non id quam repellat qui est animi ut omnis consectetur perferendis modi et vel et accusamus similique corporis ea sed est eveniet totam et sunt hic est consequatur repudiandae natus doloribus voluptas recusandae est nisi veniam esse itaque vitae molestiae perferendis fuga dicta nemo cum velit eos at maiores architecto et ducimus consequatur ut aliquid voluptatem nostrum quas exercitationem sed sed atque dolorem vel qui at est in esse et voluptate nemo necessitatibus sunt quia ut accusantium qui odit ex est et autem ex rerum quae sit quasi sit ducimus ipsa est dolores esse pariatur doloribus voluptates quam quaerat et quo accusantium rerum laudantium doloribus voluptatem nulla architecto quibusdam optio non earum a in ipsam adipisci ab aut laboriosam natus alias recusandae quia iure enim expedita in dolorem sint ab rem quia perferendis id soluta voluptatum est modi repudiandae incidunt exercitationem autem fugit itaque voluptatem doloribus aut reprehenderit unde repudiandae quasi aliquam ut impedit dolores in inventore odit deleniti voluptate perferendis quasi cum cumque eligendi corrupti ex quibusdam nulla ut modi modi omnis perferendis numquam qui sit dolorum ipsa sed vel fugiat molestiae architecto ut et voluptatem provident explicabo non autem blanditiis officiis qui occaecati qui in dolor illo voluptas dolore voluptatibus hic sapiente quis quaerat corrupti sequi dolorem illo odio dolore et iusto in sit in aliquam dicta esse sunt a eaque magnam quasi minus quasi a quidem est et excepturi et id nisi dolore ut et laboriosam quasi perspiciatis vitae expedita id quos reiciendis quod est qui numquam hic veniam incidunt qui dolorem aut iure nisi necessitatibus vel quia laudantium et cumque quas tempore totam ex suscipit sunt nulla eos at iusto saepe amet ad unde autem facere ullam nulla sed quidem tempora similique pariatur error ad est ullam voluptatum repudiandae quis aut aut dolorem odit commodi sequi culpa inventore doloribus voluptas molestiae sed aliquam molestias atque nulla animi modi eum ipsam magnam eligendi animi ducimus maxime eius totam et ex eius quis unde corrupti neque sequi eum quia impedit consequatur magni consequatur dolor officia quis praesentium quia veniam earum porro dolores quis quae fuga amet sit enim tenetur vero aut pariatur repellat voluptas consequatur perferendis eum odit vel adipisci explicabo dicta sit enim perferendis minus velit necessitatibus magni deserunt perspiciatis aut quae amet cumque voluptatibus earum doloremque neque esse maiores sed voluptates id magnam ipsa incidunt repellat officiis dolorem nulla mollitia et placeat perspiciatis sed enim harum quis ut nisi facilis architecto nobis et natus fuga dignissimos dignissimos adipisci quisquam molestias ex placeat sint magnam non autem laborum autem nihil quia est architecto tempora earum odit quos hic repellat beatae repudiandae suscipit numquam sapiente maxime laboriosam quia numquam et ea fugit numquam voluptatum sit dicta eum omnis recusandae nobis esse maiores architecto magnam rerum alias et et excepturi cupiditate autem et aut aspernatur reiciendis excepturi iusto sit natus dolores voluptatibus id numquam dolorum provident minima eligendi eum fuga nulla non enim iusto ullam id eum ratione dolores saepe beatae atque voluptatum quis earum perferendis aut accusantium omnis est quibusdam ipsum est voluptas provident.',1,'2023-06-13 08:57:18','2023-06-13 08:57:18'),(4,'faq','Have Question','Take a look at the most commonly asked questions.',1,'2023-06-13 08:57:18','2023-06-13 08:57:18'),(5,'contact','Contact Us','Take a look at the most commonly asked questions.',1,'2023-06-13 08:57:18','2023-06-13 08:57:18');
/*!40000 ALTER TABLE `pages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcel_events`
--

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

--
-- Dumping data for table `parcel_events`
--

LOCK TABLES `parcel_events` WRITE;
/*!40000 ALTER TABLE `parcel_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `parcel_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `parcels`
--

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

--
-- Dumping data for table `parcels`
--

LOCK TABLES `parcels` WRITE;
/*!40000 ALTER TABLE `parcels` DISABLE KEYS */;
/*!40000 ALTER TABLE `parcels` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `partners`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `partners`
--

LOCK TABLES `partners` WRITE;
/*!40000 ALTER TABLE `partners` DISABLE KEYS */;
INSERT INTO `partners` VALUES (1,'Ziemann-Bahringer',20,'#','1',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(2,'Lebsack Inc',21,'#','2',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(3,'Roberts, Schiller and McKenzie',22,'#','3',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(4,'Stracke-Lemke',23,'#','4',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(5,'Thiel, Gerlach and Rutherford',24,'#','5',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(6,'Cronin PLC',25,'#','6',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(7,'Lowe-Schneider',26,'#','7',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(8,'Von, Hayes and Erdman',27,'#','8',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(9,'Schamberger, Little and Abbott',28,'#','9',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(10,'Schmeler, Gulgowski and Abernathy',29,'#','10',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(11,'Sporer-Lakin',30,'#','11',1,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(12,'Satterfield-Hills',31,'#','12',1,'2025-06-29 11:29:49','2025-06-29 11:29:49');
/*!40000 ALTER TABLE `partners` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_resets`
--

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

--
-- Dumping data for table `password_resets`
--

LOCK TABLES `password_resets` WRITE;
/*!40000 ALTER TABLE `password_resets` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_resets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payment_accounts`
--

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

--
-- Dumping data for table `payment_accounts`
--

LOCK TABLES `payment_accounts` WRITE;
/*!40000 ALTER TABLE `payment_accounts` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_accounts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,2,1,49.99,'stripe','completed','PAY-XN3OXGXH','PAY-XN3OXGXH','2025-11-06 05:33:26',NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26'),(2,3,1,49.99,'stripe','completed','PAY-VUMSS6WL','PAY-VUMSS6WL','2025-11-06 05:40:09',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `permissions`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `permissions`
--

LOCK TABLES `permissions` WRITE;
/*!40000 ALTER TABLE `permissions` DISABLE KEYS */;
INSERT INTO `permissions` VALUES (1,'dashboard','{\"read\":\"dashboard_read\",\"calendar\":\"calendar_read\",\"total_Parcel\":\"total_parcel\",\"total_user\":\"total_user\",\"total_merchant\":\"total_merchant\",\"total_delivery_man\":\"total_delivery_man\",\"total_hubs\":\"total_hubs\",\"total_accounts\":\"total_accounts\",\"total_parcels_pending\":\"total_parcels_pending\",\"total_pickup_assigned\":\"total_pickup_assigned\",\"total_received_warehouse\":\"total_received_warehouse\",\"total_deliveryman_assigned\":\"total_deliveryman_assigned\",\"total_partial_deliverd\":\"total_partial_deliverd\",\"total_parcels_deliverd\":\"total_parcels_deliverd\",\"recent_accounts\":\"recent_accounts\",\"recent_salary\":\"recent_salary\",\"recent_hub\":\"recent_hub\",\"all_statements\":\"all_statements\",\"income_expense_charts\":\"income_expense_charts\",\"merchant_revenue_charts\":\"merchant_revenue_charts\",\"deliveryman_revenue_charts\":\"deliveryman_revenue_charts\",\"courier_revenue_charts\":\"courier_revenue_charts\",\"recent_parcels\":\"recent_parcels\",\"bank_transaction\":\"bank_transaction\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(2,'logs','{\"read\":\"log_read\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(3,'hubs','{\"read\":\"hub_read\",\"create\":\"hub_create\",\"update\":\"hub_update\",\"delete\":\"hub_delete\",\"incharge_read\":\"hub_incharge_read\",\"incharge_create\":\"hub_incharge_create\",\"incharge_update\":\"hub_incharge_update\",\"incharge_delete\":\"hub_incharge_delete\",\"incharge_assigned\":\"hub_incharge_assigned\",\"view\":\"hub_view\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(4,'accounts','{\"read\":\"account_read\",\"create\":\"account_create\",\"update\":\"account_update\",\"delete\":\"account_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(5,'income','{\"read\":\"income_read\",\"create\":\"income_create\",\"update\":\"income_update\",\"delete\":\"income_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(6,'expense','{\"read\":\"expense_read\",\"create\":\"expense_create\",\"update\":\"expense_update\",\"delete\":\"expense_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(7,'todo','{\"read\":\"todo_read\",\"create\":\"todo_create\",\"update\":\"todo_update\",\"delete\":\"todo_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(8,'fund_transfer','{\"read\":\"fund_transfer_read\",\"create\":\"fund_transfer_create\",\"update\":\"fund_transfer_update\",\"delete\":\"fund_transfer_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(9,'roles','{\"read\":\"role_read\",\"create\":\"role_create\",\"update\":\"role_update\",\"delete\":\"role_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(10,'designations','{\"read\":\"designation_read\",\"create\":\"designation_create\",\"update\":\"designation_update\",\"delete\":\"designation_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(11,'departments','{\"read\":\"department_read\",\"create\":\"department_create\",\"update\":\"department_update\",\"delete\":\"department_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(12,'users','{\"read\":\"user_read\",\"create\":\"user_create\",\"update\":\"user_update\",\"delete\":\"user_delete\",\"permission_update\":\"permission_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(13,'merchant','{\"read\":\"merchant_read\",\"create\":\"merchant_create\",\"update\":\"merchant_update\",\"delete\":\"merchant_delete\",\"view\":\"merchant_view\",\"delivery_charge_read\":\"merchant_delivery_charge_read\",\"delivery_charge_create\":\"merchant_delivery_charge_create\",\"delivery_charge_update\":\"merchant_delivery_charge_update\",\"delivery_charge_delete\":\"merchant_delivery_charge_delete\",\"shop_read\":\"merchant_shop_read\",\"shop_create\":\"merchant_shop_create\",\"shop_update\":\"merchant_shop_update\",\"shop_delete\":\"merchant_shop_delete\",\"payment_read\":\"merchant_payment_read\",\"payment_create\":\"merchant_payment_create\",\"payment_update\":\"merchant_payment_update\",\"payment_delete\":\"merchant_payment_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(14,'payments','{\"read\":\"payment_read\",\"create\":\"payment_create\",\"update\":\"payment_update\",\"delete\":\"payment_delete\",\"reject\":\"payment_reject\",\"process\":\"payment_process\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(15,'hub_payments','{\"read\":\"hub_payment_read\",\"create\":\"hub_payment_create\",\"update\":\"hub_payment_update\",\"delete\":\"hub_payment_delete\",\"reject\":\"hub_payment_reject\",\"process\":\"hub_payment_process\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(16,'hub_payments_request','{\"read\":\"hub_payment_request_read\",\"create\":\"hub_payment_request_create\",\"update\":\"hub_payment_request_update\",\"delete\":\"hub_payment_request_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(17,'liquid_fragile','{\"read\":\"liquid_fragile_read\",\"update\":\"liquid_fragile_update\",\"status_change\":\"liquid_status_change\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(18,'database_backup','{\"read\":\"database_backup_read\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(19,'sms_settings','{\"read\":\"sms_settings_read\",\"create\":\"sms_settings_create\",\"update\":\"sms_settings_update\",\"delete\":\"sms_settings_delete\",\"status_change\":\"sms_settings_status_change\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(20,'sms_send_settings','{\"read\":\"sms_send_settings_read\",\"create\":\"sms_send_settings_create\",\"update\":\"sms_send_settings_update\",\"delete\":\"sms_send_settings_delete\",\"status_change\":\"sms_send_settings_status_change\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(21,'general_settings','{\"read\":\"general_settings_read\",\"update\":\"general_settings_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(22,'notification_settings','{\"read\":\"notification_settings_read\",\"update\":\"notification_settings_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(23,'push_notification','{\"read\":\"push_notification_read\",\"create\":\"push_notification_create\",\"update\":\"push_notification_update\",\"delete\":\"push_notification_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(24,'parcel','{\"read\":\"parcel_read\",\"create\":\"parcel_create\",\"update\":\"parcel_update\",\"delete\":\"parcel_delete\",\"status_update\":\"parcel_status_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(25,'delivery_man','{\"read\":\"delivery_man_read\",\"create\":\"delivery_man_create\",\"update\":\"delivery_man_update\",\"delete\":\"delivery_man_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(26,'delivery_category','{\"read\":\"delivery_category_read\",\"create\":\"delivery_category_create\",\"update\":\"delivery_category_update\",\"delete\":\"delivery_category_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(27,'delivery_charge','{\"read\":\"delivery_charge_read\",\"create\":\"delivery_charge_create\",\"update\":\"delivery_charge_update\",\"delete\":\"delivery_charge_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(28,'delivery_type','{\"read\":\"delivery_type_read\",\"status_change\":\"delivery_type_status_change\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(29,'packaging','{\"read\":\"packaging_read\",\"create\":\"packaging_create\",\"update\":\"packaging_update\",\"delete\":\"packaging_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(30,'category','{\"read\":\"category_read\",\"create\":\"category_create\",\"update\":\"category_update\",\"delete\":\"category_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(31,'account_heads','{\"read\":\"account_heads_read\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(32,'salary','{\"read\":\"salary_read\",\"create\":\"salary_create\",\"update\":\"salary_update\",\"delete\":\"salary_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(33,'support','{\"read\":\"support_read\",\"create\":\"support_create\",\"update\":\"support_update\",\"delete\":\"support_delete\",\"reply\":\"support_reply\",\"status_update\":\"support_status_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(34,'asset_category','{\"read\":\"asset_category_read\",\"create\":\"asset_category_create\",\"update\":\"asset_category_update\",\"delete\":\"asset_category_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(35,'assets','{\"read\":\"assets_read\",\"create\":\"assets_create\",\"update\":\"assets_update\",\"delete\":\"assets_delete\",\"reports\":\"assets_reports\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(36,'news_offer','{\"read\":\"news_offer_read\",\"create\":\"news_offer_create\",\"update\":\"news_offer_update\",\"delete\":\"news_offer_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(37,'bank_transaction','{\"read\":\"bank_transaction_read\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(38,'cash_received_from_delivery_man','{\"read\":\"cash_received_from_delivery_man_read\",\"create\":\"cash_received_from_delivery_man_create\",\"update\":\"cash_received_from_delivery_man_update\",\"delete\":\"cash_received_from_delivery_man_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(39,'reports','{\"parcel_status_reports\":\"parcel_status_reports\",\"parcel_wise_profit\":\"parcel_wise_profit\",\"parcel_total_summery\":\"parcel_total_summery\",\"salary_reports\":\"salary_reports\",\"merchant_hub_deliveryman\":\"merchant_hub_deliveryman\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(40,'salary_generate','{\"read\":\"salary_generate_read\",\"create\":\"salary_generate_create\",\"update\":\"salary_generate_update\",\"delete\":\"salary_generate_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(41,'fraud','{\"read\":\"fraud_read\",\"create\":\"fraud_create\",\"update\":\"fraud_update\",\"delete\":\"fraud_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(42,'subscribe','{\"read\":\"subscribe_read\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(43,'pickup_request','{\"regular\":\"pickup_request_regular\",\"express\":\"pickup_request_express\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(44,'invoice','{\"read\":\"invoice_read\",\"status_update\":\"invoice_status_update\",\"paid_invoice_read\":\"paid_invoice_read\",\"invoice_generate\":\"invoice_generate_menually\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(45,'social_login_settings','{\"read\":\"social_login_settings_read\",\"update\":\"social_login_settings_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(46,'payout_setup_settings','{\"read\":\"payout_setup_settings_read\",\"update\":\"payout_setup_settings_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(47,'mail_settings','{\"read\":\"mail_settings_read\",\"update\":\"mail_settings_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(48,'online_payment','{\"read\":\"online_payment_read\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(49,'payout','{\"read\":\"payout_read\",\"create\":\"payout_create\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(50,'currency','{\"read\":\"currency_read\",\"create\":\"currency_create\",\"update\":\"currency_update\",\"delete\":\"currency_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(51,'social_link','{\"read\":\"social_link_read\",\"create\":\"social_link_create\",\"update\":\"social_link_update\",\"delete\":\"social_link_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(52,'services','{\"read\":\"service_read\",\"create\":\"service_create\",\"update\":\"service_update\",\"delete\":\"service_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(53,'why_courier','{\"read\":\"why_courier_read\",\"create\":\"why_courier_create\",\"update\":\"why_courier_update\",\"delete\":\"why_courier_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(54,'faq','{\"read\":\"faq_read\",\"create\":\"faq_create\",\"update\":\"faq_update\",\"delete\":\"faq_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(55,'partner','{\"read\":\"partner_read\",\"create\":\"partner_create\",\"update\":\"partner_update\",\"delete\":\"partner_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(56,'blogs','{\"read\":\"blogs_read\",\"create\":\"blogs_create\",\"update\":\"blogs_update\",\"delete\":\"blogs_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(57,'pages','{\"read\":\"pages_read\",\"update\":\"pages_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(58,'sections','{\"read\":\"section_read\",\"update\":\"section_update\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(59,'wallet_request','{\"read\":\"wallet_request_read\",\"create\":\"wallet_request_create\",\"delete\":\"wallet_request_delete\",\"approve\":\"wallet_request_approve\",\"reject\":\"wallet_request_reject\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(60,'vehicles','{\"read\":\"vehicles_read\",\"create\":\"vehicles_create\",\"update\":\"vehicles_update\",\"delete\":\"vehicles_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(61,'fuels','{\"read\":\"fuels_read\",\"create\":\"fuels_create\",\"update\":\"fuels_update\",\"delete\":\"fuels_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(62,'maintenances','{\"read\":\"maintenance_read\",\"create\":\"maintenance_create\",\"update\":\"maintenance_update\",\"delete\":\"maintenance_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46'),(63,'accidents','{\"read\":\"accidents_read\",\"create\":\"accidents_create\",\"update\":\"accidents_update\",\"delete\":\"accidents_delete\"}','2025-06-29 11:29:46','2025-06-29 11:29:46');
/*!40000 ALTER TABLE `permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',2,'react-dashboard-token',NULL,'0b47c351868f3302637b06fc529e9d5f3924398a971939cb5ea7e9e554c55453','[\"*\"]',NULL,'2025-11-06 05:32:21','2025-11-06 05:32:21'),(2,'App\\Models\\User',2,'react-dashboard-token',NULL,'0d93499ce09138473f0376801f1d3c851a6f0ad1e5094cc9a595cf03448af9ae','[\"*\"]',NULL,'2025-11-06 05:34:12','2025-11-06 05:34:12'),(3,'App\\Models\\User',2,'react-dashboard-token',NULL,'8354764cf73d9fb2d6db5623475058037a87998761a5ea0dbb95bd373eb657eb','[\"*\"]',NULL,'2025-11-06 05:42:36','2025-11-06 05:42:36'),(4,'App\\Models\\User',2,'react-dashboard-token',NULL,'714a98c66134703c95eb1e778c49e24169d33411b44e99eb2783bb183b5b5aee','[\"*\"]',NULL,'2025-11-06 05:52:38','2025-11-06 05:52:38'),(5,'App\\Models\\User',2,'react-dashboard-token',NULL,'4eea491c60201a2c2a017375e053399c673430afdf290320169068250f64fe17','[\"*\"]',NULL,'2025-11-06 05:53:59','2025-11-06 05:53:59'),(6,'App\\Models\\User',2,'react-dashboard-token',NULL,'f5f32f212d71d135d1558ca1cf45fe81dbbeaefac0a33c426f5ed56a04ca6777','[\"*\"]',NULL,'2025-11-06 06:32:10','2025-11-06 06:32:10'),(7,'App\\Models\\User',2,'react-dashboard-token',NULL,'3fee7616f72beede1bf71cb54796c9f4a5dac5d20964f8295d2c0d52ed8d8100','[\"*\"]',NULL,'2025-11-06 10:10:54','2025-11-06 10:10:54'),(8,'App\\Models\\User',2,'react-dashboard-token',NULL,'a5b253d1f84c47576a77e400b102cf9038c06088f7491d441f0f374c7be66091','[\"*\"]',NULL,'2025-11-06 10:39:05','2025-11-06 10:39:05'),(9,'App\\Models\\User',2,'react-dashboard-token',NULL,'945a16ce920597bb745b67da4abaaee36c44494b7389300c25972e3721e9306d','[\"*\"]',NULL,'2025-11-06 10:59:52','2025-11-06 10:59:52'),(10,'App\\Models\\User',2,'react-dashboard-token',NULL,'3c965bcd4fc5006f8e3e1ceda694a84d0549648f10d46354bcfcd2344d0968ef','[\"*\"]',NULL,'2025-11-06 11:09:22','2025-11-06 11:09:22');
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pickup_requests`
--

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

--
-- Dumping data for table `pickup_requests`
--

LOCK TABLES `pickup_requests` WRITE;
/*!40000 ALTER TABLE `pickup_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `pickup_requests` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `pod_proofs`
--

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

--
-- Dumping data for table `pod_proofs`
--

LOCK TABLES `pod_proofs` WRITE;
/*!40000 ALTER TABLE `pod_proofs` DISABLE KEYS */;
/*!40000 ALTER TABLE `pod_proofs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `push_notifications`
--

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

--
-- Dumping data for table `push_notifications`
--

LOCK TABLES `push_notifications` WRITE;
/*!40000 ALTER TABLE `push_notifications` DISABLE KEYS */;
/*!40000 ALTER TABLE `push_notifications` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `quotations`
--

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

--
-- Dumping data for table `quotations`
--

LOCK TABLES `quotations` WRITE;
/*!40000 ALTER TABLE `quotations` DISABLE KEYS */;
/*!40000 ALTER TABLE `quotations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `rate_cards`
--

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

--
-- Dumping data for table `rate_cards`
--

LOCK TABLES `rate_cards` WRITE;
/*!40000 ALTER TABLE `rate_cards` DISABLE KEYS */;
/*!40000 ALTER TABLE `rate_cards` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `return_orders`
--

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

--
-- Dumping data for table `return_orders`
--

LOCK TABLES `return_orders` WRITE;
/*!40000 ALTER TABLE `return_orders` DISABLE KEYS */;
/*!40000 ALTER TABLE `return_orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','super-admin','[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_create\",\"hub_update\",\"hub_delete\",\"hub_incharge_read\",\"hub_incharge_create\",\"hub_incharge_update\",\"hub_incharge_delete\",\"hub_incharge_assigned\",\"account_read\",\"account_create\",\"account_update\",\"account_delete\",\"income_read\",\"income_create\",\"income_update\",\"income_delete\",\"expense_read\",\"expense_create\",\"expense_update\",\"expense_delete\",\"todo_read\",\"todo_create\",\"todo_update\",\"todo_delete\",\"fund_transfer_read\",\"fund_transfer_create\",\"fund_transfer_update\",\"fund_transfer_delete\",\"role_read\",\"role_create\",\"role_update\",\"role_delete\",\"designation_read\",\"designation_create\",\"designation_update\",\"designation_delete\",\"department_read\",\"department_create\",\"department_update\",\"department_delete\",\"user_read\",\"user_create\",\"user_update\",\"user_delete\",\"permission_update\",\"merchant_read\",\"merchant_create\",\"merchant_update\",\"merchant_delete\",\"merchant_view\",\"merchant_delivery_charge_read\",\"merchant_delivery_charge_create\",\"merchant_delivery_charge_update\",\"merchant_delivery_charge_delete\",\"merchant_shop_read\",\"merchant_shop_create\",\"merchant_shop_update\",\"merchant_shop_delete\",\"merchant_payment_read\",\"merchant_payment_create\",\"merchant_payment_update\",\"merchant_payment_delete\",\"payment_read\",\"payment_create\",\"payment_update\",\"payment_delete\",\"payment_reject\",\"payment_process\",\"hub_payment_read\",\"hub_payment_create\",\"hub_payment_update\",\"hub_payment_delete\",\"hub_payment_reject\",\"hub_payment_process\",\"hub_payment_request_read\",\"hub_payment_request_create\",\"hub_payment_request_update\",\"hub_payment_request_delete\",\"parcel_read\",\"parcel_create\",\"parcel_update\",\"parcel_delete\",\"parcel_status_update\",\"delivery_man_read\",\"delivery_man_create\",\"delivery_man_update\",\"delivery_man_delete\",\"delivery_category_read\",\"delivery_category_create\",\"delivery_category_update\",\"delivery_category_delete\",\"delivery_charge_read\",\"delivery_charge_create\",\"delivery_charge_update\",\"delivery_charge_delete\",\"delivery_type_read\",\"delivery_type_status_change\",\"liquid_fragile_read\",\"liquid_fragile_update\",\"liquid_status_change\",\"packaging_read\",\"packaging_create\",\"packaging_update\",\"packaging_delete\",\"category_read\",\"category_create\",\"category_update\",\"category_delete\",\"account_heads_read\",\"database_backup_read\",\"salary_read\",\"salary_create\",\"salary_update\",\"salary_delete\",\"support_read\",\"support_create\",\"support_update\",\"support_delete\",\"support_reply\",\"support_status_update\",\"sms_settings_read\",\"sms_settings_create\",\"sms_settings_update\",\"sms_settings_delete\",\"sms_send_settings_read\",\"sms_send_settings_create\",\"sms_send_settings_update\",\"sms_send_settings_delete\",\"general_settings_read\",\"general_settings_update\",\"notification_settings_read\",\"notification_settings_update\",\"push_notification_read\",\"push_notification_create\",\"push_notification_update\",\"push_notification_delete\",\"asset_category_read\",\"asset_category_create\",\"asset_category_update\",\"asset_category_delete\",\"news_offer_read\",\"news_offer_create\",\"news_offer_update\",\"news_offer_delete\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"salary_generate_create\",\"salary_generate_update\",\"salary_generate_delete\",\"assets_read\",\"assets_create\",\"assets_update\",\"assets_delete\",\"fraud_read\",\"fraud_create\",\"fraud_update\",\"fraud_delete\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\",\"invoice_read\",\"invoice_status_update\",\"social_login_settings_read\",\"social_login_settings_update\",\"payout_setup_settings_read\",\"payout_setup_settings_update\",\"online_payment_read\",\"payout_read\",\"payout_create\",\"hub_view\",\"paid_invoice_read\",\"invoice_generate_menually\",\"currency_read\",\"currency_create\",\"currency_update\",\"currency_delete\",\"social_link_read\",\"social_link_create\",\"social_link_update\",\"social_link_delete\",\"service_read\",\"service_create\",\"service_update\",\"service_delete\",\"why_courier_read\",\"why_courier_create\",\"why_courier_update\",\"why_courier_delete\",\"faq_read\",\"faq_create\",\"faq_update\",\"faq_delete\",\"partner_read\",\"partner_create\",\"partner_update\",\"partner_delete\",\"blogs_read\",\"blogs_create\",\"blogs_update\",\"blogs_delete\",\"pages_read\",\"pages_update\",\"section_read\",\"section_update\",\"mail_settings_read\",\"mail_settings_update\",\"wallet_request_read\",\"wallet_request_create\",\"wallet_request_delete\",\"wallet_request_approve\",\"wallet_request_reject\"]',1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(2,'Admin','admin','[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_incharge_read\",\"account_read\",\"income_read\",\"expense_read\",\"todo_read\",\"sms_settings_read\",\"sms_send_settings_read\",\"general_settings_read\",\"notification_settings_read\",\"push_notification_read\",\"push_notification_create\",\"push_notification_update\",\"push_notification_delete\",\"account_heads_read\",\"salary_read\",\"support_read\",\"fund_transfer_read\",\"role_read\",\"designation_read\",\"department_read\",\"user_read\",\"merchant_read\",\"merchant_delivery_charge_read\",\"merchant_shop_read\",\"merchant_payment_read\",\"payment_read\",\"hub_payment_request_read\",\"hub_payment_read\",\"parcel_read\",\"delivery_man_read\",\"delivery_category_read\",\"delivery_charge_read\",\"delivery_type_read\",\"liquid_fragile_read\",\"packaging_read\",\"category_read\",\"asset_category_read\",\"news_offer_read\",\"sms_settings_status_change\",\"sms_send_settings_status_change\",\"bank_transaction_read\",\"database_backup_read\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"assets_read\",\"fraud_read\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\",\"cash_received_from_delivery_man_read\",\"cash_received_from_delivery_man_create\",\"cash_received_from_delivery_man_update\",\"cash_received_from_delivery_man_delete\",\"invoice_read\",\"invoice_status_update\",\"social_login_settings_read\",\"social_login_settings_update\",\"payout_setup_settings_read\",\"online_payment_read\",\"payout_read\",\"hub_view\",\"paid_invoice_read\",\"invoice_generate_menually\",\"currency_read\"]',1,'2025-06-29 11:29:43','2025-06-29 11:29:43');
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `routes`
--

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

--
-- Dumping data for table `routes`
--

LOCK TABLES `routes` WRITE;
/*!40000 ALTER TABLE `routes` DISABLE KEYS */;
/*!40000 ALTER TABLE `routes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salaries`
--

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

--
-- Dumping data for table `salaries`
--

LOCK TABLES `salaries` WRITE;
/*!40000 ALTER TABLE `salaries` DISABLE KEYS */;
/*!40000 ALTER TABLE `salaries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `salary_generates`
--

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

--
-- Dumping data for table `salary_generates`
--

LOCK TABLES `salary_generates` WRITE;
/*!40000 ALTER TABLE `salary_generates` DISABLE KEYS */;
/*!40000 ALTER TABLE `salary_generates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `scan_events`
--

DROP TABLE IF EXISTS `scan_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `scan_events` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sscc` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('ARRIVE','SORT','LOAD','DEPART','IN_TRANSIT','CUSTOMS_HOLD','CUSTOMS_CLEARED','ARRIVE_DEST','OUT_FOR_DELIVERY','DELIVERED','RETURN_TO_SENDER','DAMAGED') COLLATE utf8mb4_unicode_ci NOT NULL,
  `branch_id` bigint unsigned NOT NULL,
  `leg_id` bigint unsigned DEFAULT NULL,
  `user_id` bigint unsigned NOT NULL,
  `occurred_at` timestamp NOT NULL,
  `geojson` json DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `scan_events_branch_id_foreign` (`branch_id`),
  CONSTRAINT `scan_events_branch_id_foreign` FOREIGN KEY (`branch_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `scan_events`
--

LOCK TABLES `scan_events` WRITE;
/*!40000 ALTER TABLE `scan_events` DISABLE KEYS */;
/*!40000 ALTER TABLE `scan_events` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sections`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sections`
--

LOCK TABLES `sections` WRITE;
/*!40000 ALTER TABLE `sections` DISABLE KEYS */;
INSERT INTO `sections` VALUES (1,1,'title_1','WE PROVIDE','2023-01-27 16:30:40','2023-01-27 16:30:40'),(2,1,'title_2','HASSLE FREE','2023-01-27 16:30:40','2023-01-27 16:30:40'),(3,1,'title_3','FASTEST DELIVERY','2023-01-27 16:30:40','2023-01-27 16:30:40'),(4,1,'sub_title','We Committed to delivery - Make easy Efficient and quality delivery.','2023-01-27 16:30:40','2023-01-27 16:30:40'),(5,1,'banner','33','2023-01-27 16:30:40','2025-06-29 13:06:36'),(6,2,'branch_icon','fa fa-warehouse','2023-01-27 16:30:40','2023-01-27 16:30:40'),(7,2,'branch_count','7520','2023-01-27 16:30:40','2023-01-27 16:30:40'),(8,2,'branch_title','Branches','2023-01-27 16:30:40','2023-01-27 16:30:40'),(9,2,'parcel_icon','fa fa-gifts','2023-01-27 16:30:40','2023-01-27 16:30:40'),(10,2,'parcel_count','50000000','2023-01-27 16:30:40','2023-01-27 16:30:40'),(11,2,'parcel_title','Parcel Delivered','2023-01-27 16:30:40','2023-01-27 16:30:40'),(12,2,'merchant_icon','fa fa-users','2023-01-27 16:30:40','2023-01-27 16:30:40'),(13,2,'merchant_count','400000','2023-01-27 16:30:40','2023-01-27 16:30:40'),(14,2,'merchant_title','Happy Merchant','2023-01-27 16:30:40','2023-01-27 16:30:40'),(15,2,'reviews_icon','fa fa-thumbs-up','2023-01-27 16:30:40','2023-01-27 16:30:40'),(16,2,'reviews_count','700','2023-01-27 16:30:40','2023-01-27 16:30:40'),(17,2,'reviews_title','Positive Reviews','2023-01-27 16:30:40','2023-01-27 16:30:40'),(18,3,'about_us','Fastest platform with all courier service features. Help you start, run and grow your courier service.','2023-01-27 16:30:40','2023-01-27 16:30:40'),(19,4,'subscribe_title','Subscribe Us','2023-01-27 16:30:40','2023-01-27 16:30:40'),(20,4,'subscribe_description','Get business news , tip and solutions to your problems our experts.','2023-01-27 16:30:40','2023-01-27 16:30:40'),(21,5,'playstore_icon','fa-brands fa-google-play','2023-01-27 16:30:40','2023-01-27 16:30:40'),(22,5,'playstore_link','https://drive.google.com/drive/folders/1jLe_s4F-HDSjI7dHPsen7vRUw2wv9SMi','2023-01-27 16:30:40','2023-01-27 16:30:40'),(23,5,'ios_icon','fa-brands fa-app-store-ios','2023-01-27 16:30:40','2023-01-27 16:30:40'),(24,5,'ios_link','https://drive.google.com/drive/folders/1jLe_s4F-HDSjI7dHPsen7vRUw2wv9SMi','2023-01-27 16:30:40','2023-01-27 16:30:40'),(25,6,'map_link','https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d542.6581052086841!2d90.3516149889463!3d23.798889773393963!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755c0e8a725cb8b%3A0x5a69b65edf9c7cf4!2sWemax%20IT!5e0!3m2!1sen!2sbd!4v1687082326781!5m2!1sen!2sbd','2023-01-27 16:30:40','2023-01-27 16:30:40');
/*!40000 ALTER TABLE `sections` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `services`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `services`
--

LOCK TABLES `services` WRITE;
/*!40000 ALTER TABLE `services` DISABLE KEYS */;
INSERT INTO `services` VALUES (1,'E-Commerce delivery',10,'Culpa dolores voluptatibus voluptas qui earum et autem impedit distinctio voluptates maiores quos et minus ipsam nesciunt dolor tempore voluptatem eum voluptas accusantium voluptatum voluptatem laborum laborum molestiae asperiores numquam vitae eum sunt recusandae ut magni eaque ipsa assumenda doloremque sint nihil quia ratione magni quibusdam error dicta earum suscipit occaecati ea ut dolorum debitis tempore fuga praesentium.','1',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(2,'Pick & Drop',11,'In soluta et tempora praesentium nam vel excepturi mollitia repellat aperiam dolorem nemo voluptatem cumque et et quis adipisci tempore consectetur iste quia atque perferendis necessitatibus quasi ratione facilis dolor rerum ducimus fugit et aut impedit porro aut quia aspernatur ut modi voluptatibus animi tenetur enim aut ea exercitationem eos quia et sunt tempore necessitatibus ipsam optio doloremque veritatis assumenda quia non perspiciatis illo amet eaque in.','2',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(3,'Packageing',12,'Et esse inventore nam voluptate ea laboriosam quas ducimus maxime fugit enim commodi inventore labore et est dolores itaque a et exercitationem laboriosam architecto necessitatibus cum voluptatem doloribus dolore exercitationem vitae facere tempora atque officia ea a vero voluptatibus optio deleniti fuga provident qui veritatis est dolor dicta sint blanditiis nobis error placeat qui et necessitatibus quod reprehenderit dicta magnam et ab necessitatibus dicta aut et nihil.','3',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(4,'Warehousing',13,'Mollitia culpa esse quia molestias eligendi sunt vel cum tempore voluptatem voluptas enim fugiat doloremque voluptas beatae deserunt ducimus est qui explicabo qui reiciendis in velit optio et dignissimos temporibus corporis et ab rerum in nostrum eligendi neque et modi aut dolorem est.','4',1,'2025-06-29 11:29:48','2025-06-29 11:29:48');
/*!40000 ALTER TABLE `services` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

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

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settings`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `settings`
--

LOCK TABLES `settings` WRITE;
/*!40000 ALTER TABLE `settings` DISABLE KEYS */;
INSERT INTO `settings` VALUES (1,'facebook_client_id','facebook client id','2025-06-29 11:29:47','2025-06-29 11:29:47'),(2,'facebook_client_secret','client secret','2025-06-29 11:29:47','2025-06-29 11:29:47'),(3,'facebook_status','1','2025-06-29 11:29:47','2025-06-29 11:29:47'),(4,'google_client_id','client id','2025-06-29 11:29:47','2025-06-29 11:29:47'),(5,'google_client_secret','client secret','2025-06-29 11:29:47','2025-06-29 11:29:47'),(6,'google_status','1','2025-06-29 11:29:47','2025-06-29 11:29:47'),(7,'stripe_publishable_key','publishable key','2025-06-29 11:29:47','2025-06-29 11:29:47'),(8,'stripe_secret_key','secret key','2025-06-29 11:29:47','2025-06-29 11:29:47'),(9,'stripe_status','0','2025-06-29 11:29:47','2025-06-29 12:28:00'),(10,'razorpay_key','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(11,'razorpay_secret','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(12,'razorpay_status','0','2025-06-29 11:29:47','2025-06-29 12:28:10'),(13,'sslcommerz_store_id','store id','2025-06-29 11:29:47','2025-06-29 11:29:47'),(14,'sslcommerz_store_password','store password','2025-06-29 11:29:47','2025-06-29 11:29:47'),(15,'sslcommerz_testmode','0','2025-06-29 11:29:47','2025-06-29 12:28:33'),(16,'sslcommerz_status','0','2025-06-29 11:29:47','2025-06-29 12:28:33'),(17,'paypal_client_id','client id','2025-06-29 11:29:47','2025-06-29 11:29:47'),(18,'paypal_client_secret','client secret','2025-06-29 11:29:47','2025-06-29 11:29:47'),(19,'paypal_mode','sendbox','2025-06-29 11:29:47','2025-06-29 11:29:47'),(20,'paypal_status','1','2025-06-29 11:29:47','2025-06-29 11:29:47'),(21,'skrill_merchant_email','demoqco@sun-fish.com','2025-06-29 11:29:47','2025-06-29 11:29:47'),(22,'skrill_status','0','2025-06-29 11:29:47','2025-06-29 12:27:55'),(23,'bkash_app_id','application id','2025-06-29 11:29:47','2025-06-29 11:29:47'),(24,'bkash_app_secret','application secret key','2025-06-29 11:29:47','2025-06-29 11:29:47'),(25,'bkash_username','username','2025-06-29 11:29:47','2025-06-29 11:29:47'),(26,'bkash_password','password','2025-06-29 11:29:48','2025-06-29 11:29:48'),(27,'bkash_test_mode','0','2025-06-29 11:29:48','2025-06-29 12:28:43'),(28,'bkash_status','0','2025-06-29 11:29:48','2025-06-29 12:28:43'),(29,'aamarpay_store_id','aamarypay','2025-06-29 11:29:48','2025-06-29 11:29:48'),(30,'aamarpay_signature_key','28c78bb1f45112f5d40b956fe104645a','2025-06-29 11:29:48','2025-06-29 11:29:48'),(31,'aamarpay_sendbox_mode','0','2025-06-29 11:29:48','2025-06-29 12:28:23'),(32,'aamarpay_status','0','2025-06-29 11:29:48','2025-06-29 12:28:23');
/*!40000 ALTER TABLE `settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `settlement_cycles`
--

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

--
-- Dumping data for table `settlement_cycles`
--

LOCK TABLES `settlement_cycles` WRITE;
/*!40000 ALTER TABLE `settlement_cycles` DISABLE KEYS */;
/*!40000 ALTER TABLE `settlement_cycles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipment_logs`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipment_logs`
--

LOCK TABLES `shipment_logs` WRITE;
/*!40000 ALTER TABLE `shipment_logs` DISABLE KEYS */;
INSERT INTO `shipment_logs` VALUES (1,2,'created','Shipment created at origin branch','Riyadh',8,NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26','2025-11-06 05:33:26'),(2,2,'ready_for_pickup','Courier assigned for pickup','Riyadh',8,NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26','2025-11-06 05:33:26'),(3,2,'in_transit','Arrived at regional sorting','Riyadh',8,NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26','2025-11-06 05:33:26'),(4,2,'out_for_delivery','Courier en route to recipient','Riyadh',8,NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26','2025-11-06 05:33:26'),(5,3,'created','Shipment created at origin branch','Riyadh',8,NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09','2025-11-06 05:40:09'),(6,3,'ready_for_pickup','Courier assigned for pickup','Riyadh',8,NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09','2025-11-06 05:40:09'),(7,3,'in_transit','Arrived at regional sorting','Riyadh',8,NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09','2025-11-06 05:40:09'),(8,3,'out_for_delivery','Courier en route to recipient','Riyadh',8,NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09','2025-11-06 05:40:09');
/*!40000 ALTER TABLE `shipment_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `shipments`
--

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
  `delivered_by` bigint unsigned DEFAULT NULL,
  `tracking_number` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('created','ready_for_pickup','in_transit','arrived_at_hub','out_for_delivery','delivered','exception','cancelled') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'created',
  `has_exception` tinyint(1) NOT NULL DEFAULT '0',
  `exception_type` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exception_severity` enum('low','medium','high') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `exception_notes` text COLLATE utf8mb4_unicode_ci,
  `exception_occurred_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `service_level` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `priority` int NOT NULL DEFAULT '1',
  `incoterm` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_status` enum('CREATED','CONFIRMED','ASSIGNED','PICKED_UP','IN_TRANSIT','OUT_FOR_DELIVERY','DELIVERED','CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'CREATED',
  `assigned_at` timestamp NULL DEFAULT NULL,
  `hub_processed_at` timestamp NULL DEFAULT NULL,
  `transferred_at` timestamp NULL DEFAULT NULL,
  `picked_up_at` timestamp NULL DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `expected_delivery_date` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `returned_at` timestamp NULL DEFAULT NULL,
  `return_reason` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `return_notes` text COLLATE utf8mb4_unicode_ci,
  `metadata` json DEFAULT NULL,
  `public_token` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shipments_transfer_hub_id_index` (`transfer_hub_id`),
  KEY `shipments_delivered_by_index` (`delivered_by`),
  KEY `shipments_has_exception_index` (`has_exception`),
  KEY `shipments_priority_index` (`priority`),
  KEY `shipments_hub_processed_at_index` (`hub_processed_at`),
  KEY `shipments_exception_occurred_at_index` (`exception_occurred_at`),
  KEY `shipments_assigned_at_index` (`assigned_at`),
  KEY `shipments_delivered_at_index` (`delivered_at`),
  KEY `shipments_tracking_number_index` (`tracking_number`),
  KEY `shipments_assigned_worker_id_index` (`assigned_worker_id`),
  CONSTRAINT `shipments_delivered_by_foreign` FOREIGN KEY (`delivered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_transfer_hub_id_foreign` FOREIGN KEY (`transfer_hub_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=40 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipments`
--

LOCK TABLES `shipments` WRITE;
/*!40000 ALTER TABLE `shipments` DISABLE KEYS */;
INSERT INTO `shipments` VALUES (1,1,10,3,2,NULL,1,NULL,'K7VP2D2XVQWJ','out_for_delivery',0,NULL,NULL,NULL,NULL,7,'STANDARD',1,'DAP',49.99,'USD','OUT_FOR_DELIVERY','2025-11-05 05:32:59',NULL,NULL,NULL,NULL,'2025-11-07 05:32:59',NULL,NULL,NULL,NULL,'{\"package_count\": 3}','eyJpdiI6IjQzRTdVVTFqRTBuT2Q1M21Jck41Z2c9PSIsInZhbHVlIjoiZ3RHNms2eUFvM2FTenFocVRuSjc1dz09IiwibWFjIjoiZDQ2NmVkNjU2ZmFlNjQ2MjkwMDBiYjZjYjkyNTJjMjE1ZjkwYzk3NGVkNmVlYWJkNDFiMjZlYzUwMzQzZmVjZSIsInR',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(2,1,10,3,2,NULL,1,NULL,'MVGAKOLMJBCQ','out_for_delivery',0,NULL,NULL,NULL,NULL,7,'STANDARD',1,'DAP',49.99,'USD','OUT_FOR_DELIVERY','2025-11-05 05:33:26',NULL,NULL,NULL,NULL,'2025-11-07 05:33:26',NULL,NULL,NULL,NULL,'{\"package_count\": 3}','eyJpdiI6Ik9HVGppdEhHNXVQeHlJV3grRXM3aGc9PSIsInZhbHVlIjoiRDc4MS82SHhlME9xY3luQVdaSUJ5dz09IiwibWFjIjoiZDkzZDBmZTgyNDlhMzRjNDcwZmJhMTE0YWU1NjhhY2UwMDdlMTZlNDJhZjNlYmIzYzA3ZGMxZTI2NGI5ZTRjNCIsInR',NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26'),(3,1,10,3,2,NULL,1,NULL,'P1UR2QK1YLGI','out_for_delivery',0,NULL,NULL,NULL,NULL,7,'STANDARD',1,'DAP',49.99,'USD','OUT_FOR_DELIVERY','2025-11-05 05:40:09',NULL,NULL,NULL,NULL,'2025-11-07 05:40:09',NULL,NULL,NULL,NULL,'{\"package_count\": 3}','eyJpdiI6IkQrZlJaaGdXbTZWZnhsYTZFbjBoMkE9PSIsInZhbHVlIjoiTDdRQzBHSW04dXlxQWhodTlxVFhOdz09IiwibWFjIjoiOTU2MzQ0MDdiOGM4MGE3ZDNlMWExZTE0ZTgyZDI2YmFhZGUzZjM2ZTAwYTNjYWY5NDU2MzhiM2EyNzU5YjRlMCIsInR',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09');
/*!40000 ALTER TABLE `shipments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_send_settings`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_send_settings`
--

LOCK TABLES `sms_send_settings` WRITE;
/*!40000 ALTER TABLE `sms_send_settings` DISABLE KEYS */;
INSERT INTO `sms_send_settings` VALUES (1,1,0,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(2,2,0,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(3,3,0,'2025-06-29 11:29:47','2025-06-29 11:29:47');
/*!40000 ALTER TABLE `sms_send_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sms_settings`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sms_settings`
--

LOCK TABLES `sms_settings` WRITE;
/*!40000 ALTER TABLE `sms_settings` DISABLE KEYS */;
INSERT INTO `sms_settings` VALUES (1,'reve_api_key','Your API key','2025-06-29 11:29:46','2025-06-29 11:29:46'),(2,'reve_secret_key','Your secret key','2025-06-29 11:29:46','2025-06-29 11:29:46'),(3,'reve_api_url','http://smpp.ajuratech.com:7788/sendtext','2025-06-29 11:29:47','2025-06-29 11:29:47'),(4,'reve_username','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(5,'reve_user_password','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(6,'reve_status','0','2025-06-29 11:29:47','2025-06-29 11:29:47'),(7,'twilio_sid','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(8,'twilio_token','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(9,'twilio_from','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(10,'twilio_status','0','2025-06-29 11:29:47','2025-06-29 11:29:47'),(11,'nexmo_key','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(12,'nexmo_secret_key','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(13,'nexmo_status','0','2025-06-29 11:29:47','2025-06-29 11:29:47'),(14,'click_send_username','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(15,'click_send_api_key','','2025-06-29 11:29:47','2025-06-29 11:29:47'),(16,'click_send_status','0','2025-06-29 11:29:47','2025-06-29 11:29:47');
/*!40000 ALTER TABLE `sms_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `social_links`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `social_links`
--

LOCK TABLES `social_links` WRITE;
/*!40000 ALTER TABLE `social_links` DISABLE KEYS */;
INSERT INTO `social_links` VALUES (1,'facebook','fab fa-facebook-square','https://www.facebook.com','1',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(2,'Instagram','fab fa-instagram','https://www.instagram.com','2',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(3,'Twitter','fab fa-twitter','https://www.twitter.com','3',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(4,'Youtube','fab fa-youtube','https://www.youtube.com','4',0,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(5,'Whatsapp','fab fa-whatsapp','https://www.whatsapp.com','5',0,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(6,'Skype','fab fa-skype','https://www.skype.com','6',1,'2025-06-29 11:29:48','2025-06-29 11:29:48');
/*!40000 ALTER TABLE `social_links` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sortation_bins`
--

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

--
-- Dumping data for table `sortation_bins`
--

LOCK TABLES `sortation_bins` WRITE;
/*!40000 ALTER TABLE `sortation_bins` DISABLE KEYS */;
/*!40000 ALTER TABLE `sortation_bins` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stops`
--

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

--
-- Dumping data for table `stops`
--

LOCK TABLES `stops` WRITE;
/*!40000 ALTER TABLE `stops` DISABLE KEYS */;
/*!40000 ALTER TABLE `stops` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `subscribes`
--

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

--
-- Dumping data for table `subscribes`
--

LOCK TABLES `subscribes` WRITE;
/*!40000 ALTER TABLE `subscribes` DISABLE KEYS */;
/*!40000 ALTER TABLE `subscribes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `support_chats`
--

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

--
-- Dumping data for table `support_chats`
--

LOCK TABLES `support_chats` WRITE;
/*!40000 ALTER TABLE `support_chats` DISABLE KEYS */;
/*!40000 ALTER TABLE `support_chats` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supports`
--

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

--
-- Dumping data for table `supports`
--

LOCK TABLES `supports` WRITE;
/*!40000 ALTER TABLE `supports` DISABLE KEYS */;
/*!40000 ALTER TABLE `supports` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `surcharge_rules`
--

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

--
-- Dumping data for table `surcharge_rules`
--

LOCK TABLES `surcharge_rules` WRITE;
/*!40000 ALTER TABLE `surcharge_rules` DISABLE KEYS */;
/*!40000 ALTER TABLE `surcharge_rules` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `surveys`
--

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

--
-- Dumping data for table `surveys`
--

LOCK TABLES `surveys` WRITE;
/*!40000 ALTER TABLE `surveys` DISABLE KEYS */;
/*!40000 ALTER TABLE `surveys` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `tasks`
--

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

--
-- Dumping data for table `tasks`
--

LOCK TABLES `tasks` WRITE;
/*!40000 ALTER TABLE `tasks` DISABLE KEYS */;
/*!40000 ALTER TABLE `tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `to_dos`
--

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

--
-- Dumping data for table `to_dos`
--

LOCK TABLES `to_dos` WRITE;
/*!40000 ALTER TABLE `to_dos` DISABLE KEYS */;
/*!40000 ALTER TABLE `to_dos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `transport_legs`
--

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

--
-- Dumping data for table `transport_legs`
--

LOCK TABLES `transport_legs` WRITE;
/*!40000 ALTER TABLE `transport_legs` DISABLE KEYS */;
/*!40000 ALTER TABLE `transport_legs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `uploads`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `uploads`
--

LOCK TABLES `uploads` WRITE;
/*!40000 ALTER TABLE `uploads` DISABLE KEYS */;
INSERT INTO `uploads` VALUES (1,'uploads/users/user.png',NULL,NULL,NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(2,'uploads/users/user2.png',NULL,NULL,NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(3,'uploads/users/user3.png',NULL,NULL,NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(4,'uploads/users/user4.png',NULL,NULL,NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(5,'uploads/users/user5.png',NULL,NULL,NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(6,'uploads/users/user6.png',NULL,NULL,NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(7,'uploads/users/user7.png',NULL,NULL,NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(8,'uploads/settings/202506291450101536.png',NULL,NULL,NULL,'2025-06-29 11:29:47','2025-06-29 12:50:10'),(9,'uploads/users/user9.png',NULL,NULL,NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(10,'frontend/images/services/truck.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(11,'frontend/images/services/pick-drop.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(12,'frontend/images/services/packageing.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(13,'frontend/images/services/warehouse.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(14,'frontend/images/whycourier/timly-delivery.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(15,'frontend/images/whycourier/limitless-pickup.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(16,'frontend/images/whycourier/cash-on-delivery.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(17,'frontend/images/whycourier/payment.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(18,'frontend/images/whycourier/handling.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(19,'frontend/images/whycourier/live-tracking.png',NULL,NULL,NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(20,'frontend/images/partner/1.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(21,'frontend/images/partner/atom.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(22,'frontend/images/partner/digg.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(23,'frontend/images/partner/2.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(24,'frontend/images/partner/huawei.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(25,'frontend/images/partner/ups.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(26,'frontend/images/partner/1.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(27,'frontend/images/partner/atom.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(28,'frontend/images/partner/digg.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(29,'frontend/images/partner/2.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(30,'frontend/images/partner/huawei.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(31,'frontend/images/partner/ups.png',NULL,NULL,NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(32,'uploads/settings/202506291459237515.png',NULL,NULL,NULL,'2025-06-29 12:59:23','2025-06-29 12:59:23'),(33,'uploads/section/20250629150636.png',NULL,NULL,NULL,'2025-06-29 13:06:36','2025-06-29 13:06:36');
/*!40000 ALTER TABLE `uploads` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user_consents`
--

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

--
-- Dumping data for table `user_consents`
--

LOCK TABLES `user_consents` WRITE;
/*!40000 ALTER TABLE `user_consents` DISABLE KEYS */;
/*!40000 ALTER TABLE `user_consents` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

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
  CONSTRAINT `users_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_designation_id_foreign` FOREIGN KEY (`designation_id`) REFERENCES `designations` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_hub_id_foreign` FOREIGN KEY (`hub_id`) REFERENCES `hubs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_image_id_foreign` FOREIGN KEY (`image_id`) REFERENCES `uploads` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `users_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=146 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'baraka Administrator','info@baraka.co',NULL,'$2y$10$qwObLjayCQkTxwXqm.zGZeHVpElS5Q.c3zeL2OA2uVY2QoI8lIhpa','256706215487','eyJpdiI6IkVqdUdkNmtwVXJRT0VDTmgzcDdXUFE9PSIsInZhbHVlIjoid2hxMXQzNzh4YU15ejhRTk9aZVl6Mkl5ZU8zb0thdGFFc2xyVGE2anBqMD0iLCJtYWMiOiI0ZjVkMzNiODlkNWIxY2JhMzIyMzBkM2I1NDk4Y2Q0NTk4ZGJkMGM1YzBlYTEwYjd','121212',NULL,NULL,NULL,1,NULL,'1999-11-22','vbcvb',1,'[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_create\",\"hub_update\",\"hub_delete\",\"hub_incharge_read\",\"hub_incharge_create\",\"hub_incharge_update\",\"hub_incharge_delete\",\"hub_incharge_assigned\",\"account_read\",\"account_create\",\"account_update\",\"account_delete\",\"income_read\",\"income_create\",\"income_update\",\"income_delete\",\"expense_read\",\"expense_create\",\"expense_update\",\"expense_delete\",\"todo_read\",\"todo_create\",\"todo_update\",\"todo_delete\",\"fund_transfer_read\",\"fund_transfer_create\",\"fund_transfer_update\",\"fund_transfer_delete\",\"role_read\",\"role_create\",\"role_update\",\"role_delete\",\"designation_read\",\"designation_create\",\"designation_update\",\"designation_delete\",\"department_read\",\"department_create\",\"department_update\",\"department_delete\",\"user_read\",\"user_create\",\"user_update\",\"user_delete\",\"permission_update\",\"merchant_read\",\"merchant_create\",\"merchant_update\",\"merchant_delete\",\"merchant_view\",\"merchant_delivery_charge_read\",\"merchant_delivery_charge_create\",\"merchant_delivery_charge_update\",\"merchant_delivery_charge_delete\",\"merchant_shop_read\",\"merchant_shop_create\",\"merchant_shop_update\",\"merchant_shop_delete\",\"merchant_payment_read\",\"merchant_payment_create\",\"merchant_payment_update\",\"merchant_payment_delete\",\"payment_read\",\"payment_create\",\"payment_update\",\"payment_delete\",\"payment_reject\",\"payment_process\",\"hub_payment_read\",\"hub_payment_create\",\"hub_payment_update\",\"hub_payment_delete\",\"hub_payment_reject\",\"hub_payment_process\",\"hub_payment_request_read\",\"hub_payment_request_create\",\"hub_payment_request_update\",\"hub_payment_request_delete\",\"parcel_read\",\"parcel_create\",\"parcel_update\",\"parcel_delete\",\"parcel_status_update\",\"delivery_man_read\",\"delivery_man_create\",\"delivery_man_update\",\"delivery_man_delete\",\"delivery_category_read\",\"delivery_category_create\",\"delivery_category_update\",\"delivery_category_delete\",\"delivery_charge_read\",\"delivery_charge_create\",\"delivery_charge_update\",\"delivery_charge_delete\",\"delivery_type_read\",\"delivery_type_status_change\",\"liquid_fragile_read\",\"liquid_fragile_update\",\"liquid_status_change\",\"packaging_read\",\"packaging_create\",\"packaging_update\",\"packaging_delete\",\"category_read\",\"category_create\",\"category_update\",\"category_delete\",\"account_heads_read\",\"database_backup_read\",\"salary_read\",\"salary_create\",\"salary_update\",\"salary_delete\",\"support_read\",\"support_create\",\"support_update\",\"support_delete\",\"support_reply\",\"support_status_update\",\"sms_settings_read\",\"sms_settings_create\",\"sms_settings_update\",\"sms_settings_delete\",\"sms_send_settings_read\",\"sms_send_settings_create\",\"sms_send_settings_update\",\"sms_send_settings_delete\",\"general_settings_read\",\"general_settings_update\",\"notification_settings_read\",\"notification_settings_update\",\"push_notification_read\",\"push_notification_create\",\"push_notification_update\",\"push_notification_delete\",\"asset_category_read\",\"asset_category_create\",\"asset_category_update\",\"asset_category_delete\",\"news_offer_read\",\"news_offer_create\",\"news_offer_update\",\"news_offer_delete\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"salary_generate_create\",\"salary_generate_update\",\"salary_generate_delete\",\"assets_read\",\"assets_create\",\"assets_update\",\"assets_delete\",\"fraud_read\",\"fraud_create\",\"fraud_update\",\"fraud_delete\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\",\"invoice_read\",\"invoice_status_update\",\"social_login_settings_read\",\"social_login_settings_update\",\"payout_setup_settings_read\",\"payout_setup_settings_update\",\"online_payment_read\",\"payout_read\",\"payout_create\",\"hub_view\",\"paid_invoice_read\",\"invoice_generate_menually\",\"currency_read\",\"currency_create\",\"currency_update\",\"currency_delete\",\"social_link_read\",\"social_link_create\",\"social_link_update\",\"social_link_delete\",\"service_read\",\"service_create\",\"service_update\",\"service_delete\",\"why_courier_read\",\"why_courier_create\",\"why_courier_update\",\"why_courier_delete\",\"faq_read\",\"faq_create\",\"faq_update\",\"faq_delete\",\"partner_read\",\"partner_create\",\"partner_update\",\"partner_delete\",\"blogs_read\",\"blogs_create\",\"blogs_update\",\"blogs_delete\",\"pages_read\",\"pages_update\",\"section_read\",\"section_update\",\"mail_settings_read\",\"mail_settings_update\",\"wallet_request_read\",\"wallet_request_create\",\"wallet_request_delete\",\"wallet_request_approve\",\"wallet_request_reject\"]',NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:20:48','2025-11-06 10:13:42'),(2,'sanaa Administrator','info@sanaa.co',NULL,'$2y$10$/AWbM.ah1EyUvq9aQ17g8Oy70q42pU4i.oVI79eqiq4tcvFT/i1Mm',NULL,'eyJpdiI6IllHMUxqaVg4VEtiTjBlVUpGTVVwN2c9PSIsInZhbHVlIjoiWHVxOVZkT3BUQXN1TTgwYjVMczFHeko2RXRsRmdOcm1iRmVZN3daamN1OD0iLCJtYWMiOiI5MTQ4Y2I3N2JiODMxZGQ1MGJkMjU3MmJjMTQzMjQwOTMwNzY0ZTc5OGI4MzllYTZ',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:21:02','2025-11-06 05:21:02'),(3,'Delivery Man','deliveryman@wemaxit.com',NULL,'$2y$10$/E4iuNJY3x92Oy0.4ypK7uWbCvscUqxWWHIwkmnAtVDx6pErz105y','01912938004',NULL,NULL,NULL,NULL,1,3,3,NULL,'Mirpur-2,Dhaka',NULL,NULL,NULL,NULL,7000.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(4,'Merchant','merchant@wemaxdevs.com',NULL,'$2y$10$XhqKQRBjsRn0zeao3VhKOu/EMfKW8d5oVy1yqctOOgYMQn4ZWSiCa','01912938003',NULL,NULL,NULL,NULL,4,2,2,NULL,'Mirpur-2,Dhaka',NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(5,'Dennis Carroll','hupazef@mailinator.com',NULL,'$2y$10$t.rI6vTeUl1CepxBssA3wOEqC0wtU9jh/iFXdaWitQTOe4XYpHdLW','256702568978',NULL,NULL,NULL,NULL,3,2,NULL,NULL,'Irure aliquid porro',NULL,NULL,NULL,NULL,0.00,NULL,'dXw4xjzjtffFDb30duHn_h:APA91bGnizMRdXW-fQT1Q6ZKjImEtOtHtJQPU0bj5eDbLjHKJTGy6zXYKLqRIe_tSklfaKhCiZY0KhPzOqGUPaSvIOlUzk5nVxbHm0Kaa2y8H62QkMSlz5E',1,1,NULL,NULL,NULL,'2025-07-05 11:38:06','2025-07-29 06:25:42'),(6,'Raymond Mccray',NULL,NULL,'$2y$10$NdttmZ16zrgAS9ZWqzhP4OjNrUn5GXTheFHq.sWOzucWng2SZKndu','2567056567989',NULL,NULL,NULL,NULL,2,2,NULL,NULL,NULL,NULL,'[]',NULL,72736,0.00,NULL,NULL,1,0,NULL,NULL,NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(7,'System Admin','admin@example.com',NULL,'$2y$10$GXuAR3zspMhkOpHIf.7SWOFcrMf7aVDt2qAymncIz/OdsXRwPcrTG',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:32:59','2025-11-06 05:40:09'),(8,'Branch Manager','manager@example.com',NULL,'$2y$10$PCUFT14GuXtY379qW4NULu.LOwmOYxWfa2GhwMlFEq7IoQPPYU6nC',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:32:59','2025-11-06 05:40:09'),(9,'Branch Worker','worker@example.com',NULL,'$2y$10$ztsvFtoGaHbeneyuGpb5Lub1QMPyPVu.OeyNEhR0VAT//Cwp73c3G',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:32:59','2025-11-06 05:40:09'),(10,'Client Contact','client@example.com',NULL,'$2y$10$ljDAwNJ.iwdOkacxAEZoD.sJElt8ncugU0UiXQ1GcdwJG9BM6.LkO',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:32:59','2025-11-06 05:40:09');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vat_statements`
--

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

--
-- Dumping data for table `vat_statements`
--

LOCK TABLES `vat_statements` WRITE;
/*!40000 ALTER TABLE `vat_statements` DISABLE KEYS */;
/*!40000 ALTER TABLE `vat_statements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vehicles`
--

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
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vehicles`
--

LOCK TABLES `vehicles` WRITE;
/*!40000 ALTER TABLE `vehicles` DISABLE KEYS */;
/*!40000 ALTER TABLE `vehicles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wallets`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `wallets`
--

LOCK TABLES `wallets` WRITE;
/*!40000 ALTER TABLE `wallets` DISABLE KEYS */;
INSERT INTO `wallets` VALUES (1,'Wallet Recharge',5,2,NULL,1000.00,1,1,1,'2025-07-30 06:19:01','2025-07-30 06:19:01');
/*!40000 ALTER TABLE `wallets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_deliveries`
--

DROP TABLE IF EXISTS `webhook_deliveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_deliveries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `webhook_endpoint_id` bigint unsigned NOT NULL,
  `event` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` json NOT NULL,
  `response_status` int DEFAULT NULL,
  `response_body` json DEFAULT NULL,
  `attempts` int NOT NULL DEFAULT '0',
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_deliveries_webhook_endpoint_id_event_index` (`webhook_endpoint_id`,`event`),
  KEY `webhook_deliveries_delivered_at_index` (`delivered_at`),
  KEY `webhook_deliveries_failed_at_index` (`failed_at`),
  CONSTRAINT `webhook_deliveries_webhook_endpoint_id_foreign` FOREIGN KEY (`webhook_endpoint_id`) REFERENCES `webhook_endpoints` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_deliveries`
--

LOCK TABLES `webhook_deliveries` WRITE;
/*!40000 ALTER TABLE `webhook_deliveries` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_deliveries` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `webhook_endpoints`
--

DROP TABLE IF EXISTS `webhook_endpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `webhook_endpoints` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `secret` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `events` json NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_delivery_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `webhook_endpoints_user_id_is_active_index` (`user_id`,`is_active`),
  CONSTRAINT `webhook_endpoints_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `webhook_endpoints`
--

LOCK TABLES `webhook_endpoints` WRITE;
/*!40000 ALTER TABLE `webhook_endpoints` DISABLE KEYS */;
/*!40000 ALTER TABLE `webhook_endpoints` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `wh_locations`
--

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

--
-- Dumping data for table `wh_locations`
--

LOCK TABLES `wh_locations` WRITE;
/*!40000 ALTER TABLE `wh_locations` DISABLE KEYS */;
/*!40000 ALTER TABLE `wh_locations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `whatsapp_templates`
--

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

--
-- Dumping data for table `whatsapp_templates`
--

LOCK TABLES `whatsapp_templates` WRITE;
/*!40000 ALTER TABLE `whatsapp_templates` DISABLE KEYS */;
/*!40000 ALTER TABLE `whatsapp_templates` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `why_couriers`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `why_couriers`
--

LOCK TABLES `why_couriers` WRITE;
/*!40000 ALTER TABLE `why_couriers` DISABLE KEYS */;
INSERT INTO `why_couriers` VALUES (1,'Timely Delivery ',14,'1',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(2,'Limitless Pickup',15,'2',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(3,'Cash on delivery (COD)',16,'3',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(4,'Get Payment Any Time ',17,'4',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(5,'Secure Handling ',18,'5',1,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(6,'Live Tracking Update',19,'6',1,'2025-06-29 11:29:49','2025-06-29 11:29:49');
/*!40000 ALTER TABLE `why_couriers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_task_activities`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_task_activities`
--

LOCK TABLES `workflow_task_activities` WRITE;
/*!40000 ALTER TABLE `workflow_task_activities` DISABLE KEYS */;
INSERT INTO `workflow_task_activities` VALUES (1,1,2,'created','{\"title\": \"asrwerqw\", \"status\": \"pending\"}','2025-11-06 11:54:25');
/*!40000 ALTER TABLE `workflow_task_activities` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_task_comments`
--

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

--
-- Dumping data for table `workflow_task_comments`
--

LOCK TABLES `workflow_task_comments` WRITE;
/*!40000 ALTER TABLE `workflow_task_comments` DISABLE KEYS */;
/*!40000 ALTER TABLE `workflow_task_comments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `workflow_tasks`
--

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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_tasks`
--

LOCK TABLES `workflow_tasks` WRITE;
/*!40000 ALTER TABLE `workflow_tasks` DISABLE KEYS */;
INSERT INTO `workflow_tasks` VALUES (1,'asrwerqw','weqwew','pending','medium',2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-06 10:54:25','[]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 10:54:25','2025-11-06 10:54:25');
/*!40000 ALTER TABLE `workflow_tasks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `zones`
--

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

--
-- Dumping data for table `zones`
--

LOCK TABLES `zones` WRITE;
/*!40000 ALTER TABLE `zones` DISABLE KEYS */;
/*!40000 ALTER TABLE `zones` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-11-06 13:19:05
