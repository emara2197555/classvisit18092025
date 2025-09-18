-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 18, 2025 at 01:08 PM
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
(1, 1, 'خطة الدرس متوفرة وبنودها مستكملة ومناسبة.', 'Lesson plan is available and published on Teams, its elements are appropriate and well correlated.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 1),
(2, 1, 'أهداف التعلم مناسبة ودقيقة الصياغة وقابلة للقياس.', 'Lesson objectives are SMART and correctly stated.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 2),
(3, 1, 'أنشطة الدرس الرئيسة واضحة ومتدرجة ومرتبطة بالأهداف.', 'Lesson sequence is clear, well organized and related to the learning objectives.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 3),
(4, 2, 'أهداف التعلم معروضة ويتم مناقشتها.', 'Students are aware of lesson objectives.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 4),
(5, 2, 'أنشطة التمهيد مفعلة بشكل مناسب.', 'Starter activity is appropriate and well implemented.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 5),
(6, 2, 'محتوى الدرس واضح والعرض منظّم ومترابط.', 'The lesson follows logical progression and lesson activities are clearly linked.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 6),
(7, 2, 'طرائق التدريس وإستراتيجياته متنوعه وتتمحور حول الطالب.', 'A variety of student-centered strategies are successfully applied.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 7),
(8, 2, 'مصادر التعلم الرئيسة والمساندة موظّفة بصورة واضحة وسليمة.', 'Lesson resources- key and supporting- are appropriate and well-exploited.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 8),
(9, 2, 'الوسائل التعليميّة والتكنولوجيا موظّفة بصورة مناسبة.', 'Appropriate teaching tools and technology are used.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 9),
(10, 2, 'الأسئلة الصفية ذات صياغة سليمة ومتدرجة ومثيرة للتفكير.', 'Questions are well formulated and follow logical sequence to provoke critical thinking skills.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 10),
(11, 2, 'المادة العلمية دقيقة و مناسبة.', 'Subject knowledge is accurate and appropriate.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 11),
(12, 2, 'الكفايات الأساسية متضمنة في السياق المعرفي للدرس.', 'Key competencies are incorporated in the content of the lesson.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 12),
(13, 2, 'القيم الأساسية متضمنة في السياق المعرفي للدرس.', 'Key values are incorporated in the content of the lesson.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 13),
(14, 2, 'التكامل بين محاور المادة ومع المواد الأخرى يتم بشكل مناسب.', 'Subject integration and integration of the four skills in English is evident.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 14),
(15, 2, 'الفروق الفردية بين الطلبة يتم مراعاتها.', 'Differentiation is considered throughout the lesson.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 15),
(16, 2, 'غلق الدرس يتم بشكل مناسب.', 'The closure task is appropriate.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 16),
(17, 3, 'أساليب التقويم (القبلي والبنائي والختامي) مناسبة ومتنوعة.', 'The lesson incorporates a variety of assessment tools.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-17 14:40:47', 17),
(18, 3, 'التغذية الراجعة متنوعة ومستمرة.', 'Monitoring of students\' performance is on-going and appropriate, and timely feedback is provided.', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 18),
(19, 3, 'أعمال الطلبة متابعة ومصححة بدقة ورقيًا وإلكترونيًا.', 'Students\' work is checked in an appropriate and timely manner (both on paper and On Teams).', NULL, NULL, 0.00, '2025-05-16 08:25:23', '2025-09-18 07:08:45', 19),
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
(21, '7', 12, 1, '2025-05-16 08:01:16', '2025-05-16 08:01:16'),
(28, 'ESE', 10, 1, '2025-09-18 06:15:20', '2025-09-18 06:15:20'),
(29, 'ESE', 11, 1, '2025-09-18 06:15:31', '2025-09-18 06:15:31'),
(30, '8', 12, 1, '2025-09-18 06:15:40', '2025-09-18 06:15:40');

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
(334, 'جاسم جمعه عبدالله  المريخى', '26563400092', 'j.al-meraikhi2112', 'مدير', '55504093', 1, '2025-09-05 18:31:36', '2025-09-18 06:57:52', NULL),
(335, 'سيف محمد سيف الجفالى النعيمى', '27963400657', 's.al-naimi0801', 'النائب الأكاديمي', '51600065', 1, '2025-09-05 18:31:36', '2025-09-18 06:57:52', NULL),
(336, 'ياسر محمد أحمد عبد الرحمن', '28281810411', 'y.abdelrahman1005', 'مدير', '31031067', 1, '2025-09-05 18:31:36', '2025-09-18 06:57:52', NULL),
(337, 'وليد فوزي محمد عماره', '27581807586', 'w.emara2109', 'مدير', '30463336', 1, '2025-09-05 18:31:36', '2025-09-18 06:57:52', NULL),
(338, 'محمد رياض عمار', '28478801169', 'm.ammar0504', 'مدير', '33147731', 1, '2025-09-05 18:31:37', '2025-09-18 06:57:52', NULL),
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
(409, 'موجه أحياء', '12345678901', 'Biology', 'موجه المادة', '12345678', 1, '2025-09-05 18:44:52', '2025-09-18 06:57:52', NULL),
(410, 'موجه الفنون البصرية', '12345678902', 'art', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(411, 'موجه اللغة العربية', '12345678903', 'arabic', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(412, 'موجه المهارات الحياتية', '12345678904', 'Skills', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(413, 'موجه إدارة أعمال', '12345678905', 'Administration', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(414, 'موجه تربية خاصة', '12345678906', 'Special', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(415, 'موجه تربية رياضة', '12345678907', 'sport', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(416, 'موجه حاسب آلي', '12345678908', 'computer', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(417, 'موجه رياضيات', '12345678909', 'math', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(418, 'موجه شرعية', '12345678910', 'islam', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(419, 'موجه علوم', '12345678911', 'Science', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(420, 'موجه علوم اجتماعية', '12345678912', 'Social', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(421, 'موجه فيزياء', '12345678913', 'Physics', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(422, 'موجه كيمياء', '12345678914', 'Chemistry', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
(423, 'موجه لغة إنجليزية', '12345678915', 'English', 'موجه المادة', '12345678', 1, '2025-09-05 18:45:41', '2025-09-18 06:57:52', NULL),
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
(1, 'admin_user', 'admin@school.edu', '$2y$10$lULwHbg87xgcdGnb2Dzqm..OTmKcL/N8aDk6X6G7RYRkTt7xw.Lam', 'مدير النظام', 1, 1, 1, '2025-09-18 10:44:48', NULL, NULL, '2025-09-05 14:09:14', '2025-09-18 10:44:48'),
(235, 'j.al-meraikhi2112', 'j.al-meraikhi2112', '$2y$10$BhljEBgxrNl06LexrFeZDu4Pzcg/Z4sJcTNRMozvTDVmQ8pGGyPKa', 'جاسم جمعه عبدالله  المريخى', 2, 1, 1, '2025-09-05 21:17:51', NULL, NULL, '2025-09-05 18:31:36', '2025-09-05 21:17:51'),
(236, 's.al-naimi0801', 's.al-naimi0801', '$2y$10$lL7B75hDhBT1qBPK6Lcfr.keMeDntthjKN9b7Wwnl5CP/XlsXiGuq', 'سيف محمد سيف الجفالى النعيمى', 3, 1, 1, '2025-09-05 21:17:00', NULL, NULL, '2025-09-05 18:31:36', '2025-09-05 21:17:00'),
(237, 'y.abdelrahman1005', 'y.abdelrahman1005', '$2y$10$JxKlzNMmC/ZTikFyVAVez.T.FpioCfPcVP86ODDhgOpMmfRGp5u2G', 'ياسر محمد أحمد عبد الرحمن', 2, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:36', '2025-09-05 18:31:36'),
(238, 'w.emara2109', 'w.emara2109@education.qa', '$2y$10$9Lfj16Jy6Y1bRWbrhEE2P.3Jtvu.N.V4msAsbufxaApBmj.Xruh8G', 'وليد فوزي محمد عماره', 1, 1, 1, '2025-09-11 04:55:31', NULL, NULL, '2025-09-05 18:31:37', '2025-09-11 04:55:31'),
(239, 'm.ammar0504', 'm.ammar0504', '$2y$10$NYUpCacwsMMcdVj7aUadTepwKsB3/0Y9hvOBJiLnTUoRz2EM7gA1G', 'محمد رياض عمار', 2, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(240, 'm.ali0308', 'm.ali0308', '$2y$10$BAJFCr.WoAHc2Gvj/b6JDOX7BkaN09F5YFiuIKgNn0FFN1nqjmx.C', 'محمد مصطفى عبداللطيف  علي', 5, 1, 1, '2025-09-18 07:32:20', NULL, NULL, '2025-09-05 18:31:37', '2025-09-18 07:32:20'),
(241, 'r.eldisouky0612', 'r.eldisouky0612', '$2y$10$apWQ7hoDrtCZCg1A8vmVUuRB6fwUZLG4gvd4YnFz3iAJSTW3KL0GK', 'رضا فتحي عبدالمقصود  الدسوقى', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(242, 'k.aljabr0902', 'k.aljabr0902', '$2y$10$xIWx0QZn3kKXKPtTKbaD0OogiCzO.hau4uW6nC5kFe0XlZ0/KAu.2', 'خالد خميس المحمد  الجبر', 6, 1, 1, NULL, NULL, NULL, '2025-09-05 18:31:37', '2025-09-05 18:31:37'),
(243, 'a.alqudah1402', 'a.alqudah1402', '$2y$10$3z6thoLhRLJ.aKA2vOZ2Wux4m8HTqKhbCNnKW3M5BV6PgR0jyRVxW', 'عواد علي عواد  القضاه', 6, 1, 1, '2025-09-18 12:05:49', NULL, NULL, '2025-09-05 18:31:37', '2025-09-18 12:05:49'),
(244, 'a.aly2202', 'a.aly2202', '$2y$10$v9Em03TmCi8rEfNaO.45lunR3T8suwimIeY.m4AC8l3qoCTGD3/4.', 'عبدالعزيز معوض عبدالعزيز  علي', 6, 1, 1, '2025-09-18 11:55:48', NULL, NULL, '2025-09-05 18:31:37', '2025-09-18 11:55:48'),
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
(267, 'a.abdelrahman010129', 'a.abdelrahman010129', '$2y$10$C/1XLqQs3XjoZ8sILkrUX.pJTtqOAjCp5MJGegmHOdPWdL8s05deG', 'عبدالمحسن محمد أحمد عبدالرحمن', 5, 1, 1, '2025-09-18 12:17:24', NULL, NULL, '2025-09-05 18:31:41', '2025-09-18 12:17:24'),
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
(310, 'teacher_409', NULL, '$2y$10$nW8uWdpsU2p5kLBFByT3t.XLqwROUfzM5XEkyUhCDT8yp7he/P7/2', 'موجه أحياء', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-18 07:02:10'),
(311, 'teacher_410', NULL, '$2y$10$7YhXBDl1.kcC7Otmu1qeZO2icqsF5254rSrNAXNwHF8RYP7RXN.CO', 'موجه الفنون البصرية', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-18 07:02:10'),
(312, 'teacher_411', NULL, '$2y$10$6tH/tDGbNHIlVf43cF8WXu/iP6NrI/eDU5yO/dVOrOx7j3RMNVAWi', 'موجه اللغة العربية', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-18 07:02:10'),
(313, 'teacher_412', NULL, '$2y$10$r9VQmGxxAd48U3gr3S/e9uA6ZnSE3K0FfIzpDfIG1Vkktp3EDdNwq', 'موجه المهارات الحياتية', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-18 07:02:10'),
(314, 'teacher_413', NULL, '$2y$10$Jv/OyBSqGZ3AVXEV4QvPZeBH.sfUE9Z6mlggDN3/mlDxRdsKBppfS', 'موجه إدارة أعمال', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-18 07:02:10'),
(315, 'teacher_414', NULL, '$2y$10$vznXQiF9tNgXGfExAeVwdedX/TX8qdyFa.1LNNMaAIcmtHdk8V26i', 'موجه تربية خاصة', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-18 07:02:10'),
(316, 'teacher_415', NULL, '$2y$10$YvPN/9318Dt20m6HtDRXgO1TQdYFc/mB7YTvIi05dbYgUHxc2MDbq', 'موجه تربية رياضة', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-18 07:02:10'),
(317, 'teacher_416', NULL, '$2y$10$/xp1CBmHQQqNwbIGe3AngOLEdirxb9VCxvS5NdgMVsvfl99/B.8nC', 'موجه حاسب آلي', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:41', '2025-09-18 07:02:10'),
(318, 'teacher_417', NULL, '$2y$10$CZ9F6bc6qaK2ofRtge/jv.ANU2pLpNgkB/j5KsDJyUBqqrtrJKdge', 'موجه رياضيات', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-18 07:02:10'),
(319, 'teacher_418', NULL, '$2y$10$b4RC7cEJFfOKHM6pB.7UJOCMZU5ykC0hI1CMq29YdtSMM/5dRlQfW', 'موجه شرعية', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-18 07:02:10'),
(320, 'teacher_419', NULL, '$2y$10$oKP93/79EeESeHqfyPjU9.bcvVUnoOxMOR9/x66bByBbMwFxQgXXC', 'موجه علوم', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-18 07:02:10'),
(321, 'teacher_420', NULL, '$2y$10$586xMTt2GuJTs3t2Us1HK.81ag7BU.5LkzUTlU6TFIb4hyfFpewy2', 'موجه علوم اجتماعية', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-18 07:02:10'),
(322, 'teacher_421', NULL, '$2y$10$mnVOsjajX8EtuHL2WA89R.vBeDZppkJY4c/tpIHuJXjLUEavAi7Ri', 'موجه فيزياء', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-18 07:02:10'),
(323, 'teacher_422', NULL, '$2y$10$t/Wzbz2m61faJUrjJy56muxQ3gs9qTIKwA5TEqP7i.SjjSz10tB1K', 'موجه كيمياء', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-18 07:02:10'),
(324, 'teacher_423', 'english@education.qa', '$2y$10$LKgG4p7nuWHQCgHBlyJiwufs6LoIwRoSE9ZX/DS/IhcDeDRw2uSOe', 'موجه اللغة الإنجليزية', 4, 1, 1, NULL, NULL, NULL, '2025-09-05 23:58:42', '2025-09-18 07:02:10'),
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
(182, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 04:03:26'),
(183, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 06:52:30'),
(184, 267, 'login', 'users', 267, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 06:52:37'),
(185, 267, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 06:55:56'),
(186, 267, 'login', 'users', 267, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 06:56:02'),
(187, 267, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 06:58:38'),
(188, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 06:58:44'),
(189, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:04:11'),
(190, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:04:30'),
(191, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:08:38'),
(192, 267, 'login', 'users', 267, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:08:44'),
(193, 267, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:09:41'),
(194, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:09:59'),
(195, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:13:24'),
(196, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:13:38'),
(197, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:19:21'),
(198, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:22:07'),
(200, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:32:07'),
(201, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:32:13'),
(202, 240, 'login', 'users', 240, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 07:32:20'),
(203, 240, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 09:50:34'),
(204, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 09:50:52'),
(205, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 09:53:28'),
(206, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 09:53:43'),
(207, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 09:54:29'),
(208, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 09:54:35'),
(209, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 10:44:36'),
(210, 1, 'login', 'users', 1, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 10:44:48'),
(211, 1, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:01:52'),
(212, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:02:00'),
(213, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:03:26'),
(214, 243, 'login', 'users', 243, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:03:29'),
(215, 243, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:55:41'),
(216, 244, 'login', 'users', 244, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:55:48'),
(217, 244, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:55:52'),
(218, 267, 'login', 'users', 267, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:55:58'),
(219, 267, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:59:34'),
(220, 267, 'login', 'users', 267, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 11:59:41'),
(221, 267, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 12:05:43'),
(222, 243, 'login', 'users', 243, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 12:05:49'),
(223, 243, 'logout', NULL, NULL, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 12:17:09'),
(224, 267, 'login', 'users', 267, NULL, NULL, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36', '2025-09-18 12:17:24');

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
(30, 1, 339, 3, 12, 15, 3, 17, 335, '2025-09-11', 2, 'full', 'physical', 0, NULL, '', 'يجب توفّر الخطّة على نظام قطر للتعليم.\\r\\n\\r\\nيجب أن يستعرض المعلم أهداف الدرس المخطط لها بداية درسه بصورة واضحة ومناسبة.\\r\\n\\r\\nيجب استخدام وسائل تعليمية متنوعة وفعالة.\\r\\n\\r\\nيجب أن تكون الأسئلة متنوعة ومتدرجة في مستوياتها.\\r\\n\\r\\nيجب توزيع الطلبة بطريقة مناسبة وفق مستوياتهم والنشاط المنفذ.\\r\\n\\r\\nنوصي بأستعراض القيمة التربوية في سياق الدرس', '', 53.00, '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(31, 1, 367, 5, 10, 1, 3, 17, 335, '2025-09-14', 2, 'partial', 'physical', 0, NULL, '', 'نوصي بعرض الاهداف للطلبة على السبورة او في العرض التقديمي\\r\\n\\r\\nنوصي بتفعيل التقويم البنائي ( غلق الهدف الاول ) كما هو مبين في خطة التحضير ( wordwall ) لضمان التأكد من استيعاب جميع الطلبة.\\r\\n\\r\\nتم قياس اعمال الطلبة من خلال ورقة العمل التي تم توزيعها وتقديم التغذية الراجعة من المعلم', 'اشكر الاستاذ احمد القصاب على الجهد المبذول .. شكرا جزيلا ابا يحيى', 51.00, '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(32, 1, 352, 8, 11, 12, 3, 17, 335, '2025-09-14', 2, 'partial', 'physical', 0, NULL, '', 'يجب استخدام وسائل تعليمية متنوعة وفعالة.\\r\\n\\r\\nيجب أن يوظف المعلّم التكامل مع المواد الأخرى لتحقيق النمو المعرفي عند الطلاب.\\r\\n\\r\\nيجب توزيع الطلبة بطريقة مناسبة وفق مستوياتهم والنشاط المنفذ.', 'اشكر الدكتور عماد ابو مغلي جزيل الشكر على الجهود المبذولة ', 58.00, '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(33, 1, 355, 9, 12, 20, 3, 17, 335, '2025-09-15', 2, 'partial', 'physical', 0, NULL, '', 'يجب توفّر الخطّة على نظام قطر للتعليم.\\r\\n\\r\\nيجب تقييم إجابات الطلبة (الصحيحة والخاطئة) ومناقشتها، وربط إجاباتهم بمعارفهم السابقة.\\r\\n\\r\\nخطط التحضير لجميع الدروس غير مرفوعة على منصة قطر للصفوف 12-6 و 12-7\\r\\n\\r\\nنوصي بأستخدام التكنولوجيا ( word wall- quizes )\\r\\n\\r\\nنوصي بتصجيج اجابات الطلبة بالكتاب المدرسي وتقديم عبارات تعزيزية لهم', '', 50.00, '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(34, 1, 359, 9, 10, 1, 3, 15, 353, '2025-09-09', 2, 'full', 'physical', 0, NULL, '', ' - استخدام الخرائط فى مصدر التعلم وكذلك الأشكال \\r\\n- عدم عرض إجابات الأسئلة الصفية على البوربوينت قبل سؤال الطلاب', 'جهوده فى الحصة\\r\\n- مصدر التعلم موظف بصورة واضحة\\r\\n- الأسئلة الصفية ذات صياغة سليمة ومتدرجة ومثيرة للتفكير\\r\\n- مراعاة الفروق الفردية بين الطلبة', 58.00, '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(35, 1, 408, 13, 11, 28, 3, 17, 335, '2025-09-15', 2, 'full', 'physical', 0, NULL, '', 'يجب أن تكون الأسئلة مثيرة لاهتمام الطلبة وتحثهم على المشاركة وطرح الأسئلة.\\r\\n\\r\\nيجب أن يوظف المعلّم التكامل مع المواد الأخرى لتحقيق النمو المعرفي عند الطلاب.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي الفروق الفردية.\\r\\n\\r\\nيجب إعطاء الفرصة للطلاب في التفكير في الحل\\r\\n\\r\\nيجب مراعاة الوقت الكافي والمخصص لكل مراحل الدرس (التهيئة ــ العرض –الغلق).\\r\\n\\r\\nنوصي بأن يتم تفعيل القيمة التربوية في سياق الاهداف', 'اشكر الاستاذ محمد على الجهد المبذول', 58.00, '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(36, 1, 369, 5, 11, 8, 3, 15, 366, '2025-09-11', 2, 'full', 'physical', 1, NULL, '', 'يجب أن يستعرض المعلم أهداف الدرس المخطط لها بداية درسه بصورة واضحة ومناسبة.\\r\\n\\r\\nيجب أن تكون الإستراتيجيات المنفذة متنوعة وتفعل دور الطالب في عملية التعلم.\\r\\n\\r\\nيجب تفعيل السبورة التفاعلية بما يخدم الموقف التعليمي.\\r\\n\\r\\nيجب أن يوفّر المعلم للطلبة أنشطة تحديد المشكلات و التعاون مع الاخرين في اقتراح الحلول.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي أنماط التعلم (سمعي، بصري، حركي...).\\r\\n\\r\\nيجب إعلان الواجبات والاختبارات الورقية أو الإلكترونية للطلاب وأولياء الأمور بشكل دوري.\\r\\n\\r\\nيجب إثارة دافعية الطلبة للمشاركة في أنشطة التعلم بفاعلية.\\r\\n\\r\\nيجب أن تكون التعليمات دقيقة ومبسطة، وتُعطى في الوقت المناسب قبل وأثناء النشاط العملي.', 'اشكر المعلم / محمد المساعيد على تنفيذ النشاط بالمعمل واوصيه بالعمل على ما جاء بالتوصيات', 79.00, '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(37, 1, 367, 5, 11, 7, 3, 15, 366, '2025-09-07', 2, 'full', 'physical', 1, NULL, '', 'يجب أن ترتبط أنشطة التمهيد بخبرات الطلبة الحياتية وتجاربهم السابقة.\\r\\n\\r\\nيجب أن تكون الإستراتيجيات المنفذة متنوعة وتفعل دور الطالب في عملية التعلم.\\r\\n\\r\\nيجب أن يوفّر المعلم أنشطة تحث الطلبة على الالتزام بحقوقهم وواجباتهم.\\r\\n\\r\\nيجب إعلان الواجبات والاختبارات الورقية أو الإلكترونية للطلاب وأولياء الأمور بشكل دوري.\\r\\n\\r\\nيجب استخدام أساليب متنوعة للتغذية الراجعة (شفهية، كتابية، آنية أو مؤجلة) لمساعدة الطلبة على تحسين أدائهم وتطوير مهاراتهم.', 'اشكر المعلم / احمد القصاب على الجهد المبذول اثناء الحصة المعملية ، واوصيه بالعمل على ما جاء بالتوصيات ', 82.00, '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(39, 1, 374, 7, 11, 7, 3, 15, 370, '2025-09-15', 2, 'full', 'physical', 0, NULL, '', 'مراعاة إدارة زمن الحصة.\\r\\nمراعاة الفروق الفردية.\\r\\nتفعيل القيمة التربوية.\\r\\nضرورة الغلق المناسب للدرس', 'تجربة التوتر السطحي (طفو دبوس على سطح الماء).\\r\\nتفسير الظواهر العلمية.\\r\\nتفعيل القوانين الصفية.\\r\\nربط المفاهيم العلمية بالحياة.', 42.00, '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(40, 1, 395, 16, 11, 28, 3, 15, 394, '2025-09-16', 2, 'partial', 'physical', 0, NULL, '', 'يجب تطبيق إستراتيجيات تدريس تناسب أهداف الدرس وتراعي المتعلمين.\\r\\n\\r\\nيجب تنظيم العرض السبوري.\\r\\n\\r\\nيجب أن يوفّر المعلم أنشطة تمكّن الطلبة من اقتراح البدائل وإنتاج أفكار بطرائق مبتكرة.\\r\\n\\r\\nيجب أن يوفّر المعلم أنشطة تعزّز ثقة الطلبة بقدرتهم على التعلم وبذل الجهد في ذلك.\\r\\n\\r\\nيجب أن يربط المعلّم بين محاور المادة ومهاراتها بصورة فاعلة.\\r\\n\\r\\nيجب أن يكون الدور الأكبر في الغلق للطلبة.\\r\\n\\r\\nيجب التنوع في أنماط ومستويات الأسئلة المدرجة في التقييمات وأوراق العمل الإلكترونية.\\r\\n\\r\\nيجب تحفيز الطلبة على تقييم استجابات بعضهم بعضا.\\r\\n\\r\\nيجب استخدام وسائل مختلفة لضمان الالتزام بالزمن المحدد للأنشطة (مثل المؤقت أو العد التنازلي ــ الخ ...).', 'الجهد المبذول لعرض الدرس اثناء الحصة  و تفعيل القيم الأساسية بشكل رائع و مميز', 61.00, '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(41, 1, 396, 16, 12, 29, 3, 15, 394, '2025-09-16', 2, 'full', 'physical', 0, NULL, '', 'يجب أن يوفّر المعلم للطلبة أنشطة العمل التشاركي واحترام الذات، وتقبّل التغير الإيجابي.\\r\\n\\r\\nيجب أن يوفّر المعلم أنشطة تعزّز ثقة الطلبة بقدرتهم على التعلم وبذل الجهد في ذلك.\\r\\n\\r\\nيجب أن يوظف المعلّم التكامل مع المواد الأخرى لتحقيق النمو المعرفي عند الطلاب.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي الفروق الفردية.', '', 53.00, '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(42, 1, 356, 9, 11, 11, 3, 15, 353, '2025-09-11', 2, 'full', 'physical', 0, NULL, '', 'مراعاة تنفيذ استراتيجية التعلم الذاتى من خلال الطالب ( الهدف الأول - وليس الاعتماد على المناقشة والحوار - ) لما لذلك من أثر إيجابى على تعلم الطلاب\\r\\n\\r\\nمراعاة عدم عرض إجابات الاسئلة قبل سؤال الطلاب - الهدف الثالث - السؤال الثالث -\\r\\n\\r\\nمراعاة كتابة القيمة وبما يتناسب مع سياق الحصة', 'أشكر المعلم على جهوده فى الحصة \\r\\n( إدارة أنشطة التعلم والمشاركات الصفية )', 65.00, '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(43, 1, 349, 8, 10, 14, 3, 15, 348, '2025-09-16', 2, 'full', 'physical', 0, NULL, '', 'الأستاذ الفاضل بالأخذ بما ورد أعلاه من توصيات ', 'جهده المبذول الواضح في تعلم الطلاب وخاصة غرسه للقيم الإسلامية العليا ومنها التكافل والتعاون', 64.00, '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(44, 1, 390, 1, 10, 3, 3, 15, 383, '2025-09-07', 2, 'full', 'physical', 0, NULL, '', 'الخرص على التسلسل المنطقي لعناصر الدرس لضمان فهم المصطلحات الأدبية لدى الطلبة\\r\\n\\r\\nالعمل على تنويع وسائل وأساليب التدريس والابتعاد عن الطرح المباشر للدرس مع تفعيل الطلاب ومشاركتهم الإيجابية.\\r\\n\\r\\nتفعيل الوسائل التكنولوجية من تطبيقات تفاعلية وغيرها بشكل فاعل\\r\\n\\r\\nالحرص على تنويع الأسئلة الصفية وعدم الاكتفاء بأسئلة المنهج.\\r\\n\\r\\nالتركيز على الطلبة الضعاف ودفعهم للمشاركة في سير الحصة\\r\\n\\r\\nالعمل على عدم تجاوز الزمن المحدد للأنشطة لضمان تحققها جميعها', 'أشكر المعلم على اجتهاده في إدارة أنشطة الحصة والتعامل مع الطلبة.', 51.00, '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(45, 1, 373, 7, 11, 8, 3, 15, 370, '2025-09-16', 2, 'partial', 'physical', 0, NULL, '', 'يجب أن يستعرض المعلم أهداف الدرس المخطط لها بداية درسه بصورة واضحة ومناسبة.\\r\\n\\r\\nيجب تطبيق إستراتيجيات تدريس تناسب أهداف الدرس وتراعي المتعلمين.\\r\\n\\r\\nيجب استخدام وسائل تعليمية متنوعة وفعالة.\\r\\n\\r\\nيجب متابعة أعمال الطلبة بشكل دوري وتقويمها، سواء أكانت ورقية أم إلكترونية.\\r\\n\\r\\nيجب عرض الأهداف أمام الطالب طوال الحصة على اللوح أو في شرائح البوربوينت\\r\\n\\r\\nتنويع الاستراتيجيات وتتمحور حول الطالب\\r\\n\\r\\nتضمين القيمة التربوية ضمن السياق التعليمي', '', 57.00, '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(46, 1, 384, 1, 12, 14, 3, 15, 383, '2025-09-14', 2, 'full', 'physical', 0, NULL, '', '', '', 0.00, '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(47, 1, 388, 1, 10, 6, 3, 15, 383, '2025-09-15', 2, 'full', 'physical', 0, NULL, '', 'مناقشة أهداف التعلم تعمل على تحفيز الطالب وتشويقه لموضوع الدرس\\r\\n\\r\\nالحرص على تنويع إستراتيجيات التعلم النشط بما يخدم عملية التعلم الفاعلة\\r\\n\\r\\nتوظيف الأمثلة المناقشة في الدرس لتمرير القيمة المستهدفة\\r\\n\\r\\nالعمل على إشراك الطلبة الضعاف في سير الدرس بوسائل متعددة', '', 62.00, '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(48, 1, 387, 1, 10, 5, 3, 15, 383, '2025-09-15', 2, 'full', 'physical', 0, NULL, '', 'الحرص على توظيف نشاط التهيئة بشكل يخدم تهيئة أذهان الطلبة لمضمون الدرس\\r\\n\\r\\nالتدرج في مناقشة المصطلحات البلاغية يتيح للطلبة التمكن من الفهم الكامل للمصطلح\\r\\n\\r\\nالبعد ما أمكن عن الشرح المباشر للمفاهيم البلاغية وفسح المجال للطلبة للمشاركة في الشرح\\r\\n\\r\\nالعمل على التنويع الذكي في طرح الأسئلة بمختلف أنواعها لكشف جوانب الدرس\\r\\n\\r\\nالعمل على جلوس الطلبة متدني التحصيل في المقاعد الأمامية لضمان مشاركتهم في سير الحصة', '', 61.00, '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(49, 1, 390, 1, 10, 2, 3, 15, 383, '2025-09-14', 2, 'full', 'physical', 0, NULL, '', 'يجب أن يوفّر المعلم أنشطة تمكّن الطلبة من التواصل استماعاً وتحدثاً وكتابة، وتوظيف ذلك لأغراض مختلفة.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي أنماط التعلم (سمعي، بصري، حركي...).\\r\\n\\r\\nيجب التنويع في استخدام أدوات التقويم بما يناسب الموقف التعليمي (ملاحظة المعلم ــ تقييم الذات ــ اختبارات ذهنية وشفوية ــ الأسئلة الشفوية – تطبيق إلكتروني – مناقشة إلكترونية ...).\\r\\n\\r\\nالعمل على مناقشة الأهداف لما له من أثر في تشويق الطلبة إلى موضوع الدرس\\r\\n\\r\\nالحرص على تفعيل إستراتيجيات التعلم النشط واختيار الأنسب لدرس قراءة النص الشعري\\r\\n\\r\\nتعزيز القيمة المستهدفة من خلال التناول الأدبي للأبيات الشعرية', '', 56.00, '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(50, 1, 364, 6, 11, 9, 3, 15, 362, '2025-09-15', 2, 'full', 'physical', 0, NULL, '', 'يجب أن تتوافر التهيئة في الخطّة وأن تكون مرتبطة بموضوع الدرس وأهدافه.\\r\\n\\r\\nيجب أن تراعي الأهداف التنوع بين المستويات المعرفية والمهارية.\\r\\n\\r\\nيجب أن تكون الأنشطة مرتبطة بأهداف الدرس وتساعد على تحقيقها.\\r\\n\\r\\nيجب أن تراعي الأنشطة التدرج والتسلسل في تحقيق أهداف الدرس.\\r\\n\\r\\nيجب إن تعزّز الأنشطة الرئيسة الكفايات والقيم الأساسية ضمن السياق المعرفي.\\r\\n\\r\\nيجب أن تراعي الأنشطة بوضوح الفروق الفردية بين الطلبة.\\r\\n\\r\\nيجب تنفيذ أنشطة التمهيد بطريقة جاذبة وشائقة.\\r\\n\\r\\nيجب تطبيق إستراتيجيات تدريس تراعي المتعلمين.\\r\\n\\r\\nيجب أن تكون الإستراتيجيات المنفذة متنوعة وتفعل دور الطالب في عملية التعلم.\\r\\n\\r\\nيجب توظيف التكنولوجيا بما يخدم الموقف التعليمي والأهداف.\\r\\n\\r\\nيجب أن يوفّر المعلم أنشطة تنمّي مهارات الطلبة اللغوية لتوظيفها في التعبير عن الآراء و الأفكار.\\r\\n\\r\\nيجب توزيع الطلبة بطريقة مناسبة وفق مستوياتهم والنشاط المنفذ.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي الفروق الفردية.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي أنماط التعلم (سمعي، بصري، حركي...).', '', 63.00, '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(51, 1, 374, 7, 11, 7, 3, 15, 370, '2025-09-17', 2, 'partial', 'physical', 0, NULL, '', 'يجب تطبيق إستراتيجيات تدريس تراعي المتعلمين.\\r\\n\\r\\nيجب توظيف التكنولوجيا بما يخدم الموقف التعليمي والأهداف.\\r\\n\\r\\nيجب أن يوفّر المعلم أنشطة تساهم في اعتزاز الطلبة باللغة العربية والتاريخ و التقاليد القطرية.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي أنماط التعلم (سمعي، بصري، حركي...).\\r\\n\\r\\nيجب أن يعكس الغلق تحقق أهداف الدرس.\\r\\n\\r\\nيجب التنويع في أساليب التقويم مراعاةً للفروق الفردية بين الطلبة.', '', 62.00, '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(52, 1, 351, 8, 12, 16, 3, 15, 348, '2025-09-16', 2, 'full', 'physical', 0, NULL, '', '1- مشاركة جميع الطلاب في العملية التعليمية\\r\\n2- مراعاة الفروق الفردية بشكل واضح عند تنفيذ النشاط\\r\\n3- متابعة أعمال الطلاب الكتابية . ', 'على جهده المبذول في الحصة الدراسية ', 0.00, '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(53, 1, 382, 2, 10, 5, 3, 17, 335, '2025-09-17', 2, 'full', 'physical', 0, NULL, '', 'نوصي بعرض الاهداف للطلبة على السبورة او في العرض التقديمي\\r\\n\\r\\nنوصي بتفعيل المصدر الرئيس ( الكتاب المدرسي )\\r\\n\\r\\nنوصي بمتابعة اعمال الطلبة في كتاب الانشطة وتصحيحها وكتابة عبارات تعزيزة لها', '', 54.00, '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(54, 1, 371, 7, 10, 2, 3, 15, 370, '2025-09-17', 2, 'partial', 'physical', 0, NULL, '', 'يجب أن تكون الإستراتيجيات المنفذة متنوعة وتفعل دور الطالب في عملية التعلم.\\r\\n\\r\\nيجب أن يوفّر المعلم أنشطة تساهم في اعتزاز الطلبة باللغة العربية والتاريخ و التقاليد القطرية.\\r\\n\\r\\nيجب توزيع الطلبة بطريقة مناسبة وفق مستوياتهم والنشاط المنفذ.', '', 61.00, '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(55, 1, 342, 3, 11, 9, 3, 17, 335, '2025-09-17', 2, 'partial', 'physical', 0, NULL, '', 'يجب أن تراعي الأنشطة بوضوح الفروق الفردية بين الطلبة.\\r\\n\\r\\nيجب أن تكون الإستراتيجيات المنفذة متنوعة وتفعل دور الطالب في عملية التعلم.\\r\\n\\r\\nيجب أن تكون الأسئلة متنوعة ومتدرجة في مستوياتها.\\r\\n\\r\\nيجب أن تكون الأسئلة مثيرة لاهتمام الطلبة وتحثهم على المشاركة وطرح الأسئلة.\\r\\n\\r\\nيجب أن يوظف المعلّم التكامل مع المواد الأخرى لتحقيق النمو المعرفي عند الطلاب.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي الفروق الفردية.\\r\\n\\r\\nيجب إعطاء الفرصة للطلاب في التفكير في الحل\\r\\n\\r\\nنوصي بأستخدام التكنولوجيا ( word wall- quizes )\\r\\n\\r\\nنوصي بتفعيل القيمة التربوية', 'اشكر الاستاذ عواد جزيل الشكر على الجهد المبذول ', 51.00, '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(56, 1, 347, 3, 11, 8, 3, 17, 335, '2025-09-17', 2, 'partial', 'physical', 0, NULL, '', 'يجب أن يوظف المعلّم التكامل مع المواد الأخرى لتحقيق النمو المعرفي عند الطلاب.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي الفروق الفردية.\\r\\n\\r\\nنوصي بكتابة بيانات المعلم في خطة التحضير\\r\\n\\r\\nنوصي بقراءة الاهداف ومناقشتها مع الطلبة قبل بداية الدرس\\r\\n\\r\\nنوصي تضمين القيمة التربوية في سياق الهدف', 'اشكر استاذ محمد جزيل الشكر على الجهد المبذول ', 59.00, '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(57, 1, 373, 7, 11, 9, 3, 15, 370, '2025-09-17', 2, 'partial', 'physical', 0, NULL, '', 'يجب تطبيق إستراتيجيات تدريس تناسب أهداف الدرس وتراعي المتعلمين.\\r\\n\\r\\nيجب استخدام وسائل تعليمية متنوعة وفعالة.\\r\\n\\r\\nيجب تقديم أنشطة وتدريبات تراعي الفروق الفردية.', '', 63.00, '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(58, 1, 386, 1, 12, 20, 3, 15, 383, '2025-09-18', 2, 'full', 'physical', 0, NULL, '', 'العمل على تعديل بيانات الخطة المرفوعة على المنصة لضمان التمكن الذهني من مادة الدرس\\r\\n\\r\\nالعمل على مناقشة الأهداف لما له من أثر في تشويق الطلبة إلى موضوع الدرس\\r\\n\\r\\nالتسلسل المنطقي لمحتوى الدرس يضمن تحقق الفهم والتفاعل الجيد عند الطلبة\\r\\n\\r\\nتفعيل التعلم النشط من أهم الأمور التي تمكن الطالب من التمكن من المهارات الكتابية\\r\\n\\r\\nتوظيف الموقف التعليمي لتعزيز القيمة المستهدفة\\r\\n\\r\\nالعمل على تنظيم جلوس الطلبة في الغرفة الصفية', '', 53.00, '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(59, 1, 347, 3, 11, 9, 3, 15, 339, '2025-09-04', 2, 'full', 'physical', 0, NULL, '', 'تنظيم جلوس الطلاب في الصف و عدم تركطالب يجلس بروحه  ', 'الاعداد الجيد في الحصة و الحرص على حل الطلاب و التغذية الراجعة المستمرة لهم ', 33.00, '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(60, 1, 346, 3, 10, 4, 3, 15, 339, '2025-09-11', 2, 'full', 'physical', 0, NULL, '', '1- الالتزام بالتوقيت الزمني لكل نشاط بما فيها نشاط التمهيد مع الاخذ في الاعتبار تعديل توقيتات خطة الدرس بما يتلائم مع التوقيت الزمني ليوم الخميس حيث تكون الحصة 40 دقيقة فقط \\r\\n2- ربط كل نشاط بتوقيت زمني محدد بحيث يكون امام الطالب الفرصة للتفكير في الاجابة \\r\\n3- استخدام استراتيجية الاكتشاف الموجه خلال شرح فقرة استكشف', 'كل الشكر للاستاذ عماد على جهده المبذول مع الطلاب و جعله الله في ميزان حسناته ', 34.00, '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(61, 1, 366, 5, 12, 30, 3, 16, 421, '2025-09-18', 2, 'full', 'physical', 0, 'بلح ', 'فا قفا قصلا ثقل سث', 'بليق ل يقل يقل يل يل بل يب كوكو ', 'بوبو بوب ', 57.00, '2025-09-18 12:20:03', '2025-09-18 12:20:03');

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
(804, 30, 1, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(805, 30, 2, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(806, 30, 3, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(807, 30, 4, 3.00, 259, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(808, 30, 5, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(809, 30, 6, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(810, 30, 7, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(811, 30, 8, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(812, 30, 9, 3.00, 279, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(813, 30, 10, 3.00, 283, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(814, 30, 11, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(815, 30, 12, 2.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(816, 30, 13, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(817, 30, 14, NULL, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(818, 30, 15, 3.00, 307, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(819, 30, 16, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(820, 30, 17, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(821, 30, 18, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(822, 30, 19, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(823, 30, 20, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(824, 30, 21, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(825, 30, 22, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(826, 30, 23, 3.00, NULL, '', '2025-09-11 02:15:32', '2025-09-18 03:33:26'),
(827, 30, 24, 0.00, NULL, '', '2025-09-11 02:15:32', '2025-09-14 07:31:52'),
(828, 30, 25, 0.00, NULL, '', '2025-09-11 02:15:32', '2025-09-14 07:31:52'),
(829, 30, 26, 0.00, NULL, '', '2025-09-11 02:15:32', '2025-09-14 07:31:52'),
(830, 30, 27, 0.00, NULL, '', '2025-09-11 02:15:32', '2025-09-14 07:31:52'),
(831, 30, 28, 0.00, NULL, '', '2025-09-11 02:15:32', '2025-09-14 07:31:52'),
(832, 30, 29, 0.00, NULL, '', '2025-09-11 02:15:32', '2025-09-14 07:31:52'),
(833, 31, 1, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(834, 31, 2, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(835, 31, 3, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(836, 31, 4, 0.00, NULL, 'نوصي بعرض الاهداف للطلبة على السبورة او في العرض التقديمي ', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(837, 31, 5, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(838, 31, 6, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(839, 31, 7, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(840, 31, 8, 2.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(841, 31, 9, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(842, 31, 10, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(843, 31, 11, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(844, 31, 12, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(845, 31, 13, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(846, 31, 14, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(847, 31, 15, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(848, 31, 16, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(849, 31, 17, 1.00, NULL, 'نوصي بتفعيل التقويم البنائي ( غلق الهدف الاول ) كما هو مبين في خطة التحضير ( wordwall ) لضمان التأكد من استيعاب جميع الطلبة.', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(850, 31, 18, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(851, 31, 19, 3.00, NULL, 'تم قياس اعمال الطلبة من خلال ورقة العمل التي تم توزيعها وتقديم التغذية الراجعة من المعلم', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(852, 31, 20, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(853, 31, 21, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(854, 31, 22, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(855, 31, 23, 3.00, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(856, 31, 24, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(857, 31, 25, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(858, 31, 26, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(859, 31, 27, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(860, 31, 28, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(861, 31, 29, NULL, NULL, '', '2025-09-14 02:36:20', '2025-09-14 02:36:20'),
(862, 32, 1, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(863, 32, 2, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(864, 32, 3, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(865, 32, 4, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(866, 32, 5, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(867, 32, 6, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(868, 32, 7, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(869, 32, 8, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(870, 32, 9, 2.00, 279, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(871, 32, 10, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(872, 32, 11, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(873, 32, 12, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(874, 32, 13, NULL, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(875, 32, 14, 2.00, 306, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(876, 32, 15, 1.00, 307, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(877, 32, 16, NULL, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(878, 32, 17, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(879, 32, 18, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(880, 32, 19, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(881, 32, 20, 2.00, 333, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(882, 32, 21, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(883, 32, 22, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(884, 32, 23, 3.00, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(885, 32, 24, NULL, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(886, 32, 25, NULL, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(887, 32, 26, NULL, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(888, 32, 27, NULL, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(889, 32, 28, NULL, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(890, 32, 29, NULL, NULL, '', '2025-09-14 05:44:01', '2025-09-14 05:44:01'),
(891, 33, 1, 0.00, 239, 'خطط التحضير لجميع الدروس غير مرفوعة على منصة قطر للصفوف 12-6 و 12-7', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(892, 33, 2, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(893, 33, 3, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(894, 33, 4, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(895, 33, 5, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(896, 33, 6, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(897, 33, 7, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(898, 33, 8, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(899, 33, 9, 3.00, NULL, 'نوصي بأستخدام التكنولوجيا ( word wall- quizes ) ', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(900, 33, 10, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(901, 33, 11, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(902, 33, 12, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(903, 33, 13, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(904, 33, 14, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(905, 33, 15, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(906, 33, 16, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(907, 33, 17, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(908, 33, 18, 2.00, 324, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(909, 33, 19, NULL, NULL, 'نوصي بتصجيج اجابات الطلبة بالكتاب المدرسي وتقديم عبارات تعزيزية لهم ', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(910, 33, 20, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(911, 33, 21, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(912, 33, 22, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(913, 33, 23, 3.00, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(914, 33, 24, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(915, 33, 25, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(916, 33, 26, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(917, 33, 27, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(918, 33, 28, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(919, 33, 29, NULL, NULL, '', '2025-09-15 02:24:17', '2025-09-15 02:24:17'),
(920, 34, 1, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(921, 34, 2, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(922, 34, 3, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(923, 34, 4, 2.00, 259, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(924, 34, 5, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(925, 34, 6, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(926, 34, 7, 2.00, NULL, 'مراعاة تنفيذ استراتيجية التعلم الذاتى من خلال الطالب ( الهدف الثالث ) وليس الاعتماد على المناقشة والحوار - ) لما لذلك من أثر ايجابى على تعلم الطلاب ', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(927, 34, 8, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(928, 34, 9, 1.00, NULL, 'مراعاة استخدام الخريطة فى مصدر التعلم ص 18 وكذلك صور أشكال سطح الأرض الساحلية فى ص 20 من خلال البوربوينت وليس استخدام Google earth ومراعاة عدم عرض إجابات الأسئلة الصفية على البوربوينت قبل سؤال الطلاب ( الهدف الثانى ) فيها  ', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(929, 34, 10, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(930, 34, 11, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(931, 34, 12, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(932, 34, 13, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(933, 34, 14, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(934, 34, 15, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(935, 34, 16, 0.00, 316, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(936, 34, 17, 2.00, NULL, 'مراعاة الاهتمام بتنفيذ التقييم الختامى ( الهدف الأول )', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(937, 34, 18, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(938, 34, 19, NULL, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(939, 34, 20, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(940, 34, 21, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(941, 34, 22, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(942, 34, 23, 3.00, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(943, 34, 24, NULL, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(944, 34, 25, NULL, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(945, 34, 26, NULL, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(946, 34, 27, NULL, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(947, 34, 28, NULL, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(948, 34, 29, NULL, NULL, '', '2025-09-15 03:08:07', '2025-09-15 03:08:07'),
(949, 35, 1, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(950, 35, 2, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(951, 35, 3, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(952, 35, 4, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(953, 35, 5, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(954, 35, 6, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(955, 35, 7, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(956, 35, 8, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(957, 35, 9, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(958, 35, 10, 2.00, 285, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(959, 35, 11, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(960, 35, 12, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(961, 35, 13, 2.00, NULL, 'نوصي بأن يتم تفعيل القيمة التربوية في سياق الاهداف ', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(962, 35, 14, 0.00, 306, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(963, 35, 15, 2.00, 308, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(964, 35, 16, NULL, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(965, 35, 17, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(966, 35, 18, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(967, 35, 19, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(968, 35, 20, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(969, 35, 21, 2.00, 343, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(970, 35, 22, 3.00, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(971, 35, 23, 2.00, 347, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(972, 35, 24, NULL, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(973, 35, 25, NULL, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(974, 35, 26, NULL, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(975, 35, 27, NULL, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(976, 35, 28, NULL, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(977, 35, 29, NULL, NULL, '', '2025-09-15 03:16:36', '2025-09-15 03:16:36'),
(978, 36, 1, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(979, 36, 2, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(980, 36, 3, 2.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(981, 36, 4, 2.00, 259, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(982, 36, 5, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(983, 36, 6, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(984, 36, 7, 2.00, 273, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(985, 36, 8, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(986, 36, 9, 2.00, 282, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(987, 36, 10, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(988, 36, 11, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(989, 36, 12, 2.00, 299, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(990, 36, 13, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(991, 36, 14, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(992, 36, 15, 2.00, 309, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(993, 36, 16, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(994, 36, 17, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(995, 36, 18, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(996, 36, 19, 2.00, 330, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(997, 36, 20, 2.00, 338, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(998, 36, 21, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(999, 36, 22, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(1000, 36, 23, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(1001, 36, 24, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(1002, 36, 25, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(1003, 36, 26, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(1004, 36, 27, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(1005, 36, 28, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(1006, 36, 29, 3.00, NULL, '', '2025-09-15 03:36:09', '2025-09-18 11:57:21'),
(1007, 37, 1, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1008, 37, 2, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1009, 37, 3, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1010, 37, 4, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1011, 37, 5, 2.00, 265, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1012, 37, 6, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1013, 37, 7, 2.00, 273, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1014, 37, 8, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1015, 37, 9, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1016, 37, 10, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1017, 37, 11, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1018, 37, 12, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1019, 37, 13, 2.00, 303, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1020, 37, 14, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1021, 37, 15, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1022, 37, 16, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1023, 37, 17, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1024, 37, 18, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1025, 37, 19, 2.00, 330, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1026, 37, 20, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1027, 37, 21, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1028, 37, 22, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1029, 37, 23, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1030, 37, 24, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1031, 37, 25, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1032, 37, 26, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1033, 37, 27, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1034, 37, 28, 3.00, NULL, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1035, 37, 29, 2.00, 354, '', '2025-09-15 03:43:34', '2025-09-15 03:43:34'),
(1070, 39, 1, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1071, 39, 2, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1072, 39, 3, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1073, 39, 4, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1074, 39, 5, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1075, 39, 6, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1076, 39, 7, 2.00, 270, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1077, 39, 8, 2.00, 274, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1078, 39, 9, 2.00, 279, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1079, 39, 10, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1080, 39, 11, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1081, 39, 12, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1082, 39, 13, 2.00, 300, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1083, 39, 14, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1084, 39, 15, 2.00, 308, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1085, 39, 16, 2.00, 313, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1086, 39, 17, NULL, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1087, 39, 18, 3.00, 322, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1088, 39, 19, 3.00, 328, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1089, 39, 20, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1090, 39, 21, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1091, 39, 22, 3.00, NULL, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1092, 39, 23, 3.00, 347, '', '2025-09-15 06:16:17', '2025-09-17 02:15:08'),
(1093, 39, 24, 0.00, NULL, '', '2025-09-15 06:16:17', '2025-09-15 06:21:39'),
(1094, 39, 25, 0.00, NULL, '', '2025-09-15 06:16:17', '2025-09-15 06:21:39'),
(1095, 39, 26, 0.00, NULL, '', '2025-09-15 06:16:17', '2025-09-15 06:21:39'),
(1096, 39, 27, 0.00, NULL, '', '2025-09-15 06:16:17', '2025-09-15 06:21:39'),
(1097, 39, 28, 0.00, NULL, '', '2025-09-15 06:16:17', '2025-09-15 06:21:39'),
(1098, 39, 29, 0.00, NULL, '', '2025-09-15 06:16:17', '2025-09-15 06:21:39'),
(1099, 40, 1, 4.00, NULL, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1100, 40, 2, 4.00, NULL, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1101, 40, 3, 4.00, NULL, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1102, 40, 4, 4.00, NULL, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1103, 40, 5, 4.00, NULL, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1104, 40, 6, 4.00, NULL, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1105, 40, 7, 3.00, 270, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1106, 40, 8, 4.00, NULL, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1107, 40, 9, 2.00, 281, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1108, 40, 10, 4.00, NULL, '', '2025-09-16 02:48:05', '2025-09-16 04:32:46'),
(1109, 40, 11, 4.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1110, 40, 12, 3.00, 293, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1111, 40, 13, 2.00, 302, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1112, 40, 14, 2.00, 305, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1113, 40, 15, 4.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1114, 40, 16, 3.00, 315, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1115, 40, 17, 2.00, 321, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1116, 40, 18, 3.00, 326, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1117, 40, 19, 4.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1118, 40, 20, 3.00, 331, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1119, 40, 21, 4.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1120, 40, 22, 4.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1121, 40, 23, 2.00, 348, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1122, 40, 24, 0.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1123, 40, 25, 0.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1124, 40, 26, 0.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1125, 40, 27, 0.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1126, 40, 28, 0.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1127, 40, 29, 0.00, NULL, '', '2025-09-16 02:48:06', '2025-09-16 04:32:46'),
(1128, 41, 1, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1129, 41, 2, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1130, 41, 3, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1131, 41, 4, NULL, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1132, 41, 5, NULL, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1133, 41, 6, NULL, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1134, 41, 7, NULL, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1135, 41, 8, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1136, 41, 9, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1137, 41, 10, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1138, 41, 11, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1139, 41, 12, 2.00, 297, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1140, 41, 13, 2.00, 302, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1141, 41, 14, 2.00, 306, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1142, 41, 15, 2.00, 308, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1143, 41, 16, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1144, 41, 17, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1145, 41, 18, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1146, 41, 19, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1147, 41, 20, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1148, 41, 21, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1149, 41, 22, 3.00, NULL, '', '2025-09-16 02:55:34', '2025-09-16 02:55:34'),
(1150, 41, 23, 3.00, NULL, '', '2025-09-16 02:55:35', '2025-09-16 02:55:35'),
(1151, 41, 24, NULL, NULL, '', '2025-09-16 02:55:35', '2025-09-16 02:55:35'),
(1152, 41, 25, NULL, NULL, '', '2025-09-16 02:55:35', '2025-09-16 02:55:35'),
(1153, 41, 26, NULL, NULL, '', '2025-09-16 02:55:35', '2025-09-16 02:55:35'),
(1154, 41, 27, NULL, NULL, '', '2025-09-16 02:55:35', '2025-09-16 02:55:35'),
(1155, 41, 28, NULL, NULL, '', '2025-09-16 02:55:35', '2025-09-16 02:55:35'),
(1156, 41, 29, NULL, NULL, '', '2025-09-16 02:55:35', '2025-09-16 02:55:35'),
(1157, 42, 1, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1158, 42, 2, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1159, 42, 3, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1160, 42, 4, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1161, 42, 5, 2.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1162, 42, 6, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1163, 42, 7, 2.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1164, 42, 8, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1165, 42, 9, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1166, 42, 10, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1167, 42, 11, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1168, 42, 12, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1169, 42, 13, 1.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1170, 42, 14, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1171, 42, 15, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1172, 42, 16, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1173, 42, 17, 3.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1174, 42, 18, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1175, 42, 19, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1176, 42, 20, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1177, 42, 21, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1178, 42, 22, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1179, 42, 23, 4.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1180, 42, 24, 0.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1181, 42, 25, 0.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1182, 42, 26, 0.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1183, 42, 27, 0.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1184, 42, 28, 0.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1185, 42, 29, 0.00, NULL, '', '2025-09-16 02:56:18', '2025-09-16 03:10:56'),
(1186, 43, 1, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1187, 43, 2, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1188, 43, 3, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1189, 43, 4, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1190, 43, 5, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1191, 43, 6, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1192, 43, 7, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1193, 43, 8, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1194, 43, 9, 2.00, NULL, 'أوصي الزميل بتفعيل السبورة التفاعلية وخاصة عند مشاركة الطلاب في حل الأنشطة على السبورة', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1195, 43, 10, 2.00, NULL, 'أوصي بتخصص بعض أسئلة مهارات التفكير العليا لغثارة التفكير والتحدي لدى الطلاب', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1196, 43, 11, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1197, 43, 12, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1198, 43, 13, 2.00, NULL, 'أوصي بترك المجال أكثر للطلاب لربط مجالات التكافل من خلال حياتهم اليومية ', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1199, 43, 14, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1200, 43, 15, 2.00, NULL, 'أوصي بتوظيف بعض التطبيقات الالكترونية مثل وورد وول التي تساعد في مراعاة الفروق الفردية لدى الطلاب', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1201, 43, 16, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1202, 43, 17, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1203, 43, 18, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1204, 43, 19, 2.00, NULL, 'أوصي الأستاذ الفاضل بكتابة عبارات تعزيزية موجّهة على كتاب الطالب', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1205, 43, 20, 3.00, 333, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1206, 43, 21, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1207, 43, 22, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1208, 43, 23, 3.00, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1209, 43, 24, NULL, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1210, 43, 25, NULL, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1211, 43, 26, NULL, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1212, 43, 27, NULL, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1213, 43, 28, NULL, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1214, 43, 29, NULL, NULL, '', '2025-09-16 03:05:30', '2025-09-16 03:05:30'),
(1215, 44, 1, 0.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1216, 44, 2, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1217, 44, 3, 2.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1218, 44, 4, 4.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1219, 44, 5, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1220, 44, 6, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1221, 44, 7, 1.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1222, 44, 8, 4.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1223, 44, 9, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1224, 44, 10, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1225, 44, 11, 4.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1226, 44, 12, 4.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1227, 44, 13, 4.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1228, 44, 14, 4.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1229, 44, 15, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1230, 44, 16, 0.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1231, 44, 17, 2.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1232, 44, 18, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1233, 44, 19, 0.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1234, 44, 20, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1235, 44, 21, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1236, 44, 22, 4.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1237, 44, 23, 3.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1238, 44, 24, 0.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1239, 44, 25, 0.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1240, 44, 26, 0.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1241, 44, 27, 0.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1242, 44, 28, 0.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1243, 44, 29, 0.00, NULL, '', '2025-09-16 03:29:04', '2025-09-16 03:47:44'),
(1244, 45, 1, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1245, 45, 2, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1246, 45, 3, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1247, 45, 4, 2.00, 259, 'يجب عرض الأهداف أمام الطالب طوال الحصة على اللوح أو في شرائح البوربوينت', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1248, 45, 5, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1249, 45, 6, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1250, 45, 7, 1.00, 270, 'تنويع الاستراتيجيات وتتمحور حول الطالب', '2025-09-16 03:35:30', '2025-09-16 03:35:30'),
(1251, 45, 7, 1.00, 271, 'تنويع الاستراتيجيات وتتمحور حول الطالب', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1252, 45, 8, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1253, 45, 9, 2.00, 279, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1254, 45, 10, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1255, 45, 11, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1256, 45, 12, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1257, 45, 13, NULL, NULL, 'تضمين القيمة التربوية ضمن السياق التعليمي', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1258, 45, 14, NULL, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1259, 45, 15, 2.00, 308, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1260, 45, 16, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1261, 45, 17, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1262, 45, 18, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1263, 45, 19, 2.00, 328, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1264, 45, 20, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1265, 45, 21, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1266, 45, 22, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1267, 45, 23, 3.00, NULL, '', '2025-09-16 03:35:30', '2025-09-16 07:24:16'),
(1268, 45, 24, NULL, NULL, '', '2025-09-16 03:35:30', '2025-09-16 03:35:30'),
(1269, 45, 25, NULL, NULL, '', '2025-09-16 03:35:30', '2025-09-16 03:35:30'),
(1270, 45, 26, NULL, NULL, '', '2025-09-16 03:35:30', '2025-09-16 03:35:30'),
(1271, 45, 27, NULL, NULL, '', '2025-09-16 03:35:30', '2025-09-16 03:35:30'),
(1272, 45, 28, NULL, NULL, '', '2025-09-16 03:35:30', '2025-09-16 03:35:30'),
(1273, 45, 29, NULL, NULL, '', '2025-09-16 03:35:30', '2025-09-16 03:35:30'),
(1274, 46, 1, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1275, 46, 2, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1276, 46, 3, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1277, 46, 4, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1278, 46, 5, 3.00, NULL, 'الحرص على الاستفادة القصوى من أنشطة التمهيد في الولوج إلى موضوع الحصة', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1279, 46, 6, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1280, 46, 7, 3.00, NULL, 'العمل على تفعيل التعلم النشط بشكل أكبر', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1281, 46, 8, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1282, 46, 9, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1283, 46, 10, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1284, 46, 11, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1285, 46, 12, 3.00, NULL, 'يرجى ربط الكفاية المستهدفة مرتبطة بالنشاط المعني بتحقيقها', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1286, 46, 13, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1287, 46, 14, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1288, 46, 15, 3.00, NULL, 'تخصيص أنشطة بعينها للطلبة الضعاف', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1289, 46, 16, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1290, 46, 17, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1291, 46, 18, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1292, 46, 19, 0.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1293, 46, 20, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1294, 46, 21, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1295, 46, 22, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1296, 46, 23, 4.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1297, 46, 24, 0.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1298, 46, 25, 0.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1299, 46, 26, 0.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1300, 46, 27, 0.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1301, 46, 28, 0.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1302, 46, 29, 0.00, NULL, '', '2025-09-16 03:57:21', '2025-09-16 05:07:01'),
(1303, 47, 1, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1304, 47, 2, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1305, 47, 3, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1306, 47, 4, 2.00, NULL, 'مناقشة أهداف التعلم تعمل على تحفيز الطالب وتشويقه لموضوع الدرس', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1307, 47, 5, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1308, 47, 6, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1309, 47, 7, 2.00, NULL, 'الحرص على تنويع إستراتيجيات التعلم النشط بما يخدم عملية التعلم الفاعلة', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1310, 47, 8, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1311, 47, 9, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1312, 47, 10, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1313, 47, 11, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1314, 47, 12, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1315, 47, 13, 2.00, NULL, 'توظيف الأمثلة المناقشة في الدرس لتمرير القيمة المستهدفة', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1316, 47, 14, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1317, 47, 15, 2.00, NULL, 'العمل على إشراك الطلبة الضعاف في سير الدرس بوسائل متعددة', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1318, 47, 16, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1319, 47, 17, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1320, 47, 18, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1321, 47, 19, NULL, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1322, 47, 20, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1323, 47, 21, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1324, 47, 22, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1325, 47, 23, 3.00, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1326, 47, 24, NULL, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1327, 47, 25, NULL, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1328, 47, 26, NULL, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1329, 47, 27, NULL, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1330, 47, 28, NULL, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1331, 47, 29, NULL, NULL, '', '2025-09-16 06:00:52', '2025-09-16 06:00:52'),
(1332, 48, 1, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1333, 48, 2, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1334, 48, 3, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1335, 48, 4, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1336, 48, 5, 2.00, NULL, 'الحرص على توظيف نشاط التهيئة بشكل يخدم تهيئة أذهان الطلبة لمضمون الدرس', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1337, 48, 6, 2.00, NULL, 'التدرج في مناقشة المصطلحات البلاغية يتيح للطلبة التمكن من الفهم الكامل للمصطلح', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1338, 48, 7, 2.00, NULL, 'البعد ما أمكن عن الشرح المباشر للمفاهيم البلاغية وفسح المجال للطلبة للمشاركة في الشرح', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1339, 48, 8, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1340, 48, 9, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1341, 48, 10, 2.00, NULL, 'العمل على التنويع الذكي في طرح الأسئلة بمختلف أنواعها لكشف جوانب الدرس', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1342, 48, 11, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1343, 48, 12, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1344, 48, 13, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1345, 48, 14, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1346, 48, 15, 2.00, NULL, 'العمل على جلوس الطلبة متدني التحصيل في المقاعد الأمامية لضمان مشاركتهم في سير الحصة', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1347, 48, 16, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1348, 48, 17, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1349, 48, 18, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1350, 48, 19, NULL, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1351, 48, 20, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1352, 48, 21, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1353, 48, 22, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1354, 48, 23, 3.00, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1355, 48, 24, NULL, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1356, 48, 25, NULL, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1357, 48, 26, NULL, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1358, 48, 27, NULL, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1359, 48, 28, NULL, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1360, 48, 29, NULL, NULL, '', '2025-09-16 06:11:06', '2025-09-16 06:11:06'),
(1361, 49, 1, NULL, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1362, 49, 2, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1363, 49, 3, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1364, 49, 4, 2.00, NULL, 'العمل على مناقشة الأهداف لما له من أثر في تشويق الطلبة إلى موضوع الدرس', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1365, 49, 5, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1366, 49, 6, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1367, 49, 7, 2.00, NULL, 'الحرص على تفعيل إستراتيجيات التعلم النشط واختيار الأنسب لدرس قراءة النص الشعري', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1368, 49, 8, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1369, 49, 9, 2.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1370, 49, 10, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1371, 49, 11, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1372, 49, 12, 2.00, 296, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1373, 49, 13, 2.00, NULL, 'تعزيز القيمة المستهدفة من خلال التناول الأدبي للأبيات الشعرية', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1374, 49, 14, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1375, 49, 15, 2.00, 309, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1376, 49, 16, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1377, 49, 17, 2.00, 319, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1378, 49, 18, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1379, 49, 19, NULL, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1380, 49, 20, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1381, 49, 21, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1382, 49, 22, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1383, 49, 23, 3.00, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1384, 49, 24, NULL, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1385, 49, 25, NULL, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1386, 49, 26, NULL, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1387, 49, 27, NULL, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1388, 49, 28, NULL, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1389, 49, 29, NULL, NULL, '', '2025-09-16 06:49:59', '2025-09-16 06:49:59'),
(1390, 50, 1, 3.00, 242, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1391, 50, 2, 3.00, 250, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1392, 50, 3, 2.00, 252, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1393, 50, 3, 2.00, 253, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1394, 50, 3, 2.00, 255, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1395, 50, 3, 2.00, 256, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1396, 50, 4, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1397, 50, 5, 2.00, 262, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1398, 50, 6, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1399, 50, 7, 2.00, 271, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1400, 50, 7, 2.00, 273, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1401, 50, 8, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1402, 50, 9, 2.00, 280, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1403, 50, 10, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1404, 50, 11, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1405, 50, 12, 2.00, 294, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1406, 50, 13, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1407, 50, 14, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1408, 50, 15, 2.00, 307, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1409, 50, 15, 2.00, 308, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1410, 50, 15, 2.00, 309, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1411, 50, 16, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1412, 50, 17, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1413, 50, 18, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1414, 50, 19, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1415, 50, 20, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1416, 50, 21, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1417, 50, 22, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1418, 50, 23, 3.00, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1419, 50, 24, NULL, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1420, 50, 25, NULL, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1421, 50, 26, NULL, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1422, 50, 27, NULL, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1423, 50, 28, NULL, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1424, 50, 29, NULL, NULL, '', '2025-09-16 07:06:22', '2025-09-16 07:06:22'),
(1425, 51, 1, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1426, 51, 2, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1427, 51, 3, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1428, 51, 4, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1429, 51, 5, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1430, 51, 6, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1431, 51, 7, 2.00, 271, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1432, 51, 8, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1433, 51, 9, 2.00, 280, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1434, 51, 10, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1435, 51, 11, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1436, 51, 12, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1437, 51, 13, 2.00, 300, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1438, 51, 14, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1439, 51, 15, 2.00, 309, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1440, 51, 16, 2.00, 314, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1441, 51, 17, 2.00, 318, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1442, 51, 18, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1443, 51, 19, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1444, 51, 20, 2.00, 338, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1445, 51, 21, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1446, 51, 22, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1447, 51, 23, 3.00, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1448, 51, 24, NULL, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1449, 51, 25, NULL, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1450, 51, 26, NULL, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1451, 51, 27, NULL, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1452, 51, 28, NULL, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1453, 51, 29, NULL, NULL, '', '2025-09-17 02:13:42', '2025-09-17 02:13:42'),
(1454, 52, 1, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1455, 52, 2, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1456, 52, 3, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1457, 52, 4, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1458, 52, 5, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1459, 52, 6, 2.00, 269, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1460, 52, 7, 2.00, 270, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1461, 52, 8, 2.00, 278, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1462, 52, 9, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1463, 52, 10, 2.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1464, 52, 11, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1465, 52, 12, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15');
INSERT INTO `visit_evaluations` (`id`, `visit_id`, `indicator_id`, `score`, `recommendation_id`, `custom_recommendation`, `created_at`, `updated_at`) VALUES
(1466, 52, 13, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1467, 52, 14, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1468, 52, 15, 2.00, 308, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1469, 52, 16, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1470, 52, 17, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1471, 52, 18, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1472, 52, 19, 1.00, 328, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1473, 52, 20, 2.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1474, 52, 21, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1475, 52, 22, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1476, 52, 23, 3.00, NULL, '', '2025-09-17 02:42:08', '2025-09-17 03:25:15'),
(1477, 52, 24, NULL, NULL, '', '2025-09-17 02:42:08', '2025-09-17 02:42:08'),
(1478, 52, 25, NULL, NULL, '', '2025-09-17 02:42:08', '2025-09-17 02:42:08'),
(1479, 52, 26, NULL, NULL, '', '2025-09-17 02:42:08', '2025-09-17 02:42:08'),
(1480, 52, 27, NULL, NULL, '', '2025-09-17 02:42:08', '2025-09-17 02:42:08'),
(1481, 52, 28, NULL, NULL, '', '2025-09-17 02:42:08', '2025-09-17 02:42:08'),
(1482, 52, 29, NULL, NULL, '', '2025-09-17 02:42:08', '2025-09-17 02:42:08'),
(1483, 53, 1, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1484, 53, 2, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1485, 53, 3, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1486, 53, 4, 2.00, NULL, 'نوصي بعرض الاهداف للطلبة على السبورة او في العرض التقديمي ', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1487, 53, 5, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1488, 53, 6, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1489, 53, 7, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1490, 53, 8, 2.00, NULL, 'نوصي بتفعيل المصدر الرئيس ( الكتاب المدرسي )', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1491, 53, 9, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1492, 53, 10, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1493, 53, 11, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1494, 53, 12, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1495, 53, 13, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1496, 53, 14, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1497, 53, 15, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1498, 53, 16, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1499, 53, 17, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1500, 53, 18, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1501, 53, 19, 2.00, NULL, 'نوصي بمتابعة اعمال الطلبة في كتاب الانشطة وتصحيحها وكتابة عبارات تعزيزة لها ', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1502, 53, 20, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1503, 53, 21, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1504, 53, 22, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1505, 53, 23, 3.00, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1506, 53, 24, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1507, 53, 25, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1508, 53, 26, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1509, 53, 27, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1510, 53, 28, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1511, 53, 29, NULL, NULL, '', '2025-09-17 03:21:46', '2025-09-17 03:21:46'),
(1512, 54, 1, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1513, 54, 2, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1514, 54, 3, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1515, 54, 4, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1516, 54, 5, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1517, 54, 6, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1518, 54, 7, 2.00, 273, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1519, 54, 8, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1520, 54, 9, 2.00, 279, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1521, 54, 10, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1522, 54, 11, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1523, 54, 12, 2.00, 299, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1524, 54, 13, 2.00, 300, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1525, 54, 14, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1526, 54, 15, 2.00, 307, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1527, 54, 16, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1528, 54, 17, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1529, 54, 18, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1530, 54, 19, NULL, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1531, 54, 20, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1532, 54, 21, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1533, 54, 22, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1534, 54, 23, 3.00, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1535, 54, 24, NULL, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1536, 54, 25, NULL, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1537, 54, 26, NULL, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1538, 54, 27, NULL, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1539, 54, 28, NULL, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1540, 54, 29, NULL, NULL, '', '2025-09-17 03:54:30', '2025-09-17 03:54:30'),
(1541, 55, 1, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1542, 55, 2, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1543, 55, 3, 2.00, 256, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1544, 55, 4, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1545, 55, 5, NULL, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1546, 55, 6, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1547, 55, 7, 2.00, 273, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1548, 55, 8, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1549, 55, 9, 2.00, NULL, 'نوصي بأستخدام التكنولوجيا ( word wall- quizes ) ', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1550, 55, 10, 2.00, 284, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1551, 55, 10, 2.00, 285, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1552, 55, 11, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1553, 55, 12, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1554, 55, 13, 0.00, NULL, 'نوصي بتفعيل القيمة التربوية', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1555, 55, 14, 0.00, 306, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1556, 55, 15, 2.00, 308, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1557, 55, 16, NULL, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1558, 55, 17, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1559, 55, 18, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1560, 55, 19, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1561, 55, 20, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1562, 55, 21, 2.00, 343, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1563, 55, 22, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1564, 55, 23, 3.00, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1565, 55, 24, NULL, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1566, 55, 25, NULL, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1567, 55, 26, NULL, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1568, 55, 27, NULL, NULL, '', '2025-09-17 06:01:16', '2025-09-17 06:01:16'),
(1569, 55, 28, NULL, NULL, '', '2025-09-17 06:01:17', '2025-09-17 06:01:17'),
(1570, 55, 29, NULL, NULL, '', '2025-09-17 06:01:17', '2025-09-17 06:01:17'),
(1571, 56, 1, 3.00, NULL, 'نوصي بكتابة بيانات المعلم في خطة التحضير ', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1572, 56, 2, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1573, 56, 3, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1574, 56, 4, 2.00, NULL, 'نوصي بقراءة الاهداف ومناقشتها مع الطلبة قبل بداية الدرس', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1575, 56, 5, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1576, 56, 6, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1577, 56, 7, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1578, 56, 8, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1579, 56, 9, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1580, 56, 10, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1581, 56, 11, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1582, 56, 12, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1583, 56, 13, 0.00, NULL, 'نوصي تضمين القيمة التربوية في سياق الهدف ', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1584, 56, 14, 0.00, 306, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1585, 56, 15, 3.00, 308, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1586, 56, 16, NULL, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1587, 56, 17, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1588, 56, 18, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1589, 56, 19, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1590, 56, 20, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1591, 56, 21, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1592, 56, 22, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1593, 56, 23, 3.00, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1594, 56, 24, NULL, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1595, 56, 25, NULL, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1596, 56, 26, NULL, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1597, 56, 27, NULL, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1598, 56, 28, NULL, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1599, 56, 29, NULL, NULL, '', '2025-09-17 07:01:05', '2025-09-17 07:01:05'),
(1600, 57, 1, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1601, 57, 2, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1602, 57, 3, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1603, 57, 4, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1604, 57, 5, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1605, 57, 6, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1606, 57, 7, 2.00, 270, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1607, 57, 8, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1608, 57, 9, 2.00, 279, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1609, 57, 10, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1610, 57, 11, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1611, 57, 12, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1612, 57, 13, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1613, 57, 14, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1614, 57, 15, 2.00, 308, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1615, 57, 16, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1616, 57, 17, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1617, 57, 18, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1618, 57, 19, NULL, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1619, 57, 20, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1620, 57, 21, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1621, 57, 22, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1622, 57, 23, 3.00, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:46:15'),
(1623, 57, 24, NULL, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:29:50'),
(1624, 57, 25, NULL, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:29:50'),
(1625, 57, 26, NULL, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:29:50'),
(1626, 57, 27, NULL, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:29:50'),
(1627, 57, 28, NULL, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:29:50'),
(1628, 57, 29, NULL, NULL, '', '2025-09-17 07:29:50', '2025-09-17 07:29:50'),
(1629, 58, 1, 1.00, NULL, 'العمل على تعديل بيانات الخطة المرفوعة على المنصة لضمان التمكن الذهني من مادة الدرس', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1630, 58, 2, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1631, 58, 3, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1632, 58, 4, 2.00, NULL, 'العمل على مناقشة الأهداف لما له من أثر في تشويق الطلبة إلى موضوع الدرس', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1633, 58, 5, NULL, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1634, 58, 6, 2.00, NULL, 'التسلسل المنطقي لمحتوى الدرس يضمن تحقق الفهم والتفاعل الجيد عند الطلبة', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1635, 58, 7, 2.00, NULL, 'تفعيل التعلم النشط من أهم الأمور التي تمكن الطالب من التمكن من المهارات الكتابية', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1636, 58, 8, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1637, 58, 9, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1638, 58, 10, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1639, 58, 11, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1640, 58, 12, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1641, 58, 13, 2.00, NULL, 'توظيف الموقف التعليمي لتعزيز القيمة المستهدفة', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1642, 58, 14, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1643, 58, 15, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1644, 58, 16, NULL, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1645, 58, 17, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1646, 58, 18, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1647, 58, 19, NULL, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1648, 58, 20, 2.00, NULL, 'العمل على تنظيم جلوس الطلبة في الغرفة الصفية', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1649, 58, 21, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1650, 58, 22, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1651, 58, 23, 3.00, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1652, 58, 24, NULL, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1653, 58, 25, NULL, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1654, 58, 26, NULL, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1655, 58, 27, NULL, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1656, 58, 28, NULL, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1657, 58, 29, NULL, NULL, '', '2025-09-18 03:03:40', '2025-09-18 03:03:40'),
(1658, 59, 1, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1659, 59, 2, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1660, 59, 3, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1661, 59, 4, 3.00, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1662, 59, 5, 3.00, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1663, 59, 6, 3.00, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1664, 59, 7, 2.00, 273, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1665, 59, 8, 3.00, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1666, 59, 9, 2.00, 279, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1667, 59, 10, 3.00, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1668, 59, 11, 3.00, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1669, 59, 12, 2.00, NULL, 'يجب الاشارة الى الكفاية الموجودة بخطة الدرس و ربطها بالحصة ', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1670, 59, 13, 2.00, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1671, 59, 14, 2.00, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1672, 59, 15, 2.00, 307, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1673, 59, 16, 3.00, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1674, 59, 17, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1675, 59, 18, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1676, 59, 19, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1677, 59, 20, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1678, 59, 21, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1679, 59, 22, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1680, 59, 23, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1681, 59, 24, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1682, 59, 25, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1683, 59, 26, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1684, 59, 27, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1685, 59, 28, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1686, 59, 29, NULL, NULL, '', '2025-09-18 04:05:45', '2025-09-18 04:05:45'),
(1687, 60, 1, 3.00, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1688, 60, 2, 2.00, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1689, 60, 3, 2.00, 254, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1690, 60, 3, 2.00, 257, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1691, 60, 4, 2.00, 259, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1692, 60, 4, 2.00, 260, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1693, 60, 5, 2.00, 264, 'الالتزام بالتوقيت الزمني لنشاط التمهيد ', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1694, 60, 6, 3.00, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1695, 60, 7, 2.00, 272, 'يفضل ان تكون الاستراتيجية المستخدمة هي الاكتشاف الموجه طبقا للمتعارف به في جزئية استكشف و برر منطقياً', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1696, 60, 8, 2.00, 277, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1697, 60, 9, 2.00, 281, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1698, 60, 10, 2.00, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1699, 60, 11, 3.00, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1700, 60, 12, 2.00, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1701, 60, 13, 2.00, NULL, 'يجب ان يشار الى القيمة في الحصة او من خلال موقف صفي ', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1702, 60, 14, 2.00, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1703, 60, 15, 2.00, 307, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1704, 60, 15, 2.00, 308, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1705, 60, 15, 2.00, 312, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1706, 60, 16, 1.00, 316, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1707, 60, 17, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1708, 60, 18, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1709, 60, 19, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1710, 60, 20, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1711, 60, 21, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1712, 60, 22, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1713, 60, 23, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1714, 60, 24, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1715, 60, 25, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1716, 60, 26, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1717, 60, 27, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1718, 60, 28, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1719, 60, 29, NULL, NULL, '', '2025-09-18 05:02:19', '2025-09-18 05:02:19'),
(1720, 61, 1, 3.00, 247, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1721, 61, 2, 3.00, 250, 'هعغفقلاثيؤس قثب ص', '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1722, 61, 3, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1723, 61, 4, 1.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1724, 61, 5, 2.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1725, 61, 6, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1726, 61, 7, 3.00, 271, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1727, 61, 8, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1728, 61, 9, 2.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1729, 61, 10, 1.00, NULL, 'هنعغتفقلاثصسءش', '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1730, 61, 11, 1.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1731, 61, 12, 1.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1732, 61, 13, 2.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1733, 61, 14, 2.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1734, 61, 15, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1735, 61, 16, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1736, 61, 17, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1737, 61, 18, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1738, 61, 19, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1739, 61, 20, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1740, 61, 21, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1741, 61, 22, 3.00, NULL, NULL, '2025-09-18 12:20:03', '2025-09-18 12:20:03'),
(1742, 61, 23, 3.00, NULL, 'ىلابيؤس', '2025-09-18 12:20:03', '2025-09-18 12:20:03');

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=225;

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
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=62;

--
-- AUTO_INCREMENT for table `visit_evaluations`
--
ALTER TABLE `visit_evaluations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1743;

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
