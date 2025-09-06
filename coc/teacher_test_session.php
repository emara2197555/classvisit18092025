<?php
session_start();

// إعداد جلسة معلم للاختبار
$_SESSION['user_id'] = 343;
$_SESSION['user_role_name'] = 'Teacher';
$_SESSION['teacher_id'] = 343;
$_SESSION['username'] = 'a.aly2202';
$_SESSION['full_name'] = 'عبدالعزيز معوض عبدالعزيز علي';
$_SESSION['school_id'] = 1;

// إزالة صلاحيات المنسق
unset($_SESSION['role_name']);
unset($_SESSION['subject_id']);
unset($_SESSION['permissions']);

echo "<h1>تم تحويل الجلسة إلى معلم</h1>";
echo "<p>المعرف: " . $_SESSION['user_id'] . "</p>";
echo "<p>الدور: " . $_SESSION['user_role_name'] . "</p>";
echo "<p>الاسم: " . $_SESSION['full_name'] . "</p>";

echo "<h2>اختبر الصفحات الآن:</h2>";
echo "<ul>";
echo "<li><a href='visits.php' target='_blank'>visits.php</a> - يجب إخفاء أزرار التعديل/الحذف</li>";
echo "<li><a href='expert_trainers.php' target='_blank'>expert_trainers.php</a> - يجب إخفاء زر الاحتياجات الجماعية</li>";
echo "<li><a href='training_needs.php' target='_blank'>training_needs.php</a> - بيانات المعلم فقط</li>";
echo "<li><a href='teacher_dashboard.php' target='_blank'>teacher_dashboard.php</a> - لوحة المعلم</li>";
echo "</ul>";

echo "<br><a href='coordinator_test_session.php'>العودة لجلسة المنسق</a>";
?>

<style>
body { font-family: Arial, sans-serif; direction: rtl; text-align: right; margin: 20px; }
a { color: #0066cc; text-decoration: none; margin: 5px; display: inline-block; }
a:hover { text-decoration: underline; }
ul { background: #f0f8ff; padding: 15px; border-radius: 5px; }
li { margin: 8px 0; }
</style>
