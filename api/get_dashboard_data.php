<?php
/**
 * واجهة برمجية لتحديث بيانات لوحة التحكم
 * 
 * تستخدم للحصول على بيانات محدثة للوحة التحكم دون الحاجة لإعادة تحميل الصفحة
 */

// تضمين ملفات الاتصال بقاعدة البيانات والوظائف
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// تعيين نوع المحتوى للاستجابة
header('Content-Type: application/json; charset=utf-8');

// الحصول على معرف العام الدراسي الحالي
$current_academic_year = get_active_academic_year();
$academic_year_id = $current_academic_year['id'] ?? 0;

// بيانات لوحة التحكم
$dashboard_data = [];

try {
    // عدد الزيارات
    $sql_visits_count = "SELECT COUNT(*) as count FROM visits WHERE academic_year_id = ?";
    $visits_count_result = query_row($sql_visits_count, [$academic_year_id]);
    $dashboard_data['visitsCount'] = $visits_count_result['count'] ?? 0;
    
    // عدد المعلمين الذين تم تقييمهم
    $sql_evaluated_teachers = "SELECT COUNT(DISTINCT teacher_id) as count FROM visits WHERE academic_year_id = ?";
    $evaluated_teachers_result = query_row($sql_evaluated_teachers, [$academic_year_id]);
    $dashboard_data['teachersCount'] = $evaluated_teachers_result['count'] ?? 0;
    
    // متوسط الأداء العام
    $sql_avg_performance = "
        SELECT (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 as avg_score
        FROM visit_evaluations ve
        JOIN visits v ON ve.visit_id = v.id
        WHERE v.academic_year_id = ? AND ve.score IS NOT NULL
    ";
    $avg_performance_result = query_row($sql_avg_performance, [$academic_year_id]);
    $dashboard_data['avgPerformance'] = number_format($avg_performance_result['avg_score'] ?? 0, 1);
    
    // عدد التوصيات غير المنفذة
    $dashboard_data['pendingRecommendations'] = 0;
    // التحقق من وجود جدول التوصيات أولاً
    $db = get_db_connection();
    $check_table = $db->query("SHOW TABLES LIKE 'visit_recommendations'");
    if ($check_table->rowCount() > 0) {
        $sql_pending_recommendations = "
            SELECT COUNT(*) as count
            FROM visit_recommendations vr
            JOIN visits v ON vr.visit_id = v.id
            WHERE v.academic_year_id = ? AND vr.is_implemented = 0
        ";
        $pending_recommendations_result = query_row($sql_pending_recommendations, [$academic_year_id]);
        $dashboard_data['pendingRecommendations'] = $pending_recommendations_result['count'] ?? 0;
    }
    
    // عدد التنبيهات
    $alerts_count = 0;
    
    // عدد التنبيهات المتعلقة بالتوصيات
    if ($check_table->rowCount() > 0) {
        $sql_recommendations_alerts = "
            SELECT COUNT(*) as count FROM visit_recommendations vr
            JOIN visits v ON vr.visit_id = v.id
            WHERE v.academic_year_id = ? AND vr.is_implemented = 0
            AND DATEDIFF(NOW(), vr.created_at) > 14
        ";
        $recommendations_alerts_result = query_row($sql_recommendations_alerts, [$academic_year_id]);
        $alerts_count += $recommendations_alerts_result['count'] ?? 0;
    }
    
    // عدد التنبيهات المتعلقة بالمعلمين الذين لم تتم زيارتهم
    $sql_teachers_alerts = "
        SELECT COUNT(*) as count FROM teachers t
        LEFT JOIN (
            SELECT DISTINCT teacher_id
            FROM visits
            WHERE academic_year_id = ?
        ) v ON t.id = v.teacher_id
        WHERE v.teacher_id IS NULL AND t.is_active = 1
    ";
    $teachers_alerts_result = query_row($sql_teachers_alerts, [$academic_year_id]);
    $alerts_count += $teachers_alerts_result['count'] ?? 0;
    
    $dashboard_data['alertsCount'] = $alerts_count;
    
    // جلب أفضل المعلمين
    $sql_best_teachers = "
        SELECT 
            t.id,
            t.name as teacher_name,
            (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 as avg_score
        FROM 
            visit_evaluations ve
        JOIN 
            visits v ON ve.visit_id = v.id
        JOIN 
            teachers t ON v.teacher_id = t.id
        WHERE 
            v.academic_year_id = ? AND ve.score IS NOT NULL
        GROUP BY 
            t.id, t.name
        ORDER BY 
            avg_score DESC
        LIMIT 2
    ";
    $best_teachers = query($sql_best_teachers, [$academic_year_id]);
    $dashboard_data['bestTeachers'] = [];
    
    foreach ($best_teachers as $teacher) {
        // النسبة المئوية محسوبة بالفعل في الاستعلام
        $dashboard_data['bestTeachers'][] = [
            'name' => $teacher['teacher_name'],
            'score' => number_format($teacher['avg_score'], 0)
        ];
    }
    
    // حالة النجاح
    $dashboard_data['success'] = true;
    
} catch (PDOException $e) {
    // حالة الفشل
    $dashboard_data['success'] = false;
    $dashboard_data['error'] = 'خطأ في الاتصال بقاعدة البيانات';
}

// إرجاع البيانات بصيغة JSON
echo json_encode($dashboard_data, JSON_UNESCAPED_UNICODE); 