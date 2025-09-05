<?php
// نقل البيانات من جدول recommendations إلى evaluation_recommendations
require_once 'includes/db_connection.php';

echo "<h2>نقل البيانات من جدول recommendations إلى evaluation_recommendations</h2>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.info { color: blue; }
</style>";

try {
    // التحقق من وجود الجدولين
    $old_table_exists = query_row("SELECT table_name FROM information_schema.tables 
                                  WHERE table_schema = 'classvisit_db' 
                                  AND table_name = 'recommendations'");
    
    $new_table_exists = query_row("SELECT table_name FROM information_schema.tables 
                                  WHERE table_schema = 'classvisit_db' 
                                  AND table_name = 'evaluation_recommendations'");
    
    if (!$old_table_exists) {
        echo "<p class='error'>❌ الجدول القديم 'recommendations' غير موجود</p>";
        exit;
    }
    
    if (!$new_table_exists) {
        echo "<p class='error'>❌ الجدول الجديد 'evaluation_recommendations' غير موجود</p>";
        exit;
    }
    
    // عرض إحصائيات الجدولين
    $old_count = query_row("SELECT COUNT(*) as count FROM recommendations");
    $new_count = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
    
    echo "<p class='info'>عدد السجلات في الجدول القديم (recommendations): {$old_count['count']}</p>";
    echo "<p class='info'>عدد السجلات في الجدول الجديد (evaluation_recommendations): {$new_count['count']}</p>";
    
    // نقل البيانات إذا كان الجدول الجديد فارغ أو يحتوي على بيانات أقل
    if ($new_count['count'] < $old_count['count']) {
        echo "<h3>نقل البيانات...</h3>";
        
        // مسح البيانات الموجودة في الجدول الجديد إذا لزم الأمر
        if ($new_count['count'] > 0) {
            echo "<p>مسح البيانات الموجودة في الجدول الجديد...</p>";
            execute("DELETE FROM evaluation_recommendations");
        }
        
        // نقل البيانات
        $recommendations = query("SELECT * FROM recommendations ORDER BY indicator_id, sort_order, id");
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($recommendations as $rec) {
            try {
                $sql = "INSERT INTO evaluation_recommendations (id, indicator_id, text, sort_order, created_at, updated_at) 
                       VALUES (?, ?, ?, ?, ?, ?)";
                
                execute($sql, [
                    $rec['id'],
                    $rec['indicator_id'], 
                    $rec['text'],
                    $rec['sort_order'] ?? 0,
                    $rec['created_at'],
                    $rec['updated_at']
                ]);
                
                $success_count++;
            } catch (Exception $e) {
                $error_count++;
                echo "<p class='error'>خطأ في نقل السجل ID {$rec['id']}: " . $e->getMessage() . "</p>";
            }
        }
        
        echo "<p class='success'>✓ تم نقل {$success_count} سجل بنجاح</p>";
        if ($error_count > 0) {
            echo "<p class='error'>❌ فشل في نقل {$error_count} سجل</p>";
        }
        
        // التحقق من النقل
        $final_count = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
        echo "<p class='info'>عدد السجلات النهائي في evaluation_recommendations: {$final_count['count']}</p>";
        
    } else {
        echo "<p class='success'>✓ الجدول الجديد يحتوي على البيانات الكاملة بالفعل</p>";
    }
    
    // تحديث ملف recommendations_management.php لاستخدام الجدول الصحيح
    echo "<h3>تحديث الكود...</h3>";
    
    // عرض نموذج من البيانات للتأكد
    echo "<h4>نموذج من البيانات المنقولة:</h4>";
    $sample = query("SELECT r.*, i.name as indicator_name 
                    FROM evaluation_recommendations r
                    LEFT JOIN evaluation_indicators i ON r.indicator_id = i.id
                    ORDER BY r.indicator_id, r.sort_order 
                    LIMIT 10");
    
    if (count($sample) > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>المؤشر</th><th>النص</th><th>الترتيب</th></tr>";
        foreach ($sample as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['indicator_name']}</td>";
            echo "<td>" . substr($row['text'], 0, 60) . "...</td>";
            echo "<td>{$row['sort_order']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<p><a href='recommendations_management.php' style='background: #0284c7; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🚀 اختبار نظام إدارة التوصيات</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>خطأ عام: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
