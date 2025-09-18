-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 18, 2025 at 04:15 AM
-- Server version: 9.1.0
-- PHP Version: 8.3.16

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `classvisit_db`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `safe_delete_subject` (IN `subject_id` INT, OUT `can_delete` BOOLEAN, OUT `message` VARCHAR(255))   BEGIN
    DECLARE visit_count INT;
    DECLARE teacher_count INT;
    
    -- التحقق من وجود زيارات مرتبطة بالمادة
    SELECT COUNT(*) INTO visit_count FROM visits WHERE subject_id = subject_id;
    
    -- التحقق من وجود معلمين مرتبطين بالمادة
    SELECT COUNT(*) INTO teacher_count FROM teacher_subjects WHERE subject_id = subject_id;
    
    IF visit_count > 0 THEN
        SET can_delete = FALSE;
        SET message = 'لا يمكن حذف هذه المادة لأنها مستخدمة في سجلات الزيارات';
    ELSEIF teacher_count > 0 THEN
        SET can_delete = FALSE;
        SET message = 'لا يمكن حذف هذه المادة لأنها مرتبطة بمعلمين';
    ELSE
        -- يمكن حذف المادة بأمان
        DELETE FROM subjects WHERE id = subject_id;
        SET can_delete = TRUE;
        SET message = 'تم حذف المادة بنجاح';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `update_subject` (IN `p_subject_id` INT, IN `p_name` VARCHAR(255), IN `p_school_id` INT, IN `p_is_school_specific` TINYINT(1), IN `p_updated_by` INT, OUT `success` BOOLEAN, OUT `message` VARCHAR(255))   BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        SET success = FALSE;
        SET message = 'حدث خطأ أثناء تحديث المادة';
        ROLLBACK;
    END;

    START TRANSACTION;
    
    IF p_is_school_specific = 0 THEN
        SET p_school_id = NULL;
    END IF;

    UPDATE subjects 
    SET 
        name = p_name,
        school_id = p_school_id,
        is_school_specific = p_is_school_specific,
        updated_by = p_updated_by,
        updated_at = CURRENT_TIMESTAMP
    WHERE id = p_subject_id;

    SET success = TRUE;
    SET message = 'تم تحديث المادة بنجاح';
    
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_term_start` date DEFAULT NULL,
  `first_term_end` date DEFAULT NULL,
  `second_term_start` date DEFAULT NULL,
  `second_term_end` date DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `is_current` tinyint(1) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `name`, `first_term_start`, `first_term_end`, `second_term_start`, `second_term_end`, `is_active`, `created_at`, `updated_at`, `is_current`) VALUES
(2, '2025/2026', '2025-08-24', '2025-12-30', '2026-01-01', '2026-06-30', 1, '2025-05-16 06:53:05', '2025-09-05 18:58:38', 0);

-- --------------------------------------------------------

--
-- Table structure for table `coordinator_supervisors`
--

CREATE TABLE `coordinator_supervisors` (
  `id` int NOT NULL,
  `user_id` int NOT NULL COMMENT 'معرف المستخدم (منسق المادة)',
  `subject_id` int NOT NULL COMMENT 'معرف المادة',
  `school_id` int DEFAULT NULL COMMENT 'معرف المدرسة',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `coordinator_supervisors`
--

INSERT INTO `coordinator_supervisors` (`id`, `user_id`, `subject_id`, `school_id`, `created_at`, `updated_at`) VALUES
(20, 240, 3, NULL, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(21, 249, 8, NULL, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(22, 254, 9, NULL, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(23, 263, 6, NULL, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(24, 267, 5, NULL, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(25, 271, 7, NULL, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(26, 276, 2, NULL, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(27, 284, 1, NULL, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(28, 292, 11, NULL, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(29, 295, 16, NULL, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(30, 300, 19, NULL, '2025-09-05 18:31:46', '2025-09-05 18:31:46');

-- --------------------------------------------------------

--
-- Table structure for table `educational_levels`
--

CREATE TABLE `educational_levels` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `educational_levels`
--

INSERT INTO `educational_levels` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'الابتدائية', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(2, 'الإعدادية', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(3, 'الثانوية', '2025-05-14 17:48:51', '2025-05-14 17:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `elearning_attendance`
--

CREATE TABLE `elearning_attendance` (
  `id` int NOT NULL,
  `academic_year_id` int NOT NULL,
  `school_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `grade_id` int NOT NULL,
  `section_id` int NOT NULL,
  `lesson_date` date NOT NULL,
  `lesson_number` int NOT NULL,
  `num_students` int NOT NULL DEFAULT '0',
  `attendance_students` int NOT NULL DEFAULT '0',
  `attendance_type` enum('direct','remote') COLLATE utf8mb4_general_ci DEFAULT 'direct',
  `elearning_tools` json DEFAULT NULL,
  `lesson_topic` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `attendance_rating` enum('excellent','very_good','good','acceptable','poor') COLLATE utf8mb4_general_ci DEFAULT 'poor',
  `coordinator_id` int NOT NULL,
  `notes` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `elearning_attendance`
--

INSERT INTO `elearning_attendance` (`id`, `academic_year_id`, `school_id`, `subject_id`, `teacher_id`, `grade_id`, `section_id`, `lesson_date`, `lesson_number`, `num_students`, `attendance_students`, `attendance_type`, `elearning_tools`, `lesson_topic`, `attendance_rating`, `coordinator_id`, `notes`, `created_at`, `updated_at`) VALUES
(4, 2, 1, 16, 394, 12, 18, '2025-09-09', 1, 25, 14, 'direct', '[\"qatar_system\", \"tablets\", \"interactive_display\", \"interactive_websites\"]', 'jhgfdedwq', 'excellent', 325, 'kiujytrewdwsq', '2025-09-09 09:10:23', '2025-09-09 09:10:23'),
(5, 2, 1, 18, 398, 12, 15, '2025-09-09', 2, 25, 16, 'direct', '[\"qatar_system\", \"tablets\", \"interactive_display\", \"ai_applications\", \"interactive_websites\"]', 'تفقثس سثق سيلس يل', 'excellent', 325, 'بيل سيلسيل', '2025-09-09 09:22:16', '2025-09-09 09:22:16'),
(6, 2, 1, 3, 340, 10, 14, '2025-09-09', 3, 33, 25, 'direct', '[\"interactive_display\"]', 'awj,dg kaushdsakjgh', 'acceptable', 325, 'ksdjg fksjdghfkshdg', '2025-09-09 10:09:12', '2025-09-09 10:09:12'),
(7, 2, 1, 4, 361, 10, 4, '2025-09-10', 1, 30, 25, 'direct', '[\"qatar_system\", \"tablets\", \"interactive_websites\"]', 'fu hskuyf gidsukyf gdsukyhf', 'very_good', 325, 'مهع بلخيعغلبيعنغلبيعغبل نيسعغب لعيسغلب عسي', '2025-09-10 04:15:53', '2025-09-10 05:00:37');

-- --------------------------------------------------------

--
-- Table structure for table `elearning_attendance_old`
--

CREATE TABLE `elearning_attendance_old` (
  `id` int NOT NULL,
  `academic_year_id` int NOT NULL,
  `term` enum('first','second') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `date` date NOT NULL,
  `subject_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `class_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lesson_topic` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `lesson_time` time NOT NULL,
  `lesson_duration` int NOT NULL DEFAULT '45' COMMENT 'مدة الحصة بالدقائق',
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_domains`
--

CREATE TABLE `evaluation_domains` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `weight` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_domains`
--

INSERT INTO `evaluation_domains` (`id`, `name`, `name_en`, `description`, `description_en`, `weight`, `created_at`, `updated_at`, `sort_order`) VALUES
(1, 'التخطيط للدرس', 'Lesson Planning', NULL, NULL, 100.00, '2025-05-14 17:48:51', '2025-09-17 14:40:47', 1),
(2, 'تنفيذ الدرس', 'Lesson Delivery', NULL, NULL, 100.00, '2025-05-14 17:48:51', '2025-09-17 14:40:47', 2),
(3, 'التقويم', 'Assessment', NULL, NULL, 100.00, '2025-05-14 17:48:51', '2025-09-17 14:40:47', 3),
(4, 'الإدارة الصفية وبيئة التعلم', 'Classroom Management and Learning Environment', NULL, NULL, 100.00, '2025-05-14 17:48:51', '2025-09-17 14:40:47', 4),
(5, 'جزء خاص بمادة العلوم (النشاط العملي)', 'Science Lab Activities', NULL, NULL, 100.00, '2025-05-14 17:48:51', '2025-09-17 14:40:47', 5);

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_indicators`
--

CREATE TABLE `evaluation_indicators` (
  `id` int NOT NULL,
  `domain_id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `description_en` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `weight` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sort_order` int DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_indicators`
--

INSERT INTO `evaluation_indicators` (`id`, `domain_id`, `name`, `name_en`, `description`, `description_en`, `weight`, `created_at`, `updated_at`, `sort_order`) VALUES
(1, 1, 'خطة الدرس متوفرة وبنودها مستكملة ومناسبة.', 'The lesson plan is available and its items are complete and appropriate.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 1),
(2, 1, 'أهداف التعلم مناسبة ودقيقة الصياغة وقابلة للقياس.', 'Learning objectives are appropriate, precisely formulated and measurable.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 2),
(3, 1, 'أنشطة الدرس الرئيسة واضحة ومتدرجة ومرتبطة بالأهداف.', 'The main lesson activities are clear, progressive and linked to objectives.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 3),
(4, 2, 'أهداف التعلم معروضة ويتم مناقشتها.', 'Learning objectives are presented and discussed.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 4),
(5, 2, 'أنشطة التمهيد مفعلة بشكل مناسب.', 'Warm-up activities are appropriately activated.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 5),
(6, 2, 'محتوى الدرس واضح والعرض منظّم ومترابط.', 'The lesson content is clear and the presentation is organized and coherent.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 6),
(7, 2, 'طرائق التدريس وإستراتيجياته متنوعه وتتمحور حول الطالب.', 'Teaching methods and strategies are varied and student-centered.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 7),
(8, 2, 'مصادر التعلم الرئيسة والمساندة موظّفة بصورة واضحة وسليمة.', 'The main and supporting learning resources are clearly and properly utilized.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 8),
(9, 2, 'الوسائل التعليميّة والتكنولوجيا موظّفة بصورة مناسبة.', 'Educational aids and technology are appropriately utilized.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 9),
(10, 2, 'الأسئلة الصفية ذات صياغة سليمة ومتدرجة ومثيرة للتفكير.', 'Classroom questions are properly formulated, progressive and thought-provoking.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 10),
(11, 2, 'المادة العلمية دقيقة و مناسبة.', 'The scientific material is accurate and appropriate.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 11),
(12, 2, 'الكفايات الأساسية متضمنة في السياق المعرفي للدرس.', 'Basic competencies are included in the cognitive context of the lesson.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 12),
(13, 2, 'القيم الأساسية متضمنة في السياق المعرفي للدرس.', 'Basic values are included in the cognitive context of the lesson.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 13),
(14, 2, 'التكامل بين محاور المادة ومع المواد الأخرى يتم بشكل مناسب.', 'Integration between subject areas and with other subjects is done appropriately.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 14),
(15, 2, 'الفروق الفردية بين الطلبة يتم مراعاتها.', 'Individual differences among students are taken into account.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 15),
(16, 2, 'غلق الدرس يتم بشكل مناسب.', 'The closure task is appropriate.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 16),
(17, 3, 'أساليب التقويم (القبلي والبنائي والختامي) مناسبة ومتنوعة.', 'The lesson incorporates a variety of assessment tools.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 17),
(18, 3, 'التغذية الراجعة متنوعة ومستمرة.', 'Monitoring of students performance is on-going and appropriate, and timely feedback is provided.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 18),
(19, 3, 'أعمال الطلبة متابعة ومصححة بدقة ورقيًا وإلكترونيًا.', 'Students work is checked in an appropriate and timely manner (both on paper and On Teams).', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 19),
(20, 4, 'البيئة الصفية إيجابية وآمنة وداعمة للتعلّم.', 'Creates safe, supportive and challenging learning environment.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 20),
(21, 4, 'إدارة أنشطة التعلّم والمشاركات الصّفيّة تتم بصورة منظمة.', 'Learning activities and student participation are well managed.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 21),
(22, 4, 'قوانين إدارة الصف وإدارة السلوك مفعّلة.', 'Classroom and behaviour rules are enforced.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 22),
(23, 4, 'الاستثمار الأمثل لزمن الحصة.', 'Time is well exploited throughout the lesson.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 23),
(24, 5, 'مدى صلاحية و توافر الأدوات اللازمة لتنفيذ النشاط العملي وبكميات مناسبة.', 'Availability and suitability of tools needed for practical activities in appropriate quantities.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 24),
(25, 5, 'شرح اجراءات الأمن والسلامة المناسبة للتجربة ومتابعة تفعيلها.', 'Explaining appropriate safety and security procedures for the experiment and monitoring their implementation.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 25),
(26, 5, 'اعطاء تعليمات واضحة وسليمة لأداء النشاط العملي قبل وأثناء التنفيذ.', 'Giving clear and correct instructions for performing practical activities before and during implementation.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 26),
(27, 5, 'تسجيل الطلبة للملاحظات والنتائج أثناء تنفيذ النشاط العملي.', 'Students recording observations and results during practical activity implementation.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 27),
(28, 5, 'تقويم مهارات الطلبة أثناء تنفيذ النشاط العملي.', 'Evaluating students skills during practical activity implementation.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 28),
(29, 5, 'تنويع أساليب تقديم التغذية الراجعة للطلبة لتنمية مهاراتهم.', 'Diversifying feedback methods for students to develop their skills.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 29);

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `name`, `level_id`, `created_at`, `updated_at`) VALUES
(1, 'الصف الأول', 1, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(2, 'الصف الثاني', 1, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(3, 'الصف الثالث', 1, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(4, 'الصف الرابع', 1, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(5, 'الصف الخامس', 1, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(6, 'الصف السادس', 1, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(7, 'الصف السابع', 2, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(8, 'الصف الثامن', 2, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(9, 'الصف التاسع', 2, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(10, 'الصف العاشر', 3, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(11, 'الصف الحادي عشر', 3, '2025-05-14 14:48:51', '2025-05-14 14:48:51'),
(12, 'الصف الثاني عشر', 3, '2025-05-14 14:48:51', '2025-05-14 14:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `qatar_system_criteria`
--

CREATE TABLE `qatar_system_criteria` (
  `id` int NOT NULL,
  `category` enum('lesson_building','assessment_management') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `criterion_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `sort_order` int NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `qatar_system_criteria`
--

INSERT INTO `qatar_system_criteria` (`id`, `category`, `criterion_name`, `description`, `sort_order`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'lesson_building', 'إضافة الدروس وفقاً للخطة الفصلية', 'إضافة الدروس بما يتوافق مع الخطة الفصلية للمادة، بحيث يكون لكل حصة دراسية درس خاص بها على النظام', 1, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(2, 'lesson_building', 'كتابة عنوان الدرس واختيار التاريخ والحصة', 'كتابة عنوان الدرس واختيار التاريخ والحصة الدراسية بشكل صحيح', 2, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(3, 'lesson_building', 'إضافة أهداف الدرس', 'إضافة أهداف الدرس بشكل مختصر في خانة الوصف في بطاقة الدرس', 3, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(4, 'lesson_building', 'رفع صورة معبرة', 'رفع صورة معبرة عن الدرس في بطاقة الدرس، ووضعها بدلاً من الصورة التلقائية', 4, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(5, 'lesson_building', 'إضافة الدرس قبل الموعد', 'إضافة الدرس قبل موعد الدرس بيومين على الأقل', 5, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(6, 'lesson_building', 'الالتزام بإعدادات الدروس', 'الالتزام بإعدادات الدروس وعدم تغييرها، والتأكد بأن جميع الدروس مرتبة زمنياً من الأقدم إلى الأحدث', 6, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(7, 'lesson_building', 'ربط الدروس بنتاجات التعلم', 'ربط جميع الدروس بنتاجات التعلم المناسبة', 7, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(8, 'lesson_building', 'تفعيل تلعيب التعلم', 'تفعيل \"تلعيب التعلم\" بتفعيل لعبة \"نجوم المادة\" ومنحهم النقاط والشارات والشهادات', 8, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(9, 'lesson_building', 'العرض التقديمي المناسب', 'العرض التقديمي مناسب للدرس وأهدافه وموجه للطالب ويتناسب مع أسلوب التعلم الذاتي', 9, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(10, 'lesson_building', 'إضافة فيديو للدرس', 'فيديو للدرس من الدروس المصورة الموجودة في مكتبة المصادر في حال توفره', 10, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(11, 'lesson_building', 'مصادر التعلم الرقمية', 'مصادر تعلم رقمية (ورقة عمل تفاعلية – رابط ويب – لعبة تفاعلية – فيديو إثرائي...إلخ) لكل هدف', 11, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(12, 'lesson_building', 'إرفاق خطة الدرس', 'إرفاق خطة الدرس من تبويب ملاحظات الدرس', 12, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(13, 'assessment_management', 'التقييم الختامي', 'تقييم ختامي واحد، يغطي أهداف الدرس ويكون من فئة التقييمات البنائية / الختامية', 1, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(14, 'assessment_management', 'تحديد تواريخ التقييم', 'تحديد تاريخ البدء، وتاريخ الاستحقاق، وفئة التقييم المناسبة', 2, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(15, 'assessment_management', 'ربط التقييمات بنتاجات التعلم', 'ربط التقييمات بنتاجات التعلم المناسبة', 3, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(16, 'assessment_management', 'تصحيح التقييمات في الوقت المحدد', 'تصحيح التقييمات خلال 3 أيام من تاريخ الاستحقاق', 4, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(17, 'assessment_management', 'إنشاء بنوك الأسئلة', 'إنشاء بنوك الأسئلة لجميع الوحدات بتسميات واضحة ومشاركتها في مكتبة المدرسة', 5, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(18, 'assessment_management', 'تفعيل تلعيب التعلم في التقييمات', 'تفعيل \"تلعيب التعلم\" بتفعيل لعبة \"نجوم المادة\" ومنحهم النقاط والشارات والشهادات', 6, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(19, 'assessment_management', 'متابعة تحليلات الصف', 'الاطلاع على تحليلات الصف ودفتر الدرجات والتقييمات لمتابعة أداء الطلبة ومدى تقدمهم', 7, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13'),
(20, 'assessment_management', 'إدخال الجدول الدراسي', 'إدخال الجدول الدراسي لكل معلم وتحديثه وفقاً لمتغيرات الجدول المدرسي', 8, 1, '2025-09-09 07:01:13', '2025-09-09 07:01:13');

-- --------------------------------------------------------

--
-- Table structure for table `qatar_system_performance`
--

CREATE TABLE `qatar_system_performance` (
  `id` int NOT NULL,
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
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `qatar_system_performance`
--

INSERT INTO `qatar_system_performance` (`id`, `academic_year_id`, `term`, `teacher_id`, `subject_id`, `evaluation_date`, `evaluator_id`, `criteria_scores`, `total_score`, `performance_level`, `strengths`, `improvement_areas`, `recommendations`, `follow_up_date`, `notes`, `created_at`, `updated_at`) VALUES
(1, 2, 'first', 343, 3, '2025-09-09', 325, '{\"1\": 1, \"2\": 5, \"3\": 5, \"4\": 5, \"5\": 5, \"6\": 5, \"7\": 5, \"8\": 3, \"9\": 5, \"10\": 1, \"11\": 5, \"12\": 5, \"13\": 5, \"14\": 5, \"15\": 2, \"16\": 4, \"17\": 1, \"18\": 5, \"19\": 5, \"20\": 5}', 4.10, 'very_good', 'فغقافقثصضي', 'عةفغقثبيص', 'فغقالاثبصي', '2025-09-30', 'ف7تتا6فلقبيصض', '2025-09-09 07:18:07', '2025-09-09 07:18:07'),
(3, 2, 'first', 396, 16, '2025-09-09', 325, '{\"1\": 5, \"2\": 5, \"3\": 5, \"4\": 5, \"5\": 1, \"6\": 5, \"7\": 1, \"8\": 5, \"9\": 5, \"10\": 1, \"11\": 5, \"12\": 1, \"13\": 5, \"14\": 5, \"15\": 5, \"16\": 5, \"17\": 5, \"18\": 4, \"19\": 5, \"20\": 4}', 4.10, 'very_good', 'عغغىللابريؤس', 'تلابلايؤس', 'البلايؤس', '2025-09-30', 'بىلفلايسؤءش', '2025-09-09 07:28:45', '2025-09-10 04:43:02'),
(4, 2, 'first', 334, 1, '2024-12-19', 325, '{\"1\": 4, \"2\": 5, \"3\": 3}', 4.00, 'good', 'معلم متميز', 'يحتاج تطوير', 'المتابعة', NULL, 'ملاحظات', '2025-09-09 07:29:21', '2025-09-09 07:29:21'),
(5, 2, 'first', 389, 4, '2025-09-09', 325, '{\"1\": 1, \"2\": 2, \"3\": 3, \"4\": 4, \"5\": 5, \"6\": 4, \"7\": 3, \"8\": 2, \"9\": 1, \"10\": 3, \"11\": 4, \"12\": 5, \"13\": 5, \"14\": 4, \"15\": 3, \"16\": 2, \"17\": 1, \"18\": 2, \"19\": 3, \"20\": 5}', 3.10, 'good', 'خمعهغنتفقلثصبيض', 'مزخهنغافلثصيسض', 'زتنوتةاغىلقبثيصس', '2025-09-30', 'اتلبلايرؤسءش', '2025-09-09 09:24:25', '2025-09-09 09:24:25'),
(6, 2, 'first', 342, 3, '2025-09-09', 325, '{\"1\": 5, \"2\": 4, \"3\": 3, \"4\": 2, \"5\": 1, \"6\": 2, \"7\": 3, \"8\": 4, \"9\": 5, \"10\": 4, \"11\": 3, \"12\": 2, \"13\": 1, \"14\": 2, \"15\": 3, \"16\": 4, \"17\": 5, \"18\": 4, \"19\": 3, \"20\": 2}', 3.10, 'good', '7jythgrfedw', 'jgmnhytbrvedw', 'jmunhygtvrfdws', '2025-09-09', 'hnfgbdvcsgf ndsvcs', '2025-09-09 09:36:05', '2025-09-09 09:36:05'),
(7, 2, 'first', 395, 16, '2025-09-09', 325, '{\"1\": 2, \"2\": 2, \"3\": 3, \"4\": 4, \"5\": 2, \"6\": 4, \"7\": 3, \"8\": 2, \"9\": 1, \"10\": 2, \"11\": 3, \"12\": 1, \"13\": 2, \"14\": 2, \"15\": 1, \"16\": 2, \"17\": 1, \"18\": 2, \"19\": 1, \"20\": 1}', 2.05, 'needs_improvement', 'sjayd tkjastydruasytutasd', 'df hhzfkudfyatkudsytkuays', 'sm htsjdtasfjdtyfjash', '2025-09-30', 'cvxjkhcxedgf kuxdy', '2025-09-09 10:10:24', '2025-09-10 04:41:46'),
(8, 2, 'first', 361, 4, '2025-09-10', 325, '{\"1\": 5, \"2\": 4, \"3\": 3, \"4\": 4, \"5\": 4, \"6\": 5, \"7\": 1, \"8\": 5, \"9\": 5, \"10\": 3, \"11\": 5, \"12\": 3, \"13\": 4, \"14\": 5, \"15\": 5, \"16\": 5, \"17\": 5, \"18\": 5, \"19\": 5, \"20\": 4}', 4.25, 'very_good', 'عشيصب غشفسيبتنشسفغبيشس', 'شسينعغب شسعغي بشس - شسنعفيب شغسفيشس - سهخ عغلعسشغ', 'شسغفي بهغشسف يبتغالشس - مس عغؤؤفهشسغعفيهغشس - شسع غيفهعشغسي', '2025-10-10', 'سهيعب سهعفيشسهع غي عهشسلغي', '2025-09-10 04:16:51', '2025-09-10 04:29:24'),
(9, 2, 'first', 365, 6, '2025-09-17', 325, '{\"1\": 1, \"2\": 3, \"3\": 4, \"4\": 3, \"5\": 4, \"6\": 4, \"7\": 4, \"8\": 3, \"9\": 4, \"10\": 4, \"11\": 3, \"12\": 5, \"13\": 5, \"14\": 1, \"15\": 4, \"16\": 4, \"17\": 4, \"18\": 3, \"19\": 1, \"20\": 3}', 3.35, 'good', 'سلخ يعبغليسعنبغلنسيعت', 'ع سغلب ه7يغبهيغعب', 'يسبعغ سلاعيه بغفعغيسب', '2025-09-17', 'يلب يسليبل يبل يبل يبلبيل', '2025-09-17 07:37:34', '2025-09-17 07:37:34');

-- --------------------------------------------------------

--
-- Table structure for table `recommendations`
--

CREATE TABLE `recommendations` (
  `id` int NOT NULL,
  `indicator_id` int NOT NULL,
  `text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `text_en` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `sort_order` int DEFAULT '1'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recommendations`
--

INSERT INTO `recommendations` (`id`, `indicator_id`, `text`, `text_en`, `created_at`, `updated_at`, `sort_order`) VALUES
(239, 1, 'يجب توفّر الخطّة على نظام قطر للتعليم.', 'The lesson plan should be available on Qatar Education System.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(240, 1, 'يجب اتساق الخطّة زمنيا مع الخطة الفصلية.', 'The lesson plan should be consistent with the semester plan.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(241, 1, 'يجب أن تكون الخطّة مكتوبة بلغة سليمة وتتسم بالدقة والوضوح.', 'The lesson plan should be written in proper language and characterized by accuracy and clarity.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(242, 1, 'يجب أن تتوافر التهيئة في الخطّة وأن تكون مرتبطة بموضوع الدرس وأهدافه.', 'The lesson plan should include warm-up activities and be linked to the lesson topic and objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(243, 1, 'يجب أن تتنوّع في الخطّة طرائق التدريس وإستراتيجياته.', 'The lesson plan should include diverse teaching methods and strategies.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(244, 1, 'يجب أن يحدّد المعلم في الخطّة التكامل مع المواد الأخرى بشكل واضح ومناسب.', 'The teacher should clearly and appropriately specify integration with other subjects in the lesson plan.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 6),
(245, 1, 'يجب أن يكون غلق الدرس في الخطّة مناسبا للموضوع والأهداف.', 'The lesson closure in the plan should be appropriate for the topic and objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 7),
(246, 1, 'يجب اختيار طرائق تقييم مناسبة ومتنوعة في الخطّة.', 'Appropriate and diverse assessment methods should be chosen in the lesson plan.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 8),
(247, 1, 'يجب استخدام النموذج المحدث للخطة', 'The updated lesson plan template should be used.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 9),
(248, 2, 'يجب أن تكون الأهداف مرتبطة بموضوع الدرس بما يتضمنه من معارف ومهارات.', 'Learning objectives should be linked to the lesson topic including knowledge and skills.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(249, 2, 'يجب أن تكون الأهداف مصاغة بطريقة إجرائية سليمة وواضحة.', 'Learning objectives should be formulated in a proper and clear procedural manner.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(250, 2, 'يجب أن تراعي الأهداف التنوع بين المستويات المعرفية والمهارية.', 'Learning objectives should consider diversity between cognitive and skill levels.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(251, 2, 'يجب أن تكون الأهداف قابلة للقياس.', 'Learning objectives should be measurable.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(252, 3, 'يجب أن تكون الأنشطة مرتبطة بأهداف الدرس وتساعد على تحقيقها.', 'Activities should be linked to lesson objectives and help achieve them.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(253, 3, 'يجب أن تراعي الأنشطة التدرج والتسلسل في تحقيق أهداف الدرس.', 'Activities should consider progression and sequence in achieving lesson objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(254, 3, 'يجب أن توضّح الأنشطة الرئيسة دور كل من المعلم والطالب.', 'Main activities should clearly specify the role of both teacher and student.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(255, 3, 'يجب إن تعزّز الأنشطة الرئيسة الكفايات والقيم الأساسية ضمن السياق المعرفي.', 'Main activities should enhance core competencies and values within the cognitive context.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(256, 3, 'يجب أن تراعي الأنشطة بوضوح الفروق الفردية بين الطلبة.', 'Activities should clearly consider individual differences among students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(257, 3, 'يجب أن يكون التوزيع الزمني محدد ومناسب للأنشطة.', 'Time allocation should be specified and appropriate for activities.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 6),
(258, 3, 'يجب توضيح آلية توظيف أدوات التكنولوجيا في دور المعلم و المتعلم.', 'The mechanism for employing technology tools in teacher and learner roles should be clarified.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 7),
(259, 4, 'يجب أن يستعرض المعلم أهداف الدرس المخطط لها بداية درسه بصورة واضحة ومناسبة.', 'The teacher should clearly and appropriately present the planned lesson objectives at the beginning of the lesson.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(260, 4, 'يجب التحقق من وضوح أهداف الدرس لدى الطلبة.', 'The clarity of lesson objectives among students should be verified.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(261, 4, 'يجب أن تكون الأهداف كما هي مدونة في الخطة', 'Objectives should be as documented in the lesson plan.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(262, 5, 'يجب تنفيذ أنشطة التمهيد بطريقة جاذبة وشائقة.', 'Warm-up activities should be implemented in an attractive and interesting way.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(263, 5, 'يجب أن تكون أنشطة التمهيد ممهّدة للأنشطة الرئيسة وترتبط بها.', 'Warm-up activities should prepare for main activities and be linked to them.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(264, 5, 'يجب تنفيذ أنشطة التمهيد ضمن الزمن المحدد لها.', 'Warm-up activities should be implemented within the allocated time.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(265, 5, 'يجب أن ترتبط أنشطة التمهيد بخبرات الطلبة الحياتية وتجاربهم السابقة.', 'Warm-up activities should be linked to students\' life experiences and previous knowledge.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(266, 6, 'يجب أن يكون محتوى الدرس معروضا بطريقة واضحة.', 'Lesson content should be presented in a clear manner.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(267, 6, 'يجب أن يكون المحتوى مقدّما بصورة متدرجة ومنظمة وبأمثلة كافية.', 'Content should be presented progressively and organized with sufficient examples.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(268, 6, 'يجب أن تكون خطوات تنفيذ الدرس مترابطة ومتصلة بالأهداف.', 'Lesson implementation steps should be interconnected and connected to objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(269, 6, 'يجب ارتباط المحتوى بالبيئة والخبرات الحياتية.', 'Content should be linked to environment and life experiences.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(270, 7, 'يجب تطبيق إستراتيجيات تدريس تناسب أهداف الدرس وتراعي المتعلمين.', 'Teaching strategies should be applied that suit lesson objectives and consider learners.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(271, 7, 'يجب تطبيق إستراتيجيات تدريس تراعي المتعلمين.', 'Teaching strategies should be applied that consider learners.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(272, 7, 'يجب تنفيذ الإستراتيجية بطريقة صحيحة، ووفق ما ورد في خطة الدرس.', 'The strategy should be implemented correctly and according to what is stated in the lesson plan.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(273, 7, 'يجب أن تكون الإستراتيجيات المنفذة متنوعة وتفعل دور الطالب في عملية التعلم.', 'Implemented strategies should be diverse and activate the student\'s role in the learning process.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(274, 8, 'يجب توظيف مصدر التعلم الرئيس بصورة واضحة وسليمة.', 'The main learning resource should be employed clearly and properly.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(275, 8, 'يجب توظيف مصادر مساندة ورقية تثري الدرس وتساعد على تحقيق أهدافه.', 'Supporting paper resources should be employed to enrich the lesson and help achieve its objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(276, 8, 'يجب نشر مصادر تعلم مساندة إلكترونية للمادة على نظام قطر للتعليم.', 'Supporting electronic learning resources for the subject should be published on Qatar Education System.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(277, 8, 'يجب الحرص على استخدام الطلبة مصادر التعلم أثناء الدرس.', 'Students should be encouraged to use learning resources during the lesson.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(278, 8, 'يجب استخدام المصادر المتنوعة لمراعاة الفروق الفردية بين الطلبة.', 'Diverse resources should be used to consider individual differences among students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(279, 9, 'يجب استخدام وسائل تعليمية متنوعة وفعالة.', 'Diverse and effective educational tools should be used.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(280, 9, 'يجب توظيف التكنولوجيا بما يخدم الموقف التعليمي والأهداف.', 'Technology should be employed to serve the educational situation and objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(281, 9, 'يجب تنظيم العرض السبوري.', 'The whiteboard display should be organized.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(282, 9, 'يجب تفعيل السبورة التفاعلية بما يخدم الموقف التعليمي.', 'The interactive whiteboard should be activated to serve the educational situation.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(283, 10, 'يجب أن تكون الأسئلة واضحة وصياغتها سليمة.', 'Questions should be clear and properly formulated.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(284, 10, 'يجب أن تكون الأسئلة متنوعة ومتدرجة في مستوياتها.', 'Questions should be diverse and graduated in their levels.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(285, 10, 'يجب أن تكون الأسئلة مثيرة لاهتمام الطلبة وتحثهم على المشاركة وطرح الأسئلة.', 'Questions should arouse students\' interest and encourage them to participate and ask questions.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(286, 10, 'يجب أن تعزّز الأسئلة الحوار والمناقشة بين الطالب والمعلم والطلبة فيما بينهم.', 'Questions should enhance dialogue and discussion between student and teacher and among students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(287, 10, 'يجب أن تكون الأسئلة مثيرة للتفكير والتحدي لدى الطلبة.', 'Questions should arouse thinking and challenge among students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(288, 11, 'يجب أن تكون المادة العلمية مرتبطة بأهداف الدرس.', 'Scientific content should be linked to lesson objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(289, 11, 'يجب أن تكون المادة العلمية متوافقة مع مصدر التعلم.', 'Scientific content should be consistent with the learning resource.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(290, 11, 'يجب أن تكون المادة العلمية المقدمة صحيحة وسليمة وتخلو من الأخطاء العلمية واللغوية.', 'Presented scientific content should be correct and sound, free from scientific and linguistic errors.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(291, 11, 'يجب وضوح المادة العلمية ومناسبة مفرداتها للمرحلة الدراسية.', 'Scientific content should be clear and its vocabulary appropriate for the educational level.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(292, 11, 'يجب أن تكون المادة العلمية الإثرائية مستندة إلى مراجع موثوقة.', 'Enrichment scientific content should be based on reliable references.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(293, 12, 'يجب أن يوفّر المعلم أنشطة تمكّن الطلبة من اقتراح البدائل وإنتاج أفكار بطرائق مبتكرة.', 'The teacher should provide activities that enable students to suggest alternatives and produce ideas in innovative ways.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(294, 12, 'يجب أن يوفّر المعلم أنشطة تنمّي مهارات الطلبة اللغوية لتوظيفها في التعبير عن الآراء و الأفكار.', 'The teacher should provide activities that develop students\' language skills to employ them in expressing opinions and ideas.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(295, 12, 'يجب أن يوفّر المعلم أنشطة تنمّي مهارات الطلبة العددية لتوظيفها في مواقف متنوعة.', 'The teacher should provide activities that develop students\' numerical skills to employ them in various situations.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(296, 12, 'يجب أن يوفّر المعلم أنشطة تمكّن الطلبة من التواصل استماعاً وتحدثاً وكتابة، وتوظيف ذلك لأغراض مختلفة.', 'The teacher should provide activities that enable students to communicate through listening, speaking, and writing, and employ this for different purposes.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(297, 12, 'يجب أن يوفّر المعلم للطلبة أنشطة العمل التشاركي واحترام الذات، وتقبّل التغير الإيجابي.', 'The teacher should provide students with collaborative work activities and respect for self, and accept positive change.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(298, 12, 'يجب أن يوفّر المعلم للطلبة أنشطة الاهتمام بالتقصي و توظيف التكنولوجيا في إعداد البحوث و مشاركتها.', 'The teacher should provide students with activities for inquiry interest and employing technology in preparing and sharing research.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 6),
(299, 12, 'يجب أن يوفّر المعلم للطلبة أنشطة تحديد المشكلات و التعاون مع الاخرين في اقتراح الحلول.', 'The teacher should provide students with activities for problem identification and cooperation with others in suggesting solutions.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 7),
(300, 13, 'يجب أن يوفّر المعلم أنشطة تساهم في اعتزاز الطلبة باللغة العربية والتاريخ و التقاليد القطرية.', 'The teacher should provide activities that contribute to students\' pride in Arabic language, history, and Qatari traditions.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(301, 13, 'يجب أن يوفّر المعلم أنشطة احترام الطلبة للأخرين وتقديرهم لذواتهم.', 'The teacher should provide activities for students to respect others and appreciate themselves.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(302, 13, 'يجب أن يوفّر المعلم أنشطة تعزّز ثقة الطلبة بقدرتهم على التعلم وبذل الجهد في ذلك.', 'The teacher should provide activities that enhance students\' confidence in their ability to learn and exert effort in doing so.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(303, 13, 'يجب أن يوفّر المعلم أنشطة تحث الطلبة على الالتزام بحقوقهم وواجباتهم.', 'The teacher should provide activities that encourage students to commit to their rights and duties.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(304, 13, 'يجب أن يوفّر المعلم أنشطة لتطوير الطلبة أنماط حياتهم الصحّية.', 'The teacher should provide activities to develop students\' healthy lifestyle patterns.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(305, 14, 'يجب أن يربط المعلّم بين محاور المادة ومهاراتها بصورة فاعلة.', 'The teacher should effectively link between subject areas and skills.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(306, 14, 'يجب أن يوظف المعلّم التكامل مع المواد الأخرى لتحقيق النمو المعرفي عند الطلاب.', 'The teacher should employ integration with other subjects to achieve cognitive growth among students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(307, 15, 'يجب توزيع الطلبة بطريقة مناسبة وفق مستوياتهم والنشاط المنفذ.', 'Students should be distributed appropriately according to their levels and the implemented activity.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(308, 15, 'يجب تقديم أنشطة وتدريبات تراعي الفروق الفردية.', 'Activities and exercises that consider individual differences should be provided.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(309, 15, 'يجب تقديم أنشطة وتدريبات تراعي أنماط التعلم (سمعي، بصري، حركي...).', 'Activities and exercises that consider learning styles (auditory, visual, kinesthetic...) should be provided.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(310, 15, 'يجب أن يتابع معلم الصف المواد التي يقدمها معلم الدعم للطلبة.', 'The class teacher should follow up on materials provided by the support teacher for students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(311, 15, 'يجب تقديم التسهيلات والترتيبات اللازمة لطلاب الدعم.', 'Necessary accommodations and arrangements for support students should be provided.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(312, 15, 'يجب توظيف التكنولوجيا بما يراعي الفروق الفردية وطلاب الدعم.', 'Technology should be employed to consider individual differences and support students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 6),
(313, 16, 'يجب أن يكون غلق الدرس مناسبا وشاملا.', 'Lesson closure should be appropriate and comprehensive.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(314, 16, 'يجب أن يعكس الغلق تحقق أهداف الدرس.', 'Closure should reflect the achievement of lesson objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(315, 16, 'يجب أن يكون الدور الأكبر في الغلق للطلبة.', 'Students should have the major role in closure.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(316, 16, 'يجب تنفيذ الغلق في الزمن المحدد له.', 'Closure should be implemented within the allocated time.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(317, 17, 'يجب أن يشمل التقويم جميع الأهداف المخطط لتحقيقها.', 'Assessment methods should be appropriate and diverse.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(318, 17, 'يجب التنويع في أساليب التقويم مراعاةً للفروق الفردية بين الطلبة.', 'Assessment tools should be used to measure student achievement.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(319, 17, 'يجب التنويع في استخدام أدوات التقويم بما يناسب الموقف التعليمي (ملاحظة المعلم ــ تقييم الذات ــ اختبارات ذهنية وشفوية ــ الأسئلة الشفوية – تطبيق إلكتروني – مناقشة إلكترونية ...).', 'Assessment should be linked to lesson objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(320, 17, 'يجب أن تكون عملية التقويم مستمرة قبل الدرس وأثناءه وبعده (قبلي– بنائي – ختامي).', 'Assessment should consider individual differences among students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(321, 17, 'يجب التنوع في أنماط ومستويات الأسئلة المدرجة في التقييمات وأوراق العمل الإلكترونية.', 'Assessment should be implemented within the allocated time.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(322, 18, 'يجب تنويع أساليب التغذية الراجعة بما يناسب المتعلمين (فورية مؤجلة – لفظية مكتوبة ...).', 'Feedback should be provided to students continuously.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(323, 18, 'يجب شمولية التغذية الراجعة واستمراريتها، بحيث تشمل جميع مراحل الدرس، وجميع المتعلمين على اختلاف مستوياتهم التحصيلية والعقلية والعمرية.', 'Feedback should be constructive and help improve performance.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(324, 18, 'يجب تقييم إجابات الطلبة (الصحيحة والخاطئة) ومناقشتها، وربط إجاباتهم بمعارفهم السابقة.', 'Feedback should be provided in a timely manner.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(325, 18, 'يجب تشجيع الطلبة على تقديم التفسيرات والشروح المنطقية، ودعم إجاباتهم وأقوالهم بنصوص أو أمثلة أو بيانات.', 'Feedback should consider individual differences among students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(326, 18, 'يجب تحفيز الطلبة على تقييم استجابات بعضهم بعضا.', 'Student work should be followed up and corrected accurately.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(327, 19, 'يجب تقديم التعليمات اللازمة لإنجاز الطلبة للأعمال الكتابية بدقة ووضوح والتأكد من فهمها.', 'Student work should be corrected both on paper and electronically.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(328, 19, 'يجب متابعة أعمال الطلبة بشكل دوري وتقويمها، سواء أكانت ورقية أم إلكترونية.', 'Student work should be corrected in a timely manner.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(329, 19, 'يجب تحري الدقة في تصحيح الأعمال التحريرية وتوجيه التغذية الراجعة المناسبة.', 'Student work should be corrected according to clear criteria.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(330, 19, 'يجب إعلان الواجبات والاختبارات الورقية أو الإلكترونية للطلاب وأولياء الأمور بشكل دوري.', 'The classroom environment should be positive and safe.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(331, 20, 'يجب أن يكون مكان الدرس جاهزا للتعليم والتعلم (نظافة – ترتيب ــ تهوية ــ إضاءة...).', 'The classroom environment should be supportive of learning.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(332, 20, 'يجب توجيه الطلبة إلى مراعاة قواعد الأمن والسلامة في الصف والمختبر ومعامل الحاسب.', 'The classroom environment should encourage student participation.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(333, 20, 'يجب أن تكون طريقة جلوس الطلاب منظمة وتسهل التواصل والتعلم داخل الصف.', 'The classroom environment should be organized and clean.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(334, 20, 'يجب أن تكون أعمال الطلبة معروضة ومحدثة داخل الصف.', 'Learning activities and classroom participation should be managed in an organized manner.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(335, 20, 'يجب أن تكون أعمال الطلبة معروضة ومحدثة على نظام قطر للتعليم.', 'Learning activities should be managed according to the allocated time.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(336, 20, 'يجب بناء علاقات إيجابية وبناءة قائمة على الثقة والاحترام المتبادل بين الطالب والمعلم.', 'Classroom participation should be managed to ensure equal opportunities for all students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 6),
(337, 20, 'يجب تشجيع بناء علاقات إيجابية قائمة على الاحترام المتبادل والتعاون بين الطلاب.', 'Learning activities should be managed to achieve lesson objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 7),
(338, 20, 'يجب إثارة دافعية الطلبة للمشاركة في أنشطة التعلم بفاعلية.', 'Classroom management and behavior rules should be activated.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 8),
(339, 21, 'يجب تنظيم مشاركات الطلبة ومناقشاتهم الصفية.', 'Classroom management rules should be clear and known to all students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(340, 21, 'يجب توجيه تعليمات واضحة ومحددة قبل بدء النشاط وأثناء تنفيذه.', 'Behavior management rules should be applied consistently.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(341, 21, 'يجب متابعة استجابة الطلبة للتوجيهات وتنفيذها.', 'Classroom management rules should be applied fairly to all students.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(342, 21, 'يجب أن تسهم حركة المعلم بين الطلبة أثناء تنفيذ الأنشطة في متابعة الطلبة وتقديم الدعم المناسب لهم.', 'Class time should be optimally utilized.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 4),
(343, 21, 'يجب إعطاء الفرصة للطلاب في التفكير في الحل', 'Time should be distributed appropriately among lesson activities.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 5),
(344, 22, 'يجب أن تكون القوانين الصفية ثابتة وواضحة ويعي الطلبة ما يترتب عليها من إجراءات في حالة المخالفة.', 'Time should be managed to achieve lesson objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(345, 22, 'يجب استخدام وسائل وأساليب تربوية متنوعة لتعزيز السلوكيات الإيجابية.', 'Time should be managed to ensure student participation.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(346, 22, 'يجب استخدام وسائل وأساليب تربوية متنوعة لتقويم السلوكيات غير المرغوبة.', 'The laboratory should be used appropriately.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 3),
(347, 23, 'يجب مراعاة الوقت الكافي والمخصص لكل مراحل الدرس (التهيئة ــ العرض –الغلق).', 'Laboratory equipment should be used safely and correctly.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(348, 23, 'يجب استخدام وسائل مختلفة لضمان الالتزام بالزمن المحدد للأنشطة (مثل المؤقت أو العد التنازلي ــ الخ ...).', 'Laboratory activities should be linked to lesson objectives.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 2),
(349, 24, 'يجب التأكد من صلاحية الأدوات وتوافرها بالكميات الكافية لجميع الطلاب لتنفيذ التجربة دون عوائق.', 'Laboratory safety rules should be followed.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(350, 25, 'يجب شرح إجراءات الأمن والسلامة بوضوح، ومتابعة تنفيذها من قبل الطلاب أثناء التجربة.', 'Laboratory activities should be managed in an organized manner.', '2025-05-16 09:23:39', '2025-09-16 20:25:58', 1),
(351, 26, 'يجب أن تكون التعليمات دقيقة ومبسطة، وتُعطى في الوقت المناسب قبل وأثناء النشاط العملي.', 'Instructions must be clear and simplified, and provided at the appropriate time before and during the practical activity.', '2025-05-16 09:23:39', '2025-09-17 14:30:13', 1),
(352, 27, 'يجب تدريب الطلبة على تدوين الملاحظات والنتائج بشكل منظم أثناء أداء النشاط، لتعزيز مهارات الملاحظة والتوثيق.', 'Students should be trained to record notes and results in an organized manner while performing the activity, in order to enhance their observation and documentation skills.', '2025-05-16 09:23:39', '2025-09-17 14:30:26', 1),
(353, 28, 'يجب أن يتم تقويم أداء الطلبة بشكل فردي أو جماعي أثناء التجربة، ومراعاة تطبيق المهارات العلمية والعملية.', 'Students’ performance should be evaluated individually or in groups during the experiment, taking into account the application of scientific and practical skills.', '2025-05-16 09:23:39', '2025-09-17 14:30:41', 1),
(354, 29, 'يجب استخدام أساليب متنوعة للتغذية الراجعة (شفهية، كتابية، آنية أو مؤجلة) لمساعدة الطلبة على تحسين أدائهم وتطوير مهاراتهم.', 'Various methods of feedback (oral, written, immediate, or delayed) should be used to help students improve their performance and develop their skills.', '2025-05-16 09:23:39', '2025-09-17 14:31:04', 1);

-- --------------------------------------------------------

--
-- Stand-in structure for view `roles`
-- (See below for the actual view)
--
CREATE TABLE `roles` (
`created_at` timestamp
,`description` text
,`display_name` varchar(100)
,`id` int
,`name` varchar(50)
,`permissions` json
,`updated_at` timestamp
);

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE `schools` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `school_code` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `address` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `logo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `phone`, `created_at`, `updated_at`, `school_code`, `email`, `address`, `logo`) VALUES
(1, 'مدرسة عبد الله بن على المسند الثانوية للبنين', '30463336', '2025-05-15 21:52:39', '2025-09-05 18:22:43', '30244', 'info@education.qa', 'zone 74 - street 911 - villa 84', 'uploads/logos/school_logo_1747382863.jpg');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_id` int NOT NULL,
  `school_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `name`, `grade_id`, `school_id`, `created_at`, `updated_at`) VALUES
(1, '1', 10, 1, '2025-05-16 07:59:29', '2025-05-16 07:59:29'),
(2, '2', 10, 1, '2025-05-16 07:59:38', '2025-05-16 07:59:38'),
(3, '3', 10, 1, '2025-05-16 07:59:44', '2025-05-16 07:59:44'),
(4, '4', 10, 1, '2025-05-16 07:59:47', '2025-05-16 07:59:47'),
(5, '5', 10, 1, '2025-05-16 07:59:52', '2025-05-16 07:59:52'),
(6, '6', 10, 1, '2025-05-16 07:59:56', '2025-05-16 07:59:56'),
(7, '1', 11, 1, '2025-05-16 08:00:04', '2025-05-16 08:00:04'),
(8, '2', 11, 1, '2025-05-16 08:00:07', '2025-05-16 08:00:07'),
(9, '3', 11, 1, '2025-05-16 08:00:12', '2025-05-16 08:00:12'),
(11, '4', 11, 1, '2025-05-16 08:00:23', '2025-05-16 08:00:23'),
(12, '5', 11, 1, '2025-05-16 08:00:35', '2025-05-16 08:00:35'),
(13, '6', 11, 1, '2025-05-16 08:00:38', '2025-05-16 08:00:38'),
(14, '7', 10, 1, '2025-05-16 08:00:47', '2025-05-16 08:00:47'),
(15, '1', 12, 1, '2025-05-16 08:00:52', '2025-05-16 08:00:52'),
(16, '2', 12, 1, '2025-05-16 08:00:55', '2025-05-16 08:00:55'),
(17, '3', 12, 1, '2025-05-16 08:00:58', '2025-05-16 08:00:58'),
(18, '4', 12, 1, '2025-05-16 08:01:02', '2025-05-16 08:01:02'),
(19, '5', 12, 1, '2025-05-16 08:01:06', '2025-05-16 08:01:06'),
(20, '6', 12, 1, '2025-05-16 08:01:10', '2025-05-16 08:01:10'),
(21, '7', 12, 1, '2025-05-16 08:01:16', '2025-05-16 08:01:16');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `school_id` int DEFAULT NULL,
  `is_school_specific` tinyint(1) NOT NULL DEFAULT '0',
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `school_id`, `is_school_specific`, `is_active`, `created_by`, `updated_by`, `created_at`, `updated_at`) VALUES
(1, 'اللغة العربية', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-05-16 07:49:50'),
(2, 'الغة الانجليزية', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-16 20:37:52'),
(3, 'رياضيات', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:19:19'),
(4, 'علوم', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:19:42'),
(5, 'فيزياء', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:20:01'),
(6, 'كيمياء', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:20:10'),
(7, 'احياء', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:17:39'),
(8, 'شرعية', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:19:30'),
(9, 'علوم  اجتماعية', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:19:52'),
(10, 'الفنون البصرية', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:17:53'),
(11, 'تربية رياضة', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:27:17'),
(13, 'المهارات الحياتية', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-05-16 07:49:58'),
(16, 'حاسب آلي', NULL, 1, 1, NULL, NULL, '2025-05-16 07:44:23', '2025-09-05 18:19:09'),
(18, 'إدارة أعمال', NULL, 0, 1, NULL, NULL, '2025-09-05 18:18:44', '2025-09-05 18:18:44'),
(19, 'تربية خاصة', NULL, 0, 1, NULL, NULL, '2025-09-05 18:18:53', '2025-09-05 18:18:53');

-- --------------------------------------------------------

--
-- Table structure for table `system_settings`
--

CREATE TABLE `system_settings` (
  `id` int NOT NULL,
  `setting_key` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `setting_value` text COLLATE utf8mb4_general_ci,
  `description` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `system_settings`
--

INSERT INTO `system_settings` (`id`, `setting_key`, `setting_value`, `description`, `created_at`, `updated_at`) VALUES
(1, 'site_name', 'نظام زيارة الصفوف', 'اسم الموقع', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(2, 'site_description', 'نظام إداري لمتابعة وتقييم الزيارات الصفية', 'وصف الموقع', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(3, 'academic_year', '2025-2026', 'العام الأكاديمي الحالي', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(4, 'max_file_size', '5', 'الحد الأقصى لحجم الملفات (بالميجابايت)', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(5, 'allowed_file_types', 'pdf,doc,docx,jpg,jpeg,png', 'أنواع الملفات المسموحة', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(6, 'session_timeout', '120', 'مهلة انتهاء الجلسة (بالدقائق)', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(7, 'backup_frequency', 'daily', 'تكرار النسخ الاحتياطي', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(8, 'email_notifications', '1', 'تفعيل الإشعارات بالبريد الإلكتروني', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(9, 'maintenance_mode', '0', 'وضع الصيانة', '2025-09-05 21:10:08', '2025-09-05 21:10:08'),
(10, 'default_language', 'ar', 'اللغة الافتراضية', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(11, 'timezone', 'Asia/Qatar', 'المنطقة الزمنية', '2025-09-05 21:10:08', '2025-09-05 21:15:24'),
(12, 'max_login_attempts', '5', 'عدد محاولات تسجيل الدخول القصوى', '2025-09-05 21:10:08', '2025-09-05 21:15:24');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_id` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_title` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '????????',
  `phone` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `user_id` int DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `personal_id`, `email`, `job_title`, `phone`, `school_id`, `created_at`, `updated_at`, `user_id`) VALUES
(334, 'جاسم جمعه عبدالله  المريخى', '26563400092', 'j.al-meraikhi2112', 'مدير', '55504093', 1, '2025-09-05 18:31:36', '2025-09-05 23:55:10', 235),
(335, 'سيف محمد سيف الجفالى النعيمى', '27963400657', 's.al-naimi0801', 'النائب الأكاديمي', '51600065', 1, '2025-09-05 18:31:36', '2025-09-05 23:55:10', 236),
(336, 'ياسر محمد أحمد عبد الرحمن', '28281810411', 'y.abdelrahman1005', 'مدير', '31031067', 1, '2025-09-05 18:31:36', '2025-09-05 23:55:10', 237),
(337, 'وليد فوزي محمد عماره', '27581807586', 'w.emara2109', 'مدير', '30463336', 1, '2025-09-05 18:31:36', '2025-09-05 23:55:10', 238),
(338, 'محمد رياض عمار', '28478801169', 'm.ammar0504', 'مدير', '33147731', 1, '2025-09-05 18:31:37', '2025-09-05 23:55:10', 239),
(339, 'محمد مصطفى عبداللطيف  علي', '28081807403', 'm.ali0308', 'منسق المادة', '30039724', 1, '2025-09-05 18:31:37', '2025-09-05 23:55:10', 240),
(340, 'رضا فتحي عبدالمقصود  الدسوقى', '27381803984', 'r.eldisouky0612', 'معلم', '77016085', 1, '2025-09-05 18:31:37', '2025-09-05 23:55:10', 241),
(341, 'خالد خميس المحمد  الجبر', '28176001316', 'k.aljabr0902', 'معلم', '55037147', 1, '2025-09-05 18:31:37', '2025-09-05 23:55:10', 242),
(342, 'عواد علي عواد  القضاه', '27840001128', 'a.alqudah1402', 'معلم', '55037151', 1, '2025-09-05 18:31:37', '2025-09-05 23:55:10', 243),
(343, 'عبدالعزيز معوض عبدالعزيز  علي', '27681806952', 'a.aly2202', 'معلم', '30308273', 1, '2025-09-05 18:31:37', '2025-09-05 23:54:02', 244),
(344, 'محمد عيسى احمد الحسن المهندى', '26463400778', 'm.al-mohannadi010112', 'معلم', '55537512', 1, '2025-09-05 18:31:37', '2025-09-05 23:55:10', 245),
(345, 'باسم عبدالكريم فالح المقصقص', '28240002031', 'b.almqassqas2802', 'معلم', '30045665', 1, '2025-09-05 18:31:37', '2025-09-05 23:55:10', 246),
(346, 'عماد جمعه خميس  الغرابلي', '27499900372', 'i.algharabli2701', 'معلم', '50659803', 1, '2025-09-05 18:31:38', '2025-09-05 23:55:10', 247),
(347, 'محمد علي لطفي احمد سلامة', '28481811662', 'm.salama2110', 'معلم', '31022440', 1, '2025-09-05 18:31:38', '2025-09-05 23:55:10', 248),
(348, 'ضياء الدين رياض الحلو', '28076003896', 'd.helo2502', 'منسق المادة', '66273147', 1, '2025-09-05 18:31:38', '2025-09-05 23:55:10', 249),
(349, 'صايل عبدالرحمن عبدالكريم  الذنيبات', '27140001050', 's.althunibat0212', 'معلم', '55743112', 1, '2025-09-05 18:31:38', '2025-09-05 23:55:10', 250),
(350, 'ثروت احمدى الشبراوى محمد على', '27481803365', 's.ali2505', 'معلم', '55221241', 1, '2025-09-05 18:31:38', '2025-09-05 23:55:10', 251),
(351, 'محمد عبدالله محمد سالم بباه', '27847800070', 'm.bebah3112', 'معلم', '33565543', 1, '2025-09-05 18:31:38', '2025-09-05 23:55:10', 252),
(352, 'عماد عادل مسعود أبو مغلي', '27640001276', 'e.abumougly0103', 'معلم', '66400521', 1, '2025-09-05 18:31:39', '2025-09-05 23:55:10', 253),
(353, 'حسام الدين محمد محمد علي الصياد', '26481802610', 'h.elsayyad0110', 'منسق المادة', '77311350', 1, '2025-09-05 18:31:39', '2025-09-05 23:55:10', 254),
(354, 'إسماعيل محمد العبد', '26576000611', 'i.alabed0112', 'معلم', '66854532', 1, '2025-09-05 18:31:39', '2025-09-05 23:55:10', 255),
(355, 'منتصر محمد شعبان  على', '27881807607', 'm.ali0709', 'معلم', '66266306', 1, '2025-09-05 18:31:39', '2025-09-05 23:55:10', 256),
(356, 'ابراهيم محمد   المحمد', '28076004226', 'i.almohammad1706', 'معلم', '66407781', 1, '2025-09-05 18:31:39', '2025-09-05 23:55:10', 257),
(357, 'سعيد خاشع   السلطان', '28876002299', 's.alsultan1008', 'معلم', '50220521', 1, '2025-09-05 18:31:39', '2025-09-05 23:55:10', 258),
(358, 'محمد سبتي درويش الرمحي', '27440001342', 'm.alramahi1006', 'معلم', '30744330', 1, '2025-09-05 18:31:39', '2025-09-05 23:55:10', 259),
(359, 'أحمد زكي علي الزغول', '29540002568', '', 'معلم', '30018183', 1, '2025-09-05 18:31:40', '2025-09-05 23:55:10', 260),
(360, 'خالد علي الحمد  العلي', '28776002151', 'k.alali1802', 'معلم', '66093334', 1, '2025-09-05 18:31:40', '2025-09-05 23:55:10', 261),
(361, 'خالد سالم عبدالعزيز  محمد', '28081809628', 'k.mohamed0902', 'معلم', '30271585', 1, '2025-09-05 18:31:40', '2025-09-05 23:55:10', 262),
(362, 'محمد السيد عبدالصبور  محمد', '28181804042', 'm.mohamed2803', 'منسق المادة', '55431619', 1, '2025-09-05 18:31:40', '2025-09-05 23:55:10', 263),
(363, 'عبدالرحمن محمود الرزوق', '28476002916', 'a.alrazooq0201', 'معلم', '33547618', 1, '2025-09-05 18:31:40', '2025-09-05 23:55:10', 264),
(364, 'عامر وائل محمود  الوردات', '28840001725', 'a.alwardat1911', 'معلم', '77706526', 1, '2025-09-05 18:31:40', '2025-09-05 23:55:10', 265),
(365, 'اسامه محمد احمد  الصابر', '28381806309', 'o.elsaber1602', 'معلم', '30293236', 1, '2025-09-05 18:31:40', '2025-09-05 23:55:10', 266),
(366, 'عبدالمحسن محمد أحمد عبدالرحمن', '27573601472', 'a.abdelrahman010129', 'منسق المادة', '66289597', 1, '2025-09-05 18:31:41', '2025-09-05 23:55:10', 267),
(367, 'أحمد أديب القصاب', '28276001286', 'a.alkassab1706', 'معلم', '55037156', 1, '2025-09-05 18:31:41', '2025-09-05 23:55:10', 268),
(368, 'محمد احمد محمود  ابوحلتم', '27040000937', 'm.haltam0111', 'معلم', '55072581', 1, '2025-09-05 18:31:41', '2025-09-05 23:55:10', 269),
(369, 'محمد غالب محمد المساعيد', '29440002222', 'm.almasaeid1006', 'معلم', '31227811', 1, '2025-09-05 18:31:41', '2025-09-05 23:55:10', 270),
(370, 'فراس محمد رشيد سلامه', '27140001123', 'f.salameh2609', 'منسق المادة', '70176050', 1, '2025-09-05 18:31:41', '2025-09-05 23:55:10', 271),
(371, 'عصام حمزة جوهر ابوطالب', '27381804902', 'e.aboutaleb2203', 'معلم', '77513360', 1, '2025-09-05 18:31:41', '2025-09-05 23:55:10', 272),
(372, 'عيسى محمد احمد الحسن المهندى', '27263400266', 'e.al-muhannadi2504', 'معلم', '55515812', 1, '2025-09-05 18:31:41', '2025-09-05 23:55:10', 273),
(373, 'يمان ديندار', '28376003495', 'y.din1001', 'معلم', '33875958', 1, '2025-09-05 18:31:42', '2025-09-05 23:55:10', 274),
(374, 'حسام احمد فارس القضاه', '28940002572', 'demo1', 'معلم', '30628805', 1, '2025-09-05 18:31:42', '2025-09-05 23:55:10', 275),
(375, 'عمر زرعيد   غضوي', '27576002012', 'o.ghadawi0501', 'منسق المادة', '33834554', 1, '2025-09-05 18:31:42', '2025-09-05 23:55:10', 276),
(376, 'محمد طلاق احمد بني حمد', '27740002016', 'm.banihamad3110', 'معلم', '71007810', 1, '2025-09-05 18:31:42', '2025-09-05 23:55:10', 277),
(377, 'عصام محمود محمد  عوض', '28281809248', 'e.awad1507', 'معلم', '74745629', 1, '2025-09-05 18:31:42', '2025-09-05 23:55:10', 278),
(378, 'ايمن فضل الكريم أبوالقاسم حمزة', '27381804932', 'a.hamza0409', 'معلم', '33831248', 1, '2025-09-05 18:31:42', '2025-09-05 23:55:10', 279),
(379, 'محمد مصطفى طلبه  مصطفى', '27681806976', 'm.mostafa01013', 'معلم', '33459563', 1, '2025-09-05 18:31:43', '2025-09-05 23:55:10', 280),
(380, 'محمود حمد احمد  البديرات', '28240001228', 'm.albederat1209', 'معلم', '33071376', 1, '2025-09-05 18:31:43', '2025-09-05 23:55:10', 281),
(381, 'احمد عبود   الجيجان', '28776001982', 'a.aljejan1303', 'معلم', '66157342', 1, '2025-09-05 18:31:43', '2025-09-05 23:55:10', 282),
(382, 'محمد زياد صالح جرادات', '28540002363', 'm.jaradat0410', 'معلم', '33044029', 1, '2025-09-05 18:31:43', '2025-09-05 23:55:10', 283),
(383, 'عمر راشد حسن خليل', '26840000972', 'o.khaleel0108', 'منسق المادة', '33463557', 1, '2025-09-05 18:31:43', '2025-09-05 23:55:10', 284),
(384, 'محمد علي ابراهيم السيدعلي', '27676002444', 'm.sayedali1501', 'معلم', '50555976', 1, '2025-09-05 18:31:43', '2025-09-05 23:55:10', 285),
(385, 'تامر سالم مشرف سالم محمد', '27381801863', 't.mohamed2605', 'معلم', '55413286', 1, '2025-09-05 18:31:43', '2025-09-05 23:55:10', 286),
(386, 'صباح نصر صباح  قريبه', '29199900528', 's.quraiba0911', 'معلم', '30968771', 1, '2025-09-05 18:31:44', '2025-09-05 23:55:10', 287),
(387, 'رايلي مصطفى سليمان بني بكر', '27640001363', 'r.baker3007', 'معلم', '31010224', 1, '2025-09-05 18:31:44', '2025-09-05 23:55:10', 288),
(388, 'عمار اسود   كحيط', '27876002876', 'a.kaheet20011', 'معلم', '55626490', 1, '2025-09-05 18:31:44', '2025-09-05 23:55:10', 289),
(389, 'اشرف عبدالمنصف محمد  الهنداوى', '26981803707', 'a.elhandawy0504', 'معلم', '55765926', 1, '2025-09-05 18:31:44', '2025-09-05 23:55:10', 290),
(390, 'محمد سليم المحمد', '27476002066', 'm.almohammad0508', 'معلم', '30116775', 1, '2025-09-05 18:31:44', '2025-09-05 23:55:10', 291),
(391, 'محمد عبدالحميد محمد فرحات', '27081803456', 'm.farahat1212', 'منسق المادة', '55386404', 1, '2025-09-05 18:31:44', '2025-09-05 23:55:10', 292),
(392, 'طارق محمد علام  شلتوت', '27081801424', 't.shaltout0711', 'معلم', '55463899', 1, '2025-09-05 18:31:44', '2025-09-05 23:55:10', 293),
(393, 'طاهر سهيلي', '28878800289', 't.shili2106', 'معلم', '30257217', 1, '2025-09-05 18:31:45', '2025-09-05 23:55:10', 294),
(394, 'هشام بن عبدالرحمان   سالمي', '28178801165', 'h.selmi0208', 'منسق المادة', '66298991', 1, '2025-09-05 18:31:45', '2025-09-05 23:55:10', 295),
(395, 'انس محمد صبحي السيد عمر', '28576002648', 'a.omar1411', 'معلم', '66296146', 1, '2025-09-05 18:31:45', '2025-09-05 23:55:10', 296),
(396, 'نبيل عبدالعزيز   صحبي', '28478801690', 'n.sahbi0901', 'معلم', '66040424', 1, '2025-09-05 18:31:45', '2025-09-05 23:55:10', 297),
(397, 'مامون يوسف موسى  فراج', '28640000748', 'm.farraj1504', 'معلم', '33256106', 1, '2025-09-05 18:31:45', '2025-09-05 23:55:10', 298),
(398, 'حمد نصف محمد الحسن المهندى', '28463401368', 'h.al-mohannidi0608', 'معلم', '55811223', 1, '2025-09-05 18:31:45', '2025-09-05 23:55:10', 299),
(399, 'ابراهيم عبدالحليم المنسي عبدالحليم حسن', '28381806392', 'i.hassan1108', 'منسق المادة', '30152180', 1, '2025-09-05 18:31:45', '2025-09-05 23:55:10', 300),
(400, 'صالح عبدالفتاح فياض  الخوالده', '27740001696', 's.alkhawaldeh0210', 'معلم', '77856604', 1, '2025-09-05 18:31:46', '2025-09-05 23:55:10', 301),
(401, 'سليم محمد سليمان  النعيمات', '28740001937', 's.alnaimat0207', 'معلم', '30666545', 1, '2025-09-05 18:31:46', '2025-09-05 23:55:10', 302),
(402, 'علاء السيد محمد  أبوشحاته', '27481805163', 'a.aboushehata2007', 'معلم', '66987115', 1, '2025-09-05 18:31:46', '2025-09-05 23:55:10', 303),
(403, 'موفق الفريح', '29238400024', 'm.alfraih1009', 'معلم', '30313464', 1, '2025-09-05 18:31:46', '2025-09-05 23:55:10', 304),
(404, 'محمد عبدالرحمن إبراهيم كناعنه', '28340002358', 'demo@demo.com', 'معلم', '30031901', 1, '2025-09-05 18:31:46', '2025-09-05 23:55:10', 305),
(405, 'صائب سالم دليمي', '26876000143', 's.dlimi0310', 'معلم', '55624935', 1, '2025-09-05 18:31:46', '2025-09-05 23:55:10', 306),
(406, 'حسين رمضان   حنيت', '29376000723', 'h.hnit0101', 'معلم', '55646897', 1, '2025-09-05 18:31:46', '2025-09-05 23:55:10', 307),
(407, 'احمدحسن نبيل   كلثوم', '28476003161', 'a.colthoum2710', 'معلم', '30232770', 1, '2025-09-05 18:31:47', '2025-09-05 23:55:10', 308),
(408, 'محمد السيد محمد جاد', '27888188143', 'm.manmoud2706', 'معلم', '70607840', 1, '2025-09-05 18:31:47', '2025-09-05 23:55:10', 309),
(409, 'موجه أحياء', '12345678901', 'Biology', 'موجه المادة', '12345678', 1, '2025-09-05 18:44:52', '2025-09-05 23:58:41', 310),
(410, 'موجه الفنون البصرية', '12345678902', 'art', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:41', 311),
(411, 'موجه اللغة العربية', '12345678903', 'arabic', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:41', 312),
(412, 'موجه المهارات الحياتية', '12345678904', 'Skills', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:41', 313),
(413, 'موجه إدارة أعمال', '12345678905', 'Administration', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:41', 314),
(414, 'موجه تربية خاصة', '12345678906', 'Special', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:41', 315),
(415, 'موجه تربية رياضة', '12345678907', 'sport', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:41', 316),
(416, 'موجه حاسب آلي', '12345678908', 'computer', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:41', 317),
(417, 'موجه رياضيات', '12345678909', 'math', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:42', 318),
(418, 'موجه شرعية', '12345678910', 'islam', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:42', 319),
(419, 'موجه علوم', '12345678911', 'Science', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:42', 320),
(420, 'موجه علوم اجتماعية', '12345678912', 'Social', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:42', 321),
(421, 'موجه فيزياء', '12345678913', 'Physics', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:42', 322),
(422, 'موجه كيمياء', '12345678914', 'Chemistry', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:42', 323),
(423, 'موجه لغة إنجليزية', '12345678915', 'English', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-05 23:58:42', 324),
(424, 'موجه اللغة الإنجليزية', '12345678901', 'english.supervisor@example.com', 'موجه المادة', '12345678', 1, '2025-09-17 13:58:03', '2025-09-17 13:58:03', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `teacher_subjects`
--

CREATE TABLE `teacher_subjects` (
  `id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teacher_subjects`
--

INSERT INTO `teacher_subjects` (`id`, `teacher_id`, `subject_id`, `created_at`, `updated_at`) VALUES
(215, 339, 3, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(216, 340, 3, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(217, 341, 3, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(218, 342, 3, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(219, 343, 3, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(220, 344, 3, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(221, 345, 3, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(222, 346, 3, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(223, 347, 3, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(224, 348, 8, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(225, 349, 8, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(226, 350, 8, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(227, 351, 8, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(228, 352, 8, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(229, 353, 9, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(230, 354, 9, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(231, 355, 9, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(232, 356, 9, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(233, 357, 9, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(234, 358, 9, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(235, 359, 9, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(236, 360, 4, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(237, 361, 4, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(238, 362, 6, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(239, 363, 6, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(240, 364, 6, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(241, 365, 6, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(242, 366, 5, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(243, 367, 5, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(244, 368, 5, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(245, 369, 5, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(246, 370, 7, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(247, 371, 7, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(248, 372, 7, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(249, 373, 7, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(250, 374, 7, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(251, 375, 2, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(252, 376, 2, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(253, 377, 2, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(254, 378, 2, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(255, 379, 2, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(256, 380, 2, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(257, 381, 2, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(258, 382, 2, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(259, 383, 1, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(260, 384, 1, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(261, 385, 1, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(262, 386, 1, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(263, 387, 1, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(264, 388, 1, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(265, 389, 1, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(266, 390, 1, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(267, 391, 11, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(268, 392, 11, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(269, 393, 11, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(270, 394, 16, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(271, 395, 16, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(272, 396, 16, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(273, 397, 18, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(274, 398, 18, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(275, 399, 19, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(276, 400, 19, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(277, 401, 19, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(278, 402, 19, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(279, 403, 19, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(280, 404, 19, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(281, 405, 10, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(282, 406, 13, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(283, 407, 13, '2025-09-05 18:31:47', '2025-09-05 18:31:47'),
(284, 408, 13, '2025-09-05 18:31:47', '2025-09-05 18:31:47'),
(285, 410, 10, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(286, 411, 1, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(287, 412, 13, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(288, 413, 18, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(289, 414, 19, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(290, 415, 11, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(291, 416, 16, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(292, 417, 3, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(293, 418, 8, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(294, 419, 4, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(295, 421, 5, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(296, 422, 6, '2025-09-05 18:45:41', '2025-09-05 18:45:41'),
(297, 424, 2, '2025-09-17 13:58:03', '2025-09-17 13:58:03'),
(298, 423, 2, '2025-09-17 14:02:55', '2025-09-17 14:02:55');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_hash` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `role_id` int NOT NULL,
  `school_id` int DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `last_login` timestamp NULL DEFAULT NULL,
  `password_reset_token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `password_reset_expires` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `full_name`, `role_id`, `school_id`, `is_active`, `last_login`, `password_reset_token`, `password_reset_expires`, `created_at`, `updated_at`) VALUES
(1, 'admin_user', 'admin@school.edu', '$2y$10$lULwHbg87xgcdGnb2Dzqm..OTmKcL/N8aDk6X6G7RYRkTt7xw.Lam', 'مدير النظام', 1, 1, 1, '2025-09-18 04:03:26', NULL, NULL, '2025-09-05 14:09:14', '2025-09-18 04:03:26'),
(235, 'j.al-meraikhi2112', 'j.al-meraikhi2112', '$2y$10$BhljEBgxrNl06LexrFeZDu4Pzcg/Z4sJcTNRMozvTDVmQ8pGGyPKa', 'جاسم جمعه عبدالله  المريخى', 2, 1, 1, '2025-09-05 21:17:51', NULL, NULL, '2025-09-05 18:31:36', '2025-09-05 21:17:51'),
(236, 's.al-naimi0801', 's.al-naimi0801', '$2y$10$lL7B75hDhBT1qBPK6Lcfr.keMeDntthjKN9b7Wwnl5CP/XlsXiGuq', 'سيف محمد سيف الجفالى النعيمى', 3, 1, 1, '2025-09-05 21:17:00', NULL, NULL, '2025-09-05 18:31:36', '2025-09-05 21:17:00'),
(237, 'y.abdelrahman1005', 'y.abdelrahman1005', '$2y$10$JxKlzNMmC/ZTikFyVAVez.T.FpioCfPcVP86ODDhgOpMmfRGp5u2G', 'ياسر محمد أحمد عبد الرحمن', 2, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:36', '2025-09-05 18:31:36'),
(238, 'w.emara2109', 'w.emara2109@education.qa', '$2y$10$9Lfj16Jy6Y1bRWbrhEE2P.3Jtvu.N.V4msAsbufxaApBmj.Xruh8G', 'وليد فوزي محمد عماره', 1, 1, 1, '2025-09-11 04:55:31', NULL, NULL, '2025-09-05 18:31:37', '2025-09-11 04:55:31'),
(239, 'm.ammar0504', 'm.ammar0504', '$2y$10$NYUpCacwsMMcdVj7aUadTepwKsB3/0Y9hvOBJiLnTUoRz2EM7gA1G', 'محمد رياض عمار', 2, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(240, 'm.ali0308', 'm.ali0308', '$2y$10$BAJFCr.WoAHc2Gvj/b6JDOX7BkaN09F5YFiuIKgNn0FFN1nqjmx.C', 'محمد مصطفى عبداللطيف  علي', 5, 1, 1, '2025-09-13 03:38:20', NULL, NULL, '2025-09-05 18:31:37', '2025-09-13 03:38:20'),
(241, 'r.eldisouky0612', 'r.eldisouky0612', '$2y$10$apWQ7hoDrtCZCg1A8vmVUuRB6fwUZLG4gvd4YnFz3iAJSTW3KL0GK', 'رضا فتحي عبدالمقصود  الدسوقى', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(242, 'k.aljabr0902', 'k.aljabr0902', '$2y$10$xIWx0QZn3kKXKPtTKbaD0OogiCzO.hau4uW6nC5kFe0XlZ0/KAu.2', 'خالد خميس المحمد  الجبر', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(243, 'a.alqudah1402', 'a.alqudah1402', '$2y$10$3z6thoLhRLJ.aKA2vOZ2Wux4m8HTqKhbCNnKW3M5BV6PgR0jyRVxW', 'عواد علي عواد  القضاه', 6, 1, 1, '2025-09-11 09:28:18', NULL, NULL, '2025-09-05 18:31:37', '2025-09-11 09:28:18'),
(244, 'a.aly2202', 'a.aly2202', '$2y$10$v9Em03TmCi8rEfNaO.45lunR3T8suwimIeY.m4AC8l3qoCTGD3/4.', 'عبدالعزيز معوض عبدالعزيز  علي', 6, 1, 1, '2025-09-13 09:10:37', NULL, NULL, '2025-09-05 18:31:37', '2025-09-13 09:10:37'),
(245, 'm.al-mohannadi010112', 'm.al-mohannadi010112', '$2y$10$X6JyIIHD.kBxGzT3xh3unefDXgm.G4EhgACPPwdyJ3lpapOtYwa0q', 'محمد عيسى احمد الحسن المهندى', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(246, 'b.almqassqas2802', 'b.almqassqas2802', '$2y$10$imTDp1G.D8HdsRa8g/6Die.TkJLaIIKk1Wy.u908brcFLt2nul3aW', 'باسم عبدالكريم فالح المقصقص', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(247, 'i.algharabli2701', 'i.algharabli2701', '$2y$10$qlIeTZTIyXXhD5sLAih3E.NwVZgcxh8JUZLi6KkUXlSzP9/HjfCq.', 'عماد جمعه خميس  الغرابلي', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(248, 'm.salama2110', 'm.salama2110', '$2y$10$sQSNrHwniJolfM1ldO.F9.e2YG5Jw8Yp.wLWp5mQNKmiQUMxOQxu2', 'محمد علي لطفي احمد سلامة', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(249, 'd.helo2502', 'd.helo2502', '$2y$10$ISgyh3nSdlqVAMymXQ/BP.BdhDnihf8slPjPLbT7fnUe8bZfuApue', 'ضياء الدين رياض الحلو', 5, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(250, 's.althunibat0212', 's.althunibat0212', '$2y$10$asU4aXHX7sKolYFxtrt9d.tOVfFVEclDukxGIYV/nJKKgR2NEx94q', 'صايل عبدالرحمن عبدالكريم  الذنيبات', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(251, 's.ali2505', 's.ali2505', '$2y$10$o2lhS.JW4sNMnHneyzWwAezkyaWM5wPtG9w/WRJ6Pradb2keJlV.W', 'ثروت احمدى الشبراوى محمد على', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(252, 'm.bebah3112', 'm.bebah3112', '$2y$10$b/bb3TslTDSFxfDn.o/G7O3fjBZWeZeXzHnWPVRhZfJDTr32B/Vd2', 'محمد عبدالله محمد سالم بباه', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:38', '2025-09-05 18:31:38'),
(253, 'e.abumougly0103', 'e.abumougly0103', '$2y$10$/HHKwepxzmKqAshNmHL/4.FY1/aTvwqazNVuCJJ/aURLqR93/sf1m', 'عماد عادل مسعود أبو مغلي', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(254, 'h.elsayyad0110', 'h.elsayyad0110', '$2y$10$eWmBGzHx60IgJWI.jdAdgOZPL3TbzPODzYq4.v.6BmZeSG93CHROK', 'حسام الدين محمد محمد علي الصياد', 5, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(255, 'i.alabed0112', 'i.alabed0112', '$2y$10$x7wxqE4JPYqxKTDzzONXQuPqcF3tZrPuNLhkr72CLp90R4SCdZBp.', 'إسماعيل محمد العبد', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(256, 'm.ali0709', 'm.ali0709', '$2y$10$I4/Uu6ksjF8q.QKIoYFHQ.oCEJfGYsGDoIwEZnxFPXIF0qO0dffWO', 'منتصر محمد شعبان  على', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(257, 'i.almohammad1706', 'i.almohammad1706', '$2y$10$gLE.PJKghztGJ7eK6jFwhOcGmcQqwiSMPS2I9YFKsj0GP/bIubs/2', 'ابراهيم محمد   المحمد', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(258, 's.alsultan1008', 's.alsultan1008', '$2y$10$XREJALn7FrWWTdMpuiemX./b68H9v.nSGV5koNb/XslzINTA/XBWa', 'سعيد خاشع   السلطان', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:39', '2025-09-05 18:31:39'),
(259, 'm.alramahi1006', 'm.alramahi1006', '$2y$10$DWhXUgI5rCgdRHzhGziBMOZnlheIt8NHvphr6wYBzolDlBgvfh9F2', 'محمد سبتي درويش الرمحي', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(260, '29540002568', '', '$2y$10$9o91DWKLHFnyHI2rqVuaVeH6xJiS/v8txRW8FjTnA3hIWlwq9T1ra', 'أحمد زكي علي الزغول', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(261, 'k.alali1802', 'k.alali1802', '$2y$10$sb3x2uhWBDVvdPGeVDLP4Or2sze43DhrTkQGva.fTXzaV2DrMZ3SS', 'خالد علي الحمد  العلي', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(262, 'k.mohamed0902', 'k.mohamed0902', '$2y$10$lh5GrzAq1GgzcAjIDXXTkO1f/2e4ruUFbtE7hkWAxF8NYZIjpkdi2', 'خالد سالم عبدالعزيز  محمد', 6, 1, 1, '2025-09-06 00:18:21', NULL, NULL, '2025-09-05 18:31:40', '2025-09-06 00:18:21'),
(263, 'm.mohamed2803', 'm.mohamed2803', '$2y$10$JRLvE0s9fzwt1pWWNXwijeGpX5sZHfYxEt94sgfVuWGtcNCEZVUpq', 'محمد السيد عبدالصبور  محمد', 5, 1, 1, '2025-09-06 00:54:27', NULL, NULL, '2025-09-05 18:31:40', '2025-09-06 00:54:27'),
(264, 'a.alrazooq0201', 'a.alrazooq0201', '$2y$10$Uff.cK.DYumWkaVR4FMtjeDinNPKq2M.hX2QFW8c.Btj/wQwM/4C2', 'عبدالرحمن محمود الرزوق', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(265, 'a.alwardat1911', 'a.alwardat1911', '$2y$10$CDvyLOYJADpsyKUw4Qjwhedv9j5DjKkVgeQoEu0OiOfdfnr6CJf8i', 'عامر وائل محمود  الوردات', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:40', '2025-09-05 18:31:40'),
(266, 'o.elsaber1602', 'o.elsaber1602', '$2y$10$8y.YVzbn/01aGes.ZhXkau7QkuYE4Huv03dULYEGK8FQBP.5X6Soe', 'اسامه محمد احمد  الصابر', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(267, 'a.abdelrahman010129', 'a.abdelrahman010129', '$2y$10$C/1XLqQs3XjoZ8sILkrUX.pJTtqOAjCp5MJGegmHOdPWdL8s05deG', 'عبدالمحسن محمد أحمد عبدالرحمن', 5, 1, 1, '2025-09-15 07:27:13', NULL, NULL, '2025-09-05 18:31:41', '2025-09-15 07:27:13'),
(268, 'a.alkassab1706', 'a.alkassab1706', '$2y$10$bY9rMNf7PditmavbiT3bBe/g1v3V3kfe1rEVQGOAo/eyawcv1/Bq.', 'أحمد أديب القصاب', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(269, 'm.haltam0111', 'm.haltam0111', '$2y$10$qHaeL0eYCJUVcnaAYQg.sOjlmPno30rzSjWih13sEsJBicj1kL5Na', 'محمد احمد محمود  ابوحلتم', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(270, 'm.almasaeid1006', 'm.almasaeid1006', '$2y$10$DNkPqGztEJ0st9XXR0aJDeRJmZATz2dASorzVhqh.kWCoDVpY7WCu', 'محمد غالب محمد المساعيد', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(271, 'f.salameh2609', 'f.salameh2609', '$2y$10$nkmP0YhYUzjuUJqJPUfI9u9h.vyk9cyVYRyfBFHs1v0mayihoI72K', 'فراس محمد رشيد سلامه', 5, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(272, 'e.aboutaleb2203', 'e.aboutaleb2203', '$2y$10$OP5hhxAwWX/qRsCsMIyVn.8xImZyzxVYqSxwHBY.COLVA9AiGa7na', 'عصام حمزة جوهر ابوطالب', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:41', '2025-09-05 18:31:41'),
(273, 'e.al-muhannadi2504', 'e.al-muhannadi2504', '$2y$10$uztQq2eUjXB4rpz9m4jnGenJJtrLhLoi0ZDI6EkcneCv2o6uIywAK', 'عيسى محمد احمد الحسن المهندى', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(274, 'y.din1001', 'y.din1001', '$2y$10$9dg76A9vIeVR9O0ErPxrD.Po9iLRePYAR3TNb.P72.d.iJn3u/qoK', 'يمان ديندار', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(275, 'demo1', 'demo1', '$2y$10$0Kk3lG0xiIXWbQhu/GgHGe0giiW4IcN8MGTFkSoFpJquRdt80NuMe', 'حسام احمد فارس القضاه', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(276, 'o.ghadawi0501', 'o.ghadawi0501', '$2y$10$8z/9IWdzuqG3zO9ugdc69.U0H3ZTg7z5dk35SPol5LEIbnYu070ty', 'عمر زرعيد   غضوي', 5, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(277, 'm.banihamad3110', 'm.banihamad3110', '$2y$10$2PtIuC2bdwEBZJG.jJs/B.tvvRGTPixnj/9KT8mAYDh1rj0g7MePK', 'محمد طلاق احمد بني حمد', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(278, 'e.awad1507', 'e.awad1507', '$2y$10$mzbFAy3A2epe.UdNO.rToucyC2lz0B0Gc8vdBnlwz7GRe73qehOzK', 'عصام محمود محمد  عوض', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:42', '2025-09-05 18:31:42'),
(279, 'a.hamza0409', 'a.hamza0409', '$2y$10$pURdy9St/1jGMo37.wOLg.zMd8.T9m45ohDaTB0Vk7O5KC4ClWqjm', 'ايمن فضل الكريم أبوالقاسم حمزة', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(280, 'm.mostafa01013', 'm.mostafa01013', '$2y$10$Wfz1swz9xavcsWiuRd2FcucUV5wpsXQNw2ZOU1xMEMHUGzY3RT.QS', 'محمد مصطفى طلبه  مصطفى', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(281, 'm.albederat1209', 'm.albederat1209', '$2y$10$3phgdH27voDDxcczixfuRu1vdb8NW/CrRPkn75/s7OgUZNHjtBXUO', 'محمود حمد احمد  البديرات', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(282, 'a.aljejan1303', 'a.aljejan1303', '$2y$10$IDRD1..KtoVSPseNF1ARVuH7WipNcjkemGAouzYf131EOodSbNx26', 'احمد عبود   الجيجان', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(283, 'm.jaradat0410', 'm.jaradat0410', '$2y$10$1HhJ7NPuWxn7Ovafydv3FeSaXtFEA/67pDI.XKIgSYyYkiXofPmHW', 'محمد زياد صالح جرادات', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(284, 'o.khaleel0108', 'o.khaleel0108', '$2y$10$6BnJwTN7OkUxz9YTLR/R5ezbvAMg9CDuQA7rhvZPbetx1CwlP2Ki6', 'عمر راشد حسن خليل', 5, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(285, 'm.sayedali1501', 'm.sayedali1501', '$2y$10$qeehsyMBdVMpCCuOroK43.fiBfIqRp/wh1fQ2RA671X6mofJ.VILK', 'محمد علي ابراهيم السيدعلي', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:43', '2025-09-05 18:31:43'),
(286, 't.mohamed2605', 't.mohamed2605', '$2y$10$pGg2u0P7M2Tfze94ptpKwuNF87l/rRJdQvsHk5nJpk2Te2RPDxCl2', 'تامر سالم مشرف سالم محمد', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(287, 's.quraiba0911', 's.quraiba0911', '$2y$10$twLuVZ5gn7P1VgVPor5B/.HZdMeZMduOIxIlk3Jx1YQkdIa7vcYgC', 'صباح نصر صباح  قريبه', 6, 1, 1, '2025-09-07 05:35:10', NULL, NULL, '2025-09-05 18:31:44', '2025-09-07 05:35:10'),
(288, 'r.baker3007', 'r.baker3007', '$2y$10$VFSu5tTdXQtIECzYKQufcOeNabiLPxN4cYse/zqj07myTVL1NSpzG', 'رايلي مصطفى سليمان بني بكر', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(289, 'a.kaheet20011', 'a.kaheet20011', '$2y$10$/KG7yvpa0.gzSOYcvYKgqODyWQO9fCnKq3f6Qx2lcybKyWAVuXDZO', 'عمار اسود   كحيط', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(290, 'a.elhandawy0504', 'a.elhandawy0504', '$2y$10$cbTThzBVc..084OwZkZQk.3C6lbz10gNy/HcMQAUO61EhvyqsAWae', 'اشرف عبدالمنصف محمد  الهنداوى', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(291, 'm.almohammad0508', 'm.almohammad0508', '$2y$10$XzzKORABziO6hsnyF8vzGe.z1wF3bzZJk7x41vIRvhtD5urPhNXUC', 'محمد سليم المحمد', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(292, 'm.farahat1212', 'm.farahat1212', '$2y$10$sxOi8tFlqulqw9xqepvYZOsKy/JzWn/xcufPlZPC8QUqqNlqHR4PC', 'محمد عبدالحميد محمد فرحات', 5, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:44', '2025-09-05 18:31:44'),
(293, 't.shaltout0711', 't.shaltout0711', '$2y$10$ARgPpzYs5hhiUceW1Qw/7u44PUDXlCRty0tVxm0SHTxIo4Ota1FSa', 'طارق محمد علام  شلتوت', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(294, 't.shili2106', 't.shili2106', '$2y$10$Nw2cC3AjTLzlt6diOm7t6OKCPwXXoNNT.K51Ndhwl7wZYRBolRW02', 'طاهر سهيلي', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(295, 'h.selmi0208', 'h.selmi0208', '$2y$10$MDOCT0nZ.18yLXVnFItMW.iUjrsLuhUYq5krNC7g1p8zT6UL6LJE2', 'هشام بن عبدالرحمان   سالمي', 5, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(296, 'a.omar1411', 'a.omar1411', '$2y$10$GdlZ9k5IhIrdhaNjoly.3eX1qJ69oDcFMa21emkNa2uWxRY/Qhcii', 'انس محمد صبحي السيد عمر', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(297, 'n.sahbi0901', 'n.sahbi0901', '$2y$10$M6KWJDBvJ284Aze8R96oWuSsuK3.Lg9jUI5LONArUT8tkArdW6vR2', 'نبيل عبدالعزيز   صحبي', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(298, 'm.farraj1504', 'm.farraj1504', '$2y$10$jRf3bYrFgD3w0Hb7kzYQ4.Z.zO88oa1OS/iZakZi80uu.ndytw6JO', 'مامون يوسف موسى  فراج', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(299, 'h.al-mohannidi0608', 'h.al-mohannidi0608', '$2y$10$t4JsEoUF46WDu9miGcQWpORbdP/62WHzsSKbhogrWLe9wEbX3mwxy', 'حمد نصف محمد الحسن المهندى', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:45', '2025-09-05 18:31:45'),
(300, 'i.hassan1108', 'i.hassan1108', '$2y$10$Z021cr2NRI4NQf1b951ueu0abBXqBFG.RORcNB3R11.35fMA3NDYu', 'ابراهيم عبدالحليم المنسي عبدالحليم حسن', 5, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(301, 's.alkhawaldeh0210', 's.alkhawaldeh0210', '$2y$10$EevV/yoNBx/M3Ctz5wSf/.xr8Lihg8dkXQbvIDncqDG7CNOptRdi6', 'صالح عبدالفتاح فياض  الخوالده', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(302, 's.alnaimat0207', 's.alnaimat0207', '$2y$10$9XaOT6NWif3xhSU0rmB0WeUWsfyNo6xuAvLn0tmnrwN2sb6wUFfOO', 'سليم محمد سليمان  النعيمات', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(303, 'a.aboushehata2007', 'a.aboushehata2007', '$2y$10$NVKQNCcjBNhYBHHv6SVDROVJdZT7tM8RBiGzEz6gi3aNKohxasBeG', 'علاء السيد محمد  أبوشحاته', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(304, 'm.alfraih1009', 'm.alfraih1009', '$2y$10$8vS5TrjNhu/RjPVGLU106eiO7bTMBdXTzLmUu6r0KJck1eb.dJk/W', 'موفق الفريح', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(305, 'demo@demo.com', 'demo@demo.com', '$2y$10$yd6ZIi1Y2pggac0hcupfk.fOK7YG6Nnn.mmIqj.WrdY/.XRXxP2Pi', 'محمد عبدالرحمن إبراهيم كناعنه', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(306, 's.dlimi0310', 's.dlimi0310', '$2y$10$DvRbB30bzCE15Hie7fYM6uIhluPylOinqRBm/4SkgMMCFD.UqK/VK', 'صائب سالم دليمي', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:46', '2025-09-05 18:31:46'),
(307, 'h.hnit0101', 'h.hnit0101', '$2y$10$/GQin1.lD8ynADFSRA8NOeJTk7bMpJG/MHmvDnambWRXLj0yPRywK', 'حسين رمضان   حنيت', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:47', '2025-09-05 18:31:47'),
(308, 'a.colthoum2710', 'a.colthoum2710', '$2y$10$5yUaMJvGAkf.6lpZAFpN0uy.TZ64HuS5EZ7nZLyU5g6tD1jF6V4DO', 'احمدحسن نبيل   كلثوم', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:47', '2025-09-05 18:31:47'),
(309, 'm.manmoud2706', 'm.manmoud2706', '$2y$10$.vRX18kJxnScGYUDNfzW2eZQkNlI9HUU8WHflVzG5SJLDgRoXrd2.', 'محمد السيد محمد جاد', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:47', '2025-09-05 18:31:47'),
(310, 'teacher_409', NULL, '$2y$10$nW8uWdpsU2p5kLBFByT3t.XLqwROUfzM5XEkyUhCDT8yp7he/P7/2', 'موجه أحياء', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-05 23:58:41'),
(311, 'teacher_410', NULL, '$2y$10$7YhXBDl1.kcC7Otmu1qeZO2icqsF5254rSrNAXNwHF8RYP7RXN.CO', 'موجه الفنون البصرية', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-05 23:58:41'),
(312, 'teacher_411', NULL, '$2y$10$6tH/tDGbNHIlVf43cF8WXu/iP6NrI/eDU5yO/dVOrOx7j3RMNVAWi', 'موجه اللغة العربية', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-05 23:58:41'),
(313, 'teacher_412', NULL, '$2y$10$r9VQmGxxAd48U3gr3S/e9uA6ZnSE3K0FfIzpDfIG1Vkktp3EDdNwq', 'موجه المهارات الحياتية', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-05 23:58:41'),
(314, 'teacher_413', NULL, '$2y$10$Jv/OyBSqGZ3AVXEV4QvPZeBH.sfUE9Z6mlggDN3/mlDxRdsKBppfS', 'موجه إدارة أعمال', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-05 23:58:41'),
(315, 'teacher_414', NULL, '$2y$10$vznXQiF9tNgXGfExAeVwdedX/TX8qdyFa.1LNNMaAIcmtHdk8V26i', 'موجه تربية خاصة', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-05 23:58:41'),
(316, 'teacher_415', NULL, '$2y$10$YvPN/9318Dt20m6HtDRXgO1TQdYFc/mB7YTvIi05dbYgUHxc2MDbq', 'موجه تربية رياضة', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-05 23:58:41'),
(317, 'teacher_416', NULL, '$2y$10$/xp1CBmHQQqNwbIGe3AngOLEdirxb9VCxvS5NdgMVsvfl99/B.8nC', 'موجه حاسب آلي', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-05 23:58:41'),
(318, 'teacher_417', NULL, '$2y$10$CZ9F6bc6qaK2ofRtge/jv.ANU2pLpNgkB/j5KsDJyUBqqrtrJKdge', 'موجه رياضيات', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-05 23:58:42'),
(319, 'teacher_418', NULL, '$2y$10$b4RC7cEJFfOKHM6pB.7UJOCMZU5ykC0hI1CMq29YdtSMM/5dRlQfW', 'موجه شرعية', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-05 23:58:42'),
(320, 'teacher_419', NULL, '$2y$10$oKP93/79EeESeHqfyPjU9.bcvVUnoOxMOR9/x66bByBbMwFxQgXXC', 'موجه علوم', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-05 23:58:42'),
(321, 'teacher_420', NULL, '$2y$10$586xMTt2GuJTs3t2Us1HK.81ag7BU.5LkzUTlU6TFIb4hyfFpewy2', 'موجه علوم اجتماعية', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-05 23:58:42'),
(322, 'teacher_421', NULL, '$2y$10$mnVOsjajX8EtuHL2WA89R.vBeDZppkJY4c/tpIHuJXjLUEavAi7Ri', 'موجه فيزياء', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-05 23:58:42'),
(323, 'teacher_422', NULL, '$2y$10$t/Wzbz2m61faJUrjJy56muxQ3gs9qTIKwA5TEqP7i.SjjSz10tB1K', 'موجه كيمياء', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-05 23:58:42'),
(324, 'teacher_423', 'english@education.qa', '$2y$10$LKgG4p7nuWHQCgHBlyJiwufs6LoIwRoSE9ZX/DS/IhcDeDRw2uSOe', 'موجه اللغة الإنجليزية', 3, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-17 13:54:57'),
(325, 'elearning_coordinator', 'elearning@school.edu', '$2y$10$pJuIl54s5jP5A5x12IKIUuJMh9l6udSODpjOwsHl3QlGzYPZ0bsKO', 'منسق التعليم الإلكتروني', 7, 1, 1, '2025-09-17 07:36:17', NULL, NULL, '2025-09-09 07:05:59', '2025-09-17 07:36:17');

-- --------------------------------------------------------

--
-- Table structure for table `user_activity_log`
--

CREATE TABLE `user_activity_log` (
  `id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `table_name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `record_id` int DEFAULT NULL,
  `old_values` text COLLATE utf8mb4_unicode_ci,
  `new_values` text COLLATE utf8mb4_unicode_ci,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_activity_log`
--

INSERT INTO `user_activity_log` (`id`, `user_id`, `action`, `table_name`, `record_id`, `old_values`, `new_values`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 1, 'update_settings', 'system_settings', NULL, NULL, '{\"site_name\":\"\\u0646\\u0638\\u0627\\u0645 \\u0632\\u064a\\u0627\\u0631\\u0629 \\u0627\\u0644\\u0635\\u0641\\u0648\\u0641\",\"academic_year\":\"2025-2026\",\"site_description\":\"\\u0646\\u0638\\u0627\\u0645 \\u0625\\u062f\\u0627\\u0631\\u064a \\u0644\\u0645\\u062a\\u0627\\u0628\\u0639\\u0629 \\u0648\\u062a\\u0642\\u064a\\u064a\\u0645 \\u0627\\u0644\\u0632\\u064a\\u0627\\u0631\\u0627\\u062a \\u0627\\u0644\\u0635\\u0641\\u064a\\u0629\",\"default_language\":\"ar\",\"timezone\":\"Asia\\/Qatar\",\"max_file_size\":\"5\",\"allowed_file_types\":\"pdf,doc,docx,jpg,jpeg,png\",\"session_timeout\":\"120\",\"max_login_attempts\":\"5\",\"backup_frequency\":\"daily\",\"email_notifications\":\"1\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:15:24'),
(2, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:16:52'),
(3, 236, 'login', 'users', 236, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:17:00'),
(4, 236, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:17:44'),
(5, 235, 'login', 'users', 235, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:17:51'),
(6, 235, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:18:53'),
(7, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:19:02'),
(8, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36 Edg/139.0.0.0', '2025-09-05 21:20:27'),
(9, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:27:41'),
(10, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:27:47'),
(11, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:36:43'),
(12, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 21:36:48'),
(13, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:05:02'),
(14, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:05:07'),
(15, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:09:44'),
(16, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:09:49'),
(17, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:48:22'),
(18, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:48:27'),
(19, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:49:19'),
(20, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:49:34'),
(21, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:52:17'),
(22, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:52:23'),
(23, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:53:08'),
(24, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 22:53:15'),
(25, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:03:37'),
(26, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:03:53'),
(27, 240, 'login', 'users', 240, NULL, NULL, NULL, '', '', '2025-09-05 23:08:39'),
(28, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:25:58'),
(29, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:26:02'),
(30, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:31:10'),
(31, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:31:18'),
(32, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:32:16'),
(33, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:32:22'),
(34, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:34:04'),
(35, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:34:24'),
(36, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:35:03'),
(37, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:44:49'),
(38, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:44:57'),
(39, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:47:42'),
(40, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:47:49'),
(41, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:52:11'),
(42, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-05 23:52:16'),
(43, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 00:17:35'),
(44, 262, 'login', 'users', 262, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 00:18:21'),
(45, 262, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 00:20:41'),
(46, 263, 'login', 'users', 263, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 00:20:48'),
(47, 263, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 00:21:24'),
(48, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 00:21:37'),
(50, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 00:52:48'),
(51, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 00:53:21'),
(52, 263, 'login', 'users', 263, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 00:54:27'),
(53, 263, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 01:03:25'),
(54, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 01:03:39'),
(55, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 01:05:58'),
(56, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 01:42:28'),
(57, 1, 'update_user', 'users', 1, '{\"id\":1,\"username\":\"admin_user\",\"email\":\"admin@school.edu\",\"password_hash\":\"$2y$10$L0Uo60GyAXjKryjF4qO9ZusWC5kqqt4.oYnW1LaBnGLfnBN7PXEsy\",\"full_name\":\"\\u0645\\u062f\\u064a\\u0631 \\u0627\\u0644\\u0646\\u0638\\u0627\\u0645\",\"role_id\":1,\"school_id\":1,\"is_active\":1,\"last_login\":\"2025-09-06 04:42:28\",\"password_reset_token\":null,\"password_reset_expires\":null,\"created_at\":\"2025-09-05 17:09:14\",\"updated_at\":\"2025-09-06 04:42:28\"}', '{\"full_name\":\"\\u0645\\u062f\\u064a\\u0631 \\u0627\\u0644\\u0646\\u0638\\u0627\\u0645\",\"email\":\"admin@school.edu\",\"role_id\":1,\"school_id\":1,\"is_active\":1,\"password\":\"123456\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-06 01:46:35'),
(59, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 05:33:57'),
(60, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 05:34:18'),
(61, 287, 'login', 'users', 287, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 05:35:10'),
(62, 287, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 05:36:05'),
(63, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 05:36:11'),
(64, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 09:02:39'),
(65, 238, 'login', 'users', 238, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 09:03:36'),
(66, 238, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 14:31:37'),
(67, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 14:32:02'),
(68, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 14:36:21'),
(69, 238, 'login', 'users', 238, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 14:36:41'),
(71, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 15:06:10'),
(72, 1, 'update_user', 'users', 238, '{\"id\":238,\"username\":\"w.emara2109\",\"email\":\"w.emara2109\",\"password_hash\":\"$2y$10$9Lfj16Jy6Y1bRWbrhEE2P.3Jtvu.N.V4msAsbufxaApBmj.Xruh8G\",\"full_name\":\"\\u0648\\u0644\\u064a\\u062f \\u0641\\u0648\\u0632\\u064a \\u0645\\u062d\\u0645\\u062f \\u0639\\u0645\\u0627\\u0631\\u0647\",\"role_id\":2,\"school_id\":1,\"is_active\":1,\"last_login\":\"2025-09-07 17:36:41\",\"password_reset_token\":null,\"password_reset_expires\":null,\"created_at\":\"2025-09-05 21:31:37\",\"updated_at\":\"2025-09-07 17:36:41\"}', '{\"full_name\":\"\\u0648\\u0644\\u064a\\u062f \\u0641\\u0648\\u0632\\u064a \\u0645\\u062d\\u0645\\u062f \\u0639\\u0645\\u0627\\u0631\\u0647\",\"email\":\"w.emara2109@education.qa\",\"role_id\":1,\"school_id\":1,\"is_active\":1}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 15:07:28'),
(73, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 15:07:34'),
(74, 238, 'login', 'users', 238, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-07 15:07:54'),
(75, 238, 'login', 'users', 238, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 06:42:44'),
(76, 238, 'update_user', 'users', 1, '{\"id\":1,\"username\":\"admin_user\",\"email\":\"admin@school.edu\",\"password_hash\":\"$2y$10$lNoedogYLoqNcBheT0b8J.qr4xNe3T6uDL\\/meP.qQg0aWuD\\/RuX\\/C\",\"full_name\":\"\\u0645\\u062f\\u064a\\u0631 \\u0627\\u0644\\u0646\\u0638\\u0627\\u0645\",\"role_id\":1,\"school_id\":1,\"is_active\":1,\"last_login\":\"2025-09-07 18:06:10\",\"password_reset_token\":null,\"password_reset_expires\":null,\"created_at\":\"2025-09-05 17:09:14\",\"updated_at\":\"2025-09-07 18:06:10\"}', '{\"full_name\":\"\\u0645\\u062f\\u064a\\u0631 \\u0627\\u0644\\u0646\\u0638\\u0627\\u0645\",\"email\":\"admin@school.edu\",\"role_id\":1,\"school_id\":1,\"is_active\":1,\"password\":\"123456\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 06:46:56'),
(77, 238, 'update_user', 'users', 325, '{\"id\":325,\"username\":\"elearning_coordinator\",\"email\":\"elearning@school.edu\",\"password_hash\":\"$2y$10$morkk20uU.e01vHS20X1ie.UKJLL\\/nmQvqA9UYii8aktLPAhVpb7e\",\"full_name\":\"\\u0645\\u0646\\u0633\\u0642 \\u0627\\u0644\\u062a\\u0639\\u0644\\u064a\\u0645 \\u0627\\u0644\\u0625\\u0644\\u0643\\u062a\\u0631\\u0648\\u0646\\u064a\",\"role_id\":7,\"school_id\":1,\"is_active\":1,\"last_login\":null,\"password_reset_token\":null,\"password_reset_expires\":null,\"created_at\":\"2025-09-09 10:05:59\",\"updated_at\":\"2025-09-09 10:05:59\"}', '{\"full_name\":\"\\u0645\\u0646\\u0633\\u0642 \\u0627\\u0644\\u062a\\u0639\\u0644\\u064a\\u0645 \\u0627\\u0644\\u0625\\u0644\\u0643\\u062a\\u0631\\u0648\\u0646\\u064a\",\"email\":\"elearning@school.edu\",\"role_id\":7,\"school_id\":1,\"is_active\":1,\"password\":\"123456\"}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 07:14:26'),
(78, 238, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 07:14:41'),
(79, 325, 'login', 'users', 325, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 07:14:47'),
(80, 325, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 07:52:34'),
(81, 325, 'login', 'users', 325, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 07:52:40'),
(82, 325, 'login', 'users', 325, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 10:07:18'),
(83, 325, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 15:36:39'),
(84, 325, 'login', 'users', 325, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 15:36:45'),
(85, 325, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-09 19:58:20'),
(86, 325, 'login', 'users', 325, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-10 04:07:02'),
(88, 238, 'login', 'users', 238, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 04:55:31'),
(89, 238, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36', '2025-09-11 04:58:17'),
(91, 243, 'login', 'users', 243, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 09:28:18'),
(92, 243, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 09:30:07'),
(93, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 09:30:16'),
(94, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 09:32:03'),
(95, 325, 'login', 'users', 325, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 09:33:13'),
(96, 325, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 09:33:40'),
(97, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 09:33:56'),
(98, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 10:38:44'),
(99, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 10:41:02'),
(100, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 10:41:12'),
(102, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-11 10:42:17'),
(104, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 02:43:02'),
(106, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:22:30'),
(107, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:22:43'),
(108, 325, 'login', 'users', 325, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:22:58'),
(109, 325, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:24:06'),
(110, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:24:17'),
(111, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:26:56'),
(112, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:27:06'),
(113, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:36:57'),
(114, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:37:14'),
(115, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:37:26'),
(116, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:37:49'),
(117, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:38:01'),
(118, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 03:38:20'),
(120, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-13 07:53:53'),
(122, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 08:00:28'),
(124, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 09:10:37'),
(126, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-13 09:17:04'),
(128, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-13 10:41:47'),
(129, 267, 'login', 'users', 267, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 07:16:45'),
(130, 267, 'login', 'users', 267, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-15 07:27:13'),
(131, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 09:16:25'),
(132, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 10:51:53'),
(133, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 10:51:58'),
(134, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 12:36:18'),
(135, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 12:36:26'),
(136, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 19:02:55'),
(137, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 19:03:00'),
(138, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 20:11:13'),
(139, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 20:37:02'),
(140, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 20:37:10'),
(141, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 20:43:28'),
(142, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 20:43:34'),
(143, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 20:55:38'),
(144, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 20:55:44'),
(145, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 21:11:26'),
(146, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 21:11:32'),
(147, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-16 21:12:31'),
(148, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 21:22:48'),
(149, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 21:22:55'),
(150, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 21:38:49'),
(151, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 21:38:55'),
(152, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-16 21:42:03'),
(153, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-16 21:42:06'),
(154, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 21:46:30'),
(155, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 21:46:37'),
(156, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 22:04:46'),
(157, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 22:08:48'),
(158, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-16 22:31:41'),
(159, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-16 22:31:43'),
(160, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 22:36:21'),
(161, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-16 22:36:29'),
(162, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 04:14:48'),
(163, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 04:14:54'),
(164, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 04:25:36'),
(165, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 04:25:41'),
(166, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 04:26:55'),
(167, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 04:30:28'),
(168, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 04:30:34'),
(169, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 07:33:41'),
(170, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 07:35:40'),
(171, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 07:36:01'),
(172, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 07:36:11'),
(173, 325, 'login', 'users', 325, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 07:36:17'),
(174, 325, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 09:10:20'),
(175, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0', '2025-09-17 09:10:22'),
(176, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 09:34:28'),
(177, 1, 'update_user', 'users', 324, '{\"id\":324,\"username\":\"teacher_423\",\"email\":null,\"password_hash\":\"$2y$10$LKgG4p7nuWHQCgHBlyJiwufs6LoIwRoSE9ZX\\/DS\\/IhcDeDRw2uSOe\",\"full_name\":\"\\u0645\\u0648\\u062c\\u0647 \\u0644\\u063a\\u0629 \\u0625\\u0646\\u062c\\u0644\\u064a\\u0632\\u064a\\u0629\",\"role_id\":3,\"school_id\":1,\"is_active\":1,\"last_login\":null,\"password_reset_token\":null,\"password_reset_expires\":null,\"created_at\":\"2025-09-06 02:58:42\",\"updated_at\":\"2025-09-06 02:58:42\"}', '{\"full_name\":\"\\u0645\\u0648\\u062c\\u0647 \\u0627\\u0644\\u0644\\u063a\\u0629 \\u0627\\u0644\\u0625\\u0646\\u062c\\u0644\\u064a\\u0632\\u064a\\u0629\",\"email\":\"english@education.qa\",\"role_id\":3,\"school_id\":1,\"is_active\":1}', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 13:54:57'),
(178, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 13:59:32'),
(179, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 15:55:31'),
(180, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-17 15:55:37'),
(181, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 03:55:27'),
(182, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 04:03:26');

-- --------------------------------------------------------

--
-- Table structure for table `user_roles`
--

CREATE TABLE `user_roles` (
  `id` int NOT NULL,
  `name` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `display_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `permissions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_roles`
--

INSERT INTO `user_roles` (`id`, `name`, `display_name`, `description`, `permissions`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'مدير النظام', 'صلاحيات كاملة على جميع أجزاء النظام', '{\"all\": true}', '2025-09-05 14:09:14', '2025-09-05 14:09:14'),
(2, 'Director', 'مدير المدرسة', 'صلاحيات كاملة على جميع أجزاء النظام', '{\"all\": true}', '2025-09-05 14:09:14', '2025-09-05 14:09:14'),
(3, 'Academic Deputy', 'النائب الأكاديمي', 'صلاحيات كاملة على جميع أجزاء النظام', '{\"all\": true}', '2025-09-05 14:09:14', '2025-09-05 14:09:14'),
(4, 'Supervisor', 'مشرف تربوي', 'صلاحيات على جميع المواد والمعلمين', '{\"full_access\": true}', '2025-09-05 14:09:14', '2025-09-05 14:09:14'),
(5, 'Subject Coordinator', 'منسق المادة', 'صلاحيات على مادة محددة والمعلمين والموجهين المرتبطين بها', '{\"reports_view\": true, \"visit_creation\": true, \"subject_management\": true}', '2025-09-05 14:09:14', '2025-09-05 14:09:14'),
(6, 'Teacher', 'معلم', 'صلاحيات محدودة لعرض الزيارات والتقارير الشخصية فقط', '{\"view_own_visits\": true, \"view_own_reports\": true}', '2025-09-05 14:09:14', '2025-09-05 14:09:14'),
(7, 'E-Learning Coordinator', 'منسق التعليم الإلكتروني', 'صلاحيات إدارة حضور التعليم الإلكتروني ومتابعة أداء المعلمين على نظام قطر للتعليم', '{\"view_reports\": true, \"manage_attendance\": true, \"elearning_attendance\": true, \"qatar_system_monitoring\": true}', '2025-09-09 07:01:13', '2025-09-09 07:01:13');

-- --------------------------------------------------------

--
-- Table structure for table `user_sessions`
--

CREATE TABLE `user_sessions` (
  `id` int NOT NULL,
  `user_id` int NOT NULL,
  `session_id` varchar(128) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `last_activity` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `visitor_types`
--

CREATE TABLE `visitor_types` (
  `id` int NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name_en` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visitor_types`
--

INSERT INTO `visitor_types` (`id`, `name`, `name_en`, `created_at`, `updated_at`) VALUES
(15, 'منسق المادة', 'Subject Coordinator', '2025-05-16 08:54:31', '2025-09-17 14:40:47'),
(16, 'موجه المادة', 'Subject Supervisor', '2025-05-16 08:54:31', '2025-09-17 14:40:47'),
(17, 'النائب الأكاديمي', 'Academic Deputy', '2025-05-16 08:54:31', '2025-09-17 14:40:47'),
(18, 'مدير', 'Principal', '2025-05-16 08:54:31', '2025-09-17 14:40:47');

-- --------------------------------------------------------

--
-- Table structure for table `visits`
--

CREATE TABLE `visits` (
  `id` int NOT NULL,
  `school_id` int NOT NULL,
  `teacher_id` int NOT NULL,
  `subject_id` int NOT NULL,
  `grade_id` int NOT NULL,
  `section_id` int NOT NULL,
  `level_id` int NOT NULL,
  `visitor_type_id` int NOT NULL,
  `visitor_person_id` int DEFAULT NULL,
  `visit_date` date NOT NULL,
  `academic_year_id` int DEFAULT NULL,
  `visit_type` enum('full','partial') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `attendance_type` enum('physical','remote','hybrid') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'physical',
  `has_lab` tinyint(1) NOT NULL DEFAULT '0',
  `topic` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `general_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `recommendation_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `appreciation_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `total_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `school_id`, `teacher_id`, `subject_id`, `grade_id`, `section_id`, `level_id`, `visitor_type_id`, `visitor_person_id`, `visit_date`, `academic_year_id`, `visit_type`, `attendance_type`, `has_lab`, `topic`, `general_notes`, `recommendation_notes`, `appreciation_notes`, `total_score`, `created_at`, `updated_at`) VALUES
(20, 1, 343, 3, 11, 7, 3, 18, 334, '2025-09-05', 2, 'full', 'physical', 0, NULL, '', 'بالاهتمام الزائد بالاسئلة للطلاب الضعاف', 'مجهودة و تركيزه و مهاراته ', 68.00, '2025-09-05 18:57:00', '2025-09-05 18:59:17'),
(21, 1, 343, 3, 11, 7, 3, 17, 335, '2025-09-03', 2, 'full', 'physical', 0, NULL, '', 'الاهتمام اكثر و اكثر', 'شكرا', 58.00, '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(22, 1, 343, 3, 11, 8, 3, 15, 339, '2025-08-31', 2, 'full', 'physical', 0, NULL, '', 'تطوير ', 'المجهود', 58.00, '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(24, 1, 342, 3, 10, 14, 3, 16, 417, '2025-09-05', 2, 'full', 'physical', 0, NULL, '', 'يجب أن تراعي الأنشطة التدرج والتسلسل في تحقيق أهداف الدرس.\r\n\r\nيجب أن ترتبط أنشطة التمهيد بخبرات الطلبة الحياتية وتجاربهم السابقة.\r\n\r\nيجب تفعيل السبورة التفاعلية بما يخدم الموقف التعليمي.\r\n\r\nيجب أن يربط المعلّم بين محاور المادة ومهاراتها بصورة فاعلة.\r\n\r\nيجب تقديم أنشطة وتدريبات تراعي أنماط التعلم (سمعي، بصري، حركي...).\r\n\r\nيجب تقديم التعليمات اللازمة لإنجاز الطلبة للأعمال الكتابية بدقة ووضوح والتأكد من فهمها.\r\n\r\nيجب أن تكون القوانين الصفية ثابتة وواضحة ويعي الطلبة ما يترتب عليها من إجراءات في حالة المخالفة.\r\n\r\nيجب استخدام وسائل مختلفة لضمان الالتزام بالزمن المحدد للأنشطة (مثل المؤقت أو العد التنازلي ــ الخ ...).', '', 57.00, '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(25, 1, 346, 3, 10, 2, 3, 15, 339, '2025-09-05', 2, 'full', 'physical', 0, NULL, '', 'تمام', 'تمام التمام', 58.00, '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(26, 1, 345, 3, 12, 15, 3, 15, 339, '2025-09-05', 2, 'full', 'physical', 0, NULL, '', 'يجب أن يكون مكان الدرس جاهزا للتعليم والتعلم (نظافة – ترتيب ــ تهوية ــ إضاءة...).\r\n\r\nيجب توجيه الطلبة إلى مراعاة قواعد الأمن والسلامة في الصف والمختبر ومعامل الحاسب.\r\n\r\nيجب أن تكون طريقة جلوس الطلاب منظمة وتسهل التواصل والتعلم داخل الصف.\r\n\r\nيجب توجيه تعليمات واضحة ومحددة قبل بدء النشاط وأثناء تنفيذه.\r\n\r\nيجب متابعة استجابة الطلبة للتوجيهات وتنفيذها.\r\n\r\nيجب أن تكون القوانين الصفية ثابتة وواضحة ويعي الطلبة ما يترتب عليها من إجراءات في حالة المخالفة.\r\n\r\nيجب مراعاة الوقت الكافي والمخصص لكل مراحل الدرس (التهيئة ــ العرض –الغلق).', '', 16.00, '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(27, 1, 361, 4, 10, 1, 3, 17, 335, '2025-09-05', 2, 'full', 'physical', 0, NULL, '', 'بالاهتمام بالطالب الضعيف', 'على الجهد المبذول و التكنولوجيا الفاعلة', 60.00, '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(29, 1, 365, 6, 12, 15, 3, 15, 362, '2025-09-06', 2, 'full', 'physical', 1, NULL, '', '', '', 81.00, '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(31, 1, 343, 3, 12, 19, 3, 17, 335, '2025-09-13', 2, 'full', 'physical', 0, NULL, '', 'بالإهتمام  بالطلاب قليلي التحصيل', 'المجهود و التطور الواضح فى المستوي', 63.00, '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(32, 1, 381, 2, 12, 21, 3, 18, 334, '2025-09-16', 2, 'full', 'physical', 0, 'البلح البلح ', '', 'يجب أن يكون غلق الدرس في الخطّة مناسبا للموضوع والأهداف.\r\n\r\nيجب أن تكون الأهداف قابلة للقياس.\r\n\r\nيجب أن تراعي الأنشطة التدرج والتسلسل في تحقيق أهداف الدرس.\r\n\r\nيجب أن توضّح الأنشطة الرئيسة دور كل من المعلم والطالب.', '', 9.00, '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(33, 1, 378, 2, 12, 20, 3, 17, 335, '2025-09-16', 2, 'full', 'physical', 0, 'البلح البلح ', '', 'غع تغفاقثصبي', 'كوكووكوكوكوكوكوكو', 15.00, '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(34, 1, 377, 2, 12, 21, 3, 17, 335, '2025-09-16', 2, 'full', 'physical', 0, 'توت توت', '', 'يجب توفّر الخطّة على نظام قطر للتعليم.\r\n\r\nيجب أن تكون الأهداف مصاغة بطريقة إجرائية سليمة وواضحة.\r\n\r\nيجب أن تكون الأنشطة مرتبطة بأهداف الدرس وتساعد على تحقيقها.', '', 8.00, '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(35, 1, 381, 2, 12, 21, 3, 17, 335, '2025-09-17', 2, 'full', 'physical', 0, 'توت توت', 'الىبلايرسؤء', 'تالبىلايرس', 'نتاةلبلاي', 110.00, '2025-09-17 10:03:40', '2025-09-17 10:03:40'),
(36, 1, 378, 2, 12, 15, 3, 15, 375, '2025-09-17', 2, 'full', 'physical', 0, 'كوكو واوا', 'تةالبلايسش', 'البلايسء', 'لبيسبيس', 62.00, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(37, 1, 382, 2, 11, 8, 3, 18, 334, '2025-09-17', 2, 'full', 'physical', 0, 'يا سلام يا سلام', 'jghfnbdvcsx', 'بيل بل يل يل يبل يبل', 'بيل ل ي بليبليبليب', 69.00, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(38, 1, 377, 2, 11, 12, 3, 16, 424, '2025-09-17', 2, 'full', 'physical', 0, 'قيسيءفلرلااتىةنوم', 'يسل الاتىةو', 'يريبل  يقليبليبل يبل يبلبي', 'يبل يبل يبليبليبليلبل', 26.00, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(39, 1, 380, 2, 12, 20, 3, 16, 424, '2025-09-17', 2, 'full', 'physical', 0, 'trtrtrtrtrtrtrtrtr', 'dfvecswxaqzgfbdsva', 'mhngfbdsxafdsva', 'hnfgbdsxadfsxa', 58.00, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(40, 1, 342, 3, 11, 11, 3, 17, 335, '2025-09-17', 2, 'full', 'physical', 0, 'التوت التوت ', 'هغعتفلقثيسءش', 'فغقلارثؤصسءغفقلاث', 'عةفقىلاثؤصبيبيس', 69.00, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(41, 1, 376, 2, 11, 11, 3, 17, 335, '2025-09-17', 2, 'full', 'physical', 0, 'فبلرغلااع قثيفبلغعاتهف فثءقؤبغلرلااتى', '', '', '', 48.00, '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(42, 1, 380, 2, 12, 21, 3, 16, 424, '2025-09-17', 2, 'full', 'physical', 0, 'rererererererererererererererere', '', 'tytytytytytytytytytytyyttyyt', 'thanks thanks thanks thanks thanks thanks thanks thanks ', 46.00, '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(43, 1, 377, 2, 10, 5, 3, 17, 335, '2025-09-17', 2, 'full', 'physical', 0, 'koko wawa', 'dhgf oduyfg oduyfg ksduhfg sdukyfg ods\r\nldi fugdiug udyfguyds\r\nsdi tsib8y tisduytfidsyufdsf', 's fysat dfiystf isyuf kuys\r\ns udytsiudytiuysagd\r\nyyyyyyyyyyyyyyyyyyy', 's dukfsakudyfiasuy guaysd\r\ns kuytiuasydtiuasydua\r\nuuuuuuuuuuuuuuuuu', 62.00, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(44, 1, 377, 2, 10, 5, 3, 17, 335, '2025-09-17', 2, 'full', 'physical', 0, 'koko wawa', 'dhgf oduyfg oduyfg ksduhfg sdukyfg ods\r\nldi fugdiug udyfguyds\r\nsdi tsib8y tisduytfidsyufdsf', 's fysat dfiystf isyuf kuys\r\ns udytsiudytiuysagd\r\nyyyyyyyyyyyyyyyyyyy', 's dukfsakudyfiasuy guaysd\r\ns kuytiuasydtiuasydua\r\nuuuuuuuuuuuuuuuuu', 62.00, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(45, 1, 398, 18, 11, 7, 3, 18, 334, '2025-09-18', 2, 'full', 'physical', 0, 'الالتهابات', 'قثغف ثقغفثقفثقف', 'الاهتمام الاهتمام الاهتمام الاهتمام الاهتمام الاهتمام الاهتمام الاهتمام الاهتمام ', 'اشكر اشكر اشكر اشكر اشكر اشكر اشكر اشكر اشكر اشكر اشكر اشكر اشكر اشكر ', 67.00, '2025-09-18 04:13:49', '2025-09-18 04:13:49');

-- --------------------------------------------------------

--
-- Table structure for table `visit_evaluations`
--

CREATE TABLE `visit_evaluations` (
  `id` int NOT NULL,
  `visit_id` int NOT NULL,
  `indicator_id` int NOT NULL,
  `score` decimal(5,2) DEFAULT NULL,
  `recommendation_id` int DEFAULT NULL,
  `custom_recommendation` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visit_evaluations`
--

INSERT INTO `visit_evaluations` (`id`, `visit_id`, `indicator_id`, `score`, `recommendation_id`, `custom_recommendation`, `created_at`, `updated_at`) VALUES
(497, 20, 1, 3.00, 239, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(498, 20, 2, 3.00, 248, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(499, 20, 3, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(500, 20, 4, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(501, 20, 5, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(502, 20, 6, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(503, 20, 7, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(504, 20, 8, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(505, 20, 9, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(506, 20, 10, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(507, 20, 11, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(508, 20, 12, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(509, 20, 13, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(510, 20, 14, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(511, 20, 15, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(512, 20, 16, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(513, 20, 17, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(514, 20, 18, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(515, 20, 19, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(516, 20, 20, 3.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(517, 20, 21, 2.00, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(518, 20, 22, 3.00, 344, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(519, 20, 23, 3.00, 348, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(520, 20, 24, NULL, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(521, 20, 25, NULL, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(522, 20, 26, NULL, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(523, 20, 27, NULL, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(524, 20, 28, NULL, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(525, 20, 29, NULL, NULL, '', '2025-09-05 18:57:00', '2025-09-05 18:57:00'),
(526, 21, 1, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(527, 21, 2, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(528, 21, 3, 3.00, 255, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(529, 21, 4, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(530, 21, 5, 2.00, 265, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(531, 21, 6, 2.00, 267, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(532, 21, 7, 1.00, 271, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(533, 21, 8, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(534, 21, 9, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(535, 21, 10, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(536, 21, 11, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(537, 21, 12, 2.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(538, 21, 13, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(539, 21, 14, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(540, 21, 15, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(541, 21, 16, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(542, 21, 17, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(543, 21, 18, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(544, 21, 19, 2.00, 327, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(545, 21, 20, 1.00, 335, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(546, 21, 21, 0.00, 342, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(547, 21, 21, 0.00, 343, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(548, 21, 22, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(549, 21, 23, 3.00, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(550, 21, 24, NULL, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(551, 21, 25, NULL, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(552, 21, 26, NULL, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(553, 21, 27, NULL, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(554, 21, 28, NULL, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(555, 21, 29, NULL, NULL, '', '2025-09-05 19:03:31', '2025-09-05 19:03:31'),
(556, 22, 1, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(557, 22, 2, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(558, 22, 3, 2.00, 256, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(559, 22, 4, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(560, 22, 5, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(561, 22, 6, 2.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(562, 22, 7, 2.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(563, 22, 8, 2.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(564, 22, 9, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(565, 22, 10, 1.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(566, 22, 11, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(567, 22, 12, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(568, 22, 13, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(569, 22, 14, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(570, 22, 15, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(571, 22, 16, 1.00, 314, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(572, 22, 16, 1.00, 315, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(573, 22, 17, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(574, 22, 18, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(575, 22, 19, 3.00, 330, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(576, 22, 20, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(577, 22, 21, 3.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(578, 22, 22, 2.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(579, 22, 23, 1.00, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(580, 22, 24, NULL, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(581, 22, 25, NULL, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(582, 22, 26, NULL, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(583, 22, 27, NULL, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(584, 22, 28, NULL, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(585, 22, 29, NULL, NULL, '', '2025-09-05 19:05:54', '2025-09-05 19:05:54'),
(616, 24, 1, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(617, 24, 2, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(618, 24, 3, 2.00, 253, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(619, 24, 4, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(620, 24, 5, 3.00, 265, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(621, 24, 6, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(622, 24, 7, 2.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(623, 24, 8, 1.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(624, 24, 9, 0.00, 282, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(625, 24, 10, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(626, 24, 11, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(627, 24, 12, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(628, 24, 13, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(629, 24, 14, 2.00, 305, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(630, 24, 15, 3.00, 309, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(631, 24, 16, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(632, 24, 17, 2.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(633, 24, 18, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(634, 24, 19, 3.00, 327, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(635, 24, 20, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(636, 24, 21, 3.00, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(637, 24, 22, 2.00, 344, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(638, 24, 23, 1.00, 348, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(639, 24, 24, NULL, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(640, 24, 25, NULL, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(641, 24, 26, NULL, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(642, 24, 27, NULL, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(643, 24, 28, NULL, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(644, 24, 29, NULL, NULL, '', '2025-09-05 21:49:39', '2025-09-05 21:49:39'),
(645, 25, 1, 3.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(646, 25, 2, 3.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(647, 25, 3, 2.00, 255, '', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(648, 25, 3, 2.00, 256, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(649, 25, 4, 2.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(650, 25, 5, 2.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(651, 25, 6, 1.00, 266, '', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(652, 25, 6, 1.00, 267, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(653, 25, 7, 3.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(654, 25, 8, 3.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(655, 25, 9, 3.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(656, 25, 10, 3.00, 284, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(657, 25, 11, 3.00, 289, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(658, 25, 12, 3.00, 298, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(659, 25, 13, 3.00, 304, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(660, 25, 14, 3.00, 305, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(661, 25, 15, 3.00, 308, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(662, 25, 16, 3.00, 314, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(663, 25, 17, 2.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(664, 25, 18, 3.00, 325, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(665, 25, 19, 3.00, 327, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(666, 25, 20, 3.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(667, 25, 21, 3.00, NULL, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(668, 25, 22, 1.00, 344, '', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(669, 25, 22, 1.00, 345, '', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(670, 25, 23, 0.00, 347, 'كده غلط', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(671, 25, 23, 0.00, 348, 'كده غلط', '2025-09-05 21:54:34', '2025-09-16 10:19:38'),
(672, 25, 24, NULL, NULL, '', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(673, 25, 25, NULL, NULL, '', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(674, 25, 26, NULL, NULL, '', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(675, 25, 27, NULL, NULL, '', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(676, 25, 28, NULL, NULL, '', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(677, 25, 29, NULL, NULL, '', '2025-09-05 21:54:34', '2025-09-05 21:54:34'),
(678, 26, 1, 3.00, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(679, 26, 2, 3.00, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(680, 26, 3, 3.00, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(681, 26, 4, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(682, 26, 5, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(683, 26, 6, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(684, 26, 7, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(685, 26, 8, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(686, 26, 9, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(687, 26, 10, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(688, 26, 11, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(689, 26, 12, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(690, 26, 13, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(691, 26, 14, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(692, 26, 15, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(693, 26, 16, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(694, 26, 17, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(695, 26, 18, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(696, 26, 19, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(697, 26, 20, 3.00, 331, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(698, 26, 20, 3.00, 332, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(699, 26, 20, 3.00, 333, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(700, 26, 21, 3.00, 340, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(701, 26, 21, 3.00, 341, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(702, 26, 22, 1.00, 344, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(703, 26, 23, 0.00, 347, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(704, 26, 24, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(705, 26, 25, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(706, 26, 26, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(707, 26, 27, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(708, 26, 28, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(709, 26, 29, NULL, NULL, '', '2025-09-05 21:55:43', '2025-09-05 21:55:43'),
(710, 27, 1, 3.00, 245, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(711, 27, 2, 3.00, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(712, 27, 3, 2.00, 253, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(713, 27, 3, 2.00, 254, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(714, 27, 4, 3.00, 260, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(715, 27, 5, 3.00, 264, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(716, 27, 6, 3.00, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(717, 27, 7, 1.00, 271, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(718, 27, 7, 1.00, 272, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(719, 27, 8, 0.00, 274, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(720, 27, 8, 0.00, 275, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(721, 27, 9, 3.00, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(722, 27, 10, 3.00, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(723, 27, 11, 3.00, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(724, 27, 12, 3.00, 297, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(725, 27, 12, 3.00, 298, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(726, 27, 13, 3.00, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(727, 27, 14, 3.00, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(728, 27, 15, 3.00, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(729, 27, 16, 3.00, 315, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(730, 27, 17, 3.00, 321, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(731, 27, 18, 3.00, 326, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(732, 27, 19, 3.00, 329, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(733, 27, 20, 3.00, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(734, 27, 21, 1.00, 340, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(735, 27, 21, 1.00, 341, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(736, 27, 22, 3.00, 344, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(737, 27, 23, 2.00, 347, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(738, 27, 24, NULL, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(739, 27, 25, NULL, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(740, 27, 26, NULL, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(741, 27, 27, NULL, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(742, 27, 28, NULL, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(743, 27, 29, NULL, NULL, '', '2025-09-05 22:42:07', '2025-09-05 22:42:07'),
(775, 29, 1, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(776, 29, 2, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(777, 29, 3, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(778, 29, 4, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(779, 29, 5, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(780, 29, 6, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(781, 29, 7, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(782, 29, 8, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(783, 29, 9, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(784, 29, 10, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(785, 29, 11, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(786, 29, 12, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(787, 29, 13, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(788, 29, 14, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(789, 29, 15, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(790, 29, 16, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(791, 29, 17, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(792, 29, 18, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(793, 29, 19, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(794, 29, 20, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(795, 29, 21, 0.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(796, 29, 22, 0.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(797, 29, 23, 0.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(798, 29, 24, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(799, 29, 25, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(800, 29, 26, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(801, 29, 27, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(802, 29, 28, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(803, 29, 29, 3.00, NULL, '', '2025-09-06 00:59:54', '2025-09-16 10:12:43'),
(835, 31, 1, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(836, 31, 2, 2.00, 249, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(837, 31, 3, 0.00, 253, 'vggjfgv', '2025-09-13 10:52:14', '2025-09-13 10:52:14'),
(838, 31, 3, 0.00, 254, 'vggjfgv', '2025-09-13 10:52:14', '2025-09-13 10:52:14'),
(839, 31, 3, 0.00, 255, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(840, 31, 4, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(841, 31, 5, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(842, 31, 6, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(843, 31, 7, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(844, 31, 8, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(845, 31, 9, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(846, 31, 10, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(847, 31, 11, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(848, 31, 12, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(849, 31, 13, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(850, 31, 14, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(851, 31, 15, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(852, 31, 16, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(853, 31, 17, 3.00, 321, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(854, 31, 18, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(855, 31, 19, 3.00, 329, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(856, 31, 20, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(857, 31, 21, 3.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(858, 31, 22, 4.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(859, 31, 23, 2.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:29:01'),
(860, 31, 24, 0.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:17:39'),
(861, 31, 25, 0.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:17:39'),
(862, 31, 26, 0.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:17:39'),
(863, 31, 27, 0.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:17:39'),
(864, 31, 28, 0.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:17:39'),
(865, 31, 29, 0.00, NULL, '', '2025-09-13 10:52:14', '2025-09-16 09:17:39'),
(866, 32, 1, 3.00, 245, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(867, 32, 2, 3.00, 251, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(868, 32, 3, 3.00, 253, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(869, 32, 3, 3.00, 254, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(870, 32, 4, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(871, 32, 5, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(872, 32, 6, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(873, 32, 7, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(874, 32, 8, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(875, 32, 9, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(876, 32, 10, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(877, 32, 11, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(878, 32, 12, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(879, 32, 13, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(880, 32, 14, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(881, 32, 15, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(882, 32, 16, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(883, 32, 17, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(884, 32, 18, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(885, 32, 19, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(886, 32, 20, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(887, 32, 21, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(888, 32, 22, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(889, 32, 23, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(890, 32, 24, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(891, 32, 25, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(892, 32, 26, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(893, 32, 27, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(894, 32, 28, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(895, 32, 29, NULL, NULL, '', '2025-09-16 12:39:46', '2025-09-16 12:39:46'),
(896, 33, 1, 3.00, 247, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(897, 33, 2, 3.00, 248, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(898, 33, 3, 3.00, 252, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(899, 33, 4, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(900, 33, 5, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(901, 33, 6, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(902, 33, 7, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(903, 33, 8, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(904, 33, 9, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(905, 33, 10, 2.00, 284, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(906, 33, 11, 1.00, 289, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(907, 33, 12, 0.00, 293, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(908, 33, 13, 0.00, 300, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(909, 33, 14, 3.00, NULL, 'صعغ بيسهغبيغسيبهسغعي بسيلي', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(910, 33, 15, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(911, 33, 16, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(912, 33, 17, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(913, 33, 18, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(914, 33, 19, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(915, 33, 20, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(916, 33, 21, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(917, 33, 22, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(918, 33, 23, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(919, 33, 24, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(920, 33, 25, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(921, 33, 26, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(922, 33, 27, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(923, 33, 28, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(924, 33, 29, NULL, NULL, '', '2025-09-16 12:41:26', '2025-09-16 12:41:26'),
(925, 34, 1, 3.00, 239, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(926, 34, 2, 2.00, 249, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(927, 34, 3, 3.00, 252, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(928, 34, 4, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(929, 34, 5, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(930, 34, 6, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(931, 34, 7, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(932, 34, 8, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(933, 34, 9, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(934, 34, 10, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(935, 34, 11, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(936, 34, 12, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(937, 34, 13, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(938, 34, 14, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(939, 34, 15, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(940, 34, 16, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(941, 34, 17, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(942, 34, 18, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(943, 34, 19, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(944, 34, 20, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(945, 34, 21, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(946, 34, 22, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(947, 34, 23, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(948, 34, 24, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(949, 34, 25, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(950, 34, 26, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(951, 34, 27, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(952, 34, 28, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(953, 34, 29, NULL, NULL, '', '2025-09-16 19:04:55', '2025-09-16 19:04:55'),
(954, 35, 1, 4.00, NULL, NULL, '2025-09-17 10:03:40', '2025-09-17 10:03:40'),
(955, 35, 2, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(956, 35, 3, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(957, 35, 4, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(958, 35, 5, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(959, 35, 6, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(960, 35, 7, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(961, 35, 8, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(962, 35, 9, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(963, 35, 10, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(964, 35, 11, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(965, 35, 12, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(966, 35, 13, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(967, 35, 14, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(968, 35, 15, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(969, 35, 16, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(970, 35, 17, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(971, 35, 18, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(972, 35, 19, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(973, 35, 20, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(974, 35, 21, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(975, 35, 22, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(976, 35, 23, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(977, 35, 24, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(978, 35, 25, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(979, 35, 26, 1.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(980, 35, 27, 2.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(981, 35, 28, 3.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(982, 35, 29, 4.00, NULL, NULL, '2025-09-17 10:03:41', '2025-09-17 10:03:41'),
(983, 36, 1, 3.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(984, 36, 2, 1.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(985, 36, 3, 1.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(986, 36, 4, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(987, 36, 5, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(988, 36, 6, 3.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(989, 36, 7, 3.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(990, 36, 8, 3.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(991, 36, 9, 3.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(992, 36, 10, 3.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(993, 36, 11, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(994, 36, 12, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(995, 36, 13, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(996, 36, 14, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(997, 36, 15, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(998, 36, 16, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(999, 36, 17, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1000, 36, 18, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1001, 36, 19, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1002, 36, 20, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1003, 36, 21, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1004, 36, 22, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1005, 36, 23, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1006, 36, 24, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1007, 36, 25, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1008, 36, 26, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1009, 36, 27, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1010, 36, 28, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1011, 36, 29, 2.00, NULL, NULL, '2025-09-17 10:13:11', '2025-09-17 10:13:11'),
(1012, 37, 1, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1013, 37, 2, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1014, 37, 3, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1015, 37, 4, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1016, 37, 5, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1017, 37, 6, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1018, 37, 7, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1019, 37, 8, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1020, 37, 9, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1021, 37, 10, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1022, 37, 11, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1023, 37, 12, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1024, 37, 13, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1025, 37, 14, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1026, 37, 15, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1027, 37, 16, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1028, 37, 17, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1029, 37, 18, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1030, 37, 19, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1031, 37, 20, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1032, 37, 21, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1033, 37, 22, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1034, 37, 23, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1035, 37, 24, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1036, 37, 25, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1037, 37, 26, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1038, 37, 27, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1039, 37, 28, 3.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1040, 37, 29, 2.00, NULL, NULL, '2025-09-17 10:23:04', '2025-09-17 10:23:04'),
(1041, 38, 1, 0.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1042, 38, 2, 1.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1043, 38, 3, 2.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1044, 38, 4, 3.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1045, 38, 5, 2.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1046, 38, 6, 1.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1047, 38, 7, 0.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1048, 38, 8, NULL, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1049, 38, 9, 0.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1050, 38, 10, 1.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1051, 38, 11, 2.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1052, 38, 12, 3.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1053, 38, 13, 2.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1054, 38, 14, 0.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1055, 38, 15, NULL, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1056, 38, 16, 0.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1057, 38, 17, 1.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1058, 38, 18, 2.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1059, 38, 19, 3.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1060, 38, 20, 2.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1061, 38, 21, 1.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1062, 38, 22, 0.00, NULL, NULL, '2025-09-17 14:05:29', '2025-09-17 14:05:29'),
(1063, 39, 1, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1064, 39, 2, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1065, 39, 3, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1066, 39, 4, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1067, 39, 5, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1068, 39, 6, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1069, 39, 7, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1070, 39, 8, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1071, 39, 9, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1072, 39, 10, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1073, 39, 11, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1074, 39, 12, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1075, 39, 13, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1076, 39, 14, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1077, 39, 15, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1078, 39, 16, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1079, 39, 17, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1080, 39, 18, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1081, 39, 19, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1082, 39, 20, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1083, 39, 21, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1084, 39, 22, 2.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1085, 39, 23, 3.00, NULL, NULL, '2025-09-17 14:14:54', '2025-09-17 14:14:54'),
(1086, 40, 1, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1087, 40, 2, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1088, 40, 3, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1089, 40, 4, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1090, 40, 5, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1091, 40, 6, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1092, 40, 7, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1093, 40, 8, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1094, 40, 9, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1095, 40, 10, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1096, 40, 11, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1097, 40, 12, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1098, 40, 13, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1099, 40, 14, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1100, 40, 15, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1101, 40, 16, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1102, 40, 17, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1103, 40, 18, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1104, 40, 19, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1105, 40, 20, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1106, 40, 21, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1107, 40, 22, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1108, 40, 23, 3.00, NULL, NULL, '2025-09-17 14:19:09', '2025-09-17 14:19:09'),
(1109, 41, 1, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1110, 41, 2, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1111, 41, 3, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1112, 41, 4, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1113, 41, 5, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1114, 41, 6, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1115, 41, 7, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1116, 41, 8, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1117, 41, 9, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1118, 41, 10, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1119, 41, 11, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1120, 41, 12, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1121, 41, 13, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1122, 41, 14, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1123, 41, 15, 3.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1124, 41, 16, 0.00, NULL, '', '2025-09-17 14:22:40', '2025-09-17 14:23:14'),
(1125, 42, 1, 3.00, 240, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1126, 42, 2, 3.00, 248, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1127, 42, 3, 3.00, 253, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1128, 42, 4, 3.00, 259, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1129, 42, 5, 3.00, 263, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1130, 42, 6, 3.00, 267, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1131, 42, 7, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1132, 42, 8, 3.00, 274, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1133, 42, 10, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1134, 42, 11, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1135, 42, 12, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1136, 42, 13, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1137, 42, 14, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1138, 42, 15, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1139, 42, 16, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1140, 42, 17, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1141, 42, 18, 3.00, NULL, '', '2025-09-17 14:48:59', '2025-09-17 14:56:12'),
(1142, 43, 1, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1143, 43, 2, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1144, 43, 3, 2.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1145, 43, 4, 2.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1146, 43, 5, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1147, 43, 6, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1148, 43, 7, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1149, 43, 8, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1150, 43, 9, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1151, 43, 10, 2.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1152, 43, 11, 1.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1153, 43, 12, 2.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1154, 43, 13, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1155, 43, 14, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1156, 43, 15, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1157, 43, 16, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1158, 43, 17, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1159, 43, 18, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1160, 43, 19, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1161, 43, 20, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1162, 43, 21, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1163, 43, 22, 3.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1164, 43, 23, 2.00, NULL, NULL, '2025-09-17 16:33:47', '2025-09-17 16:33:47'),
(1165, 44, 1, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1166, 44, 2, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1167, 44, 3, 2.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1168, 44, 4, 2.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1169, 44, 5, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1170, 44, 6, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1171, 44, 7, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1172, 44, 8, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1173, 44, 9, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1174, 44, 10, 2.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1175, 44, 11, 1.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1176, 44, 12, 2.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1177, 44, 13, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1178, 44, 14, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1179, 44, 15, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1180, 44, 16, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1181, 44, 17, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1182, 44, 18, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1183, 44, 19, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1184, 44, 20, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1185, 44, 21, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1186, 44, 22, 3.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1187, 44, 23, 2.00, NULL, NULL, '2025-09-17 16:36:44', '2025-09-17 16:36:44'),
(1188, 45, 1, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1189, 45, 2, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1190, 45, 3, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1191, 45, 4, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1192, 45, 5, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1193, 45, 6, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1194, 45, 7, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1195, 45, 8, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1196, 45, 9, 2.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1197, 45, 10, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1198, 45, 11, 2.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1199, 45, 12, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1200, 45, 13, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1201, 45, 14, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1202, 45, 15, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1203, 45, 16, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1204, 45, 17, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1205, 45, 18, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1206, 45, 19, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1207, 45, 20, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1208, 45, 21, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1209, 45, 22, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49'),
(1210, 45, 23, 3.00, NULL, NULL, '2025-09-18 04:13:49', '2025-09-18 04:13:49');

-- --------------------------------------------------------

--
-- Structure for view `roles`
--
DROP TABLE IF EXISTS `roles`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `roles`  AS SELECT `user_roles`.`id` AS `id`, `user_roles`.`name` AS `name`, `user_roles`.`display_name` AS `display_name`, `user_roles`.`description` AS `description`, `user_roles`.`permissions` AS `permissions`, `user_roles`.`created_at` AS `created_at`, `user_roles`.`updated_at` AS `updated_at` FROM `user_roles` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_years`
--
ALTER TABLE `academic_years`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `coordinator_supervisors`
--
ALTER TABLE `coordinator_supervisors`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_subject_unique` (`user_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `educational_levels`
--
ALTER TABLE `educational_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `elearning_attendance`
--
ALTER TABLE `elearning_attendance`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `grade_id` (`grade_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `coordinator_id` (`coordinator_id`),
  ADD KEY `idx_date` (`lesson_date`),
  ADD KEY `idx_teacher` (`teacher_id`),
  ADD KEY `idx_subject` (`subject_id`),
  ADD KEY `idx_school` (`school_id`);

--
-- Indexes for table `elearning_attendance_old`
--
ALTER TABLE `elearning_attendance_old`
  ADD PRIMARY KEY (`id`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `date_index` (`date`);

--
-- Indexes for table `evaluation_domains`
--
ALTER TABLE `evaluation_domains`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `evaluation_indicators`
--
ALTER TABLE `evaluation_indicators`
  ADD PRIMARY KEY (`id`),
  ADD KEY `domain_id` (`domain_id`),
  ADD KEY `sort_order` (`sort_order`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `level_id` (`level_id`);

--
-- Indexes for table `qatar_system_criteria`
--
ALTER TABLE `qatar_system_criteria`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `qatar_system_performance`
--
ALTER TABLE `qatar_system_performance`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_evaluation` (`teacher_id`,`subject_id`,`academic_year_id`,`term`,`evaluation_date`),
  ADD KEY `academic_year_id` (`academic_year_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `evaluator_id` (`evaluator_id`),
  ADD KEY `evaluation_date_index` (`evaluation_date`);

--
-- Indexes for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_recommendation` (`indicator_id`,`text`(255)),
  ADD KEY `indicator_id` (`indicator_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `sections`
--
ALTER TABLE `sections`
  ADD PRIMARY KEY (`id`),
  ADD KEY `grade_id` (`grade_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `subjects`
--
ALTER TABLE `subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `subjects_created_by_foreign` (`created_by`),
  ADD KEY `subjects_updated_by_foreign` (`updated_by`),
  ADD KEY `subjects_school_id_foreign` (`school_id`);

--
-- Indexes for table `system_settings`
--
ALTER TABLE `system_settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`setting_key`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `action` (`action`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `user_roles`
--
ALTER TABLE `user_roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `name` (`name`);

--
-- Indexes for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `last_activity` (`last_activity`);

--
-- Indexes for table `visitor_types`
--
ALTER TABLE `visitor_types`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `visits`
--
ALTER TABLE `visits`
  ADD PRIMARY KEY (`id`),
  ADD KEY `school_id` (`school_id`),
  ADD KEY `teacher_id` (`teacher_id`),
  ADD KEY `subject_id` (`subject_id`),
  ADD KEY `visitor_type_id` (`visitor_type_id`),
  ADD KEY `grade_id` (`grade_id`),
  ADD KEY `section_id` (`section_id`),
  ADD KEY `level_id` (`level_id`),
  ADD KEY `visitor_person_id` (`visitor_person_id`),
  ADD KEY `visits_academic_year_id_foreign` (`academic_year_id`);

--
-- Indexes for table `visit_evaluations`
--
ALTER TABLE `visit_evaluations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `visit_id` (`visit_id`),
  ADD KEY `indicator_id` (`indicator_id`),
  ADD KEY `recommendation_id` (`recommendation_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_years`
--
ALTER TABLE `academic_years`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `coordinator_supervisors`
--
ALTER TABLE `coordinator_supervisors`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `educational_levels`
--
ALTER TABLE `educational_levels`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `elearning_attendance`
--
ALTER TABLE `elearning_attendance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `elearning_attendance_old`
--
ALTER TABLE `elearning_attendance_old`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `evaluation_domains`
--
ALTER TABLE `evaluation_domains`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `evaluation_indicators`
--
ALTER TABLE `evaluation_indicators`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `qatar_system_criteria`
--
ALTER TABLE `qatar_system_criteria`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `qatar_system_performance`
--
ALTER TABLE `qatar_system_performance`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `recommendations`
--
ALTER TABLE `recommendations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=615;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `system_settings`
--
ALTER TABLE `system_settings`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=425;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=299;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=326;

--
-- AUTO_INCREMENT for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=183;

--
-- AUTO_INCREMENT for table `user_roles`
--
ALTER TABLE `user_roles`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `user_sessions`
--
ALTER TABLE `user_sessions`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `visitor_types`
--
ALTER TABLE `visitor_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

--
-- AUTO_INCREMENT for table `visit_evaluations`
--
ALTER TABLE `visit_evaluations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1211;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `coordinator_supervisors`
--
ALTER TABLE `coordinator_supervisors`
  ADD CONSTRAINT `coord_sup_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `coord_sup_subject_fk` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `coord_sup_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `elearning_attendance`
--
ALTER TABLE `elearning_attendance`
  ADD CONSTRAINT `elearning_attendance_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  ADD CONSTRAINT `elearning_attendance_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`),
  ADD CONSTRAINT `elearning_attendance_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `elearning_attendance_ibfk_4` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `elearning_attendance_ibfk_5` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`),
  ADD CONSTRAINT `elearning_attendance_ibfk_6` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`),
  ADD CONSTRAINT `elearning_attendance_ibfk_7` FOREIGN KEY (`coordinator_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `elearning_attendance_old`
--
ALTER TABLE `elearning_attendance_old`
  ADD CONSTRAINT `elearning_attendance_old_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  ADD CONSTRAINT `elearning_attendance_old_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `elearning_attendance_old_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `elearning_attendance_old_ibfk_4` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `evaluation_indicators`
--
ALTER TABLE `evaluation_indicators`
  ADD CONSTRAINT `evaluation_indicators_ibfk_1` FOREIGN KEY (`domain_id`) REFERENCES `evaluation_domains` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `qatar_system_performance`
--
ALTER TABLE `qatar_system_performance`
  ADD CONSTRAINT `qatar_system_performance_ibfk_1` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`),
  ADD CONSTRAINT `qatar_system_performance_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`),
  ADD CONSTRAINT `qatar_system_performance_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`),
  ADD CONSTRAINT `qatar_system_performance_ibfk_4` FOREIGN KEY (`evaluator_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD CONSTRAINT `recommendations_ibfk_1` FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_grade_id_foreign` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`),
  ADD CONSTRAINT `sections_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`);

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `subjects_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `subjects_updated_by_foreign` FOREIGN KEY (`updated_by`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_school_id_foreign` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`);

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_subject_id_foreign` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_teacher_id_foreign` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_role_fk` FOREIGN KEY (`role_id`) REFERENCES `user_roles` (`id`),
  ADD CONSTRAINT `users_school_fk` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`);

--
-- Constraints for table `user_activity_log`
--
ALTER TABLE `user_activity_log`
  ADD CONSTRAINT `activity_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `user_sessions`
--
ALTER TABLE `user_sessions`
  ADD CONSTRAINT `sessions_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) REFERENCES `academic_years` (`id`) ON DELETE RESTRICT,
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_4` FOREIGN KEY (`visitor_type_id`) REFERENCES `visitor_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_5` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_6` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_7` FOREIGN KEY (`level_id`) REFERENCES `educational_levels` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_8` FOREIGN KEY (`visitor_person_id`) REFERENCES `teachers` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `visit_evaluations`
--
ALTER TABLE `visit_evaluations`
  ADD CONSTRAINT `visit_evaluations_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visit_evaluations_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visit_evaluations_ibfk_3` FOREIGN KEY (`recommendation_id`) REFERENCES `recommendations` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
