<?php
require_once 'includes/db_connection.php';

echo "<h2>اختبار صفحة المعلمين المتميزين للمعلم</h2>";

// بيانات المعلم للاختبار
$teacher_user_id = 244; // المعلم عبدالعزيز
$teacher_id = 343;

echo "<h3>معلومات المعلم</h3>";
$user = query_row("SELECT * FROM users WHERE id = ?", [$teacher_user_id]);
echo "المستخدم: " . $user['full_name'] . " (ID: $teacher_user_id)<br>";

$teacher = query_row("SELECT * FROM teachers WHERE id = ?", [$teacher_id]);
echo "المعلم: " . $teacher['name'] . " (ID: $teacher_id)<br>";

echo "<h3>تحليل أداء المعلم في المؤشرات</h3>";

// التحقق من أداء المعلم في المؤشرات
$performance_sql = "
    SELECT 
        ei.id AS indicator_id,
        ei.name AS indicator_name,
        ed.name AS domain_name,
        AVG(ve.score) AS avg_score,
        (AVG(ve.score) * (100/3)) AS percentage_score,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        evaluation_indicators ei ON ve.indicator_id = ei.id
    JOIN 
        evaluation_domains ed ON ei.domain_id = ed.id
    WHERE 
        v.teacher_id = ?
        AND ve.score IS NOT NULL
    GROUP BY 
        ei.id, ei.name, ed.name
    ORDER BY 
        percentage_score DESC
";

$performance = query($performance_sql, [$teacher_id]);

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='padding: 8px;'>المؤشر</th>";
echo "<th style='padding: 8px;'>المجال</th>";
echo "<th style='padding: 8px;'>النسبة المئوية</th>";
echo "<th style='padding: 8px;'>عدد الزيارات</th>";
echo "<th style='padding: 8px;'>مؤهل للتدريب</th>";
echo "</tr>";

$expert_indicators = 0;
foreach ($performance as $indicator) {
    $is_expert = ($indicator['percentage_score'] >= 85 && $indicator['visits_count'] >= 2);
    if ($is_expert) $expert_indicators++;
    
    echo "<tr" . ($is_expert ? " style='background: #e7f3ff;'" : "") . ">";
    echo "<td style='padding: 8px;'>" . $indicator['indicator_name'] . "</td>";
    echo "<td style='padding: 8px;'>" . $indicator['domain_name'] . "</td>";
    echo "<td style='padding: 8px;'>" . round($indicator['percentage_score'], 1) . "%</td>";
    echo "<td style='padding: 8px;'>" . $indicator['visits_count'] . "</td>";
    echo "<td style='padding: 8px;'>" . ($is_expert ? "✅ نعم" : "❌ لا") . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>الملخص</h3>";
echo "<p><strong>إجمالي المؤشرات:</strong> " . count($performance) . "</p>";
echo "<p><strong>المؤشرات المؤهلة للتدريب:</strong> $expert_indicators</p>";
echo "<p><strong>الحد الأدنى للتأهيل:</strong> 85% مع زيارتين على الأقل</p>";

echo "<h3>التعديلات المُنفذة</h3>";
echo "<ul>";
echo "<li>✅ إضافة صلاحية 'Teacher' لصفحة expert_trainers.php</li>";
echo "<li>✅ المعلمون يرون أنفسهم فقط إذا كانوا مؤهلين للتدريب</li>";
echo "<li>✅ تغيير عنوان الصفحة للمعلمين: 'تميزي في التدريب - نقاط قوتك التدريبية'</li>";
echo "<li>✅ رسالة ترحيب خاصة للمعلمين</li>";
echo "<li>✅ رسالة تشجيعية إذا لم يكونوا مؤهلين بعد</li>";
echo "</ul>";

echo "<h3>ما سيراه المعلم في الصفحة</h3>";
if ($expert_indicators > 0) {
    echo "<p style='color: green;'>✅ سيرى المؤشرات التي يتميز فيها ($expert_indicators مؤشر)</p>";
    echo "<p>سيرى نفسه كمدرب معتمد في هذه المؤشرات</p>";
} else {
    echo "<p style='color: orange;'>⚠️ سيرى رسالة تشجيعية للوصول للحد الأدنى (85%)</p>";
    echo "<p>الصفحة ستكون فارغة من المدربين لأنه لم يصل للحد الأدنى بعد</p>";
}

echo "<h3>رابط الاختبار</h3>";
echo "<p><a href='expert_trainers.php' target='_blank'>expert_trainers.php</a></p>";
?>
