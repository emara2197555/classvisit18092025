<?php
/**
 * API لجلب الشعب حسب الصف
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';

// التحقق من وجود معرف الصف
if (!isset($_GET['grade_id']) || empty($_GET['grade_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'معرف الصف مطلوب',
        'sections' => []
    ]);
    exit;
}

$grade_id = (int)$_GET['grade_id'];

try {
    // جلب الشعب المرتبطة بالصف
    $sql = "SELECT id, name FROM sections WHERE grade_id = ? ORDER BY name";
    $sections = query($sql, [$grade_id]);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '',
        'sections' => $sections
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب البيانات',
        'sections' => []
    ]);
} 