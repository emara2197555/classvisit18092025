<?php
/**
 * API لجلب معلومات الزيارات السابقة للمعلم
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';

// التحقق من وجود المعرفات المطلوبة
if (!isset($_GET['teacher_id']) || empty($_GET['teacher_id']) ||
    !isset($_GET['visitor_person_id']) || empty($_GET['visitor_person_id'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'معرف المعلم ومعرف الزائر مطلوبان',
        'data' => null
    ]);
    exit;
}

$teacher_id = (int)$_GET['teacher_id'];
$visitor_person_id = (int)$_GET['visitor_person_id'];

try {
    // جلب عدد الزيارات السابقة
    $visits_count = query_row("
        SELECT COUNT(*) as count
        FROM visits
        WHERE teacher_id = ? AND visitor_person_id = ?
    ", [$teacher_id, $visitor_person_id]);
    
    // جلب بيانات آخر زيارة
    $last_visit = query_row("
        SELECT v.*, g.name as grade_name, s.name as section_name, 
        v.total_score, (v.total_score / (COUNT(DISTINCT ve.indicator_id) * 4)) as average_score
        FROM visits v 
        LEFT JOIN grades g ON v.grade_id = g.id 
        LEFT JOIN sections s ON v.section_id = s.id
        LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE v.teacher_id = ? AND v.visitor_person_id = ?
        GROUP BY v.id
        ORDER BY v.visit_date DESC 
        LIMIT 1
    ", [$teacher_id, $visitor_person_id]);
    
    // جلب التوصيات من آخر زيارة
    $last_recommendations = [];
    if ($last_visit) {
        $last_recommendations = query("
            SELECT r.text
            FROM visit_evaluations ve 
            JOIN recommendations r ON ve.recommendation_id = r.id
            WHERE ve.visit_id = ?
            GROUP BY r.text
        ", [$last_visit['id']]);
    }
    
    $data = [
        'visits_count' => $visits_count ? $visits_count['count'] : 0,
        'last_visit' => $last_visit ? [
            'date' => $last_visit['visit_date'],
            'grade' => $last_visit['grade_name'],
            'section' => $last_visit['section_name'],
            'notes' => $last_visit['general_notes'],
            'average_score' => floatval($last_visit['average_score'])
        ] : null,
        'recommendations' => $last_recommendations
    ];

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '',
        'data' => $data
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ]);
} 