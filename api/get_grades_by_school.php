<?php
/**
 * API لجلب الصفوف حسب المدرسة
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

if ($school_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'معرف المدرسة مطلوب',
        'grades' => []
    ]);
    exit;
}

try {
    // جلب الصفوف المتاحة للمدرسة
    $sql = "SELECT DISTINCT g.id, g.name 
            FROM grades g 
            INNER JOIN sections s ON g.id = s.grade_id 
            WHERE s.school_id = ? 
            ORDER BY g.id";
    
    $grades = query($sql, [$school_id]);

    echo json_encode([
        'success' => true,
        'grades' => $grades,
        'message' => ''
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في جلب البيانات: ' . $e->getMessage(),
        'grades' => []
    ]);
}
?>
