<?php
/**
 * API لجلب معلومات الزيارات السابقة للمعلم
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once '../includes/db_connection.php';

// إضافة سجل أخطاء للتصحيح
function debug_log($message) {
    error_log(date('Y-m-d H:i:s') . ' - Get Previous Visits API: ' . $message);
}

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

debug_log("استدعاء API لجلب معلومات الزيارات السابقة للمعلم: {$teacher_id} والزائر: {$visitor_person_id}");

try {
    // جلب عدد الزيارات السابقة
    $visits_count_query = "
        SELECT COUNT(*) as count
        FROM visits
        WHERE teacher_id = ?
    ";
    $visits_count = query_row($visits_count_query, [$teacher_id]);
    debug_log("عدد الزيارات: " . ($visits_count ? $visits_count['count'] : 0) . " للمعلم {$teacher_id}");
    
    // للتصحيح: عرض جميع الزيارات للمعلم
    $all_visits_debug = query("
        SELECT id, teacher_id, visitor_person_id, visit_date, total_score 
        FROM visits 
        WHERE teacher_id = ?
        ORDER BY visit_date DESC
    ", [$teacher_id]);
    
    debug_log("جميع الزيارات للمعلم {$teacher_id}: " . json_encode($all_visits_debug));
    
    // جلب متوسط أداء المعلم لكل الزائرين - تصحيح الاستعلام
    $average_performance_all_query = "
        SELECT 
            AVG(v.total_score / (
                NULLIF((SELECT COUNT(DISTINCT ve2.indicator_id) FROM visit_evaluations ve2 WHERE ve2.visit_id = v.id), 0) * 4
            )) as avg_score,
            COUNT(DISTINCT v.id) as visits_used_in_calculation
        FROM visits v 
        WHERE v.teacher_id = ?
        AND EXISTS (SELECT 1 FROM visit_evaluations ve WHERE ve.visit_id = v.id)
    ";
    
    $average_performance_all = query_row($average_performance_all_query, [$teacher_id]);
    debug_log("متوسط الأداء لكل الزائرين (تصحيح): " . 
        ($average_performance_all ? 
        "القيمة: " . $average_performance_all['avg_score'] . 
        ", عدد الزيارات في الحساب: " . ($average_performance_all['visits_used_in_calculation'] ?? 'غير معروف') : 
        'غير متوفر'));
    
    // استعلام بديل لمتوسط الأداء لكل الزائرين
    $alt_average_query = "
        SELECT 
            SUM(v.total_score) as total_sum,
            SUM(
                (SELECT COUNT(DISTINCT ve2.indicator_id) FROM visit_evaluations ve2 WHERE ve2.visit_id = v.id) * 4
            ) as total_possible,
            COUNT(DISTINCT v.id) as visits_count
        FROM visits v 
        WHERE v.teacher_id = ?
        AND EXISTS (SELECT 1 FROM visit_evaluations ve WHERE ve.visit_id = v.id)
    ";
    
    $alt_average = query_row($alt_average_query, [$teacher_id]);
    $alt_avg_score = null;
    if ($alt_average && $alt_average['total_possible'] > 0) {
        $alt_avg_score = $alt_average['total_sum'] / $alt_average['total_possible'];
        debug_log("متوسط الأداء البديل لكل الزائرين: مجموع الدرجات {$alt_average['total_sum']} / الدرجة الكاملة الممكنة {$alt_average['total_possible']} = {$alt_avg_score}");
    }
    
    // جلب متوسط أداء المعلم للزائر الحالي - تصحيح الاستعلام
    $average_performance_current_visitor_query = "
        SELECT 
            AVG(v.total_score / (
                NULLIF((SELECT COUNT(DISTINCT ve2.indicator_id) FROM visit_evaluations ve2 WHERE ve2.visit_id = v.id), 0) * 4
            )) as avg_score,
            COUNT(DISTINCT v.id) as visits_used_in_calculation
        FROM visits v 
        WHERE v.teacher_id = ?
        AND v.visitor_person_id = ?
        AND EXISTS (SELECT 1 FROM visit_evaluations ve WHERE ve.visit_id = v.id)
    ";
    
    $average_performance_current_visitor = query_row($average_performance_current_visitor_query, [$teacher_id, $visitor_person_id]);
    debug_log("متوسط الأداء للزائر الحالي (تصحيح): " . 
        ($average_performance_current_visitor ? 
        "القيمة: " . $average_performance_current_visitor['avg_score'] . 
        ", عدد الزيارات في الحساب: " . ($average_performance_current_visitor['visits_used_in_calculation'] ?? 'غير معروف') : 
        'غير متوفر'));
    
    // استعلام بديل لمتوسط الأداء للزائر الحالي
    $alt_current_average_query = "
        SELECT 
            SUM(v.total_score) as total_sum,
            SUM(
                (SELECT COUNT(DISTINCT ve2.indicator_id) FROM visit_evaluations ve2 WHERE ve2.visit_id = v.id) * 4
            ) as total_possible,
            COUNT(DISTINCT v.id) as visits_count
        FROM visits v 
        WHERE v.teacher_id = ?
        AND v.visitor_person_id = ?
        AND EXISTS (SELECT 1 FROM visit_evaluations ve WHERE ve.visit_id = v.id)
    ";
    
    $alt_current_average = query_row($alt_current_average_query, [$teacher_id, $visitor_person_id]);
    $alt_current_avg_score = null;
    if ($alt_current_average && $alt_current_average['total_possible'] > 0) {
        $alt_current_avg_score = $alt_current_average['total_sum'] / $alt_current_average['total_possible'];
        debug_log("متوسط الأداء البديل للزائر الحالي: مجموع الدرجات {$alt_current_average['total_sum']} / الدرجة الكاملة الممكنة {$alt_current_average['total_possible']} = {$alt_current_avg_score}");
    }
    
    // جلب بيانات آخر زيارة للزائر الحالي
    $last_visit_current_visitor_query = "
        SELECT 
            v.id, v.visit_date, v.general_notes, v.recommendation_notes, v.appreciation_notes, v.total_score,
            g.name as grade_name, s.name as section_name,
            (v.total_score / (
                SELECT COUNT(DISTINCT ve2.indicator_id) * 4 
                FROM visit_evaluations ve2 
                WHERE ve2.visit_id = v.id
            )) as average_score,
            (SELECT COUNT(DISTINCT ve3.indicator_id) FROM visit_evaluations ve3 WHERE ve3.visit_id = v.id) as total_indicators
        FROM visits v 
        LEFT JOIN grades g ON v.grade_id = g.id 
        LEFT JOIN sections s ON v.section_id = s.id
        WHERE v.teacher_id = ?
        AND v.visitor_person_id = ?
        ORDER BY v.visit_date DESC 
        LIMIT 1
    ";
    
    $last_visit_current_visitor = query_row($last_visit_current_visitor_query, [$teacher_id, $visitor_person_id]);
    debug_log("آخر زيارة للزائر الحالي: " . ($last_visit_current_visitor ? 
        "ID: " . $last_visit_current_visitor['id'] . 
        ", تاريخ: " . $last_visit_current_visitor['visit_date'] . 
        ", الدرجة الكلية: " . $last_visit_current_visitor['total_score'] . 
        ", عدد المؤشرات: " . ($last_visit_current_visitor['total_indicators'] ?? 'غير معروف') . 
        ", متوسط الدرجة: " . ($last_visit_current_visitor['average_score'] ?? 'غير محسوب') : 
        'غير متوفرة'));
    
    // جلب بيانات آخر زيارة عامة (أي زائر)
    $last_visit_any_visitor_query = "
        SELECT 
            v.id, v.visit_date, v.general_notes, v.recommendation_notes, v.appreciation_notes, v.total_score,
            g.name as grade_name, s.name as section_name, v.visitor_person_id,
            vt.name as visitor_type_name,
            (v.total_score / (
                SELECT COUNT(DISTINCT ve2.indicator_id) * 4 
                FROM visit_evaluations ve2 
                WHERE ve2.visit_id = v.id
            )) as average_score,
            (SELECT COUNT(DISTINCT ve3.indicator_id) FROM visit_evaluations ve3 WHERE ve3.visit_id = v.id) as total_indicators
        FROM visits v 
        LEFT JOIN grades g ON v.grade_id = g.id 
        LEFT JOIN sections s ON v.section_id = s.id
        LEFT JOIN visitor_types vt ON v.visitor_type_id = vt.id
        WHERE v.teacher_id = ?
        ORDER BY v.visit_date DESC 
        LIMIT 1
    ";
    
    $last_visit_any_visitor = query_row($last_visit_any_visitor_query, [$teacher_id]);
    debug_log("آخر زيارة لأي زائر: " . ($last_visit_any_visitor ? 
        "ID: " . $last_visit_any_visitor['id'] . 
        ", تاريخ: " . $last_visit_any_visitor['visit_date'] . 
        ", معرف الزائر: " . $last_visit_any_visitor['visitor_person_id'] . 
        ", نوع الزائر: " . $last_visit_any_visitor['visitor_type_name'] . 
        ", الدرجة الكلية: " . $last_visit_any_visitor['total_score'] . 
        ", عدد المؤشرات: " . ($last_visit_any_visitor['total_indicators'] ?? 'غير معروف') . 
        ", متوسط الدرجة: " . ($last_visit_any_visitor['average_score'] ?? 'غير محسوب') : 
        'غير متوفرة'));
    
    // جلب التوصيات من آخر زيارة للزائر الحالي
    $last_recommendations = [];
    if ($last_visit_current_visitor) {
        $recommendations_query = "
            SELECT r.text
            FROM visit_evaluations ve 
            JOIN recommendations r ON ve.recommendation_id = r.id
            WHERE ve.visit_id = ?
            GROUP BY r.text
        ";
        $last_recommendations = query($recommendations_query, [$last_visit_current_visitor['id']]);
        debug_log("عدد التوصيات الموجودة: " . count($last_recommendations));
    }
    
    // تجهيز البيانات للعميل
    $data = [
        'visits_count' => $visits_count ? (int)$visits_count['count'] : 0,
        'average_performance_all' => $average_performance_all && isset($average_performance_all['avg_score']) ? 
            floatval($average_performance_all['avg_score']) : null,
        'average_performance_current_visitor' => $average_performance_current_visitor && isset($average_performance_current_visitor['avg_score']) ? 
            floatval($average_performance_current_visitor['avg_score']) : null,
        'last_visit_current_visitor' => $last_visit_current_visitor ? [
            'id' => (int)$last_visit_current_visitor['id'],
            'date' => $last_visit_current_visitor['visit_date'],
            'grade' => $last_visit_current_visitor['grade_name'],
            'section' => $last_visit_current_visitor['section_name'],
            'notes' => $last_visit_current_visitor['general_notes'],
            'average_score' => isset($last_visit_current_visitor['average_score']) ? 
                floatval($last_visit_current_visitor['average_score']) : null,
            'recommendation_notes' => $last_visit_current_visitor['recommendation_notes'],
            'appreciation_notes' => $last_visit_current_visitor['appreciation_notes']
        ] : null,
        'last_visit_any_visitor' => $last_visit_any_visitor ? [
            'id' => (int)$last_visit_any_visitor['id'],
            'date' => $last_visit_any_visitor['visit_date'],
            'grade' => $last_visit_any_visitor['grade_name'],
            'section' => $last_visit_any_visitor['section_name'],
            'visitor_person_id' => $last_visit_any_visitor['visitor_person_id'],
            'visitor_type' => $last_visit_any_visitor['visitor_type_name'],
            'notes' => $last_visit_any_visitor['general_notes'],
            'average_score' => isset($last_visit_any_visitor['average_score']) ? 
                floatval($last_visit_any_visitor['average_score']) : null,
            'recommendation_notes' => $last_visit_any_visitor['recommendation_notes'],
            'appreciation_notes' => $last_visit_any_visitor['appreciation_notes']
        ] : null,
        'recommendations' => $last_recommendations
    ];
    
    // استخدام الحساب البديل إذا كان أكثر دقة
    if ($alt_avg_score !== null && ($data['average_performance_all'] === null || $data['average_performance_all'] == 0)) {
        $data['average_performance_all'] = $alt_avg_score;
    }
    
    if ($alt_current_avg_score !== null && ($data['average_performance_current_visitor'] === null || $data['average_performance_current_visitor'] == 0)) {
        $data['average_performance_current_visitor'] = $alt_current_avg_score;
    }
    
    // تسجيل البيانات المرسلة للتصحيح
    debug_log("البيانات المرسلة للعميل: " . json_encode([
        'visits_count' => $data['visits_count'],
        'average_performance_all' => $data['average_performance_all'],
        'average_performance_current_visitor' => $data['average_performance_current_visitor'],
        'last_visit_current_average' => $data['last_visit_current_visitor'] ? $data['last_visit_current_visitor']['average_score'] : null,
        'last_visit_any_average' => $data['last_visit_any_visitor'] ? $data['last_visit_any_visitor']['average_score'] : null,
    ], JSON_UNESCAPED_UNICODE));

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => '',
        'data' => $data
    ]);
} catch (Exception $e) {
    debug_log("خطأ: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => null
    ]);
} 