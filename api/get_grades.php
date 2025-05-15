<?php
/**
 * API لجلب الصفوف حسب المرحلة التعليمية
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';

// التحقق من وجود معرف المرحلة التعليمية
if (!isset($_GET['level_id']) || empty($_GET['level_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'معرف المرحلة التعليمية مطلوب',
        'grades' => []
    ]);
    exit;
}

$level_id = (int)$_GET['level_id'];

try {
    // جلب الصفوف المرتبطة بالمرحلة التعليمية
    $sql = "SELECT id, name FROM grades WHERE level_id = ? ORDER BY id";
    $grades = query($sql, [$level_id]);

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '',
        'grades' => $grades
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب البيانات',
        'grades' => []
    ]);
} 