-- حذف الجدول إذا كان موجوداً
DROP TABLE IF EXISTS `visit_evaluations`;

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