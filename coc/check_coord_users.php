<?php
require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

echo "فحص أول 5 منسقين:\n";
$coordinators = $pdo->query("
    SELECT * FROM teachers 
    WHERE job_title = 'منسق المادة'
    LIMIT 5
")->fetchAll();

foreach ($coordinators as $coord) {
    echo "المعرف: {$coord['id']}\n";
    echo "الاسم: {$coord['name']}\n";
    echo "المدرسة: {$coord['school_id']}\n";
    echo "---\n";
}

echo "\nفحص أول 5 مستخدمين:\n";
$users = $pdo->query("
    SELECT * FROM users 
    WHERE role_id = 4
    LIMIT 5
")->fetchAll();

foreach ($users as $user) {
    echo "المعرف: {$user['id']}\n";
    echo "اسم المستخدم: {$user['username']}\n";
    echo "الاسم الكامل: {$user['full_name']}\n";
    echo "دور: {$user['role_id']}\n";
    echo "المدرسة: {$user['school_id']}\n";
    echo "---\n";
}
?>
