-- إنشاء جدول evaluation_recommendations إذا لم يكن موجوداً
CREATE TABLE IF NOT EXISTS `evaluation_recommendations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `indicator_id` int(11) NOT NULL,
  `text` text NOT NULL,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `indicator_id` (`indicator_id`),
  KEY `sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- إضافة مفتاح أجنبي للربط مع جدول evaluation_indicators
ALTER TABLE `evaluation_recommendations` 
ADD CONSTRAINT `fk_evaluation_recommendations_indicator` 
FOREIGN KEY (`indicator_id`) REFERENCES `evaluation_indicators` (`id`) 
ON DELETE CASCADE ON UPDATE CASCADE;
