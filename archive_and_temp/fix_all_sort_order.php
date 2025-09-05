<?php
// إصلاح أعمدة sort_order في جميع الجداول
require_once 'includes/db_connection.php';

echo "<h2>إصلاح أعمدة sort_order في جميع الجداول</h2>";

try {
    // قائمة الجداول التي تحتاج عمود sort_order
    $tables = [
        'evaluation_domains' => 'جدول المجالات',
        'evaluation_indicators' => 'جدول المؤشرات',
        'evaluation_recommendations' => 'جدول التوصيات'
    ];
    
    foreach ($tables as $table => $description) {
        echo "<h3>فحص $description ($table)</h3>";
        
        // التحقق من وجود الجدول
        $table_exists = query_row("SELECT table_name FROM information_schema.tables 
                                  WHERE table_schema = 'classvisit_db' 
                                  AND table_name = '$table'");
        
        if (!$table_exists) {
            echo "<p style='color: red;'>❌ الجدول $table غير موجود</p>";
            continue;
        }
        
        // التحقق من وجود عمود sort_order
        $sort_order_exists = query_row("SELECT COLUMN_NAME 
                                       FROM INFORMATION_SCHEMA.COLUMNS 
                                       WHERE TABLE_SCHEMA = 'classvisit_db' 
                                       AND TABLE_NAME = '$table' 
                                       AND COLUMN_NAME = 'sort_order'");
        
        if (!$sort_order_exists) {
            echo "<p>إضافة عمود sort_order إلى $table...</p>";
            execute("ALTER TABLE `$table` ADD COLUMN `sort_order` int(11) DEFAULT 0");
            execute("ALTER TABLE `$table` ADD INDEX `sort_order` (`sort_order`)");
            
            // إعطاء قيم افتراضية للسجلات الموجودة
            execute("UPDATE `$table` SET sort_order = id WHERE sort_order = 0");
            
            echo "<p style='color: green;'>✓ تم إضافة عمود sort_order إلى $table</p>";
        } else {
            echo "<p style='color: blue;'>✓ عمود sort_order موجود في $table</p>";
        }
        
        // عرض بنية الجدول
        echo "<details><summary>بنية الجدول $table</summary>";
        $columns = query("DESCRIBE `$table`");
        echo "<table border='1' cellpadding='3' style='font-size: 12px;'>";
        echo "<tr><th>العمود</th><th>النوع</th><th>NULL</th><th>Key</th><th>Default</th></tr>";
        foreach ($columns as $col) {
            echo "<tr>";
            echo "<td>{$col['Field']}</td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>{$col['Default']}</td>";
            echo "</tr>";
        }
        echo "</table></details><br>";
    }
    
    echo "<hr>";
    echo "<h3>اختبار الاستعلامات</h3>";
    
    // اختبار استعلام evaluation_domains
    try {
        $domains = query("SELECT * FROM evaluation_domains ORDER BY sort_order, id LIMIT 3");
        echo "<p style='color: green;'>✓ استعلام evaluation_domains يعمل بنجاح (" . count($domains) . " سجل)</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في استعلام evaluation_domains: " . $e->getMessage() . "</p>";
    }
    
    // اختبار استعلام evaluation_indicators
    try {
        $indicators = query("SELECT i.*, d.name as domain_name 
                            FROM evaluation_indicators i 
                            LEFT JOIN evaluation_domains d ON i.domain_id = d.id 
                            ORDER BY d.sort_order, d.id, i.sort_order, i.id LIMIT 3");
        echo "<p style='color: green;'>✓ استعلام evaluation_indicators يعمل بنجاح (" . count($indicators) . " سجل)</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في استعلام evaluation_indicators: " . $e->getMessage() . "</p>";
    }
    
    // اختبار استعلام evaluation_recommendations
    try {
        $recommendations = query("SELECT r.*, i.name as indicator_name, d.name as domain_name
                                 FROM evaluation_recommendations r
                                 LEFT JOIN evaluation_indicators i ON r.indicator_id = i.id
                                 LEFT JOIN evaluation_domains d ON i.domain_id = d.id
                                 ORDER BY d.sort_order, i.sort_order, r.sort_order, r.id LIMIT 3");
        echo "<p style='color: green;'>✓ استعلام evaluation_recommendations يعمل بنجاح (" . count($recommendations) . " سجل)</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ خطأ في استعلام evaluation_recommendations: " . $e->getMessage() . "</p>";
    }
    
    echo "<hr>";
    echo "<p><a href='recommendations_management.php' style='background: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>اختبار صفحة إدارة التوصيات</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ عام: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
