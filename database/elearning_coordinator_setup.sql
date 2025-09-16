-- إضافة دور منسق التعليم الإلكتروني
INSERT INTO `user_roles` (`id`, `name`, `display_name`, `description`, `permissions`, `created_at`, `updated_at`) VALUES
(7, 'E-Learning Coordinator', 'منسق التعليم الإلكتروني', 'صلاحيات إدارة حضور التعليم الإلكتروني ومتابعة أداء المعلمين على نظام قطر للتعليم', '{"elearning_attendance": true, "qatar_system_monitoring": true, "view_reports": true, "manage_attendance": true}', NOW(), NOW());

-- جدول حضور التعليم الإلكتروني
CREATE TABLE `elearning_attendance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `academic_year_id` int NOT NULL,
  `term` enum('first','second') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `class_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lesson_topic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lesson_time` time NOT NULL,
  `lesson_duration` int NOT NULL DEFAULT 45 COMMENT 'مدة الحصة بالدقائق',
  `attendance_type` enum('live','recorded','interactive') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `platform_used` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'المنصة المستخدمة',
  `total_students` int NOT NULL,
  `present_students` int NOT NULL,
  `absent_students` int NOT NULL,
  `interaction_level` enum('high','medium','low') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `technical_issues` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_by` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `academic_year_id` (`academic_year_id`),
  KEY `subject_id` (`subject_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `created_by` (`created_by`),
  KEY `date_index` (`date`),
  CONSTRAINT `elearning_attendance_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  CONSTRAINT `elearning_attendance_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `elearning_attendance_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  CONSTRAINT `elearning_attendance_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول معايير أداء نظام قطر للتعليم
CREATE TABLE `qatar_system_criteria` (
  `id` int NOT NULL AUTO_INCREMENT,
  `category` enum('lesson_building','assessment_management') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criterion_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج معايير بناء الدروس
INSERT INTO `qatar_system_criteria` (`category`, `criterion_name`, `description`, `sort_order`) VALUES
('lesson_building', 'إضافة الدروس وفقاً للخطة الفصلية', 'إضافة الدروس بما يتوافق مع الخطة الفصلية للمادة، بحيث يكون لكل حصة دراسية درس خاص بها على النظام', 1),
('lesson_building', 'كتابة عنوان الدرس واختيار التاريخ والحصة', 'كتابة عنوان الدرس واختيار التاريخ والحصة الدراسية بشكل صحيح', 2),
('lesson_building', 'إضافة أهداف الدرس', 'إضافة أهداف الدرس بشكل مختصر في خانة الوصف في بطاقة الدرس', 3),
('lesson_building', 'رفع صورة معبرة', 'رفع صورة معبرة عن الدرس في بطاقة الدرس، ووضعها بدلاً من الصورة التلقائية', 4),
('lesson_building', 'إضافة الدرس قبل الموعد', 'إضافة الدرس قبل موعد الدرس بيومين على الأقل', 5),
('lesson_building', 'الالتزام بإعدادات الدروس', 'الالتزام بإعدادات الدروس وعدم تغييرها، والتأكد بأن جميع الدروس مرتبة زمنياً من الأقدم إلى الأحدث', 6),
('lesson_building', 'ربط الدروس بنتاجات التعلم', 'ربط جميع الدروس بنتاجات التعلم المناسبة', 7),
('lesson_building', 'تفعيل تلعيب التعلم', 'تفعيل "تلعيب التعلم" بتفعيل لعبة "نجوم المادة" ومنحهم النقاط والشارات والشهادات', 8),
('lesson_building', 'العرض التقديمي المناسب', 'العرض التقديمي مناسب للدرس وأهدافه وموجه للطالب ويتناسب مع أسلوب التعلم الذاتي', 9),
('lesson_building', 'إضافة فيديو للدرس', 'فيديو للدرس من الدروس المصورة الموجودة في مكتبة المصادر في حال توفره', 10),
('lesson_building', 'مصادر التعلم الرقمية', 'مصادر تعلم رقمية (ورقة عمل تفاعلية – رابط ويب – لعبة تفاعلية – فيديو إثرائي...إلخ) لكل هدف', 11),
('lesson_building', 'إرفاق خطة الدرس', 'إرفاق خطة الدرس من تبويب ملاحظات الدرس', 12),

('assessment_management', 'التقييم الختامي', 'تقييم ختامي واحد، يغطي أهداف الدرس ويكون من فئة التقييمات البنائية / الختامية', 1),
('assessment_management', 'تحديد تواريخ التقييم', 'تحديد تاريخ البدء، وتاريخ الاستحقاق، وفئة التقييم المناسبة', 2),
('assessment_management', 'ربط التقييمات بنتاجات التعلم', 'ربط التقييمات بنتاجات التعلم المناسبة', 3),
('assessment_management', 'تصحيح التقييمات في الوقت المحدد', 'تصحيح التقييمات خلال 3 أيام من تاريخ الاستحقاق', 4),
('assessment_management', 'إنشاء بنوك الأسئلة', 'إنشاء بنوك الأسئلة لجميع الوحدات بتسميات واضحة ومشاركتها في مكتبة المدرسة', 5),
('assessment_management', 'تفعيل تلعيب التعلم في التقييمات', 'تفعيل "تلعيب التعلم" بتفعيل لعبة "نجوم المادة" ومنحهم النقاط والشارات والشهادات', 6),
('assessment_management', 'متابعة تحليلات الصف', 'الاطلاع على تحليلات الصف ودفتر الدرجات والتقييمات لمتابعة أداء الطلبة ومدى تقدمهم', 7),
('assessment_management', 'إدخال الجدول الدراسي', 'إدخال الجدول الدراسي لكل معلم وتحديثه وفقاً لمتغيرات الجدول المدرسي', 8);

-- جدول تقييم أداء المعلمين على نظام قطر
CREATE TABLE `qatar_system_performance` (
  `id` int NOT NULL AUTO_INCREMENT,
  `academic_year_id` int NOT NULL,
  `term` enum('first','second') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `teacher_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `evaluation_date` date NOT NULL,
  `evaluator_id` int NOT NULL COMMENT 'منسق التعليم الإلكتروني',
  `criteria_scores` json NOT NULL COMMENT 'درجات المعايير - مصفوفة {criterion_id: score}',
  `total_score` decimal(5,2) NOT NULL,
  `performance_level` enum('excellent','very_good','good','needs_improvement','poor') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `strengths` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `improvement_areas` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recommendations` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `follow_up_date` date DEFAULT NULL,
  `notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `academic_year_id` (`academic_year_id`),
  KEY `teacher_id` (`teacher_id`),
  KEY `subject_id` (`subject_id`),
  KEY `evaluator_id` (`evaluator_id`),
  KEY `evaluation_date_index` (`evaluation_date`),
  CONSTRAINT `qatar_system_performance_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  CONSTRAINT `qatar_system_performance_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  CONSTRAINT `qatar_system_performance_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  CONSTRAINT `qatar_system_performance_ibfk_4` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إنشاء فهرس مركب لتجنب التقييمات المكررة
ALTER TABLE `qatar_system_performance` ADD UNIQUE KEY `unique_evaluation` (`teacher_id`, `subject_id`, `academic_year_id`, `term`, `evaluation_date`);
