<?php
session_start();

// إعادة تعيين جلسة المنسق
$_SESSION['user_id'] = 343;
$_SESSION['username'] = 'm.ali0308';
$_SESSION['role_name'] = 'Subject Coordinator';
$_SESSION['role_id'] = 5;
$_SESSION['school_id'] = 1;
$_SESSION['full_name'] = 'محمد مصطفى عبداللطيف علي';
$_SESSION['permissions'] = array(
    'reports_view' => 1,
    'visit_creation' => 1,
    'subject_management' => 1
);
$_SESSION['teacher_id'] = 343;
$_SESSION['subject_id'] = 3;
$_SESSION['user_role_name'] = 'Subject Coordinator'; // تصحيح هذا

echo "<h1>تم إعادة تعيين جلسة المنسق</h1>";
echo "<p>المعرف: " . $_SESSION['user_id'] . "</p>";
echo "<p>الدور: " . $_SESSION['role_name'] . "</p>";
echo "<p>مادة: " . $_SESSION['subject_id'] . "</p>";

echo "<h2>اختبر صفحات المنسق:</h2>";
echo "<ul>";
echo "<li><a href='coordinator_dashboard.php' target='_blank'>لوحة المنسق</a></li>";
echo "<li><a href='visits.php' target='_blank'>visits.php</a> - يجب أن تظهر جميع الأزرار</li>";
echo "</ul>";

echo "<br><a href='teacher_test_session.php'>التحويل لجلسة معلم</a>";
?>

<style>
body { font-family: Arial, sans-serif; direction: rtl; text-align: right; margin: 20px; }
a { color: #0066cc; text-decoration: none; margin: 5px; display: inline-block; }
a:hover { text-decoration: underline; }
ul { background: #e8f5e8; padding: 15px; border-radius: 5px; }
li { margin: 8px 0; }
</style>
