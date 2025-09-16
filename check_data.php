<?php
require_once 'includes/db_connection.php';

echo "فحص البيانات المرجعية:\n\n";

// فحص السنوات الدراسية
echo "=== السنوات الدراسية ===\n";
$years = query("SELECT * FROM academic_years");
foreach($years as $year) {
    echo "ID: " . $year['id'] . " - الاسم: " . $year['name'] . " - نشطة: " . ($year['is_active'] ? 'نعم' : 'لا') . "\n";
}

// فحص المعلمين
echo "\n=== المعلمين (أول 5) ===\n";
$teachers = query("SELECT * FROM teachers LIMIT 5");
foreach($teachers as $teacher) {
    echo "ID: " . $teacher['id'] . " - الاسم: " . $teacher['name'] . "\n";
}

// فحص المواد
echo "\n=== المواد (أول 5) ===\n";
$subjects = query("SELECT * FROM subjects LIMIT 5");
foreach($subjects as $subject) {
    echo "ID: " . $subject['id'] . " - الاسم: " . $subject['name'] . "\n";
}

// فحص المستخدمين (منسقي التعليم الإلكتروني)
echo "\n=== منسقي التعليم الإلكتروني ===\n";
$coordinators = query("SELECT * FROM users WHERE role_id = 7");
foreach($coordinators as $coordinator) {
    echo "ID: " . $coordinator['id'] . " - الاسم: " . $coordinator['full_name'] . "\n";
}
?>
