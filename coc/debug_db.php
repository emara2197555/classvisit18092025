<?php
require_once 'includes/db_connection.php';

echo "=== فحص قاعدة البيانات ===\n";

// فحص وجود المعايير
$criteria_count = query_row("SELECT COUNT(*) as count FROM qatar_system_criteria WHERE is_active = 1");
echo "عدد المعايير: " . $criteria_count['count'] . "\n";

// فحص الجداول
$tables = ['qatar_system_criteria', 'qatar_system_performance', 'elearning_attendance'];
foreach ($tables as $table) {
    $result = query_row("SHOW TABLES LIKE '$table'");
    echo "جدول $table: " . ($result ? "موجود" : "غير موجود") . "\n";
}

// فحص بعض المعايير
echo "\n=== المعايير الموجودة ===\n";
$criteria = query("SELECT id, criterion_name, category FROM qatar_system_criteria WHERE is_active = 1 LIMIT 5");
foreach ($criteria as $criterion) {
    echo $criterion['id'] . " - " . $criterion['criterion_name'] . " (" . $criterion['category'] . ")\n";
}

// فحص دور منسق التعليم الإلكتروني
echo "\n=== فحص الأدوار ===\n";
$role = query_row("SELECT * FROM user_roles WHERE name = 'E-Learning Coordinator'");
if ($role) {
    echo "دور منسق التعليم الإلكتروني موجود برقم: " . $role['id'] . "\n";
} else {
    echo "دور منسق التعليم الإلكتروني غير موجود!\n";
}
?>
