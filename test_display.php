<?php
/**
 * اختبار عرض التوصيات - نفس المنطق المستخدم في الصفحة الأصلية
 */

require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "<h2>اختبار عرض التوصيات</h2>";

try {
    // نفس الكود المستخدم في recommendations_management.php
    $domains = query("SELECT * FROM evaluation_domains ORDER BY sort_order, id");
    $indicators = query("SELECT i.*, d.name as domain_name 
                        FROM evaluation_indicators i 
                        LEFT JOIN evaluation_domains d ON i.domain_id = d.id 
                        ORDER BY COALESCE(d.sort_order, 999), d.id, i.sort_order, i.id");
    
    // جلب جميع التوصيات (مع حماية من التكرار)
    $all_recommendations_raw = query("
        SELECT r.*, i.name as indicator_name, d.name as domain_name, d.id as domain_id
        FROM recommendations r
        LEFT JOIN evaluation_indicators i ON r.indicator_id = i.id
        LEFT JOIN evaluation_domains d ON i.domain_id = d.id
        GROUP BY r.id
        ORDER BY COALESCE(d.sort_order, 999), COALESCE(i.sort_order, 999), COALESCE(r.sort_order, 999), r.id
    ");
    
    echo "<h3>نتائج الاستعلام الخام:</h3>";
    echo "<p>عدد النتائج: " . count($all_recommendations_raw) . "</p>";
    
    // فلترة إضافية لضمان عدم وجود تكرار في الواجهة
    $all_recommendations = [];
    $seen_combinations = [];
    
    foreach ($all_recommendations_raw as $rec) {
        $key = $rec['indicator_id'] . '_' . md5($rec['text']);
        if (!isset($seen_combinations[$key])) {
            $seen_combinations[$key] = true;
            $all_recommendations[] = $rec;
        } else {
            echo "<div style='background: #ffe6e6; padding: 5px; margin: 2px; border: 1px solid #ff9999;'>";
            echo "🔄 تم فلترة توصية مكررة: ID=" . $rec['id'] . ", المؤشر=" . $rec['indicator_id'] . ", النص=" . htmlspecialchars(substr($rec['text'], 0, 30)) . "...";
            echo "</div>";
        }
    }
    
    echo "<h3>النتائج بعد الفلترة:</h3>";
    echo "<p>عدد التوصيات النهائي: " . count($all_recommendations) . "</p>";
    echo "<p>عدد التوصيات المفلترة: " . (count($all_recommendations_raw) - count($all_recommendations)) . "</p>";
    
    // إحصائيات تفصيلية
    $indicator_counts = [];
    foreach ($all_recommendations as $rec) {
        $indicator_id = $rec['indicator_id'];
        if (!isset($indicator_counts[$indicator_id])) {
            $indicator_counts[$indicator_id] = 0;
        }
        $indicator_counts[$indicator_id]++;
    }
    
    echo "<h3>توزيع التوصيات حسب المؤشر:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>معرف المؤشر</th><th>عدد التوصيات</th><th>حالة</th></tr>";
    
    foreach ($indicator_counts as $indicator_id => $count) {
        $status = $count > 1 ? "⚠️ متعدد" : "✅ واحد";
        $color = $count > 1 ? "background: #ffe6e6;" : "background: #e8f5e8;";
        echo "<tr style='$color'>";
        echo "<td>$indicator_id</td>";
        echo "<td>$count</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // عرض عينة من البيانات
    echo "<h3>عينة من البيانات النهائية (أول 20):</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
    echo "<tr><th>ID</th><th>المؤشر</th><th>المجال</th><th>النص (أول 50 حرف)</th></tr>";
    
    for ($i = 0; $i < min(20, count($all_recommendations)); $i++) {
        $rec = $all_recommendations[$i];
        echo "<tr>";
        echo "<td>" . $rec['id'] . "</td>";
        echo "<td>" . $rec['indicator_id'] . "</td>";
        echo "<td>" . htmlspecialchars($rec['domain_name']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($rec['text'], 0, 50)) . "...</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #f44336; margin: 10px 0;'>";
    echo "<p>❌ خطأ: " . $e->getMessage() . "</p>";
    echo "</div>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; direction: rtl; }
h2, h3 { color: #333; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: right; border: 1px solid #ddd; }
th { background: #f5f5f5; }
</style>

<p><a href="recommendations_management.php">العودة إلى إدارة التوصيات</a></p>
<p><a href="debug_recommendations.php">فحص قاعدة البيانات</a></p>
<p><a href="fix_duplicates.php">إصلاح التكرار</a></p>
