<?php
session_start();

// Test file to verify teacher UI restrictions
echo "<h1>اختبار واجهة المعلم</h1>";
echo "<p>اختبار نظام إخفاء العناصر عن المعلمين</p>";

// Simulate teacher session
$_SESSION['user_id'] = 343; // Teacher ID we've been working with
$_SESSION['user_role_name'] = 'Teacher';
$_SESSION['teacher_id'] = 343;

echo "<h2>الجلسة الحالية:</h2>";
echo "<ul>";
echo "<li>user_id: " . $_SESSION['user_id'] . "</li>";
echo "<li>user_role_name: " . $_SESSION['user_role_name'] . "</li>";
echo "<li>teacher_id: " . $_SESSION['teacher_id'] . "</li>";
echo "</ul>";

echo "<h2>الصفحات للاختبار:</h2>";
echo "<ul>";
echo "<li><a href='visits.php' target='_blank'>visits.php</a> - يجب أن تظهر بدون أزرار التعديل والحذف</li>";
echo "<li><a href='expert_trainers.php' target='_blank'>expert_trainers.php</a> - يجب أن تظهر بدون زر الاحتياجات الجماعية</li>";
echo "<li><a href='training_needs.php' target='_blank'>training_needs.php</a> - يجب أن تظهر بيانات المعلم فقط</li>";
echo "<li><a href='teacher_dashboard.php' target='_blank'>teacher_dashboard.php</a> - لوحة تحكم المعلم</li>";
echo "</ul>";

echo "<h2>اختبار الشروط:</h2>";
$user_role_name = $_SESSION['user_role_name'];

echo "<p>الدور الحالي: <strong>$user_role_name</strong></p>";

echo "<p>اختبار شرط إخفاء أزرار الإدارة:</p>";
if ($user_role_name !== 'Teacher') {
    echo "<span style='color: red;'>❌ سيتم عرض أزرار الإدارة (خطأ!)</span>";
} else {
    echo "<span style='color: green;'>✅ سيتم إخفاء أزرار الإدارة (صحيح!)</span>";
}

echo "<br><br>";
echo "<a href='index.php'>العودة للصفحة الرئيسية</a>";
?>

<style>
body {
    font-family: Arial, sans-serif;
    direction: rtl;
    text-align: right;
    margin: 20px;
}
ul {
    background: #f5f5f5;
    padding: 15px;
    border-radius: 5px;
}
li {
    margin: 5px 0;
}
a {
    color: #0066cc;
    text-decoration: none;
}
a:hover {
    text-decoration: underline;
}
</style>
