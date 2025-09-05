-- نظام الصلاحيات والأدوار (محدث للتوافق مع MySQL 5.7+)
-- تاريخ الإنشاء: 2025-09-05

-- التحقق من وجود الجداول الأساسية المطلوبة
-- SELECT 'Checking required tables...' AS message;

-- إزالة الجداول إذا كانت موجودة لضمان التثبيت النظيف
-- ترتيب الحذف مهم بسبب المفاتيح الأجنبية
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `user_activity_log`;
DROP TABLE IF EXISTS `user_sessions`;
DROP TABLE IF EXISTS `coordinator_supervisors`;
DROP TABLE IF EXISTS `users`;
DROP TABLE IF EXISTS `user_roles`;
SET FOREIGN_KEY_CHECKS = 1;

-- جدول الأدوار
CREATE TABLE `user_roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج الأدوار الأساسية
INSERT INTO `user_roles` (`name`, `display_name`, `description`, `permissions`) VALUES
('Admin', 'مدير النظام', 'صلاحيات كاملة على جميع أجزاء النظام', JSON_OBJECT('all', true)),
('Director', 'مدير المدرسة', 'صلاحيات كاملة على جميع أجزاء النظام', JSON_OBJECT('all', true)),
('Academic Deputy', 'النائب الأكاديمي', 'صلاحيات كاملة على جميع أجزاء النظام', JSON_OBJECT('all', true)),
('Supervisor', 'مشرف تربوي', 'صلاحيات على جميع المواد والمعلمين', JSON_OBJECT('full_access', true)),
('Subject Coordinator', 'منسق المادة', 'صلاحيات على مادة محددة والمعلمين والموجهين المرتبطين بها', JSON_OBJECT('subject_management', true, 'visit_creation', true, 'reports_view', true)),
('Teacher', 'معلم', 'صلاحيات محدودة لعرض الزيارات والتقارير الشخصية فقط', JSON_OBJECT('view_own_visits', true, 'view_own_reports', true));

-- جدول المستخدمين
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int NOT NULL,
  `school_id` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `users_role_fk` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`),
  CONSTRAINT `users_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول ربط منسق المادة بالمشرفين والمواد
CREATE TABLE `coordinator_supervisors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL COMMENT 'معرف المستخدم (منسق المادة)',
  `subject_id` int NOT NULL COMMENT 'معرف المادة',
  `school_id` int DEFAULT NULL COMMENT 'معرف المدرسة',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_subject_unique` (`user_id`, `subject_id`),
  KEY `subject_id` (`subject_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `coord_sup_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coord_sup_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coord_sup_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول جلسات المستخدمين (للأمان والتتبع)
CREATE TABLE `user_sessions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `session_id` varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `session_id` (`session_id`),
  KEY `user_id` (`user_id`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `sessions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجل النشاطات (للمراجعة والأمان)
CREATE TABLE `user_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `activity_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
