<?php
/**
 * API لجلب المعلمين حسب المادة
 */

require_once __DIR__ . '/../includes/db_connection.php';

header('Content-Type: application/json');

// التحقق من وجود معرف المادة
if (!isset($_GET['subject_id']) || !is_numeric($_GET['subject_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف المادة غير صحيح',
        'teachers' => []
    ]);
    exit;
}

$subject_id = (int)$_GET['subject_id'];

try {
    // جلب المعلمين الذين يدرسون هذه المادة
    $teachers = query("
        SELECT DISTINCT t.id, t.name, t.email
        FROM teachers t
        INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE ts.subject_id = ?
        ORDER BY t.name
    ", [$subject_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'تم جلب المعلمين بنجاح',
        'teachers' => $teachers
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب المعلمين: ' . $e->getMessage(),
        'teachers' => []
    ]);
}
?>
