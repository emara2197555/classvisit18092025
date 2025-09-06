<?php
/**
 * سكريبت فحص التوصيات المكررة بشكل مفصل
 */

require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "<h2>فحص التوصيات في قاعدة البيانات</h2>";

try {
    // فحص شامل للتوصيات
    echo "<h3>1. إحصائيات عامة:</h3>";
    $total_recommendations = query_row("SELECT COUNT(*) as count FROM recommendations");
    echo "<p>إجمالي التوصيات: " . $total_recommendations['count'] . "</p>";
    
    // فحص التوصيات المكررة بالنص والمؤشر
    echo "<h3>2. البحث عن التوصيات المكررة:</h3>";
    $duplicates = query("
        SELECT indicator_id, text, COUNT(*) as count, 
               GROUP_CONCAT(id ORDER BY id) as ids
        FROM recommendations 
        GROUP BY indicator_id, text 
        HAVING COUNT(*) > 1
        ORDER BY count DESC
    ");
    
    if (empty($duplicates)) {
        echo "<p style='color: green;'>✅ لا توجد توصيات مكررة في قاعدة البيانات</p>";
    } else {
        echo "<p style='color: red;'>❌ تم العثور على " . count($duplicates) . " مجموعة من التوصيات المكررة:</p>";
        foreach ($duplicates as $dup) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px;'>";
            echo "<strong>المؤشر ID:</strong> " . $dup['indicator_id'] . "<br>";
            echo "<strong>عدد التكرار:</strong> " . $dup['count'] . "<br>";
            echo "<strong>IDs:</strong> " . $dup['ids'] . "<br>";
            echo "<strong>النص:</strong> " . htmlspecialchars(substr($dup['text'], 0, 100)) . "...<br>";
            echo "</div>";
        }
    }
    
    // فحص الاستعلام المستخدم في الصفحة
    echo "<h3>3. اختبار الاستعلام المستخدم في الصفحة:</h3>";
    $query_result = query("
        SELECT r.*, i.name as indicator_name, d.name as domain_name, d.id as domain_id
        FROM recommendations r
        LEFT JOIN evaluation_indicators i ON r.indicator_id = i.id
        LEFT JOIN evaluation_domains d ON i.domain_id = d.id
        GROUP BY r.id
        ORDER BY COALESCE(d.sort_order, 999), COALESCE(i.sort_order, 999), COALESCE(r.sort_order, 999), r.id
    ");
    
    echo "<p>عدد النتائج من الاستعلام: " . count($query_result) . "</p>";
    
    // عرض أول 10 نتائج كعينة
    echo "<h3>4. عينة من النتائج (أول 10):</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>المؤشر ID</th><th>النص (أول 50 حرف)</th><th>المجال</th></tr>";
    
    for ($i = 0; $i < min(10, count($query_result)); $i++) {
        $rec = $query_result[$i];
        echo "<tr>";
        echo "<td>" . $rec['id'] . "</td>";
        echo "<td>" . $rec['indicator_id'] . "</td>";
        echo "<td>" . htmlspecialchars(substr($rec['text'], 0, 50)) . "...</td>";
        echo "<td>" . htmlspecialchars($rec['domain_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // فحص التوصيات لمؤشر معين (مثال)
    echo "<h3>5. فحص توصيات لمؤشر معين (ID=1):</h3>";
    $indicator_1_recs = query("SELECT * FROM recommendations WHERE indicator_id = 1");
    echo "<p>عدد التوصيات للمؤشر 1: " . count($indicator_1_recs) . "</p>";
    
    if (count($indicator_1_recs) > 1) {
        echo "<div style='background: #ffe6e6; padding: 10px; border: 1px solid #ff9999;'>";
        echo "<strong>تحذير:</strong> يوجد أكثر من توصية للمؤشر 1:<br>";
        foreach ($indicator_1_recs as $rec) {
            echo "- ID: " . $rec['id'] . " | النص: " . htmlspecialchars(substr($rec['text'], 0, 50)) . "...<br>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; direction: rtl; }
h2, h3 { color: #333; }
table { margin: 10px 0; }
th, td { padding: 8px; text-align: right; }
</style>

<p><a href="recommendations_management.php">العودة إلى إدارة التوصيات</a></p>
