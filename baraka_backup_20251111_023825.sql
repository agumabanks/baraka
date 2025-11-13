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
) ENGINE=InnoDB AUTO_INCREMENT=1627 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `activity_log`
--

LOCK TABLES `activity_log` WRITE;
/*!40000 ALTER TABLE `activity_log` DISABLE KEYS */;
INSERT INTO `activity_log` VALUES (1,'User','created','App\\Models\\User','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-11-06 05:20:48','2025-11-06 05:20:48'),(2,'User','updated','App\\Models\\User','updated',1,NULL,NULL,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-11-06 05:21:02','2025-11-06 05:21:02'),(3,'User','created','App\\Models\\User','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"sanaa Administrator\", \"email\": \"info@sanaa.co\"}}',NULL,'2025-11-06 05:21:02','2025-11-06 05:21:02'),(4,'Upload','created','App\\Models\\Backend\\Upload','created',4,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user4.png\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(5,'Upload','created','App\\Models\\Backend\\Upload','created',5,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user5.png\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(6,'Upload','created','App\\Models\\Backend\\Upload','created',6,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user6.png\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(7,'Upload','created','App\\Models\\Backend\\Upload','created',7,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user7.png\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(8,'Hub','created','App\\Models\\Backend\\Hub','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Mirpur-10\", \"phone\": \"01000000001\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(9,'Hub','created','App\\Models\\Backend\\Hub','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Uttara\", \"phone\": \"01000000002\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(10,'Hub','created','App\\Models\\Backend\\Hub','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Dhanmundi\", \"phone\": \"01000000003\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(11,'Hub','created','App\\Models\\Backend\\Hub','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Old Dhaka\", \"phone\": \"01000000004\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(12,'Hub','created','App\\Models\\Backend\\Hub','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Jatrabari\", \"phone\": \"01000000005\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(13,'Hub','created','App\\Models\\Backend\\Hub','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Badda\", \"phone\": \"01000000006\", \"address\": \"Dhaka, Bangladesh\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(14,'Department','created','App\\Models\\Backend\\Department','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"General Management\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(15,'Department','created','App\\Models\\Backend\\Department','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Marketing\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(16,'Department','created','App\\Models\\Backend\\Department','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Operations\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(17,'Department','created','App\\Models\\Backend\\Department','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Finance\"}}',NULL,'2025-06-29 11:29:42','2025-06-29 11:29:42'),(18,'Department','created','App\\Models\\Backend\\Department','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Sales\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(19,'Department','created','App\\Models\\Backend\\Department','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"Human Resource\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(20,'Department','created','App\\Models\\Backend\\Department','created',7,NULL,NULL,'{\"attributes\": {\"title\": \"Purchase\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(21,'Designation','created','App\\Models\\Backend\\Designation','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Executive Officer (CEO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(22,'Designation','created','App\\Models\\Backend\\Designation','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Operating Officer (COO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(23,'Designation','created','App\\Models\\Backend\\Designation','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Financial Officer (CFO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(24,'Designation','created','App\\Models\\Backend\\Designation','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Technology Officer (CTO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(25,'Designation','created','App\\Models\\Backend\\Designation','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Legal Officer (CLO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(26,'Designation','created','App\\Models\\Backend\\Designation','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"Chief Marketing Officer (CMO)\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(27,'Role','created','App\\Models\\Backend\\Role','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Super Admin\", \"permissions\": [\"dashboard_read\", \"calendar_read\", \"total_parcel\", \"total_user\", \"total_merchant\", \"total_delivery_man\", \"total_hubs\", \"total_accounts\", \"total_parcels_pending\", \"total_pickup_assigned\", \"total_received_warehouse\", \"total_deliveryman_assigned\", \"total_partial_deliverd\", \"total_parcels_deliverd\", \"recent_accounts\", \"recent_salary\", \"recent_hub\", \"all_statements\", \"income_expense_charts\", \"merchant_revenue_charts\", \"deliveryman_revenue_charts\", \"courier_revenue_charts\", \"recent_parcels\", \"bank_transaction\", \"log_read\", \"hub_read\", \"hub_create\", \"hub_update\", \"hub_delete\", \"hub_incharge_read\", \"hub_incharge_create\", \"hub_incharge_update\", \"hub_incharge_delete\", \"hub_incharge_assigned\", \"account_read\", \"account_create\", \"account_update\", \"account_delete\", \"income_read\", \"income_create\", \"income_update\", \"income_delete\", \"expense_read\", \"expense_create\", \"expense_update\", \"expense_delete\", \"todo_read\", \"todo_create\", \"todo_update\", \"todo_delete\", \"fund_transfer_read\", \"fund_transfer_create\", \"fund_transfer_update\", \"fund_transfer_delete\", \"role_read\", \"role_create\", \"role_update\", \"role_delete\", \"designation_read\", \"designation_create\", \"designation_update\", \"designation_delete\", \"department_read\", \"department_create\", \"department_update\", \"department_delete\", \"user_read\", \"user_create\", \"user_update\", \"user_delete\", \"permission_update\", \"merchant_read\", \"merchant_create\", \"merchant_update\", \"merchant_delete\", \"merchant_view\", \"merchant_delivery_charge_read\", \"merchant_delivery_charge_create\", \"merchant_delivery_charge_update\", \"merchant_delivery_charge_delete\", \"merchant_shop_read\", \"merchant_shop_create\", \"merchant_shop_update\", \"merchant_shop_delete\", \"merchant_payment_read\", \"merchant_payment_create\", \"merchant_payment_update\", \"merchant_payment_delete\", \"payment_read\", \"payment_create\", \"payment_update\", \"payment_delete\", \"payment_reject\", \"payment_process\", \"hub_payment_read\", \"hub_payment_create\", \"hub_payment_update\", \"hub_payment_delete\", \"hub_payment_reject\", \"hub_payment_process\", \"hub_payment_request_read\", \"hub_payment_request_create\", \"hub_payment_request_update\", \"hub_payment_request_delete\", \"parcel_read\", \"parcel_create\", \"parcel_update\", \"parcel_delete\", \"parcel_status_update\", \"delivery_man_read\", \"delivery_man_create\", \"delivery_man_update\", \"delivery_man_delete\", \"delivery_category_read\", \"delivery_category_create\", \"delivery_category_update\", \"delivery_category_delete\", \"delivery_charge_read\", \"delivery_charge_create\", \"delivery_charge_update\", \"delivery_charge_delete\", \"delivery_type_read\", \"delivery_type_status_change\", \"liquid_fragile_read\", \"liquid_fragile_update\", \"liquid_status_change\", \"packaging_read\", \"packaging_create\", \"packaging_update\", \"packaging_delete\", \"category_read\", \"category_create\", \"category_update\", \"category_delete\", \"account_heads_read\", \"database_backup_read\", \"salary_read\", \"salary_create\", \"salary_update\", \"salary_delete\", \"support_read\", \"support_create\", \"support_update\", \"support_delete\", \"support_reply\", \"support_status_update\", \"sms_settings_read\", \"sms_settings_create\", \"sms_settings_update\", \"sms_settings_delete\", \"sms_send_settings_read\", \"sms_send_settings_create\", \"sms_send_settings_update\", \"sms_send_settings_delete\", \"general_settings_read\", \"general_settings_update\", \"notification_settings_read\", \"notification_settings_update\", \"push_notification_read\", \"push_notification_create\", \"push_notification_update\", \"push_notification_delete\", \"asset_category_read\", \"asset_category_create\", \"asset_category_update\", \"asset_category_delete\", \"news_offer_read\", \"news_offer_create\", \"news_offer_update\", \"news_offer_delete\", \"parcel_status_reports\", \"parcel_wise_profit\", \"parcel_total_summery\", \"salary_reports\", \"merchant_hub_deliveryman\", \"salary_generate_read\", \"salary_generate_create\", \"salary_generate_update\", \"salary_generate_delete\", \"assets_read\", \"assets_create\", \"assets_update\", \"assets_delete\", \"fraud_read\", \"fraud_create\", \"fraud_update\", \"fraud_delete\", \"subscribe_read\", \"pickup_request_regular\", \"pickup_request_express\", \"invoice_read\", \"invoice_status_update\", \"social_login_settings_read\", \"social_login_settings_update\", \"payout_setup_settings_read\", \"payout_setup_settings_update\", \"online_payment_read\", \"payout_read\", \"payout_create\", \"hub_view\", \"paid_invoice_read\", \"invoice_generate_menually\", \"currency_read\", \"currency_create\", \"currency_update\", \"currency_delete\", \"social_link_read\", \"social_link_create\", \"social_link_update\", \"social_link_delete\", \"service_read\", \"service_create\", \"service_update\", \"service_delete\", \"why_courier_read\", \"why_courier_create\", \"why_courier_update\", \"why_courier_delete\", \"faq_read\", \"faq_create\", \"faq_update\", \"faq_delete\", \"partner_read\", \"partner_create\", \"partner_update\", \"partner_delete\", \"blogs_read\", \"blogs_create\", \"blogs_update\", \"blogs_delete\", \"pages_read\", \"pages_update\", \"section_read\", \"section_update\", \"mail_settings_read\", \"mail_settings_update\", \"wallet_request_read\", \"wallet_request_create\", \"wallet_request_delete\", \"wallet_request_approve\", \"wallet_request_reject\"]}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(28,'Role','created','App\\Models\\Backend\\Role','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Admin\", \"permissions\": [\"dashboard_read\", \"calendar_read\", \"total_parcel\", \"total_user\", \"total_merchant\", \"total_delivery_man\", \"total_hubs\", \"total_accounts\", \"total_parcels_pending\", \"total_pickup_assigned\", \"total_received_warehouse\", \"total_deliveryman_assigned\", \"total_partial_deliverd\", \"total_parcels_deliverd\", \"recent_accounts\", \"recent_salary\", \"recent_hub\", \"all_statements\", \"income_expense_charts\", \"merchant_revenue_charts\", \"deliveryman_revenue_charts\", \"courier_revenue_charts\", \"recent_parcels\", \"bank_transaction\", \"log_read\", \"hub_read\", \"hub_incharge_read\", \"account_read\", \"income_read\", \"expense_read\", \"todo_read\", \"sms_settings_read\", \"sms_send_settings_read\", \"general_settings_read\", \"notification_settings_read\", \"push_notification_read\", \"push_notification_create\", \"push_notification_update\", \"push_notification_delete\", \"account_heads_read\", \"salary_read\", \"support_read\", \"fund_transfer_read\", \"role_read\", \"designation_read\", \"department_read\", \"user_read\", \"merchant_read\", \"merchant_delivery_charge_read\", \"merchant_shop_read\", \"merchant_payment_read\", \"payment_read\", \"hub_payment_request_read\", \"hub_payment_read\", \"parcel_read\", \"delivery_man_read\", \"delivery_category_read\", \"delivery_charge_read\", \"delivery_type_read\", \"liquid_fragile_read\", \"packaging_read\", \"category_read\", \"asset_category_read\", \"news_offer_read\", \"sms_settings_status_change\", \"sms_send_settings_status_change\", \"bank_transaction_read\", \"database_backup_read\", \"parcel_status_reports\", \"parcel_wise_profit\", \"parcel_total_summery\", \"salary_reports\", \"merchant_hub_deliveryman\", \"salary_generate_read\", \"assets_read\", \"fraud_read\", \"subscribe_read\", \"pickup_request_regular\", \"pickup_request_express\", \"cash_received_from_delivery_man_read\", \"cash_received_from_delivery_man_create\", \"cash_received_from_delivery_man_update\", \"cash_received_from_delivery_man_delete\", \"invoice_read\", \"invoice_status_update\", \"social_login_settings_read\", \"social_login_settings_update\", \"payout_setup_settings_read\", \"online_payment_read\", \"payout_read\", \"hub_view\", \"paid_invoice_read\", \"invoice_generate_menually\", \"currency_read\"]}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(29,'User','created','App\\Models\\User','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"WemaxDevs\", \"email\": \"admin@wemaxdevs.com\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(30,'User','created','App\\Models\\User','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Branch\", \"email\": \"branch@wemaxdevs.com\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(31,'User','created','App\\Models\\User','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Delivery Man\", \"email\": \"deliveryman@wemaxit.com\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(32,'DeliveryMan','created','App\\Models\\Backend\\DeliveryMan','created',1,NULL,NULL,'{\"attributes\": {\"user.name\": \"Delivery Man\", \"current_balance\": \"0.00\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(33,'InCharges','created','App\\Models\\Backend\\HubInCharge','created',1,NULL,NULL,'{\"attributes\": {\"user.name\": \"Branch\"}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(34,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',1,NULL,NULL,'{\"attributes\": {\"title\": \"KG\", \"description\": null}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(35,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',2,NULL,NULL,'{\"attributes\": {\"title\": \"Mobile\", \"description\": null}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(36,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',3,NULL,NULL,'{\"attributes\": {\"title\": \"Laptop\", \"description\": null}}',NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(37,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',4,NULL,NULL,'{\"attributes\": {\"title\": \"Tabs\", \"description\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(38,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',5,NULL,NULL,'{\"attributes\": {\"title\": \"Gaming Kybord\", \"description\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(39,'Deliverycategory','created','App\\Models\\Backend\\Deliverycategory','created',6,NULL,NULL,'{\"attributes\": {\"title\": \"Cosmetices\", \"description\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(40,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',1,NULL,NULL,'{\"attributes\": {\"weight\": 1, \"next_day\": \"60.00\", \"position\": 1, \"same_day\": \"50.00\", \"sub_city\": \"70.00\", \"outside_city\": \"80.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(41,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',2,NULL,NULL,'{\"attributes\": {\"weight\": 2, \"next_day\": \"100.00\", \"position\": 2, \"same_day\": \"90.00\", \"sub_city\": \"110.00\", \"outside_city\": \"120.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(42,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',3,NULL,NULL,'{\"attributes\": {\"weight\": 3, \"next_day\": \"140.00\", \"position\": 3, \"same_day\": \"130.00\", \"sub_city\": \"150.00\", \"outside_city\": \"160.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(43,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',4,NULL,NULL,'{\"attributes\": {\"weight\": 4, \"next_day\": \"180.00\", \"position\": 4, \"same_day\": \"170.00\", \"sub_city\": \"190.00\", \"outside_city\": \"200.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(44,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',5,NULL,NULL,'{\"attributes\": {\"weight\": 5, \"next_day\": \"220.00\", \"position\": 5, \"same_day\": \"210.00\", \"sub_city\": \"230.00\", \"outside_city\": \"240.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(45,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',6,NULL,NULL,'{\"attributes\": {\"weight\": 6, \"next_day\": \"260.00\", \"position\": 6, \"same_day\": \"250.00\", \"sub_city\": \"270.00\", \"outside_city\": \"280.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(46,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',7,NULL,NULL,'{\"attributes\": {\"weight\": 7, \"next_day\": \"300.00\", \"position\": 7, \"same_day\": \"290.00\", \"sub_city\": \"310.00\", \"outside_city\": \"320.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(47,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',8,NULL,NULL,'{\"attributes\": {\"weight\": 8, \"next_day\": \"350.00\", \"position\": 8, \"same_day\": \"340.00\", \"sub_city\": \"360.00\", \"outside_city\": \"370.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(48,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',9,NULL,NULL,'{\"attributes\": {\"weight\": 9, \"next_day\": \"390.00\", \"position\": 9, \"same_day\": \"380.00\", \"sub_city\": \"400.00\", \"outside_city\": \"410.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(49,'DeliveryCharge','created','App\\Models\\Backend\\DeliveryCharge','created',10,NULL,NULL,'{\"attributes\": {\"weight\": 10, \"next_day\": \"430.00\", \"position\": 10, \"same_day\": \"420.00\", \"sub_city\": \"440.00\", \"outside_city\": \"450.00\", \"category.name\": null}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(50,'User','created','App\\Models\\User','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Merchant\", \"email\": \"merchant@wemaxdevs.com\"}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(51,'Merchant','created','App\\Models\\Backend\\Merchant','created',1,NULL,NULL,'{\"attributes\": {\"user.name\": \"Merchant\", \"business_name\": \"WemaxDevs\", \"current_balance\": \"0.00\"}}',NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(52,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',1,NULL,NULL,'{\"attributes\": {\"weight\": 1, \"next_day\": \"60.00\", \"same_day\": \"50.00\", \"sub_city\": \"70.00\", \"outside_city\": \"80.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(53,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',2,NULL,NULL,'{\"attributes\": {\"weight\": 2, \"next_day\": \"100.00\", \"same_day\": \"90.00\", \"sub_city\": \"110.00\", \"outside_city\": \"120.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(54,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',3,NULL,NULL,'{\"attributes\": {\"weight\": 3, \"next_day\": \"140.00\", \"same_day\": \"130.00\", \"sub_city\": \"150.00\", \"outside_city\": \"160.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(55,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',4,NULL,NULL,'{\"attributes\": {\"weight\": 4, \"next_day\": \"180.00\", \"same_day\": \"170.00\", \"sub_city\": \"190.00\", \"outside_city\": \"200.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(56,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',5,NULL,NULL,'{\"attributes\": {\"weight\": 5, \"next_day\": \"220.00\", \"same_day\": \"210.00\", \"sub_city\": \"230.00\", \"outside_city\": \"240.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(57,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',6,NULL,NULL,'{\"attributes\": {\"weight\": 6, \"next_day\": \"260.00\", \"same_day\": \"250.00\", \"sub_city\": \"270.00\", \"outside_city\": \"280.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(58,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',7,NULL,NULL,'{\"attributes\": {\"weight\": 7, \"next_day\": \"300.00\", \"same_day\": \"290.00\", \"sub_city\": \"310.00\", \"outside_city\": \"320.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(59,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',8,NULL,NULL,'{\"attributes\": {\"weight\": 8, \"next_day\": \"350.00\", \"same_day\": \"340.00\", \"sub_city\": \"360.00\", \"outside_city\": \"370.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(60,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',9,NULL,NULL,'{\"attributes\": {\"weight\": 9, \"next_day\": \"390.00\", \"same_day\": \"380.00\", \"sub_city\": \"400.00\", \"outside_city\": \"410.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(61,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',10,NULL,NULL,'{\"attributes\": {\"weight\": 10, \"next_day\": \"430.00\", \"same_day\": \"420.00\", \"sub_city\": \"440.00\", \"outside_city\": \"450.00\", \"merchant.business_name\": \"WemaxDevs\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(62,'MerchantShops','created','App\\Models\\MerchantShops','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 1\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(63,'MerchantShops','created','App\\Models\\MerchantShops','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 2\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(64,'MerchantShops','created','App\\Models\\MerchantShops','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 3\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(65,'MerchantShops','created','App\\Models\\MerchantShops','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 4\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(66,'MerchantShops','created','App\\Models\\MerchantShops','created',5,NULL,NULL,'{\"attributes\": {\"name\": \"Shop 5\", \"address\": \"Wemaxdevs,Dhaka\", \"contact_no\": \"+88013000000\", \"merchant.business_name\": \"WemaxDevs\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(67,'Config','created','App\\Models\\Config','created',1,NULL,NULL,'{\"attributes\": {\"key\": \"fragile_liquid_status\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(68,'Config','created','App\\Models\\Config','created',2,NULL,NULL,'{\"attributes\": {\"key\": \"fragile_liquid_charge\", \"value\": \"20\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(69,'Config','created','App\\Models\\Config','created',3,NULL,NULL,'{\"attributes\": {\"key\": \"same_day\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(70,'Config','created','App\\Models\\Config','created',4,NULL,NULL,'{\"attributes\": {\"key\": \"next_day\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(71,'Config','created','App\\Models\\Config','created',5,NULL,NULL,'{\"attributes\": {\"key\": \"sub_city\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(72,'Config','created','App\\Models\\Config','created',6,NULL,NULL,'{\"attributes\": {\"key\": \"outside_City\", \"value\": \"1\"}}',NULL,'2025-06-29 11:29:45','2025-06-29 11:29:45'),(73,'Packaging','created','App\\Models\\Backend\\Packaging','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"Poly\", \"price\": \"10.00\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(74,'Packaging','created','App\\Models\\Backend\\Packaging','created',2,NULL,NULL,'{\"attributes\": {\"name\": \"Bubble Poly\", \"price\": \"20.00\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(75,'Packaging','created','App\\Models\\Backend\\Packaging','created',3,NULL,NULL,'{\"attributes\": {\"name\": \"Box\", \"price\": \"30.00\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(76,'Packaging','created','App\\Models\\Backend\\Packaging','created',4,NULL,NULL,'{\"attributes\": {\"name\": \"Box Poly\", \"price\": \"40.00\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(77,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',1,NULL,NULL,'{\"attributes\": {\"key\": \"reve_api_key\", \"value\": \"Your API key\"}}',NULL,'2025-06-29 11:29:46','2025-06-29 11:29:46'),(78,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',2,NULL,NULL,'{\"attributes\": {\"key\": \"reve_secret_key\", \"value\": \"Your secret key\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(79,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',3,NULL,NULL,'{\"attributes\": {\"key\": \"reve_api_url\", \"value\": \"http://smpp.ajuratech.com:7788/sendtext\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(80,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',4,NULL,NULL,'{\"attributes\": {\"key\": \"reve_username\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(81,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',5,NULL,NULL,'{\"attributes\": {\"key\": \"reve_user_password\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(82,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',6,NULL,NULL,'{\"attributes\": {\"key\": \"reve_status\", \"value\": \"0\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(83,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',7,NULL,NULL,'{\"attributes\": {\"key\": \"twilio_sid\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(84,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',8,NULL,NULL,'{\"attributes\": {\"key\": \"twilio_token\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(85,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',9,NULL,NULL,'{\"attributes\": {\"key\": \"twilio_from\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(86,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',10,NULL,NULL,'{\"attributes\": {\"key\": \"twilio_status\", \"value\": \"0\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(87,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',11,NULL,NULL,'{\"attributes\": {\"key\": \"nexmo_key\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(88,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',12,NULL,NULL,'{\"attributes\": {\"key\": \"nexmo_secret_key\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(89,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',13,NULL,NULL,'{\"attributes\": {\"key\": \"nexmo_status\", \"value\": \"0\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(90,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',14,NULL,NULL,'{\"attributes\": {\"key\": \"click_send_username\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(91,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',15,NULL,NULL,'{\"attributes\": {\"key\": \"click_send_api_key\", \"value\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(92,'smsSettings','created','App\\Models\\Backend\\SmsSetting','created',16,NULL,NULL,'{\"attributes\": {\"key\": \"click_send_status\", \"value\": \"0\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(93,'SmsSendSetting','created','App\\Models\\Backend\\SmsSendSetting','created',1,NULL,NULL,'{\"attributes\": {\"sms_send_status\": 1}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(94,'SmsSendSetting','created','App\\Models\\Backend\\SmsSendSetting','created',2,NULL,NULL,'{\"attributes\": {\"sms_send_status\": 2}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(95,'SmsSendSetting','created','App\\Models\\Backend\\SmsSendSetting','created',3,NULL,NULL,'{\"attributes\": {\"sms_send_status\": 3}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(96,'Upload','created','App\\Models\\Backend\\Upload','created',8,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user8.png\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(97,'Upload','created','App\\Models\\Backend\\Upload','created',9,NULL,NULL,'{\"attributes\": {\"original\": \"uploads/users/user9.png\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(98,'General Settings','created','App\\Models\\Backend\\GeneralSettings','created',1,NULL,NULL,'{\"attributes\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(99,'Notification Settings','created','App\\Models\\Backend\\NotificationSettings','created',1,NULL,NULL,'{\"attributes\": {\"fcm_topic\": \"\", \"fcm_secret_key\": \"\"}}',NULL,'2025-06-29 11:29:47','2025-06-29 11:29:47'),(100,'Upload','created','App\\Models\\Backend\\Upload','created',10,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/services/truck.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(101,'Upload','created','App\\Models\\Backend\\Upload','created',11,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/services/pick-drop.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(102,'Upload','created','App\\Models\\Backend\\Upload','created',12,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/services/packageing.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(103,'Upload','created','App\\Models\\Backend\\Upload','created',13,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/services/warehouse.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(104,'Upload','created','App\\Models\\Backend\\Upload','created',14,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/timly-delivery.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(105,'Upload','created','App\\Models\\Backend\\Upload','created',15,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/limitless-pickup.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(106,'Upload','created','App\\Models\\Backend\\Upload','created',16,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/cash-on-delivery.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(107,'Upload','created','App\\Models\\Backend\\Upload','created',17,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/payment.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(108,'Upload','created','App\\Models\\Backend\\Upload','created',18,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/handling.png\"}}',NULL,'2025-06-29 11:29:48','2025-06-29 11:29:48'),(109,'Upload','created','App\\Models\\Backend\\Upload','created',19,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/whycourier/live-tracking.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(110,'Upload','created','App\\Models\\Backend\\Upload','created',20,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/1.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(111,'Upload','created','App\\Models\\Backend\\Upload','created',21,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/atom.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(112,'Upload','created','App\\Models\\Backend\\Upload','created',22,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/digg.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(113,'Upload','created','App\\Models\\Backend\\Upload','created',23,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/2.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(114,'Upload','created','App\\Models\\Backend\\Upload','created',24,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/huawei.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(115,'Upload','created','App\\Models\\Backend\\Upload','created',25,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/ups.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(116,'Upload','created','App\\Models\\Backend\\Upload','created',26,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/1.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(117,'Upload','created','App\\Models\\Backend\\Upload','created',27,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/atom.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(118,'Upload','created','App\\Models\\Backend\\Upload','created',28,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/digg.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(119,'Upload','created','App\\Models\\Backend\\Upload','created',29,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/2.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(120,'Upload','created','App\\Models\\Backend\\Upload','created',30,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/huawei.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(121,'Upload','created','App\\Models\\Backend\\Upload','created',31,NULL,NULL,'{\"attributes\": {\"original\": \"frontend/images/partner/ups.png\"}}',NULL,'2025-06-29 11:29:49','2025-06-29 11:29:49'),(122,'User','updated','App\\Models\\User','updated',1,NULL,NULL,'{\"old\": {\"name\": \"WemaxDevs\", \"email\": \"admin@wemaxdevs.com\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-06-29 11:29:50','2025-06-29 11:29:50'),(123,'User','updated','App\\Models\\User','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-06-29 11:54:32','2025-06-29 11:54:32'),(124,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:13:06','2025-06-29 12:13:06'),(125,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:14:12','2025-06-29 12:14:12'),(126,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:17:52','2025-06-29 12:17:52'),(127,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"We Courier\", \"phone\": \"20022002\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:20:33','2025-06-29 12:20:33'),(128,'Hub','updated','App\\Models\\Backend\\Hub','updated',6,'App\\Models\\User',1,'{\"old\": {\"name\": \"Badda\", \"phone\": \"01000000006\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Kinshasa\", \"phone\": \"08222254001\", \"address\": \"Haut-Congo 356 / Binza Upn / Ngaliema / Kinshasa\"}}',NULL,'2025-06-29 12:31:33','2025-06-29 12:31:33'),(129,'Hub','updated','App\\Models\\Backend\\Hub','updated',5,'App\\Models\\User',1,'{\"old\": {\"name\": \"Jatrabari\", \"phone\": \"01000000005\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Goma\", \"phone\": \"01000000005\", \"address\": \"Haut-Congo 356 / Binza Upn / Goma /\"}}',NULL,'2025-06-29 12:32:05','2025-06-29 12:32:05'),(130,'Hub','updated','App\\Models\\Backend\\Hub','updated',4,'App\\Models\\User',1,'{\"old\": {\"name\": \"Old Dhaka\", \"phone\": \"01000000004\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Lumubashi\", \"phone\": \"01000000004\", \"address\": \"Haut-Congo 356 / Binza Upn\"}}',NULL,'2025-06-29 12:32:37','2025-06-29 12:32:37'),(131,'Hub','updated','App\\Models\\Backend\\Hub','updated',3,'App\\Models\\User',1,'{\"old\": {\"name\": \"Dhanmundi\", \"phone\": \"01000000003\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Bukavu\", \"phone\": \"01000000003\", \"address\": \"825 Nobel Street\"}}',NULL,'2025-06-29 12:33:05','2025-06-29 12:33:05'),(132,'Hub','updated','App\\Models\\Backend\\Hub','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"Mirpur-10\", \"phone\": \"01000000001\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Kivu\", \"phone\": \"01000000001\", \"address\": \"North Kivu\"}}',NULL,'2025-06-29 12:33:46','2025-06-29 12:33:46'),(133,'Hub','updated','App\\Models\\Backend\\Hub','updated',2,'App\\Models\\User',1,'{\"old\": {\"name\": \"Uttara\", \"phone\": \"01000000002\", \"address\": \"Dhaka, Bangladesh\"}, \"attributes\": {\"name\": \"Kivu South\", \"phone\": \"01000000002\", \"address\": \"Kivu South\"}}',NULL,'2025-06-29 12:34:17','2025-06-29 12:34:17'),(134,'Upload','updated','App\\Models\\Backend\\Upload','updated',8,'App\\Models\\User',1,'{\"old\": {\"original\": \"uploads/users/user8.png\"}, \"attributes\": {\"original\": \"uploads/settings/202506291450101536.png\"}}',NULL,'2025-06-29 12:50:10','2025-06-29 12:50:10'),(135,'Upload','created','App\\Models\\Backend\\Upload','created',32,'App\\Models\\User',1,'{\"attributes\": {\"original\": \"uploads/settings/202506291459237515.png\"}}',NULL,'2025-06-29 12:59:23','2025-06-29 12:59:23'),(136,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-06-29 12:59:23','2025-06-29 12:59:23'),(137,'Upload','created','App\\Models\\Backend\\Upload','created',33,'App\\Models\\User',1,'{\"attributes\": {\"original\": \"uploads/section/20250629150636.png\"}}',NULL,'2025-06-29 13:06:36','2025-06-29 13:06:36'),(138,'User','created','App\\Models\\User','created',5,'App\\Models\\User',1,'{\"attributes\": {\"name\": \"Dennis Carroll\", \"email\": \"hupazef@mailinator.com\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(139,'Merchant','created','App\\Models\\Backend\\Merchant','created',2,'App\\Models\\User',1,'{\"attributes\": {\"user.name\": \"Dennis Carroll\", \"business_name\": \"Austin Chaney\", \"current_balance\": \"5.00\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(140,'MerchantShops','created','App\\Models\\MerchantShops','created',6,'App\\Models\\User',1,'{\"attributes\": {\"name\": \"Austin Chaney\", \"address\": \"Irure aliquid porro\", \"contact_no\": \"256702568978\", \"merchant.business_name\": \"Austin Chaney\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(141,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',11,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 1, \"next_day\": \"60.00\", \"same_day\": \"50.00\", \"sub_city\": \"70.00\", \"outside_city\": \"80.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(142,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',12,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 2, \"next_day\": \"100.00\", \"same_day\": \"90.00\", \"sub_city\": \"110.00\", \"outside_city\": \"120.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(143,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',13,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 3, \"next_day\": \"140.00\", \"same_day\": \"130.00\", \"sub_city\": \"150.00\", \"outside_city\": \"160.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(144,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',14,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 4, \"next_day\": \"180.00\", \"same_day\": \"170.00\", \"sub_city\": \"190.00\", \"outside_city\": \"200.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(145,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',15,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 5, \"next_day\": \"220.00\", \"same_day\": \"210.00\", \"sub_city\": \"230.00\", \"outside_city\": \"240.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(146,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',16,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 6, \"next_day\": \"260.00\", \"same_day\": \"250.00\", \"sub_city\": \"270.00\", \"outside_city\": \"280.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(147,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',17,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 7, \"next_day\": \"300.00\", \"same_day\": \"290.00\", \"sub_city\": \"310.00\", \"outside_city\": \"320.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(148,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',18,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 8, \"next_day\": \"350.00\", \"same_day\": \"340.00\", \"sub_city\": \"360.00\", \"outside_city\": \"370.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(149,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',19,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 9, \"next_day\": \"390.00\", \"same_day\": \"380.00\", \"sub_city\": \"400.00\", \"outside_city\": \"410.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(150,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',20,'App\\Models\\User',1,'{\"attributes\": {\"weight\": 10, \"next_day\": \"430.00\", \"same_day\": \"420.00\", \"sub_city\": \"440.00\", \"outside_city\": \"450.00\", \"merchant.business_name\": \"Austin Chaney\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:38:06','2025-07-05 11:38:06'),(151,'User','created','App\\Models\\User','created',6,NULL,NULL,'{\"attributes\": {\"name\": \"Raymond Mccray\", \"email\": null}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(152,'Merchant','created','App\\Models\\Backend\\Merchant','created',3,NULL,NULL,'{\"attributes\": {\"user.name\": \"Raymond Mccray\", \"business_name\": \"India Cote\", \"current_balance\": \"0.00\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(153,'MerchantShops','created','App\\Models\\MerchantShops','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"India Cote\", \"address\": \"Rem porro in delenit\", \"contact_no\": \"2567056567989\", \"merchant.business_name\": \"India Cote\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(154,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',21,NULL,NULL,'{\"attributes\": {\"weight\": 1, \"next_day\": \"60.00\", \"same_day\": \"50.00\", \"sub_city\": \"70.00\", \"outside_city\": \"80.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(155,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',22,NULL,NULL,'{\"attributes\": {\"weight\": 2, \"next_day\": \"100.00\", \"same_day\": \"90.00\", \"sub_city\": \"110.00\", \"outside_city\": \"120.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(156,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',23,NULL,NULL,'{\"attributes\": {\"weight\": 3, \"next_day\": \"140.00\", \"same_day\": \"130.00\", \"sub_city\": \"150.00\", \"outside_city\": \"160.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(157,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',24,NULL,NULL,'{\"attributes\": {\"weight\": 4, \"next_day\": \"180.00\", \"same_day\": \"170.00\", \"sub_city\": \"190.00\", \"outside_city\": \"200.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(158,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',25,NULL,NULL,'{\"attributes\": {\"weight\": 5, \"next_day\": \"220.00\", \"same_day\": \"210.00\", \"sub_city\": \"230.00\", \"outside_city\": \"240.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(159,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',26,NULL,NULL,'{\"attributes\": {\"weight\": 6, \"next_day\": \"260.00\", \"same_day\": \"250.00\", \"sub_city\": \"270.00\", \"outside_city\": \"280.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(160,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',27,NULL,NULL,'{\"attributes\": {\"weight\": 7, \"next_day\": \"300.00\", \"same_day\": \"290.00\", \"sub_city\": \"310.00\", \"outside_city\": \"320.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(161,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',28,NULL,NULL,'{\"attributes\": {\"weight\": 8, \"next_day\": \"350.00\", \"same_day\": \"340.00\", \"sub_city\": \"360.00\", \"outside_city\": \"370.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(162,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',29,NULL,NULL,'{\"attributes\": {\"weight\": 9, \"next_day\": \"390.00\", \"same_day\": \"380.00\", \"sub_city\": \"400.00\", \"outside_city\": \"410.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(163,'MerchantDeliveryCharge','created','App\\Models\\Backend\\MerchantDeliveryCharge','created',30,NULL,NULL,'{\"attributes\": {\"weight\": 10, \"next_day\": \"430.00\", \"same_day\": \"420.00\", \"sub_city\": \"440.00\", \"outside_city\": \"450.00\", \"merchant.business_name\": \"India Cote\", \"deliveryCharge.category.title\": \"KG\"}}',NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(164,'User','updated','App\\Models\\User','updated',5,'App\\Models\\User',5,'{\"old\": {\"name\": \"Dennis Carroll\", \"email\": \"hupazef@mailinator.com\"}, \"attributes\": {\"name\": \"Dennis Carroll\", \"email\": \"hupazef@mailinator.com\"}}',NULL,'2025-07-29 06:25:43','2025-07-29 06:25:43'),(165,'User','updated','App\\Models\\User','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-07-30 06:22:06','2025-07-30 06:22:06'),(166,'User','updated','App\\Models\\User','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-07-30 06:22:09','2025-07-30 06:22:09'),(167,'General Settings','updated','App\\Models\\Backend\\GeneralSettings','updated',1,'App\\Models\\User',1,'{\"old\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}, \"attributes\": {\"name\": \"Baraka\", \"phone\": \"0200903222\", \"prefix\": null, \"details\": null, \"tracking_id\": null}}',NULL,'2025-07-30 06:23:51','2025-07-30 06:23:51'),(168,'User','created','App\\Models\\User','created',7,NULL,NULL,'{\"attributes\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(169,'User','created','App\\Models\\User','created',8,NULL,NULL,'{\"attributes\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(170,'User','created','App\\Models\\User','created',9,NULL,NULL,'{\"attributes\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(171,'User','created','App\\Models\\User','created',10,NULL,NULL,'{\"attributes\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(172,'Branch','created branch: Central Hub','App\\Models\\Backend\\Branch','created',1,NULL,NULL,'{\"attributes\": {\"code\": \"HUB-001\", \"name\": \"Central Hub\", \"type\": \"HUB\", \"is_hub\": true, \"status\": 1}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(173,'Branch','created branch: Regional Branch','App\\Models\\Backend\\Branch','created',2,NULL,NULL,'{\"attributes\": {\"code\": \"REG-001\", \"name\": \"Regional Branch\", \"type\": \"REGIONAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(174,'Branch','created branch: Local Branch','App\\Models\\Backend\\Branch','created',3,NULL,NULL,'{\"attributes\": {\"code\": \"LOC-001\", \"name\": \"Local Branch\", \"type\": \"LOCAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(175,'BranchManager','created branch manager: ','App\\Models\\Backend\\BranchManager','created',1,NULL,NULL,'{\"attributes\": {\"status\": 1, \"business_name\": null, \"current_balance\": \"0.00\"}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(176,'BranchWorker','created branch worker: Branch Worker','App\\Models\\Backend\\BranchWorker','created',1,NULL,NULL,'{\"attributes\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(177,'shipment','created shipment','App\\Models\\Shipment','created',1,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 10, \"price_amount\": \"49.99\", \"dest_branch_id\": 2, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(178,'User','updated','App\\Models\\User','updated',7,NULL,NULL,'{\"old\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}, \"attributes\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}}',NULL,'2025-11-06 05:33:25','2025-11-06 05:33:25'),(179,'User','updated','App\\Models\\User','updated',8,NULL,NULL,'{\"old\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}, \"attributes\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}}',NULL,'2025-11-06 05:33:25','2025-11-06 05:33:25'),(180,'User','updated','App\\Models\\User','updated',9,NULL,NULL,'{\"old\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}, \"attributes\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}}',NULL,'2025-11-06 05:33:25','2025-11-06 05:33:25'),(181,'User','updated','App\\Models\\User','updated',10,NULL,NULL,'{\"old\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}, \"attributes\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}}',NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26'),(182,'BranchWorker','updated branch worker: Branch Worker','App\\Models\\Backend\\BranchWorker','updated',1,NULL,NULL,'{\"old\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}, \"attributes\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26'),(183,'shipment','created shipment','App\\Models\\Shipment','created',2,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 10, \"price_amount\": \"49.99\", \"dest_branch_id\": 2, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26'),(184,'User','updated','App\\Models\\User','updated',7,NULL,NULL,'{\"old\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}, \"attributes\": {\"name\": \"System Admin\", \"email\": \"admin@example.com\"}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(185,'User','updated','App\\Models\\User','updated',8,NULL,NULL,'{\"old\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}, \"attributes\": {\"name\": \"Branch Manager\", \"email\": \"manager@example.com\"}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(186,'User','updated','App\\Models\\User','updated',9,NULL,NULL,'{\"old\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}, \"attributes\": {\"name\": \"Branch Worker\", \"email\": \"worker@example.com\"}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(187,'User','updated','App\\Models\\User','updated',10,NULL,NULL,'{\"old\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}, \"attributes\": {\"name\": \"Client Contact\", \"email\": \"client@example.com\"}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(188,'BranchWorker','updated branch worker: Branch Worker','App\\Models\\Backend\\BranchWorker','updated',1,NULL,NULL,'{\"old\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}, \"attributes\": {\"role\": \"courier\", \"status\": 1, \"assigned_at\": \"2025-11-06T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(189,'shipment','created shipment','App\\Models\\Shipment','created',3,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 10, \"price_amount\": \"49.99\", \"dest_branch_id\": 2, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(347,'User','updated','App\\Models\\User','updated',1,'App\\Models\\User',2,'{\"old\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}, \"attributes\": {\"name\": \"baraka Administrator\", \"email\": \"info@baraka.co\"}}',NULL,'2025-11-06 10:13:42','2025-11-06 10:13:42'),(901,'ToDo','created','App\\Models\\Backend\\To_do','created',1,NULL,NULL,'{\"attributes\": {\"date\": \"2025-11-06\", \"title\": \"Test\", \"user.name\": \"baraka Administrator\", \"description\": \"desc\"}}',NULL,'2025-11-06 12:27:07','2025-11-06 12:27:07'),(1327,'User','created','App\\Models\\User','created',533,NULL,NULL,'{\"attributes\": {\"name\": \"Imelda Collier\", \"email\": \"doug14@example.com\"}}',NULL,'2025-11-06 15:34:27','2025-11-06 15:34:27'),(1328,'User','created','App\\Models\\User','created',534,NULL,NULL,'{\"attributes\": {\"name\": \"Chelsie Kerluke\", \"email\": \"lela.okeefe@example.net\"}}',NULL,'2025-11-06 15:34:27','2025-11-06 15:34:27'),(1329,'User','created','App\\Models\\User','created',535,NULL,NULL,'{\"attributes\": {\"name\": \"Prof. Zechariah Herman\", \"email\": \"durgan.jan@example.com\"}}',NULL,'2025-11-06 15:34:27','2025-11-06 15:34:27'),(1330,'User','created','App\\Models\\User','created',536,NULL,NULL,'{\"attributes\": {\"name\": \"Bud Ortiz\", \"email\": \"arno.bogisich@example.net\"}}',NULL,'2025-11-06 15:34:28','2025-11-06 15:34:28'),(1331,'User','created','App\\Models\\User','created',537,NULL,NULL,'{\"attributes\": {\"name\": \"Miss Tara Adams\", \"email\": \"nbogisich@example.com\"}}',NULL,'2025-11-06 15:34:28','2025-11-06 15:34:28'),(1332,'User','created','App\\Models\\User','created',538,NULL,NULL,'{\"attributes\": {\"name\": \"Nikko Mayert\", \"email\": \"kevin.terry@example.org\"}}',NULL,'2025-11-06 15:34:28','2025-11-06 15:34:28'),(1333,'User','created','App\\Models\\User','created',539,NULL,NULL,'{\"attributes\": {\"name\": \"Tyrese Steuber\", \"email\": \"nova.toy@example.com\"}}',NULL,'2025-11-06 15:34:28','2025-11-06 15:34:28'),(1334,'User','created','App\\Models\\User','created',540,NULL,NULL,'{\"attributes\": {\"name\": \"Willard Emard\", \"email\": \"green.megane@example.com\"}}',NULL,'2025-11-06 15:34:28','2025-11-06 15:34:28'),(1335,'User','created','App\\Models\\User','created',541,NULL,NULL,'{\"attributes\": {\"name\": \"Torrance Cummerata I\", \"email\": \"murray.buford@example.net\"}}',NULL,'2025-11-06 15:34:28','2025-11-06 15:34:28'),(1336,'User','created','App\\Models\\User','created',542,NULL,NULL,'{\"attributes\": {\"name\": \"Lorenzo Borer IV\", \"email\": \"bergstrom.okey@example.net\"}}',NULL,'2025-11-06 15:34:28','2025-11-06 15:34:28'),(1337,'User','created','App\\Models\\User','created',543,NULL,NULL,'{\"attributes\": {\"name\": \"Lavada Wintheiser V\", \"email\": \"jayce.nolan@example.org\"}}',NULL,'2025-11-06 15:34:29','2025-11-06 15:34:29'),(1338,'User','created','App\\Models\\User','created',544,NULL,NULL,'{\"attributes\": {\"name\": \"Jaqueline Robel\", \"email\": \"zvonrueden@example.org\"}}',NULL,'2025-11-06 15:34:29','2025-11-06 15:34:29'),(1339,'User','created','App\\Models\\User','created',545,NULL,NULL,'{\"attributes\": {\"name\": \"Daphney Hand IV\", \"email\": \"scottie.halvorson@example.org\"}}',NULL,'2025-11-06 15:34:29','2025-11-06 15:34:29'),(1340,'User','created','App\\Models\\User','created',546,NULL,NULL,'{\"attributes\": {\"name\": \"Prof. Liliane Barton\", \"email\": \"vrowe@example.com\"}}',NULL,'2025-11-06 15:34:29','2025-11-06 15:34:29'),(1341,'User','created','App\\Models\\User','created',547,NULL,NULL,'{\"attributes\": {\"name\": \"Dustin White\", \"email\": \"rico.towne@example.net\"}}',NULL,'2025-11-06 15:34:29','2025-11-06 15:34:29'),(1342,'User','created','App\\Models\\User','created',548,NULL,NULL,'{\"attributes\": {\"name\": \"Dr. Samantha Thiel\", \"email\": \"zulauf.brandt@example.com\"}}',NULL,'2025-11-06 15:34:29','2025-11-06 15:34:29'),(1343,'User','created','App\\Models\\User','created',549,NULL,NULL,'{\"attributes\": {\"name\": \"Moses Collins\", \"email\": \"adolphus.greenholt@example.org\"}}',NULL,'2025-11-06 15:34:29','2025-11-06 15:34:29'),(1344,'User','created','App\\Models\\User','created',550,NULL,NULL,'{\"attributes\": {\"name\": \"Prof. Hipolito Collier\", \"email\": \"feil.lauryn@example.com\"}}',NULL,'2025-11-06 15:34:29','2025-11-06 15:34:29'),(1458,'User','deleted','App\\Models\\User','deleted',541,'App\\Models\\User',2,'{\"old\": {\"name\": \"Torrance Cummerata I\", \"email\": \"murray.buford@example.net\"}}',NULL,'2025-11-06 23:31:48','2025-11-06 23:31:48'),(1459,'Role','created','App\\Models\\Backend\\Role','created',32,NULL,NULL,'{\"attributes\": {\"name\": \"Branch Ops Manager\", \"permissions\": []}}',NULL,'2025-11-07 10:34:37','2025-11-07 10:34:37'),(1460,'User','created','App\\Models\\User','created',629,NULL,NULL,'{\"attributes\": {\"name\": \"Central Hub Ops Lead\", \"email\": \"branch.ops+hub-001@baraka.sanaa.co\"}}',NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(1461,'User','updated','App\\Models\\User','updated',629,NULL,NULL,'{\"old\": {\"name\": \"Central Hub Ops Lead\", \"email\": \"branch.ops+hub-001@baraka.sanaa.co\"}, \"attributes\": {\"name\": \"Central Hub Ops Lead\", \"email\": \"branch.ops+hub-001@baraka.sanaa.co\"}}',NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(1462,'BranchManager','created branch manager: Central Hub Operations','App\\Models\\Backend\\BranchManager','created',2,NULL,NULL,'{\"attributes\": {\"status\": 1, \"business_name\": \"Central Hub Operations\", \"current_balance\": \"0.00\"}}',NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(1463,'User','created','App\\Models\\User','created',630,NULL,NULL,'{\"attributes\": {\"name\": \"Regional Branch Ops Lead\", \"email\": \"branch.ops+reg-001@baraka.sanaa.co\"}}',NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(1464,'User','updated','App\\Models\\User','updated',630,NULL,NULL,'{\"old\": {\"name\": \"Regional Branch Ops Lead\", \"email\": \"branch.ops+reg-001@baraka.sanaa.co\"}, \"attributes\": {\"name\": \"Regional Branch Ops Lead\", \"email\": \"branch.ops+reg-001@baraka.sanaa.co\"}}',NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(1465,'BranchManager','updated branch manager: Regional Branch Operations','App\\Models\\Backend\\BranchManager','updated',1,NULL,NULL,'{\"old\": {\"status\": 1, \"business_name\": null, \"current_balance\": \"0.00\"}, \"attributes\": {\"status\": 1, \"business_name\": \"Regional Branch Operations\", \"current_balance\": \"0.00\"}}',NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(1466,'User','created','App\\Models\\User','created',631,NULL,NULL,'{\"attributes\": {\"name\": \"Local Branch Ops Lead\", \"email\": \"branch.ops+loc-001@baraka.sanaa.co\"}}',NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(1467,'User','updated','App\\Models\\User','updated',631,NULL,NULL,'{\"old\": {\"name\": \"Local Branch Ops Lead\", \"email\": \"branch.ops+loc-001@baraka.sanaa.co\"}, \"attributes\": {\"name\": \"Local Branch Ops Lead\", \"email\": \"branch.ops+loc-001@baraka.sanaa.co\"}}',NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(1468,'BranchManager','created branch manager: Local Branch Operations','App\\Models\\Backend\\BranchManager','created',3,NULL,NULL,'{\"attributes\": {\"status\": 1, \"business_name\": \"Local Branch Operations\", \"current_balance\": \"0.00\"}}',NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(1469,'User','created','App\\Models\\User','created',632,NULL,NULL,'{\"attributes\": {\"name\": \"Layla Al-Dosari\", \"email\": \"layla.al.dosari.HUB-001.1@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:29:07','2025-11-07 12:29:07'),(1470,'BranchWorker','created branch worker: Layla Al-Dosari','App\\Models\\Backend\\BranchWorker','created',2,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_SUPERVISOR\", \"status\": 1, \"assigned_at\": \"2025-10-23T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1471,'User','created','App\\Models\\User','created',633,NULL,NULL,'{\"attributes\": {\"name\": \"Yousef Al-Shammari\", \"email\": \"yousef.al.shammari.HUB-001.2@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1472,'BranchWorker','created branch worker: Yousef Al-Shammari','App\\Models\\Backend\\BranchWorker','created',3,NULL,NULL,'{\"attributes\": {\"role\": \"DISPATCHER\", \"status\": 1, \"assigned_at\": \"2025-04-26T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1473,'User','created','App\\Models\\User','created',634,NULL,NULL,'{\"attributes\": {\"name\": \"Nora Al-Ghamdi\", \"email\": \"nora.al.ghamdi.HUB-001.3@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1474,'BranchWorker','created branch worker: Nora Al-Ghamdi','App\\Models\\Backend\\BranchWorker','created',4,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-07-11T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1475,'User','created','App\\Models\\User','created',635,NULL,NULL,'{\"attributes\": {\"name\": \"Abdullah Al-Mutairi\", \"email\": \"abdullah.al.mutairi.HUB-001.4@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1476,'BranchWorker','created branch worker: Abdullah Al-Mutairi','App\\Models\\Backend\\BranchWorker','created',5,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-10-07T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1477,'User','created','App\\Models\\User','created',636,NULL,NULL,'{\"attributes\": {\"name\": \"Hala Al-Subaie\", \"email\": \"hala.al.subaie.HUB-001.5@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1478,'BranchWorker','created branch worker: Hala Al-Subaie','App\\Models\\Backend\\BranchWorker','created',6,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-05-10T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1479,'User','created','App\\Models\\User','created',637,NULL,NULL,'{\"attributes\": {\"name\": \"Saad Al-Enezi\", \"email\": \"saad.al.enezi.HUB-001.6@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1480,'BranchWorker','created branch worker: Saad Al-Enezi','App\\Models\\Backend\\BranchWorker','created',7,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-06-26T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1481,'User','created','App\\Models\\User','created',638,NULL,NULL,'{\"attributes\": {\"name\": \"Rania Al-Asmari\", \"email\": \"rania.al.asmari.HUB-001.7@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1482,'BranchWorker','created branch worker: Rania Al-Asmari','App\\Models\\Backend\\BranchWorker','created',8,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-06-03T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(1483,'User','created','App\\Models\\User','created',639,NULL,NULL,'{\"attributes\": {\"name\": \"Fahad Al-Shahrani\", \"email\": \"fahad.al.shahrani.HUB-001.8@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1484,'BranchWorker','created branch worker: Fahad Al-Shahrani','App\\Models\\Backend\\BranchWorker','created',9,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-04-13T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1485,'User','created','App\\Models\\User','created',640,NULL,NULL,'{\"attributes\": {\"name\": \"Maha Al-Balawi\", \"email\": \"maha.al.balawi.HUB-001.9@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1486,'BranchWorker','created branch worker: Maha Al-Balawi','App\\Models\\Backend\\BranchWorker','created',10,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-04-15T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1487,'User','created','App\\Models\\User','created',641,NULL,NULL,'{\"attributes\": {\"name\": \"Sultan Al-Dawsari\", \"email\": \"sultan.al.dawsari.HUB-001.10@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1488,'BranchWorker','created branch worker: Sultan Al-Dawsari','App\\Models\\Backend\\BranchWorker','created',11,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-10-22T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1489,'User','created','App\\Models\\User','created',642,NULL,NULL,'{\"attributes\": {\"name\": \"Lina Al-Qadir\", \"email\": \"lina.al.qadir.HUB-001.11@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1490,'BranchWorker','created branch worker: Lina Al-Qadir','App\\Models\\Backend\\BranchWorker','created',12,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-03-30T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1491,'User','created','App\\Models\\User','created',643,NULL,NULL,'{\"attributes\": {\"name\": \"Faisal Al-Zahrani\", \"email\": \"faisal.al.zahrani.HUB-001.12@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1492,'BranchWorker','created branch worker: Faisal Al-Zahrani','App\\Models\\Backend\\BranchWorker','created',13,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-03-29T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1493,'User','created','App\\Models\\User','created',644,NULL,NULL,'{\"attributes\": {\"name\": \"Aisha Al-Mazroa\", \"email\": \"aisha.al.mazroa.HUB-001.13@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1494,'BranchWorker','created branch worker: Aisha Al-Mazroa','App\\Models\\Backend\\BranchWorker','created',14,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-07-03T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1495,'User','created','App\\Models\\User','created',645,NULL,NULL,'{\"attributes\": {\"name\": \"Bandar Al-Harbi\", \"email\": \"bandar.al.harbi.HUB-001.14@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1496,'BranchWorker','created branch worker: Bandar Al-Harbi','App\\Models\\Backend\\BranchWorker','created',15,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-09-20T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(1497,'User','created','App\\Models\\User','created',646,NULL,NULL,'{\"attributes\": {\"name\": \"Nouf Al-Otaibi\", \"email\": \"nouf.al.otaibi.REG-001.15@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1498,'BranchWorker','created branch worker: Nouf Al-Otaibi','App\\Models\\Backend\\BranchWorker','created',16,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_SUPERVISOR\", \"status\": 1, \"assigned_at\": \"2025-05-20T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1499,'User','created','App\\Models\\User','created',647,NULL,NULL,'{\"attributes\": {\"name\": \"Turki Al-Rashid\", \"email\": \"turki.al.rashid.REG-001.16@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1500,'BranchWorker','created branch worker: Turki Al-Rashid','App\\Models\\Backend\\BranchWorker','created',17,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-06-01T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1501,'User','created','App\\Models\\User','created',648,NULL,NULL,'{\"attributes\": {\"name\": \"Joud Al-Ghamdi\", \"email\": \"joud.al.ghamdi.REG-001.17@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1502,'BranchWorker','created branch worker: Joud Al-Ghamdi','App\\Models\\Backend\\BranchWorker','created',18,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-05-21T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1503,'User','created','App\\Models\\User','created',649,NULL,NULL,'{\"attributes\": {\"name\": \"Nasser Al-Qahtani\", \"email\": \"nasser.al.qahtani.REG-001.18@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1504,'BranchWorker','created branch worker: Nasser Al-Qahtani','App\\Models\\Backend\\BranchWorker','created',19,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-06-25T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1505,'User','created','App\\Models\\User','created',650,NULL,NULL,'{\"attributes\": {\"name\": \"Salma Al-Malki\", \"email\": \"salma.al.malki.REG-001.19@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1506,'BranchWorker','created branch worker: Salma Al-Malki','App\\Models\\Backend\\BranchWorker','created',20,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-04-15T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1507,'User','created','App\\Models\\User','created',651,NULL,NULL,'{\"attributes\": {\"name\": \"Waleed Al-Shamrani\", \"email\": \"waleed.al.shamrani.REG-001.20@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1508,'BranchWorker','created branch worker: Waleed Al-Shamrani','App\\Models\\Backend\\BranchWorker','created',21,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-07-15T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1509,'User','created','App\\Models\\User','created',652,NULL,NULL,'{\"attributes\": {\"name\": \"Hind Al-Zahrani\", \"email\": \"hind.al.zahrani.REG-001.21@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1510,'BranchWorker','created branch worker: Hind Al-Zahrani','App\\Models\\Backend\\BranchWorker','created',22,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-08-21T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1511,'User','created','App\\Models\\User','created',653,NULL,NULL,'{\"attributes\": {\"name\": \"Majed Al-Anazi\", \"email\": \"majed.al.anazi.REG-001.22@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1512,'BranchWorker','created branch worker: Majed Al-Anazi','App\\Models\\Backend\\BranchWorker','created',23,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-03-19T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(1513,'User','created','App\\Models\\User','created',654,NULL,NULL,'{\"attributes\": {\"name\": \"Reem Al-Saud\", \"email\": \"reem.al.saud.REG-001.23@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1514,'BranchWorker','created branch worker: Reem Al-Saud','App\\Models\\Backend\\BranchWorker','created',24,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-05-25T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1515,'User','created','App\\Models\\User','created',655,NULL,NULL,'{\"attributes\": {\"name\": \"Talal Al-Ghamdi\", \"email\": \"talal.al.ghamdi.LOC-001.24@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1516,'BranchWorker','created branch worker: Talal Al-Ghamdi','App\\Models\\Backend\\BranchWorker','created',25,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-05-02T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1517,'User','created','App\\Models\\User','created',656,NULL,NULL,'{\"attributes\": {\"name\": \"Lama Al-Harbi\", \"email\": \"lama.al.harbi.LOC-001.25@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1518,'BranchWorker','created branch worker: Lama Al-Harbi','App\\Models\\Backend\\BranchWorker','created',26,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-10-14T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1519,'User','created','App\\Models\\User','created',657,NULL,NULL,'{\"attributes\": {\"name\": \"Saud Al-Dosari\", \"email\": \"saud.al.dosari.LOC-001.26@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1520,'BranchWorker','created branch worker: Saud Al-Dosari','App\\Models\\Backend\\BranchWorker','created',27,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-06-19T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1521,'User','created','App\\Models\\User','created',658,NULL,NULL,'{\"attributes\": {\"name\": \"Jana Al-Mutairi\", \"email\": \"jana.al.mutairi.LOC-001.27@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1522,'BranchWorker','created branch worker: Jana Al-Mutairi','App\\Models\\Backend\\BranchWorker','created',28,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-07-03T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1523,'User','created','App\\Models\\User','created',659,NULL,NULL,'{\"attributes\": {\"name\": \"Mishal Al-Shammari\", \"email\": \"mishal.al.shammari.LOC-001.28@baraka.sanaa.co\"}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1524,'BranchWorker','created branch worker: Mishal Al-Shammari','App\\Models\\Backend\\BranchWorker','created',29,NULL,NULL,'{\"attributes\": {\"role\": \"OPS_AGENT\", \"status\": 1, \"assigned_at\": \"2025-08-12T00:00:00.000000Z\", \"unassigned_at\": null}}',NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(1525,'shipment','created shipment','App\\Models\\Shipment','created',140,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"147.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": null}}',NULL,'2025-11-07 12:33:54','2025-11-07 12:33:54'),(1526,'shipment','created shipment','App\\Models\\Shipment','created',141,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 8, \"price_amount\": \"185.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": null}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1527,'shipment','created shipment','App\\Models\\Shipment','created',142,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 8, \"price_amount\": \"53.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": null}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1528,'shipment','created shipment','App\\Models\\Shipment','created',143,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 631, \"price_amount\": \"128.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": null}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1529,'shipment','created shipment','App\\Models\\Shipment','created',144,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 542, \"price_amount\": \"197.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": null}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1530,'shipment','created shipment','App\\Models\\Shipment','created',145,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 545, \"price_amount\": \"296.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": null}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1531,'shipment','created shipment','App\\Models\\Shipment','created',146,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 542, \"price_amount\": \"274.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": null}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1532,'shipment','created shipment','App\\Models\\Shipment','created',147,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 7, \"price_amount\": \"311.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": null}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1533,'shipment','created shipment','App\\Models\\Shipment','created',148,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"404.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": null}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1534,'shipment','created shipment','App\\Models\\Shipment','created',149,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 630, \"price_amount\": \"135.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1535,'shipment','created shipment','App\\Models\\Shipment','created',150,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 538, \"price_amount\": \"204.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1536,'shipment','created shipment','App\\Models\\Shipment','created',151,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 2, \"price_amount\": \"480.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1537,'shipment','created shipment','App\\Models\\Shipment','created',152,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 2, \"price_amount\": \"259.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1538,'shipment','created shipment','App\\Models\\Shipment','created',153,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 544, \"price_amount\": \"189.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1539,'shipment','created shipment','App\\Models\\Shipment','created',154,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 8, \"price_amount\": \"108.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1540,'shipment','created shipment','App\\Models\\Shipment','created',155,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"486.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1541,'shipment','created shipment','App\\Models\\Shipment','created',156,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 542, \"price_amount\": \"50.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1542,'shipment','created shipment','App\\Models\\Shipment','created',157,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 2, \"price_amount\": \"457.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1543,'shipment','created shipment','App\\Models\\Shipment','created',158,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 542, \"price_amount\": \"455.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1544,'shipment','created shipment','App\\Models\\Shipment','created',159,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 629, \"price_amount\": \"499.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1545,'shipment','created shipment','App\\Models\\Shipment','created',160,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 1, \"price_amount\": \"360.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1546,'shipment','created shipment','App\\Models\\Shipment','created',161,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"264.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1547,'shipment','created shipment','App\\Models\\Shipment','created',162,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 1, \"price_amount\": \"281.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1548,'shipment','created shipment','App\\Models\\Shipment','created',163,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 544, \"price_amount\": \"286.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1549,'shipment','created shipment','App\\Models\\Shipment','created',164,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 631, \"price_amount\": \"429.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1550,'shipment','created shipment','App\\Models\\Shipment','created',165,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"418.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1551,'shipment','created shipment','App\\Models\\Shipment','created',166,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 545, \"price_amount\": \"366.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1552,'shipment','created shipment','App\\Models\\Shipment','created',167,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 629, \"price_amount\": \"389.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1553,'shipment','created shipment','App\\Models\\Shipment','created',168,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 2, \"price_amount\": \"479.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1554,'shipment','created shipment','App\\Models\\Shipment','created',169,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 631, \"price_amount\": \"289.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1555,'shipment','created shipment','App\\Models\\Shipment','created',170,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 545, \"price_amount\": \"250.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1556,'shipment','created shipment','App\\Models\\Shipment','created',171,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 7, \"price_amount\": \"369.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1557,'shipment','created shipment','App\\Models\\Shipment','created',172,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 542, \"price_amount\": \"71.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1558,'shipment','created shipment','App\\Models\\Shipment','created',173,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 545, \"price_amount\": \"312.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1559,'shipment','created shipment','App\\Models\\Shipment','created',174,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 8, \"price_amount\": \"390.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1560,'shipment','created shipment','App\\Models\\Shipment','created',175,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 1, \"price_amount\": \"412.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1561,'shipment','created shipment','App\\Models\\Shipment','created',176,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 8, \"price_amount\": \"288.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1562,'shipment','created shipment','App\\Models\\Shipment','created',177,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 631, \"price_amount\": \"311.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1563,'shipment','created shipment','App\\Models\\Shipment','created',178,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 2, \"price_amount\": \"473.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1564,'shipment','created shipment','App\\Models\\Shipment','created',179,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 629, \"price_amount\": \"464.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1565,'shipment','created shipment','App\\Models\\Shipment','created',180,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 542, \"price_amount\": \"60.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1566,'shipment','created shipment','App\\Models\\Shipment','created',181,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 545, \"price_amount\": \"388.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:12','2025-11-07 12:34:12'),(1567,'shipment','created shipment','App\\Models\\Shipment','created',182,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 535, \"price_amount\": \"313.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1568,'shipment','created shipment','App\\Models\\Shipment','created',183,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 538, \"price_amount\": \"174.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1569,'shipment','created shipment','App\\Models\\Shipment','created',184,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 545, \"price_amount\": \"329.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1570,'shipment','created shipment','App\\Models\\Shipment','created',185,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 7, \"price_amount\": \"90.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1571,'shipment','created shipment','App\\Models\\Shipment','created',186,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 630, \"price_amount\": \"238.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1572,'shipment','created shipment','App\\Models\\Shipment','created',187,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"443.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1573,'shipment','created shipment','App\\Models\\Shipment','created',188,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 535, \"price_amount\": \"438.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1574,'shipment','created shipment','App\\Models\\Shipment','created',189,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 545, \"price_amount\": \"97.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1575,'shipment','created shipment','App\\Models\\Shipment','created',190,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 544, \"price_amount\": \"488.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1576,'shipment','created shipment','App\\Models\\Shipment','created',191,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 542, \"price_amount\": \"407.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1577,'shipment','created shipment','App\\Models\\Shipment','created',192,NULL,NULL,'{\"attributes\": {\"status\": \"\", \"client_id\": 1, \"customer_id\": 535, \"price_amount\": \"419.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1578,'shipment','created shipment','App\\Models\\Shipment','created',193,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 631, \"price_amount\": \"107.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1579,'shipment','created shipment','App\\Models\\Shipment','created',194,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 538, \"price_amount\": \"344.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1580,'shipment','created shipment','App\\Models\\Shipment','created',195,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"410.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1581,'shipment','created shipment','App\\Models\\Shipment','created',196,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 538, \"price_amount\": \"172.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1582,'shipment','created shipment','App\\Models\\Shipment','created',197,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"115.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1583,'shipment','created shipment','App\\Models\\Shipment','created',198,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 538, \"price_amount\": \"144.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1584,'shipment','created shipment','App\\Models\\Shipment','created',199,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 629, \"price_amount\": \"140.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1585,'shipment','created shipment','App\\Models\\Shipment','created',200,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 8, \"price_amount\": \"146.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1586,'shipment','created shipment','App\\Models\\Shipment','created',201,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 1, \"price_amount\": \"217.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1587,'shipment','created shipment','App\\Models\\Shipment','created',202,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 630, \"price_amount\": \"162.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1588,'shipment','created shipment','App\\Models\\Shipment','created',203,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 631, \"price_amount\": \"260.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1589,'shipment','created shipment','App\\Models\\Shipment','created',204,NULL,NULL,'{\"attributes\": {\"status\": \"out_for_delivery\", \"client_id\": 1, \"customer_id\": 7, \"price_amount\": \"305.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1590,'shipment','created shipment','App\\Models\\Shipment','created',205,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 631, \"price_amount\": \"189.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1591,'shipment','created shipment','App\\Models\\Shipment','created',206,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 629, \"price_amount\": \"495.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1592,'shipment','created shipment','App\\Models\\Shipment','created',207,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 629, \"price_amount\": \"283.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1593,'shipment','created shipment','App\\Models\\Shipment','created',208,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 544, \"price_amount\": \"184.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1594,'shipment','created shipment','App\\Models\\Shipment','created',209,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 1, \"price_amount\": \"184.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1595,'shipment','created shipment','App\\Models\\Shipment','created',210,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 630, \"price_amount\": \"457.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1596,'shipment','created shipment','App\\Models\\Shipment','created',211,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 2, \"price_amount\": \"246.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1597,'shipment','created shipment','App\\Models\\Shipment','created',212,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 544, \"price_amount\": \"411.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1598,'shipment','created shipment','App\\Models\\Shipment','created',213,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 630, \"price_amount\": \"281.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1599,'shipment','created shipment','App\\Models\\Shipment','created',214,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 544, \"price_amount\": \"102.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1600,'shipment','created shipment','App\\Models\\Shipment','created',215,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 544, \"price_amount\": \"69.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1601,'shipment','created shipment','App\\Models\\Shipment','created',216,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 544, \"price_amount\": \"437.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1602,'shipment','created shipment','App\\Models\\Shipment','created',217,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 630, \"price_amount\": \"473.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1603,'shipment','created shipment','App\\Models\\Shipment','created',218,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 535, \"price_amount\": \"95.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1604,'shipment','created shipment','App\\Models\\Shipment','created',219,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 8, \"price_amount\": \"219.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1605,'shipment','created shipment','App\\Models\\Shipment','created',220,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 631, \"price_amount\": \"370.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1606,'shipment','created shipment','App\\Models\\Shipment','created',221,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 8, \"price_amount\": \"450.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1607,'shipment','created shipment','App\\Models\\Shipment','created',222,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 542, \"price_amount\": \"480.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1608,'shipment','created shipment','App\\Models\\Shipment','created',223,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 2, \"price_amount\": \"319.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1609,'shipment','created shipment','App\\Models\\Shipment','created',224,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 2, \"price_amount\": \"147.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1610,'shipment','created shipment','App\\Models\\Shipment','created',225,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 535, \"price_amount\": \"236.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1611,'shipment','created shipment','App\\Models\\Shipment','created',226,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 545, \"price_amount\": \"385.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1612,'shipment','created shipment','App\\Models\\Shipment','created',227,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 538, \"price_amount\": \"233.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1613,'shipment','created shipment','App\\Models\\Shipment','created',228,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 630, \"price_amount\": \"186.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1614,'shipment','created shipment','App\\Models\\Shipment','created',229,NULL,NULL,'{\"attributes\": {\"status\": \"delivered\", \"client_id\": 1, \"customer_id\": 535, \"price_amount\": \"349.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1615,'shipment','created shipment','App\\Models\\Shipment','created',230,NULL,NULL,'{\"attributes\": {\"status\": \"exception\", \"client_id\": 1, \"customer_id\": 535, \"price_amount\": \"478.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 3, \"assigned_worker_id\": 1}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1616,'shipment','created shipment','App\\Models\\Shipment','created',231,NULL,NULL,'{\"attributes\": {\"status\": \"exception\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"482.00\", \"dest_branch_id\": 2, \"origin_branch_id\": 1, \"assigned_worker_id\": 2}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1617,'shipment','created shipment','App\\Models\\Shipment','created',232,NULL,NULL,'{\"attributes\": {\"status\": \"exception\", \"client_id\": 1, \"customer_id\": 546, \"price_amount\": \"193.00\", \"dest_branch_id\": 1, \"origin_branch_id\": 2, \"assigned_worker_id\": 16}}',NULL,'2025-11-07 12:34:13','2025-11-07 12:34:13'),(1618,'Branch','created branch: Riyadh Central Hub','App\\Models\\Backend\\Branch','created',442,NULL,NULL,'{\"attributes\": {\"code\": \"HUB-RYD-001\", \"name\": \"Riyadh Central Hub\", \"type\": \"HUB\", \"is_hub\": true, \"status\": 1}}',NULL,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(1619,'Branch','created branch: Jeddah Regional Center','App\\Models\\Backend\\Branch','created',443,NULL,NULL,'{\"attributes\": {\"code\": \"REG-JED-001\", \"name\": \"Jeddah Regional Center\", \"type\": \"REGIONAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(1620,'Branch','created branch: Dammam Regional Center','App\\Models\\Backend\\Branch','created',444,NULL,NULL,'{\"attributes\": {\"code\": \"REG-DMM-001\", \"name\": \"Dammam Regional Center\", \"type\": \"REGIONAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(1621,'Branch','created branch: Jeddah North Branch','App\\Models\\Backend\\Branch','created',445,NULL,NULL,'{\"attributes\": {\"code\": \"LOC-JED-N01\", \"name\": \"Jeddah North Branch\", \"type\": \"LOCAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(1622,'Branch','created branch: Jeddah South Branch','App\\Models\\Backend\\Branch','created',446,NULL,NULL,'{\"attributes\": {\"code\": \"LOC-JED-S01\", \"name\": \"Jeddah South Branch\", \"type\": \"LOCAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(1623,'Branch','created branch: Dammam City Branch','App\\Models\\Backend\\Branch','created',447,NULL,NULL,'{\"attributes\": {\"code\": \"LOC-DMM-C01\", \"name\": \"Dammam City Branch\", \"type\": \"LOCAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-07 20:26:56','2025-11-07 20:26:56'),(1624,'Branch','created branch: Riyadh North Branch','App\\Models\\Backend\\Branch','created',448,NULL,NULL,'{\"attributes\": {\"code\": \"LOC-RYD-N01\", \"name\": \"Riyadh North Branch\", \"type\": \"LOCAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-07 20:26:56','2025-11-07 20:26:56'),(1625,'Branch','created branch: Riyadh South Branch','App\\Models\\Backend\\Branch','created',449,NULL,NULL,'{\"attributes\": {\"code\": \"LOC-RYD-S01\", \"name\": \"Riyadh South Branch\", \"type\": \"LOCAL\", \"is_hub\": false, \"status\": 1}}',NULL,'2025-11-07 20:26:56','2025-11-07 20:26:56'),(1626,'User','created','App\\Models\\User','created',660,NULL,NULL,'{\"attributes\": {\"name\": \"drtrt\", \"email\": \"reet@fdfdg.vb\"}}',NULL,'2025-11-08 20:41:43','2025-11-08 20:41:43');
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
INSERT INTO `blogs` VALUES (1,'Et voluptatem maxime minima quasi minus quia.',NULL,'Vel voluptatibus consectetur totam exercitationem qui officiis saepe aperiam ut est consectetur eos quia consequuntur provident itaque eligendi ut consectetur iusto et quidem cumque beatae consequatur ea et debitis ab ipsum fugiat doloremque aut facilis magnam enim earum neque error quo aut ea aut voluptates itaque harum soluta quae porro minima maxime velit est perspiciatis voluptatem perspiciatis consequatur quidem provident iste accusantium odio iusto cum voluptatem sed est harum architecto eveniet ut illum repudiandae reiciendis deserunt nisi sapiente enim ratione perferendis itaque fuga eos modi non rerum molestiae dicta ratione ab voluptatum ad enim aliquam rerum et possimus ipsam qui porro hic sit rerum ab ipsa animi tenetur debitis culpa non deserunt rerum voluptatem ex ullam assumenda dolorum tenetur dignissimos aliquid similique aliquid asperiores ut labore et dolore omnis nostrum.','0',1,1,'2','2025-06-29 11:29:49','2025-11-07 18:04:21'),(2,'Ut rerum et debitis quo itaque qui ducimus tempore.',NULL,'Nihil est neque quos incidunt nulla quis sunt temporibus odio sed ullam nobis similique cum quidem laborum sequi labore nostrum sunt ut inventore nihil laboriosam molestias fuga similique architecto temporibus vitae laudantium dolore soluta voluptatum alias quis et aperiam expedita ex tempore magni autem molestiae porro optio sunt quae aut rerum delectus eum sequi et et voluptatem ducimus beatae cumque vel exercitationem dolorum molestias occaecati rem minus rem quo modi dolores excepturi quas quod ipsa aut fugiat vel est et libero voluptas quas unde eos sequi nostrum dolore dolore explicabo nesciunt sint debitis sit sunt maiores sunt culpa necessitatibus minima facilis autem quibusdam.','1',1,1,'3','2025-06-29 11:29:49','2025-11-07 18:04:13'),(3,'Sequi quaerat non unde blanditiis tempora dolore nihil aut recusandae cupiditate molestiae.',NULL,'Corporis facilis sed voluptatem eos eum sed occaecati aut in ut et neque porro minus perferendis aut sunt similique nihil tenetur et sequi officia possimus laborum maiores sapiente consequuntur neque consequuntur dolore omnis dolor error earum asperiores impedit quam eum rerum provident ipsam aut quis assumenda cumque incidunt ut aut ut est ex nesciunt et aspernatur reprehenderit sint voluptas dolore beatae et ut consequuntur ex molestiae praesentium nihil nostrum fuga iusto cum quam labore cumque voluptates repellendus vel qui atque atque quia.','2',1,1,'2','2025-06-29 11:29:49','2025-11-07 18:04:21'),(4,'Quia laudantium veniam reiciendis quo reiciendis occaecati rerum expedita soluta asperiores.',NULL,'Ut officia enim voluptates sunt vel in ut architecto quidem laboriosam cumque dignissimos cupiditate accusamus voluptates consequatur laborum architecto alias et dignissimos quod explicabo quia temporibus voluptates cupiditate modi eius officiis maxime veniam modi et et at nobis deserunt repellendus alias porro dolorem iure enim nemo natus exercitationem omnis cupiditate dolor vel eum dolor voluptatibus sed excepturi ea nostrum iure sint asperiores eos deserunt ipsum qui cum ea quia architecto et voluptatem hic ut quos iure reiciendis aut unde dicta magnam quia vero voluptatibus quibusdam impedit est omnis distinctio vitae maxime ducimus deleniti repellat temporibus asperiores aut facere sunt labore ipsam illum expedita molestias dicta doloremque pariatur expedita nesciunt laboriosam numquam laborum mollitia dolore exercitationem magnam sed voluptate reiciendis consectetur explicabo quisquam id.','3',1,1,'0','2025-06-29 11:29:49','2025-06-29 11:29:49'),(5,'Ut molestiae dolor est neque eaque est corrupti qui qui pariatur error.',NULL,'Ad repellat consectetur eos omnis earum sed quas omnis qui est ducimus quo voluptatem culpa sed mollitia suscipit id quidem et corrupti sint eaque sed incidunt voluptas quo totam aut aperiam repellat sint quibusdam in quo in harum deserunt optio sed temporibus dolor est adipisci quis fugiat officia similique illum eaque facere est ipsum qui totam blanditiis veritatis excepturi vel adipisci nihil autem perferendis est nesciunt fuga cupiditate accusamus ipsam pariatur qui velit enim et temporibus itaque ducimus exercitationem ut iure omnis sequi numquam dolores nihil dolor odit deserunt repellat earum culpa et hic sit optio quia dolore magnam voluptatem nihil porro minima esse ut distinctio.','4',1,1,'0','2025-06-29 11:29:49','2025-06-29 11:29:49'),(6,'Fuga nam possimus dicta ad iste quaerat architecto cum.',NULL,'Molestiae fugiat minima et eos sequi qui incidunt quae sed voluptatibus adipisci quae nam illo error perferendis praesentium ea asperiores molestias et voluptate perferendis similique blanditiis quod aliquam labore dolores ad atque esse iusto sequi accusamus a et voluptates pariatur reprehenderit omnis minima consequatur nisi hic qui tempore ea voluptatem iure aspernatur quam quam neque et atque aut ipsam ut nostrum qui aut consequatur illo et dolores eos voluptatibus quibusdam eveniet sequi molestias perferendis mollitia consequatur dolores commodi aspernatur qui veniam.','5',1,1,'2','2025-06-29 11:29:50','2025-11-07 18:04:28'),(7,'Possimus rerum architecto sint quia distinctio quia consequatur expedita perspiciatis deserunt.',NULL,'Placeat et qui non omnis neque ut itaque exercitationem est ipsam dolor qui dolorem voluptate omnis modi aut nemo ut sunt consequuntur saepe assumenda quaerat sunt ut minima illum mollitia nesciunt aut similique vel nostrum voluptatem quaerat repellendus et ex blanditiis in dolore consequatur vel quia ea soluta est omnis magni provident nisi nihil aut mollitia dolorem dicta et est et temporibus blanditiis adipisci veniam deleniti quo id et eaque nostrum aut et culpa eius aut ut ut impedit ipsa quos assumenda consequatur et quod labore qui sint consequatur quo at suscipit ducimus maiores fugiat molestiae amet fugit ex.','6',1,1,'1','2025-06-29 11:29:50','2025-11-07 18:04:21'),(8,'Velit hic tempore quae eum nulla quisquam et ut ipsum exercitationem blanditiis cupiditate.',NULL,'Ullam repudiandae consequuntur reiciendis et ex consectetur delectus minima itaque architecto voluptatibus quia possimus perspiciatis ut qui doloribus voluptates harum qui voluptatem ut sed qui consectetur similique a harum soluta illo vero laboriosam est optio iusto quia est aliquam saepe aliquam eaque atque incidunt rem voluptatem inventore et temporibus ullam occaecati sed aspernatur amet esse cum nesciunt provident soluta totam quaerat quam minus.','7',1,1,'1','2025-06-29 11:29:50','2025-11-07 18:04:21'),(9,'Sit corrupti enim autem rerum quis voluptatem cumque iste quisquam amet in tempore.',NULL,'Non id nemo nesciunt molestiae sit id labore temporibus blanditiis rerum sapiente quis nulla ab aut dolorem impedit velit tempore sed exercitationem natus soluta ut eligendi tempora quis aut eos optio quo qui possimus laborum ut ut eveniet est et molestias quam eaque omnis assumenda omnis ea quam consequatur laudantium hic voluptatibus maxime exercitationem qui et rerum consectetur qui minus consequuntur ut provident quas praesentium aut et voluptas corporis dolores vitae nisi quibusdam aliquid fugit dolor sit delectus delectus blanditiis laboriosam voluptas itaque repellat at ipsa facere facere ut ducimus odio placeat et sequi qui rerum maxime excepturi doloremque consequuntur a quod hic velit distinctio aut quos provident nemo eum deleniti illum sed qui et sunt occaecati sequi laborum praesentium aliquid eum deserunt dolorem quo iste deserunt autem non saepe harum atque facilis in voluptate rerum perspiciatis ex eos labore natus.','8',1,1,'1','2025-06-29 11:29:50','2025-11-07 18:04:21'),(10,'Libero aut totam quia magnam non ab.',NULL,'Neque sunt quia tempore quam pariatur voluptatem maxime magnam sint porro voluptatibus aperiam sunt enim iusto perspiciatis sunt occaecati sed delectus ipsa ut ullam quos quia culpa tempore et enim aspernatur suscipit qui minima aut aspernatur beatae quasi qui sit enim fuga laboriosam similique inventore provident corporis quibusdam ut rerum impedit aspernatur velit ipsum ut similique quos aut neque non cupiditate laboriosam voluptatem et eaque provident ut autem qui laboriosam velit consequatur placeat omnis qui reiciendis voluptatem odio dolores vitae error.','9',1,1,'1','2025-06-29 11:29:50','2025-11-07 18:04:13');
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_managers`
--

LOCK TABLES `branch_managers` WRITE;
/*!40000 ALTER TABLE `branch_managers` DISABLE KEYS */;
INSERT INTO `branch_managers` VALUES (1,2,630,'branch_manager','2025-11-06 06:32:59','Regional Branch Operations',0.00,NULL,NULL,NULL,'{\"seeded_at\": \"2025-11-07T11:34:38+00:00\", \"seeded_demo\": true}',1,'2025-11-06 05:32:59','2025-11-07 10:34:38'),(2,1,629,'branch_manager','2025-11-07 11:34:38','Central Hub Operations',0.00,NULL,NULL,NULL,'{\"seeded_at\": \"2025-11-07T11:34:38+00:00\", \"seeded_demo\": true}',1,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(3,3,631,'branch_manager','2025-11-07 11:34:38','Local Branch Operations',0.00,NULL,NULL,NULL,'{\"seeded_at\": \"2025-11-07T11:34:38+00:00\", \"seeded_demo\": true}',1,'2025-11-07 10:34:38','2025-11-07 10:34:38');
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
) ENGINE=InnoDB AUTO_INCREMENT=30 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branch_workers`
--

LOCK TABLES `branch_workers` WRITE;
/*!40000 ALTER TABLE `branch_workers` DISABLE KEYS */;
INSERT INTO `branch_workers` VALUES (1,3,9,'courier',NULL,'ACTIVE',NULL,NULL,NULL,NULL,NULL,'2025-11-06 05:40:09',NULL,NULL,NULL,1,'2025-11-06 05:32:59','2025-11-06 05:40:09'),(2,1,632,'OPS_SUPERVISOR','Team Supervisor','ACTIVE','+25671000001',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": true, \"can_update_status\": true}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',75.00,'2025-10-22 22:00:00',NULL,'Area supervisor','{\"zone\": \"HUB-001\", \"experience_years\": 12, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(3,1,633,'DISPATCHER',NULL,'ACTIVE','+25671000002',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": true}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',60.00,'2025-04-25 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 9, \"vehicle_assigned\": false}',1,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(4,1,634,'OPS_AGENT',NULL,'ACTIVE','+25671000003',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-07-10 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 9, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(5,1,635,'OPS_AGENT',NULL,'ACTIVE','+25671000004',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-10-06 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 1, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(6,1,636,'OPS_AGENT',NULL,'ACTIVE','+25671000005',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-05-09 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 4, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(7,1,637,'OPS_AGENT',NULL,'ACTIVE','+25671000006',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-06-25 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 12, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(8,1,638,'OPS_AGENT',NULL,'ACTIVE','+25671000007',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-06-02 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 1, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(9,1,639,'OPS_AGENT',NULL,'ACTIVE','+25671000008',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-04-12 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 5, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(10,1,640,'OPS_AGENT',NULL,'ACTIVE','+25671000009',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-04-14 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 12, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(11,1,641,'OPS_AGENT',NULL,'ACTIVE','+25671000010',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-10-21 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 4, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(12,1,642,'OPS_AGENT',NULL,'ACTIVE','+25671000011',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-03-29 23:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 12, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(13,1,643,'OPS_AGENT',NULL,'ACTIVE','+25671000012',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-03-28 23:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 10, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(14,1,644,'OPS_AGENT',NULL,'ACTIVE','+25671000013',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-07-02 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 10, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(15,1,645,'OPS_AGENT',NULL,'ON_LEAVE','+25671000014',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-09-19 22:00:00',NULL,NULL,'{\"zone\": \"HUB-001\", \"experience_years\": 11, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(16,2,646,'OPS_SUPERVISOR','Team Supervisor','ACTIVE','+25671000015',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": true, \"can_update_status\": true}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',75.00,'2025-05-19 22:00:00',NULL,'Area supervisor','{\"zone\": \"REG-001\", \"experience_years\": 9, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(17,2,647,'OPS_AGENT',NULL,'ACTIVE','+25671000016',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-05-31 22:00:00',NULL,NULL,'{\"zone\": \"REG-001\", \"experience_years\": 11, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(18,2,648,'OPS_AGENT',NULL,'ACTIVE','+25671000017',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-05-20 22:00:00',NULL,NULL,'{\"zone\": \"REG-001\", \"experience_years\": 9, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(19,2,649,'OPS_AGENT',NULL,'ACTIVE','+25671000018',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-06-24 22:00:00',NULL,NULL,'{\"zone\": \"REG-001\", \"experience_years\": 11, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(20,2,650,'OPS_AGENT',NULL,'ACTIVE','+25671000019',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-04-14 22:00:00',NULL,NULL,'{\"zone\": \"REG-001\", \"experience_years\": 3, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(21,2,651,'OPS_AGENT',NULL,'ACTIVE','+25671000020',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-07-14 22:00:00',NULL,NULL,'{\"zone\": \"REG-001\", \"experience_years\": 10, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(22,2,652,'OPS_AGENT',NULL,'ACTIVE','+25671000021',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-08-20 22:00:00',NULL,NULL,'{\"zone\": \"REG-001\", \"experience_years\": 2, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(23,2,653,'OPS_AGENT',NULL,'ACTIVE','+25671000022',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-03-18 23:00:00',NULL,NULL,'{\"zone\": \"REG-001\", \"experience_years\": 7, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(24,2,654,'OPS_AGENT',NULL,'ON_LEAVE','+25671000023',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-05-24 22:00:00',NULL,NULL,'{\"zone\": \"REG-001\", \"experience_years\": 6, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(25,3,655,'OPS_AGENT',NULL,'ACTIVE','+25671000024',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-05-01 22:00:00',NULL,NULL,'{\"zone\": \"LOC-001\", \"experience_years\": 9, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(26,3,656,'OPS_AGENT',NULL,'ACTIVE','+25671000025',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-10-13 22:00:00',NULL,NULL,'{\"zone\": \"LOC-001\", \"experience_years\": 8, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(27,3,657,'OPS_AGENT',NULL,'ACTIVE','+25671000026',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-06-18 22:00:00',NULL,NULL,'{\"zone\": \"LOC-001\", \"experience_years\": 9, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(28,3,658,'OPS_AGENT',NULL,'ACTIVE','+25671000027',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-07-02 22:00:00',NULL,NULL,'{\"zone\": \"LOC-001\", \"experience_years\": 6, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(29,3,659,'OPS_AGENT',NULL,'ACTIVE','+25671000028',NULL,'{\"can_scan\": true, \"can_pickup\": true, \"can_deliver\": true, \"can_handle_cod\": true, \"can_manage_team\": false, \"can_update_status\": false}','{\"friday\": {\"end\": \"OFF\", \"start\": \"OFF\"}, \"monday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"sunday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"tuesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"saturday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"thursday\": {\"end\": \"17:00\", \"start\": \"08:00\"}, \"wednesday\": {\"end\": \"17:00\", \"start\": \"08:00\"}}',45.00,'2025-08-11 22:00:00',NULL,NULL,'{\"zone\": \"LOC-001\", \"experience_years\": 3, \"vehicle_assigned\": true}',1,'2025-11-07 12:30:41','2025-11-07 12:30:41');
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
) ENGINE=InnoDB AUTO_INCREMENT=450 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Central Hub','HUB-001','HUB',1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"capacity\": \"1000 parcels/day\"}',1,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(2,'Regional Branch','REG-001','REGIONAL',0,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"region\": \"North\"}',1,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(3,'Local Branch','LOC-001','LOCAL',0,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'{\"service_area\": \"Downtown\"}',1,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(442,'Riyadh Central Hub','HUB-RYD-001','HUB',1,NULL,'King Fahd Road, Riyadh, Saudi Arabia','+966112345678','hub.riyadh@baraka.sanaa.co',24.71360000,46.67530000,'\"{\\\"monday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"22:00\\\"},\\\"tuesday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"22:00\\\"},\\\"wednesday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"22:00\\\"},\\\"thursday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"22:00\\\"},\\\"friday\\\":{\\\"open\\\":\\\"14:00\\\",\\\"close\\\":\\\"22:00\\\"},\\\"saturday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"22:00\\\"},\\\"sunday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"22:00\\\"}}\"','\"[\\\"sorting\\\",\\\"processing\\\",\\\"customs\\\",\\\"international\\\",\\\"storage\\\"]\"','\"{\\\"capacity\\\":10000,\\\"sorting_lines\\\":5,\\\"loading_docks\\\":20}\"',1,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(443,'Jeddah Regional Center','REG-JED-001','REGIONAL',0,442,'Corniche Road, Jeddah, Saudi Arabia','+966126789012','regional.jeddah@baraka.sanaa.co',21.48580000,39.19250000,'\"{\\\"monday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"tuesday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"wednesday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"thursday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"friday\\\":{\\\"open\\\":\\\"14:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"saturday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"sunday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"}}\"','\"[\\\"sorting\\\",\\\"processing\\\",\\\"storage\\\",\\\"pickup\\\",\\\"delivery\\\"]\"','\"{\\\"capacity\\\":5000,\\\"sorting_lines\\\":3,\\\"loading_docks\\\":10}\"',1,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(444,'Dammam Regional Center','REG-DMM-001','REGIONAL',0,442,'King Saud Road, Dammam, Saudi Arabia','+966138901234','regional.dammam@baraka.sanaa.co',26.42070000,50.08880000,'\"{\\\"monday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"tuesday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"wednesday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"thursday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"friday\\\":{\\\"open\\\":\\\"14:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"saturday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"},\\\"sunday\\\":{\\\"open\\\":\\\"08:00\\\",\\\"close\\\":\\\"20:00\\\"}}\"','\"[\\\"sorting\\\",\\\"processing\\\",\\\"storage\\\",\\\"pickup\\\",\\\"delivery\\\"]\"','\"{\\\"capacity\\\":4000,\\\"sorting_lines\\\":2,\\\"loading_docks\\\":8}\"',1,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(445,'Jeddah North Branch','LOC-JED-N01','LOCAL',0,443,'Al Hamra District, Jeddah, Saudi Arabia','+966126789013','branch.jeddah.north@baraka.sanaa.co',21.62580000,39.15690000,'\"{\\\"monday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"tuesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"wednesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"thursday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"friday\\\":{\\\"open\\\":\\\"14:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"saturday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"sunday\\\":{\\\"open\\\":\\\"closed\\\",\\\"close\\\":\\\"closed\\\"}}\"','\"[\\\"pickup\\\",\\\"delivery\\\",\\\"dropoff\\\"]\"','\"{\\\"capacity\\\":500,\\\"vehicles\\\":5}\"',1,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(446,'Jeddah South Branch','LOC-JED-S01','LOCAL',0,443,'Al Khalidiyah District, Jeddah, Saudi Arabia','+966126789014','branch.jeddah.south@baraka.sanaa.co',21.42240000,39.21920000,'\"{\\\"monday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"tuesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"wednesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"thursday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"friday\\\":{\\\"open\\\":\\\"14:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"saturday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"sunday\\\":{\\\"open\\\":\\\"closed\\\",\\\"close\\\":\\\"closed\\\"}}\"','\"[\\\"pickup\\\",\\\"delivery\\\",\\\"dropoff\\\"]\"','\"{\\\"capacity\\\":500,\\\"vehicles\\\":5}\"',1,'2025-11-07 20:26:55','2025-11-07 20:26:55'),(447,'Dammam City Branch','LOC-DMM-C01','LOCAL',0,444,'Al Faisaliyah District, Dammam, Saudi Arabia','+966138901235','branch.dammam.city@baraka.sanaa.co',26.43930000,50.10340000,'\"{\\\"monday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"tuesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"wednesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"thursday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"friday\\\":{\\\"open\\\":\\\"14:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"saturday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"sunday\\\":{\\\"open\\\":\\\"closed\\\",\\\"close\\\":\\\"closed\\\"}}\"','\"[\\\"pickup\\\",\\\"delivery\\\",\\\"dropoff\\\"]\"','\"{\\\"capacity\\\":400,\\\"vehicles\\\":4}\"',1,'2025-11-07 20:26:56','2025-11-07 20:26:56'),(448,'Riyadh North Branch','LOC-RYD-N01','LOCAL',0,442,'Al Olaya District, Riyadh, Saudi Arabia','+966112345679','branch.riyadh.north@baraka.sanaa.co',24.77430000,46.66950000,'\"{\\\"monday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"tuesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"wednesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"thursday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"friday\\\":{\\\"open\\\":\\\"14:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"saturday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"sunday\\\":{\\\"open\\\":\\\"closed\\\",\\\"close\\\":\\\"closed\\\"}}\"','\"[\\\"pickup\\\",\\\"delivery\\\",\\\"dropoff\\\"]\"','\"{\\\"capacity\\\":600,\\\"vehicles\\\":6}\"',1,'2025-11-07 20:26:56','2025-11-07 20:26:56'),(449,'Riyadh South Branch','LOC-RYD-S01','LOCAL',0,442,'Al Malaz District, Riyadh, Saudi Arabia','+966112345680','branch.riyadh.south@baraka.sanaa.co',24.64780000,46.72090000,'\"{\\\"monday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"tuesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"wednesday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"thursday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"friday\\\":{\\\"open\\\":\\\"14:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"saturday\\\":{\\\"open\\\":\\\"09:00\\\",\\\"close\\\":\\\"18:00\\\"},\\\"sunday\\\":{\\\"open\\\":\\\"closed\\\",\\\"close\\\":\\\"closed\\\"}}\"','\"[\\\"pickup\\\",\\\"delivery\\\",\\\"dropoff\\\"]\"','\"{\\\"capacity\\\":600,\\\"vehicles\\\":6}\"',1,'2025-11-07 20:26:56','2025-11-07 20:26:56');
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
) ENGINE=InnoDB AUTO_INCREMENT=148 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=46 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  CONSTRAINT `general_settings_currency_id_foreign` FOREIGN KEY (`currency_id`) REFERENCES `currencies` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `general_settings`
--

LOCK TABLES `general_settings` WRITE;
/*!40000 ALTER TABLE `general_settings` DISABLE KEYS */;
INSERT INTO `general_settings` VALUES (1,'Baraka','0200903222','info@sanaa.com','Nasser Road Kampala','$',2,'Copyright © All rights reserved. Development by Sanaa Co.',8,32,9,'1','BA','BA','#7e2995','#fcf7f8','1.4','2025-06-29 11:29:47','2025-07-30 06:23:51');
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
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=150 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'2014_09_12_000000_create_hubs_table',1),(2,'2014_09_12_000000_create_uploads_table',1),(3,'2014_10_10_040240_create_roles_table',1),(4,'2014_10_11_000000_create_deliverycategories_table',1),(5,'2014_10_11_000000_create_departments_table',1),(6,'2014_10_11_000000_create_designations_table',1),(7,'2014_10_11_000000_create_packagings_table',1),(8,'2014_10_11_000000_create_users_table',1),(9,'2014_10_11_000001_create_merchants_table',1),(10,'2014_10_12_100000_create_password_resets_table',1),(11,'2019_08_19_000000_create_failed_jobs_table',1),(12,'2019_12_14_000001_create_personal_access_tokens_table',1),(13,'2022_02_15_122629_create_push_notifications_table',1),(14,'2022_03_20_060621_create_categories_table',1),(15,'2022_03_24_042455_create_activity_log_table',1),(16,'2022_03_24_042456_add_event_column_to_activity_log_table',1),(17,'2022_03_24_042457_add_batch_uuid_column_to_activity_log_table',1),(18,'2022_04_04_142330_create_delivery_man_table',1),(19,'2022_04_04_142330_create_hub_incharges_table',1),(20,'2022_04_04_142330_create_parcels_table',1),(21,'2022_04_09_101126_create_delivery_charges_table',1),(22,'2022_04_09_101126_create_merchant_delivery_charges_table',1),(23,'2022_04_10_050353_create_merchant_shops_table',1),(24,'2022_04_13_034848_create_merchant_payments_table',1),(25,'2022_04_13_054047_create_accounts_table',1),(26,'2022_04_14_045839_create_fund_transfers_table',1),(27,'2022_04_14_063624_create_payments_table',1),(28,'2022_04_17_061311_create_payment_accounts_table',1),(29,'2022_04_19_035758_create_configs_table',1),(30,'2022_04_20_053011_create_sessions_table',1),(31,'2022_04_23_032024_create_permissions_table',1),(32,'2022_04_24_045606_create_parcel_logs_table',2),(33,'2022_04_27_123343_create_parcel_events_table',2),(34,'2022_05_14_112714_create_account_heads_table',2),(35,'2022_05_14_112715_create_expenses_table',2),(36,'2022_05_14_112717_create_deliveryman_statements_table',2),(37,'2022_05_15_102801_create_merchant_statements_table',2),(38,'2022_05_17_124213_create_incomes_table',2),(39,'2022_05_17_132716_create_courier_statements_table',2),(40,'2022_05_18_113259_create_to_dos_table',2),(41,'2022_05_23_111055_create_supports_table',2),(42,'2022_05_23_122723_create_sms_send_settings_table',2),(43,'2022_05_23_122723_create_sms_settings_table',2),(44,'2022_05_24_141546_create_vat_statements_table',2),(45,'2022_05_26_093710_create_bank_transactions_table',2),(46,'2022_05_31_094551_create_general_settings_table',2),(47,'2022_05_31_094551_create_notification_settings_table',2),(48,'2022_05_31_122026_create_assets_table',2),(49,'2022_05_31_122655_create_assetcategories_table',2),(50,'2022_05_31_150039_create_salaries_table',2),(51,'2022_05_6_063624_create_hub_payments_table',2),(52,'2022_06_01_144229_create_news_offers_table',2),(53,'2022_06_02_125218_create_support_chats_table',2),(54,'2022_06_04_104751_create_hub_statements_table',2),(55,'2022_06_05_093107_create_frauds_table',2),(56,'2022_06_05_140650_create_cash_received_from_deliverymen_table',2),(57,'2022_06_12_111844_create_salary_generates_table',2),(58,'2022_08_17_145916_create_subscribes_table',2),(59,'2022_09_08_102027_create_pickup_requests_table',2),(60,'2022_10_11_121745_create_invoices_table',2),(61,'2022_10_17_102458_create_settings_table',2),(62,'2022_10_30_135339_create_merchant_online_payments_table',2),(63,'2022_11_02_105821_create_merchant_online_payment_receiveds_table',2),(64,'2022_11_02_113430_create_merchant_settings_table',2),(65,'2022_12_08_104319_create_addons_table',2),(66,'2022_12_08_104319_create_currencies_table',2),(67,'2023_06_11_172412_create_social_links_table',2),(68,'2023_06_12_144849_create_services_table',2),(69,'2023_06_13_111335_create_why_couriers_table',2),(70,'2023_06_13_122133_create_faqs_table',2),(71,'2023_06_13_133544_create_partners_table',2),(72,'2023_06_13_154945_create_blogs_table',2),(73,'2023_06_13_164933_create_pages_table',2),(74,'2023_06_13_180141_create_sections_table',2),(75,'2023_10_17_122352_create_wallets_table',2),(76,'2023_10_8_094551_create_google_map_settings_table',2),(77,'2024_06_26_065107_create_invoice_parcels_table',2),(78,'2025_03_24_091421_create_notifications_table',2),(79,'2025_05_19_065351_create_banks_table',2),(80,'2025_05_19_094956_create_mobile_banks_table',2),(81,'2025_05_20_000001_add_columns_to_assets_table',2),(82,'2025_05_20_065306_create_vehicles_table',2),(83,'2025_05_20_065340_create_fuels_table',2),(84,'2025_05_20_065408_create_maintainances_table',2),(85,'2025_05_20_065438_create_accidents_table',2),(86,'2025_05_20_065505_create_asset_assigns_table',2),(87,'2025_05_24_055308_create_online_payments_table',2),(88,'2025_05_27_062557_add_deliveryman_current_location_to_delivery_man_table',2),(89,'2025_09_01_215819_enhance_hubs_for_multi_branch_support',2),(90,'2025_09_01_220734_create_branch_configurations_table',2),(91,'2025_09_10_173359_create_shipments_table',3),(92,'2025_09_10_173723_create_scan_events_table',4),(93,'2025_09_10_174158_create_transport_legs_table',4),(94,'2025_09_10_181042_create_bags_table',4),(95,'2025_09_10_194116_create_bag_parcel_table',4),(96,'2025_09_10_194603_create_routes_table',4),(97,'2025_09_10_194749_create_stops_table',4),(98,'2025_09_10_201807_create_epods_table',4),(99,'2025_09_10_204228_create_notifications_table',4),(100,'2025_09_10_204449_create_rate_cards_table',4),(101,'2025_09_10_204632_create_charge_lines_table',4),(102,'2025_09_10_204740_create_invoices_table',4),(103,'2025_09_10_205437_create_cod_receipts_table',4),(104,'2025_09_10_205923_create_settlement_cycles_table',4),(105,'2025_09_10_210025_create_commodities_table',4),(106,'2025_09_10_210334_create_hs_codes_table',4),(107,'2025_09_10_211711_create_customs_docs_table',4),(108,'2025_09_12_000001_create_otp_codes_table',4),(109,'2025_09_12_000002_add_phone_e164_to_users_table',4),(110,'2025_09_12_000003_create_user_consents_table',4),(111,'2025_09_13_000001_create_dhl_modules_tables',4),(112,'2025_09_13_150000_create_zones_table',4),(113,'2025_09_13_150100_create_lanes_table',4),(114,'2025_09_13_150200_create_carriers_table',4),(115,'2025_09_13_150300_create_carrier_services_table',4),(116,'2025_09_13_150400_create_whatsapp_templates_table',4),(117,'2025_09_13_150500_create_edi_providers_table',4),(118,'2025_09_13_150600_create_surveys_table',4),(119,'2025_09_13_170000_create_api_keys_table',4),(120,'2025_09_17_000001_create_impersonation_logs_table',4),(121,'2025_09_17_000002_add_notification_prefs_to_users',4),(122,'2025_09_25_190000_rename_deliverd_date_to_delivered_date_in_parcels_table',4),(123,'2025_09_25_190100_change_weight_to_decimal_in_parcels_table',4),(124,'2025_09_30_003358_create_devices_table',4),(125,'2025_09_30_012435_add_public_token_to_shipments_table',4),(126,'2025_09_30_020000_create_pod_proofs_table',4),(127,'2025_09_30_021000_create_tasks_table',4),(128,'2025_09_30_022000_create_webhook_endpoints_table',4),(129,'2025_09_30_023000_create_webhook_deliveries_table',4),(130,'2025_09_30_024000_create_driver_locations_table',4),(131,'2025_10_02_224758_create_unified_branches_table',4),(132,'2025_10_02_224905_create_branch_managers_table',4),(133,'2025_10_02_225004_create_branch_workers_table',4),(134,'2025_10_02_232657_create_customers_table',5),(135,'2025_10_03_004509_add_unified_workflow_fields_to_shipments_table',6),(136,'2025_10_05_000001_create_clients_table',6),(137,'2025_10_06_022706_create_shipment_logs_table',7),(138,'2025_10_08_120000_create_operations_notifications_table',7),(139,'2025_11_06_100000_create_workflow_tasks_table',7),(140,'2025_11_06_100100_create_workflow_task_comments_table',7),(141,'2025_11_06_100200_create_workflow_task_activities_table',7),(142,'2025_11_06_110000_add_name_to_customers_table',7),(143,'2025_11_06_111000_add_shipment_foreign_key_to_payments_table',7),(144,'2025_11_06_070000_add_transaction_id_to_payments_table',8),(145,'2025_11_07_122724_add_currency_id_to_general_settings_table',9),(146,'2025_11_06_211000_update_branch_workers_table_for_workforce',10),(147,'2025_11_07_120000_add_mode_to_shipments_table',11),(148,'2025_11_09_200000_create_payment_requests_table',12);
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
-- Table structure for table `payment_requests`
--

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

--
-- Dumping data for table `payment_requests`
--

LOCK TABLES `payment_requests` WRITE;
/*!40000 ALTER TABLE `payment_requests` DISABLE KEYS */;
/*!40000 ALTER TABLE `payment_requests` ENABLE KEYS */;
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
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
INSERT INTO `personal_access_tokens` VALUES (1,'App\\Models\\User',2,'react-dashboard-token',NULL,'0b47c351868f3302637b06fc529e9d5f3924398a971939cb5ea7e9e554c55453','[\"*\"]',NULL,'2025-11-06 05:32:21','2025-11-06 05:32:21'),(2,'App\\Models\\User',2,'react-dashboard-token',NULL,'0d93499ce09138473f0376801f1d3c851a6f0ad1e5094cc9a595cf03448af9ae','[\"*\"]',NULL,'2025-11-06 05:34:12','2025-11-06 05:34:12'),(3,'App\\Models\\User',2,'react-dashboard-token',NULL,'8354764cf73d9fb2d6db5623475058037a87998761a5ea0dbb95bd373eb657eb','[\"*\"]',NULL,'2025-11-06 05:42:36','2025-11-06 05:42:36'),(4,'App\\Models\\User',2,'react-dashboard-token',NULL,'714a98c66134703c95eb1e778c49e24169d33411b44e99eb2783bb183b5b5aee','[\"*\"]',NULL,'2025-11-06 05:52:38','2025-11-06 05:52:38'),(5,'App\\Models\\User',2,'react-dashboard-token',NULL,'4eea491c60201a2c2a017375e053399c673430afdf290320169068250f64fe17','[\"*\"]',NULL,'2025-11-06 05:53:59','2025-11-06 05:53:59'),(6,'App\\Models\\User',2,'react-dashboard-token',NULL,'f5f32f212d71d135d1558ca1cf45fe81dbbeaefac0a33c426f5ed56a04ca6777','[\"*\"]',NULL,'2025-11-06 06:32:10','2025-11-06 06:32:10'),(7,'App\\Models\\User',2,'react-dashboard-token',NULL,'3fee7616f72beede1bf71cb54796c9f4a5dac5d20964f8295d2c0d52ed8d8100','[\"*\"]',NULL,'2025-11-06 10:10:54','2025-11-06 10:10:54'),(8,'App\\Models\\User',2,'react-dashboard-token',NULL,'a5b253d1f84c47576a77e400b102cf9038c06088f7491d441f0f374c7be66091','[\"*\"]','2025-11-09 16:53:21','2025-11-06 10:39:05','2025-11-09 16:53:21'),(9,'App\\Models\\User',2,'react-dashboard-token',NULL,'945a16ce920597bb745b67da4abaaee36c44494b7389300c25972e3721e9306d','[\"*\"]','2025-11-07 10:39:26','2025-11-06 10:59:52','2025-11-07 10:39:26'),(10,'App\\Models\\User',2,'react-dashboard-token',NULL,'3c965bcd4fc5006f8e3e1ceda694a84d0549648f10d46354bcfcd2344d0968ef','[\"*\"]','2025-11-06 19:31:41','2025-11-06 11:09:22','2025-11-06 19:31:41'),(11,'App\\Models\\User',2,'react-dashboard-token',NULL,'ebdc3c3a26f910a27b7a9b0a075bd946bca86713d4e231a8e6da7c941db7b44d','[\"*\"]','2025-11-06 19:29:38','2025-11-06 12:05:46','2025-11-06 19:29:38'),(12,'App\\Models\\User',2,'react-dashboard-token',NULL,'ea7340760eb846c259febb215b98ef4004a19f2d27c128e0af27bf65a5f87e8b','[\"*\"]','2025-11-08 00:23:36','2025-11-06 12:13:44','2025-11-08 00:23:36'),(13,'App\\Models\\User',2,'react-dashboard-token',NULL,'20cb8df6270ca13a7657dc81b4fad828fbc27fd8047c6f2ea201c15bb4b86405','[\"*\"]',NULL,'2025-11-06 19:29:41','2025-11-06 19:29:41'),(14,'App\\Models\\User',2,'react-dashboard-token',NULL,'2e81cdcf600ff242f669b2a3357d620ed196a2d40447f614158991601922bb64','[\"*\"]',NULL,'2025-11-07 00:47:22','2025-11-07 00:47:22'),(15,'App\\Models\\User',2,'react-dashboard-token',NULL,'fe572ca0487806acaefb4e4383217d36ba6c2676de55adbea8c44efc21487616','[\"*\"]','2025-11-09 00:15:42','2025-11-07 07:53:15','2025-11-09 00:15:42'),(16,'App\\Models\\User',2,'react-dashboard-token',NULL,'afa9fffe1f55f03fa2675186f8a382961c2d40b8f00429ffcc65f82de3222934','[\"*\"]','2025-11-07 19:04:48','2025-11-07 10:21:33','2025-11-07 19:04:48'),(17,'App\\Models\\User',629,'react-dashboard-token',NULL,'71c74ce5139a9a1a63f0b1c6d3362c51cd54faea235a34389ce72da8e8cb1aa5','[\"*\"]',NULL,'2025-11-07 10:40:28','2025-11-07 10:40:28'),(18,'App\\Models\\User',2,'react-dashboard-token',NULL,'492d32352ed4cc4d9949de56ae2e6ce177d9a657b9ad8f9d3bbbb52d6e81b911','[\"*\"]',NULL,'2025-11-07 11:19:45','2025-11-07 11:19:45'),(19,'App\\Models\\User',2,'react-dashboard-token',NULL,'429f3de1c4b630bd2d2d9118dd6d0a5a7ac6251b7769f188118503b60c23f0e7','[\"*\"]',NULL,'2025-11-07 12:23:43','2025-11-07 12:23:43'),(20,'App\\Models\\User',2,'react-dashboard-token',NULL,'00f76848224af1861517a1ff24cc990cbadaa93194157142213e3a6c0289f0ae','[\"*\"]','2025-11-07 22:58:58','2025-11-07 12:51:31','2025-11-07 22:58:58'),(21,'App\\Models\\User',2,'react-dashboard-token',NULL,'2722bc89fd8e8476050f79852b339577e0368bc182ebca94f4e4554dbc5ecc8e','[\"*\"]',NULL,'2025-11-08 00:24:04','2025-11-08 00:24:04'),(22,'App\\Models\\User',2,'react-dashboard-token',NULL,'378cefb5c7840a1e86b2a2efeb54c3bc0320eb798203b21bed45d3f7730940b2','[\"*\"]',NULL,'2025-11-09 00:15:47','2025-11-09 00:15:47'),(23,'App\\Models\\User',2,'react-dashboard-token',NULL,'8bf7f23723db083b0e229d288ce54bfaa35536a7892113d85d55a27894f73218','[\"*\"]','2025-11-09 14:07:12','2025-11-09 01:54:39','2025-11-09 14:07:12'),(24,'App\\Models\\User',2,'react-dashboard-token',NULL,'716621e00c41451f35a9e097fdf9fb84bf0bc85f890635dfc10b212868219d29','[\"*\"]','2025-11-10 10:44:44','2025-11-09 14:57:02','2025-11-10 10:44:44'),(25,'App\\Models\\User',1,'test-api-token','2025-11-16 16:07:45','0e73d4880fa92f5a79c44d44f7a4f3e0e29d010a51361477bb87011a556dbd3c','[\"dashboard_read\"]',NULL,'2025-11-09 15:07:45','2025-11-09 15:07:45'),(26,'App\\Models\\User',1,'test-api-token','2025-11-16 16:07:57','bef4d05aaef3f9eca98d89d944878425d6b6ad8977186cd545d7aa716934df4f','[\"dashboard_read\"]',NULL,'2025-11-09 15:07:57','2025-11-09 15:07:57'),(27,'App\\Models\\User',2,'react-dashboard-token',NULL,'593ab4e6e288ef3ab0afa162d0544642a74c6a8e764890b7b749abc22dbd7e8b','[\"*\"]',NULL,'2025-11-10 10:45:21','2025-11-10 10:45:21'),(28,'App\\Models\\User',629,'react-dashboard-token',NULL,'cf45568ba7bfc2656c2e06f7b2ad52ecd06b83303b494caef4f172bc6d480bf3','[\"*\"]',NULL,'2025-11-10 12:46:12','2025-11-10 12:46:12'),(29,'App\\Models\\User',629,'react-dashboard-token',NULL,'e370b0e7419cf27866ecd43b6c5ad258e16bbe6c3aa1ab3e5242263a8088ae0a','[\"*\"]',NULL,'2025-11-10 12:47:34','2025-11-10 12:47:34'),(30,'App\\Models\\User',629,'react-dashboard-token',NULL,'0e4e4ea054da0c0e36a58388109c56ab9adea5b37cfeeddc564e43defe8147dd','[\"*\"]',NULL,'2025-11-10 12:51:15','2025-11-10 12:51:15'),(31,'App\\Models\\User',629,'react-dashboard-token',NULL,'0b23ccba751ae9115bc856d909ea92286dd6018b9bbe9975a52144490fa2bc78','[\"*\"]','2025-11-10 12:56:53','2025-11-10 12:56:53','2025-11-10 12:56:53'),(32,'App\\Models\\User',629,'react-dashboard-token',NULL,'37dd54466b0979d924ee8372b3a1bb57ca531fa6ce13e0c84363489b9d0b66bb','[\"*\"]',NULL,'2025-11-10 21:53:31','2025-11-10 21:53:31'),(33,'App\\Models\\User',2,'react-dashboard-token',NULL,'53aaf44d440c7259e9d7df75ae11c087f97b3070395fbe4a003f217a04f0d22d','[\"*\"]','2025-11-11 00:33:47','2025-11-10 22:07:05','2025-11-11 00:33:47');
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
  KEY `quotations_created_by_id_foreign` (`created_by_id`),
  KEY `idx_quotation_customer_status` (`customer_id`,`status`),
  KEY `idx_quotation_route` (`origin_branch_id`,`destination_country`),
  KEY `idx_quotation_service_date` (`service_type`,`created_at`),
  KEY `idx_quotation_validity` (`valid_until`,`status`),
  KEY `idx_quotation_customer_date` (`customer_id`,`created_at`),
  CONSTRAINT `quotations_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `quotations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `quotations_origin_branch_id_foreign` FOREIGN KEY (`origin_branch_id`) REFERENCES `hubs` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
  KEY `rate_cards_is_active_index` (`is_active`),
  KEY `idx_ratecard_route_active` (`origin_country`,`dest_country`,`is_active`),
  KEY `idx_ratecard_active_date` (`is_active`,`created_at`)
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
) ENGINE=InnoDB AUTO_INCREMENT=33 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
INSERT INTO `roles` VALUES (1,'Super Admin','super-admin','[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_create\",\"hub_update\",\"hub_delete\",\"hub_incharge_read\",\"hub_incharge_create\",\"hub_incharge_update\",\"hub_incharge_delete\",\"hub_incharge_assigned\",\"account_read\",\"account_create\",\"account_update\",\"account_delete\",\"income_read\",\"income_create\",\"income_update\",\"income_delete\",\"expense_read\",\"expense_create\",\"expense_update\",\"expense_delete\",\"todo_read\",\"todo_create\",\"todo_update\",\"todo_delete\",\"fund_transfer_read\",\"fund_transfer_create\",\"fund_transfer_update\",\"fund_transfer_delete\",\"role_read\",\"role_create\",\"role_update\",\"role_delete\",\"designation_read\",\"designation_create\",\"designation_update\",\"designation_delete\",\"department_read\",\"department_create\",\"department_update\",\"department_delete\",\"user_read\",\"user_create\",\"user_update\",\"user_delete\",\"permission_update\",\"merchant_read\",\"merchant_create\",\"merchant_update\",\"merchant_delete\",\"merchant_view\",\"merchant_delivery_charge_read\",\"merchant_delivery_charge_create\",\"merchant_delivery_charge_update\",\"merchant_delivery_charge_delete\",\"merchant_shop_read\",\"merchant_shop_create\",\"merchant_shop_update\",\"merchant_shop_delete\",\"merchant_payment_read\",\"merchant_payment_create\",\"merchant_payment_update\",\"merchant_payment_delete\",\"payment_read\",\"payment_create\",\"payment_update\",\"payment_delete\",\"payment_reject\",\"payment_process\",\"hub_payment_read\",\"hub_payment_create\",\"hub_payment_update\",\"hub_payment_delete\",\"hub_payment_reject\",\"hub_payment_process\",\"hub_payment_request_read\",\"hub_payment_request_create\",\"hub_payment_request_update\",\"hub_payment_request_delete\",\"parcel_read\",\"parcel_create\",\"parcel_update\",\"parcel_delete\",\"parcel_status_update\",\"delivery_man_read\",\"delivery_man_create\",\"delivery_man_update\",\"delivery_man_delete\",\"delivery_category_read\",\"delivery_category_create\",\"delivery_category_update\",\"delivery_category_delete\",\"delivery_charge_read\",\"delivery_charge_create\",\"delivery_charge_update\",\"delivery_charge_delete\",\"delivery_type_read\",\"delivery_type_status_change\",\"liquid_fragile_read\",\"liquid_fragile_update\",\"liquid_status_change\",\"packaging_read\",\"packaging_create\",\"packaging_update\",\"packaging_delete\",\"category_read\",\"category_create\",\"category_update\",\"category_delete\",\"account_heads_read\",\"database_backup_read\",\"salary_read\",\"salary_create\",\"salary_update\",\"salary_delete\",\"support_read\",\"support_create\",\"support_update\",\"support_delete\",\"support_reply\",\"support_status_update\",\"sms_settings_read\",\"sms_settings_create\",\"sms_settings_update\",\"sms_settings_delete\",\"sms_send_settings_read\",\"sms_send_settings_create\",\"sms_send_settings_update\",\"sms_send_settings_delete\",\"general_settings_read\",\"general_settings_update\",\"notification_settings_read\",\"notification_settings_update\",\"push_notification_read\",\"push_notification_create\",\"push_notification_update\",\"push_notification_delete\",\"asset_category_read\",\"asset_category_create\",\"asset_category_update\",\"asset_category_delete\",\"news_offer_read\",\"news_offer_create\",\"news_offer_update\",\"news_offer_delete\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"salary_generate_create\",\"salary_generate_update\",\"salary_generate_delete\",\"assets_read\",\"assets_create\",\"assets_update\",\"assets_delete\",\"fraud_read\",\"fraud_create\",\"fraud_update\",\"fraud_delete\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\",\"invoice_read\",\"invoice_status_update\",\"social_login_settings_read\",\"social_login_settings_update\",\"payout_setup_settings_read\",\"payout_setup_settings_update\",\"online_payment_read\",\"payout_read\",\"payout_create\",\"hub_view\",\"paid_invoice_read\",\"invoice_generate_menually\",\"currency_read\",\"currency_create\",\"currency_update\",\"currency_delete\",\"social_link_read\",\"social_link_create\",\"social_link_update\",\"social_link_delete\",\"service_read\",\"service_create\",\"service_update\",\"service_delete\",\"why_courier_read\",\"why_courier_create\",\"why_courier_update\",\"why_courier_delete\",\"faq_read\",\"faq_create\",\"faq_update\",\"faq_delete\",\"partner_read\",\"partner_create\",\"partner_update\",\"partner_delete\",\"blogs_read\",\"blogs_create\",\"blogs_update\",\"blogs_delete\",\"pages_read\",\"pages_update\",\"section_read\",\"section_update\",\"mail_settings_read\",\"mail_settings_update\",\"wallet_request_read\",\"wallet_request_create\",\"wallet_request_delete\",\"wallet_request_approve\",\"wallet_request_reject\"]',1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(2,'Admin','admin','[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_incharge_read\",\"account_read\",\"income_read\",\"expense_read\",\"todo_read\",\"sms_settings_read\",\"sms_send_settings_read\",\"general_settings_read\",\"notification_settings_read\",\"push_notification_read\",\"push_notification_create\",\"push_notification_update\",\"push_notification_delete\",\"account_heads_read\",\"salary_read\",\"support_read\",\"fund_transfer_read\",\"role_read\",\"designation_read\",\"department_read\",\"user_read\",\"merchant_read\",\"merchant_delivery_charge_read\",\"merchant_shop_read\",\"merchant_payment_read\",\"payment_read\",\"hub_payment_request_read\",\"hub_payment_read\",\"parcel_read\",\"delivery_man_read\",\"delivery_category_read\",\"delivery_charge_read\",\"delivery_type_read\",\"liquid_fragile_read\",\"packaging_read\",\"category_read\",\"asset_category_read\",\"news_offer_read\",\"sms_settings_status_change\",\"sms_send_settings_status_change\",\"bank_transaction_read\",\"database_backup_read\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"assets_read\",\"fraud_read\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\",\"cash_received_from_delivery_man_read\",\"cash_received_from_delivery_man_create\",\"cash_received_from_delivery_man_update\",\"cash_received_from_delivery_man_delete\",\"invoice_read\",\"invoice_status_update\",\"social_login_settings_read\",\"social_login_settings_update\",\"payout_setup_settings_read\",\"online_payment_read\",\"payout_read\",\"hub_view\",\"paid_invoice_read\",\"invoice_generate_menually\",\"currency_read\"]',1,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(32,'Branch Ops Manager','branch_ops_manager','[]',1,'2025-11-07 10:34:37','2025-11-07 10:34:37');
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
  `mode` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual',
  `priority` int NOT NULL DEFAULT '1',
  `incoterm` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `price_amount` decimal(10,2) DEFAULT NULL,
  `currency` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `current_status` enum('BOOKED','PICKUP_SCHEDULED','PICKED_UP','AT_ORIGIN_HUB','BAGGED','LINEHAUL_DEPARTED','LINEHAUL_ARRIVED','AT_DESTINATION_HUB','CUSTOMS_HOLD','CUSTOMS_CLEARED','OUT_FOR_DELIVERY','DELIVERED','RETURN_INITIATED','RETURN_IN_TRANSIT','RETURNED','CANCELLED','EXCEPTION') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BOOKED',
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
  KEY `shipments_mode_index` (`mode`),
  CONSTRAINT `shipments_delivered_by_foreign` FOREIGN KEY (`delivered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shipments_transfer_hub_id_foreign` FOREIGN KEY (`transfer_hub_id`) REFERENCES `branches` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=233 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `shipments`
--

LOCK TABLES `shipments` WRITE;
/*!40000 ALTER TABLE `shipments` DISABLE KEYS */;
INSERT INTO `shipments` VALUES (1,1,10,3,2,NULL,1,NULL,'K7VP2D2XVQWJ','out_for_delivery',0,NULL,NULL,NULL,NULL,7,'STANDARD','individual',1,'DAP',49.99,'USD','OUT_FOR_DELIVERY','2025-11-05 05:32:59',NULL,NULL,NULL,NULL,'2025-11-07 05:32:59',NULL,NULL,NULL,NULL,'{\"package_count\": 3}','eyJpdiI6IjQzRTdVVTFqRTBuT2Q1M21Jck41Z2c9PSIsInZhbHVlIjoiZ3RHNms2eUFvM2FTenFocVRuSjc1dz09IiwibWFjIjoiZDQ2NmVkNjU2ZmFlNjQ2MjkwMDBiYjZjYjkyNTJjMjE1ZjkwYzk3NGVkNmVlYWJkNDFiMjZlYzUwMzQzZmVjZSIsInR',NULL,'2025-11-06 05:32:59','2025-11-06 05:32:59'),(2,1,10,3,2,NULL,1,NULL,'MVGAKOLMJBCQ','out_for_delivery',0,NULL,NULL,NULL,NULL,7,'STANDARD','individual',1,'DAP',49.99,'USD','OUT_FOR_DELIVERY','2025-11-05 05:33:26',NULL,NULL,NULL,NULL,'2025-11-07 05:33:26',NULL,NULL,NULL,NULL,'{\"package_count\": 3}','eyJpdiI6Ik9HVGppdEhHNXVQeHlJV3grRXM3aGc9PSIsInZhbHVlIjoiRDc4MS82SHhlME9xY3luQVdaSUJ5dz09IiwibWFjIjoiZDkzZDBmZTgyNDlhMzRjNDcwZmJhMTE0YWU1NjhhY2UwMDdlMTZlNDJhZjNlYmIzYzA3ZGMxZTI2NGI5ZTRjNCIsInR',NULL,'2025-11-06 05:33:26','2025-11-06 05:33:26'),(3,1,10,3,2,NULL,1,NULL,'P1UR2QK1YLGI','out_for_delivery',0,NULL,NULL,NULL,NULL,7,'STANDARD','individual',1,'DAP',49.99,'USD','OUT_FOR_DELIVERY','2025-11-05 05:40:09',NULL,NULL,NULL,NULL,'2025-11-07 05:40:09',NULL,NULL,NULL,NULL,'{\"package_count\": 3}','eyJpdiI6IkQrZlJaaGdXbTZWZnhsYTZFbjBoMkE9PSIsInZhbHVlIjoiTDdRQzBHSW04dXlxQWhodTlxVFhOdz09IiwibWFjIjoiOTU2MzQ0MDdiOGM4MGE3ZDNlMWExZTE0ZTgyZDI2YmFhZGUzZjM2ZTAwYTNjYWY5NDU2MzhiM2EyNzU5YjRlMCIsInR',NULL,'2025-11-06 05:40:09','2025-11-06 05:40:09'),(140,1,546,3,1,NULL,NULL,NULL,'BRK202500000004','',0,NULL,NULL,NULL,NULL,546,'priority','individual',1,'DAP',147.00,'UGX','BOOKED',NULL,NULL,NULL,NULL,NULL,'2025-10-23 11:33:54',NULL,NULL,NULL,NULL,'{\"weight\": 0.9, \"insurance\": true, \"dimensions\": {\"width\": 68, \"height\": 41, \"length\": 96}, \"declared_value\": 4584, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlVlRDQ3Nk1QdnowNzZWYTNEYWM4WEE9PSIsInZhbHVlIjoibTZqZ3ZrWGtISWM5SGVGYjY1d3VBQT09IiwibWFjIjoiODM3YjM5ZTVhOGFlNTVhN2M2MDhjYTE4MzI0ZTY5N2Y4MDg0OTQ5YWM0YzZmNTg5NGNlZDFmM2YxY2YxZTE3NSIsInR',NULL,'2025-10-18 11:33:54','2025-10-18 11:33:54'),(141,1,8,1,2,NULL,NULL,NULL,'BRK202500000005','',0,NULL,NULL,NULL,NULL,8,'express','individual',1,'DAP',185.00,'UGX','BOOKED',NULL,NULL,NULL,NULL,NULL,'2025-10-15 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 0.9, \"insurance\": false, \"dimensions\": {\"width\": 94, \"height\": 20, \"length\": 94}, \"declared_value\": 3201, \"special_instructions\": null}','eyJpdiI6InF1UHA1MnVPejZVNDB5Y0VtclJiSFE9PSIsInZhbHVlIjoiSlF3L0xNV3U1dlVlZDIyaG14UU9CUT09IiwibWFjIjoiM2Y1YjBlNzc2ODM1ZDZhZmFhMzZlYzJlZDg3MGNjMGE0MTlhMTE0Mjg0YWNiZDZjMGZkYzk4ZWMwMTZlMDFkNiIsInR',NULL,'2025-10-12 11:34:12','2025-10-12 11:34:12'),(142,1,8,3,1,NULL,NULL,NULL,'BRK202500000006','',0,NULL,NULL,NULL,NULL,8,'standard','individual',2,'DAP',53.00,'UGX','BOOKED',NULL,NULL,NULL,NULL,NULL,'2025-10-21 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2.4, \"insurance\": false, \"dimensions\": {\"width\": 51, \"height\": 32, \"length\": 57}, \"declared_value\": 3464, \"special_instructions\": \"Handle with care\"}','eyJpdiI6InQ4NnZVZDFjaitraFpKcDd6NnRYaGc9PSIsInZhbHVlIjoiR0tocFlyY29kZzN5KzJJSDRYR1ppQT09IiwibWFjIjoiMmNlYzA5OWViZTQ4MWUyYWE5MDliYmUzOWUwNmMzNjFhODViMTkxODI1MDYwYzBjMjNiMzc0NjFhMGRiN2ZlNyIsInR',NULL,'2025-10-15 11:34:12','2025-10-15 11:34:12'),(143,1,631,2,1,NULL,NULL,NULL,'BRK202500000007','',0,NULL,NULL,NULL,NULL,631,'priority','individual',1,'DDP',128.00,'UGX','BOOKED',NULL,NULL,NULL,NULL,NULL,'2025-10-21 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.6, \"insurance\": false, \"dimensions\": {\"width\": 84, \"height\": 58, \"length\": 20}, \"declared_value\": 3458, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlZ0Tmd5bTEwUVRXVUJhZzVDSXlKWnc9PSIsInZhbHVlIjoiRXRlMGNTeXBST29LMWdNVkNuK1dZQT09IiwibWFjIjoiY2NlODQwZmVjM2FmOTQxMTQyYTM1ODM3ODBmZTdjOTg3MmEwYWRiMTA0Yzc3NDgyN2Y4ODMwYTZkZWE4MjU2MiIsInR',NULL,'2025-10-14 11:34:12','2025-10-14 11:34:12'),(144,1,542,1,2,NULL,NULL,NULL,'BRK202500000008','',1,'missing_item','medium','Exception: missing_item detected during transit','2025-11-04 21:34:12',542,'priority','individual',1,'DAP',197.00,'UGX','BOOKED',NULL,NULL,NULL,NULL,NULL,'2025-11-08 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.8, \"insurance\": false, \"dimensions\": {\"width\": 73, \"height\": 36, \"length\": 16}, \"declared_value\": 2286, \"special_instructions\": null}','eyJpdiI6ImxYVkdENVpQMjNEODNodmg1RS9ZbEE9PSIsInZhbHVlIjoiN20waGlZZXc1UjJUanFrdnJvQTA0Zz09IiwibWFjIjoiMzZmZDQzN2ZkODZlZTEwZWY5YWFkMzNlNjg1N2M5ZTYwMjZmZDA0Yjk4ZTQxNGZmN2YwYTc1OGNhNDJiNGYzZCIsInR',NULL,'2025-11-03 12:34:12','2025-11-03 12:34:12'),(145,1,545,1,2,NULL,NULL,NULL,'BRK202500000009','',0,NULL,NULL,NULL,NULL,545,'express','individual',1,'DAP',296.00,'UGX','BOOKED',NULL,NULL,NULL,NULL,NULL,'2025-10-23 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.1, \"insurance\": false, \"dimensions\": {\"width\": 85, \"height\": 67, \"length\": 18}, \"declared_value\": 2001, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IndHczF5V1pOWHhHdWJVNVBGd1FMY3c9PSIsInZhbHVlIjoiclRHdEhQb3Z1L2tKR0NCc1B4dWxIUT09IiwibWFjIjoiMjQ1MzIyNmI0MDcyYTk1OThlZmE1YWU0MzBmMDJkZGQ2Yjk5YWJhN2YzNThjOWUxOGM4NzIyZDgzYzExMzQ1MiIsInR',NULL,'2025-10-19 11:34:12','2025-10-19 11:34:12'),(146,1,542,2,1,NULL,NULL,NULL,'BRK202500000010','',0,NULL,NULL,NULL,NULL,542,'standard','individual',2,'DDP',274.00,'UGX','BOOKED',NULL,NULL,NULL,NULL,NULL,'2025-10-28 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.8, \"insurance\": true, \"dimensions\": {\"width\": 33, \"height\": 77, \"length\": 57}, \"declared_value\": 1399, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlI0NmIxSzZxYUhoOGh5dWJFNGN5ZlE9PSIsInZhbHVlIjoicTJFRDN1eUNTQW45UGVMdGlLT1FHQT09IiwibWFjIjoiODA3MThhMGQ4NDgzOWY5OWQwZDkwYTlkY2VkZGNkMDc5NDc3OTkzNjI2NGQ2NmRmY2Q5ZGI1N2U0YzIwZDE0NyIsInR',NULL,'2025-10-22 11:34:12','2025-10-22 11:34:12'),(147,1,7,1,2,NULL,NULL,NULL,'BRK202500000011','',0,NULL,NULL,NULL,NULL,7,'express','individual',1,'DAP',311.00,'UGX','BOOKED',NULL,NULL,NULL,NULL,NULL,'2025-10-31 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.3, \"insurance\": true, \"dimensions\": {\"width\": 30, \"height\": 13, \"length\": 51}, \"declared_value\": 2464, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Imt5VzFRWTA2UGE3bTJ1TTVmN29hOUE9PSIsInZhbHVlIjoibU5iUFF4Yi9WUk1sazhtTGFnQzFEdz09IiwibWFjIjoiMjU4YjliYmEwYjliYzE3NGQ4YjM3ZTM2MzE3YzViYmNjMmE0NzM1MGI4ZGMzMmViMDA4YjgzNzQ0ODFhZTBmYyIsInR',NULL,'2025-10-25 11:34:12','2025-10-25 11:34:12'),(148,1,546,3,1,NULL,NULL,NULL,'BRK202500000012','',0,NULL,NULL,NULL,NULL,546,'priority','individual',1,'DDP',404.00,'UGX','BOOKED',NULL,NULL,NULL,NULL,NULL,'2025-10-19 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 4.6, \"insurance\": true, \"dimensions\": {\"width\": 30, \"height\": 30, \"length\": 38}, \"declared_value\": 2943, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IjhKa29ydmNJNDdSazRFcDVFM2doa3c9PSIsInZhbHVlIjoiWXhhWjVCWGJha0VhYkRHVHRPSUJ2QT09IiwibWFjIjoiMzQwNzE2Y2M3YjBmZjIzNzUwMzYzN2MwMTJjMmM2YzNmNzA2NjY4MjkzNmJlY2Q4Y2ViYTAyYzc0YTQ5MDc4YSIsInR',NULL,'2025-10-14 11:34:12','2025-10-14 11:34:12'),(149,1,630,3,1,NULL,1,NULL,'BRK202500000013','',0,NULL,NULL,NULL,NULL,630,'standard','individual',1,'DDP',135.00,'UGX','BOOKED','2025-11-04 13:34:12',NULL,NULL,NULL,NULL,'2025-11-11 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 0.4, \"insurance\": true, \"dimensions\": {\"width\": 19, \"height\": 90, \"length\": 53}, \"declared_value\": 3790, \"special_instructions\": null}','eyJpdiI6Im1uTEVVS1VnSk5QOTEyVkJ3ZThQeXc9PSIsInZhbHVlIjoiRFY3NFpKWVo2QUtSd0tXYm8wS2tsdz09IiwibWFjIjoiYWJjNDQ5ZjRlODQ1ZDBjOWI2YTBiYzc4NDlmZDA4NTQ3OTliMzc1ZmUwMmVmOTk3NjYxZTA0OTM4ZTYxZjZjNiIsInR',NULL,'2025-11-04 12:34:12','2025-11-04 13:34:12'),(150,1,538,1,2,NULL,2,NULL,'BRK202500000014','',0,NULL,NULL,NULL,NULL,538,'express','individual',1,'DAP',204.00,'UGX','BOOKED','2025-11-03 14:34:12',NULL,NULL,NULL,NULL,'2025-11-10 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1.3, \"insurance\": false, \"dimensions\": {\"width\": 59, \"height\": 47, \"length\": 71}, \"declared_value\": 884, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IllERlEvd1EzeEN3L1JuREFRK2hyc0E9PSIsInZhbHVlIjoiSkxzVjBVaVFHSTRTaXNSV01VZVZIZz09IiwibWFjIjoiN2QyMTYwMWJmZmM2ZmJiOTliYjFjNWFjN2Q5YmJmZjg2ZTY4MmY1MDQ2MDU2ZDYwZTI3MzRmY2Y2NzI0NGU3MiIsInR',NULL,'2025-11-03 12:34:12','2025-11-03 14:34:12'),(151,1,2,1,2,NULL,2,NULL,'BRK202500000015','',0,NULL,NULL,NULL,NULL,2,'standard','individual',1,'DAP',480.00,'UGX','BOOKED','2025-10-16 15:34:12',NULL,NULL,NULL,NULL,'2025-10-23 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 5, \"insurance\": true, \"dimensions\": {\"width\": 25, \"height\": 20, \"length\": 49}, \"declared_value\": 792, \"special_instructions\": null}','eyJpdiI6IlhMZW00b1YrTk0zaDFWdzlUd3RDUVE9PSIsInZhbHVlIjoiT2dZd0dmOVpnRkNuZWhla1A2WmEvQT09IiwibWFjIjoiZGJmMGE0N2Y4NjkzYzllNTM1ZTczZjcwN2FiMmE5NmRmZDVhN2NlNzliY2M5NTc1NDYxMjIyYjVhMGU3ZDNkZCIsInR',NULL,'2025-10-16 11:34:12','2025-10-16 15:34:12'),(152,1,2,2,1,NULL,16,NULL,'BRK202500000016','',0,NULL,NULL,NULL,NULL,2,'standard','individual',2,'DDP',259.00,'UGX','BOOKED','2025-10-19 12:34:12',NULL,NULL,NULL,NULL,'2025-10-22 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1, \"insurance\": true, \"dimensions\": {\"width\": 79, \"height\": 21, \"length\": 40}, \"declared_value\": 3416, \"special_instructions\": null}','eyJpdiI6IlZBcnlEYkNENm85OTJUWkJEMUp1QXc9PSIsInZhbHVlIjoibU5uQndBM0REZjl3Z240Vk9mQnU1UT09IiwibWFjIjoiMmQxYzEwYWIyZWJlZWM0MjlmNWIxZmFiY2EyYTNmNTk0ZDYzOTA2ODU3ODcyODc3MGQ5MDIzMTA5MzU1ZmJmNyIsInR',NULL,'2025-10-19 11:34:12','2025-10-19 12:34:12'),(153,1,544,1,2,NULL,2,NULL,'BRK202500000017','',0,NULL,NULL,NULL,NULL,544,'express','individual',1,'DAP',189.00,'UGX','BOOKED','2025-10-11 14:34:12',NULL,NULL,NULL,NULL,'2025-10-18 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.1, \"insurance\": true, \"dimensions\": {\"width\": 100, \"height\": 87, \"length\": 97}, \"declared_value\": 2679, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ild6VjZ4OVNpUU8yL3J2WnJhNCtoc0E9PSIsInZhbHVlIjoiR2hpV0dlN0RNVW1MY2tQMTRRUFNSUT09IiwibWFjIjoiMDZmMDM2OTI0M2U2MDY0ZGI1YTNjMmU2ZTAwNzU0MTZiZDE4Y2Y3OTk0ZGRjZmNjNDc0OTUyNjk1NzBhZmEyMyIsInR',NULL,'2025-10-11 11:34:12','2025-10-11 14:34:12'),(154,1,8,2,1,NULL,16,NULL,'BRK202500000018','',0,NULL,NULL,NULL,NULL,8,'priority','individual',1,'DDP',108.00,'UGX','BOOKED','2025-11-03 14:34:12',NULL,NULL,NULL,NULL,'2025-11-06 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1.9, \"insurance\": false, \"dimensions\": {\"width\": 71, \"height\": 81, \"length\": 100}, \"declared_value\": 291, \"special_instructions\": \"Handle with care\"}','eyJpdiI6InNveEY4dGhiL1dnL0tPSjM2eFV5aUE9PSIsInZhbHVlIjoiZU1aWnRuWlJ6ZjRRTlAxWW15OW5XZz09IiwibWFjIjoiNDU3YjI3NWM4Y2ZmNTA3MGE0ODRiYTVmMmQxMzY5NmQ2ZjFlZjUwMDgyNWZiMzJmM2FlYjQyMTYyN2MwMjBkMiIsInR',NULL,'2025-11-03 12:34:12','2025-11-03 14:34:12'),(155,1,546,3,1,NULL,1,NULL,'BRK202500000019','',0,NULL,NULL,NULL,NULL,546,'express','individual',1,'DAP',486.00,'UGX','PICKED_UP','2025-11-04 16:34:12',NULL,NULL,'2025-11-04 19:34:12',NULL,'2025-11-10 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1, \"insurance\": false, \"dimensions\": {\"width\": 49, \"height\": 95, \"length\": 79}, \"declared_value\": 3467, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IkxocTlCWERWbEJYeTJXVUk0bEVqWGc9PSIsInZhbHVlIjoiK1lRN0NSQk9XU3RHamd5ZzdQVEFDZz09IiwibWFjIjoiMDNkMDU2MmMyODJkYzc2ODYyZTVmYjgxM2U2MTVhOTU2M2I1ZmU3ZGI0ZTFjMDc4MDU3ZDE2ZWIyYzQ1OTQ3NiIsInR',NULL,'2025-11-04 12:34:12','2025-11-04 16:34:12'),(156,1,542,2,1,NULL,16,NULL,'BRK202500000020','',0,NULL,NULL,NULL,NULL,542,'standard','individual',1,'DDP',50.00,'UGX','PICKED_UP','2025-10-17 14:34:12',NULL,NULL,'2025-10-17 17:34:12',NULL,'2025-10-23 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2.9, \"insurance\": false, \"dimensions\": {\"width\": 82, \"height\": 63, \"length\": 86}, \"declared_value\": 4513, \"special_instructions\": null}','eyJpdiI6IndDQjlDUFhFSFdyS2JWUnpianVRMkE9PSIsInZhbHVlIjoiTjlxZXVyR2s0ZWRhTjVhNUtSMm1rQT09IiwibWFjIjoiMWZlYmNhYTg0MThjNTFmOTFmOGFjNDc1YmNiYmFhZDkwYzA5NjdkNDJmOGJhMWQyNmY2YTQ3M2JmYTVhN2NiZCIsInR',NULL,'2025-10-17 11:34:12','2025-10-17 14:34:12'),(157,1,2,3,1,NULL,1,NULL,'BRK202500000021','',0,NULL,NULL,NULL,NULL,2,'priority','individual',1,'DDP',457.00,'UGX','PICKED_UP','2025-10-10 15:34:12',NULL,NULL,'2025-10-10 16:34:12',NULL,'2025-10-14 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2.1, \"insurance\": false, \"dimensions\": {\"width\": 100, \"height\": 41, \"length\": 97}, \"declared_value\": 3046, \"special_instructions\": \"Handle with care\"}','eyJpdiI6ImNXMmtlMUdyU0VMTm55UlkwR3NCM3c9PSIsInZhbHVlIjoiMTdhM3NrTmdRNmNDTVVXMHNoUkdzQT09IiwibWFjIjoiZGU5MmViYzIzZjI2OTM4Y2UxOTYwZTRhNzkzY2UyMzE4ZGRmNDA1MjNiYzg4NjI5NWFkNTFlOTc2NTJlOWE1MiIsInR',NULL,'2025-10-10 11:34:12','2025-10-10 15:34:12'),(158,1,542,2,1,NULL,16,NULL,'BRK202500000022','',0,NULL,NULL,NULL,NULL,542,'express','individual',1,'DAP',455.00,'UGX','PICKED_UP','2025-10-31 15:34:12',NULL,NULL,'2025-10-31 16:34:12',NULL,'2025-11-06 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 4.7, \"insurance\": true, \"dimensions\": {\"width\": 31, \"height\": 64, \"length\": 28}, \"declared_value\": 636, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ink2Sm1DcUtXanlUejFGampNcVhsN1E9PSIsInZhbHVlIjoiUC85RmpneW1QMVo3TlJVZWNZUU5tZz09IiwibWFjIjoiNWY2MmQzZjU2NDYwN2I2YTlmZTU2MjAxMjBhNGU5MzJmMzRhNjRiYmJkOWU3MTQ0MzY5MDk1ZTNmN2ZhNTcyZSIsInR',NULL,'2025-10-31 12:34:12','2025-10-31 15:34:12'),(159,1,629,3,1,NULL,1,NULL,'BRK202500000023','',0,NULL,NULL,NULL,NULL,629,'standard','individual',1,'DAP',499.00,'UGX','PICKED_UP','2025-10-25 12:34:12',NULL,NULL,'2025-10-25 14:34:12',NULL,'2025-11-01 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.9, \"insurance\": false, \"dimensions\": {\"width\": 43, \"height\": 16, \"length\": 23}, \"declared_value\": 1128, \"special_instructions\": null}','eyJpdiI6InpmZFFENkQ2dUhEemlvY0NXUkJOWHc9PSIsInZhbHVlIjoiRzRGS0picEdKZ3B5UGE1Y1V2bFVCdz09IiwibWFjIjoiMTk0NWU0MDkwZDYwZTdjNzQ4NTBjMjdjZjkzMDhkNWU0NzAzODg2MWEwYzBlYmFhNjI5MTFhNjc3YjM3MDVlZCIsInR',NULL,'2025-10-25 11:34:12','2025-10-25 12:34:12'),(160,1,1,1,2,NULL,2,NULL,'BRK202500000024','',0,NULL,NULL,NULL,NULL,1,'express','individual',2,'DAP',360.00,'UGX','PICKED_UP','2025-10-26 16:34:12',NULL,NULL,'2025-10-26 19:34:12',NULL,'2025-10-30 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1.1, \"insurance\": true, \"dimensions\": {\"width\": 86, \"height\": 67, \"length\": 77}, \"declared_value\": 2805, \"special_instructions\": null}','eyJpdiI6IlR0REhkTUhnN2RQb21pcWtyS2R1UEE9PSIsInZhbHVlIjoiS3hkaHQvR2h1QVpTUWU4Q1ZyYysyZz09IiwibWFjIjoiY2E4ZWVhNzcyZjUyY2QwNjgzZTg4NmE5MTY4NzY4NmJiMDExOTc4MjZmZTBjMDUzYWIwYTA0OGUwOTM3Y2JhMSIsInR',NULL,'2025-10-26 12:34:12','2025-10-26 16:34:12'),(161,1,546,3,1,NULL,1,NULL,'BRK202500000025','',0,NULL,NULL,NULL,NULL,546,'priority','individual',2,'DDP',264.00,'UGX','PICKED_UP','2025-10-20 12:34:12',NULL,NULL,'2025-10-20 14:34:12',NULL,'2025-10-27 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2, \"insurance\": false, \"dimensions\": {\"width\": 38, \"height\": 17, \"length\": 36}, \"declared_value\": 4511, \"special_instructions\": \"Handle with care\"}','eyJpdiI6ImlDZEdnSVk3aTlad3dyV08wMzBnZ0E9PSIsInZhbHVlIjoiWGRSaTJDYzdlNjhBWWc5bVVoVG1Pdz09IiwibWFjIjoiNWZiOTlkYmI1NWJmMzMzY2UwOWJmZmM4OGE2MjcwNTdlNGRlOWJiNDlmMDk5ZTcwYWIyNzI4NDMwYWQ3MmZjMyIsInR',NULL,'2025-10-20 11:34:12','2025-10-20 12:34:12'),(162,1,1,3,1,NULL,1,NULL,'BRK202500000026','',1,'wrong_address','medium','Exception: wrong_address detected during transit','2025-11-07 01:34:12',1,'priority','individual',2,'DAP',281.00,'UGX','PICKED_UP','2025-11-05 16:34:12',NULL,NULL,'2025-11-05 17:34:12',NULL,'2025-11-09 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.5, \"insurance\": false, \"dimensions\": {\"width\": 78, \"height\": 47, \"length\": 92}, \"declared_value\": 654, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ild6YzVsR2h4R21IWUZPMmhWT0NuUmc9PSIsInZhbHVlIjoiUlRUV28rSVpvODJpMUZBQ3dXamcwdz09IiwibWFjIjoiYTA3NWU3ZGJkNTcyMzg0YWMzODkxNjJmNzhjZDVlZDNjOGJhMTBiZjI1NDg5NGZmNjFkYTMwMTEzZWUyOGQ5MSIsInR',NULL,'2025-11-05 12:34:12','2025-11-05 16:34:12'),(163,1,544,1,2,NULL,2,NULL,'BRK202500000027','',0,NULL,NULL,NULL,NULL,544,'express','individual',1,'DAP',286.00,'UGX','PICKED_UP','2025-10-27 15:34:12',NULL,NULL,'2025-10-27 16:34:12',NULL,'2025-11-03 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 4.9, \"insurance\": false, \"dimensions\": {\"width\": 57, \"height\": 88, \"length\": 99}, \"declared_value\": 3645, \"special_instructions\": \"Handle with care\"}','eyJpdiI6ImxrMC9UcTV1ZFVvQ3ZtdzhBeG9pNEE9PSIsInZhbHVlIjoiZW1mdUJySFpEOENtKzkyNlg2US96Zz09IiwibWFjIjoiMzkxYzY3ZGRiMjJkY2U4NzU3YjA2MTU1MzE1MzQ1YmU2ODA1MTdlZTFkODQ1YWUxNTBmMzUxNmNmODk4ZTNiNSIsInR',NULL,'2025-10-27 12:34:12','2025-10-27 15:34:12'),(164,1,631,2,1,NULL,16,NULL,'BRK202500000028','',0,NULL,NULL,NULL,NULL,631,'standard','individual',2,'DDP',429.00,'UGX','PICKED_UP','2025-11-05 14:34:12',NULL,NULL,'2025-11-05 16:34:12',NULL,'2025-11-11 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1.5, \"insurance\": true, \"dimensions\": {\"width\": 13, \"height\": 80, \"length\": 40}, \"declared_value\": 4213, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ik1majNSYWJLVDVlUEFYdzVPaDZncEE9PSIsInZhbHVlIjoiQldicFcxdjY0VEorTVl2OHp6YU5VUT09IiwibWFjIjoiOWM3MDM2ZWRiN2M5NDFhNTFiYjVmYmMzYzQ4NjZmYTEyYTFiNDFjNDBkMmZmZWQzZDU2NzhiNmFiM2JjNGMxOCIsInR',NULL,'2025-11-05 12:34:12','2025-11-05 14:34:12'),(165,1,546,1,2,NULL,2,NULL,'BRK202500000029','',0,NULL,NULL,NULL,NULL,546,'express','individual',2,'DAP',418.00,'UGX','BOOKED','2025-10-12 15:34:12',NULL,NULL,'2025-10-12 17:34:12',NULL,'2025-10-16 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 4.2, \"insurance\": true, \"dimensions\": {\"width\": 71, \"height\": 71, \"length\": 18}, \"declared_value\": 3775, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ikx0ZGFuYUhKZVhrOUNmWU8vMm8wWUE9PSIsInZhbHVlIjoicmVubW5FOC8wd1V5QitmYmNQV0xsdz09IiwibWFjIjoiYjExOGQ0NDMxODE0OTFkYjc2NTA1MTZiZmRhMTY3ODIyMWQwYWFhZTBkMTFhYzRlNThiMTNhMWE2OGQ2MTIzYiIsInR',NULL,'2025-10-12 11:34:12','2025-10-12 15:34:12'),(166,1,545,3,1,NULL,1,NULL,'BRK202500000030','',0,NULL,NULL,NULL,NULL,545,'priority','individual',1,'DAP',366.00,'UGX','BOOKED','2025-10-26 16:34:12',NULL,NULL,'2025-10-26 17:34:12',NULL,'2025-10-30 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.2, \"insurance\": true, \"dimensions\": {\"width\": 73, \"height\": 89, \"length\": 27}, \"declared_value\": 4991, \"special_instructions\": null}','eyJpdiI6IjRwVVlPT3J5RGdxRDlHK1BRRkxpRFE9PSIsInZhbHVlIjoiS05RN2VWazVYcnpDaTBUOXNBSWZSUT09IiwibWFjIjoiZTYyZjU5YWMxYzMxMDc1MjQ4MTlmOTFkZGYzZTU3NDFjMmU4YWEwNDc0MWE2OWI3M2MwZWYyMDJlNDE0ZDExYyIsInR',NULL,'2025-10-26 12:34:12','2025-10-26 16:34:12'),(167,1,629,3,1,NULL,1,NULL,'BRK202500000031','',1,'damage','high','Exception: damage detected during transit','2025-11-05 03:34:12',629,'priority','individual',1,'DDP',389.00,'UGX','BOOKED','2025-11-04 15:34:12',NULL,NULL,'2025-11-04 18:34:12',NULL,'2025-11-08 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2.3, \"insurance\": true, \"dimensions\": {\"width\": 23, \"height\": 87, \"length\": 19}, \"declared_value\": 2109, \"special_instructions\": null}','eyJpdiI6InBDdEQ5TnA3aVpxVVVCSG1ZYmFZRWc9PSIsInZhbHVlIjoid2xJM01QS2NuTUx4c2gyUldUa2NNUT09IiwibWFjIjoiOTRhN2FiYmJhMTMzODk2NTY2MzFjYjgzZWZkMzg1Mjg1ZjdhOGI4ODNhN2RhOThjYWQ4NGI3NTU5MTUyMzVlOSIsInR',NULL,'2025-11-04 12:34:12','2025-11-04 15:34:12'),(168,1,2,3,1,NULL,1,NULL,'BRK202500000032','',0,NULL,NULL,NULL,NULL,2,'express','individual',1,'DAP',479.00,'UGX','BOOKED','2025-10-17 12:34:12',NULL,NULL,'2025-10-17 14:34:12',NULL,'2025-10-20 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2.1, \"insurance\": false, \"dimensions\": {\"width\": 31, \"height\": 65, \"length\": 100}, \"declared_value\": 2443, \"special_instructions\": null}','eyJpdiI6IjFFYUtsbGNEeUtvZ25DendRc0xUU3c9PSIsInZhbHVlIjoickhTeTZMVHFuU0h0dkJHSWdkUmNldz09IiwibWFjIjoiMWM5YTU2MDM1ZTFkNjFiMGRkN2RhZWViOTczNTRiYWMzN2JhYjllMjhhMmVmNTRiMzEyMWM0ZGVjNTQ5ZTUxMSIsInR',NULL,'2025-10-17 11:34:12','2025-10-17 12:34:12'),(169,1,631,1,2,NULL,2,NULL,'BRK202500000033','',0,NULL,NULL,NULL,NULL,631,'standard','individual',1,'DDP',289.00,'UGX','BOOKED','2025-10-30 13:34:12',NULL,NULL,'2025-10-30 14:34:12',NULL,'2025-11-05 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2.2, \"insurance\": false, \"dimensions\": {\"width\": 38, \"height\": 73, \"length\": 94}, \"declared_value\": 3950, \"special_instructions\": null}','eyJpdiI6InpBQjRxSzhvL3gwT1luZjEzbmJtYnc9PSIsInZhbHVlIjoiSjNrNU1wSEMyM2h1ZXM0bWsraklLZz09IiwibWFjIjoiZWMzOTNhNmVhZDliYjMxN2E2ODJiYmNhZmNmMTdiZTFmYzdlODhjOWYxN2Y2OTY0Mzk5OTRhMDI5YTNiODdjZSIsInR',NULL,'2025-10-30 12:34:12','2025-10-30 13:34:12'),(170,1,545,2,1,NULL,16,NULL,'BRK202500000034','',0,NULL,NULL,NULL,NULL,545,'priority','individual',1,'DAP',250.00,'UGX','BOOKED','2025-10-29 13:34:12',NULL,NULL,'2025-10-29 15:34:12',NULL,'2025-11-03 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 0.7, \"insurance\": true, \"dimensions\": {\"width\": 40, \"height\": 43, \"length\": 15}, \"declared_value\": 3226, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IkJqczNEK0dRNjFDcmJTUkdpQjcvYmc9PSIsInZhbHVlIjoiNFFRV2xoRkQ0UjYyK3JMM0MrQVFyUT09IiwibWFjIjoiZWI2ZWY4MTlmMmNlZjFiYzM4MmJjZGI1ZjVkN2MyZmNiNmQyNDg4OWI1MDhmNTkwNGQ5OTI2Mjk3ODJlZTU1NiIsInR',NULL,'2025-10-29 12:34:12','2025-10-29 13:34:12'),(171,1,7,1,2,NULL,2,NULL,'BRK202500000035','',1,'customer_unavailable','medium','Exception: customer_unavailable detected during transit','2025-11-07 23:34:12',7,'express','individual',1,'DAP',369.00,'UGX','BOOKED','2025-11-06 15:34:12',NULL,NULL,'2025-11-06 18:34:12',NULL,'2025-11-11 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2.8, \"insurance\": false, \"dimensions\": {\"width\": 83, \"height\": 28, \"length\": 78}, \"declared_value\": 329, \"special_instructions\": null}','eyJpdiI6IjF2OGdMN1ZmVzVld2FqcStYWlJzWEE9PSIsInZhbHVlIjoieE4rc0xhL2xqQjVCUHMxNjZXdFN6Zz09IiwibWFjIjoiYjkwZWU2Njg0MDZkNzI1MzU0YTUzNjU4ODYyMWFmYTBhNDExM2E4N2VhYzdlMzhhMmNiYTQ3YWQxZWY4ZTJmOSIsInR',NULL,'2025-11-06 12:34:12','2025-11-06 15:34:12'),(172,1,542,2,1,NULL,16,NULL,'BRK202500000036','',0,NULL,NULL,NULL,NULL,542,'priority','individual',1,'DAP',71.00,'UGX','BOOKED','2025-10-30 14:34:12',NULL,NULL,'2025-10-30 15:34:12',NULL,'2025-11-05 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.7, \"insurance\": true, \"dimensions\": {\"width\": 85, \"height\": 48, \"length\": 18}, \"declared_value\": 4015, \"special_instructions\": null}','eyJpdiI6IkJYQThYMkRFVnhybE10SUp6a1pSMFE9PSIsInZhbHVlIjoibnh5R0E5QktSekFoZmVYbktyWEprQT09IiwibWFjIjoiNTA4NzE2YzdlMWQxZTc2YmE5NGY2NzEwYjQzMWVhNzA1NzY5ZmMzNmQwOTRlMzdlN2FlNGE2M2E2MTZlZjg4MSIsInR',NULL,'2025-10-30 12:34:12','2025-10-30 14:34:12'),(173,1,545,1,2,NULL,2,NULL,'BRK202500000037','',0,NULL,NULL,NULL,NULL,545,'express','individual',1,'DAP',312.00,'UGX','BOOKED','2025-11-02 13:34:12',NULL,NULL,'2025-11-02 15:34:12',NULL,'2025-11-05 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2, \"insurance\": false, \"dimensions\": {\"width\": 18, \"height\": 62, \"length\": 20}, \"declared_value\": 3491, \"special_instructions\": null}','eyJpdiI6IktMR0J1ajBsUnJVczVkUXUrcjRmR1E9PSIsInZhbHVlIjoidy8yTUNkQjhZU1ZGblpNV3lhcjFnZz09IiwibWFjIjoiYTQ1YjY2ODkyZjM1YjNjYWVmYmE5OGM3Y2Q3NTliYzFjNzMzMDJlMzYwYTQ1ZDczYzQ1YzA1OTY0NmFkZmIxMSIsInR',NULL,'2025-11-02 12:34:12','2025-11-02 13:34:12'),(174,1,8,2,1,NULL,16,NULL,'BRK202500000038','',0,NULL,NULL,NULL,NULL,8,'priority','individual',1,'DDP',390.00,'UGX','BOOKED','2025-10-17 14:34:12',NULL,NULL,'2025-10-17 17:34:12',NULL,'2025-10-23 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 4.9, \"insurance\": false, \"dimensions\": {\"width\": 23, \"height\": 84, \"length\": 20}, \"declared_value\": 2679, \"special_instructions\": null}','eyJpdiI6IlFzMHlFeC92eHhtSXpoMEQyVU1RR3c9PSIsInZhbHVlIjoiUEx3bGJQMUpMaE1RYU00cVhIMEVNZz09IiwibWFjIjoiZWY1NzUyNDQ5ZDgxZDhhOTZmYTMwMDUyNTE2ZGYyN2NkMDYyYzM5ZjJlMTJmMzI1MDgxNzJhY2U1OTQ5YTRmNCIsInR',NULL,'2025-10-17 11:34:12','2025-10-17 14:34:12'),(175,1,1,2,1,NULL,16,NULL,'BRK202500000039','',0,NULL,NULL,NULL,NULL,1,'standard','individual',1,'DAP',412.00,'UGX','BOOKED','2025-11-05 16:34:12',NULL,NULL,'2025-11-05 18:34:12',NULL,'2025-11-11 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1.4, \"insurance\": true, \"dimensions\": {\"width\": 72, \"height\": 99, \"length\": 81}, \"declared_value\": 3796, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IkE4b2VySnl1UExlVm81ZHllWWs3ZlE9PSIsInZhbHVlIjoiaWk0bWRadFIvWXVxKzViVUJKMVMrdz09IiwibWFjIjoiNDI5Y2YyOWZlNmVkNzBiMjE4MDlkMjc5NWUyYzRjYzcyYWJjNTBmOGQyNjJhOTAxYzBkOTM2ODE2MjY4ZTkzMCIsInR',NULL,'2025-11-05 12:34:12','2025-11-05 16:34:12'),(176,1,8,2,1,NULL,16,NULL,'BRK202500000040','',0,NULL,NULL,NULL,NULL,8,'priority','individual',1,'DAP',288.00,'UGX','BOOKED','2025-10-23 15:34:12',NULL,NULL,'2025-10-23 18:34:12',NULL,'2025-10-27 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 2.8, \"insurance\": false, \"dimensions\": {\"width\": 83, \"height\": 93, \"length\": 32}, \"declared_value\": 4131, \"special_instructions\": null}','eyJpdiI6ImxMTVMvRlY3LytIYlB6MWErRGdYMFE9PSIsInZhbHVlIjoiYzRNYjRabWlLelEvZThhS2JOM0hCQT09IiwibWFjIjoiYjg4YmViM2UwZGRmOWFmMjE1YTFhOWUyMjllMjIxM2UzNWQ2YWU5MjE0ZDY2NDFiYjdjYWM3YTI3NTlkNTQzNCIsInR',NULL,'2025-10-23 11:34:12','2025-10-23 15:34:12'),(177,1,631,3,1,NULL,1,NULL,'BRK202500000041','',0,NULL,NULL,NULL,NULL,631,'priority','individual',1,'DDP',311.00,'UGX','BOOKED','2025-11-02 16:34:12',NULL,NULL,'2025-11-02 18:34:12',NULL,'2025-11-09 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1.2, \"insurance\": true, \"dimensions\": {\"width\": 96, \"height\": 46, \"length\": 16}, \"declared_value\": 1855, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IkM5OE01bU9kVEJmdS9WNCtzMEVwUEE9PSIsInZhbHVlIjoiSy9FZDk4WkNrbzlrdHIyU0dYWFBvUT09IiwibWFjIjoiNGQxZjczYWFkMGI0MjBhNGY4MmIxNmI0NGFmMWU5NmJjYmY1ZmJlYTk4YTBiYjk2YzIxYTc5NWM3NDc4YThmMSIsInR',NULL,'2025-11-02 12:34:12','2025-11-02 16:34:12'),(178,1,2,3,1,NULL,1,NULL,'BRK202500000042','',0,NULL,NULL,NULL,NULL,2,'standard','individual',1,'DAP',473.00,'UGX','BOOKED','2025-10-31 13:34:12',NULL,NULL,'2025-10-31 14:34:12',NULL,'2025-11-05 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 5, \"insurance\": true, \"dimensions\": {\"width\": 13, \"height\": 72, \"length\": 33}, \"declared_value\": 1649, \"special_instructions\": null}','eyJpdiI6IkxsUHBFcDhCUTV5SzhiRFlEU2d3WWc9PSIsInZhbHVlIjoiQU1mSThxSEFITTFWVDhWNHZ0Q25jZz09IiwibWFjIjoiZTA1YmI5ZTEyMWU0YTAxZDk3MTkzY2ViNjFkNTViNDk5ZjdmOTg2YjgzZWUyMDk4ZjIwMGUxYzEzNDAyYjNhYiIsInR',NULL,'2025-10-31 12:34:12','2025-10-31 13:34:12'),(179,1,629,2,1,NULL,16,NULL,'BRK202500000043','',0,NULL,NULL,NULL,NULL,629,'standard','individual',1,'DDP',464.00,'UGX','BOOKED','2025-10-20 12:34:12',NULL,NULL,'2025-10-20 14:34:12',NULL,'2025-10-23 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1.5, \"insurance\": false, \"dimensions\": {\"width\": 67, \"height\": 85, \"length\": 51}, \"declared_value\": 2442, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Im5xSXdXbGpCT05IMjdxNVZIWlhJMWc9PSIsInZhbHVlIjoidy9GMDN2M0VKaXpyREdkUno2cHdSdz09IiwibWFjIjoiMWRmZmM0NmU2MDkxMjg5OTc3NGI2MzIwYzFmNzEyYzZlYjBmNDViMmE5ZmY0MmNlOWE3ZGFkMTc5OGUxMGI0NSIsInR',NULL,'2025-10-20 11:34:12','2025-10-20 12:34:12'),(180,1,542,3,1,NULL,1,NULL,'BRK202500000044','',0,NULL,NULL,NULL,NULL,542,'priority','individual',1,'DAP',60.00,'UGX','BOOKED','2025-10-29 15:34:12',NULL,NULL,'2025-10-29 16:34:12',NULL,'2025-11-04 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.6, \"insurance\": true, \"dimensions\": {\"width\": 84, \"height\": 59, \"length\": 67}, \"declared_value\": 1662, \"special_instructions\": \"Handle with care\"}','eyJpdiI6ImExNjNrVmYxQ1IzSGo0Si9Yb0ZCdEE9PSIsInZhbHVlIjoiUCtrZE56QnBzeXZ4eDVJSWZ3eFZidz09IiwibWFjIjoiMjFmZDliYmZmYjU2ZDY5ODdiYmYzNjExODNhMDY2MGVhNGVlYjkzN2E0MjY0NjA5MjZlZWU4ZDQxYjhlYWI4ZiIsInR',NULL,'2025-10-29 12:34:12','2025-10-29 15:34:12'),(181,1,545,3,1,NULL,1,NULL,'BRK202500000045','',0,NULL,NULL,NULL,NULL,545,'priority','individual',3,'DDP',388.00,'UGX','BOOKED','2025-10-27 14:34:12',NULL,NULL,'2025-10-27 15:34:12',NULL,'2025-11-02 12:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 1.2, \"insurance\": true, \"dimensions\": {\"width\": 52, \"height\": 51, \"length\": 38}, \"declared_value\": 152, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IkpzUG9LRW8rSXlLT1J3Zmg1Y2M3Unc9PSIsInZhbHVlIjoicy9BZkUzaU5VcnNMeVdoTFN4Rm0rZz09IiwibWFjIjoiZjI1ODRjNTgxOGRlNzZiM2YxZGUxZjU2MzRiNjBiODJlOTZmOTUwNTdkMWMwMDNkZjBhOTJkZWVjNzUzNjJkNiIsInR',NULL,'2025-10-27 12:34:12','2025-10-27 14:34:12'),(182,1,535,2,1,NULL,16,NULL,'BRK202500000046','',0,NULL,NULL,NULL,NULL,535,'express','individual',1,'DAP',313.00,'UGX','BOOKED','2025-10-11 15:34:12',NULL,NULL,'2025-10-11 16:34:12',NULL,'2025-10-15 11:34:12',NULL,NULL,NULL,NULL,'{\"weight\": 3.2, \"insurance\": true, \"dimensions\": {\"width\": 35, \"height\": 52, \"length\": 77}, \"declared_value\": 1753, \"special_instructions\": null}','eyJpdiI6ImhxVkVDemU0TnBVdXlVRS9JZVhOSlE9PSIsInZhbHVlIjoiaTdKeU5UWkhyM0drZmJtY2w4M09IUT09IiwibWFjIjoiZGE3NDUwNTNhZGE0ZjczNjA5MWZjZTU1N2UyNjdlMDNmN2Y1NzQyNGJjMWJmMGU5MDhhYTcxZjNlYzZmMmY5YSIsInR',NULL,'2025-10-11 11:34:12','2025-10-11 15:34:12'),(183,1,538,3,1,NULL,1,NULL,'BRK202500000047','',0,NULL,NULL,NULL,NULL,538,'priority','individual',2,'DDP',174.00,'UGX','BOOKED','2025-10-23 12:34:13',NULL,NULL,'2025-10-23 15:34:13',NULL,'2025-10-27 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 1.1, \"insurance\": true, \"dimensions\": {\"width\": 41, \"height\": 51, \"length\": 42}, \"declared_value\": 2882, \"special_instructions\": null}','eyJpdiI6Im9ETUU5SFV6UEN4MFQ3TW55dEc0V1E9PSIsInZhbHVlIjoieTh2b0NkdUxWUjR6RnRnd1pYYlByQT09IiwibWFjIjoiY2JmNGYwMjIzN2YyNzc5MmQzN2VjNzhmZWI2MTNhZTZkNGUxZmNlZTJiYmY4ZDFmYjQzMWYzYzU2MDk4MGIwNiIsInR',NULL,'2025-10-23 11:34:13','2025-10-23 12:34:13'),(184,1,545,1,2,NULL,2,NULL,'BRK202500000048','',0,NULL,NULL,NULL,NULL,545,'express','individual',2,'DDP',329.00,'UGX','BOOKED','2025-10-20 15:34:13',NULL,NULL,'2025-10-20 16:34:13',NULL,'2025-10-26 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 1.1, \"insurance\": true, \"dimensions\": {\"width\": 92, \"height\": 59, \"length\": 48}, \"declared_value\": 1961, \"special_instructions\": null}','eyJpdiI6IjFwQVBLWTE5eFBZLzAwbzNvK0xGVUE9PSIsInZhbHVlIjoiK3ZQZ09OL0Z0aElSMFI0K3NCcW9rdz09IiwibWFjIjoiMDFmNTYxZTk3MThlMDk3YjU5ODM2MmU0ZmExYWViMGQ3YTg5ZTA0ZDIyMmIwNWFiOTI3Zjg4OTY0NGZlZGIzOSIsInR',NULL,'2025-10-20 11:34:13','2025-10-20 15:34:13'),(185,1,7,2,1,NULL,16,NULL,'BRK202500000049','',0,NULL,NULL,NULL,NULL,7,'express','individual',1,'DAP',90.00,'UGX','BOOKED','2025-11-04 16:34:13',NULL,NULL,'2025-11-04 19:34:13',NULL,'2025-11-07 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 4.8, \"insurance\": false, \"dimensions\": {\"width\": 76, \"height\": 63, \"length\": 21}, \"declared_value\": 2813, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ik4xWGkwNmVmczQzOFl3b1IzMTNFZnc9PSIsInZhbHVlIjoiSTNNZHowdVI0VFplOVpOUHl4TEJGZz09IiwibWFjIjoiNjNlNDIxMzg4MmM0MWFjYmYxMWNlMjZhNjIzOTBiMmVhMzdjNGRmM2VkOTM2MTIyMmRjOTkwZmRmODE1NjM1MCIsInR',NULL,'2025-11-04 12:34:13','2025-11-04 16:34:13'),(186,1,630,2,1,NULL,16,NULL,'BRK202500000050','',0,NULL,NULL,NULL,NULL,630,'priority','individual',1,'DDP',238.00,'UGX','BOOKED','2025-10-13 14:34:13',NULL,NULL,'2025-10-13 17:34:13',NULL,'2025-10-20 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 2.7, \"insurance\": false, \"dimensions\": {\"width\": 100, \"height\": 93, \"length\": 71}, \"declared_value\": 2939, \"special_instructions\": null}','eyJpdiI6IkRGWTBiM29JWVc4aGdIcDBqcGN1blE9PSIsInZhbHVlIjoiTnFGT24zM0tjUHdFNC9uMGhZTThMQT09IiwibWFjIjoiNzQ5ZGJjMzU3NTQzOWZjMWIyOGJiMjEzZTRmYzkyMGNjNGNmZWNlNWE5YTJjNTBjNzRkNGQzMTY1YWUxYTZkMyIsInR',NULL,'2025-10-13 11:34:13','2025-10-13 14:34:13'),(187,1,546,1,2,NULL,2,NULL,'BRK202500000051','',0,NULL,NULL,NULL,NULL,546,'priority','individual',2,'DDP',443.00,'UGX','BOOKED','2025-10-08 12:34:13',NULL,NULL,'2025-10-08 13:34:13',NULL,'2025-10-13 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 1.4, \"insurance\": true, \"dimensions\": {\"width\": 40, \"height\": 39, \"length\": 49}, \"declared_value\": 3955, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlUweXRtS3JjY00wUG1qWTNERHhveWc9PSIsInZhbHVlIjoiN0wrZll0VVQrcmt1SXFsR2FDUXpaZz09IiwibWFjIjoiMGE1NTNlYjVmN2VmYWQ0NzEwOGM5ZmZmZjI1NTM3Njk3OGI0ZDQxMTExM2RlNDEwYTQ2NzYxOTQyNjBjODI5ZCIsInR',NULL,'2025-10-08 11:34:13','2025-10-08 12:34:13'),(188,1,535,1,2,NULL,2,NULL,'BRK202500000052','',0,NULL,NULL,NULL,NULL,535,'priority','individual',1,'DAP',438.00,'UGX','BOOKED','2025-10-18 12:34:13',NULL,NULL,'2025-10-18 14:34:13',NULL,'2025-10-24 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 0.3, \"insurance\": true, \"dimensions\": {\"width\": 91, \"height\": 24, \"length\": 17}, \"declared_value\": 366, \"special_instructions\": null}','eyJpdiI6Ik5hbzQySHIvZy9XTFFMY1VicHM2dUE9PSIsInZhbHVlIjoiRFhRekMzZWRwdmt5QTNWeGpMb0hRZz09IiwibWFjIjoiMWNjN2U0MmYzZDIyZTA4MjE0N2NhZTNmMGEzMmQzZmFjMDgzYWJiYzBhMGMzODc5M2RkMzEyNThmMDRhZjc1MyIsInR',NULL,'2025-10-18 11:34:13','2025-10-18 12:34:13'),(189,1,545,3,1,NULL,1,NULL,'BRK202500000053','',0,NULL,NULL,NULL,NULL,545,'priority','individual',3,'DDP',97.00,'UGX','BOOKED','2025-10-09 14:34:13',NULL,NULL,'2025-10-09 15:34:13',NULL,'2025-10-16 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 4.6, \"insurance\": true, \"dimensions\": {\"width\": 52, \"height\": 14, \"length\": 46}, \"declared_value\": 137, \"special_instructions\": null}','eyJpdiI6IjZLRDgyZ29mRHpSalVXOGorUlVtdUE9PSIsInZhbHVlIjoiUWozdHpNNUN2TTZRdWc2UGx4SFVldz09IiwibWFjIjoiOTFjZjUyM2MyZDE0MTQyYTJjZTIzZTdiMTA5YmFiZTM5NzZhNmY2YTQzZmUzOWIzNTYwNDFkY2EzOTgyZjUxYiIsInR',NULL,'2025-10-09 11:34:13','2025-10-09 14:34:13'),(190,1,544,1,2,NULL,2,NULL,'BRK202500000054','',0,NULL,NULL,NULL,NULL,544,'express','individual',3,'DAP',488.00,'UGX','BOOKED','2025-11-05 16:34:13',NULL,NULL,'2025-11-05 18:34:13',NULL,'2025-11-09 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 4, \"insurance\": true, \"dimensions\": {\"width\": 37, \"height\": 89, \"length\": 15}, \"declared_value\": 595, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IkM3cjd4WjZDLzVtRzc4SzVieEpjSFE9PSIsInZhbHVlIjoiQm9nekE1eTlXaDQwNENPTzVybFd2QT09IiwibWFjIjoiMmM0Y2VkMjk0MDIyOTJkNWI2ZTQ5MWY2Yjk4NDliOGZjYjU1MGVjZGRjZTRjYjZjOTYxNTEzMDAxZDMwYjQ1ZCIsInR',NULL,'2025-11-05 12:34:13','2025-11-05 16:34:13'),(191,1,542,1,2,NULL,2,NULL,'BRK202500000055','',0,NULL,NULL,NULL,NULL,542,'priority','individual',3,'DDP',407.00,'UGX','BOOKED','2025-10-29 15:34:13',NULL,NULL,'2025-10-29 18:34:13',NULL,'2025-11-03 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 1.2, \"insurance\": false, \"dimensions\": {\"width\": 50, \"height\": 12, \"length\": 62}, \"declared_value\": 3633, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlRKMEJyblF0TmJKbDNjZWhHR3hQemc9PSIsInZhbHVlIjoiWU1xSW1YNk1HSVg0cXZJdGRMWDhVdz09IiwibWFjIjoiYzdmZGMxODNhYTlmMGEyYzY0MWE4NDQ0MjVjYWFmZjhlNGFjMjlhM2ZkMTZjNzJhMDM4ZTVhOTQ5OTQwZDRjNSIsInR',NULL,'2025-10-29 12:34:13','2025-10-29 15:34:13'),(192,1,535,3,1,NULL,1,NULL,'BRK202500000056','',0,NULL,NULL,NULL,NULL,535,'priority','individual',2,'DAP',419.00,'UGX','BOOKED','2025-10-20 12:34:13',NULL,NULL,'2025-10-20 14:34:13',NULL,'2025-10-25 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 2.8, \"insurance\": false, \"dimensions\": {\"width\": 20, \"height\": 72, \"length\": 20}, \"declared_value\": 685, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IjR5SVY5SHJ2aVJSNDVnSXhRQUdDMWc9PSIsInZhbHVlIjoicmxNa1pVQUlHTWE0L2NPK2xBcGxndz09IiwibWFjIjoiOGNjNDk0Y2M5OGVkNDkwMWRkYjFiZTMwMTY1Y2I0YThmNjU3MTI0MWI5MzIzOTQ0NTliMDY1MTk4ZTAyYjI5NCIsInR',NULL,'2025-10-20 11:34:13','2025-10-20 12:34:13'),(193,1,631,3,1,NULL,1,NULL,'BRK202500000057','out_for_delivery',0,NULL,NULL,NULL,NULL,631,'express','individual',1,'DDP',107.00,'UGX','OUT_FOR_DELIVERY','2025-10-20 12:34:13',NULL,NULL,'2025-10-20 14:34:13',NULL,'2025-10-27 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 0.6, \"insurance\": true, \"dimensions\": {\"width\": 97, \"height\": 87, \"length\": 35}, \"declared_value\": 1556, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Im52OVN0cysvbFRxY01UeGNFTXJMeVE9PSIsInZhbHVlIjoiVzZXbENTbEMvWVRES01NOGFvc0ZFZz09IiwibWFjIjoiYWRlYWU3ZjU3Mzc1NGM0NWZhZjg0NTZmNjQwNjYwZWY3NGM2MjM1NDViMWVkM2E4NTkzNTEwMjUzNmVlZGFiMiIsInR',NULL,'2025-10-20 11:34:13','2025-10-20 12:34:13'),(194,1,538,2,1,NULL,16,NULL,'BRK202500000058','out_for_delivery',0,NULL,NULL,NULL,NULL,538,'standard','individual',2,'DAP',344.00,'UGX','OUT_FOR_DELIVERY','2025-10-28 15:34:13',NULL,NULL,'2025-10-28 16:34:13',NULL,'2025-11-01 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 4.4, \"insurance\": true, \"dimensions\": {\"width\": 13, \"height\": 60, \"length\": 84}, \"declared_value\": 4333, \"special_instructions\": null}','eyJpdiI6Ik5BVEdwOGZZVFJoZnNzK241R3ZvUnc9PSIsInZhbHVlIjoiK29kQ1RTcnYyTUZMWGlvRzhZTHlZZz09IiwibWFjIjoiNjg5ZWRmYzM2Y2FkNDI1ZTUwNzZmM2YyMTY5NjU2NWVhOTdiMmQ1ZjUyM2QyZjE4NjgzMjQ5MjllNThjMDc4NSIsInR',NULL,'2025-10-28 12:34:13','2025-10-28 15:34:13'),(195,1,546,3,1,NULL,1,NULL,'BRK202500000059','out_for_delivery',0,NULL,NULL,NULL,NULL,546,'express','individual',1,'DAP',410.00,'UGX','OUT_FOR_DELIVERY','2025-10-26 16:34:13',NULL,NULL,'2025-10-26 17:34:13',NULL,'2025-10-30 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 3.6, \"insurance\": false, \"dimensions\": {\"width\": 42, \"height\": 76, \"length\": 37}, \"declared_value\": 639, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlMvU05DYzUrc0ZidFZjaDQ1enBZb2c9PSIsInZhbHVlIjoiMHI5clFXMzI2eGFtQUViUk9pb1A2UT09IiwibWFjIjoiNzA4ZWZjMzI5MjM3ZWVjYmUzZWUxYWY2YmI0YmNmZmViNzllMDBmYzA1MGQ0ZTEwZTZlMTFlZTAyZjAyODUyMSIsInR',NULL,'2025-10-26 12:34:13','2025-10-26 16:34:13'),(196,1,538,1,2,NULL,2,NULL,'BRK202500000060','out_for_delivery',0,NULL,NULL,NULL,NULL,538,'express','individual',1,'DDP',172.00,'UGX','OUT_FOR_DELIVERY','2025-10-21 13:34:13',NULL,NULL,'2025-10-21 16:34:13',NULL,'2025-10-25 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 3.8, \"insurance\": false, \"dimensions\": {\"width\": 42, \"height\": 79, \"length\": 20}, \"declared_value\": 1245, \"special_instructions\": \"Handle with care\"}','eyJpdiI6InA4RUZobGpDVUdHUW8xUkd4WlVvNHc9PSIsInZhbHVlIjoiM0pKUWZjNUloak9jZHZaMGtCY3FDQT09IiwibWFjIjoiYmI4NjFmODVlMzVlOWVhMTllOTMzYzA4ODhkMTI3YTI4ZjA2MzIzYjQ2Mjc0NGNmNDI0MTg0N2M3NjA5NTUyNiIsInR',NULL,'2025-10-21 11:34:13','2025-10-21 13:34:13'),(197,1,546,2,1,NULL,16,NULL,'BRK202500000061','out_for_delivery',1,'delay','medium','Exception: delay detected during transit','2025-10-16 04:34:13',546,'priority','individual',1,'DDP',115.00,'UGX','OUT_FOR_DELIVERY','2025-10-14 13:34:13',NULL,NULL,'2025-10-14 16:34:13',NULL,'2025-10-21 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 2, \"insurance\": false, \"dimensions\": {\"width\": 14, \"height\": 26, \"length\": 56}, \"declared_value\": 3209, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IkZsVTdZZnpvZHBxa3pOZjVQOW93OGc9PSIsInZhbHVlIjoiTkdYRFlxSlFPTU8ra0NKTERwTlZwZz09IiwibWFjIjoiMzU2YzM3ODlhNzk4NTAyYzIxZGZjNTIxOGUwZmU0MjQ3ZmI4NTM2NjI2YzM2ZjBmMzcxM2U5Yzc4MTQ1OWIwNSIsInR',NULL,'2025-10-14 11:34:13','2025-10-14 13:34:13'),(198,1,538,3,1,NULL,1,NULL,'BRK202500000062','out_for_delivery',0,NULL,NULL,NULL,NULL,538,'express','individual',1,'DDP',144.00,'UGX','OUT_FOR_DELIVERY','2025-10-24 12:34:13',NULL,NULL,'2025-10-24 13:34:13',NULL,'2025-10-30 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 1.6, \"insurance\": true, \"dimensions\": {\"width\": 73, \"height\": 70, \"length\": 94}, \"declared_value\": 1445, \"special_instructions\": null}','eyJpdiI6InMyc3B1M2N0VTAvM3c4dlNqRjdEZVE9PSIsInZhbHVlIjoiTkZsRHJYUml0aWFSMmhSRDFNOEY4QT09IiwibWFjIjoiMjk3Y2IzZmEwNGQ1Y2NkNmZmN2FjYmIzYTk2OTg2YzcwOTExMmE5YTQ0MjY1ODU5NGNmNjQyOWYxYmJiMjk4YyIsInR',NULL,'2025-10-24 11:34:13','2025-10-24 12:34:13'),(199,1,629,3,1,NULL,1,NULL,'BRK202500000063','out_for_delivery',0,NULL,NULL,NULL,NULL,629,'standard','individual',1,'DAP',140.00,'UGX','OUT_FOR_DELIVERY','2025-11-02 13:34:13',NULL,NULL,'2025-11-02 16:34:13',NULL,'2025-11-08 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 1.9, \"insurance\": true, \"dimensions\": {\"width\": 11, \"height\": 81, \"length\": 82}, \"declared_value\": 1369, \"special_instructions\": null}','eyJpdiI6IlhGTGM5WlVOTFdyN3FGamRPNHNWOXc9PSIsInZhbHVlIjoibE9BR2ZtTjArdHpwVjAvbnBuMFYzZz09IiwibWFjIjoiZmRiMTM5OTNjNDgzOThhZGE5YzU0OTdhN2FiNzNlZjI2MDRjNjNjODlmNjViODMzM2Q3NDQ4MWM0OTkzMGExZiIsInR',NULL,'2025-11-02 12:34:13','2025-11-02 13:34:13'),(200,1,8,2,1,NULL,16,NULL,'BRK202500000064','out_for_delivery',0,NULL,NULL,NULL,NULL,8,'standard','individual',2,'DAP',146.00,'UGX','OUT_FOR_DELIVERY','2025-10-10 12:34:13',NULL,NULL,'2025-10-10 13:34:13',NULL,'2025-10-17 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 4.9, \"insurance\": false, \"dimensions\": {\"width\": 86, \"height\": 97, \"length\": 73}, \"declared_value\": 3875, \"special_instructions\": \"Handle with care\"}','eyJpdiI6ImpxWWJTVHNMSE55Z1FSS3VmU0tnd3c9PSIsInZhbHVlIjoidU9vR0t0RjBVczg5S0Z1Uk9KZjZ4QT09IiwibWFjIjoiYmVhOTMzYWQ4ZjllYmQzNmU0MmYzNTBmYjJmMzZiMzJhZTUwOGJiMTRmYzUzNWE5YWJiZjJlZDVjNzk0NDljNiIsInR',NULL,'2025-10-10 11:34:13','2025-10-10 12:34:13'),(201,1,1,2,1,NULL,16,NULL,'BRK202500000065','out_for_delivery',0,NULL,NULL,NULL,NULL,1,'express','individual',3,'DDP',217.00,'UGX','OUT_FOR_DELIVERY','2025-10-13 13:34:13',NULL,NULL,'2025-10-13 14:34:13',NULL,'2025-10-17 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 2.8, \"insurance\": true, \"dimensions\": {\"width\": 24, \"height\": 37, \"length\": 30}, \"declared_value\": 1194, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Imh2eUt6S0ppd0I3R1pPdHNSNUVndmc9PSIsInZhbHVlIjoiV2RKMVJJc05lRVU2dDJXVFh0TDhkQT09IiwibWFjIjoiOThlNGQxNmM1YjE1MGY1ZWM3YWZjNDA0MGRjNDJlODc4MTE4MTgxZmQzMmNhZDZlY2E2YmMxNTE0OTUyNmQ4MiIsInR',NULL,'2025-10-13 11:34:13','2025-10-13 13:34:13'),(202,1,630,1,2,NULL,2,NULL,'BRK202500000066','out_for_delivery',0,NULL,NULL,NULL,NULL,630,'priority','individual',1,'DDP',162.00,'UGX','OUT_FOR_DELIVERY','2025-10-25 15:34:13',NULL,NULL,'2025-10-25 17:34:13',NULL,'2025-10-30 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 2.7, \"insurance\": false, \"dimensions\": {\"width\": 55, \"height\": 78, \"length\": 52}, \"declared_value\": 2420, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IklLUDR0emZZZWJ5WGpzSGFOQ1ZnOEE9PSIsInZhbHVlIjoiSnVtUTBkbHB6Z0tHOWlBb3ZHaTJKdz09IiwibWFjIjoiNTVlYWYyZWRiNWYwZGFjNThlM2VmZjFkZDRmZDk5NWQ4YTczNWVjZTY1MzgzOTE1NzM5N2RjOTY2NWVlMGUzNSIsInR',NULL,'2025-10-25 11:34:13','2025-10-25 15:34:13'),(203,1,631,3,1,NULL,1,NULL,'BRK202500000067','out_for_delivery',0,NULL,NULL,NULL,NULL,631,'priority','individual',1,'DAP',260.00,'UGX','OUT_FOR_DELIVERY','2025-10-23 14:34:13',NULL,NULL,'2025-10-23 17:34:13',NULL,'2025-10-27 12:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 0.2, \"insurance\": false, \"dimensions\": {\"width\": 85, \"height\": 63, \"length\": 52}, \"declared_value\": 649, \"special_instructions\": \"Handle with care\"}','eyJpdiI6ImhGdGtLQjVobnhsQ3FNTS9Lci96WlE9PSIsInZhbHVlIjoidHJYa1ZhQTJNNlhBS0NBZEh5NTlpdz09IiwibWFjIjoiYTdjNTFlMTYzNTAyM2FhZjgxY2EyNGYxNTM1OGM5ZTU5ZWI3MzJmMGNlYjZkNzA2YTNkOGZmOTBjMzQ1NTc4ZiIsInR',NULL,'2025-10-23 11:34:13','2025-10-23 14:34:13'),(204,1,7,2,1,NULL,16,NULL,'BRK202500000068','out_for_delivery',0,NULL,NULL,NULL,NULL,7,'standard','individual',2,'DDP',305.00,'UGX','OUT_FOR_DELIVERY','2025-10-13 13:34:13',NULL,NULL,'2025-10-13 15:34:13',NULL,'2025-10-19 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 1.2, \"insurance\": false, \"dimensions\": {\"width\": 62, \"height\": 13, \"length\": 87}, \"declared_value\": 395, \"special_instructions\": null}','eyJpdiI6Ik12aEp4RTBXWjk0ekliN2p0Y3NwZXc9PSIsInZhbHVlIjoiaTUwd3FKWHd3NnE4REQ1WGRVSE9UZz09IiwibWFjIjoiYmI0OTI0ZjQ2OGZjODIxYjA2OTVhMDIxYTkxZjI4NGYyZDYyMzcwZjdjMWMxNWY1ZTY0MTdjN2U0NzlhYzQzZCIsInR',NULL,'2025-10-13 11:34:13','2025-10-13 13:34:13'),(205,1,631,2,1,NULL,16,NULL,'BRK202500000069','delivered',0,NULL,NULL,NULL,NULL,631,'express','individual',3,'DDP',189.00,'UGX','DELIVERED','2025-11-02 13:34:13',NULL,NULL,'2025-11-02 14:34:13',NULL,'2025-11-09 12:34:13','2025-11-03 04:34:13',NULL,NULL,NULL,'{\"weight\": 3.3, \"insurance\": true, \"dimensions\": {\"width\": 36, \"height\": 20, \"length\": 22}, \"declared_value\": 3706, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ii8vUm8vcDlubEZlMnB6WEg2cDViT0E9PSIsInZhbHVlIjoiRDlRWWpNZnNxc1F5cEl1M1F5WE84Zz09IiwibWFjIjoiYjE2ZTM0NTRlODNhNWQyYjQxZDI4MTBkMmEzMzQ0YWY2MGMyOThjODRhYWU1ZTQ2NjY4MjAzY2VlNGFmZTMwMyIsInR',NULL,'2025-11-02 12:34:13','2025-11-03 04:34:13'),(206,1,629,1,2,NULL,2,NULL,'BRK202500000070','delivered',0,NULL,NULL,NULL,NULL,629,'standard','individual',1,'DAP',495.00,'UGX','DELIVERED','2025-10-12 15:34:13',NULL,NULL,'2025-10-12 16:34:13',NULL,'2025-10-16 11:34:13','2025-10-13 08:34:13',NULL,NULL,NULL,'{\"weight\": 0.4, \"insurance\": false, \"dimensions\": {\"width\": 80, \"height\": 71, \"length\": 91}, \"declared_value\": 3041, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IjJtdHJPc0EvUWdhTGEwZWxRdW00dUE9PSIsInZhbHVlIjoiQjBTUGFrYTBOb2Q4MmFqY0RObzU3dz09IiwibWFjIjoiMDc0OGI0MzNhZDhhYzBiYzg4NTY3OTUxYTZjOTIwZTViYzNlYTFhM2ZhNzY0Mjg3YTM1ZGNhYzAyMjRlOWM1ZSIsInR',NULL,'2025-10-12 11:34:13','2025-10-13 08:34:13'),(207,1,629,3,1,NULL,1,NULL,'BRK202500000071','delivered',0,NULL,NULL,NULL,NULL,629,'standard','individual',2,'DDP',283.00,'UGX','DELIVERED','2025-10-10 12:34:13',NULL,NULL,'2025-10-10 13:34:13',NULL,'2025-10-16 11:34:13','2025-10-10 23:34:13',NULL,NULL,NULL,'{\"weight\": 4.9, \"insurance\": true, \"dimensions\": {\"width\": 43, \"height\": 15, \"length\": 39}, \"declared_value\": 3793, \"special_instructions\": null}','eyJpdiI6Ik5xcFl3NTRtSFN0SEFHc0NyVGJlQnc9PSIsInZhbHVlIjoiSE9Rc0JHUU52UmlhaXQ4K3hTc29xdz09IiwibWFjIjoiN2M5ZTZkNWY2ZjFkYjI1ZWFmMzkzNGUyODI1NWMwODllYWVkOWRmZDkzZjUxNmY3ZTE3YmU1ZTUwZTlhZjNkYSIsInR',NULL,'2025-10-10 11:34:13','2025-10-10 23:34:13'),(208,1,544,1,2,NULL,2,NULL,'BRK202500000072','delivered',0,NULL,NULL,NULL,NULL,544,'standard','individual',2,'DDP',184.00,'UGX','DELIVERED','2025-10-16 14:34:13',NULL,NULL,'2025-10-16 15:34:13',NULL,'2025-10-20 11:34:13','2025-10-17 03:34:13',NULL,NULL,NULL,'{\"weight\": 0.7, \"insurance\": true, \"dimensions\": {\"width\": 80, \"height\": 32, \"length\": 10}, \"declared_value\": 2888, \"special_instructions\": \"Handle with care\"}','eyJpdiI6InFYaGJRM2NucnlORGsrZGxRaXNPTlE9PSIsInZhbHVlIjoiRDRoRHhjT2NqOUlYb3JkZVZEeHNOUT09IiwibWFjIjoiM2VjNjU5MTgyOGM2ZTAzOTM2MTUyOWU3OGVhMjU2ZWNhOGQ1OGFkMDk4MjVkODIwZTQ3N2EyYzAxOTdjZjExZCIsInR',NULL,'2025-10-16 11:34:13','2025-10-17 03:34:13'),(209,1,1,3,1,NULL,1,NULL,'BRK202500000073','delivered',0,NULL,NULL,NULL,NULL,1,'standard','individual',1,'DDP',184.00,'UGX','DELIVERED','2025-10-26 16:34:13',NULL,NULL,'2025-10-26 18:34:13',NULL,'2025-10-29 12:34:13','2025-10-27 10:34:13',NULL,NULL,NULL,'{\"weight\": 0.4, \"insurance\": false, \"dimensions\": {\"width\": 45, \"height\": 23, \"length\": 34}, \"declared_value\": 4501, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Iktpa3JRcUNrMEcrS21tYnlsN1haY3c9PSIsInZhbHVlIjoiSHgxdkZlQUlMdEYraXhqYTRIUWVaQT09IiwibWFjIjoiYzcxNDM0ZGE4NGRhNGYzNjUxZjE3NjhjMjllNDg1MzlkYmNlZDY1NzliMTFmMWQyZDM2MTI5NGQ3ZmFkYzdhNiIsInR',NULL,'2025-10-26 12:34:13','2025-10-27 10:34:13'),(210,1,630,3,1,NULL,1,NULL,'BRK202500000074','delivered',0,NULL,NULL,NULL,NULL,630,'priority','individual',2,'DDP',457.00,'UGX','DELIVERED','2025-10-29 15:34:13',NULL,NULL,'2025-10-29 16:34:13',NULL,'2025-11-04 12:34:13','2025-10-30 06:34:13',NULL,NULL,NULL,'{\"weight\": 4.2, \"insurance\": false, \"dimensions\": {\"width\": 81, \"height\": 11, \"length\": 18}, \"declared_value\": 843, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IjZYQXNkVXZpNloxZVZSbkVWTzVybmc9PSIsInZhbHVlIjoiWTNYZFR6d0dNVCt5elRFK21ON2Q4Zz09IiwibWFjIjoiMjM0NWFkNjk2MTg4ODJiMjQ2ZTAzOWRhNzkxOTY2MGM5MmU2NDAzZWUxNzRkMDdlNGE0NjgwYzc4NzI0NjgzYiIsInR',NULL,'2025-10-29 12:34:13','2025-10-30 06:34:13'),(211,1,2,1,2,NULL,2,NULL,'BRK202500000075','delivered',0,NULL,NULL,NULL,NULL,2,'priority','individual',2,'DAP',246.00,'UGX','DELIVERED','2025-10-24 13:34:13',NULL,NULL,'2025-10-24 16:34:13',NULL,'2025-10-28 12:34:13','2025-10-25 16:34:13',NULL,NULL,NULL,'{\"weight\": 0.7, \"insurance\": false, \"dimensions\": {\"width\": 46, \"height\": 53, \"length\": 13}, \"declared_value\": 4402, \"special_instructions\": null}','eyJpdiI6IlpxbUdSR2t5QlYyN01xbVdlcHFGZVE9PSIsInZhbHVlIjoiTW91NzJ5d1E2SDVHQWUwNHo2MCtsdz09IiwibWFjIjoiNDg0OGYyZmRmMjdiMGMzMzVlMTIxZmFiNWY1NWIyMDJjYTc0ZWYzYjc3ZTJhYzE4N2NkOWIyYjI2MDliYzUyYyIsInR',NULL,'2025-10-24 11:34:13','2025-10-25 16:34:13'),(212,1,544,2,1,NULL,16,NULL,'BRK202500000076','delivered',0,NULL,NULL,NULL,NULL,544,'standard','individual',1,'DDP',411.00,'UGX','DELIVERED','2025-10-12 12:34:13',NULL,NULL,'2025-10-12 14:34:13',NULL,'2025-10-17 11:34:13','2025-10-13 12:34:13',NULL,NULL,NULL,'{\"weight\": 1.1, \"insurance\": false, \"dimensions\": {\"width\": 78, \"height\": 97, \"length\": 54}, \"declared_value\": 654, \"special_instructions\": null}','eyJpdiI6Im9pWk5rWnI1VENtOTFZNU53R1hnOVE9PSIsInZhbHVlIjoiV0ZWVDQ4bXRMSFQ2ZSt3UXltRkZ1QT09IiwibWFjIjoiZjkwMjcyYzUyZWIyMDM2OTNiZjZmNzQyOWVmZmE3MTk2ZDEzMWI2Mzc4MzRiOTAyN2E1M2MzMTNmOGFiZWE5YSIsInR',NULL,'2025-10-12 11:34:13','2025-10-13 12:34:13'),(213,1,630,3,1,NULL,1,NULL,'BRK202500000077','delivered',0,NULL,NULL,NULL,NULL,630,'express','individual',1,'DAP',281.00,'UGX','DELIVERED','2025-10-22 13:34:13',NULL,NULL,'2025-10-22 16:34:13',NULL,'2025-10-28 12:34:13','2025-10-23 07:34:13',NULL,NULL,NULL,'{\"weight\": 4.3, \"insurance\": false, \"dimensions\": {\"width\": 21, \"height\": 52, \"length\": 51}, \"declared_value\": 2603, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlhFSGpFUnpYeEk1Y2RqdkJzS3FnSVE9PSIsInZhbHVlIjoiYlUxNng5ampUQmZvUU91VWloR0s3dz09IiwibWFjIjoiNTk0YTA0OWYzZWMyM2ViZjVlM2Q5NGIwNDgxYjM2ZGQ0MmYyN2Q2YjM1YjIwZjdiYTIxMWI2YzVkNDk2MDlkMiIsInR',NULL,'2025-10-22 11:34:13','2025-10-23 07:34:13'),(214,1,544,3,1,NULL,1,NULL,'BRK202500000078','delivered',0,NULL,NULL,NULL,NULL,544,'express','individual',1,'DDP',102.00,'UGX','DELIVERED','2025-10-09 12:34:13',NULL,NULL,'2025-10-09 13:34:13',NULL,'2025-10-14 11:34:13','2025-10-09 22:34:13',NULL,NULL,NULL,'{\"weight\": 3.7, \"insurance\": true, \"dimensions\": {\"width\": 37, \"height\": 46, \"length\": 37}, \"declared_value\": 2130, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlR0Ymt3aU9CSWdxSUMrWlhEdktHa0E9PSIsInZhbHVlIjoiUjZTM1QwZlFGbGhyYS9ZK2xGQ3FNQT09IiwibWFjIjoiMzk5NTkxYzdkNWU1NmUyNTZiYjRiOWViYWZiMmE0NzczNzFiMGRlZmZjMTQ3N2ZlNGE3NTM1ZjlkYjY2N2IyNSIsInR',NULL,'2025-10-09 11:34:13','2025-10-09 22:34:13'),(215,1,544,3,1,NULL,1,NULL,'BRK202500000079','delivered',0,NULL,NULL,NULL,NULL,544,'priority','individual',1,'DDP',69.00,'UGX','DELIVERED','2025-10-25 14:34:13',NULL,NULL,'2025-10-25 15:34:13',NULL,'2025-10-31 12:34:13','2025-10-26 14:34:13',NULL,NULL,NULL,'{\"weight\": 2.9, \"insurance\": true, \"dimensions\": {\"width\": 93, \"height\": 55, \"length\": 31}, \"declared_value\": 4306, \"special_instructions\": null}','eyJpdiI6IlRFS2dpSC9uMDYxaC9adzZYZWNUNHc9PSIsInZhbHVlIjoiWENjM3pINytUdzR2RzI5SU1ZRlA5Zz09IiwibWFjIjoiMzgwZjIwOTE3NWUxOTM1NGYwYjIyNGEzZDg2ZTgwZTQ4OTBjYjZmMWM1NTIyMDA4YTRmZWQxMzAwZGViMDgzYyIsInR',NULL,'2025-10-25 11:34:13','2025-10-26 14:34:13'),(216,1,544,1,2,NULL,2,NULL,'BRK202500000080','delivered',0,NULL,NULL,NULL,NULL,544,'priority','individual',2,'DAP',437.00,'UGX','DELIVERED','2025-10-29 13:34:13',NULL,NULL,'2025-10-29 16:34:13',NULL,'2025-11-05 12:34:13','2025-10-30 07:34:13',NULL,NULL,NULL,'{\"weight\": 2, \"insurance\": true, \"dimensions\": {\"width\": 36, \"height\": 14, \"length\": 70}, \"declared_value\": 3083, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlY4V1Y3S3pIZm1Ed0tmUHFjUkJ2cWc9PSIsInZhbHVlIjoiSHZyZ0JpeEZVdlAzUjZpbHBDcWlPZz09IiwibWFjIjoiNDE1NTk3YTU1ODg4MjI3MGM2ZGQ2YmJhODg0NTYxMGRiOTM5MDUxNTA3MTdmODc1YWIyYjc2NzU2NDc1MzI1OSIsInR',NULL,'2025-10-29 12:34:13','2025-10-30 07:34:13'),(217,1,630,3,1,NULL,1,NULL,'BRK202500000081','delivered',0,NULL,NULL,NULL,NULL,630,'standard','individual',2,'DAP',473.00,'UGX','DELIVERED','2025-10-09 14:34:13',NULL,NULL,'2025-10-09 15:34:13',NULL,'2025-10-12 11:34:13','2025-10-10 09:34:13',NULL,NULL,NULL,'{\"weight\": 0.6, \"insurance\": true, \"dimensions\": {\"width\": 90, \"height\": 72, \"length\": 70}, \"declared_value\": 4517, \"special_instructions\": null}','eyJpdiI6IjJPeER0dndORE9uZmhBSUUyMkI2Znc9PSIsInZhbHVlIjoiM05mL3RkdFIrUTNQbVJvb0FVOGd0UT09IiwibWFjIjoiY2VkYmNmMjczY2U1OWQ3YjE0NjUyNmZlMTExODBmNDI3NzFhMWFjZGM2NmJhYzEwYTNkY2NjYjMyZTQzNmU0YSIsInR',NULL,'2025-10-09 11:34:13','2025-10-10 09:34:13'),(218,1,535,1,2,NULL,2,NULL,'BRK202500000082','delivered',0,NULL,NULL,NULL,NULL,535,'standard','individual',1,'DDP',95.00,'UGX','DELIVERED','2025-10-27 15:34:13',NULL,NULL,'2025-10-27 16:34:13',NULL,'2025-11-03 12:34:13','2025-10-28 03:34:13',NULL,NULL,NULL,'{\"weight\": 3.5, \"insurance\": true, \"dimensions\": {\"width\": 11, \"height\": 41, \"length\": 58}, \"declared_value\": 1604, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ik80WE1CNHM1WGZYRjFvbCtEMjFxK1E9PSIsInZhbHVlIjoid0FrUm1Da1RhUEN3WmxBSVh2WVpvUT09IiwibWFjIjoiZDkzOTYyNTc0ZDUxNDk5MzI4NTdlYWIzYzkzM2UyNzMxMDM1YTI0NThhMGQzNmIzZDM4OWJhMWVlZDA0MGU2NyIsInR',NULL,'2025-10-27 12:34:13','2025-10-28 03:34:13'),(219,1,8,3,1,NULL,1,NULL,'BRK202500000083','delivered',0,NULL,NULL,NULL,NULL,8,'standard','individual',1,'DAP',219.00,'UGX','DELIVERED','2025-10-24 13:34:13',NULL,NULL,'2025-10-24 14:34:13',NULL,'2025-10-30 12:34:13','2025-10-25 09:34:13',NULL,NULL,NULL,'{\"weight\": 4.6, \"insurance\": true, \"dimensions\": {\"width\": 21, \"height\": 41, \"length\": 30}, \"declared_value\": 4648, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IjhSSTlrMHNLNlpURE1yaVZ0VnZKRHc9PSIsInZhbHVlIjoibUV0Q0FIeWg2emNBaCs0VHovWUJqUT09IiwibWFjIjoiZThkYzhjNWEwZDM1M2YwNGY3ZmE4Mjg4NzgyOTc3N2RlMGVkYjllYTE2M2Y4NjI5OWVhZDQ1YzAzYTgwODUwMCIsInR',NULL,'2025-10-24 11:34:13','2025-10-25 09:34:13'),(220,1,631,3,1,NULL,1,NULL,'BRK202500000084','delivered',0,NULL,NULL,NULL,NULL,631,'priority','individual',1,'DDP',370.00,'UGX','DELIVERED','2025-10-29 14:34:13',NULL,NULL,'2025-10-29 17:34:13',NULL,'2025-11-02 12:34:13','2025-10-30 05:34:13',NULL,NULL,NULL,'{\"weight\": 1.5, \"insurance\": true, \"dimensions\": {\"width\": 57, \"height\": 54, \"length\": 65}, \"declared_value\": 816, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ing4d1dDZHljKzNodUlWNkFOWW5RZUE9PSIsInZhbHVlIjoiSmxMSEV6TGszNzJPWWFVWFdDK3dVdz09IiwibWFjIjoiOGQwOTdkM2M0MDZkMWM1MzE4OGVkMjY4ZjA0NzUzODQxOGM4M2FlYjUyNTEzMGE0YjFhZTdmNTRmZDJjYjAzOCIsInR',NULL,'2025-10-29 12:34:13','2025-10-30 05:34:13'),(221,1,8,3,1,NULL,1,NULL,'BRK202500000085','delivered',0,NULL,NULL,NULL,NULL,8,'express','individual',1,'DDP',450.00,'UGX','DELIVERED','2025-10-28 13:34:13',NULL,NULL,'2025-10-28 14:34:13',NULL,'2025-10-31 12:34:13','2025-10-29 13:34:13',NULL,NULL,NULL,'{\"weight\": 3, \"insurance\": true, \"dimensions\": {\"width\": 52, \"height\": 68, \"length\": 52}, \"declared_value\": 1781, \"special_instructions\": null}','eyJpdiI6IldTejRBOWRVYWNVOHdDNm9FZjk4R1E9PSIsInZhbHVlIjoiLzdlVzFKdkFlNE9BRTNWSXRlWkZRZz09IiwibWFjIjoiNjk0N2UwMTc2MTgxNzI5MWYzNjUxMTNiYjEyZGUzZmU4ODkzMTNmYTYyODI4NmM3NGViOGIxYjAwNGM0NmZlNiIsInR',NULL,'2025-10-28 12:34:13','2025-10-29 13:34:13'),(222,1,542,2,1,NULL,16,NULL,'BRK202500000086','delivered',0,NULL,NULL,NULL,NULL,542,'standard','individual',1,'DAP',480.00,'UGX','DELIVERED','2025-10-29 15:34:13',NULL,NULL,'2025-10-29 16:34:13',NULL,'2025-11-02 12:34:13','2025-10-30 10:34:13',NULL,NULL,NULL,'{\"weight\": 2.5, \"insurance\": true, \"dimensions\": {\"width\": 21, \"height\": 71, \"length\": 73}, \"declared_value\": 4321, \"special_instructions\": null}','eyJpdiI6ImMyODNDNFA0aUZpU2xLRHhMYjhtSFE9PSIsInZhbHVlIjoiYVl2MHJvbmc2Yi9RaGRJUDhiSjgrUT09IiwibWFjIjoiOThlZTFlMmIyMDY2Mzk1MzFiYzE0OTcxMzYyNGE4MjFjMjc4ODdkYzllYmU0ODQyZjk5MTFkMTE0ZGVkOGFiNSIsInR',NULL,'2025-10-29 12:34:13','2025-10-30 10:34:13'),(223,1,2,2,1,NULL,16,NULL,'BRK202500000087','delivered',0,NULL,NULL,NULL,NULL,2,'priority','individual',2,'DDP',319.00,'UGX','DELIVERED','2025-10-26 14:34:13',NULL,NULL,'2025-10-26 16:34:13',NULL,'2025-10-31 12:34:13','2025-10-27 05:34:13',NULL,NULL,NULL,'{\"weight\": 1.5, \"insurance\": true, \"dimensions\": {\"width\": 49, \"height\": 96, \"length\": 76}, \"declared_value\": 841, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ik9rZC9nVkl1WUNjR0JvYm9oNm94dXc9PSIsInZhbHVlIjoiNXVTU1BOR0xzOGNNcFlGTEtKS1BQUT09IiwibWFjIjoiMzI0N2ZlM2FlODk2ZTQzZDE4MzEwM2VjMDYzMWNmYzIyMmZlZTY3MTNlZWUwMDBmMmQzYjcwNzBkOGM3NTYxOSIsInR',NULL,'2025-10-26 12:34:13','2025-10-27 05:34:13'),(224,1,2,2,1,NULL,16,NULL,'BRK202500000088','delivered',0,NULL,NULL,NULL,NULL,2,'express','individual',1,'DAP',147.00,'UGX','DELIVERED','2025-10-28 15:34:13',NULL,NULL,'2025-10-28 16:34:13',NULL,'2025-10-31 12:34:13','2025-10-29 04:34:13',NULL,NULL,NULL,'{\"weight\": 4.1, \"insurance\": false, \"dimensions\": {\"width\": 82, \"height\": 32, \"length\": 24}, \"declared_value\": 4535, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Ilc0dDg0U0JydFNwSzUvaktxWFVIWUE9PSIsInZhbHVlIjoiOUVFVy9rbndlTjJuSTBnbHFXRTlMUT09IiwibWFjIjoiZjZmMTgwZjA2MTljOTJkNzIyYWU1NDBiN2Q3ZjY3N2E4Y2EwZTNhYTQ3ZWRjYjVkNjI5NDE4MGE0ZTVjNjMzNSIsInR',NULL,'2025-10-28 12:34:13','2025-10-29 04:34:13'),(225,1,535,3,1,NULL,1,NULL,'BRK202500000089','delivered',0,NULL,NULL,NULL,NULL,535,'priority','individual',1,'DDP',236.00,'UGX','DELIVERED','2025-10-09 14:34:13',NULL,NULL,'2025-10-09 17:34:13',NULL,'2025-10-14 11:34:13','2025-10-10 02:34:13',NULL,NULL,NULL,'{\"weight\": 4.4, \"insurance\": true, \"dimensions\": {\"width\": 96, \"height\": 57, \"length\": 52}, \"declared_value\": 2783, \"special_instructions\": \"Handle with care\"}','eyJpdiI6Im40Ykx1NDY1L25lTVh3em1jNm9lOEE9PSIsInZhbHVlIjoiSW8yWUNWRWRTUCtLKzg2Q0xYWWk2Zz09IiwibWFjIjoiMmUzZTVlMmE0YzE2M2ZjYzYzYzM1MmM4MTQ0YTYzNzBjYzkyN2UzZGE4NDc5MmEyMmQ3MjdjYWM2YWQ4OTFkOSIsInR',NULL,'2025-10-09 11:34:13','2025-10-10 02:34:13'),(226,1,545,3,1,NULL,1,NULL,'BRK202500000090','delivered',0,NULL,NULL,NULL,NULL,545,'standard','individual',3,'DAP',385.00,'UGX','DELIVERED','2025-10-13 12:34:13',NULL,NULL,'2025-10-13 14:34:13',NULL,'2025-10-17 11:34:13','2025-10-14 10:34:13',NULL,NULL,NULL,'{\"weight\": 2.5, \"insurance\": false, \"dimensions\": {\"width\": 13, \"height\": 86, \"length\": 14}, \"declared_value\": 1867, \"special_instructions\": null}','eyJpdiI6ImhRRkVERjFWbUlBR2ozcVBIYjE3K3c9PSIsInZhbHVlIjoiamczWUdmVmFKS0RTQ01KTzJFTUplUT09IiwibWFjIjoiNzNmN2E0NWU1NGIxMGYxYmQ0ZDE5MDU1Mjk5MTFiNjAzOWYyZjc0YmQzNWM1ZWI0ZTA4NzYyNzhjZmVkNjBjNiIsInR',NULL,'2025-10-13 11:34:13','2025-10-14 10:34:13'),(227,1,538,2,1,NULL,16,NULL,'BRK202500000091','delivered',0,NULL,NULL,NULL,NULL,538,'express','individual',1,'DDP',233.00,'UGX','DELIVERED','2025-10-18 15:34:13',NULL,NULL,'2025-10-18 17:34:13',NULL,'2025-10-24 11:34:13','2025-10-19 07:34:13',NULL,NULL,NULL,'{\"weight\": 4.4, \"insurance\": false, \"dimensions\": {\"width\": 18, \"height\": 37, \"length\": 93}, \"declared_value\": 2745, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IjNTTFJzYlc3Z28ybTBSZHBKUDk4SGc9PSIsInZhbHVlIjoidGVZRCtOR1ZHVlBLWDNwOEQxdEthQT09IiwibWFjIjoiM2VjODgzNWI5ZmRjM2RhYzU1ZGFlZTBlNzg2NTU0ZmFlZmFiOTcwZmNjN2RhMmZmYWI5NDdhMzI3NTI5N2I3YSIsInR',NULL,'2025-10-18 11:34:13','2025-10-19 07:34:13'),(228,1,630,2,1,NULL,16,NULL,'BRK202500000092','delivered',0,NULL,NULL,NULL,NULL,630,'express','individual',3,'DDP',186.00,'UGX','DELIVERED','2025-10-21 15:34:13',NULL,NULL,'2025-10-21 16:34:13',NULL,'2025-10-24 11:34:13','2025-10-22 00:34:13',NULL,NULL,NULL,'{\"weight\": 3.7, \"insurance\": true, \"dimensions\": {\"width\": 28, \"height\": 43, \"length\": 94}, \"declared_value\": 3488, \"special_instructions\": \"Handle with care\"}','eyJpdiI6InV4OXdFWk1oZ1k3cGVGVkZodzNyUFE9PSIsInZhbHVlIjoiQjRUb0lRMS9qYWZsWndESk8zL1Jxdz09IiwibWFjIjoiYWMxMTg0OThjZWYxODMxYWY1OTdkNWQ1NGIyOGFmNjYxODBmNmIxODE2ZWNjZWQwMjk4Mzc1ZWNhNDk2MDFmZSIsInR',NULL,'2025-10-21 11:34:13','2025-10-22 00:34:13'),(229,1,535,3,1,NULL,1,NULL,'BRK202500000093','delivered',1,'customer_unavailable','low','Exception: customer_unavailable detected during transit','2025-11-08 04:34:13',535,'priority','individual',1,'DDP',349.00,'UGX','DELIVERED','2025-11-06 15:34:13',NULL,NULL,'2025-11-06 18:34:13',NULL,'2025-11-12 12:34:13','2025-11-07 08:34:13',NULL,NULL,NULL,'{\"weight\": 2.7, \"insurance\": false, \"dimensions\": {\"width\": 29, \"height\": 13, \"length\": 65}, \"declared_value\": 2938, \"special_instructions\": \"Handle with care\"}','eyJpdiI6IlltSVpZRnVVWlhld040blZVVkk4Qnc9PSIsInZhbHVlIjoiTFcwODBMRjdqakNEYlNwYjNTYTJiZz09IiwibWFjIjoiYWY5ODdjNmUzMzllYjIzZGYzZjI0ZDJjOTNlNjJhYWI4MjBmYzA1Njk3YmE0YjFlYjllYWVjNzU1NWU1NzI4NSIsInR',NULL,'2025-11-06 12:34:13','2025-11-07 08:34:13'),(230,1,535,3,1,NULL,1,NULL,'BRK202500000094','exception',1,'damage','low','Exception: damage detected during transit','2025-10-16 04:34:13',535,'express','individual',1,'DDP',478.00,'UGX','BOOKED','2025-10-14 13:34:13',NULL,NULL,NULL,NULL,'2025-10-19 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 3.8, \"insurance\": true, \"dimensions\": {\"width\": 95, \"height\": 52, \"length\": 31}, \"declared_value\": 1956, \"special_instructions\": null}','eyJpdiI6ImdpbHRzUmNXK3NKTDZvZnU5N0I5RlE9PSIsInZhbHVlIjoia2JGS1ljWDMzTWFXc3lHSWVQb1Ardz09IiwibWFjIjoiMWZkYjM1ZDdjOTkzMzQ5OTgxNzQzNzM5MmQzMzJlMzAyNTM0Yjg5YzM2NDJjYmQwNjEzYjhkMWRkNzkzOTdkZCIsInR',NULL,'2025-10-14 11:34:13','2025-10-14 13:34:13'),(231,1,546,1,2,NULL,2,NULL,'BRK202500000095','exception',1,'delay','high','Exception: delay detected during transit','2025-10-17 08:34:13',546,'express','individual',1,'DAP',482.00,'UGX','BOOKED','2025-10-16 12:34:13',NULL,NULL,NULL,NULL,'2025-10-19 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 4.4, \"insurance\": true, \"dimensions\": {\"width\": 43, \"height\": 33, \"length\": 10}, \"declared_value\": 2728, \"special_instructions\": null}','eyJpdiI6IndVRncvN3F4cVRGNHJtWFNTbVYzZFE9PSIsInZhbHVlIjoiYUI1RnVRQ0gxWUlOY1ZqY25RV3ltQT09IiwibWFjIjoiMzQ5YjYwY2Q3ZWY1YzZjNTc4OTcwMmExZDdiMDVhZDI4ZTdkMWE5MzYwNjllMmE3MTZiYmY2MjYxN2Y1Yzk2NCIsInR',NULL,'2025-10-16 11:34:13','2025-10-16 12:34:13'),(232,1,546,2,1,NULL,16,NULL,'BRK202500000096','exception',1,'wrong_address','high','Exception: wrong_address detected during transit','2025-10-09 09:34:13',546,'priority','individual',3,'DDP',193.00,'UGX','BOOKED','2025-10-08 15:34:13',NULL,NULL,NULL,NULL,'2025-10-12 11:34:13',NULL,NULL,NULL,NULL,'{\"weight\": 1, \"insurance\": false, \"dimensions\": {\"width\": 13, \"height\": 80, \"length\": 76}, \"declared_value\": 4469, \"special_instructions\": null}','eyJpdiI6Im1NbE5mZ2VnM0l6eU1PR2ZEaUhsRXc9PSIsInZhbHVlIjoiakhVZ0xkWm10ZjFDTW5yWFV1a2liUT09IiwibWFjIjoiY2Y3ZjliZmM1ZmUwOTczMGVhZjZjOTU1NTJkOTNiMDAxNDcxM2NjMmJiMWM3NGUzNTM2N2I5MTYyZmViNTBlOSIsInR',NULL,'2025-10-08 11:34:13','2025-10-08 15:34:13');
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
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
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
) ENGINE=InnoDB AUTO_INCREMENT=661 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'baraka Administrator','info@baraka.co',NULL,'$2y$10$qwObLjayCQkTxwXqm.zGZeHVpElS5Q.c3zeL2OA2uVY2QoI8lIhpa','256706215487','eyJpdiI6IkVqdUdkNmtwVXJRT0VDTmgzcDdXUFE9PSIsInZhbHVlIjoid2hxMXQzNzh4YU15ejhRTk9aZVl6Mkl5ZU8zb0thdGFFc2xyVGE2anBqMD0iLCJtYWMiOiI0ZjVkMzNiODlkNWIxY2JhMzIyMzBkM2I1NDk4Y2Q0NTk4ZGJkMGM1YzBlYTEwYjd','121212',NULL,NULL,NULL,1,NULL,'1999-11-22','vbcvb',1,'[\"dashboard_read\",\"calendar_read\",\"total_parcel\",\"total_user\",\"total_merchant\",\"total_delivery_man\",\"total_hubs\",\"total_accounts\",\"total_parcels_pending\",\"total_pickup_assigned\",\"total_received_warehouse\",\"total_deliveryman_assigned\",\"total_partial_deliverd\",\"total_parcels_deliverd\",\"recent_accounts\",\"recent_salary\",\"recent_hub\",\"all_statements\",\"income_expense_charts\",\"merchant_revenue_charts\",\"deliveryman_revenue_charts\",\"courier_revenue_charts\",\"recent_parcels\",\"bank_transaction\",\"log_read\",\"hub_read\",\"hub_create\",\"hub_update\",\"hub_delete\",\"hub_incharge_read\",\"hub_incharge_create\",\"hub_incharge_update\",\"hub_incharge_delete\",\"hub_incharge_assigned\",\"account_read\",\"account_create\",\"account_update\",\"account_delete\",\"income_read\",\"income_create\",\"income_update\",\"income_delete\",\"expense_read\",\"expense_create\",\"expense_update\",\"expense_delete\",\"todo_read\",\"todo_create\",\"todo_update\",\"todo_delete\",\"fund_transfer_read\",\"fund_transfer_create\",\"fund_transfer_update\",\"fund_transfer_delete\",\"role_read\",\"role_create\",\"role_update\",\"role_delete\",\"designation_read\",\"designation_create\",\"designation_update\",\"designation_delete\",\"department_read\",\"department_create\",\"department_update\",\"department_delete\",\"user_read\",\"user_create\",\"user_update\",\"user_delete\",\"permission_update\",\"merchant_read\",\"merchant_create\",\"merchant_update\",\"merchant_delete\",\"merchant_view\",\"merchant_delivery_charge_read\",\"merchant_delivery_charge_create\",\"merchant_delivery_charge_update\",\"merchant_delivery_charge_delete\",\"merchant_shop_read\",\"merchant_shop_create\",\"merchant_shop_update\",\"merchant_shop_delete\",\"merchant_payment_read\",\"merchant_payment_create\",\"merchant_payment_update\",\"merchant_payment_delete\",\"payment_read\",\"payment_create\",\"payment_update\",\"payment_delete\",\"payment_reject\",\"payment_process\",\"hub_payment_read\",\"hub_payment_create\",\"hub_payment_update\",\"hub_payment_delete\",\"hub_payment_reject\",\"hub_payment_process\",\"hub_payment_request_read\",\"hub_payment_request_create\",\"hub_payment_request_update\",\"hub_payment_request_delete\",\"parcel_read\",\"parcel_create\",\"parcel_update\",\"parcel_delete\",\"parcel_status_update\",\"delivery_man_read\",\"delivery_man_create\",\"delivery_man_update\",\"delivery_man_delete\",\"delivery_category_read\",\"delivery_category_create\",\"delivery_category_update\",\"delivery_category_delete\",\"delivery_charge_read\",\"delivery_charge_create\",\"delivery_charge_update\",\"delivery_charge_delete\",\"delivery_type_read\",\"delivery_type_status_change\",\"liquid_fragile_read\",\"liquid_fragile_update\",\"liquid_status_change\",\"packaging_read\",\"packaging_create\",\"packaging_update\",\"packaging_delete\",\"category_read\",\"category_create\",\"category_update\",\"category_delete\",\"account_heads_read\",\"database_backup_read\",\"salary_read\",\"salary_create\",\"salary_update\",\"salary_delete\",\"support_read\",\"support_create\",\"support_update\",\"support_delete\",\"support_reply\",\"support_status_update\",\"sms_settings_read\",\"sms_settings_create\",\"sms_settings_update\",\"sms_settings_delete\",\"sms_send_settings_read\",\"sms_send_settings_create\",\"sms_send_settings_update\",\"sms_send_settings_delete\",\"general_settings_read\",\"general_settings_update\",\"notification_settings_read\",\"notification_settings_update\",\"push_notification_read\",\"push_notification_create\",\"push_notification_update\",\"push_notification_delete\",\"asset_category_read\",\"asset_category_create\",\"asset_category_update\",\"asset_category_delete\",\"news_offer_read\",\"news_offer_create\",\"news_offer_update\",\"news_offer_delete\",\"parcel_status_reports\",\"parcel_wise_profit\",\"parcel_total_summery\",\"salary_reports\",\"merchant_hub_deliveryman\",\"salary_generate_read\",\"salary_generate_create\",\"salary_generate_update\",\"salary_generate_delete\",\"assets_read\",\"assets_create\",\"assets_update\",\"assets_delete\",\"fraud_read\",\"fraud_create\",\"fraud_update\",\"fraud_delete\",\"subscribe_read\",\"pickup_request_regular\",\"pickup_request_express\",\"invoice_read\",\"invoice_status_update\",\"social_login_settings_read\",\"social_login_settings_update\",\"payout_setup_settings_read\",\"payout_setup_settings_update\",\"online_payment_read\",\"payout_read\",\"payout_create\",\"hub_view\",\"paid_invoice_read\",\"invoice_generate_menually\",\"currency_read\",\"currency_create\",\"currency_update\",\"currency_delete\",\"social_link_read\",\"social_link_create\",\"social_link_update\",\"social_link_delete\",\"service_read\",\"service_create\",\"service_update\",\"service_delete\",\"why_courier_read\",\"why_courier_create\",\"why_courier_update\",\"why_courier_delete\",\"faq_read\",\"faq_create\",\"faq_update\",\"faq_delete\",\"partner_read\",\"partner_create\",\"partner_update\",\"partner_delete\",\"blogs_read\",\"blogs_create\",\"blogs_update\",\"blogs_delete\",\"pages_read\",\"pages_update\",\"section_read\",\"section_update\",\"mail_settings_read\",\"mail_settings_update\",\"wallet_request_read\",\"wallet_request_create\",\"wallet_request_delete\",\"wallet_request_approve\",\"wallet_request_reject\"]',NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:20:48','2025-11-06 10:13:42'),(2,'sanaa Administrator','info@sanaa.co',NULL,'$2y$10$/AWbM.ah1EyUvq9aQ17g8Oy70q42pU4i.oVI79eqiq4tcvFT/i1Mm',NULL,'eyJpdiI6IllHMUxqaVg4VEtiTjBlVUpGTVVwN2c9PSIsInZhbHVlIjoiWHVxOVZkT3BUQXN1TTgwYjVMczFHeko2RXRsRmdOcm1iRmVZN3daamN1OD0iLCJtYWMiOiI5MTQ4Y2I3N2JiODMxZGQ1MGJkMjU3MmJjMTQzMjQwOTMwNzY0ZTc5OGI4MzllYTZ',NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:21:02','2025-11-06 05:21:02'),(3,'Delivery Man','deliveryman@wemaxit.com',NULL,'$2y$10$/E4iuNJY3x92Oy0.4ypK7uWbCvscUqxWWHIwkmnAtVDx6pErz105y','01912938004',NULL,NULL,NULL,NULL,1,3,3,NULL,'Mirpur-2,Dhaka',NULL,NULL,NULL,NULL,7000.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-06-29 11:29:43','2025-06-29 11:29:43'),(4,'Merchant','merchant@wemaxdevs.com',NULL,'$2y$10$XhqKQRBjsRn0zeao3VhKOu/EMfKW8d5oVy1yqctOOgYMQn4ZWSiCa','01912938003',NULL,NULL,NULL,NULL,4,2,2,NULL,'Mirpur-2,Dhaka',NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-06-29 11:29:44','2025-06-29 11:29:44'),(5,'Dennis Carroll','hupazef@mailinator.com',NULL,'$2y$10$t.rI6vTeUl1CepxBssA3wOEqC0wtU9jh/iFXdaWitQTOe4XYpHdLW','256702568978',NULL,NULL,NULL,NULL,3,2,NULL,NULL,'Irure aliquid porro',NULL,NULL,NULL,NULL,0.00,NULL,'dXw4xjzjtffFDb30duHn_h:APA91bGnizMRdXW-fQT1Q6ZKjImEtOtHtJQPU0bj5eDbLjHKJTGy6zXYKLqRIe_tSklfaKhCiZY0KhPzOqGUPaSvIOlUzk5nVxbHm0Kaa2y8H62QkMSlz5E',1,1,NULL,NULL,NULL,'2025-07-05 11:38:06','2025-07-29 06:25:42'),(6,'Raymond Mccray',NULL,NULL,'$2y$10$NdttmZ16zrgAS9ZWqzhP4OjNrUn5GXTheFHq.sWOzucWng2SZKndu','2567056567989',NULL,NULL,NULL,NULL,2,2,NULL,NULL,NULL,NULL,'[]',NULL,72736,0.00,NULL,NULL,1,0,NULL,NULL,NULL,'2025-07-05 11:42:23','2025-07-05 11:42:23'),(7,'System Admin','admin@example.com',NULL,'$2y$10$GXuAR3zspMhkOpHIf.7SWOFcrMf7aVDt2qAymncIz/OdsXRwPcrTG',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:32:59','2025-11-06 05:40:09'),(8,'Branch Manager','manager@example.com',NULL,'$2y$10$PCUFT14GuXtY379qW4NULu.LOwmOYxWfa2GhwMlFEq7IoQPPYU6nC',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:32:59','2025-11-06 05:40:09'),(9,'Branch Worker','worker@example.com',NULL,'$2y$10$ztsvFtoGaHbeneyuGpb5Lub1QMPyPVu.OeyNEhR0VAT//Cwp73c3G',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:32:59','2025-11-06 05:40:09'),(10,'Client Contact','client@example.com',NULL,'$2y$10$ljDAwNJ.iwdOkacxAEZoD.sJElt8ncugU0UiXQ1GcdwJG9BM6.LkO',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-06 05:32:59','2025-11-06 05:40:09'),(533,'Imelda Collier','doug14@example.com','2025-11-06 15:34:27','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'2UgIpxpwej','2025-11-06 15:34:27','2025-11-06 15:34:27'),(534,'Chelsie Kerluke','lela.okeefe@example.net','2025-11-06 15:34:27','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'uFzNTBjuBZ','2025-11-06 15:34:27','2025-11-06 15:34:27'),(535,'Prof. Zechariah Herman','durgan.jan@example.com','2025-11-06 15:34:27','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'ZrV8Nr9WC2','2025-11-06 15:34:27','2025-11-06 15:34:27'),(536,'Bud Ortiz','arno.bogisich@example.net','2025-11-06 15:34:28','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'AtKvyXXW70','2025-11-06 15:34:28','2025-11-06 15:34:28'),(537,'Miss Tara Adams','nbogisich@example.com','2025-11-06 15:34:28','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'cqXkCKNjRZ','2025-11-06 15:34:28','2025-11-06 15:34:28'),(538,'Nikko Mayert','kevin.terry@example.org','2025-11-06 15:34:28','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'rnLBenXDjJ','2025-11-06 15:34:28','2025-11-06 15:34:28'),(539,'Tyrese Steuber','nova.toy@example.com','2025-11-06 15:34:28','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'mTmI3Bp5nJ','2025-11-06 15:34:28','2025-11-06 15:34:28'),(540,'Willard Emard','green.megane@example.com','2025-11-06 15:34:28','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'vDVTbUr1wU','2025-11-06 15:34:28','2025-11-06 15:34:28'),(542,'Lorenzo Borer IV','bergstrom.okey@example.net','2025-11-06 15:34:28','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'Fgcx0gW2Ac','2025-11-06 15:34:28','2025-11-06 15:34:28'),(543,'Lavada Wintheiser V','jayce.nolan@example.org','2025-11-06 15:34:29','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'ftesRd5XoJ','2025-11-06 15:34:29','2025-11-06 15:34:29'),(544,'Jaqueline Robel','zvonrueden@example.org','2025-11-06 15:34:29','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'RlHXImhpYE','2025-11-06 15:34:29','2025-11-06 15:34:29'),(545,'Daphney Hand IV','scottie.halvorson@example.org','2025-11-06 15:34:29','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'nI1ZdJFBvd','2025-11-06 15:34:29','2025-11-06 15:34:29'),(546,'Prof. Liliane Barton','vrowe@example.com','2025-11-06 15:34:29','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,1,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'tQQSlZtDou','2025-11-06 15:34:29','2025-11-06 15:34:29'),(547,'Dustin White','rico.towne@example.net','2025-11-06 15:34:29','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'Ncb0QD7UAe','2025-11-06 15:34:29','2025-11-06 15:34:29'),(548,'Dr. Samantha Thiel','zulauf.brandt@example.com','2025-11-06 15:34:29','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'ujHLygyYkb','2025-11-06 15:34:29','2025-11-06 15:34:29'),(549,'Moses Collins','adolphus.greenholt@example.org','2025-11-06 15:34:29','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'txrMehLRVe','2025-11-06 15:34:29','2025-11-06 15:34:29'),(550,'Prof. Hipolito Collier','feil.lauryn@example.com','2025-11-06 15:34:29','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',NULL,NULL,NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,'OcLpR1qeLE','2025-11-06 15:34:29','2025-11-06 15:34:29'),(629,'Central Hub Ops Lead','branch.ops+hub-001@baraka.sanaa.co',NULL,'$2y$10$NojSUHLioL9vIf3DthUnyupmRFbAxDmb.8RZXnl0wuzrxs/pKgSyO',NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,32,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(630,'Regional Branch Ops Lead','branch.ops+reg-001@baraka.sanaa.co',NULL,'$2y$10$7ZXAn3Wn9DoOEWmH1BATEuC4lf5d9eSkr0RT6NIUh1Lb5OOFpLkq6',NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,32,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(631,'Local Branch Ops Lead','branch.ops+loc-001@baraka.sanaa.co',NULL,'$2y$10$4CmV.Vs.G3VMDBTrJvK6SOwcJJwWPuWgV71UUpU7.b5dkHkhraoPy',NULL,NULL,NULL,NULL,NULL,NULL,4,NULL,NULL,NULL,32,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 10:34:38','2025-11-07 10:34:38'),(632,'Layla Al-Dosari','layla.al.dosari.HUB-001.1@baraka.sanaa.co',NULL,'$2y$10$CSTDI8xW8J0fySGnY/Otne3rTgEFq4SyjXCGJ1X/TGFFbW8hgjWHe','+25671000001','eyJpdiI6ImYwSXR2N2NZTkNGdENQSk9hWm1Damc9PSIsInZhbHVlIjoiVHNuTVZZQkwrSDlPZFQ3VmFHR0JjYUtmeWh1NVJWbUtuTGR5ZURTNU1xVT0iLCJtYWMiOiJhZjQ1MDM2NTUyOTUzYTU4MDIxNGI5ZmNiMGMwNDEyMTNkN2QzMGYwY2JjZjJiYjZ',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:29:07','2025-11-07 12:29:07'),(633,'Yousef Al-Shammari','yousef.al.shammari.HUB-001.2@baraka.sanaa.co',NULL,'$2y$10$44PrEB38HRaJeEcaqffnlOJVFt7MyVbjCNcxC50RIyQna/92qa04u','+25671000002','eyJpdiI6IjFDN3FDcjk4S2NpdHZpbzZEK2t1U2c9PSIsInZhbHVlIjoic09Ud1BnaGpiWjZIY0NqSVBuQU5mMEdGc2hmZk5HenJjTFRnMjdrcXpDbz0iLCJtYWMiOiJhNzk5OWY5OGNkYmEyOWQxMjdkODEwMjRkY2Q4NzY1OWQ3Yjc1OWQzNjkxMGJlMDI',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(634,'Nora Al-Ghamdi','nora.al.ghamdi.HUB-001.3@baraka.sanaa.co',NULL,'$2y$10$hPuVGwoL3hS48Jhfs1bDNeV.6EFV6zL/wLdnnxleAkBWGQhIZkAK6','+25671000003','eyJpdiI6ImMwS1gyVFpBWnRJM3hBVEZYd2JmSEE9PSIsInZhbHVlIjoiMnNrbHdwd2Y2L1JDNzcraCtFWDkzazN0bm8vVlZJVHljYkswMzhuZUluVT0iLCJtYWMiOiI0NDEzYjRkZTY2MTgzYjFiMWExMDY4NmRlMWNjZWZiZWI4YTU1NTc1NDQyMjZjZWI',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(635,'Abdullah Al-Mutairi','abdullah.al.mutairi.HUB-001.4@baraka.sanaa.co',NULL,'$2y$10$b0e.MaPZRQ9iYTZo9i0BcO.v.cKDd78HbE.tNnshpcoWF2ibOvcE6','+25671000004','eyJpdiI6IlZiRkdrV2lpU3hRUkZYZ2lvcTdNTHc9PSIsInZhbHVlIjoiUzZyeUI5ZVdWczRpTTFZTVRpUkZ1WmpzZkN6MXAyRFJNU3JOOVBNVVk4MD0iLCJtYWMiOiIwNTA4NjAyMjYxNTdjZjI2YmVmM2QyYjI5MjM2ZGI1NTYzNDg2YmJiYWE2ZmEwNWZ',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(636,'Hala Al-Subaie','hala.al.subaie.HUB-001.5@baraka.sanaa.co',NULL,'$2y$10$1uqMg84iUMBZcCsIzCuEWOizzbMHeltok1kS0yndeQp4Eo5etTbIq','+25671000005','eyJpdiI6InFCbFlQVEZaL01NS1lweFhIMTRBckE9PSIsInZhbHVlIjoiUGZDc1Vkd0ZhY2Q4ZnlJQ0g5M0hsVEU2eGd6UGNrK3Y5N3g1Y285QWlkST0iLCJtYWMiOiI5MTYxODZhYTY2ZTZhNGU3MmY0NTBiODE3YmQ3M2FmYTFmZDQ2ODU3MjdhODE5YzI',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(637,'Saad Al-Enezi','saad.al.enezi.HUB-001.6@baraka.sanaa.co',NULL,'$2y$10$aXwNH8c8TkZ9W3ZOkzaPmeilkHf6xyUO/kfcmAPI57fXEYRzAVZUW','+25671000006','eyJpdiI6IkRPVEYwTVVzZUc0eFYyTjQ3MFQ3YWc9PSIsInZhbHVlIjoiTnRNYXlHN2JuNGI2V1lLSzVZZnM1WWJhWW9vVmRBeis2bTZtbXE5Q1VyUT0iLCJtYWMiOiI2YWEwZjkwM2UwY2RjNTY0MTgxZGI3ZjBmMzJkODBiZjA0ZjU4ODYyNzdhNzk0MjB',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(638,'Rania Al-Asmari','rania.al.asmari.HUB-001.7@baraka.sanaa.co',NULL,'$2y$10$zRaU/Q8E0afNYDKZoX7UL.Nqg6EPDuAma8DFBHnUgTtphUoJqNsqa','+25671000007','eyJpdiI6IndFL0N1RVdTUncrU1FSZTZ0MDgvalE9PSIsInZhbHVlIjoiZktTVEM5Q3lMZkxVeHZUQXRPMk5wL3BqOTNJTExaU0p5RERGeTExemhodz0iLCJtYWMiOiIyMTQxZGFjM2MwNDRjNDFjNzRmYWI2NzBjODU4OTBjNjEyYjBjNzk4ZjUzNDM1MGU',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:38','2025-11-07 12:30:38'),(639,'Fahad Al-Shahrani','fahad.al.shahrani.HUB-001.8@baraka.sanaa.co',NULL,'$2y$10$rOBLIH4RUJwRzxpU9lBAGe7q/.ALoRh8IYItF8VtynMSypi3.TIv.','+25671000008','eyJpdiI6Ikt3YXo4WFhaY2lzeVpyZ0x4M2p2ZWc9PSIsInZhbHVlIjoiRjhYSllIb01TNHRDQ09pTE01ZkFEVjlqZitSUFJtYk85MzdWTkNWd0FlZz0iLCJtYWMiOiJjZWM3YjM0ZTJjNDI5ZGQzZTQ4NWUyOWZhYzQzOTcxMjdlNjY4OWU0MTljNWYxZDc',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(640,'Maha Al-Balawi','maha.al.balawi.HUB-001.9@baraka.sanaa.co',NULL,'$2y$10$sVzGnhoQeh.jUvf6OyF1p.wyUlStWIn0dflPwPuEAGB3kRaLgyCpm','+25671000009','eyJpdiI6IkU1bUdDVUJFTVhGZlp6QzcvUldWQlE9PSIsInZhbHVlIjoiNFFleXVZL2g1dHhMeFZwRkdGY3J1bFRFVlRocVoyR1h4WUpmNDBqNGkrST0iLCJtYWMiOiIzZTgwNzYyM2ZmNmRmYTRjYjZhNzExMWJkOGY0Y2Y5NjM3OWE0MjQxN2VkZTA0MGR',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(641,'Sultan Al-Dawsari','sultan.al.dawsari.HUB-001.10@baraka.sanaa.co',NULL,'$2y$10$ChJxpMlm4KeQZtgNiFMujeb3zUCooqFzzr/MaQpuxSC.6BJrSZq.K','+25671000010','eyJpdiI6IjBOYktWWTBDa2FpcUpqNkpIc0kyRGc9PSIsInZhbHVlIjoiM0tUaWFxTnhCYlM3MDhqU2dUWklvVXNzeEwrbkNCVGtNc2tLbjErVyt3TT0iLCJtYWMiOiIwNzVmZGJhNTQ5ZTczNzAzNDliNmM4NjE3NDljZTBlNTU4Y2UxNzc3MmNjMjE2ZmZ',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(642,'Lina Al-Qadir','lina.al.qadir.HUB-001.11@baraka.sanaa.co',NULL,'$2y$10$JB.Cxmuj2/4Ec.XnFFnB3eXM3FrPtyIIQ/HniDdKB9i5NNmIwYkB2','+25671000011','eyJpdiI6Ik4vWFdpdEI2Rll2MDZ5c2QydmRCTVE9PSIsInZhbHVlIjoiZWZiWTJNd2NFMGxSU2l4eGpubUpyTDhCOGl3YmpwRGNDZjZFSHVTM2hGZz0iLCJtYWMiOiIyNzdiMzliZjU4ZTg4MDBmZGFlNmQzNGNkYjk5YWM4MjM4MDdhZTE5MzMxYzZkOTZ',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(643,'Faisal Al-Zahrani','faisal.al.zahrani.HUB-001.12@baraka.sanaa.co',NULL,'$2y$10$dous4cCqMw5yYIjh2czjIuDQK9Xbw4liNjhSGwjS1cXboReArUoqG','+25671000012','eyJpdiI6IllHL0xBbjJiTDZtNUxzVGlFZTlGdWc9PSIsInZhbHVlIjoiQk16WDRuVzlaRmwrU1FKbUlrdVRuU0NCcnJ2OHFsVERKMTVOMWx6SzhYZz0iLCJtYWMiOiJhN2M2NDJkNzNmODk5ZTdjYzVkMmY3OGIwNjg0YjY1YjJjYzZkZGVmODUyYjFlODk',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(644,'Aisha Al-Mazroa','aisha.al.mazroa.HUB-001.13@baraka.sanaa.co',NULL,'$2y$10$x1QaYd6XVyx9SqMFkuGxm.Rp/EvA6I5uH0lxE13JymOjXZ9IUBLcu','+25671000013','eyJpdiI6IjE4aVVyTE03UnpZcFEwanBSa3kydGc9PSIsInZhbHVlIjoiUlpNc1M5ZWtITkNwWEtWTkpVeFdwRWk3MklKZHJscm5PVzJRbVREV3Ezbz0iLCJtYWMiOiJjNDAyN2E5YmEyYzMzNzFjYzkxMjI0N2U1MTljYjUzZDg1OWNkMDIxYjgzNDE5MmI',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(645,'Bandar Al-Harbi','bandar.al.harbi.HUB-001.14@baraka.sanaa.co',NULL,'$2y$10$5x1kWVlcCmbvdNwiWMLL2uNgO0PwmhbApY8voV1NpdJNOVDZp4Toq','+25671000014','eyJpdiI6IkZBaElQN2c4VEZLc0Mrai9qZjVvQnc9PSIsInZhbHVlIjoiQTFvSHJzMVJRUjRoc2F1a00yL25raEQ3V2toT3k5V1BPdXl0cnY2cDBmTT0iLCJtYWMiOiJjMTJhZDU3MTZiMjRjYTNlZmJkZWYzOGFmNjlhMGNhNjVkM2JlNTk5MzM3OTViY2R',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:39','2025-11-07 12:30:39'),(646,'Nouf Al-Otaibi','nouf.al.otaibi.REG-001.15@baraka.sanaa.co',NULL,'$2y$10$VBZszXDvn.ugelfBwiYhouUuZSrq89jeCx8gMY3OEqbf.04278quS','+25671000015','eyJpdiI6InJYYjhCTmtrOEVNZVlFL3NDV2NUVHc9PSIsInZhbHVlIjoiMmhLMDUzWFA2d2hHYk5aYjJ5eHJmcFVac3pWbGt0UUdvaDdDYlUyUnhvbz0iLCJtYWMiOiIxODZjZGE5ZjgxYjU1MDFmYjA4OTZkNTc5N2I3NDA3NzYwNmE2ZjVkYWQwNDY5Zjk',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(647,'Turki Al-Rashid','turki.al.rashid.REG-001.16@baraka.sanaa.co',NULL,'$2y$10$bixqym5bkPFY3hU.zbh4yOIMB4Kzq4fQ6NoJRgrF50rmKbrW0DgOS','+25671000016','eyJpdiI6IlIzZ0VxcmttTmQ5OE11WUlTUGh3a3c9PSIsInZhbHVlIjoiRHdsajh0clFkbE42emRaSHVCQm1NaGMyZVk5MGt2ek5ZaTNPRmNvY05tQT0iLCJtYWMiOiJjODYyM2IzMzZkN2ZiZDYyNGUyYmJlNzU2NDMwNDIwNWVjZTQ4ODQ3MjkwYjQ5OGU',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(648,'Joud Al-Ghamdi','joud.al.ghamdi.REG-001.17@baraka.sanaa.co',NULL,'$2y$10$rR.nKiF1nJTjBmh3BSGXaOQeZyK0NGMkY4B1SbYcp3punICLCO6Ki','+25671000017','eyJpdiI6IkRRdC9rbDhmK0hHeUVYQVBiOFdCaXc9PSIsInZhbHVlIjoiUVoyMy9SVnBrNkY5T3JxTnlGcXpsckpqc21LVGFUWEg2MFp1dFQ1VTJJaz0iLCJtYWMiOiI1NGMxMzUzYTNlYTMxYjVlNjJlZjgzNjIxMWM4ZjZjZDU3ZjIzMTlmYzk5NWE4N2Q',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(649,'Nasser Al-Qahtani','nasser.al.qahtani.REG-001.18@baraka.sanaa.co',NULL,'$2y$10$pBDPMtir/Kky81bsP/Ryh.NwQQJ2XqKTkmIZ/cfQEVHtftWyBY7wi','+25671000018','eyJpdiI6IjB0bFoxUE44NUYyZ1JYUEp1QkdMK3c9PSIsInZhbHVlIjoibklEdVZXQVNYTTdsY0tLSjR0K09XWmtqRHpYSmNZK1VjWlpQeFIrZURrWT0iLCJtYWMiOiJjNjIwYzZjMTcxNTYzODA2YjQwOTBiNWJlOWVkODBhNTg5ODJiMWY0YzBiMTUyNGE',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(650,'Salma Al-Malki','salma.al.malki.REG-001.19@baraka.sanaa.co',NULL,'$2y$10$jYTMpOy0ZkLZR9x.gOc3C.HP2uL7Ee5aet69oG2tjvFvVXdMJcTmO','+25671000019','eyJpdiI6IlF1TStjNzhVZ1F1a1RHVm1UdkR1ZHc9PSIsInZhbHVlIjoiR2Z3b3AxTEVFd2pORG1Ia0puMVJyK2tVN2tIc001VjFGUkNQTGtPSE5ETT0iLCJtYWMiOiIxMmJlMTU0Y2YyZGQ4OTAyYmNkYjFhZWY4ZWJjZDE3ZTcxNWVhMjAwYWE2NzA1MTl',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(651,'Waleed Al-Shamrani','waleed.al.shamrani.REG-001.20@baraka.sanaa.co',NULL,'$2y$10$JW0shCnHp45OVeaoGbE/6O93itx/3BCoSvenlck2TmxDx5kL2Xb8e','+25671000020','eyJpdiI6IktramtFK1pCSkxxTklMRzRWUzZPRnc9PSIsInZhbHVlIjoiOGtLSWlucFhQQWJJa3FFa2F0U1k0WS9XSllCSW5saWUyOExWalRvelRPND0iLCJtYWMiOiIyMzdlMTg3YzI3MzE0NjhlYTM3YjM3YzAzMDM5YWYyZThjMjIzYTNmMGMzNTY5MDV',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(652,'Hind Al-Zahrani','hind.al.zahrani.REG-001.21@baraka.sanaa.co',NULL,'$2y$10$1gZmVusIzSZyeYbpEm7nu.9/8yrdS7lvF1et/hCJGA1DLBB5oPsE6','+25671000021','eyJpdiI6IkdrMys1TlI1cm1lM1A5RnYwT01talE9PSIsInZhbHVlIjoiaVkvRmlUcU1jaklmaWFBTk45SFYxWklEM0lpdnBkQjBWRUlTTXpUWk90WT0iLCJtYWMiOiJjZDY3ZmZhMWEzN2VmNTc1ODcyNzhjMjUzMTJhYjY1ZTQ1MzZmMmFmNGYxMmZiYmJ',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(653,'Majed Al-Anazi','majed.al.anazi.REG-001.22@baraka.sanaa.co',NULL,'$2y$10$6.QTnM9sUq8tWKFq4t3TwOxmhoxWgAJ28jaitXsWhwnQVdrAkbBN2','+25671000022','eyJpdiI6ImxBdm1lMENPRUFKSkwwMDdibnpZU3c9PSIsInZhbHVlIjoiRTRzdGxERzdKRERCMGlBOFROWmdTWjZKcFAyNEpPZ1BIajA2THR0THYzZz0iLCJtYWMiOiJlODU4NmQ4OWExNTllMWJjM2E0MDM2ZmVjNGIxYTY1YjA0MzJmZjQ4Y2QwZDMzMWU',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:40','2025-11-07 12:30:40'),(654,'Reem Al-Saud','reem.al.saud.REG-001.23@baraka.sanaa.co',NULL,'$2y$10$YeX0qxJZYbqAtxgDN..kD.5/j71K13yJPJwkuH2GmvQaz5le8s4RO','+25671000023','eyJpdiI6InZTYXE1UGRwVGUvSUc2Nk5sMWZnYVE9PSIsInZhbHVlIjoiMG1JcHRQTTVKaGNiemNnUXNPZDNPS0pxaThybXVvUXBIdGlEcHJnUDNEND0iLCJtYWMiOiI3NzI2ZTFjOWQwMTEzM2U1ZTIyNTY0ZmVhY2M4ZjQ5ZDM0OGFhZTlmYzYxYjVlMzk',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(655,'Talal Al-Ghamdi','talal.al.ghamdi.LOC-001.24@baraka.sanaa.co',NULL,'$2y$10$nrDB1VgnMzK0jNeWdAmeT.RgeaNsnDxRXulgFJZ4leuRQXXf0KhKO','+25671000024','eyJpdiI6IjEzRG10VmpKMmdxbE95TS9uWlEzV2c9PSIsInZhbHVlIjoiTkxVNkptclNKRWFjd3IxTUM3Rk5CeTA1ZWJ3RDhDTm1ibmYyMmZtelZpWT0iLCJtYWMiOiJkMWFjNmI3ZjhiODJmMTk0MjFlOGI3OWQ3ODExMDFhZmVjMTg1Y2Q0YWQyMDZmNzc',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(656,'Lama Al-Harbi','lama.al.harbi.LOC-001.25@baraka.sanaa.co',NULL,'$2y$10$cSFSGaWYDbgtaurtSRZeTexbi4RmHYENwqlswhugXxvOvkofS7.yO','+25671000025','eyJpdiI6InFaYXlXcDdYYWg2dTBDUlhDNTZrMnc9PSIsInZhbHVlIjoiZkFYU01SYzZRL215OVdxMllJZXhnNVRmN1U3bnZDdFd5VjUxUytkcVNZQT0iLCJtYWMiOiI2MzU2NjE1NGJkY2Q0NmRhOTJjNDM0NGNhNTA4MTM0NWJkYTlmN2FmNzU5OTlkNTc',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(657,'Saud Al-Dosari','saud.al.dosari.LOC-001.26@baraka.sanaa.co',NULL,'$2y$10$0KPFkvPswSn8MdmvQOmEiOcIVgTn.lE8gbYt0xX0EOP4GTTBk8HV.','+25671000026','eyJpdiI6ImJ1WHhQZXl4VUR5VEdHOHZsNWl1Nmc9PSIsInZhbHVlIjoiTGZNOHh6cTJRbmtsOVBpUEJKWUFKeThDNWFRMkg1cWlaSzNKSVBNV0dGOD0iLCJtYWMiOiJjNDg2MzZjYmI2OTYzNWQ1NjNjZTFjNzYyZDYwMWFkYjczMDk3OWM5ZTY1NGVkNDE',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(658,'Jana Al-Mutairi','jana.al.mutairi.LOC-001.27@baraka.sanaa.co',NULL,'$2y$10$K6JN5uLl.TI7kPUB.EGoougFcV0jwEhpghAbzCLO5f21eFTY6NsZu','+25671000027','eyJpdiI6Inl6WHdHYlQ5S3BLcmsxejB6ZmR6SVE9PSIsInZhbHVlIjoib2N3SWRZM1JNRTB6K1RPYTZpclA0MzlvQWF3YlZldEQxbDZVdXV3enNvbz0iLCJtYWMiOiI5MTM1ODFmNDk2NDNlM2I5ODZlY2MwZGY5MWQ2MmUyM2FhZTJiNWI2N2YyMmQwYWY',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(659,'Mishal Al-Shammari','mishal.al.shammari.LOC-001.28@baraka.sanaa.co',NULL,'$2y$10$BSkYA.7lb7/iIU7OcN44quuHOdnkEWs86nK4vb0qU7hSpRXf.omAy','+25671000028','eyJpdiI6ImxRYXRuZ1YyZjFVREhKQUhiWGRSNGc9PSIsInZhbHVlIjoiaDNReDN2UnNMUS9QR2wySTZGbnZZZ2VZbzN0WHpHSVNGU1llYmZNNy9Dcz0iLCJtYWMiOiIzNjc3MGI3OGRmMmY2MzEwYjQwNGYwZjQ1ZmU2ZTc0NjljOTcyNDkxNDQ5OWUxMjg',NULL,NULL,NULL,NULL,3,NULL,NULL,NULL,NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-07 12:30:41','2025-11-07 12:30:41'),(660,'drtrt','reet@fdfdg.vb',NULL,'$2y$10$13hCwzMLdw.WGH9bRbuLG.4suKKBwwg2hzApJ/9T0pHFF7Gby0lIu',NULL,'eyJpdiI6IlFtR1RSSG1UUW1zNDNsUnp4eStvanc9PSIsInZhbHVlIjoiK2lEcVpDQ2VVNmlnRkVTWDN0RkxpSUc1UnRsV3J0UVhEWHBzY3J0Q1JpRT0iLCJtYWMiOiI4MjNkNjQ1OTJhMTg3YjcxZmNhZGVkMjE2OTYyYzQ3Mjc4ZjZjYTllOTgzYWY1ZTg',NULL,NULL,NULL,NULL,2,NULL,NULL,'ghjfh',NULL,NULL,NULL,NULL,0.00,NULL,NULL,1,1,NULL,NULL,NULL,'2025-11-08 20:41:43','2025-11-08 20:41:43');
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
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_task_activities`
--

LOCK TABLES `workflow_task_activities` WRITE;
/*!40000 ALTER TABLE `workflow_task_activities` DISABLE KEYS */;
INSERT INTO `workflow_task_activities` VALUES (1,1,2,'created','{\"title\": \"asrwerqw\", \"status\": \"pending\"}','2025-11-06 11:54:25'),(2,9,2,'created','{\"title\": \"rterwetr\", \"status\": \"in_progress\"}','2025-11-06 11:40:32'),(3,10,2,'created','{\"title\": \"Xsdsads\", \"status\": \"pending\"}','2025-11-06 11:52:05'),(4,12,2,'created','{\"title\": \"fretret\", \"status\": \"pending\"}','2025-11-06 12:03:16'),(5,13,2,'created','{\"title\": \"tarter\", \"status\": \"pending\"}','2025-11-06 12:04:32'),(6,14,2,'created','{\"title\": \"dgdgfg\", \"status\": \"in_progress\"}','2025-11-06 12:06:12'),(7,15,2,'created','{\"title\": \"dgdgfg\", \"status\": \"in_progress\"}','2025-11-06 12:06:42'),(8,18,2,'created','{\"title\": \"tryrty\", \"status\": \"pending\"}','2025-11-06 12:16:42'),(9,19,2,'created','{\"title\": \"test task\", \"status\": \"pending\"}','2025-11-06 12:18:08'),(10,14,2,'updated','{\"status\": \"awaiting_feedback\", \"changed\": [\"title\", \"description\", \"status\", \"priority\", \"assigned_to\", \"tracking_number\", \"due_at\", \"tags\"], \"previous_status\": \"in_progress\"}','2025-11-06 13:25:25');
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
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `workflow_tasks`
--

LOCK TABLES `workflow_tasks` WRITE;
/*!40000 ALTER TABLE `workflow_tasks` DISABLE KEYS */;
INSERT INTO `workflow_tasks` VALUES (1,'asrwerqw','weqwew','testing','medium',2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-06 10:54:25','[]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 10:54:25','2025-11-06 12:59:49'),(9,'rterwetr','ereret','in_progress','medium',2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-06 11:40:32','[\"sss\"]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 11:40:32','2025-11-06 11:40:32'),(10,'Xsdsads','saddasds','pending','medium',2,2,NULL,NULL,NULL,NULL,NULL,'dsdsa','2025-11-07 10:01:00',NULL,'2025-11-06 11:52:05','[]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 11:52:05','2025-11-06 11:52:05'),(11,'Test Task','Test Description','pending','medium',2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]','{}','{}','[]','[]','[]','{}','[]',NULL,'2025-11-06 11:53:42','2025-11-06 11:53:42'),(12,'fretret','tryrt6y','pending','medium',2,NULL,NULL,NULL,NULL,NULL,NULL,'tryrty','2025-11-08 21:02:00',NULL,'2025-11-06 12:03:16','[]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 12:03:16','2025-11-06 12:03:16'),(13,'tarter','esdrewser','pending','medium',2,2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-06 12:04:32','[]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 12:04:32','2025-11-06 12:04:32'),(14,'dgdgfg','fdgdgdfg','awaiting_feedback','medium',2,8,NULL,NULL,NULL,NULL,NULL,'retrt','2025-11-06 17:06:00',NULL,'2025-11-06 13:25:25','[]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 12:06:12','2025-11-06 13:25:25'),(15,'dgdgfg','fdgdgdfg','in_progress','medium',2,8,NULL,NULL,NULL,NULL,NULL,'retrt','2025-11-06 17:06:00',NULL,'2025-11-06 12:06:42','[]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 12:06:42','2025-11-06 12:06:42'),(16,'Test Task From Form','Test Description','pending','medium',2,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,'[]','{}','{}','[]','[]','[]','{}','[]',NULL,'2025-11-06 12:07:25','2025-11-06 12:07:25'),(17,'Test Task with Assignment','Test workflow task creation','in_progress','high',2,1,NULL,NULL,NULL,NULL,NULL,'TEST-001',NULL,NULL,NULL,'[]','{}','{}','[]','[]','[]','{}','[]',NULL,'2025-11-06 12:12:12','2025-11-06 12:12:12'),(18,'tryrty','tryrtytr','pending','medium',2,8,NULL,NULL,NULL,NULL,NULL,NULL,'2025-11-08 21:02:00',NULL,'2025-11-06 12:16:42','[]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 12:16:42','2025-11-06 12:16:42'),(19,'test task','test task','pending','medium',2,8,NULL,NULL,NULL,NULL,NULL,'fgh','2025-11-07 21:02:00',NULL,'2025-11-06 12:18:08','[]','{}','{}','[]','[]','[]',NULL,'[]',NULL,'2025-11-06 12:18:08','2025-11-06 12:18:08');
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

-- Dump completed on 2025-11-11  2:38:28
