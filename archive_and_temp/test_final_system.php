<?php
// اختبار نهائي لنظام إدارة التوصيات
require_once 'includes/db_connection.php';

echo "<h2>🎯 اختبار نهائي لنظام إدارة التوصيات</h2>";
echo "<style>
.success { color: green; font-weight: bold; }
.error { color: red; font-weight: bold; }
.warning { color: orange; font-weight: bold; }
.info { color: blue; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: right; }
th { background-color: #f2f2f2; }
.card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; margin: 10px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
</style>";

try {
    echo "<div class='card'>";
    echo "<h3>1️⃣ فحص الجداول المطلوبة</h3>";
    
    // فحص الجداول الأساسية
    $required_tables = [
        'evaluation_domains' => 'جدول المجالات',
        'evaluation_indicators' => 'جدول المؤشرات', 
        'evaluation_recommendations' => 'جدول التوصيات'
    ];
    
    $all_tables_exist = true;
    
    foreach ($required_tables as $table => $description) {
        $exists = query_row("SELECT table_name FROM information_schema.tables 
                           WHERE table_schema = 'classvisit_db' AND table_name = '$table'");
        
        if ($exists) {
            $count = query_row("SELECT COUNT(*) as count FROM `$table`");
            echo "<p class='success'>✓ $description ($table): {$count['count']} سجل</p>";
        } else {
            echo "<p class='error'>❌ $description ($table): غير موجود</p>";
            $all_tables_exist = false;
        }
    }
    echo "</div>";
    
    if (!$all_tables_exist) {
        echo "<div class='card'><p class='error'>❌ لا يمكن المتابعة - جداول مطلوبة مفقودة</p></div>";
        exit;
    }
    
    echo "<div class='card'>";
    echo "<h3>2️⃣ اختبار الاستعلامات الأساسية</h3>";
    
    // اختبار استعلام المجالات
    try {
        $domains = query("SELECT * FROM evaluation_domains ORDER BY COALESCE(sort_order, id) LIMIT 5");
        echo "<p class='success'>✓ استعلام المجالات: " . count($domains) . " نتيجة</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ استعلام المجالات: " . $e->getMessage() . "</p>";
    }
    
    // اختبار استعلام المؤشرات
    try {
        $indicators = query("SELECT i.*, d.name as domain_name 
                           FROM evaluation_indicators i 
                           LEFT JOIN evaluation_domains d ON i.domain_id = d.id 
                           ORDER BY COALESCE(d.sort_order, 999), COALESCE(i.sort_order, 999) LIMIT 5");
        echo "<p class='success'>✓ استعلام المؤشرات: " . count($indicators) . " نتيجة</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ استعلام المؤشرات: " . $e->getMessage() . "</p>";
    }
    
    // اختبار استعلام التوصيات
    try {
        $recommendations = query("SELECT r.*, i.name as indicator_name, d.name as domain_name
                                FROM evaluation_recommendations r
                                LEFT JOIN evaluation_indicators i ON r.indicator_id = i.id
                                LEFT JOIN evaluation_domains d ON i.domain_id = d.id
                                ORDER BY COALESCE(d.sort_order, 999), COALESCE(i.sort_order, 999), COALESCE(r.sort_order, 999) LIMIT 5");
        echo "<p class='success'>✓ استعلام التوصيات: " . count($recommendations) . " نتيجة</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ استعلام التوصيات: " . $e->getMessage() . "</p>";
    }
    echo "</div>";
    
    echo "<div class='card'>";
    echo "<h3>3️⃣ عرض عينة من البيانات</h3>";
    
    // عرض توزيع التوصيات حسب المجالات
    $domain_stats = query("SELECT d.name as domain_name, COUNT(r.id) as recommendations_count
                          FROM evaluation_domains d
                          LEFT JOIN evaluation_indicators i ON d.id = i.domain_id
                          LEFT JOIN evaluation_recommendations r ON i.id = r.indicator_id
                          GROUP BY d.id, d.name
                          ORDER BY d.id");
    
    if (count($domain_stats) > 0) {
        echo "<h4>توزيع التوصيات حسب المجالات:</h4>";
        echo "<table>";
        echo "<tr><th>المجال</th><th>عدد التوصيات</th></tr>";
        foreach ($domain_stats as $stat) {
            echo "<tr><td>{$stat['domain_name']}</td><td>{$stat['recommendations_count']}</td></tr>";
        }
        echo "</table>";
    }
    
    // عرض نموذج من التوصيات
    $sample_recommendations = query("SELECT r.text, i.name as indicator_name, d.name as domain_name
                                   FROM evaluation_recommendations r
                                   LEFT JOIN evaluation_indicators i ON r.indicator_id = i.id
                                   LEFT JOIN evaluation_domains d ON i.domain_id = d.id
                                   ORDER BY r.id LIMIT 10");
    
    if (count($sample_recommendations) > 0) {
        echo "<h4>نموذج من التوصيات:</h4>";
        echo "<table>";
        echo "<tr><th>المجال</th><th>المؤشر</th><th>التوصية</th></tr>";
        foreach ($sample_recommendations as $rec) {
            $short_text = mb_substr($rec['text'], 0, 50) . (mb_strlen($rec['text']) > 50 ? '...' : '');
            echo "<tr><td>{$rec['domain_name']}</td><td>{$rec['indicator_name']}</td><td>{$short_text}</td></tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
    echo "<div class='card'>";
    echo "<h3>4️⃣ النتيجة النهائية</h3>";
    
    $total_domains = query_row("SELECT COUNT(*) as count FROM evaluation_domains");
    $total_indicators = query_row("SELECT COUNT(*) as count FROM evaluation_indicators");
    $total_recommendations = query_row("SELECT COUNT(*) as count FROM evaluation_recommendations");
    
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px;'>";
    echo "<h4 style='color: #155724; margin: 0 0 10px 0;'>🎉 النظام جاهز للاستخدام!</h4>";
    echo "<ul style='color: #155724; margin: 0;'>";
    echo "<li><strong>{$total_domains['count']}</strong> مجال تقييم</li>";
    echo "<li><strong>{$total_indicators['count']}</strong> مؤشر تقييم</li>";
    echo "<li><strong>{$total_recommendations['count']}</strong> توصية</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='text-align: center; margin-top: 20px;'>";
    echo "<a href='recommendations_management.php' style='background: #007bff; color: white; padding: 15px 30px; text-decoration: none; border-radius: 8px; font-size: 18px; display: inline-block; margin: 10px;'>🚀 بدء استخدام نظام إدارة التوصيات</a>";
    echo "<br>";
    echo "<a href='archive_and_temp/cleanup_old_recommendations.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-size: 14px; display: inline-block; margin: 5px;'>🧹 تنظيف قاعدة البيانات (اختياري)</a>";
    echo "</div>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='card'>";
    echo "<p class='error'>❌ خطأ في الاختبار: " . $e->getMessage() . "</p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
