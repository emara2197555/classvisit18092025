<?php
/**
 * API لجلب المواد الدراسية
 */

// تضمين ملفات قاعدة البيانات والوظائف
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// التأكد من وجود معرف المدرسة
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;

// التحقق من صحة المعرف
if ($school_id <= 0) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

// جلب المواد المرتبطة بالمدرسة
$subjects = query("
    SELECT DISTINCT s.* 
    FROM subjects s
    JOIN teacher_subjects ts ON s.id = ts.subject_id
    JOIN teachers t ON ts.teacher_id = t.id
    WHERE t.school_id = ?
    ORDER BY s.name
", [$school_id]);

// إرجاع النتائج بتنسيق JSON
header('Content-Type: application/json');
echo json_encode($subjects); 