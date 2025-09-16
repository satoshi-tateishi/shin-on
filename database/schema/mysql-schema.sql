/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` int NOT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `dropbox_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `dropbox_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `service_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'backup' COMMENT 'サービス名（将来的に複数のサービスで使用可能）',
  `access_token` text COLLATE utf8mb4_unicode_ci COMMENT 'アクセストークン（短期間有効）',
  `access_token_expires_at` timestamp NULL DEFAULT NULL COMMENT 'アクセストークンの有効期限',
  `refresh_token` text COLLATE utf8mb4_unicode_ci COMMENT 'リフレッシュトークン（長期間有効）',
  `account_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Dropboxアカウント ID',
  `account_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Dropboxアカウント表示名',
  `scope` text COLLATE utf8mb4_unicode_ci COMMENT '許可されたスコープ',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'トークンが有効かどうか',
  `last_refreshed_at` timestamp NULL DEFAULT NULL COMMENT '最後にトークンを更新した日時',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dropbox_tokens_service_name_account_id_unique` (`service_name`,`account_id`),
  KEY `dropbox_tokens_service_name_is_active_index` (`service_name`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort` int NOT NULL DEFAULT '0' COMMENT 'ソート順',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '大分類名',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipment_categories_sort_index` (`sort`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_sets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_sets` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort` int NOT NULL DEFAULT '0' COMMENT 'ソート順',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'セット名',
  `description` text COLLATE utf8mb4_unicode_ci COMMENT '説明',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '有効フラグ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipment_sets_sort_index` (`sort`),
  KEY `equipment_sets_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipment_subcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipment_subcategories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `category_id` bigint unsigned NOT NULL,
  `sort` int NOT NULL DEFAULT '0' COMMENT 'ソート順',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '中分類名',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `equipment_subcategories_category_id_index` (`category_id`),
  KEY `equipment_subcategories_sort_index` (`sort`),
  CONSTRAINT `equipment_subcategories_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `equipment_categories` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `equipments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `equipments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `subcategory_id` bigint unsigned NOT NULL,
  `sort` int NOT NULL DEFAULT '0' COMMENT 'ソート順',
  `manufacturer` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'メーカー名',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '機材名',
  `company_number` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '新音番号',
  `management_type` enum('individual','quantity') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'individual' COMMENT '管理方式',
  `quantity` int NOT NULL DEFAULT '1' COMMENT '在庫数量',
  `unit` enum('台','個','本','箱','ケース','ラック','セット') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '台' COMMENT '単位',
  `model_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '型番',
  `serial_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'シリアル番号',
  `supplier` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '仕入先',
  `purchase_date` date DEFAULT NULL COMMENT '購入日',
  `warranty_expiry` date DEFAULT NULL COMMENT '保証期限',
  `price` decimal(12,2) DEFAULT NULL COMMENT '価格',
  `status` enum('available','in_use','repair','maintenance','retired','lost') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'available' COMMENT '状態',
  `location_id` bigint unsigned DEFAULT NULL,
  `is_discard` tinyint(1) NOT NULL DEFAULT '0' COMMENT '廃棄フラグ',
  `discard_at` date DEFAULT NULL COMMENT '廃棄日',
  `notes` text COLLATE utf8mb4_unicode_ci COMMENT '備考',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `equipments_company_number_unique` (`company_number`),
  KEY `equipments_subcategory_id_index` (`subcategory_id`),
  KEY `equipments_management_type_index` (`management_type`),
  KEY `equipments_status_index` (`status`),
  KEY `equipments_manufacturer_index` (`manufacturer`),
  KEY `equipments_name_index` (`name`),
  KEY `equipments_location_id_index` (`location_id`),
  KEY `equipments_is_discard_index` (`is_discard`),
  CONSTRAINT `equipments_location_id_foreign` FOREIGN KEY (`location_id`) REFERENCES `locations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `equipments_subcategory_id_foreign` FOREIGN KEY (`subcategory_id`) REFERENCES `equipment_subcategories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `locations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `locations` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort` int NOT NULL DEFAULT '0' COMMENT 'ソート順',
  `type` enum('劇場','稽古場','倉庫') COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '場所タイプ',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT '場所名',
  `furigana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'ふりがな',
  `tel1_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '電話1名称',
  `tel1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '電話1',
  `tel2_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '電話2名称',
  `tel2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '電話2',
  `fax` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'FAX',
  `email1_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'メール1名称',
  `email1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'メール1',
  `email2_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'メール2名称',
  `email2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'メール2',
  `postal_code` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '郵便番号',
  `address` text COLLATE utf8mb4_unicode_ci COMMENT '住所',
  `note` text COLLATE utf8mb4_unicode_ci COMMENT '備考',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '有効フラグ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `locations_type_index` (`type`),
  KEY `locations_sort_index` (`sort`),
  KEY `locations_name_index` (`name`),
  KEY `locations_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `positions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `positions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort` int NOT NULL DEFAULT '0' COMMENT 'ソート順',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ポジション名',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '有効フラグ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `positions_sort_index` (`sort`),
  KEY `positions_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `productions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort` int NOT NULL DEFAULT '0' COMMENT 'ソート順',
  `type` enum('株式会社','有限会社','合同会社','財団法人','公益財団法人','その他') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '株式会社' COMMENT '法人種別',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'プロダクション名',
  `postal_code` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '郵便番号',
  `address` text COLLATE utf8mb4_unicode_ci COMMENT '住所',
  `note` text COLLATE utf8mb4_unicode_ci COMMENT '備考',
  `is_active` tinyint(1) NOT NULL DEFAULT '1' COMMENT '有効フラグ',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `productions_sort_index` (`sort`),
  KEY `productions_type_index` (`type`),
  KEY `productions_is_active_index` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `sort` int NOT NULL DEFAULT '0' COMMENT 'ソート順',
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `furigana` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `lineworks_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lineworks_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `lineworks_refresh_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icon` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mobile_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `address` text COLLATE utf8mb4_unicode_ci,
  `birthday` date DEFAULT NULL COMMENT '生年月日',
  `hired_at` date DEFAULT NULL COMMENT '入社日',
  `resigned_at` date DEFAULT NULL COMMENT '退職日',
  `postal_code` varchar(8) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT '郵便番号',
  `emergency_contact_name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `emergency_contact_phone` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `role` enum('viewer','editor','admin') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'viewer',
  `affiliation` enum('employee','partner') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'employee',
  `is_designer` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'サウンドデザイナー選択に表示するか',
  `is_staff` tinyint(1) NOT NULL DEFAULT '0' COMMENT '公演担当者選択に表示するか（担当者フラグ）',
  `is_driver` tinyint(1) NOT NULL DEFAULT '0' COMMENT 'ドライバーフラグ',
  `is_on_leave` tinyint(1) NOT NULL DEFAULT '0' COMMENT '休職フラグ',
  `is_resigned` tinyint(1) NOT NULL DEFAULT '0' COMMENT '退職フラグ',
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  UNIQUE KEY `users_lineworks_id_unique` (`lineworks_id`),
  KEY `users_sort_index` (`sort`),
  KEY `users_is_designer_index` (`is_designer`),
  KEY `users_is_staff_index` (`is_staff`),
  KEY `users_is_resigned_index` (`is_resigned`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2025_09_05_070944_add_lineworks_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2025_09_06_011443_add_employee_fields_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2025_09_06_012203_add_role_field_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2025_09_06_012528_add_affiliation_field_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2025_09_06_012801_add_is_retired_field_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2025_09_06_015618_add_furigana_field_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2025_09_06_110914_rename_avatar_to_icon_in_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2025_09_07_103554_create_dropbox_tokens_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2025_09_14_233028_add_missing_fields_to_users_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2025_09_14_233119_create_positions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2025_09_14_233200_create_equipment_categories_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2025_09_14_233235_create_equipment_subcategories_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2025_09_14_233303_create_locations_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2025_09_14_233341_create_equipments_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2025_09_14_233420_create_equipment_sets_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2025_09_14_233452_create_productions_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2025_09_15_110357_remove_unused_fields_from_users_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2025_09_15_223005_remove_unnecessary_columns_from_users_table',4);
