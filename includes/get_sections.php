<?php
require_once 'db_connection.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // التحقق من وجود المعرفات المطلوبة
    if (!isset($_GET['grade_id']) || empty($_GET['grade_id']) || 
        !isset($_GET['school_id']) || empty($_GET['school_id'])) {
        throw new Exception('معرف الصف والمدرسة مطلوبان');
    }

    $grade_id = intval($_GET['grade_id']);
    $school_id = intval($_GET['school_id']);

    // استعلام لجلب الشعب حسب الصف والمدرسة
    $sql = "
        SELECT id, name 
        FROM sections 
        WHERE grade_id = ? 
        AND school_id = ?
        ORDER BY name
    ";
    
    $sections = query($sql, [$grade_id, $school_id]);

    // إرجاع النتائج
    echo json_encode($sections);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 