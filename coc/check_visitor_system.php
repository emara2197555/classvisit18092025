<?php
require_once 'includes/db_connection.php';

echo "فحص جداول النظام المرتبطة بالزيارات:\n";
echo "====================================\n\n";

// فحص جدول visitor_types
echo "جدول visitor_types:\n";
echo "-------------------\n";
$visitor_types = query("SELECT * FROM visitor_types LIMIT 5");
foreach ($visitor_types as $row) {
    print_r($row);
    echo "\n";
}

echo "\n";

// فحص جدول المعلمين
echo "جدول teachers (عينة):\n";
echo "---------------------\n";
$teachers = query("SELECT id, name, job_title FROM teachers LIMIT 5");
foreach ($teachers as $row) {
    print_r($row);
    echo "\n";
}

echo "\n";

// فحص كيفية الحصول على اسم الزائر
echo "تجربة الحصول على اسم الزائر:\n";
echo "----------------------------\n";
$visit_with_visitor = query("SELECT v.id, v.visitor_person_id, vt.name as visitor_type_name, t.name as teacher_name
                             FROM visits v
                             LEFT JOIN visitor_types vt ON v.visitor_type_id = vt.id
                             LEFT JOIN teachers t ON v.visitor_person_id = t.id
                             LIMIT 5");

foreach ($visit_with_visitor as $row) {
    print_r($row);
    echo "\n";
}

?>
