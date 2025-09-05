<?php
// محاكاة معاملات GET
$_GET['teacher_id'] = '45';
$_GET['visitor_person_id'] = '1';

// تضمين ملف API
require_once '../includes/db_connection.php';
require_once '../includes/auth_functions.php';

// تشغيل نفس منطق API
$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : null;
$visitor_person_id = isset($_GET['visitor_person_id']) ? intval($_GET['visitor_person_id']) : null;

echo "=== اختبار API مع teacher_id=$teacher_id و visitor_person_id=$visitor_person_id ===\n";

if (!$teacher_id || !$visitor_person_id) {
    echo "معرف المعلم ومعرف الزائر مطلوبان\n";
    exit;
}

try {
    // استعلام للحصول على عدد الزيارات لهذا المعلم
    $visits_count_query = "SELECT COUNT(*) as total FROM visits WHERE teacher_id = ?";
    $visits_count_result = query($visits_count_query, [$teacher_id]);
    $visits_count = $visits_count_result[0]['total'] ?? 0;
    
    echo "عدد الزيارات للمعلم: $visits_count\n";
    
    if ($visits_count > 0) {
        // جلب آخر زيارة لهذا الزائر لهذا المعلم
        $last_visit_query = "
            SELECT v.*, ve.average_score, ve.recommendation_notes, ve.appreciation_notes
            FROM visits v
            LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
            WHERE v.teacher_id = ? AND v.visitor_person_id = ?
            ORDER BY v.visit_date DESC, v.id DESC
            LIMIT 1
        ";
        
        $last_visit_result = query($last_visit_query, [$teacher_id, $visitor_person_id]);
        
        if (!empty($last_visit_result)) {
            $last_visit = $last_visit_result[0];
            echo "آخر زيارة للزائر الحالي:\n";
            echo "  التاريخ: " . $last_visit['visit_date'] . "\n";
            echo "  المتوسط: " . $last_visit['average_score'] . "\n";
            echo "  التوصيات: " . $last_visit['recommendation_notes'] . "\n";
        } else {
            echo "لا توجد زيارات سابقة لهذا الزائر لهذا المعلم\n";
        }
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
