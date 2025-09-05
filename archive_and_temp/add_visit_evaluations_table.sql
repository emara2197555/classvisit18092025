SET NAMES utf8mb4;
SET CHARACTER SET utf8mb4;

-- إنشاء جدول تقييمات الزيارات
CREATE TABLE IF NOT EXISTS `visit_evaluations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visit_id` int NOT NULL,
  `indicator_id` int NOT NULL,
  `score` int NOT NULL DEFAULT '0',
  `recommendation_id` int DEFAULT NULL,
  `custom_recommendation` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `visit_id` (`visit_id`),
  KEY `indicator_id` (`indicator_id`),
  KEY `recommendation_id` (`recommendation_id`),
  CONSTRAINT `visit_evaluations_ibfk_1` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visit_evaluations_ibfk_2` FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`) ON DELETE CASCADE,
  CONSTRAINT `visit_evaluations_ibfk_3` FOREIGN KEY (`recommendation_id`) REFERENCES `recommendations` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 