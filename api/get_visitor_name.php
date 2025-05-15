<?php
/**
 * API لجلب أسماء الزائرين حسب نوع الزائر
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';

// التحقق من وجود المعرف المطلوب
if (!isset($_GET['visitor_type_id']) || empty($_GET['visitor_type_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'معرف نوع الزائر مطلوب',
        'visitors' => []
    ]);
    exit;
}

$visitor_type_id = (int)$_GET['visitor_type_id'];

try {
    // جلب نوع الزائر أولاً للتحقق من الوظيفة
    $visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$visitor_type_id]);
    
    if (!$visitor_type) {
        throw new Exception("نوع الزائر غير موجود");
    }
    
    // تحديد الوظيفة المطلوبة بناءً على نوع الزائر
    $job_title = '';
    switch ($visitor_type['name']) {
        case 'مدير المدرسة':
            $job_title = 'مدير';
            break;
        case 'نائب المدير للشؤون الأكاديمية':
            $job_title = 'النائب الأكاديمي';
            break;
        case 'منسق المادة':
            $job_title = 'منسق المادة';
            break;
        case 'موجه المادة':
            $job_title = 'موجه المادة';
            break;
        default:
            $job_title = $visitor_type['name'];
    }
    
    // جلب المعلمين بالوظيفة المحددة
    $visitors = query("SELECT id, name FROM teachers WHERE job_title = ? ORDER BY name", [$job_title]);
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '',
        'visitors' => $visitors
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'visitors' => []
    ]);
} 