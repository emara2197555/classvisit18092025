-- إزالة المفاتيح الأجنبية والرئيسية
ALTER TABLE IF EXISTS `visit_evaluations` DROP FOREIGN KEY IF EXISTS `visit_evaluations_visit_id_foreign`;
ALTER TABLE IF EXISTS `visit_evaluations` DROP FOREIGN KEY IF EXISTS `visit_evaluations_indicator_id_foreign`;
ALTER TABLE IF EXISTS `visit_evaluations` DROP FOREIGN KEY IF EXISTS `visit_evaluations_recommendation_id_foreign`;

ALTER TABLE IF EXISTS `visits` DROP FOREIGN KEY IF EXISTS `visits_school_id_foreign`;
ALTER TABLE IF EXISTS `visits` DROP FOREIGN KEY IF EXISTS `visits_teacher_id_foreign`;
ALTER TABLE IF EXISTS `visits` DROP FOREIGN KEY IF EXISTS `visits_subject_id_foreign`;
ALTER TABLE IF EXISTS `visits` DROP FOREIGN KEY IF EXISTS `visits_visitor_type_id_foreign`;
ALTER TABLE IF EXISTS `visits` DROP FOREIGN KEY IF EXISTS `visits_grade_id_foreign`;
ALTER TABLE IF EXISTS `visits` DROP FOREIGN KEY IF EXISTS `visits_section_id_foreign`;
ALTER TABLE IF EXISTS `visits` DROP FOREIGN KEY IF EXISTS `visits_level_id_foreign`;
ALTER TABLE IF EXISTS `visits` DROP FOREIGN KEY IF EXISTS `visits_academic_year_id_foreign`;

ALTER TABLE IF EXISTS `sections` DROP FOREIGN KEY IF EXISTS `sections_grade_id_foreign`;
ALTER TABLE IF EXISTS `grades` DROP FOREIGN KEY IF EXISTS `grades_level_id_foreign`;
ALTER TABLE IF EXISTS `teachers` DROP FOREIGN KEY IF EXISTS `teachers_school_id_foreign`;
ALTER TABLE IF EXISTS `subjects` DROP FOREIGN KEY IF EXISTS `subjects_school_id_foreign`;

-- إزالة المفاتيح الرئيسية
ALTER TABLE IF EXISTS `teachers` DROP PRIMARY KEY;
ALTER TABLE IF EXISTS `schools` DROP PRIMARY KEY;
ALTER TABLE IF EXISTS `subjects` DROP PRIMARY KEY;
ALTER TABLE IF EXISTS `visitor_types` DROP PRIMARY KEY;
ALTER TABLE IF EXISTS `grades` DROP PRIMARY KEY;
ALTER TABLE IF EXISTS `sections` DROP PRIMARY KEY;
ALTER TABLE IF EXISTS `educational_levels` DROP PRIMARY KEY;
ALTER TABLE IF EXISTS `academic_years` DROP PRIMARY KEY;
ALTER TABLE IF EXISTS `visits` DROP PRIMARY KEY;
ALTER TABLE IF EXISTS `visit_evaluations` DROP PRIMARY KEY;

-- حذف الجداول
DROP TABLE IF EXISTS `visit_evaluations`;
DROP TABLE IF EXISTS `visits`;
DROP TABLE IF EXISTS `academic_years`;
DROP TABLE IF EXISTS `teachers`;
DROP TABLE IF EXISTS `subjects`;
DROP TABLE IF EXISTS `sections`;
DROP TABLE IF EXISTS `grades`;
DROP TABLE IF EXISTS `educational_levels`;
DROP TABLE IF EXISTS `visitor_types`;
DROP TABLE IF EXISTS `schools`;

-- إنشاء الجداول من جديد
CREATE TABLE `schools` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `educational_levels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `grades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `level_id` (`level_id`),
  CONSTRAINT `grades_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `educational_levels` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `sections` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_id` int NOT NULL,
  `school_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `grade_id` (`grade_id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `sections_grade_id_foreign` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`),
  CONSTRAINT `sections_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `teachers` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `school_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `teachers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `subjects` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `school_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  CONSTRAINT `subjects_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `visitor_types` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `academic_years` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_term_start` date DEFAULT NULL,
  `first_term_end` date DEFAULT NULL,
  `second_term_start` date DEFAULT NULL,
  `second_term_end` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `visits` (
  `id` int NOT NULL AUTO_INCREMENT,
  `school_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `visitor_type_id` int NOT NULL,
  `visitor_person_id` int NOT NULL,
  `grade_id` int NOT NULL,
  `section_id` int NOT NULL,
  `level_id` int NOT NULL,
  `academic_year_id` int NOT NULL,
  `visit_date` date NOT NULL,
  `period_number` int NOT NULL,
  `lesson_title` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `school_id` (`school_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `subject_id` (`subject_id`),
  KEY `visitor_type_id` (`visitor_type_id`),
  KEY `visitor_person_id` (`visitor_person_id`),
  KEY `grade_id` (`grade_id`),
  KEY `section_id` (`section_id`),
  KEY `level_id` (`level_id`),
  KEY `academic_year_id` (`academic_year_id`),
  CONSTRAINT `visits_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  CONSTRAINT `visits_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  CONSTRAINT `visits_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `visits_visitor_type_id_foreign` FOREIGN KEY (`visitor_type_id`) REFERENCES `visitor_types` (`id`),
  CONSTRAINT `visits_grade_id_foreign` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`),
  CONSTRAINT `visits_section_id_foreign` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  CONSTRAINT `visits_level_id_foreign` FOREIGN KEY (`level_id`) REFERENCES `educational_levels` (`id`),
  CONSTRAINT `visits_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `visit_evaluations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visit_id` int NOT NULL,
  `indicator_id` int NOT NULL,
  `score` decimal(5,2) NOT NULL DEFAULT '0.00',
  `recommendation_id` int DEFAULT NULL,
  `notes` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `visit_id` (`visit_id`),
  KEY `indicator_id` (`indicator_id`),
  KEY `recommendation_id` (`recommendation_id`),
  CONSTRAINT `visit_evaluations_visit_id_foreign` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visit_evaluations_indicator_id_foreign` FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`),
  CONSTRAINT `visit_evaluations_recommendation_id_foreign` FOREIGN KEY (`recommendation_id`) REFERENCES `recommendations` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة بيانات المدرسة
INSERT INTO schools (name, created_at, updated_at) VALUES 
('مدرسة الأمل الابتدائية للبنين', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP); 