<?php
require_once 'includes/db_connection.php';

echo "<h2>اختبار صفحة الاحتياجات التدريبية للمعلم</h2>";

// بيانات المعلم للاختبار
$teacher_user_id = 244; // المعلم عبدالعزيز
$teacher_id = 343;

echo "<h3>معلومات المعلم</h3>";
$user = query_row("SELECT * FROM users WHERE id = ?", [$teacher_user_id]);
echo "المستخدم: " . $user['full_name'] . " (ID: $teacher_user_id)<br>";

$teacher = query_row("SELECT * FROM teachers WHERE id = ?", [$teacher_id]);
echo "المعلم: " . $teacher['name'] . " (ID: $teacher_id)<br>";
echo "المدرسة: " . $teacher['school_id'] . "<br>";

echo "<h3>مواد المعلم</h3>";
$teacher_subjects = query("
    SELECT s.* 
    FROM subjects s
    JOIN teacher_subjects ts ON s.id = ts.subject_id
    WHERE ts.teacher_id = ?
    ORDER BY s.name
", [$teacher_id]);

if (!empty($teacher_subjects)) {
    foreach ($teacher_subjects as $subject) {
        echo "- " . $subject['name'] . " (ID: " . $subject['id'] . ")<br>";
    }
} else {
    echo "لا توجد مواد مسجلة للمعلم<br>";
}

echo "<h3>مدرسة المعلم</h3>";
$school = query_row("SELECT * FROM schools WHERE id = ?", [$teacher['school_id']]);
if ($school) {
    echo "المدرسة: " . $school['name'] . " (ID: " . $school['id'] . ")<br>";
}

echo "<h3>زيارات المعلم</h3>";
$visits = query("SELECT COUNT(*) as count FROM visits WHERE teacher_id = ?", [$teacher_id]);
echo "عدد الزيارات: " . $visits[0]['count'] . "<br>";

echo "<h3>التعديلات المُنفذة</h3>";
echo "<ul>";
echo "<li>✅ إضافة صلاحية 'Teacher' لصفحة training_needs.php</li>";
echo "<li>✅ المعلمون يرون أنفسهم فقط في قائمة المعلمين</li>";
echo "<li>✅ المعلمون يرون موادهم فقط في قائمة المواد</li>";
echo "<li>✅ المعلمون يرون مدرستهم فقط في قائمة المدارس</li>";
echo "<li>✅ إعادة توجيه تلقائية إذا حاول معلم رؤية بيانات معلم آخر</li>";
echo "</ul>";

echo "<h3>الاختبار</h3>";
echo "<p>رابط الاختبار: <a href='training_needs.php?teacher_id=343' target='_blank'>training_needs.php?teacher_id=343</a></p>";
echo "<p style='color: green;'>الآن المعلم سيرى:</p>";
echo "<ul>";
echo "<li>نفسه فقط في قائمة المعلمين</li>";
echo "<li>موادة فقط في قائمة المواد</li>";
echo "<li>مدرسته فقط في قائمة المدارس</li>";
echo "<li>احتياجاته التدريبية الخاصة به</li>";
echo "</ul>";
?>
