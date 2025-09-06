<?php
require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

echo "فحص ربط المنسقين بالمواد:\n\n";

// فحص جدول teacher_subjects للمنسقين
$coord_subjects = $pdo->query("
    SELECT t.id, t.name, ts.subject_id, s.name as subject_name
    FROM teachers t
    LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
    LEFT JOIN subjects s ON ts.subject_id = s.id
    WHERE t.job_title = 'منسق المادة'
    ORDER BY t.id
    LIMIT 10
")->fetchAll();

echo "ربط المنسقين بالمواد:\n";
foreach ($coord_subjects as $cs) {
    echo "المعلم: {$cs['name']} (ID: {$cs['id']})\n";
    echo "المادة: " . ($cs['subject_name'] ?? 'غير مربوط') . " (ID: " . ($cs['subject_id'] ?? 'لا يوجد') . ")\n";
    echo "---\n";
}

// فحص عدد المنسقين المربوطين بمواد
$linked_count = $pdo->query("
    SELECT COUNT(DISTINCT t.id) as count
    FROM teachers t
    INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id
    WHERE t.job_title = 'منسق المادة'
")->fetchColumn();

echo "\nعدد المنسقين المربوطين بمواد: $linked_count\n";

$total_count = $pdo->query("
    SELECT COUNT(*) as count
    FROM teachers 
    WHERE job_title = 'منسق المادة'
")->fetchColumn();

echo "إجمالي عدد المنسقين: $total_count\n";
?>
