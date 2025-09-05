-- نظام الصلاحيات والأدوار
-- تاريخ الإنشاء: 2025-09-05

-- جدول الأدوار
CREATE TABLE IF NOT EXISTS `user_roles` (
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
('admin', 'مدير النظام', 'صلاحيات كاملة على جميع أجزاء النظام', JSON_OBJECT('all', true)),
('director', 'مدير المدرسة', 'صلاحيات كاملة على جميع أجزاء النظام', JSON_OBJECT('all', true)),
('academic_deputy', 'النائب الأكاديمي', 'صلاحيات كاملة على جميع أجزاء النظام', JSON_OBJECT('all', true)),
('coordinator', 'منسق المادة', 'صلاحيات على مادة محددة والمعلمين والموجهين المرتبطين بها', JSON_OBJECT('subject_management', true, 'visit_creation', true, 'reports_view', true)),
('teacher', 'معلم', 'صلاحيات محدودة لعرض الزيارات والتقارير الشخصية فقط', JSON_OBJECT('view_own_visits', true, 'view_own_reports', true));

-- جدول المستخدمين
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int NOT NULL,
  `school_id` int DEFAULT NULL,
  `subject_id` int DEFAULT NULL COMMENT 'للمنسق - المادة التي يديرها',
  `teacher_id` int DEFAULT NULL COMMENT 'للمعلم - ربط مع جدول المعلمين',
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
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`),
  CONSTRAINT `users_role_fk` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`),
  CONSTRAINT `users_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `users_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `users_teacher_fk` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول ربط المنسق بالموجهين (الموجهين المخصصين للمنسق في مادته)
CREATE TABLE IF NOT EXISTS `coordinator_supervisors` (
  `id` int NOT NULL AUTO_INCREMENT,
  `coordinator_id` int NOT NULL COMMENT 'معرف المنسق في جدول المستخدمين',
  `supervisor_id` int NOT NULL COMMENT 'معرف الموجه في جدول visitor_types',
  `subject_id` int NOT NULL COMMENT 'المادة المشتركة',
  `school_id` int NOT NULL COMMENT 'المدرسة',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `coordinator_supervisor_subject` (`coordinator_id`, `supervisor_id`, `subject_id`),
  KEY `coordinator_id` (`coordinator_id`),
  KEY `supervisor_id` (`supervisor_id`),
  KEY `subject_id` (`subject_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `coord_sup_coordinator_fk` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coord_sup_supervisor_fk` FOREIGN KEY (`supervisor_id`) REFERENCES `visitor_types` (`id`),
  CONSTRAINT `coord_sup_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `coord_sup_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول جلسات المستخدمين
CREATE TABLE IF NOT EXISTS `user_sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` int NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `last_activity` (`last_activity`),
  CONSTRAINT `sessions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول سجل العمليات (للمراجعة والأمان)
CREATE TABLE IF NOT EXISTS `user_activity_log` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`),
  CONSTRAINT `activity_log_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
