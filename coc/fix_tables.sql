-- إضافة المفاتيح الرئيسية أولاً

-- إضافة المفتاح الرئيسي لجدول schools
ALTER TABLE `schools`
ADD PRIMARY KEY (`id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول teachers
ALTER TABLE `teachers`
ADD PRIMARY KEY (`id`),
ADD KEY `school_id` (`school_id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول subjects
ALTER TABLE `subjects`
ADD PRIMARY KEY (`id`),
ADD KEY `school_id` (`school_id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول grades
ALTER TABLE `grades`
ADD PRIMARY KEY (`id`),
ADD KEY `level_id` (`level_id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول sections
ALTER TABLE `sections`
ADD PRIMARY KEY (`id`),
ADD KEY `grade_id` (`grade_id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول educational_levels
ALTER TABLE `educational_levels`
ADD PRIMARY KEY (`id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول academic_years
ALTER TABLE `academic_years`
ADD PRIMARY KEY (`id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول visitor_types
ALTER TABLE `visitor_types`
ADD PRIMARY KEY (`id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول teacher_subjects
ALTER TABLE `teacher_subjects`
ADD PRIMARY KEY (`id`),
ADD KEY `teacher_id` (`teacher_id`),
ADD KEY `subject_id` (`subject_id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول evaluation_domains
ALTER TABLE `evaluation_domains`
ADD PRIMARY KEY (`id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول evaluation_indicators
ALTER TABLE `evaluation_indicators`
ADD PRIMARY KEY (`id`),
ADD KEY `domain_id` (`domain_id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول recommendations
ALTER TABLE `recommendations`
ADD PRIMARY KEY (`id`),
ADD KEY `indicator_id` (`indicator_id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول visits
ALTER TABLE `visits`
ADD PRIMARY KEY (`id`),
ADD KEY `school_id` (`school_id`),
ADD KEY `teacher_id` (`teacher_id`),
ADD KEY `subject_id` (`subject_id`),
ADD KEY `visitor_type_id` (`visitor_type_id`),
ADD KEY `visitor_person_id` (`visitor_person_id`),
ADD KEY `grade_id` (`grade_id`),
ADD KEY `section_id` (`section_id`),
ADD KEY `level_id` (`level_id`),
ADD KEY `academic_year_id` (`academic_year_id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- إضافة المفتاح الرئيسي لجدول visit_evaluations
ALTER TABLE `visit_evaluations`
ADD PRIMARY KEY (`id`),
ADD KEY `visit_id` (`visit_id`),
ADD KEY `indicator_id` (`indicator_id`),
ADD KEY `recommendation_id` (`recommendation_id`),
MODIFY `id` int NOT NULL AUTO_INCREMENT;

-- تعديل جدول academic_years
ALTER TABLE `academic_years`
DROP COLUMN IF EXISTS `start_date`,
DROP COLUMN IF EXISTS `end_date`,
ADD COLUMN IF NOT EXISTS `first_term_start` date NULL AFTER `name`,
ADD COLUMN IF NOT EXISTS `first_term_end` date NULL AFTER `first_term_start`,
ADD COLUMN IF NOT EXISTS `second_term_start` date NULL AFTER `first_term_end`,
ADD COLUMN IF NOT EXISTS `second_term_end` date NULL AFTER `second_term_start`,
MODIFY COLUMN `is_active` tinyint(1) NOT NULL DEFAULT '0';

-- حذف الجداول إذا كانت موجودة
DROP TABLE IF EXISTS `visit_evaluations`;
DROP TABLE IF EXISTS `visits`;

-- إنشاء جدول visits
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

-- إنشاء جدول visit_evaluations
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