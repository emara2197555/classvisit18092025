<?php
/**
 * API لجلب المعلمين حسب المدرسة والمادة ونوع الزائر
 */

// تضمين ملفات قاعدة البيانات والوظائف
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// جلب المعلمين حسب المدرسة و/أو المادة
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;

$teachers = [];

if ($school_id && $subject_id) {
    // جلب المعلمين حسب المدرسة والمادة
    $teachers = query("
        SELECT DISTINCT t.* 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.job_title = 'معلم' 
        AND t.school_id = ? 
        AND ts.subject_id = ?
        ORDER BY t.name
    ", [$school_id, $subject_id]);
} elseif ($school_id) {
    // جلب كل المعلمين في المدرسة
    $teachers = query("
        SELECT * FROM teachers 
        WHERE job_title = 'معلم' 
        AND school_id = ? 
        ORDER BY name
    ", [$school_id]);
} elseif ($subject_id) {
    // جلب المعلمين حسب المادة فقط (من جميع المدارس)
    $teachers = query("
        SELECT DISTINCT t.* 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.job_title = 'معلم' 
        AND ts.subject_id = ?
        ORDER BY t.name
    ", [$subject_id]);
}

header('Content-Type: application/json');
echo json_encode($teachers); 