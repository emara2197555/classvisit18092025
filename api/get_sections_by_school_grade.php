<?php
/**
 * API لجلب الشعب حسب المدرسة والصف
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

$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0;
$grade_id = isset($_GET['grade_id']) ? (int)$_GET['grade_id'] : 0;

if ($school_id <= 0 || $grade_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف المدرسة والصف مطلوبان',
        'sections' => []
    ]);
    exit;
}

try {
    // جلب الشعب المتاحة للمدرسة والصف
    $sql = "SELECT id, name 
            FROM sections 
            WHERE school_id = ? AND grade_id = ? 
            ORDER BY name";
    
    $sections = query($sql, [$school_id, $grade_id]);

    echo json_encode([
        'success' => true,
        'sections' => $sections,
        'message' => ''
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في جلب البيانات: ' . $e->getMessage(),
        'sections' => []
    ]);
}
?>
