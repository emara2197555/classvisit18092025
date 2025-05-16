-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: May 15, 2025 at 12:02 PM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

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

-- --------------------------------------------------------

--
-- Table structure for table `academic_years`
--

CREATE TABLE `academic_years` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `first_term_start` date NOT NULL,
  `first_term_end` date NOT NULL,
  `second_term_start` date NOT NULL,
  `second_term_end` date NOT NULL,
  `is_current` tinyint(1) NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `academic_years`
--

INSERT INTO `academic_years` (`id`, `name`, `first_term_start`, `first_term_end`, `second_term_start`, `second_term_end`, `is_current`, `created_at`, `updated_at`) VALUES
(1, '2024/2025', '2024-09-01', '2024-12-31', '2025-01-01', '2025-05-31', 1, '2025-05-14 17:48:51', '2025-05-14 17:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `educational_levels`
--

CREATE TABLE `educational_levels` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
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
-- Table structure for table `evaluation_domains`
--

CREATE TABLE `evaluation_domains` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `weight` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_domains`
--

INSERT INTO `evaluation_domains` (`id`, `name`, `description`, `weight`, `created_at`, `updated_at`) VALUES
(1, 'التخطيط للدرس', 'كذا كذا كذا', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(2, 'تنفيذ الدرس', 'هعوغعةىفبيسرؤءش', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(3, 'التقويم', 'خزعغوةفقلاثرؤصء', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(4, 'الإدارة الصفية وبيئة التعلم', 'على دورة و مجهودة', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(5, 'جزء خاص بمادة العلوم (النشاط العملي)', 'توت توت توت توت توت توت توت توت توت', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `evaluation_indicators`
--

CREATE TABLE `evaluation_indicators` (
  `id` int NOT NULL AUTO_INCREMENT,
  `domain_id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `weight` decimal(5,2) NOT NULL DEFAULT '0.00',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `domain_id` (`domain_id`),
  FOREIGN KEY (`domain_id`) REFERENCES `evaluation_domains` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `evaluation_indicators`
--

INSERT INTO `evaluation_indicators` (`id`, `domain_id`, `name`, `description`, `weight`, `created_at`, `updated_at`) VALUES
(1, 1, 'خطة الدرس متوفرة وبنودها مستكملة ومناسبة.', 'كذا كذا كذا', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(2, 1, 'أهداف التعلم مناسبة ودقيقة الصياغة وقابلة للقياس.', 'أهداف التعلم معروضة ويتم مناقشتها .', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(3, 1, 'أنشطة الدرس الرئيسة واضحة ومتدرجة ومرتبطة بالأهداف.', 'أنشطة التمهيد مفعلة بشكل مناسب.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(4, 2, 'محتوى الدرس واضح والعرض منظّم ومترابط.', 'طرائق التدريس وإستراتيجياته متنوعه وتتمحور حول الطالب.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(5, 2, 'مصادر التعلم الرئيسة والمساندة موظّفة بصورة واضحة وسليمة.', 'الوسائل التعليميّة والتكنولوجيا موظّفة بصورة مناسبة.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(6, 2, 'الأسئلة الصفية ذات صياغة سليمة ومتدرجة ومثيرة للتفكير .', 'المادة العلمية دقيقة و مناسبة.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(7, 2, 'الكفايات الأساسية متضمنة في السياق المعرفي للدرس.', 'القيم الأساسية متضمنة في السياق المعرفي للدرس.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(8, 2, 'التكامل بين محاور المادة ومع المواد الأخرى يتم بشكل مناسب.', 'الفروق الفردية بين الطلبة يتم مراعاتها.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(9, 2, 'غلق الدرس يتم بشكل مناسب.', 'أساليب التقويم ( القبلي والبنائي والختامي ) مناسبة ومتنوعة.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(10, 3, 'التغذية الراجعة متنوعة ومستمرة', 'أعمال الطلبة متابعة ومصححة بدقة ورقيًا وإلكترونيًا .', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(11, 4, 'البيئة الصفية إيجابية وآمنة وداعمة للتعلّم.', 'إدارة أنشطة التعلّم والمشاركات الصّفيّة تتم بصورة منظمة.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(12, 4, 'قوانين إدارة الصف وإدارة السلوك مفعّلة.', 'الاستثمار الأمثل لزمن الحصة', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(13, 5, 'مدى صلاحية وتوافر الأدوات اللازمة لتنفيذ النشاط العملي.', 'شرح إجراءات الأمن والسلامة المناسبة للتجربة ومتابعة تفعيلها.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(14, 5, 'إعطاء تعليمات واضحة وسليمة لأداء النشاط العملي قبل وأثناء التنفيذ.', 'تسجيل الطلبة للملاحظات والنتائج أثناء تنفيذ النشاط العملي.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(15, 5, 'تقويم مهارات الطلبة أثناء تنفيذ النشاط العملي.', 'تنويع أساليب تقديم التغذية الراجعة للطلبة لتنمية مهاراتهم.', '100.00', '2025-05-14 17:48:51', '2025-05-14 17:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `grades`
--

CREATE TABLE `grades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `level_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `level_id` (`level_id`),
  FOREIGN KEY (`level_id`) REFERENCES `educational_levels` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `grades`
--

INSERT INTO `grades` (`id`, `name`, `level_id`, `created_at`, `updated_at`) VALUES
(1, 'الصف الأول', 1, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(2, 'الصف الثاني', 1, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(3, 'الصف الثالث', 1, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(4, 'الصف الرابع', 1, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(5, 'الصف الخامس', 1, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(6, 'الصف السادس', 1, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(7, 'الصف السابع', 2, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(8, 'الصف الثامن', 2, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(9, 'الصف التاسع', 2, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(10, 'الصف العاشر', 3, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(11, 'الصف الحادي عشر', 3, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(12, 'الصف الثاني عشر', 3, '2025-05-14 17:48:51', '2025-05-14 17:48:51');

-- --------------------------------------------------------

--
-- Table structure for table `recommendations`
--

CREATE TABLE `recommendations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `indicator_id` int NOT NULL,
  `text` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `indicator_id` (`indicator_id`),
  FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `recommendations`
--

INSERT INTO `recommendations` (`id`, `indicator_id`, `text`, `created_at`, `updated_at`) VALUES
(1, 1, 'التخطيط اليومي للدروس وتحديد الأهداف بدقة', '2025-05-15 06:35:24', '2025-05-15 06:35:24'),
(2, 1, 'مراجعة خطة الدرس بانتظام والتأكد من اكتمالها', '2025-05-15 06:35:24', '2025-05-15 06:35:24'),
(3, 2, 'صياغة أهداف تعلم ذكية قابلة للقياس', '2025-05-15 06:35:24', '2025-05-15 06:35:24'),
(4, 2, 'ربط أهداف التعلم بأهداف المنهج والمعايير', '2025-05-15 06:35:24', '2025-05-15 06:35:24'),
(5, 3, 'استخدام أنشطة تمهيدية متنوعة وجاذبة للانتباه', '2025-05-15 06:35:24', '2025-05-15 06:35:24'),
(6, 4, 'عرض أهداف التعلم بشكل واضح وبلغة مناسبة للطلاب', '2025-05-15 06:35:24', '2025-05-15 06:35:24'),
(7, 1, 'يجب توفّر الخطّة على نظام قطر للتعليم.', '2025-05-15 11:09:02', '2025-05-15 11:09:02'),
(8, 1, 'يجب اتساق الخطّة زمنيا مع الخطة الفصلية.', '2025-05-15 11:09:02', '2025-05-15 11:09:02'),
(9, 1, 'يجب أن تكون الخطّة مكتوبة بلغة سليمة وتتسم بالدقة والوضوح.', '2025-05-15 11:09:02', '2025-05-15 11:09:02'),
(10, 1, 'يجب أن تتوافر التهيئة في الخطّة وأن تكون مرتبطة بموضوع الدرس وأهدافه.', '2025-05-15 11:09:02', '2025-05-15 11:09:02');

-- --------------------------------------------------------

--
-- Table structure for table `schools`
--

CREATE TABLE IF NOT EXISTS schools (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    school_code VARCHAR(50) UNIQUE,
    email VARCHAR(255),
    phone VARCHAR(50),
    address TEXT,
    logo VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `schools`
--

INSERT INTO `schools` (`id`, `name`, `school_code`, `email`, `phone`, `address`, `logo`, `created_at`, `updated_at`) VALUES
(1, 'مدرسة عبد الله بن علي المسند الثانوية للبنين', '30244', 'emara21975@gmail.com', '30463336', 'zone 74 - street 911 - villa 84', 'uploads/logos/school_logo_1747244953.png', '2025-05-14 17:49:13', '2025-05-14 18:09:11');

-- --------------------------------------------------------

--
-- Table structure for table `sections`
--

CREATE TABLE `sections` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `grade_id` int NOT NULL,
  `school_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `sections`
--

INSERT INTO `sections` (`id`, `name`, `grade_id`, `school_id`, `created_at`, `updated_at`) VALUES
(1, '1', 10, 1, '2025-05-14 17:55:36', '2025-05-14 17:55:36'),
(2, '2', 10, 1, '2025-05-14 17:55:41', '2025-05-14 17:55:41'),
(3, '3', 10, 1, '2025-05-14 17:55:45', '2025-05-14 17:55:45'),
(4, '4', 10, 1, '2025-05-14 17:55:51', '2025-05-14 17:55:51'),
(5, '5', 10, 1, '2025-05-14 17:55:56', '2025-05-14 17:55:56'),
(6, '5', 10, 1, '2025-05-14 17:56:02', '2025-05-14 17:56:02'),
(7, '1', 11, 1, '2025-05-14 17:56:07', '2025-05-14 17:56:07'),
(8, '2', 11, 1, '2025-05-14 17:56:14', '2025-05-14 17:56:14'),
(9, '3', 11, 1, '2025-05-14 17:56:19', '2025-05-14 17:56:19'),
(10, '4', 11, 1, '2025-05-14 17:56:23', '2025-05-14 17:56:23'),
(11, '5', 11, 1, '2025-05-14 17:56:32', '2025-05-14 17:56:32'),
(12, '6', 11, 1, '2025-05-14 17:56:50', '2025-05-14 17:56:50'),
(13, '1', 12, 1, '2025-05-14 17:56:57', '2025-05-14 17:56:57'),
(14, '2', 12, 1, '2025-05-14 17:57:00', '2025-05-14 17:57:00'),
(15, '3', 12, 1, '2025-05-14 17:57:04', '2025-05-14 17:57:04'),
(16, '4', 12, 1, '2025-05-14 17:57:09', '2025-05-14 17:57:09'),
(17, '5', 12, 1, '2025-05-14 17:57:14', '2025-05-14 17:57:14'),
(18, '6', 12, 1, '2025-05-14 17:57:18', '2025-05-14 17:57:18'),
(19, '7', 12, 1, '2025-05-14 17:57:24', '2025-05-14 17:57:24'),
(20, '7', 10, 1, '2025-05-14 17:57:29', '2025-05-14 17:57:29');

-- --------------------------------------------------------

--
-- Table structure for table `subjects`
--

CREATE TABLE `subjects` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `school_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `subjects`
--

INSERT INTO `subjects` (`id`, `name`, `school_id`, `created_at`, `updated_at`) VALUES
(1, 'العلوم', NULL, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(2, 'الرياضيات', NULL, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(3, 'اللغة العربية', NULL, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(4, 'اللغة الإنجليزية', NULL, '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(5, 'الفيزياء', NULL, '2025-05-14 17:50:32', '2025-05-14 17:50:32'),
(6, 'الكيمياء', NULL, '2025-05-14 17:50:42', '2025-05-14 17:50:42'),
(7, 'الأحياء', NULL, '2025-05-14 17:50:49', '2025-05-14 17:50:49'),
(8, 'الحوسبة وتكنولوجيا المعلومات', NULL, '2025-05-14 17:50:57', '2025-05-14 17:50:57'),
(9, 'التربية الإسلامية', NULL, '2025-05-14 17:51:03', '2025-05-14 17:51:03'),
(10, 'الدراسات الاجتماعية', NULL, '2025-05-14 17:51:08', '2025-05-14 17:51:08'),
(11, 'التربية البدنية', NULL, '2025-05-14 17:51:14', '2025-05-14 17:51:14'),
(12, 'الفنون البصرية', NULL, '2025-05-14 17:51:21', '2025-05-14 17:51:21'),
(13, 'التاريخ', NULL, '2025-05-14 17:51:29', '2025-05-14 17:51:29'),
(14, 'الجغرافيا', NULL, '2025-05-14 17:51:35', '2025-05-14 17:51:35'),
(15, 'المهارات الحياتية', NULL, '2025-05-14 17:52:10', '2025-05-14 17:52:10'),
(16, 'إدارة الأعمال', NULL, '2025-05-14 17:52:17', '2025-05-14 17:52:17'),
(17, 'تكنولوجيا المعلومات', NULL, '2025-05-14 17:52:27', '2025-05-14 17:52:27');

-- --------------------------------------------------------

--
-- Table structure for table `teachers`
--

CREATE TABLE `teachers` (
  `id` int NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `personal_id` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `job_title` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `school_id` int DEFAULT NULL,
  `phone` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `teachers`
--

INSERT INTO `teachers` (`id`, `name`, `personal_id`, `email`, `job_title`, `school_id`, `phone`, `created_at`, `updated_at`) VALUES
(1, 'walied emara', '27581807586', 'emara21975@gmail.com', 'منسق المادة', 1, '30463336', '2025-05-14 17:55:18', '2025-05-14 17:59:07'),
(2, 'رياض محرز', '87654321', 'ryad@education.qa', 'معلم', 1, '8765432', '2025-05-14 18:02:46', '2025-05-14 18:02:46'),
(3, 'ياسر محمد', '98765432', 'rfghyuj@dfghjk.com', 'منسق المادة', 1, '98765432', '2025-05-14 18:03:24', '2025-05-14 18:03:24'),
(4, 'هشام شيكابالا', '987654321', 'drtcfvgybhu@redtfgyuh.com', 'موجه المادة', 1, '', '2025-05-14 18:04:10', '2025-05-14 18:04:10'),
(5, 'سيف النعيمي', '876543253432', 'saif@education.qa', 'النائب الأكاديمي', 1, '23456789', '2025-05-14 18:05:53', '2025-05-14 18:05:53'),
(6, 'جاسم جمعة المريخي', '098765432', 'jasem@education.qa', 'مدير', 1, '7654326543', '2025-05-14 18:06:33', '2025-05-14 18:06:33'),
(7, 'مفيد عمارة', '987654324567', 'w.emara2109@education.qa', 'معلم', 1, '30463336', '2025-05-15 11:25:30', '2025-05-15 11:25:30'),
(8, 'حسن سامي', '987654324567865', 'w.emara2109@education.qa', 'معلم', 1, '30463336', '2025-05-15 11:26:01', '2025-05-15 11:26:01'),
(10, 'صلاح فكري', '34253436534', 'emara21975@gmail.com', 'معلم', 1, '66924429', '2025-05-15 11:27:16', '2025-05-15 11:27:16'),
(11, 'ايهاب زايد', '8765434567', 'hgfbd@rtdftyguh.com', 'منسق المادة', 1, '7654324', '2025-05-15 11:59:43', '2025-05-15 11:59:43');

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
(1, 1, 2, '2025-05-14 17:59:07', '2025-05-14 17:59:07'),
(2, 2, 8, '2025-05-14 18:02:46', '2025-05-14 18:02:46'),
(3, 3, 8, '2025-05-14 18:03:24', '2025-05-14 18:03:24'),
(4, 4, 8, '2025-05-14 18:04:10', '2025-05-14 18:04:10'),
(5, 7, 7, '2025-05-15 11:25:30', '2025-05-15 11:25:30'),
(6, 8, 3, '2025-05-15 11:26:01', '2025-05-15 11:26:01'),
(8, 10, 2, '2025-05-15 11:27:32', '2025-05-15 11:27:32'),
(9, 11, 1, '2025-05-15 11:59:43', '2025-05-15 11:59:43');

-- --------------------------------------------------------

--
-- Table structure for table `visitor_types`
--

CREATE TABLE `visitor_types` (
  `id` int NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visitor_types`
--

INSERT INTO `visitor_types` (`id`, `name`, `created_at`, `updated_at`) VALUES
(1, 'المدير', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(2, 'النائب الأكاديمي', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(3, 'منسق المادة', '2025-05-14 17:48:51', '2025-05-14 17:48:51'),
(4, 'موجه المادة', '2025-05-14 17:48:51', '2025-05-14 17:48:51');

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
  `academic_year_id` int NOT NULL,
  `visit_date` date NOT NULL,
  `visit_type` enum('full','partial') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'full',
  `attendance_type` enum('physical','remote','hybrid') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'physical',
  `has_lab` tinyint(1) NOT NULL DEFAULT '0',
  `general_notes` text COLLATE utf8mb4_unicode_ci,
  `recommendation_notes` text COLLATE utf8mb4_unicode_ci,
  `appreciation_notes` text COLLATE utf8mb4_unicode_ci,
  `total_score` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE,
  FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
  FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
  FOREIGN KEY (visitor_type_id) REFERENCES visitor_types(id) ON DELETE CASCADE,
  FOREIGN KEY (grade_id) REFERENCES grades(id) ON DELETE CASCADE,
  FOREIGN KEY (section_id) REFERENCES sections(id) ON DELETE CASCADE,
  FOREIGN KEY (level_id) REFERENCES educational_levels(id) ON DELETE CASCADE,
  FOREIGN KEY (visitor_person_id) REFERENCES teachers(id) ON DELETE SET NULL,
  FOREIGN KEY (academic_year_id) REFERENCES academic_years(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visits`
--

INSERT INTO `visits` (`id`, `school_id`, `teacher_id`, `subject_id`, `grade_id`, `section_id`, `level_id`, `visitor_type_id`, `visitor_person_id`, `academic_year_id`, `visit_date`, `visit_type`, `attendance_type`, `has_lab`, `general_notes`, `recommendation_notes`, `appreciation_notes`, `total_score`, `created_at`, `updated_at`) VALUES
(1, 1, 2, 8, 11, 7, 3, 4, 4, 1, '2025-05-14', 'full', 'physical', 0, '', 'كذا كذا كذا', 'على دورة و مجهودة', 116.00, '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(2, 1, 2, 8, 11, 8, 3, 3, 3, 1, '2025-05-14', 'full', 'physical', 0, 'عهغوعفقلاثرؤصء', 'هعوغعةىفبيسرؤءش', 'خزعغوةفقلاثرؤصء', 116.00, '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(3, 1, 2, 8, 10, 1, 3, 2, 5, 1, '2025-05-15', 'full', 'physical', 0, '', 'البطيخ', 'المور', 95.00, '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(4, 1, 2, 8, 11, 9, 3, 2, 5, 1, '2025-05-15', 'full', 'physical', 0, '', '', '', 64.00, '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(5, 1, 10, 2, 12, 18, 3, 2, 5, 1, '2025-05-15', 'full', 'physical', 0, '', '', '', 12.00, '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(6, 1, 10, 2, 11, 9, 3, 2, 5, 1, '2025-05-15', 'full', 'physical', 0, '', 'يجب توفّر الخطّة على نظام قطر للتعليم.\r\n\r\nمراجعة خطة الدرس بانتظام والتأكد من اكتمالها\r\n\r\nيجب اتساق الخطّة زمنيا مع الخطة الفصلية.\r\n\r\nيجب إن تعزّز الأنشطة الرئيسة الكفايات والقيم الأساسية ضمن السياق المعرفي.\r\n\r\nيجب توضيح آلية توظيف أدوات التكنولوجيا في دور المعلم و المتعلم.\r\n\r\nتوت توت توت توت توت توت توت توت توت توت', '', 9.00, '2025-05-15 11:49:59', '2025-05-15 11:49:59');

-- --------------------------------------------------------

--
-- Table structure for table `visit_evaluations`
--

CREATE TABLE `visit_evaluations` (
  `id` int NOT NULL,
  `visit_id` int NOT NULL,
  `indicator_id` int NOT NULL,
  `score` tinyint NOT NULL,
  `recommendation_id` int DEFAULT NULL,
  `custom_recommendation` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `visit_evaluations`
--

INSERT INTO `visit_evaluations` (`id`, `visit_id`, `indicator_id`, `score`, `recommendation_id`, `custom_recommendation`, `created_at`, `updated_at`) VALUES
(1, 1, 1, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(2, 1, 2, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(3, 1, 3, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(4, 1, 4, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(5, 1, 5, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(6, 1, 6, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(7, 1, 7, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(8, 1, 8, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(9, 1, 9, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(10, 1, 10, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(11, 1, 11, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(12, 1, 12, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(13, 1, 13, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(14, 1, 14, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(15, 1, 15, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(16, 1, 16, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(17, 1, 17, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(18, 1, 18, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(19, 1, 19, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(20, 1, 20, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(21, 1, 21, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(22, 1, 22, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(23, 1, 23, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(24, 1, 24, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(25, 1, 25, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(26, 1, 26, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(27, 1, 27, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(28, 1, 28, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(29, 1, 29, 4, NULL, '', '2025-05-14 18:44:25', '2025-05-14 18:44:25'),
(30, 2, 1, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(31, 2, 2, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(32, 2, 3, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(33, 2, 4, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(34, 2, 5, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(35, 2, 6, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(36, 2, 7, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(37, 2, 8, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(38, 2, 9, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(39, 2, 10, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(40, 2, 11, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(41, 2, 12, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(42, 2, 13, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(43, 2, 14, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(44, 2, 15, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(45, 2, 16, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(46, 2, 17, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(47, 2, 18, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(48, 2, 19, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(49, 2, 20, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(50, 2, 21, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(51, 2, 22, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(52, 2, 23, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(53, 2, 24, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(54, 2, 25, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(55, 2, 26, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(56, 2, 27, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(57, 2, 28, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(58, 2, 29, 4, NULL, '', '2025-05-14 18:46:13', '2025-05-14 18:46:13'),
(59, 3, 1, 3, 1, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(60, 3, 1, 3, 9, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(61, 3, 1, 3, 12, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(62, 3, 2, 2, 4, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(63, 3, 2, 2, 17, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(64, 3, 3, 1, 23, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(65, 3, 3, 1, 24, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(66, 3, 4, 0, NULL, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(67, 3, 5, 0, NULL, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(68, 3, 6, 4, NULL, '', '2025-05-15 11:14:27', '2025-05-15 11:14:27'),
(69, 3, 7, 3, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(70, 3, 8, 3, 46, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(71, 3, 9, 3, 50, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(72, 3, 10, 3, 53, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(73, 3, 11, 1, 57, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(74, 3, 12, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(75, 3, 13, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(76, 3, 14, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(77, 3, 15, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(78, 3, 16, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(79, 3, 17, 4, NULL, 'بلح', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(80, 3, 18, 4, NULL, 'بلح بلح', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(81, 3, 19, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(82, 3, 20, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(83, 3, 21, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(84, 3, 22, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(85, 3, 23, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(86, 3, 24, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(87, 3, 25, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(88, 3, 26, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(89, 3, 27, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(90, 3, 28, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(91, 3, 29, 4, NULL, '', '2025-05-15 11:14:28', '2025-05-15 11:14:28'),
(92, 4, 1, 2, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(93, 4, 2, 2, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(94, 4, 3, 1, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(95, 4, 4, 3, 6, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(96, 4, 4, 3, 27, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(97, 4, 4, 3, 28, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(98, 4, 5, 1, 32, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(99, 4, 5, 1, 33, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(100, 4, 6, 1, 35, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(101, 4, 6, 1, 36, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(102, 4, 7, 2, 39, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(103, 4, 8, 3, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(104, 4, 9, 1, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(105, 4, 10, 0, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(106, 4, 11, 0, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(107, 4, 12, 0, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(108, 4, 13, 0, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(109, 4, 14, 0, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(110, 4, 15, 0, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(111, 4, 16, 0, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(112, 4, 17, 0, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(113, 4, 18, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(114, 4, 19, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(115, 4, 20, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(116, 4, 21, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(117, 4, 22, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(118, 4, 23, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(119, 4, 24, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(120, 4, 25, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(121, 4, 26, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(122, 4, 27, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(123, 4, 28, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(124, 4, 29, 4, NULL, '', '2025-05-15 11:19:52', '2025-05-15 11:19:52'),
(125, 5, 1, 4, 9, 'البلح البلح البلح', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(126, 5, 1, 4, 11, 'البلح البلح البلح', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(127, 5, 2, 4, 4, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(128, 5, 2, 4, 17, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(129, 5, 3, 4, 22, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(130, 5, 3, 4, 23, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(131, 5, 4, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(132, 5, 5, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(133, 5, 6, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(134, 5, 7, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(135, 5, 8, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(136, 5, 9, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(137, 5, 10, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(138, 5, 11, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(139, 5, 12, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(140, 5, 13, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(141, 5, 14, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(142, 5, 15, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(143, 5, 16, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(144, 5, 17, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(145, 5, 18, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(146, 5, 19, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(147, 5, 20, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(148, 5, 21, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(149, 5, 22, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(150, 5, 23, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(151, 5, 24, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(152, 5, 25, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(153, 5, 26, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(154, 5, 27, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(155, 5, 28, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(156, 5, 29, 0, NULL, '', '2025-05-15 11:31:26', '2025-05-15 11:31:26'),
(157, 6, 1, 4, 7, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(158, 6, 1, 4, 2, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(159, 6, 1, 4, 8, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(160, 6, 2, 4, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(161, 6, 3, 1, 23, 'توت توت توت توت توت توت توت توت توت توت ', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(162, 6, 3, 1, 26, 'توت توت توت توت توت توت توت توت توت توت ', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(163, 6, 4, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(164, 6, 5, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(165, 6, 6, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(166, 6, 7, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(167, 6, 8, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(168, 6, 9, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(169, 6, 10, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(170, 6, 11, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(171, 6, 12, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(172, 6, 13, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(173, 6, 14, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(174, 6, 15, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(175, 6, 16, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(176, 6, 17, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(177, 6, 18, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(178, 6, 19, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(179, 6, 20, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(180, 6, 21, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(181, 6, 22, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(182, 6, 23, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(183, 6, 24, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(184, 6, 25, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(185, 6, 26, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(186, 6, 27, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(187, 6, 28, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59'),
(188, 6, 29, 0, NULL, '', '2025-05-15 11:49:59', '2025-05-15 11:49:59');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `educational_levels`
--
ALTER TABLE `educational_levels`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `evaluation_domains`
--
ALTER TABLE `evaluation_domains`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `evaluation_indicators`
--
ALTER TABLE `evaluation_indicators`
  ADD PRIMARY KEY (`id`),
  ADD KEY `domain_id` (`domain_id`);

--
-- Indexes for table `grades`
--
ALTER TABLE `grades`
  ADD PRIMARY KEY (`id`),
  ADD KEY `level_id` (`level_id`);

--
-- Indexes for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `indicator_id` (`indicator_id`);

--
-- Indexes for table `schools`
--
ALTER TABLE `schools`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `school_code` (`school_code`);

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
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `teachers`
--
ALTER TABLE `teachers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `personal_id` (`personal_id`),
  ADD KEY `school_id` (`school_id`);

--
-- Indexes for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_teacher_subject` (`teacher_id`,`subject_id`),
  ADD KEY `subject_id` (`subject_id`);

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
  ADD KEY `level_id` (`level_id`);

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
-- AUTO_INCREMENT for table `educational_levels`
--
ALTER TABLE `educational_levels`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `evaluation_domains`
--
ALTER TABLE `evaluation_domains`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `evaluation_indicators`
--
ALTER TABLE `evaluation_indicators`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT for table `grades`
--
ALTER TABLE `grades`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `recommendations`
--
ALTER TABLE `recommendations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=123;

--
-- AUTO_INCREMENT for table `schools`
--
ALTER TABLE `schools`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `sections`
--
ALTER TABLE `sections`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `subjects`
--
ALTER TABLE `subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `teachers`
--
ALTER TABLE `teachers`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `visitor_types`
--
ALTER TABLE `visitor_types`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `visits`
--
ALTER TABLE `visits`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `visit_evaluations`
--
ALTER TABLE `visit_evaluations`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `evaluation_indicators`
--
ALTER TABLE `evaluation_indicators`
  ADD CONSTRAINT `evaluation_indicators_ibfk_1` FOREIGN KEY (`domain_id`) REFERENCES `evaluation_domains` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `grades`
--
ALTER TABLE `grades`
  ADD CONSTRAINT `grades_ibfk_1` FOREIGN KEY (`level_id`) REFERENCES `educational_levels` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `recommendations`
--
ALTER TABLE `recommendations`
  ADD CONSTRAINT `recommendations_ibfk_1` FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `sections`
--
ALTER TABLE `sections`
  ADD CONSTRAINT `sections_ibfk_1` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sections_ibfk_2` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `subjects`
--
ALTER TABLE `subjects`
  ADD CONSTRAINT `subjects_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `teachers`
--
ALTER TABLE `teachers`
  ADD CONSTRAINT `teachers_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `teacher_subjects`
--
ALTER TABLE `teacher_subjects`
  ADD CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `visits`
--
ALTER TABLE `visits`
  ADD CONSTRAINT `visits_ibfk_1` FOREIGN KEY (`school_id`) REFERENCES `schools` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_2` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_3` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_4` FOREIGN KEY (`visitor_type_id`) REFERENCES `visitor_types` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_5` FOREIGN KEY (`grade_id`) REFERENCES `grades` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_6` FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `visits_ibfk_7` FOREIGN KEY (`level_id`) REFERENCES `educational_levels` (`id`) ON DELETE CASCADE;

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
