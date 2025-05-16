<?php
// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

try {
    // إنشاء جدول تقييمات الزيارات
    $sql = "CREATE TABLE IF NOT EXISTS `visit_evaluations` (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    execute($sql);
    
    echo "<div style='direction: rtl; font-family: Arial; padding: 20px;'>";
    echo "<h2>تم إنشاء جدول تقييمات الزيارات بنجاح!</h2>";
    echo "<p>الآن يمكنك استخدام نموذج تقييم الزيارة الصفية بشكل كامل.</p>";
    echo "<p><a href='evaluation_form.php' style='background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>العودة إلى نموذج التقييم</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='direction: rtl; font-family: Arial; padding: 20px;'>";
    echo "<h2>حدث خطأ أثناء إنشاء جدول تقييمات الزيارات</h2>";
    echo "<p>تفاصيل الخطأ: " . $e->getMessage() . "</p>";
    echo "</div>";
} 