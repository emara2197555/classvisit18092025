<?php
// تشخيص قيم NULL في print_visit.php

require_once 'includes/db_connection.php';

$visit_id = isset($_GET['id']) ? (int)$_GET['id'] : 16;

echo "<h1>تشخيص قيم الدرجات للزيارة رقم $visit_id</h1>";

// جلب التقييمات
$evaluation_sql = "
    SELECT 
        ve.score,
        ei.name as indicator_text
    FROM 
        visit_evaluations ve
    JOIN 
        evaluation_indicators ei ON ve.indicator_id = ei.id
    WHERE 
        ve.visit_id = ?
    ORDER BY
        ve.indicator_id
    LIMIT 10
";

$evaluations = query($evaluation_sql, [$visit_id]);

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>المؤشر</th><th>القيمة</th><th>النوع</th><th>== 0</th><th>=== null</th><th>is_null</th><th>empty</th></tr>";

foreach ($evaluations as $eval) {
    $score = $eval['score'];
    echo "<tr>";
    echo "<td>" . htmlspecialchars($eval['indicator_text']) . "</td>";
    echo "<td>" . ($score === null ? 'NULL' : $score) . "</td>";
    echo "<td>" . gettype($score) . "</td>";
    echo "<td>" . ($score == 0 ? 'YES' : 'NO') . "</td>";
    echo "<td>" . ($score === null ? 'YES' : 'NO') . "</td>";
    echo "<td>" . (is_null($score) ? 'YES' : 'NO') . "</td>";
    echo "<td>" . (empty($score) ? 'YES' : 'NO') . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>اختبار الشروط:</h2>";

foreach ($evaluations as $eval) {
    $score = $eval['score'];
    echo "<p><strong>" . htmlspecialchars($eval['indicator_text']) . ":</strong><br>";
    echo "القيمة: " . ($score === null ? 'NULL' : $score) . "<br>";
    echo "== 3: " . ($score == 3 ? '✓' : '') . "<br>";
    echo "== 2: " . ($score == 2 ? '✓' : '') . "<br>";
    echo "== 1: " . ($score == 1 ? '✓' : '') . "<br>";
    echo "== 0: " . ($score == 0 ? '✓' : '') . "<br>";
    echo "=== null: " . ($score === null ? '✓' : '') . "<br>";
    echo "is_null: " . (is_null($score) ? '✓' : '') . "<br>";
    echo "</p>";
    
    if (count($evaluations) > 3) break; // عرض 3 أمثلة فقط
}
?>
