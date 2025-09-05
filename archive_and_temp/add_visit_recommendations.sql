-- إضافة جدول التوصيات للزيارات
CREATE TABLE IF NOT EXISTS `visit_recommendations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `visit_id` int NOT NULL,
  `indicator_id` int NOT NULL,
  `recommendation_id` int DEFAULT NULL,
  `custom_text` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `is_implemented` tinyint(1) NOT NULL DEFAULT '0',
  `implementation_date` date DEFAULT NULL,
  `implementation_notes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_visit_recommendations_visit_id` (`visit_id`),
  KEY `fk_visit_recommendations_indicator_id` (`indicator_id`),
  KEY `fk_visit_recommendations_recommendation_id` (`recommendation_id`),
  CONSTRAINT `fk_visit_recommendations_indicator_id` FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_visit_recommendations_recommendation_id` FOREIGN KEY (`recommendation_id`) REFERENCES `recommendations` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_visit_recommendations_visit_id` FOREIGN KEY (`visit_id`) REFERENCES `visits` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci; 