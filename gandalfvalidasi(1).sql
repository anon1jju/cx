-- Adminer 5.3.0 MariaDB 5.5.5-10.11.10-MariaDB-log dump

SET NAMES utf8;
SET time_zone = '+00:00';
SET foreign_key_checks = 0;
SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';

SET NAMES utf8mb4;

DROP TABLE IF EXISTS `admins`;
CREATE TABLE `admins` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `admins` (`id`, `username`, `email`, `password`) VALUES
(1,	'admin',	'admin@example.com',	'$2a$12$gKSpY.BPfQkHMWyGIEv8MuWCuKQ5yT8.jaYX.bNEYOnRaYAMwil76');

DROP TABLE IF EXISTS `customers`;
CREATE TABLE `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `api_key` varchar(64) NOT NULL,
  `customer_name` varchar(255) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `daily_request_limit` int(15) NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `email_check_server` varchar(255) NOT NULL DEFAULT 'check.php',
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `api_key` (`api_key`),
  UNIQUE KEY `email` (`email`),
  KEY `idx_customers_api_key` (`api_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customers` (`id`, `api_key`, `customer_name`, `email`, `daily_request_limit`, `is_active`, `email_check_server`, `created_at`, `updated_at`) VALUES
(1,	'dadamin',	'admin',	NULL,	99000000,	1,	'check2.php',	'2025-06-12 13:28:53',	'2025-06-13 18:17:53'),
(6,	'9272f984effd9085',	'jaupan',	NULL,	15000,	1,	'check2.php',	'2025-06-13 04:57:48',	'2025-06-13 15:01:15'),
(7,	'54b671914a5d666a',	'luffy',	NULL,	15000,	1,	'check2.php',	'2025-06-13 05:08:58',	'2025-06-13 12:48:18'),
(8,	'a0379a8fd62b5ca2',	'boalin',	NULL,	20000,	1,	'check2.php',	'2025-06-13 05:09:09',	'2025-06-16 16:23:24'),
(9,	'85d8c96d5fc0b87f',	'bagus',	NULL,	10000,	1,	'check2.php',	'2025-06-13 05:09:16',	'2025-06-15 09:16:30'),
(10,	'4c851eda1a59a763',	'kojo',	NULL,	15000,	1,	'check2.php',	'2025-06-13 05:09:36',	'2025-06-13 11:56:21');

DROP TABLE IF EXISTS `customer_api_usage`;
CREATE TABLE `customer_api_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customer_id` int(11) NOT NULL,
  `usage_date` date NOT NULL,
  `request_count` int(11) NOT NULL DEFAULT 0,
  `last_request_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_customer_date` (`customer_id`,`usage_date`),
  KEY `idx_customer_api_usage_customer_date` (`customer_id`,`usage_date`),
  CONSTRAINT `customer_api_usage_ibfk_1` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `customer_api_usage` (`id`, `customer_id`, `usage_date`, `request_count`, `last_request_at`) VALUES
(5,	1,	'2025-06-13',	304,	'2025-06-13 13:26:40'),
(36,	10,	'2025-06-13',	11,	'2025-06-13 12:34:31'),
(320,	1,	'2025-06-14',	75,	'2025-06-14 13:53:03'),
(480,	10,	'2025-06-14',	3849,	'2025-06-14 16:00:03'),
(8176,	10,	'2025-06-15',	15002,	'2025-06-15 13:41:47'),
(30476,	6,	'2025-06-15',	15002,	'2025-06-15 11:59:41'),
(68180,	10,	'2025-06-16',	15001,	'2025-06-16 15:43:32'),
(98180,	1,	'2025-06-17',	10,	'2025-06-16 16:16:05'),
(98198,	10,	'2025-06-17',	15001,	'2025-06-16 22:31:08'),
(128198,	6,	'2025-06-17',	5088,	'2025-06-17 15:59:58'),
(138372,	6,	'2025-06-18',	9882,	'2025-06-17 17:36:12'),
(148617,	10,	'2025-06-18',	15000,	'2025-06-18 07:46:44'),
(188132,	10,	'2025-06-20',	14993,	'2025-06-19 20:01:58'),
(218116,	10,	'2025-06-21',	15002,	'2025-06-21 15:21:16'),
(248118,	10,	'2025-06-22',	11657,	'2025-06-21 19:05:32'),
(271430,	10,	'2025-06-23',	14994,	'2025-06-22 23:51:55'),
(301416,	6,	'2025-06-23',	449,	'2025-06-23 13:48:13');

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` text DEFAULT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('admin_email_notifications',	'admin@contoh.com'),
('app_name',	'Gandalf Tools'),
('default_api_limit',	'15000');

-- 2025-06-23 18:35:31 UTC
