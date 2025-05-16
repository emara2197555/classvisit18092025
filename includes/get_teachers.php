<?php
require_once 'db_connection.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // التحقق من وجود المعرفات المطلوبة
    if (!isset($_GET['subject_id']) || empty($_GET['subject_id']) || 
        !isset($_GET['school_id']) || empty($_GET['school_id'])) {
        throw new Exception('معرف المادة والمدرسة مطلوبان');
    }

    $subject_id = intval($_GET['subject_id']);
    $school_id = intval($_GET['school_id']);
    
    // التحقق من وجود معرف الزائر لاستثنائه من القائمة (مثل: عدم إظهار المنسق كمعلم)
    $exclude_visitor = isset($_GET['exclude_visitor']) ? intval($_GET['exclude_visitor']) : null;
    
    // نوع الزائر (منسق، موجه، إلخ)
    $visitor_type = isset($_GET['visitor_type']) ? $_GET['visitor_type'] : null;

    // الاستعلام الأساسي لجلب المعلمين
    $sql = "
        SELECT DISTINCT t.id, t.name, t.job_title
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE ts.subject_id = ? 
        AND t.school_id = ?
    ";
    
    $params = [$subject_id, $school_id];
    
    // استثناء الزائر نفسه من القائمة
    if ($exclude_visitor) {
        $sql .= " AND t.id <> ?";
        $params[] = $exclude_visitor;
    }
    
    // تصفية حسب نوع الزائر
    if ($visitor_type === 'منسق المادة') {
        // منسق المادة يمكنه زيارة المعلمين فقط (ليس المنسقين أو الموجهين)
        $sql .= " AND t.job_title = 'معلم'";
    } elseif ($visitor_type === 'موجه المادة') {
        // موجه المادة يمكنه زيارة المعلمين فقط (مع استبعاده هو)
        $sql .= " AND t.job_title = 'معلم'";
    }
    
    $sql .= " ORDER BY t.name";
    
    $teachers = query($sql, $params);

    // إرجاع النتائج
    echo json_encode($teachers);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 