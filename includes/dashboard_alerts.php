<?php
/**
 * ملف تنبيهات لوحة القيادة
 * 
 * يستخدم لإنشاء وعرض التنبيهات المهمة للمستخدم
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once __DIR__ . '/db_connection.php';

// الحصول على معرف العام الدراسي الحالي
if (!isset($current_academic_year)) {
    $current_academic_year = get_active_academic_year();
    $academic_year_id = $current_academic_year['id'] ?? 0;
}

// مصفوفة التنبيهات
$alerts = [];

/**
 * تنبيه بالتوصيات التي لم تتم متابعتها
 */
// التحقق من وجود جدول التوصيات أولاً
try {
    // التحقق من وجود جدول visit_recommendations
    global $pdo;
    $check_table = $pdo->query("SHOW TABLES LIKE 'visit_recommendations'");
    if ($check_table->rowCount() > 0) {
        $sql_unimplemented_recommendations = "
            SELECT COUNT(DISTINCT vr.id) as count
            FROM visit_recommendations vr
            JOIN visits v ON vr.visit_id = v.id
            WHERE v.academic_year_id = ? AND vr.is_implemented = 0
            AND DATEDIFF(NOW(), vr.created_at) > 14
        ";
        $unimplemented_count_result = query_row($sql_unimplemented_recommendations, [$academic_year_id]);
        $unimplemented_count = $unimplemented_count_result['count'] ?? 0;
        
        if ($unimplemented_count > 0) {
            $alerts[] = [
                'icon' => 'exclamation-triangle',
                'color' => 'warning',
                'message' => "لديك {$unimplemented_count} توصيات لم تُتابَع بعد",
                'link' => 'training_needs.php'
            ];
        }
    }
} catch (PDOException $e) {
    // تجاهل الخطأ واستمر في بقية التنبيهات
}

/**
 * تنبيه بوجود تحديث في نموذج التقييم
 */
$sql_recent_indicator_updates = "
    SELECT COUNT(*) as count
    FROM evaluation_indicators
    WHERE DATEDIFF(NOW(), updated_at) < 30
";
$indicator_updates_result = query_row($sql_recent_indicator_updates);
$indicator_updates_count = $indicator_updates_result['count'] ?? 0;

if ($indicator_updates_count > 0) {
    $alerts[] = [
        'icon' => 'bullhorn',
        'color' => 'info',
        'message' => "تم تحديث نموذج التقييم بإضافة أو تعديل {$indicator_updates_count} مؤشرات",
        'link' => 'evaluation_form.php'
    ];
}

/**
 * تنبيه بوجود تحديث في بيانات الصفوف للعام القادم
 */
$next_year = $academic_year_id + 1;
$sql_next_year_update = "
    SELECT name FROM academic_years WHERE id = ?
";
$next_year_result = query_row($sql_next_year_update, [$next_year]);

if ($next_year_result) {
    $next_year_name = $next_year_result['name'] ?? '';
    
    $alerts[] = [
        'icon' => 'calendar-check',
        'color' => 'primary',
        'message' => "تم تحديث بيانات الصفوف للعام الأكاديمي {$next_year_name}",
        'link' => 'sections_management.php'
    ];
}

/**
 * تنبيه بوجود معلمين لم يتم زيارتهم هذا العام
 */
$sql_unvisited_teachers = "
    SELECT COUNT(*) as count
    FROM teachers t
    LEFT JOIN (
        SELECT DISTINCT teacher_id
        FROM visits
        WHERE academic_year_id = ?
    ) v ON t.id = v.teacher_id
    WHERE v.teacher_id IS NULL
";
$unvisited_teachers_result = query_row($sql_unvisited_teachers, [$academic_year_id]);
$unvisited_teachers_count = $unvisited_teachers_result['count'] ?? 0;

if ($unvisited_teachers_count > 0) {
    $alerts[] = [
        'icon' => 'user-clock',
        'color' => 'danger',
        'message' => "يوجد {$unvisited_teachers_count} معلم لم تتم زيارتهم هذا العام",
        'link' => 'teachers_management.php'
    ];
}

/**
 * تنبيه بتقييمات منخفضة تحتاج لمتابعة
 */
$sql_low_evaluations = "
    SELECT COUNT(DISTINCT v.id) as count
    FROM visits v
    JOIN (
        SELECT visit_id, AVG(score) as avg_score
        FROM visit_evaluations
        GROUP BY visit_id
        HAVING avg_score < 2
    ) ve ON v.id = ve.visit_id
    WHERE v.academic_year_id = ?
";
$low_evaluations_result = query_row($sql_low_evaluations, [$academic_year_id]);
$low_evaluations_count = $low_evaluations_result['count'] ?? 0;

if ($low_evaluations_count > 0) {
    $alerts[] = [
        'icon' => 'exclamation-circle',
        'color' => 'danger',
        'message' => "يوجد {$low_evaluations_count} زيارات بتقييم منخفض تحتاج إلى متابعة",
        'link' => 'visits.php'
    ];
}

// دالة لعرض التنبيهات
function render_alerts($alerts) {
    $html = '';
    
    foreach ($alerts as $alert) {
        $icon = $alert['icon'] ?? 'info-circle';
        $color = $alert['color'] ?? 'info';
        $message = $alert['message'] ?? '';
        $link = $alert['link'] ?? '#';
        
        $html .= '<div class="alert-item mb-3">';
        $html .= '<a href="' . $link . '" class="alert alert-' . $color . ' d-flex align-items-center p-3 mb-0 text-decoration-none">';
        $html .= '<div class="alert-icon me-3">';
        $html .= '<i class="fas fa-' . $icon . ' fa-lg"></i>';
        $html .= '</div>';
        $html .= '<div class="alert-content">' . $message . '</div>';
        $html .= '</a>';
        $html .= '</div>';
    }
    
    return $html;
} 