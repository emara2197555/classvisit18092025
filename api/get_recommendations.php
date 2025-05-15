<?php
/**
 * API لجلب التوصيات الخاصة بمؤشر تقييم
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// التحقق من وجود معرف المؤشر
if (!isset($_GET['indicator_id']) || empty($_GET['indicator_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'معرف المؤشر مطلوب',
        'recommendations' => []
    ]);
    exit;
}

$indicator_id = (int)$_GET['indicator_id'];

try {
    // جلب التوصيات الخاصة بالمؤشر
    $recommendations = get_recommendations_by_indicator($indicator_id);
    
    // إضافة حقل الترتيب إذا لم يكن موجوداً في النتيجة
    $recommendations_with_sort = [];
    foreach ($recommendations as $rec) {
        $rec['sort_order'] = $rec['sort_order'] ?? 0;
        $recommendations_with_sort[] = $rec;
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '',
        'recommendations' => $recommendations_with_sort
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'recommendations' => []
    ]);
} 