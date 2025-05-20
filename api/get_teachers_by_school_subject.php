<?php
// تضمين ملفات قاعدة البيانات والوظائف
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// استلام معرف المدرسة والمادة
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

// بناء الاستعلام حسب المعلمات المتوفرة
if ($school_id > 0 && $subject_id > 0) {
    // فلترة حسب المدرسة والمادة
    $query = "
        SELECT DISTINCT t.id, t.name
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.school_id = ? AND ts.subject_id = ?
        ORDER BY t.name
    ";
    $teachers = query($query, [$school_id, $subject_id]);
} elseif ($school_id > 0) {
    // فلترة حسب المدرسة فقط
    $query = "
        SELECT DISTINCT t.id, t.name
        FROM teachers t
        WHERE t.school_id = ?
        ORDER BY t.name
    ";
    $teachers = query($query, [$school_id]);
} elseif ($subject_id > 0) {
    // فلترة حسب المادة فقط
    $query = "
        SELECT DISTINCT t.id, t.name
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE ts.subject_id = ?
        ORDER BY t.name
    ";
    $teachers = query($query, [$subject_id]);
} else {
    // إذا لم يتم تحديد أي منهما، ارجع جميع المعلمين
    $teachers = query("SELECT id, name FROM teachers ORDER BY name");
}

// إرجاع البيانات بتنسيق JSON
header('Content-Type: application/json');
echo json_encode($teachers);
?> 