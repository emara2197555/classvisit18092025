<?php
// إصلاح جدول evaluation_recommendations
require_once 'includes/db_connection.php';

echo "<h2>إصلاح جدول evaluation_recommendations</h2>";

try {
    // التحقق من وجود الجدول
    $table_exists = query_row("SELECT table_name FROM information_schema.tables 
                              WHERE table_schema = 'classvisit_db' 
                              AND table_name = 'evaluation_recommendations'");
    
    if (!$table_exists) {
        echo "<p>إنشاء جدول evaluation_recommendations...</p>";
        
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
        echo "<p style='color: green;'>✓ تم إنشاء الجدول بنجاح</p>";
        
    } else {
        echo "<p>الجدول موجود. التحقق من الأعمدة...</p>";
        
        // التحقق من وجود عمود sort_order
        $sort_order_exists = query_row("SELECT COLUMN_NAME 
                                       FROM INFORMATION_SCHEMA.COLUMNS 
                                       WHERE TABLE_SCHEMA = 'classvisit_db' 
                                       AND TABLE_NAME = 'evaluation_recommendations' 
                                       AND COLUMN_NAME = 'sort_order'");
        
        if (!$sort_order_exists) {
            echo "<p>إضافة عمود sort_order...</p>";
            execute("ALTER TABLE evaluation_recommendations ADD COLUMN sort_order int(11) DEFAULT 0");
            execute("ALTER TABLE evaluation_recommendations ADD INDEX sort_order (sort_order)");
            echo "<p style='color: green;'>✓ تم إضافة عمود sort_order</p>";
        } else {
            echo "<p style='color: blue;'>✓ عمود sort_order موجود</p>";
        }
        
        // التحقق من وجود أعمدة التاريخ
        $created_at_exists = query_row("SELECT COLUMN_NAME 
                                       FROM INFORMATION_SCHEMA.COLUMNS 
                                       WHERE TABLE_SCHEMA = 'classvisit_db' 
                                       AND TABLE_NAME = 'evaluation_recommendations' 
                                       AND COLUMN_NAME = 'created_at'");
        
        if (!$created_at_exists) {
            echo "<p>إضافة أعمدة التاريخ...</p>";
            execute("ALTER TABLE evaluation_recommendations 
                    ADD COLUMN created_at timestamp NULL DEFAULT current_timestamp(),
                    ADD COLUMN updated_at timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()");
            echo "<p style='color: green;'>✓ تم إضافة أعمدة التاريخ</p>";
        } else {
            echo "<p style='color: blue;'>✓ أعمدة التاريخ موجودة</p>";
        }
    }
    
    // عرض بنية الجدول
    echo "<h3>بنية الجدول الحالية:</h3>";
    $columns = query("DESCRIBE evaluation_recommendations");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>اسم العمود</th><th>النوع</th><th>NULL</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "<td>{$col['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // إضافة بعض التوصيات النموذجية إذا كان الجدول فارغاً
    $count = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
    if ($count['count'] == 0) {
        echo "<p>إضافة بعض التوصيات النموذجية...</p>";
        
        $recommendations = [
            [1, 'تطوير استراتيجيات التدريس المتنوعة', 1],
            [1, 'الاستفادة من نقاط القوة الحالية', 2],
            [1, 'تطبيق ممارسات تعليمية أفضل', 3],
            [2, 'التركيز على وضوح الأهداف التعليمية', 1],
            [2, 'ربط الأهداف بنواتج التعلم', 2]
        ];
        
        foreach ($recommendations as $rec) {
            try {
                execute("INSERT INTO evaluation_recommendations (indicator_id, text, sort_order) VALUES (?, ?, ?)", $rec);
            } catch (Exception $e) {
                echo "<p style='color: orange;'>تحذير: خطأ في إضافة توصية للمؤشر {$rec[0]}</p>";
            }
        }
        
        echo "<p style='color: green;'>✓ تم إضافة التوصيات النموذجية</p>";
    }
    
    // عرض عدد التوصيات
    $final_count = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
    echo "<p><strong>عدد التوصيات الحالية: {$final_count['count']}</strong></p>";
    
    echo "<p><a href='recommendations_management.php' style='background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>الذهاب لإدارة التوصيات</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
    echo "<p>تفاصيل إضافية: " . $e->getTraceAsString() . "</p>";
}
?>
