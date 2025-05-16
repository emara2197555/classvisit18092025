<?php
require_once 'db_connection.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // التحقق من وجود معرف المادة
    if (!isset($_GET['subject_id']) || empty($_GET['subject_id'])) {
        throw new Exception('معرف المادة مطلوب');
    }

    $subject_id = intval($_GET['subject_id']);
    $school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : null;
    
    // البحث عن منسقي المادة
    $sql = "SELECT DISTINCT t.id, t.name 
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE t.job_title = 'منسق المادة' 
            AND ts.subject_id = ?";
    
    $params = [$subject_id];
    
    // إضافة شرط المدرسة إذا كان متوفراً
    if ($school_id) {
        $sql .= " AND t.school_id = ?";
        $params[] = $school_id;
    }
    
    $sql .= " ORDER BY t.name";
    
    // تنفيذ الاستعلام
    $coordinators = query($sql, $params);
    
    // إرجاع النتائج
    echo json_encode($coordinators);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 