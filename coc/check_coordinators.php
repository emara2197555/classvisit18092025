<?php
require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

echo "فحص بيانات المنسقين:\n\n";

// جلب المنسقين الذين لديهم حساب مستخدم
$coordinators = $pdo->query("
    SELECT t.id, t.name, t.job_title, t.school_id, u.username, u.role_id
    FROM teachers t 
    LEFT JOIN users u ON t.id = u.teacher_id
    WHERE t.job_title = 'منسق المادة'
    ORDER BY t.id
")->fetchAll();

echo "المنسقون في النظام:\n";
foreach ($coordinators as $coord) {
    echo "المعرف: {$coord['id']}\n";
    echo "الاسم: {$coord['name']}\n";
    echo "الوظيفة: {$coord['job_title']}\n";
    echo "المدرسة: {$coord['school_id']}\n";
    echo "اسم المستخدم: " . ($coord['username'] ?? 'لا يوجد') . "\n";
    echo "الدور: " . ($coord['role_id'] ?? 'لا يوجد') . "\n";
    echo "---\n";
}

// فحص جدول coordinator_supervisors
echo "\nبيانات coordinator_supervisors:\n";
$coord_supervisors = $pdo->query("
    SELECT cs.*, u.username, t.name as teacher_name 
    FROM coordinator_supervisors cs
    LEFT JOIN users u ON cs.user_id = u.id
    LEFT JOIN teachers t ON u.teacher_id = t.id
")->fetchAll();

foreach ($coord_supervisors as $cs) {
    echo "معرف المستخدم: {$cs['user_id']}\n";
    echo "معرف المادة: {$cs['subject_id']}\n";
    echo "اسم المستخدم: " . ($cs['username'] ?? 'لا يوجد') . "\n";
    echo "اسم المعلم: " . ($cs['teacher_name'] ?? 'لا يوجد') . "\n";
    echo "---\n";
}
?>
