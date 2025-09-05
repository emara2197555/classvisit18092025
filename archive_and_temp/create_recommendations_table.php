<?php
// إنشاء جدول evaluation_recommendations
require_once 'includes/db_connection.php';

echo "<h1>إنشاء جدول evaluation_recommendations</h1>";

try {
    // التحقق من وجود الجدول
    $check_table = query_row("SHOW TABLES LIKE 'evaluation_recommendations'");
    
    if (!$check_table) {
        echo "<p>إنشاء جدول evaluation_recommendations...</p>";
        
        // إنشاء الجدول
        $create_table_sql = "
        CREATE TABLE `evaluation_recommendations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `indicator_id` int(11) NOT NULL,
          `text` text NOT NULL,
          `sort_order` int(11) DEFAULT 0,
          `created_at` timestamp NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `indicator_id` (`indicator_id`),
          KEY `sort_order` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
        ";
        
        execute($create_table_sql);
        echo "<p style='color: green;'>✓ تم إنشاء الجدول بنجاح</p>";
        
        // إضافة بعض التوصيات النموذجية
        echo "<p>إضافة بعض التوصيات النموذجية...</p>";
        
        $sample_recommendations = [
            ['indicator_id' => 1, 'text' => 'الاستمرار في تطبيق الممارسات الجيدة المتبعة', 'sort_order' => 1],
            ['indicator_id' => 1, 'text' => 'تعزيز نقاط القوة الموجودة', 'sort_order' => 2],
            ['indicator_id' => 1, 'text' => 'تطوير استراتيجيات التدريس المستخدمة', 'sort_order' => 3],
            ['indicator_id' => 2, 'text' => 'التركيز على الأهداف التعليمية الواضحة', 'sort_order' => 1],
            ['indicator_id' => 2, 'text' => 'ربط الأهداف بنواتج التعلم المطلوبة', 'sort_order' => 2],
        ];
        
        $insert_sql = "INSERT INTO evaluation_recommendations (indicator_id, text, sort_order) VALUES (?, ?, ?)";
        
        foreach ($sample_recommendations as $rec) {
            try {
                execute($insert_sql, [$rec['indicator_id'], $rec['text'], $rec['sort_order']]);
            } catch (Exception $e) {
                // تجاهل أخطاء المؤشرات غير الموجودة
                echo "<p style='color: orange;'>تحذير: لا يمكن إضافة توصية للمؤشر {$rec['indicator_id']} - قد يكون غير موجود</p>";
            }
        }
        
        echo "<p style='color: green;'>✓ تم إضافة التوصيات النموذجية</p>";
        
    } else {
        echo "<p style='color: blue;'>ℹ الجدول موجود بالفعل</p>";
        
        // عرض عدد التوصيات الموجودة
        $count = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
        echo "<p>عدد التوصيات الموجودة: {$count['count']}</p>";
    }
    
    echo "<p><a href='recommendations_management.php' style='background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>الذهاب لإدارة التوصيات</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}
?>
