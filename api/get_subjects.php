<?php
/**
 * API لجلب المواد الدراسية
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';

// التحقق من وجود معرف المدرسة (اختياري)
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : null;

try {
    if ($school_id) {
        // جلب المواد المرتبطة بالمدرسة
        $sql = "SELECT id, name FROM subjects WHERE school_id = ? OR school_id IS NULL ORDER BY name";
        $subjects = query($sql, [$school_id]);
    } else {
        // جلب جميع المواد
        $sql = "SELECT id, name FROM subjects ORDER BY name";
        $subjects = query($sql);
    }

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '',
        'subjects' => $subjects
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء جلب البيانات',
        'subjects' => []
    ]);
} 