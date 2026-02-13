/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `action1_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `action1_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `organization_id` varchar(255) NOT NULL,
  `api_key` text NOT NULL,
  `region` varchar(255) NOT NULL DEFAULT 'us',
  `sync_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sync_interval_hours` int(11) NOT NULL DEFAULT 24,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `action1_configs_client_id_organization_id_index` (`client_id`,`organization_id`),
  CONSTRAINT `action1_configs_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `action1_device_cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `action1_device_cache` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `action1_device_id` varchar(255) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `os_type` varchar(255) NOT NULL,
  `os_version` varchar(255) NOT NULL,
  `is_online` tinyint(1) NOT NULL DEFAULT 0,
  `assigned_user_email` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `last_seen_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `action1_device_cache_action1_device_id_unique` (`action1_device_id`),
  KEY `action1_device_cache_client_id_is_online_index` (`client_id`,`is_online`),
  KEY `action1_device_cache_action1_device_id_index` (`action1_device_id`),
  CONSTRAINT `action1_device_cache_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `action1_sync_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `action1_sync_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `sync_type` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL,
  `devices_synced` int(11) NOT NULL DEFAULT 0,
  `devices_failed` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `action1_sync_logs_client_id_sync_type_created_at_index` (`client_id`,`sync_type`,`created_at`),
  CONSTRAINT `action1_sync_logs_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `activity_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) DEFAULT NULL,
  `description` text NOT NULL,
  `subject_type` varchar(255) DEFAULT NULL,
  `subject_id` bigint(20) unsigned DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `causer_type` varchar(255) DEFAULT NULL,
  `causer_id` bigint(20) unsigned DEFAULT NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`properties`)),
  `batch_uuid` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `subject` (`subject_type`,`subject_id`),
  KEY `causer` (`causer_type`,`causer_id`),
  KEY `activity_log_log_name_index` (`log_name`),
  KEY `activity_log_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alert_delivery_log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_delivery_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alert_subscription_id` bigint(20) unsigned DEFAULT NULL,
  `alert_type_code` varchar(255) NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `event_class` varchar(255) DEFAULT NULL,
  `event_id` varchar(255) DEFAULT NULL,
  `recipient` varchar(255) NOT NULL,
  `recipient_name` varchar(255) DEFAULT NULL,
  `channel` enum('email','slack','sms','database') NOT NULL,
  `status` enum('queued','sent','delivered','failed','throttled') NOT NULL DEFAULT 'queued',
  `error_message` text DEFAULT NULL,
  `retry_count` int(11) NOT NULL DEFAULT 0,
  `subject` varchar(255) DEFAULT NULL,
  `body_preview` text DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload`)),
  `queued_at` timestamp NULL DEFAULT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_delivery_log_alert_subscription_id_index` (`alert_subscription_id`),
  KEY `alert_delivery_log_alert_type_code_index` (`alert_type_code`),
  KEY `alert_delivery_log_client_id_index` (`client_id`),
  KEY `alert_delivery_log_event_id_index` (`event_id`),
  KEY `alert_delivery_log_channel_index` (`channel`),
  KEY `alert_delivery_log_status_index` (`status`),
  KEY `alert_delivery_log_created_at_index` (`created_at`),
  KEY `alert_delivery_log_alert_type_code_client_id_created_at_index` (`alert_type_code`,`client_id`,`created_at`),
  CONSTRAINT `alert_delivery_log_alert_subscription_id_foreign` FOREIGN KEY (`alert_subscription_id`) REFERENCES `alert_subscriptions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alert_digest_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_digest_queue` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alert_subscription_id` bigint(20) unsigned NOT NULL,
  `alert_type_code` varchar(255) NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `scheduled_for` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_processed` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_digest_queue_alert_subscription_id_is_processed_index` (`alert_subscription_id`,`is_processed`),
  KEY `alert_digest_queue_scheduled_for_is_processed_index` (`scheduled_for`,`is_processed`),
  CONSTRAINT `alert_digest_queue_alert_subscription_id_foreign` FOREIGN KEY (`alert_subscription_id`) REFERENCES `alert_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alert_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `alert_type_id` bigint(20) unsigned NOT NULL,
  `client_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`client_ids`)),
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`channels`)),
  `frequency` enum('immediate','hourly','daily','weekly') NOT NULL DEFAULT 'immediate',
  `digest_time` time DEFAULT NULL,
  `digest_timezone` varchar(255) NOT NULL DEFAULT 'America/New_York',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `last_notified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `alert_subscriptions_user_id_index` (`user_id`),
  KEY `alert_subscriptions_role_index` (`role`),
  KEY `alert_subscriptions_alert_type_id_index` (`alert_type_id`),
  KEY `alert_subscriptions_is_active_index` (`is_active`),
  KEY `alert_subscriptions_user_id_alert_type_id_is_active_index` (`user_id`,`alert_type_id`,`is_active`),
  CONSTRAINT `alert_subscriptions_alert_type_id_foreign` FOREIGN KEY (`alert_type_id`) REFERENCES `alert_types` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alert_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alert_throttles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_throttles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alert_type_code` varchar(255) NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `throttle_key` varchar(255) NOT NULL,
  `count` int(11) NOT NULL DEFAULT 1,
  `window_start` timestamp NULL DEFAULT NULL,
  `window_end` timestamp NULL DEFAULT NULL,
  `suppressed_event_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suppressed_event_ids`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alert_throttles_throttle_key_unique` (`throttle_key`),
  KEY `alert_throttles_alert_type_code_index` (`alert_type_code`),
  KEY `alert_throttles_client_id_index` (`client_id`),
  KEY `alert_throttles_window_end_index` (`window_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `alert_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alert_types` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `severity` enum('info','warning','error','critical') NOT NULL DEFAULT 'warning',
  `default_channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`default_channels`)),
  `is_user_configurable` tinyint(1) NOT NULL DEFAULT 1,
  `email_template` varchar(255) DEFAULT NULL,
  `slack_template` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `alert_types_code_unique` (`code`),
  KEY `alert_types_category_index` (`category`),
  KEY `alert_types_severity_index` (`severity`),
  KEY `alert_types_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `api_rate_limit_tracking`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `api_rate_limit_tracking` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) NOT NULL,
  `attempts` int(11) NOT NULL DEFAULT 0,
  `reset_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_rate_limit_tracking_key_unique` (`key`),
  KEY `api_rate_limit_tracking_reset_at_index` (`reset_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `approval_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `approval_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `approvable_type` varchar(255) NOT NULL,
  `approvable_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `request_type` varchar(255) NOT NULL,
  `status` enum('pending','approved','rejected','signed') NOT NULL DEFAULT 'pending',
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approval_notes` text DEFAULT NULL,
  `signature_data` text DEFAULT NULL,
  `signature_method` varchar(255) DEFAULT NULL,
  `signed_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `approvable_index` (`approvable_type`,`approvable_id`),
  KEY `approval_requests_client_id_foreign` (`client_id`),
  KEY `approval_requests_status_created_at_index` (`status`,`created_at`),
  KEY `approval_requests_approved_by_foreign` (`approved_by`),
  CONSTRAINT `approval_requests_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `approval_requests_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `asset_staging_records`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `asset_staging_records` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `asset_id` bigint(20) unsigned NOT NULL,
  `source` enum('GoogleAdmin','Action1','Manual') NOT NULL,
  `proposed_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`proposed_changes`)),
  `status` enum('pending_review','approved','rejected') NOT NULL,
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `asset_staging_records_asset_id_foreign` (`asset_id`),
  KEY `asset_staging_records_status_created_at_index` (`status`,`created_at`),
  CONSTRAINT `asset_staging_records_asset_id_foreign` FOREIGN KEY (`asset_id`) REFERENCES `assets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `serial_number` varchar(255) NOT NULL,
  `hostname` varchar(255) DEFAULT NULL,
  `asset_type` enum('chromebook','windows','macos','linux') NOT NULL,
  `status` enum('active','inactive','retired') NOT NULL,
  `assigned_user_email` varchar(255) DEFAULT NULL,
  `source` enum('GoogleAdmin','Action1','Manual') NOT NULL,
  `procurement_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`procurement_metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `assets_client_id_serial_number_unique` (`client_id`,`serial_number`),
  KEY `assets_company_id_foreign` (`company_id`),
  KEY `assets_client_id_asset_type_index` (`client_id`,`asset_type`),
  KEY `assets_client_id_status_index` (`client_id`,`status`),
  KEY `assets_serial_number_index` (`serial_number`),
  KEY `assets_hostname_index` (`hostname`),
  KEY `assets_assigned_user_email_index` (`assigned_user_email`),
  CONSTRAINT `assets_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `assets_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` bigint(20) unsigned NOT NULL,
  `conversation_id` bigint(20) unsigned DEFAULT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_dir` varchar(255) NOT NULL,
  `file_size` int(10) unsigned NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `embedded` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `attachments_thread_id_index` (`thread_id`),
  KEY `attachments_conversation_id_index` (`conversation_id`),
  CONSTRAINT `attachments_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `attachments_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `billing_adjustments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `billing_adjustments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `adjustment_type` varchar(255) NOT NULL,
  `effective_date` date NOT NULL,
  `old_value` decimal(10,2) DEFAULT NULL,
  `new_value` decimal(10,2) DEFAULT NULL,
  `justification` text NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `applied_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `billing_adjustments_created_by_foreign` (`created_by`),
  KEY `billing_adjustments_approved_by_foreign` (`approved_by`),
  KEY `billing_adjustments_client_id_effective_date_index` (`client_id`,`effective_date`),
  KEY `billing_adjustments_status_index` (`status`),
  CONSTRAINT `billing_adjustments_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `billing_adjustments_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `billing_adjustments_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `channel_customer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `channel_customer` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel_id` bigint(20) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `channel_customer_channel_id_customer_id_unique` (`channel_id`,`customer_id`),
  KEY `channel_customer_customer_id_index` (`customer_id`),
  CONSTRAINT `channel_customer_channel_id_foreign` FOREIGN KEY (`channel_id`) REFERENCES `channels` (`id`) ON DELETE CASCADE,
  CONSTRAINT `channel_customer_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `channels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `channels_active_index` (`active`),
  KEY `channels_type_index` (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `circuit_breaker_states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `circuit_breaker_states` (
  `service` varchar(255) NOT NULL,
  `state` enum('closed','open','half_open') NOT NULL DEFAULT 'closed',
  `failure_count` int(11) NOT NULL DEFAULT 0,
  `last_failure_at` timestamp NULL DEFAULT NULL,
  `opened_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`service`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_asset_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_asset_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `allocation_type` varchar(255) NOT NULL,
  `count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_asset_counters_client_id_allocation_type_unique` (`client_id`,`allocation_type`),
  CONSTRAINT `client_asset_counters_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `total_time_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `service_category` varchar(50) NOT NULL DEFAULT 'included',
  `opened_at` timestamp NULL DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `linked_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `linked_via` varchar(50) NOT NULL DEFAULT 'email_match',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_client_conversation` (`client_id`,`conversation_id`),
  KEY `client_conversations_linked_by_user_id_foreign` (`linked_by_user_id`),
  KEY `client_conversations_conversation_id_index` (`conversation_id`),
  KEY `idx_client_opened` (`client_id`,`opened_at`),
  KEY `client_conversations_service_category_index` (`service_category`),
  CONSTRAINT `client_conversations_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_conversations_linked_by_user_id_foreign` FOREIGN KEY (`linked_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_credit_ledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_credit_ledger` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `amount` decimal(10,2) NOT NULL COMMENT 'Transaction amount in dollars (positive=credit, negative=debit)',
  `balance_before` decimal(10,2) NOT NULL DEFAULT 0.00,
  `transaction_type` enum('credit','debit') NOT NULL,
  `description` varchar(255) NOT NULL,
  `reference_type` varchar(255) DEFAULT NULL COMMENT 'e.g., Invoice, Payment, AssetAssignment',
  `reference_id` bigint(20) unsigned DEFAULT NULL,
  `balance_after` decimal(10,2) NOT NULL COMMENT 'Balance snapshot after transaction',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  PRIMARY KEY (`id`),
  KEY `client_credit_ledger_client_id_created_at_index` (`client_id`,`created_at`),
  KEY `client_credit_ledger_reference_type_reference_id_index` (`reference_type`,`reference_id`),
  KEY `client_credit_ledger_created_by_foreign` (`created_by`),
  CONSTRAINT `client_credit_ledger_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_credit_ledger_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_credits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_credits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `balance_cents` int(11) NOT NULL DEFAULT 0 COMMENT 'Credit balance in cents (integer for atomic operations)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_credits_client_id_unique` (`client_id`),
  KEY `client_credits_balance_cents_index` (`balance_cents`),
  CONSTRAINT `client_credits_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_service_metrics`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_service_metrics` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `period_year` smallint(6) NOT NULL,
  `period_month` tinyint(4) NOT NULL,
  `tickets_opened` int(10) unsigned NOT NULL DEFAULT 0,
  `tickets_closed` int(10) unsigned NOT NULL DEFAULT 0,
  `tickets_open_at_period_end` int(10) unsigned NOT NULL DEFAULT 0,
  `included_ticket_count` int(10) unsigned NOT NULL DEFAULT 0,
  `ad_hoc_ticket_count` int(10) unsigned NOT NULL DEFAULT 0,
  `emergency_ticket_count` int(10) unsigned NOT NULL DEFAULT 0,
  `avg_first_response_minutes` int(10) unsigned DEFAULT NULL,
  `avg_time_to_resolution_minutes` int(10) unsigned DEFAULT NULL,
  `avg_wait_time_unassigned_minutes` int(10) unsigned DEFAULT NULL,
  `max_wait_time_unassigned_minutes` int(10) unsigned DEFAULT NULL,
  `unique_technicians_assigned` int(10) unsigned NOT NULL DEFAULT 0,
  `total_assignments` int(10) unsigned NOT NULL DEFAULT 0,
  `total_status_changes` int(10) unsigned NOT NULL DEFAULT 0,
  `calculated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_client_period` (`client_id`,`period_year`,`period_month`),
  KEY `idx_period` (`period_year`,`period_month`),
  CONSTRAINT `client_service_metrics_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_software_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_software_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `software_product_id` bigint(20) unsigned NOT NULL,
  `billing_behavior` enum('included','passthrough','markup','fixed','direct') NOT NULL DEFAULT 'passthrough',
  `custom_price` decimal(10,2) DEFAULT NULL,
  `markup_percentage` decimal(5,2) DEFAULT NULL,
  `custom_tiers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`custom_tiers`)),
  `purchased_quantity` int(11) NOT NULL DEFAULT 0,
  `assigned_count` int(11) NOT NULL DEFAULT 0,
  `minimum_quantity` int(11) NOT NULL DEFAULT 0,
  `billing_template_id` bigint(20) unsigned DEFAULT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `renewal_date` date DEFAULT NULL,
  `status` enum('active','suspended','cancelled','pending') NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_client_product` (`client_id`,`software_product_id`),
  KEY `client_software_subscriptions_billing_template_id_foreign` (`billing_template_id`),
  KEY `client_software_subscriptions_client_id_index` (`client_id`),
  KEY `client_software_subscriptions_software_product_id_index` (`software_product_id`),
  KEY `client_software_subscriptions_status_index` (`status`),
  KEY `client_software_subscriptions_client_id_status_index` (`client_id`,`status`),
  CONSTRAINT `client_software_subscriptions_billing_template_id_foreign` FOREIGN KEY (`billing_template_id`) REFERENCES `cm_billing_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `client_software_subscriptions_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_software_subscriptions_software_product_id_foreign` FOREIGN KEY (`software_product_id`) REFERENCES `software_products` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_user_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_user_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `active_user_count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_user_counters_client_id_unique` (`client_id`),
  CONSTRAINT `client_user_counters_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `client_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `client_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `job_title` varchar(255) DEFAULT NULL,
  `manager_id` bigint(20) unsigned DEFAULT NULL,
  `is_approver` tinyint(1) NOT NULL DEFAULT 0,
  `approval_limit` decimal(15,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `client_users_email_unique` (`email`),
  KEY `client_users_company_id_foreign` (`company_id`),
  KEY `client_users_manager_id_foreign` (`manager_id`),
  CONSTRAINT `client_users_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `client_users_manager_id_foreign` FOREIGN KEY (`manager_id`) REFERENCES `client_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `clients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `clients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `tier` enum('Small Business','Non-Profit','Consumer') DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `company_id` int(11) DEFAULT NULL,
  `company_type` enum('business','non_profit','consumer') NOT NULL DEFAULT 'business',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `clients_status_index` (`status`),
  KEY `clients_company_type_index` (`company_type`),
  KEY `clients_email_index` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `companies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `companies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zip` varchar(255) DEFAULT NULL,
  `country` varchar(255) DEFAULT NULL,
  `primary_contact_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `sms_notifications_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `vat_number` varchar(255) DEFAULT NULL,
  `tax_id` varchar(255) DEFAULT NULL,
  `billing_address` text DEFAULT NULL,
  `billing_mode` varchar(255) NOT NULL DEFAULT 'manual',
  `pricing_tier` varchar(255) DEFAULT 'standard',
  `scenario` varchar(255) DEFAULT NULL,
  `margin_floor_percent` decimal(5,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_domains`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_domains` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `domain` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_domains_domain_unique` (`domain`),
  KEY `company_domains_company_id_foreign` (`company_id`),
  CONSTRAINT `company_domains_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `company_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('pending','approved','blocked') NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_user_user_id_company_id_unique` (`user_id`,`company_id`),
  KEY `company_user_company_id_foreign` (`company_id`),
  KEY `company_user_role_id_foreign` (`role_id`),
  CONSTRAINT `company_user_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `company_user_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `company_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversation_billing_metadata`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversation_billing_metadata` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_conversation_id` bigint(20) unsigned NOT NULL,
  `billing_category` enum('included','ad_hoc','warranty','project','emergency') NOT NULL DEFAULT 'included',
  `is_billable` tinyint(1) NOT NULL DEFAULT 0,
  `billable_time_minutes` int(10) unsigned NOT NULL DEFAULT 0,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `invoiced_at` timestamp NULL DEFAULT NULL,
  `billing_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_conversation_billing` (`client_conversation_id`),
  KEY `idx_billable_category` (`billing_category`,`is_billable`),
  KEY `conversation_billing_metadata_invoice_id_index` (`invoice_id`),
  CONSTRAINT `conversation_billing_metadata_client_conversation_id_foreign` FOREIGN KEY (`client_conversation_id`) REFERENCES `client_conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_billing_metadata_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `pib_invoices` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `number` int(10) unsigned NOT NULL,
  `threads_count` int(10) unsigned NOT NULL DEFAULT 0,
  `type` tinyint(3) unsigned NOT NULL,
  `folder_id` bigint(20) unsigned NOT NULL,
  `mailbox_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `state` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `subject` varchar(998) DEFAULT NULL,
  `customer_email` varchar(191) DEFAULT NULL,
  `cc` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`cc`)),
  `bcc` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`bcc`)),
  `preview` varchar(255) NOT NULL,
  `imported` tinyint(1) NOT NULL DEFAULT 0,
  `has_attachments` tinyint(1) NOT NULL DEFAULT 0,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_customer_id` bigint(20) unsigned DEFAULT NULL,
  `source_via` tinyint(3) unsigned NOT NULL,
  `source_type` tinyint(3) unsigned NOT NULL,
  `channel` tinyint(3) unsigned DEFAULT NULL,
  `closed_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `closed_at` timestamp NULL DEFAULT NULL,
  `follow_up_date` datetime DEFAULT NULL,
  `follow_up_reminded_at` datetime DEFAULT NULL,
  `user_updated_at` timestamp NULL DEFAULT NULL,
  `last_reply_at` timestamp NULL DEFAULT NULL,
  `last_reply_from` tinyint(3) unsigned DEFAULT NULL,
  `read_by_user` tinyint(1) NOT NULL DEFAULT 0,
  `meta` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversations_mailbox_id_number_unique` (`mailbox_id`,`number`),
  KEY `conversations_customer_id_foreign` (`customer_id`),
  KEY `conversations_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `conversations_closed_by_user_id_foreign` (`closed_by_user_id`),
  KEY `conversations_folder_id_status_index` (`folder_id`,`status`),
  KEY `conversations_mailbox_id_customer_id_index` (`mailbox_id`,`customer_id`),
  KEY `conversations_user_id_index` (`user_id`),
  KEY `conversations_state_index` (`state`),
  KEY `conversations_last_reply_at_index` (`last_reply_at`),
  CONSTRAINT `conversations_closed_by_user_id_foreign` FOREIGN KEY (`closed_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_folder_id_foreign` FOREIGN KEY (`folder_id`) REFERENCES `folders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_mailbox_id_foreign` FOREIGN KEY (`mailbox_id`) REFERENCES `mailboxes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_contact_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_contact_permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id` bigint(20) unsigned NOT NULL,
  `permission_type` varchar(255) NOT NULL,
  `scope` varchar(255) NOT NULL DEFAULT 'client',
  `allowed_actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`allowed_actions`)),
  `granted_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_contact_permissions_contact_id_permission_type_unique` (`contact_id`,`permission_type`),
  KEY `crm_contact_permissions_granted_by_foreign` (`granted_by`),
  CONSTRAINT `crm_contact_permissions_contact_id_foreign` FOREIGN KEY (`contact_id`) REFERENCES `crm_contacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `crm_contact_permissions_granted_by_foreign` FOREIGN KEY (`granted_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_contacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_contacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `first_name` varchar(255) NOT NULL,
  `last_name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `role` varchar(255) DEFAULT NULL,
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_contacts_client_id_index` (`client_id`),
  KEY `crm_contacts_email_index` (`email`),
  KEY `crm_contacts_is_primary_index` (`is_primary`),
  CONSTRAINT `crm_contacts_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_custom_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entity_type` varchar(255) NOT NULL,
  `entity_id` bigint(20) unsigned NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_value` text DEFAULT NULL,
  `field_type` enum('text','number','date','boolean','select','json') NOT NULL DEFAULT 'text',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `crm_custom_fields_entity_type_entity_id_index` (`entity_type`,`entity_id`),
  KEY `crm_custom_fields_field_name_index` (`field_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `crm_field_definitions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `crm_field_definitions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `entity_type` varchar(255) NOT NULL DEFAULT 'ModulesCrmModelsClient',
  `type` varchar(255) NOT NULL DEFAULT 'text',
  `options` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options`)),
  `order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `crm_field_definitions_key_unique` (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_channel`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_channel` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `channel` tinyint(3) unsigned NOT NULL,
  `channel_id` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_channel_channel_id_unique` (`channel_id`),
  KEY `customer_channel_customer_id_channel_index` (`customer_id`,`channel`),
  CONSTRAINT `customer_channel_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_customer_field`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_customer_field` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `customer_field_id` bigint(20) unsigned NOT NULL,
  `value` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `customer_customer_field_customer_id_customer_field_id_unique` (`customer_id`,`customer_field_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customer_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customer_fields` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(75) NOT NULL,
  `type` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `options` longtext DEFAULT NULL COMMENT ' ',
  `required` tinyint(1) NOT NULL DEFAULT 0,
  `display` tinyint(1) NOT NULL DEFAULT 1,
  `customer_can_view` tinyint(1) NOT NULL DEFAULT 0,
  `customer_can_edit` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(11) NOT NULL DEFAULT 1,
  `conv_list` tinyint(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `customer_fields_sort_order_index` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `customers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(50) DEFAULT NULL,
  `last_name` varchar(50) DEFAULT NULL,
  `photo_url` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `company` varchar(100) DEFAULT NULL,
  `company_id` bigint(20) unsigned DEFAULT NULL,
  `is_non_profit` tinyint(1) NOT NULL DEFAULT 0,
  `job_title` varchar(100) DEFAULT NULL,
  `photo_type` tinyint(3) unsigned DEFAULT NULL,
  `age` varchar(7) DEFAULT NULL,
  `gender` tinyint(3) unsigned DEFAULT NULL,
  `phones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`phones`)),
  `websites` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`websites`)),
  `social_profiles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`social_profiles`)),
  `chats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`chats`)),
  `background` text DEFAULT NULL,
  `address` text DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
  `zip` varchar(12) DEFAULT NULL,
  `country` varchar(2) DEFAULT NULL,
  `channel` tinyint(3) unsigned DEFAULT NULL,
  `channel_id` varchar(255) DEFAULT NULL,
  `meta` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `customers_first_name_last_name_index` (`first_name`,`last_name`),
  KEY `customers_channel_index` (`channel`),
  KEY `customers_company_id_index` (`company_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `emails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `emails` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `customer_id` bigint(20) unsigned NOT NULL,
  `email` varchar(191) NOT NULL,
  `type` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emails_email_unique` (`email`),
  KEY `emails_customer_id_index` (`customer_id`),
  KEY `emails_email_index` (`email`),
  CONSTRAINT `emails_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `folders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mailbox_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `name` varchar(100) DEFAULT NULL,
  `total_count` int(10) unsigned NOT NULL DEFAULT 0,
  `active_count` int(10) unsigned NOT NULL DEFAULT 0,
  `meta` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `folders_mailbox_id_type_index` (`mailbox_id`,`type`),
  KEY `folders_user_id_index` (`user_id`),
  CONSTRAINT `folders_mailbox_id_foreign` FOREIGN KEY (`mailbox_id`) REFERENCES `mailboxes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `folders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `google_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `google_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `domain` varchar(255) NOT NULL,
  `customer_id` varchar(255) NOT NULL,
  `admin_email` varchar(255) DEFAULT NULL,
  `org_unit_path` varchar(255) DEFAULT NULL,
  `service_account_json` text DEFAULT NULL,
  `credentials_updated_at` timestamp NULL DEFAULT NULL,
  `sync_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `sync_interval_hours` int(11) NOT NULL DEFAULT 24,
  `last_sync_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `google_configs_client_id_domain_index` (`client_id`,`domain`),
  CONSTRAINT `google_configs_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `google_push_channels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `google_push_channels` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `channel_id` varchar(255) NOT NULL,
  `resource_id` varchar(255) NOT NULL,
  `resource_type` varchar(255) NOT NULL,
  `expiration_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `google_push_channels_channel_id_unique` (`channel_id`),
  KEY `google_push_channels_client_id_resource_type_index` (`client_id`,`resource_type`),
  KEY `google_push_channels_expiration_at_index` (`expiration_at`),
  CONSTRAINT `google_push_channels_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `google_sync_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `google_sync_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `sync_type` varchar(255) NOT NULL,
  `status` enum('started','completed','failed') NOT NULL,
  `items_synced` int(11) NOT NULL DEFAULT 0,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `google_sync_logs_client_id_sync_type_created_at_index` (`client_id`,`sync_type`,`created_at`),
  KEY `google_sync_logs_status_started_at_index` (`status`,`started_at`),
  CONSTRAINT `google_sync_logs_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'unpaid',
  `due_date` date DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_invoice_number_unique` (`invoice_number`),
  KEY `invoices_company_id_status_index` (`company_id`,`status`),
  KEY `invoices_due_date_index` (`due_date`),
  CONSTRAINT `invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ltm_translations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ltm_translations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `status` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `locale` varchar(255) NOT NULL,
  `group` varchar(255) NOT NULL,
  `key` text NOT NULL,
  `value` text DEFAULT NULL,
  `hash` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ltm_translations_hash_unique` (`hash`),
  KEY `ltm_translations_status_index` (`status`),
  KEY `ltm_translations_locale_index` (`locale`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailbox_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailbox_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mailbox_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `access` tinyint(3) unsigned NOT NULL DEFAULT 10,
  `after_send` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailbox_user_mailbox_id_user_id_unique` (`mailbox_id`,`user_id`),
  KEY `mailbox_user_user_id_index` (`user_id`),
  CONSTRAINT `mailbox_user_mailbox_id_foreign` FOREIGN KEY (`mailbox_id`) REFERENCES `mailboxes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mailbox_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailboxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mailboxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(40) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `email` varchar(128) NOT NULL,
  `aliases` text DEFAULT NULL,
  `aliases_reply` tinyint(1) NOT NULL DEFAULT 0,
  `from_name` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `from_name_custom` varchar(128) DEFAULT NULL,
  `ticket_status` tinyint(3) unsigned NOT NULL DEFAULT 2,
  `ticket_assignee` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `template` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `signature` text DEFAULT NULL,
  `before_reply` text DEFAULT NULL,
  `out_method` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `out_server` varchar(255) DEFAULT NULL,
  `out_username` text DEFAULT NULL,
  `out_password` text DEFAULT NULL,
  `out_port` int(10) unsigned DEFAULT NULL,
  `out_encryption` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `in_server` varchar(255) DEFAULT NULL,
  `in_port` int(10) unsigned NOT NULL DEFAULT 143,
  `in_username` varchar(100) DEFAULT NULL,
  `in_password` text DEFAULT NULL,
  `in_protocol` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `in_encryption` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `in_validate_cert` tinyint(1) NOT NULL DEFAULT 1,
  `in_imap_folders` text DEFAULT NULL,
  `imap_sent_folder` text DEFAULT NULL,
  `auto_reply_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `auto_reply_subject` varchar(128) DEFAULT NULL,
  `auto_reply_message` text DEFAULT NULL,
  `auto_bcc` varchar(255) DEFAULT NULL,
  `office_hours_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `ratings` tinyint(1) NOT NULL DEFAULT 0,
  `ratings_placement` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `ratings_text` text DEFAULT NULL,
  `meta` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailboxes_email_unique` (`email`),
  KEY `mailboxes_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_batches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `batch_id` varchar(255) NOT NULL,
  `type` enum('discovery','verification','initial_sync','delta_sync') NOT NULL,
  `status` enum('pending','running','completed','failed','paused') NOT NULL DEFAULT 'pending',
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `estimated_completion` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `resumed_at` timestamp NULL DEFAULT NULL,
  `total_mailboxes` int(11) NOT NULL DEFAULT 0,
  `completed_mailboxes` int(11) NOT NULL DEFAULT 0,
  `failed_mailboxes` int(11) NOT NULL DEFAULT 0,
  `total_emails` int(11) NOT NULL DEFAULT 0,
  `migrated_emails` int(11) NOT NULL DEFAULT 0,
  `sync_from_date` timestamp NULL DEFAULT NULL,
  `sync_to_date` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_batches_batch_id_unique` (`batch_id`),
  KEY `migration_batches_project_id_type_index` (`project_id`,`type`),
  CONSTRAINT `migration_batches_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `migration_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_checkpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_checkpoints` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mapping_id` bigint(20) unsigned NOT NULL,
  `folder_name` varchar(255) NOT NULL,
  `last_uid` int(10) unsigned NOT NULL DEFAULT 0,
  `uidvalidity` int(10) unsigned NOT NULL,
  `uidnext` int(10) unsigned DEFAULT NULL,
  `total_messages` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mapping_folder` (`mapping_id`,`folder_name`),
  CONSTRAINT `migration_checkpoints_mapping_id_foreign` FOREIGN KEY (`mapping_id`) REFERENCES `migration_mappings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_job_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_job_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `mapping_id` bigint(20) unsigned DEFAULT NULL,
  `level` enum('debug','info','warning','error','critical') NOT NULL,
  `error_category` varchar(255) DEFAULT NULL,
  `event_type` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `email_message_id` varchar(255) DEFAULT NULL,
  `email_subject` varchar(255) DEFAULT NULL,
  `source_folder` varchar(255) DEFAULT NULL,
  `dest_folder` varchar(255) DEFAULT NULL,
  `email_date` timestamp NULL DEFAULT NULL,
  `duration_ms` int(11) DEFAULT NULL,
  `bytes_transferred` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `migration_job_logs_batch_id_foreign` (`batch_id`),
  KEY `migration_job_logs_mapping_id_foreign` (`mapping_id`),
  KEY `migration_job_logs_project_id_created_at_index` (`project_id`,`created_at`),
  KEY `migration_job_logs_error_category_index` (`error_category`),
  CONSTRAINT `migration_job_logs_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `migration_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `migration_job_logs_mapping_id_foreign` FOREIGN KEY (`mapping_id`) REFERENCES `migration_mappings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `migration_job_logs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `migration_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mailbox_id` bigint(20) unsigned NOT NULL,
  `phase` enum('pre-stage','delta') NOT NULL,
  `log_content` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `migration_logs_mailbox_id_phase_created_at_index` (`mailbox_id`,`phase`,`created_at`),
  CONSTRAINT `migration_logs_mailbox_id_foreign` FOREIGN KEY (`mailbox_id`) REFERENCES `migration_mailboxes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_mailboxes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_mailboxes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `email` varchar(255) NOT NULL,
  `source_size_bytes` bigint(20) DEFAULT NULL,
  `source_user` varchar(255) NOT NULL,
  `source_pass` text NOT NULL,
  `dest_user` varchar(255) NOT NULL,
  `dest_pass` text NOT NULL,
  `dest_email` varchar(255) DEFAULT NULL,
  `status` enum('pending','syncing','synced','failed') NOT NULL DEFAULT 'pending',
  `container_id` varchar(255) DEFAULT NULL,
  `last_error` text DEFAULT NULL,
  `last_run_stats` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`last_run_stats`)),
  `is_debug_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `preflight_warnings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preflight_warnings`)),
  `connection_status` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`connection_status`)),
  `source_folders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`source_folders`)),
  `folder_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`folder_map`)),
  `verification_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_results`)),
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `migration_mailboxes_project_id_status_index` (`project_id`,`status`),
  CONSTRAINT `migration_mailboxes_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `migration_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `batch_id` bigint(20) unsigned DEFAULT NULL,
  `source_email` varchar(255) NOT NULL,
  `source_user` varchar(255) NOT NULL,
  `source_password` text DEFAULT NULL,
  `source_folders` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`source_folders`)),
  `source_size_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `dest_email` varchar(255) NOT NULL,
  `dest_user` varchar(255) NOT NULL,
  `dest_password` text DEFAULT NULL,
  `dest_folder_mappings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`dest_folder_mappings`)),
  `action` enum('migrate','skip','verify_only') NOT NULL DEFAULT 'migrate',
  `is_discovered` tinyint(1) NOT NULL DEFAULT 0,
  `is_user_edited` tinyint(1) NOT NULL DEFAULT 0,
  `verification_passed` tinyint(1) DEFAULT NULL,
  `verification_errors` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_errors`)),
  `status` enum('pending','running','completed','failed','skipped','paused') NOT NULL DEFAULT 'pending',
  `last_synced_at` timestamp NULL DEFAULT NULL,
  `last_synced_uid` varchar(255) DEFAULT NULL,
  `total_emails_migrated` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_mappings_project_id_source_email_unique` (`project_id`,`source_email`),
  KEY `migration_mappings_batch_id_foreign` (`batch_id`),
  CONSTRAINT `migration_mappings_batch_id_foreign` FOREIGN KEY (`batch_id`) REFERENCES `migration_batches` (`id`) ON DELETE SET NULL,
  CONSTRAINT `migration_mappings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `migration_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `mailbox_id` bigint(20) unsigned NOT NULL,
  `folder_name` varchar(255) NOT NULL,
  `source_uid` int(10) unsigned NOT NULL,
  `message_id` varchar(255) DEFAULT NULL,
  `message_hash` char(64) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `migrated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_migration` (`mailbox_id`,`folder_name`,`source_uid`),
  CONSTRAINT `migration_messages_mailbox_id_foreign` FOREIGN KEY (`mailbox_id`) REFERENCES `migration_mailboxes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_profiles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `provider_type` varchar(255) NOT NULL DEFAULT 'imap',
  `host` varchar(255) NOT NULL,
  `port` int(11) NOT NULL DEFAULT 993,
  `encryption` varchar(255) NOT NULL DEFAULT 'ssl',
  `is_default_source` tinyint(1) NOT NULL DEFAULT 0,
  `is_default_destination` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `source_host` varchar(255) NOT NULL,
  `source_port` varchar(255) NOT NULL DEFAULT '993',
  `source_encryption` varchar(255) NOT NULL DEFAULT 'ssl',
  `dest_host` varchar(255) NOT NULL,
  `dest_port` varchar(255) NOT NULL DEFAULT '993',
  `dest_encryption` varchar(255) NOT NULL DEFAULT 'ssl',
  `destination_quota_bytes` bigint(20) DEFAULT NULL,
  `stage` enum('draft','discovering','discovery_complete','mapping','mapping_uploaded','verifying','verified','executing','delta_syncing','completed','failed','paused') NOT NULL DEFAULT 'draft',
  `source_manifest` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`source_manifest`)),
  `discovered_at` timestamp NULL DEFAULT NULL,
  `discovered_mailbox_count` int(11) NOT NULL DEFAULT 0,
  `mapping_file_path` varchar(255) DEFAULT NULL,
  `mapping_uploaded_at` timestamp NULL DEFAULT NULL,
  `verification_results` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`verification_results`)),
  `verified_at` timestamp NULL DEFAULT NULL,
  `preflight_report` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preflight_report`)),
  `last_sync_completed_at` timestamp NULL DEFAULT NULL,
  `is_delta_sync_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `total_emails_migrated` int(11) NOT NULL DEFAULT 0,
  `paused_at` timestamp NULL DEFAULT NULL,
  `pause_reason` varchar(255) DEFAULT NULL,
  `resume_at` timestamp NULL DEFAULT NULL,
  `scheduled_at` timestamp NULL DEFAULT NULL,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `folder_transformation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`folder_transformation_rules`)),
  `google_service_account_json` text DEFAULT NULL,
  `google_admin_email` varchar(255) DEFAULT NULL,
  `dns_trigger_record` varchar(255) DEFAULT NULL,
  `ticket_id` int(11) DEFAULT NULL,
  `public_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_projects_public_token_unique` (`public_token`),
  KEY `migration_projects_stage_paused_at_index` (`stage`,`paused_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `events` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`events`)),
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`channels`)),
  `quiet_hours_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `quiet_hours_start` time DEFAULT NULL,
  `quiet_hours_end` time DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `migration_subscriptions_project_id_user_id_unique` (`project_id`,`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migration_websocket_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migration_websocket_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `event_type` varchar(255) NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `delivered` tinyint(1) NOT NULL DEFAULT 0,
  `delivered_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `migration_websocket_events_project_id_delivered_created_at_index` (`project_id`,`delivered`,`created_at`),
  CONSTRAINT `migration_websocket_events_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `migration_projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `milestones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_type` varchar(255) DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `sequence_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('pending','in_progress','achieved','blocked','skipped') NOT NULL DEFAULT 'pending',
  `progress_percentage` decimal(5,2) NOT NULL DEFAULT 0.00,
  `target_date` timestamp NULL DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `notes` text DEFAULT NULL,
  `blockers` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `milestones_assigned_to_foreign` (`assigned_to`),
  KEY `milestones_project_type_project_id_index` (`project_type`,`project_id`),
  KEY `milestones_status_index` (`status`),
  KEY `milestones_sequence_order_index` (`sequence_order`),
  KEY `milestones_status_target_date_index` (`status`,`target_date`),
  CONSTRAINT `milestones_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `module_activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `module_activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `module_name` varchar(100) NOT NULL,
  `action` varchar(50) NOT NULL,
  `metadata` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `module_activity_logs_user_id_foreign` (`user_id`),
  KEY `module_activity_logs_module_name_action_index` (`module_name`,`action`),
  KEY `module_activity_logs_created_at_index` (`created_at`),
  KEY `module_activity_logs_module_name_index` (`module_name`),
  KEY `module_activity_logs_action_index` (`action`),
  CONSTRAINT `module_activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `alias` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `version` varchar(11) NOT NULL,
  `author` varchar(255) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT 0,
  `settings` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`settings`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `modules_alias_unique` (`alias`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notification_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notification_subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `alert_type` varchar(255) NOT NULL,
  `channels` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`channels`)),
  `frequency` varchar(255) NOT NULL DEFAULT 'immediate',
  `thresholds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`thresholds`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `notification_subscriptions_user_id_alert_type_unique` (`user_id`,`alert_type`),
  KEY `notification_subscriptions_alert_type_index` (`alert_type`),
  CONSTRAINT `notification_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notifications` (
  `id` char(36) NOT NULL,
  `type` varchar(255) NOT NULL,
  `notifiable_type` varchar(255) NOT NULL,
  `notifiable_id` bigint(20) unsigned NOT NULL,
  `data` text NOT NULL,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `notifications_notifiable_type_notifiable_id_index` (`notifiable_type`,`notifiable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `options` (
  `name` varchar(255) NOT NULL,
  `value` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payment_methods`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payment_methods` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `helcim_customer_id` varchar(255) DEFAULT NULL,
  `helcim_card_token` varchar(255) DEFAULT NULL,
  `last_four` varchar(4) DEFAULT NULL,
  `card_brand` varchar(50) DEFAULT NULL,
  `card_type` varchar(50) DEFAULT NULL,
  `expiry_month` varchar(2) DEFAULT NULL,
  `expiry_year` varchar(4) DEFAULT NULL,
  `cardholder_name` varchar(255) DEFAULT NULL,
  `billing_address` varchar(255) DEFAULT NULL,
  `billing_city` varchar(255) DEFAULT NULL,
  `billing_state` varchar(50) DEFAULT NULL,
  `billing_zip` varchar(20) DEFAULT NULL,
  `billing_country` varchar(2) NOT NULL DEFAULT 'US',
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `status` enum('active','expiring','expired','invalid','suspended') NOT NULL DEFAULT 'active',
  `verified_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payment_methods_company_id_is_default_index` (`company_id`,`is_default`),
  KEY `payment_methods_company_id_is_active_index` (`company_id`,`is_active`),
  KEY `payment_methods_status_index` (`status`),
  KEY `payment_methods_helcim_customer_id_index` (`helcim_customer_id`),
  CONSTRAINT `payment_methods_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_id` bigint(20) unsigned NOT NULL,
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `payment_method_id` bigint(20) unsigned DEFAULT NULL,
  `transaction_id` varchar(255) NOT NULL,
  `helcim_transaction_id` varchar(255) DEFAULT NULL,
  `idempotency_key` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `fee_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL,
  `currency` varchar(3) NOT NULL DEFAULT 'USD',
  `status` enum('pending','processing','successful','failed','declined','refunded','partially_refunded','cancelled','chargeback') NOT NULL DEFAULT 'pending',
  `dispute_status` enum('none','open','won','lost') NOT NULL DEFAULT 'none',
  `dispute_reason` varchar(255) DEFAULT NULL,
  `disputed_at` timestamp NULL DEFAULT NULL,
  `payment_type` varchar(50) DEFAULT NULL,
  `card_brand` varchar(50) DEFAULT NULL,
  `last_four` varchar(4) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `invoice_number` varchar(255) DEFAULT NULL,
  `transaction_type` enum('purchase','refund','void','preauth','capture') NOT NULL DEFAULT 'purchase',
  `response_payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`response_payload`)),
  `failure_reason` text DEFAULT NULL,
  `approval_code` varchar(255) DEFAULT NULL,
  `avs_response` varchar(255) DEFAULT NULL,
  `cvv_response` varchar(255) DEFAULT NULL,
  `reconciled` tinyint(1) NOT NULL DEFAULT 0,
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `reconciled_by` varchar(255) DEFAULT NULL,
  `refunded_from_payment_id` bigint(20) unsigned DEFAULT NULL,
  `refunded_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `initiated_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `processed_at` timestamp NULL DEFAULT NULL,
  `failed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `payments_transaction_id_unique` (`transaction_id`),
  UNIQUE KEY `payments_idempotency_key_unique` (`idempotency_key`),
  KEY `payments_payment_method_id_foreign` (`payment_method_id`),
  KEY `payments_refunded_from_payment_id_foreign` (`refunded_from_payment_id`),
  KEY `payments_initiated_by_user_id_foreign` (`initiated_by_user_id`),
  KEY `payments_company_id_status_index` (`company_id`,`status`),
  KEY `payments_company_id_created_at_index` (`company_id`,`created_at`),
  KEY `payments_invoice_id_status_index` (`invoice_id`,`status`),
  KEY `payments_created_at_index` (`created_at`),
  KEY `payments_invoice_id_index` (`invoice_id`),
  KEY `payments_helcim_transaction_id_index` (`helcim_transaction_id`),
  KEY `payments_status_index` (`status`),
  KEY `payments_invoice_number_index` (`invoice_number`),
  KEY `payments_dispute_status_index` (`dispute_status`),
  CONSTRAINT `payments_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_initiated_by_user_id_foreign` FOREIGN KEY (`initiated_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_payment_method_id_foreign` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_methods` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_refunded_from_payment_id_foreign` FOREIGN KEY (`refunded_from_payment_id`) REFERENCES `payments` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permission_role`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permission_role` (
  `permission_id` bigint(20) unsigned NOT NULL,
  `role_id` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`permission_id`,`role_id`),
  KEY `permission_role_role_id_foreign` (`role_id`),
  CONSTRAINT `permission_role_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `permission_role_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `permissions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `permissions_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pib_billing_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pib_billing_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `product_type` varchar(255) NOT NULL,
  `product_config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`product_config`)),
  `billing_cycle` varchar(255) NOT NULL DEFAULT 'monthly',
  `next_invoice_date` date DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pib_billing_templates_client_id_status_index` (`client_id`,`status`),
  KEY `pib_billing_templates_next_invoice_date_index` (`next_invoice_date`),
  KEY `pib_billing_templates_company_id_index` (`company_id`),
  CONSTRAINT `pib_billing_templates_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pib_billing_templates_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pib_invoice_line_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pib_invoice_line_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint(20) unsigned NOT NULL,
  `description` varchar(255) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `pib_invoice_line_items_invoice_id_index` (`invoice_id`),
  CONSTRAINT `pib_invoice_line_items_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `pib_invoices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pib_invoices`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pib_invoices` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `company_id` bigint(20) unsigned NOT NULL,
  `billing_template_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_number` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `invoice_date` date NOT NULL,
  `due_date` date NOT NULL,
  `subtotal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tax_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `paid_at` timestamp NULL DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pib_invoices_invoice_number_unique` (`invoice_number`),
  KEY `pib_invoices_billing_template_id_foreign` (`billing_template_id`),
  KEY `pib_invoices_client_id_status_index` (`client_id`,`status`),
  KEY `pib_invoices_invoice_number_index` (`invoice_number`),
  KEY `pib_invoices_invoice_date_index` (`invoice_date`),
  KEY `pib_invoices_company_id_index` (`company_id`),
  CONSTRAINT `pib_invoices_billing_template_id_foreign` FOREIGN KEY (`billing_template_id`) REFERENCES `pib_billing_templates` (`id`) ON DELETE SET NULL,
  CONSTRAINT `pib_invoices_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pib_invoices_company_id_foreign` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `polycast_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `polycast_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `channel` varchar(255) NOT NULL,
  `event` text NOT NULL,
  `payload` longtext NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `polycast_events_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `processed_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `processed_events` (
  `event_id` varchar(255) NOT NULL,
  `handler_class` varchar(255) NOT NULL,
  `processed_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  UNIQUE KEY `processed_events_unique` (`event_id`,`handler_class`),
  KEY `processed_events_event_id_index` (`event_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reconciliation_discrepancies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reconciliation_discrepancies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reconciliation_run_id` bigint(20) unsigned NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` varchar(255) DEFAULT NULL,
  `field_name` varchar(100) NOT NULL,
  `expected_value` text DEFAULT NULL,
  `actual_value` text DEFAULT NULL,
  `source_system` text DEFAULT NULL,
  `severity` enum('low','medium','high','critical') NOT NULL DEFAULT 'medium',
  `resolution_status` enum('pending','auto_corrected','manual_review','resolved','ignored') NOT NULL DEFAULT 'pending',
  `resolution_action` text DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolution_notes` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reconciliation_discrepancies_resolved_by_foreign` (`resolved_by`),
  KEY `reconciliation_discrepancies_reconciliation_run_id_index` (`reconciliation_run_id`),
  KEY `reconciliation_discrepancies_entity_type_index` (`entity_type`),
  KEY `reconciliation_discrepancies_resolution_status_index` (`resolution_status`),
  KEY `reconciliation_discrepancies_severity_index` (`severity`),
  KEY `reconciliation_discrepancies_resolution_status_severity_index` (`resolution_status`,`severity`),
  CONSTRAINT `reconciliation_discrepancies_reconciliation_run_id_foreign` FOREIGN KEY (`reconciliation_run_id`) REFERENCES `reconciliation_runs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reconciliation_discrepancies_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reconciliation_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reconciliation_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `run_type` varchar(50) NOT NULL,
  `status` enum('running','completed','failed','partial') NOT NULL DEFAULT 'running',
  `started_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `completed_at` timestamp NULL DEFAULT NULL,
  `scope` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`scope`)),
  `items_checked` int(11) NOT NULL DEFAULT 0,
  `total_discrepancies` int(11) NOT NULL DEFAULT 0,
  `auto_corrected` int(11) NOT NULL DEFAULT 0,
  `manual_review_required` int(11) NOT NULL DEFAULT 0,
  `critical_issues` int(11) NOT NULL DEFAULT 0,
  `success_rate` decimal(5,2) DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `duration_seconds` int(11) DEFAULT NULL,
  `triggered_by` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reconciliation_runs_status_index` (`status`),
  KEY `reconciliation_runs_started_at_index` (`started_at`),
  KEY `reconciliation_runs_run_type_index` (`run_type`),
  KEY `reconciliation_runs_status_started_at_index` (`status`,`started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `label` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `roles_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saved_searches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saved_searches` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `query` text DEFAULT NULL,
  `filters` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`filters`)),
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `saved_searches_user_id_is_default_index` (`user_id`,`is_default`),
  KEY `saved_searches_user_id_sort_order_index` (`user_id`,`sort_order`),
  CONSTRAINT `saved_searches_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `send_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `send_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` bigint(20) unsigned DEFAULT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `message_id` varchar(998) DEFAULT NULL,
  `email` varchar(191) NOT NULL,
  `subject` varchar(255) DEFAULT NULL,
  `mail_type` tinyint(3) unsigned DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL,
  `status_message` text DEFAULT NULL,
  `smtp_queue_id` varchar(100) DEFAULT NULL,
  `opens` int(10) unsigned NOT NULL DEFAULT 0,
  `clicks` int(10) unsigned NOT NULL DEFAULT 0,
  `opened_at` timestamp NULL DEFAULT NULL,
  `clicked_at` timestamp NULL DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `send_logs_user_id_foreign` (`user_id`),
  KEY `send_logs_thread_id_index` (`thread_id`),
  KEY `send_logs_email_index` (`email`),
  KEY `send_logs_status_index` (`status`),
  KEY `send_logs_customer_id_index` (`customer_id`),
  KEY `send_logs_message_id_index` (`message_id`(191)),
  CONSTRAINT `send_logs_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `send_logs_thread_id_foreign` FOREIGN KEY (`thread_id`) REFERENCES `threads` (`id`) ON DELETE SET NULL,
  CONSTRAINT `send_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `service_usage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `service_usage` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `service_type` varchar(255) NOT NULL,
  `hours` decimal(8,2) NOT NULL,
  `hourly_rate` decimal(10,2) NOT NULL,
  `description` text DEFAULT NULL,
  `service_date` date NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'draft',
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `service_usage_user_id_foreign` (`user_id`),
  KEY `service_usage_invoice_id_foreign` (`invoice_id`),
  KEY `service_usage_approved_by_foreign` (`approved_by`),
  KEY `service_usage_client_id_status_index` (`client_id`,`status`),
  KEY `service_usage_service_date_index` (`service_date`),
  CONSTRAINT `service_usage_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`),
  CONSTRAINT `service_usage_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `service_usage_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `pib_invoices` (`id`) ON DELETE SET NULL,
  CONSTRAINT `service_usage_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `software_assignments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `software_assignments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `assignable_type` varchar(255) NOT NULL,
  `assignable_id` bigint(20) unsigned NOT NULL,
  `license_key` varchar(255) DEFAULT NULL,
  `seat_id` varchar(255) DEFAULT NULL,
  `deployment_status` enum('pending','deployed','failed','uninstalling','removed') NOT NULL DEFAULT 'pending',
  `deployed_at` timestamp NULL DEFAULT NULL,
  `deployment_notes` text DEFAULT NULL,
  `assigned_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `assigned_by` bigint(20) unsigned DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `revoked_by` bigint(20) unsigned DEFAULT NULL,
  `revocation_reason` varchar(255) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_active_assignment` (`subscription_id`,`assignable_type`,`assignable_id`),
  KEY `software_assignments_assigned_by_foreign` (`assigned_by`),
  KEY `software_assignments_revoked_by_foreign` (`revoked_by`),
  KEY `software_assignments_subscription_id_index` (`subscription_id`),
  KEY `software_assignments_assignable_type_assignable_id_index` (`assignable_type`,`assignable_id`),
  KEY `software_assignments_deployment_status_index` (`deployment_status`),
  KEY `software_assignments_revoked_at_index` (`revoked_at`),
  CONSTRAINT `software_assignments_assigned_by_foreign` FOREIGN KEY (`assigned_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `software_assignments_revoked_by_foreign` FOREIGN KEY (`revoked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `software_assignments_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `client_software_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `software_discoveries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `software_discoveries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source` enum('action1','google','intune','manual') NOT NULL,
  `source_identifier` varchar(255) NOT NULL,
  `software_product_id` bigint(20) unsigned DEFAULT NULL,
  `raw_software_name` varchar(255) NOT NULL,
  `version` varchar(100) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `discovered_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `reconciliation_status` enum('unrecognized','verified','over_deployed','under_utilized') NOT NULL DEFAULT 'unrecognized',
  `reconciled_at` timestamp NULL DEFAULT NULL,
  `assignment_id` bigint(20) unsigned DEFAULT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `asset_id` bigint(20) unsigned DEFAULT NULL,
  `contact_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_discovery` (`source`,`source_identifier`,`raw_software_name`),
  KEY `software_discoveries_software_product_id_foreign` (`software_product_id`),
  KEY `software_discoveries_assignment_id_foreign` (`assignment_id`),
  KEY `software_discoveries_source_source_identifier_index` (`source`,`source_identifier`),
  KEY `software_discoveries_client_id_index` (`client_id`),
  KEY `software_discoveries_asset_id_index` (`asset_id`),
  KEY `software_discoveries_contact_id_index` (`contact_id`),
  KEY `software_discoveries_source_index` (`source`),
  KEY `software_discoveries_reconciliation_status_index` (`reconciliation_status`),
  CONSTRAINT `software_discoveries_assignment_id_foreign` FOREIGN KEY (`assignment_id`) REFERENCES `software_assignments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `software_discoveries_software_product_id_foreign` FOREIGN KEY (`software_product_id`) REFERENCES `software_products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `software_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `software_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `sku` varchar(255) DEFAULT NULL,
  `vendor` varchar(255) NOT NULL,
  `category` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `licensing_model` enum('per_user','per_device','per_site','flat','concurrent') NOT NULL DEFAULT 'per_user',
  `pricing_type` enum('flat','tiered','volume') NOT NULL DEFAULT 'flat',
  `vendor_cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `default_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `pricing_tiers` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`pricing_tiers`)),
  `billing_frequency` enum('monthly','quarterly','annually') NOT NULL DEFAULT 'monthly',
  `vendor_sku` varchar(255) DEFAULT NULL,
  `vendor_api_id` varchar(255) DEFAULT NULL,
  `auto_assign_new_users` tinyint(1) NOT NULL DEFAULT 0,
  `auto_assign_new_devices` tinyint(1) NOT NULL DEFAULT 0,
  `applicable_asset_types` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applicable_asset_types`)),
  `supports_deployment` tinyint(1) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `software_products_sku_unique` (`sku`),
  KEY `software_products_vendor_index` (`vendor`),
  KEY `software_products_category_index` (`category`),
  KEY `software_products_is_active_index` (`is_active`),
  KEY `software_products_vendor_vendor_sku_index` (`vendor`,`vendor_sku`),
  KEY `software_products_licensing_model_index` (`licensing_model`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `software_subscription_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `software_subscription_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `subscription_id` bigint(20) unsigned NOT NULL,
  `snapshot_date` date NOT NULL,
  `billing_period` varchar(255) NOT NULL,
  `assigned_count` int(11) NOT NULL,
  `purchased_quantity` int(11) NOT NULL,
  `unit_cost` decimal(10,2) NOT NULL,
  `unit_price` decimal(10,2) NOT NULL,
  `total_cost` decimal(10,2) NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `margin` decimal(10,2) NOT NULL DEFAULT 0.00,
  `tier_name` varchar(255) DEFAULT NULL,
  `tier_details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tier_details`)),
  `invoice_id` bigint(20) unsigned DEFAULT NULL,
  `invoice_line_item_id` bigint(20) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_subscription_period` (`subscription_id`,`billing_period`),
  KEY `software_subscription_snapshots_subscription_id_index` (`subscription_id`),
  KEY `software_subscription_snapshots_snapshot_date_index` (`snapshot_date`),
  KEY `software_subscription_snapshots_billing_period_index` (`billing_period`),
  KEY `idx_sw_sub_snap_sub_per` (`subscription_id`,`billing_period`),
  CONSTRAINT `software_subscription_snapshots_subscription_id_foreign` FOREIGN KEY (`subscription_id`) REFERENCES `client_software_subscriptions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `subscriptions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `medium` tinyint(3) unsigned NOT NULL,
  `event` tinyint(3) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `subscriptions_user_id_medium_event_unique` (`user_id`,`medium`,`event`),
  CONSTRAINT `subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sync_operations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_operations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `operation_type` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'running',
  `total_items` int(11) NOT NULL DEFAULT 0,
  `processed_items` int(11) NOT NULL DEFAULT 0,
  `failed_items` int(11) NOT NULL DEFAULT 0,
  `success_items` int(11) NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `last_progress_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `checkpoint_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`checkpoint_data`)),
  `failures` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`failures`)),
  `items_per_second` decimal(8,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sync_operations_status_started_at_index` (`status`,`started_at`),
  KEY `sync_operations_operation_type_index` (`operation_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `test_counters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `test_counters` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `count` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `themes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `themes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `title` varchar(255) NOT NULL,
  `config` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`config`)),
  `is_system` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `themes_name_unique` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `threads`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `threads` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `type` tinyint(3) unsigned NOT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `state` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `action_type` tinyint(3) unsigned DEFAULT NULL,
  `action_data` varchar(255) DEFAULT NULL,
  `body` mediumtext DEFAULT NULL,
  `headers` text DEFAULT NULL,
  `from` varchar(191) DEFAULT NULL,
  `to` text DEFAULT NULL,
  `cc` text DEFAULT NULL,
  `bcc` text DEFAULT NULL,
  `has_attachments` tinyint(1) NOT NULL DEFAULT 0,
  `message_id` varchar(760) DEFAULT NULL,
  `source_via` tinyint(3) unsigned NOT NULL,
  `source_type` tinyint(3) unsigned NOT NULL,
  `customer_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `created_by_customer_id` bigint(20) unsigned DEFAULT NULL,
  `edited_by_user_id` bigint(20) unsigned DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `body_original` mediumtext DEFAULT NULL,
  `first` tinyint(1) NOT NULL DEFAULT 0,
  `saved_reply_id` bigint(20) unsigned DEFAULT NULL,
  `send_status` tinyint(3) unsigned DEFAULT NULL,
  `send_status_data` text DEFAULT NULL,
  `meta_subtype` varchar(20) DEFAULT NULL,
  `meta_id` bigint(20) unsigned DEFAULT NULL,
  `imported` tinyint(1) NOT NULL DEFAULT 0,
  `opened_at` timestamp NULL DEFAULT NULL,
  `meta` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `threads_user_id_foreign` (`user_id`),
  KEY `threads_customer_id_foreign` (`customer_id`),
  KEY `threads_created_by_user_id_foreign` (`created_by_user_id`),
  KEY `threads_edited_by_user_id_foreign` (`edited_by_user_id`),
  KEY `threads_conversation_id_index` (`conversation_id`),
  KEY `threads_message_id_index` (`message_id`),
  CONSTRAINT `threads_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `threads_created_by_user_id_foreign` FOREIGN KEY (`created_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `threads_customer_id_foreign` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL,
  CONSTRAINT `threads_edited_by_user_id_foreign` FOREIGN KEY (`edited_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `threads_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ticket_lifecycle_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ticket_lifecycle_events` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned DEFAULT NULL,
  `event_type` enum('opened','assigned','unassigned','status_changed','replied','closed','reopened') NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `old_status` varchar(50) DEFAULT NULL,
  `new_status` varchar(50) DEFAULT NULL,
  `old_assignee_id` bigint(20) unsigned DEFAULT NULL,
  `new_assignee_id` bigint(20) unsigned DEFAULT NULL,
  `event_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `time_since_open_minutes` int(10) unsigned DEFAULT NULL,
  `time_since_last_event_minutes` int(10) unsigned DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ticket_lifecycle_events_conversation_id_index` (`conversation_id`),
  KEY `idx_client_period` (`client_id`,`event_at`),
  KEY `idx_event_type` (`event_type`,`event_at`),
  KEY `idx_user` (`user_id`,`event_at`),
  CONSTRAINT `ticket_lifecycle_events_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ticket_lifecycle_events_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `time_entries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `time_entries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `entry_id` char(36) NOT NULL,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `client_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration_minutes` int(10) unsigned NOT NULL,
  `work_type` enum('troubleshooting','implementation','documentation','travel','meeting','research','other') NOT NULL DEFAULT 'troubleshooting',
  `is_billable` tinyint(1) NOT NULL DEFAULT 0,
  `billing_rate` decimal(10,2) DEFAULT NULL,
  `service_usage_id` bigint(20) unsigned DEFAULT NULL,
  `description` text DEFAULT NULL,
  `internal_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `time_entries_entry_id_unique` (`entry_id`),
  KEY `time_entries_service_usage_id_foreign` (`service_usage_id`),
  KEY `time_entries_conversation_id_index` (`conversation_id`),
  KEY `time_entries_client_id_started_at_index` (`client_id`,`started_at`),
  KEY `time_entries_user_id_started_at_index` (`user_id`,`started_at`),
  KEY `time_entries_client_id_service_usage_id_index` (`client_id`,`service_usage_id`),
  CONSTRAINT `time_entries_client_id_foreign` FOREIGN KEY (`client_id`) REFERENCES `clients` (`id`) ON DELETE CASCADE,
  CONSTRAINT `time_entries_service_usage_id_foreign` FOREIGN KEY (`service_usage_id`) REFERENCES `service_usage` (`id`) ON DELETE SET NULL,
  CONSTRAINT `time_entries_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `first_name` varchar(20) NOT NULL,
  `last_name` varchar(30) NOT NULL,
  `email` varchar(191) NOT NULL,
  `google_id` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `timezone` varchar(255) NOT NULL DEFAULT 'UTC',
  `photo_url` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `type` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `invite_state` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `invite_hash` varchar(100) DEFAULT NULL,
  `emails` varchar(100) DEFAULT NULL,
  `job_title` varchar(100) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `time_format` tinyint(3) unsigned NOT NULL DEFAULT 24,
  `enable_kb_shortcuts` tinyint(1) NOT NULL DEFAULT 1,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `status` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `locale` varchar(5) NOT NULL DEFAULT 'en',
  `theme` varchar(50) DEFAULT NULL,
  `dark_mode` tinyint(1) NOT NULL DEFAULT 1,
  `permissions` text DEFAULT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_google_id_unique` (`google_id`),
  KEY `users_role_index` (`role`),
  KEY `users_status_index` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_themes_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_system_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'0001_01_01_000003_create_mailboxes_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'0001_01_01_000004_create_channels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'0001_01_01_000005_create_customers_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'0001_01_01_000006_create_conversations_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'0001_01_01_000007_create_attachments_and_logs_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'0001_01_01_000008_create_saved_searches_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'0001_01_01_000009_create_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'0001_01_01_000010_create_rbac_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_01_01_000000_create_crm_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_01_01_000000_create_email_migration_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_01_01_000000_create_synchronization_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_01_13_000000_create_invoices_table_for_testing',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_01_13_000001_create_payment_methods_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_01_13_000002_create_payments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_01_13_000003_add_payment_fields_to_companies_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_01_15_000001_add_auth_fields_to_client_users',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_01_15_000001_create_action1_configs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_01_15_000001_create_google_configs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_01_15_000001_create_pib_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_01_15_000002_create_action1_sync_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_01_15_000002_create_google_sync_logs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_01_15_000003_create_action1_device_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_01_15_000003_create_google_push_channels_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_01_15_000004_create_test_counters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_01_15_024342_add_crm_fields_to_clients_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_01_15_024343_create_crm_contacts_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_01_15_024344_create_crm_custom_fields_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_01_15_032129_create_assets_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_01_15_032130_create_asset_staging_records_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_01_15_032131_create_client_asset_counters_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_01_15_050000_create_client_credit_system',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_01_15_060000_add_dispute_fields_to_payments',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_01_15_070000_create_crm_field_definitions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_01_15_100000_create_crm_contact_permissions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_01_15_202042_create_billing_adjustments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_01_15_999999_fix_clients_table_for_tests',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_01_16_000000_create_service_usage_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_01_16_000000_remove_balance_from_companies',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_01_16_000001_fix_client_credit_ledger_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_01_16_000500_create_approval_requests_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_01_16_011530_create_milestones_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_01_16_024012_create_notification_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_01_16_100001_add_company_id_to_pib_tables',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_01_16_200000_create_client_conversations_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_01_16_200000_create_time_entries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_01_16_200001_create_ticket_lifecycle_events_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_01_16_200002_create_client_service_metrics_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_01_16_210000_create_conversation_billing_metadata_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_01_19_000001_create_software_products_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_01_19_000002_create_client_software_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_01_19_000003_create_software_assignments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_01_19_000004_create_software_subscription_snapshots_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_01_19_000005_create_software_discoveries_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_01_19_100001_create_alert_types_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_01_19_100002_create_alert_subscriptions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_01_19_100003_create_alert_delivery_log_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_01_19_100004_create_alert_throttles_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_01_19_100005_create_alert_digest_queue_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_01_20_000001_add_admin_email_to_google_configs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_01_21_000002_add_rejection_columns_to_quotes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_01_21_000003_add_frequency_to_quote_items',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_01_21_000004_update_billing_template_enums',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_01_21_030000_add_service_category_to_client_conversations',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_01_21_051350_create_client_credit_ledgers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_01_21_170307_add_revision_fields_to_quotes',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_01_22_003412_create_client_credit_ledger_table',1);
