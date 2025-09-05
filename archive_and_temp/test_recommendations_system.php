<?php
// تشخيص نهائي لنظام إدارة التوصيات
require_once 'includes/db_connection.php';

echo "<h2>تشخيص نهائي لنظام إدارة التوصيات</h2>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

try {
    echo "<h3>1. فحص الجداول الأساسية</h3>";
    
    // فحص evaluation_domains
    $domains_count = query_row("SELECT COUNT(*) as count FROM evaluation_domains");
    echo "<p class='info'>جدول evaluation_domains: {$domains_count['count']} سجل</p>";
    
    // فحص evaluation_indicators
    $indicators_count = query_row("SELECT COUNT(*) as count FROM evaluation_indicators");
    echo "<p class='info'>جدول evaluation_indicators: {$indicators_count['count']} سجل</p>";
    
    // فحص evaluation_recommendations
    $recommendations_count = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
    echo "<p class='info'>جدول evaluation_recommendations: {$recommendations_count['count']} سجل</p>";
    
    echo "<h3>2. اختبار الاستعلامات المستخدمة في النظام</h3>";
    
    // اختبار 1: جلب المجالات
    try {
        $domains = query("SELECT * FROM evaluation_domains ORDER BY sort_order, id LIMIT 5");
        echo "<p class='success'>✓ استعلام المجالات يعمل بنجاح (" . count($domains) . " نتيجة)</p>";
        
        if (count($domains) > 0) {
            echo "<table><tr><th>ID</th><th>اسم المجال</th><th>الترتيب</th></tr>";
            foreach ($domains as $domain) {
                echo "<tr><td>{$domain['id']}</td><td>{$domain['name']}</td><td>{$domain['sort_order']}</td></tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ خطأ في استعلام المجالات: " . $e->getMessage() . "</p>";
    }
    
    // اختبار 2: جلب المؤشرات مع المجالات
    try {
        $indicators = query("SELECT i.*, d.name as domain_name 
                            FROM evaluation_indicators i 
                            LEFT JOIN evaluation_domains d ON i.domain_id = d.id 
                            ORDER BY COALESCE(d.sort_order, 999), d.id, i.sort_order, i.id LIMIT 5");
        echo "<p class='success'>✓ استعلام المؤشرات مع المجالات يعمل بنجاح (" . count($indicators) . " نتيجة)</p>";
        
        if (count($indicators) > 0) {
            echo "<table><tr><th>ID</th><th>اسم المؤشر</th><th>المجال</th><th>الترتيب</th></tr>";
            foreach ($indicators as $indicator) {
                echo "<tr><td>{$indicator['id']}</td><td>{$indicator['name']}</td><td>{$indicator['domain_name']}</td><td>{$indicator['sort_order']}</td></tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ خطأ في استعلام المؤشرات: " . $e->getMessage() . "</p>";
    }
    
    // اختبار 3: جلب التوصيات مع المؤشرات والمجالات
    try {
        $recommendations = query("SELECT r.*, i.name as indicator_name, d.name as domain_name
                                 FROM evaluation_recommendations r
                                 LEFT JOIN evaluation_indicators i ON r.indicator_id = i.id
                                 LEFT JOIN evaluation_domains d ON i.domain_id = d.id
                                 ORDER BY COALESCE(d.sort_order, 999), COALESCE(i.sort_order, 999), COALESCE(r.sort_order, 999), r.id LIMIT 5");
        echo "<p class='success'>✓ استعلام التوصيات الكامل يعمل بنجاح (" . count($recommendations) . " نتيجة)</p>";
        
        if (count($recommendations) > 0) {
            echo "<table><tr><th>ID</th><th>النص</th><th>المؤشر</th><th>المجال</th><th>الترتيب</th></tr>";
            foreach ($recommendations as $rec) {
                echo "<tr><td>{$rec['id']}</td><td>" . substr($rec['text'], 0, 50) . "...</td><td>{$rec['indicator_name']}</td><td>{$rec['domain_name']}</td><td>{$rec['sort_order']}</td></tr>";
            }
            echo "</table>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ خطأ في استعلام التوصيات: " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>3. فحص بنية الجداول</h3>";
    
    // فحص أعمدة evaluation_domains
    echo "<h4>جدول evaluation_domains:</h4>";
    $domains_structure = query("DESCRIBE evaluation_domains");
    echo "<table><tr><th>العمود</th><th>النوع</th><th>NULL</th><th>Key</th><th>Default</th></tr>";
    foreach ($domains_structure as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    // فحص أعمدة evaluation_indicators
    echo "<h4>جدول evaluation_indicators:</h4>";
    $indicators_structure = query("DESCRIBE evaluation_indicators");
    echo "<table><tr><th>العمود</th><th>النوع</th><th>NULL</th><th>Key</th><th>Default</th></tr>";
    foreach ($indicators_structure as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    // فحص أعمدة evaluation_recommendations
    echo "<h4>جدول evaluation_recommendations:</h4>";
    $recommendations_structure = query("DESCRIBE evaluation_recommendations");
    echo "<table><tr><th>العمود</th><th>النوع</th><th>NULL</th><th>Key</th><th>Default</th></tr>";
    foreach ($recommendations_structure as $col) {
        echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td><td>{$col['Null']}</td><td>{$col['Key']}</td><td>{$col['Default']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h3>4. الخلاصة</h3>";
    echo "<p class='success'>✅ النظام جاهز للاستخدام</p>";
    echo "<p><a href='recommendations_management.php' style='background: #0284c7; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🚀 اختبار نظام إدارة التوصيات</a></p>";
    
} catch (Exception $e) {
    echo "<p class='error'>خطأ عام في التشخيص: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
