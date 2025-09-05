<?php
// التحقق من وجود جدول evaluation_recommendations وإنشاؤه إذا لم يكن موجوداً
require_once '../includes/db_connection.php';

try {
    // التحقق من وجود الجدول
    $table_exists = query_row("SELECT table_name FROM information_schema.tables 
                              WHERE table_schema = 'classvisit_db' 
                              AND table_name = 'evaluation_recommendations'");
    
    if (!$table_exists) {
        echo "إنشاء جدول evaluation_recommendations...<br>";
        
        $sql = "CREATE TABLE `evaluation_recommendations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `indicator_id` int(11) NOT NULL,
          `text` text NOT NULL,
          `sort_order` int(11) DEFAULT 0,
          `created_at` timestamp NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `indicator_id` (`indicator_id`),
          KEY `sort_order` (`sort_order`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        execute($sql);
        echo "✓ تم إنشاء الجدول بنجاح<br>";
        
        // إضافة بعض التوصيات الأساسية
        $recommendations = [
            [1, 'تطوير استراتيجيات التدريس المتنوعة', 1],
            [1, 'الاستفادة من نقاط القوة الحالية', 2],
            [1, 'تطبيق ممارسات تعليمية أفضل', 3]
        ];
        
        foreach ($recommendations as $rec) {
            execute("INSERT INTO evaluation_recommendations (indicator_id, text, sort_order) VALUES (?, ?, ?)", $rec);
        }
        
        echo "✓ تم إضافة التوصيات الأساسية<br>";
    } else {
        echo "✓ الجدول موجود بالفعل<br>";
    }
    
    // عرض عدد التوصيات
    $count = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
    echo "عدد التوصيات الحالية: " . $count['count'] . "<br>";
    
    echo "<br><a href='../recommendations_management.php'>الذهاب لإدارة التوصيات</a>";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
