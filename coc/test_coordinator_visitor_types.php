<?php
// محاكاة جلسة منسق مادة
session_start();

// تعيين بيانات منسق مادة
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'Subject Coordinator';
$_SESSION['school_id'] = 1;

require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

$current_user_role = 'Subject Coordinator';

echo "<h2>اختبار أنواع الزوار للمنسق</h2>";

// تطبيق نفس منطق evaluation_form.php
if ($current_user_role === 'Subject Coordinator') {
    $visitor_types = query("
        SELECT * FROM visitor_types 
        WHERE name IN ('منسق المادة', 'موجه المادة') 
        ORDER BY name
    ");
} else {
    $visitor_types = query("SELECT * FROM visitor_types ORDER BY name");
}

echo "<h3>أنواع الزوار المتاحة للمنسق:</h3>";
echo "<ul>";
foreach ($visitor_types as $type) {
    echo "<li>ID: {$type['id']} - الاسم: {$type['name']}</li>";
}
echo "</ul>";

echo "<h3>محاكاة قائمة منسدلة:</h3>";
echo "<select>";
echo "<option value=''>اختر نوع الزائر...</option>";
foreach ($visitor_types as $type) {
    echo "<option value='{$type['id']}'>{$type['name']}</option>";
}
echo "</select>";
?>
