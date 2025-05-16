<?php
require_once 'db_connection.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // التحقق من وجود معرف نوع الزائر
    if (!isset($_GET['type_id']) || empty($_GET['type_id'])) {
        throw new Exception('معرف نوع الزائر مطلوب');
    }

    $type_id = intval($_GET['type_id']);
    $school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : null;
    $subject_id = isset($_GET['subject_id']) ? intval($_GET['subject_id']) : null;
    
    // البحث عن نوع الزائر للحصول على اسمه
    $visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$type_id]);
    
    if (!$visitor_type) {
        throw new Exception('نوع الزائر غير موجود');
    }
    
    $job_title = $visitor_type['name'];
    
    // إذا كان النوع منسق المادة أو موجه المادة، نقوم بجلب المعلمين مع مواد التنسيق/التوجيه
    if ($job_title == 'منسق المادة' || $job_title == 'موجه المادة') {
        $sql = "SELECT DISTINCT t.id, t.name, 
                GROUP_CONCAT(DISTINCT ts.subject_id) as subject_ids
                FROM teachers t
                JOIN teacher_subjects ts ON t.id = ts.teacher_id
                WHERE t.job_title = ?";
        $params = [$job_title];
        
        // إضافة شرط المدرسة إذا كان متوفراً
        if ($school_id) {
            $sql .= " AND t.school_id = ?";
            $params[] = $school_id;
        }
        
        $sql .= " GROUP BY t.id ORDER BY t.name";
        
        $visitors = query($sql, $params);
        
        // تحويل subject_ids من نص إلى مصفوفة لكل زائر
        foreach ($visitors as &$visitor) {
            if (isset($visitor['subject_ids'])) {
                $subject_ids = explode(',', $visitor['subject_ids']);
                
                // جلب أسماء المواد
                $subjects = [];
                foreach ($subject_ids as $sid) {
                    $subject_info = query_row("SELECT id, name FROM subjects WHERE id = ?", [$sid]);
                    if ($subject_info) {
                        $subjects[] = [
                            'id' => $subject_info['id'],
                            'name' => $subject_info['name']
                        ];
                    }
                }
                $visitor['subjects'] = $subjects;
            } else {
                $visitor['subjects'] = [];
            }
            unset($visitor['subject_ids']);
        }
        
    } else {
        // لباقي أنواع الزائرين، نستخدم الاستعلام الأصلي
        $sql = "SELECT DISTINCT t.id, t.name 
                FROM teachers t
                WHERE t.job_title = ?";
        $params = [$job_title];
        
        // إضافة شرط المدرسة إذا كان متوفراً
        if ($school_id) {
            $sql .= " AND t.school_id = ?";
            $params[] = $school_id;
        }
        
        $sql .= " ORDER BY t.name";
        
        $visitors = query($sql, $params);
    }
    
    // إرجاع النتائج
    echo json_encode($visitors);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} 