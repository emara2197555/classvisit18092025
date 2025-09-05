<?php
// تنظيف قاعدة البيانات - حذف الجدول القديم recommendations (اختياري)
require_once 'includes/db_connection.php';

echo "<h2>تنظيف قاعدة البيانات</h2>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
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
    
    if ($old_table_exists && $new_table_exists) {
        // مقارنة عدد السجلات
        $old_count = query_row("SELECT COUNT(*) as count FROM recommendations");
        $new_count = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
        
        echo "<p class='info'>عدد السجلات في الجدول القديم: {$old_count['count']}</p>";
        echo "<p class='info'>عدد السجلات في الجدول الجديد: {$new_count['count']}</p>";
        
        if ($new_count['count'] >= $old_count['count']) {
            echo "<p class='success'>✓ تم نقل البيانات بنجاح إلى الجدول الجديد</p>";
            
            // عرض تحذير قبل الحذف
            echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
            echo "<h3 style='color: #856404;'>⚠️ تحذير</h3>";
            echo "<p>هذا الإجراء سيحذف الجدول القديم 'recommendations' نهائياً.</p>";
            echo "<p>تأكد من أن النظام يعمل بشكل صحيح قبل تنفيذ هذا الإجراء.</p>";
            echo "<p><strong>هل أنت متأكد من أنك تريد حذف الجدول القديم؟</strong></p>";
            echo "</div>";
            
            if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
                // إنشاء نسخة احتياطية أولاً
                echo "<p>إنشاء نسخة احتياطية من الجدول القديم...</p>";
                execute("CREATE TABLE recommendations_backup AS SELECT * FROM recommendations");
                
                // حذف الجدول القديم
                execute("DROP TABLE recommendations");
                
                echo "<p class='success'>✓ تم حذف الجدول القديم بنجاح</p>";
                echo "<p class='info'>تم إنشاء نسخة احتياطية باسم 'recommendations_backup'</p>";
                
            } else {
                echo "<p><a href='?confirm=yes' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;' onclick='return confirm(\"هل أنت متأكد من حذف الجدول القديم؟\")'>نعم، احذف الجدول القديم</a></p>";
                echo "<p><a href='recommendations_management.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>لا، الذهاب لإدارة التوصيات</a></p>";
            }
            
        } else {
            echo "<p class='error'>❌ لم يتم نقل جميع البيانات. لا يمكن حذف الجدول القديم</p>";
        }
        
    } else if (!$old_table_exists) {
        echo "<p class='success'>✓ الجدول القديم غير موجود - التنظيف مكتمل</p>";
    } else {
        echo "<p class='error'>❌ الجدول الجديد غير موجود</p>";
    }
    
    // عرض حالة الجداول الحالية
    echo "<h3>حالة الجداول الحالية:</h3>";
    $tables = query("SELECT table_name FROM information_schema.tables 
                    WHERE table_schema = 'classvisit_db' 
                    AND table_name LIKE '%recommendation%'
                    ORDER BY table_name");
    
    foreach ($tables as $table) {
        $count = query_row("SELECT COUNT(*) as count FROM `{$table['table_name']}`");
        echo "<p class='info'>جدول {$table['table_name']}: {$count['count']} سجل</p>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>خطأ: " . $e->getMessage() . "</p>";
}
?>
