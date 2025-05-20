<?php
// تضمين ملفات قاعدة البيانات والوظائف
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// استلام معرف المدرسة
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;

// التحقق من وجود معرف صحيح للمدرسة
if ($school_id <= 0) {
    // إذا لم يتم تحديد مدرسة، ارجع جميع المواد
    $subjects = query("SELECT * FROM subjects ORDER BY name");
} else {
    // استعلام لجلب المواد المرتبطة بالمدرسة
    $subjects_query = "
        SELECT DISTINCT s.id, s.name
        FROM subjects s
        JOIN teacher_subjects ts ON s.id = ts.subject_id
        JOIN teachers t ON ts.teacher_id = t.id
        WHERE t.school_id = ?
        ORDER BY s.name
    ";
    $subjects = query($subjects_query, [$school_id]);
}

// إرجاع البيانات بتنسيق JSON
header('Content-Type: application/json');
echo json_encode($subjects);
?> 