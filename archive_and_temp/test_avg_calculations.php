<?php
// اختبار الاستعلامات المُحدثة لحساب متوسط الأداء

require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "<h1>اختبار الاستعلامات المُحدثة لحساب متوسط الأداء</h1>";

// بيانات اختبار وهمية
$test_scores = [
    ['visit_id' => 1, 'scores' => [3, 2, null, 1, 3]], // متوسط 2.25، نسبة 75%
    ['visit_id' => 2, 'scores' => [2, 2, 2, 2]], // متوسط 2.0، نسبة 66.67%
    ['visit_id' => 3, 'scores' => [3, 3, 3]], // متوسط 3.0، نسبة 100%
    ['visit_id' => 4, 'scores' => [0, 1, null, 2]], // متوسط 1.0، نسبة 33.33%
];

echo "<h2>1. اختبار الحسابات النظرية</h2>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>الزيارة</th><th>الدرجات</th><th>المتوسط (SUM/COUNT)</th><th>النسبة المئوية</th><th>التقدير</th></tr>";

foreach ($test_scores as $test) {
    $valid_scores = array_filter($test['scores'], function($score) {
        return $score !== null;
    });
    
    $sum = array_sum($valid_scores);
    $count = count($valid_scores);
    $average = $count > 0 ? $sum / $count : 0;
    $percentage = $count > 0 ? ($sum / ($count * 3)) * 100 : 0;
    
    $grade = '';
    if ($percentage >= 90) $grade = 'ممتاز';
    elseif ($percentage >= 80) $grade = 'جيد جداً';
    elseif ($percentage >= 65) $grade = 'جيد';
    elseif ($percentage >= 50) $grade = 'مقبول';
    else $grade = 'يحتاج إلى تحسين';
    
    echo "<tr>";
    echo "<td>زيارة {$test['visit_id']}</td>";
    echo "<td>[" . implode(', ', array_map(function($s) { return $s === null ? 'NULL' : $s; }, $test['scores'])) . "]</td>";
    echo "<td>" . number_format($average, 2) . "</td>";
    echo "<td>" . number_format($percentage, 2) . "%</td>";
    echo "<td style='font-weight: bold; color: " . ($percentage >= 80 ? 'green' : ($percentage >= 50 ? 'orange' : 'red')) . ";'>$grade</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h2>2. مقارنة الطرق القديمة والجديدة</h2>";

// محاكاة بيانات زيارة
$simulation_data = [
    ['score' => 3], ['score' => 2], ['score' => null], ['score' => 1], ['score' => 3]
];

// الطريقة القديمة (AVG مع NULL)
$old_sum = 0; $old_count = 0;
foreach ($simulation_data as $row) {
    if ($row['score'] !== null) {
        $old_sum += $row['score'];
        $old_count++;
    }
}
$old_avg = $old_count > 0 ? $old_sum / $old_count : 0;
$old_percentage = $old_avg * 25; // الطريقة القديمة الخاطئة

// الطريقة الجديدة (SUM/COUNT مع استثناء NULL)
$new_sum = 0; $new_count = 0;
foreach ($simulation_data as $row) {
    if ($row['score'] !== null) {
        $new_sum += $row['score'];
        $new_count++;
    }
}
$new_avg = $new_count > 0 ? $new_sum / $new_count : 0;
$new_percentage = $new_count > 0 ? ($new_sum / ($new_count * 3)) * 100 : 0;

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>الطريقة</th><th>المتوسط</th><th>النسبة المئوية</th><th>الملاحظات</th></tr>";
echo "<tr>";
echo "<td>القديمة (خطأ)</td>";
echo "<td>" . number_format($old_avg, 2) . "</td>";
echo "<td style='color: red;'>" . number_format($old_percentage, 2) . "%</td>";
echo "<td>خطأ: ضرب في 25 بدلاً من القسمة على 3</td>";
echo "</tr>";
echo "<tr>";
echo "<td>الجديدة (صحيح)</td>";
echo "<td>" . number_format($new_avg, 2) . "</td>";
echo "<td style='color: green;'>" . number_format($new_percentage, 2) . "%</td>";
echo "<td>صحيح: (SUM/COUNT) مع استثناء NULL</td>";
echo "</tr>";
echo "</table>";

echo "<h2>3. اختبار استعلام SQL الجديد</h2>";

try {
    // اختبار الاستعلام الجديد على بيانات حقيقية
    $test_query = "
        SELECT 
            v.id,
            (SUM(ve.score) / COUNT(ve.score)) AS avg_score_new,
            (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 AS percentage_new,
            COUNT(ve.score) AS evaluated_indicators,
            SUM(ve.score) AS total_score
        FROM visits v
        JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE ve.score IS NOT NULL
        GROUP BY v.id
        ORDER BY v.id DESC
        LIMIT 5
    ";
    
    $results = query($test_query);
    
    if ($results) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID الزيارة</th><th>المجموع</th><th>عدد المؤشرات</th><th>المتوسط</th><th>النسبة المئوية</th></tr>";
        
        foreach ($results as $row) {
            echo "<tr>";
            echo "<td>{$row['id']}</td>";
            echo "<td>{$row['total_score']}</td>";
            echo "<td>{$row['evaluated_indicators']}</td>";
            echo "<td>" . number_format($row['avg_score_new'], 2) . "</td>";
            echo "<td>" . number_format($row['percentage_new'], 2) . "%</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>لا توجد بيانات زيارات للاختبار</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ في الاستعلام: " . $e->getMessage() . "</p>";
}

echo "<h2>4. الفوائد من التحديث</h2>";
echo "<ul>";
echo "<li><strong>دقة أكبر:</strong> استثناء القيم NULL من الحساب</li>";
echo "<li><strong>شفافية:</strong> استخدام SUM/COUNT بدلاً من AVG</li>";
echo "<li><strong>نسب صحيحة:</strong> القسمة على 3 بدلاً من الضرب في 25</li>";
echo "<li><strong>مرونة:</strong> التعامل مع المؤشرات غير المطبقة</li>";
echo "</ul>";

echo "<p><a href='index.php'>العودة للصفحة الرئيسية</a> | <a href='evaluation_form.php'>اختبار نموذج التقييم</a></p>";
?>
