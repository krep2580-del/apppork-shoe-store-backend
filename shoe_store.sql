-- ==========================================================
-- Shoe Store Database Schema & Initial Seed Data (All-in-One)
-- สำหรับนำเข้าผ่าน phpMyAdmin ใน Laragon (MySQL / MariaDB)
-- รวมทั้งตารางหลัก, personal_access_tokens (Sanctum), ระบบชำระเงิน/แนบสลิป และข้อมูลตัวอย่าง
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `shoe_store` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `shoe_store`;

-- ปิดการตรวจสอบ Foreign Key ชั่วคราวเพื่อความปลอดภัยในการ Drop/Create
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `personal_access_tokens`;
DROP TABLE IF EXISTS `order_items`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `products`;
DROP TABLE IF EXISTS `users`;

-- ----------------------------------------------------------
-- 1. ตาราง users
-- ----------------------------------------------------------
CREATE TABLE `users` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('customer','admin') NOT NULL DEFAULT 'customer',
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 2. ตาราง products (มีคอลัมน์ is_active สำหรับ Soft Delete / ซ่อนสินค้า)
-- ----------------------------------------------------------
CREATE TABLE `products` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `category` varchar(100) NOT NULL DEFAULT 'รองเท้าผ้าใบ',
  `stock` int(11) NOT NULL DEFAULT 0,
  `sizes` json NOT NULL,
  `colors` json NOT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 3. ตาราง orders (รองรับ payment_status, expires_at, paid_at, และ cancelled status)
-- ----------------------------------------------------------
CREATE TABLE `orders` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) UNSIGNED NOT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `shipping_address` text NOT NULL,
  `status` enum('pending','shipping','completed','cancelled') NOT NULL DEFAULT 'pending',
  `payment_status` enum('unpaid','pending_verification','paid') NOT NULL DEFAULT 'unpaid',
  `expires_at` timestamp NULL DEFAULT NULL,
  `paid_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `orders_user_id_foreign` (`user_id`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 4. ตาราง order_items
-- ----------------------------------------------------------
CREATE TABLE `order_items` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `product_id` bigint(20) UNSIGNED DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `size` varchar(50) DEFAULT NULL,
  `color` varchar(50) DEFAULT NULL,
  `image_url` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 5. ตาราง personal_access_tokens (สำหรับ Laravel Sanctum Auth)
-- ----------------------------------------------------------
CREATE TABLE `personal_access_tokens` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `token` varchar(64) NOT NULL,
  `abilities` text DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------
-- 6. ตาราง payments (สำหรับระบบชำระเงิน แนบสลิป และตรวจสอบ)
-- ----------------------------------------------------------
CREATE TABLE `payments` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_id` bigint(20) UNSIGNED NOT NULL,
  `method` varchar(50) NOT NULL DEFAULT 'promptpay',
  `amount` decimal(10,2) NOT NULL DEFAULT 0.00,
  `slip_path` varchar(500) NOT NULL,
  `slip_hash` varchar(64) NOT NULL,
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `reject_reason` text DEFAULT NULL,
  `verified_by` bigint(20) UNSIGNED DEFAULT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `payments_order_id_foreign` (`order_id`),
  KEY `payments_verified_by_foreign` (`verified_by`),
  KEY `payments_slip_hash_index` (`slip_hash`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ==========================================================
-- ข้อมูลเริ่มต้น (Seed Data)
-- ==========================================================

-- บัญชีผู้ใช้งานเริ่มต้น (รหัสผ่าน: password123)
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `created_at`, `updated_at`) VALUES
(1, 'ผู้ดูแลระบบ (Admin)', 'admin@shoestore.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin', NOW(), NOW()),
(2, 'สมชาย ช้อปปิ้งดี (Customer)', 'customer@shoestore.com', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'customer', NOW(), NOW());

-- สินค้าตัวอย่างครบ 4 หมวดหมู่ (is_active = 1 พร้อมจำหน่าย)
INSERT INTO `products` (`id`, `name`, `description`, `price`, `category`, `stock`, `sizes`, `colors`, `image_url`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Nike Air Max 270', 'รองเท้าผ้าใบระดับพรีเมียม สวมใส่สบายด้วยเทคโนโลยี Air Max รองรับแรงกระแทกได้อย่างยอดเยี่ยม', 4900.00, 'รองเท้าผ้าใบ', 15, '["39", "40", "41", "42", "43", "44"]', '["ดำ", "ขาว", "แดง"]', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600', 1, NOW(), NOW()),
(2, 'Adidas Ultraboost Light', 'รองเท้าวิ่งน้ำหนักเบา ให้ความยืดหยุ่นและการคืนพลังงานสูงสุด เหมาะสำหรับการวิ่งและออกกำลังกาย', 5200.00, 'รองเท้ากีฬา', 20, '["38", "39", "40", "41", "42"]', '["ดำ", "ขาว", "น้ำเงิน"]', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=600', 1, NOW(), NOW()),
(3, 'Clarks Classic Leather Oxford', 'รองเท้าหนังแท้ทรง Oxford ดีไซน์คลาสสิก เรียบหรู เหมาะสำหรับใส่ทำงานและงานเป็นทางการ', 3800.00, 'รองเท้าหนัง', 8, '["40", "41", "42", "43"]', '["น้ำตาล", "ดำ"]', 'https://images.unsplash.com/photo-1614252235316-8c857d38b5f4?w=600', 1, NOW(), NOW()),
(4, 'Birkenstock Comfort Sandal', 'รองเท้าแตะเพื่อสุขภาพ พื้นรองเท้าออกแบบตามสรีระเท้า สวมใส่สบายตลอดทั้งวัน', 2400.00, 'รองเท้าแตะ', 25, '["37", "38", "39", "40", "41", "42"]', '["น้ำตาล", "ดำ", "เบจ"]', 'https://images.unsplash.com/photo-1603808033192-082d6919d3e1?w=600', 1, NOW(), NOW()),
(5, 'Puma RS-X Triple White', 'รองเท้าสตรีทสไตล์ทรง Chunky ดีไซน์โดดเด่น แมตช์ได้กับทุกชุด', 3600.00, 'รองเท้าผ้าใบ', 12, '["39", "40", "41", "42", "43"]', '["ขาว"]', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600', 1, NOW(), NOW());

-- คำสั่งซื้อตัวอย่างเริ่มต้น (ออเดอร์เดิมกำหนด payment_status = paid)
INSERT INTO `orders` (`id`, `user_id`, `total_price`, `shipping_address`, `status`, `payment_status`, `expires_at`, `paid_at`, `created_at`, `updated_at`) VALUES
(1, 2, 4900.00, '123/45 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110', 'completed', 'paid', NULL, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(2, 2, 5200.00, '123/45 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110', 'shipping', 'paid', NULL, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(3, 2, 3600.00, '123/45 ถนนสุขุมวิท แขวงคลองเตย เขตคลองเตย กรุงเทพฯ 10110', 'pending', 'pending_verification', DATE_ADD(NOW(), INTERVAL 24 HOUR), NULL, NOW(), NOW());

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `size`, `color`, `image_url`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 'Nike Air Max 270', 4900.00, 1, '42', 'ดำ', 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600', DATE_SUB(NOW(), INTERVAL 2 DAY), NOW()),
(2, 2, 2, 'Adidas Ultraboost Light', 5200.00, 1, '40', 'ขาว', 'https://images.unsplash.com/photo-1584735935682-2f2b69dff9d2?w=600', DATE_SUB(NOW(), INTERVAL 1 DAY), NOW()),
(3, 3, 5, 'Puma RS-X Triple White', 3600.00, 1, '41', 'ขาว', 'https://images.unsplash.com/photo-1595950653106-6c9ebd614d3a?w=600', NOW(), NOW());
