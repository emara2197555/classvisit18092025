<?php
/**
 * API لجلب منسق المادة حسب المادة والمدرسة
 */

require_once '../includes/db_connection.php';
require_once '../includes/auth_functions.php';

session_start();
if (!is_logged_in()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$subject_id = isset($_GET['subject_id']) ? (int)$_GET['subject_id'] : 0;
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;

if ($subject_id <= 0 || $school_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف المادة والمدرسة مطلوبان',
        'coordinators' => []
    ]);
    exit;
}

try {
    // جلب منسق المادة للمادة والمدرسة المحددة
    $sql = "SELECT t.id, t.name 
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE t.job_title = 'منسق المادة' 
            AND ts.subject_id = ? 
            AND t.school_id = ?
            ORDER BY t.name";
    
    $coordinators = query($sql, [$subject_id, $school_id]);

    echo json_encode([
        'success' => true,
        'coordinators' => $coordinators,
        'message' => count($coordinators) > 0 ? 'تم جلب منسقي المادة بنجاح' : 'لا يوجد منسقي مادة متاحين'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في جلب البيانات: ' . $e->getMessage(),
        'coordinators' => []
    ]);
}
?>
