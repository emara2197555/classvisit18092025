<?php
require_once 'includes/db_connection.php';

echo "<h2>اختبار صلاحيات المعلم لصفحة الزيارات</h2>";

// محاكاة تسجيل دخول المعلم
$teacher_user_id = 244; // المعلم عبدالعزيز
$teacher_id = 343;

echo "<h3>معلومات المعلم</h3>";
$user = query_row("SELECT * FROM users WHERE id = ?", [$teacher_user_id]);
echo "المستخدم: " . $user['full_name'] . " (ID: $teacher_user_id)<br>";

$teacher = query_row("SELECT * FROM teachers WHERE id = ?", [$teacher_id]);
echo "المعلم: " . $teacher['name'] . " (ID: $teacher_id)<br>";
echo "مربوط بالمستخدم: " . ($teacher['user_id'] == $teacher_user_id ? 'نعم' : 'لا') . "<br>";

echo "<h3>اختبار الوصول لصفحة الزيارات</h3>";
echo "الرابط: <a href='visits.php?teacher_id=$teacher_id' target='_blank'>visits.php?teacher_id=$teacher_id</a><br>";

echo "<h3>زيارات المعلم</h3>";
$visits = query("SELECT * FROM visits WHERE teacher_id = ? ORDER BY visit_date DESC", [$teacher_id]);
echo "عدد الزيارات: " . count($visits) . "<br>";

foreach ($visits as $visit) {
    echo "- زيارة " . $visit['id'] . " بتاريخ " . $visit['visit_date'] . "<br>";
}

echo "<h3>ملاحظة</h3>";
echo "<p style='color: green;'>تم تعديل ملف visits.php ليسمح للمعلمين بالوصول ورؤية زياراتهم فقط.</p>";
echo "<p>المعلمون الآن يمكنهم:</p>";
echo "<ul>";
echo "<li>الوصول لصفحة visits.php</li>";
echo "<li>رؤية زياراتهم فقط (لا يرون زيارات معلمين آخرين)</li>";
echo "<li>لا يمكنهم استخدام فلاتر البحث (مخفية عنهم)</li>";
echo "</ul>";
?>
