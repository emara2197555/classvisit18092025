<?php
// تنظيف نهائي - حذف الجدول الإضافي evaluation_recommendations
require_once 'includes/db_connection.php';

echo "<h2>تنظيف قاعدة البيانات - حذف الجدول الإضافي</h2>";

try {
    // التحقق من وجود الجدول الإضافي
    $extra_table_exists = query_row("SELECT table_name FROM information_schema.tables 
                                    WHERE table_schema = 'classvisit_db' 
                                    AND table_name = 'evaluation_recommendations'");
    
    if ($extra_table_exists) {
        echo "<p style='color: orange;'>⚠️ وجد جدول إضافي: evaluation_recommendations</p>";
        
        // عرض عدد السجلات في الجدول الإضافي
        $extra_count = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
        echo "<p>عدد السجلات في الجدول الإضافي: {$extra_count['count']}</p>";
        
        // التحقق من وجود الجدول الأساسي
        $main_table_exists = query_row("SELECT table_name FROM information_schema.tables 
                                       WHERE table_schema = 'classvisit_db' 
                                       AND table_name = 'recommendations'");
        
        if ($main_table_exists) {
            $main_count = query_row("SELECT COUNT(*) as count FROM recommendations");
            echo "<p>عدد السجلات في الجدول الأساسي: {$main_count['count']}</p>";
            
            if ($main_count['count'] >= $extra_count['count']) {
                echo "<p style='color: green;'>✓ الجدول الأساسي يحتوي على البيانات الكاملة</p>";
                
                if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
                    // حذف الجدول الإضافي
                    execute("DROP TABLE evaluation_recommendations");
                    echo "<p style='color: green;'>✅ تم حذف الجدول الإضافي بنجاح</p>";
                    echo "<p>النظام الآن يستخدم الجدول الأساسي فقط كما هو مطلوب</p>";
                } else {
                    echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
                    echo "<h3 style='color: #856404;'>تأكيد الحذف</h3>";
                    echo "<p>هل تريد حذف الجدول الإضافي evaluation_recommendations؟</p>";
                    echo "<p>سيبقى النظام يعمل بالجدول الأساسي recommendations فقط</p>";
                    echo "<a href='?confirm=yes' style='background: #dc3545; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>نعم، احذف الجدول الإضافي</a> ";
                    echo "<a href='recommendations_management.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>لا، الذهاب لإدارة التوصيات</a>";
                    echo "</div>";
                }
            } else {
                echo "<p style='color: red;'>❌ الجدول الأساسي لا يحتوي على جميع البيانات - لا يمكن الحذف</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ الجدول الأساسي غير موجود</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ لا يوجد جدول إضافي - النظام نظيف</p>";
        echo "<p>النظام يستخدم الجدول الأساسي recommendations فقط</p>";
    }
    
    // عرض حالة الجداول النهائية
    echo "<h3>حالة الجداول النهائية:</h3>";
    $recommendation_tables = query("SELECT table_name FROM information_schema.tables 
                                   WHERE table_schema = 'classvisit_db' 
                                   AND table_name LIKE '%recommendation%'
                                   ORDER BY table_name");
    
    foreach ($recommendation_tables as $table) {
        $count = query_row("SELECT COUNT(*) as count FROM `{$table['table_name']}`");
        echo "<p>📁 {$table['table_name']}: {$count['count']} سجل</p>";
    }
    
    echo "<p><a href='recommendations_management.php' style='background: #0284c7; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px; margin-top: 20px; display: inline-block;'>🚀 اختبار النظام النهائي</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}
?>
