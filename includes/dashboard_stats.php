<?php
/**
 * ملف إحصائيات لوحة القيادة
 * 
 * يستخدم لاستخراج الإحصائيات التي تعرض في الصفحة الرئيسية
 */

// تضمين مكون فلترة العام الأكاديمي والفصل الدراسي
require_once 'includes/academic_filter.php';

// الحصول على معرف العام الدراسي المحدد من جلسة المستخدم
$academic_year_id = $selected_year_id;

/**
 * إحصائيات عامة
 */

// عدد الزيارات في العام الدراسي المحدد
$sql_visits_count = "SELECT COUNT(*) as count FROM visits WHERE academic_year_id = ?" . $date_condition;
$visits_count_result = query_row($sql_visits_count, [$academic_year_id]);
$visits_count = $visits_count_result['count'] ?? 0;

// عدد المعلمين الذين تم تقييمهم
$sql_evaluated_teachers = "SELECT COUNT(DISTINCT teacher_id) as count FROM visits WHERE academic_year_id = ?" . $date_condition;
$evaluated_teachers_result = query_row($sql_evaluated_teachers, [$academic_year_id]);
$evaluated_teachers_count = $evaluated_teachers_result['count'] ?? 0;

// متوسط الأداء العام للمعلمين
$sql_avg_performance = "
    SELECT AVG(score) * 25 as avg_score
    FROM visit_evaluations ve
    JOIN visits v ON ve.visit_id = v.id
    WHERE v.academic_year_id = ?" . $date_condition . "
";
$avg_performance_result = query_row($sql_avg_performance, [$academic_year_id]);
$avg_performance = number_format($avg_performance_result['avg_score'] ?? 0, 1);

// عدد التوصيات غير المنفذة
// التحقق من وجود جدول التوصيات أولاً
$pending_recommendations_count = 0;
try {
    // التحقق من وجود جدول visit_recommendations
    global $pdo;
    $check_table = $pdo->query("SHOW TABLES LIKE 'visit_recommendations'");
    if ($check_table->rowCount() > 0) {
        $sql_pending_recommendations = "
            SELECT COUNT(*) as count
            FROM visit_recommendations vr
            JOIN visits v ON vr.visit_id = v.id
            WHERE v.academic_year_id = ? AND vr.is_implemented = 0" . $date_condition . "
        ";
        $pending_recommendations_result = query_row($sql_pending_recommendations, [$academic_year_id]);
        $pending_recommendations_count = $pending_recommendations_result['count'] ?? 0;
    }
} catch (PDOException $e) {
    // تجاهل الخطأ واستمر في بقية الإحصائيات
    $pending_recommendations_count = 0;
}

/**
 * إحصائيات سريعة
 */

// عدد المدارس المسجلة
$sql_schools_count = "SELECT COUNT(*) as count FROM schools";
$schools_count_result = query_row($sql_schools_count);
$schools_count = $schools_count_result['count'] ?? 0;

// عدد الزائرين
$sql_visitors_count = "
    SELECT COUNT(DISTINCT visitor_type_id) as count
    FROM visits
    WHERE academic_year_id = ?" . $date_condition . "
";
$visitors_count_result = query_row($sql_visitors_count, [$academic_year_id]);
$visitors_count = $visitors_count_result['count'] ?? 0;

// عدد المواد الدراسية
$sql_subjects_count = "SELECT COUNT(*) as count FROM subjects";
$subjects_count_result = query_row($sql_subjects_count);
$subjects_count = $subjects_count_result['count'] ?? 0;

// عدد الشعب الدراسية
$sql_sections_count = "SELECT COUNT(*) as count FROM sections";
$sections_count_result = query_row($sql_sections_count);
$sections_count = $sections_count_result['count'] ?? 0;

// عدد المعلمين المسجلة
$sql_total_teachers = "SELECT COUNT(*) as count FROM teachers";
$total_teachers_result = query_row($sql_total_teachers);
$total_teachers_count = $total_teachers_result['count'] ?? 0;

/**
 * أداء المعلمين
 */

// أفضل المعلمين أداءً
$sql_best_teachers = "
    SELECT 
        t.id,
        t.name as teacher_name,
        s.name as subject_name,
        AVG(ve.score) * 25 as avg_score
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        teachers t ON v.teacher_id = t.id
    JOIN 
        subjects s ON v.subject_id = s.id
    WHERE 
        v.academic_year_id = ?" . $date_condition . "
    GROUP BY 
        t.id, t.name, s.name
    ORDER BY 
        avg_score DESC
    LIMIT 5
";
$best_teachers = query($sql_best_teachers, [$academic_year_id]);

// أقل المعلمين أداءً
$sql_worst_teachers = "
    SELECT 
        t.id,
        t.name as teacher_name,
        s.name as subject_name,
        AVG(ve.score) * 25 as avg_score
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        teachers t ON v.teacher_id = t.id
    JOIN 
        subjects s ON v.subject_id = s.id
    WHERE 
        v.academic_year_id = ?" . $date_condition . "
    GROUP BY 
        t.id, t.name, s.name
    ORDER BY 
        avg_score ASC
    LIMIT 5
";
$worst_teachers = query($sql_worst_teachers, [$academic_year_id]);

/**
 * أداء المدارس / الصفوف
 */

// أفضل مدرسة من حيث نتائج التقييم
$sql_best_school = "
    SELECT 
        sch.name as school_name, 
        AVG(ve.score) * 25 as avg_score
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        schools sch ON v.school_id = sch.id
    WHERE 
        v.academic_year_id = ?" . $date_condition . "
    GROUP BY 
        sch.name
    ORDER BY 
        avg_score DESC
    LIMIT 1
";
$best_school_result = query_row($sql_best_school, [$academic_year_id]);
$best_school = $best_school_result['school_name'] ?? '';
$best_school_score = number_format($best_school_result['avg_score'] ?? 0, 0);

// الصف الأعلى أداءً
$sql_best_grade = "
    SELECT 
        g.name as grade_name, 
        sec.name as section_name,
        AVG(ve.score) * 25 as avg_score
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        grades g ON v.grade_id = g.id
    JOIN 
        sections sec ON v.section_id = sec.id
    WHERE 
        v.academic_year_id = ?" . $date_condition . "
    GROUP BY 
        g.name, sec.name
    ORDER BY 
        avg_score DESC
    LIMIT 1
";
$best_grade_result = query_row($sql_best_grade, [$academic_year_id]);
$best_grade = ($best_grade_result['grade_name'] ?? '') . ' - شعبة ' . ($best_grade_result['section_name'] ?? '');
$best_grade_score = number_format($best_grade_result['avg_score'] ?? 0, 0);

// الصف الأقل أداءً
$sql_worst_grade = "
    SELECT 
        g.name as grade_name, 
        sec.name as section_name,
        AVG(ve.score) * 25 as avg_score
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        grades g ON v.grade_id = g.id
    JOIN 
        sections sec ON v.section_id = sec.id
    WHERE 
        v.academic_year_id = ?" . $date_condition . "
    GROUP BY 
        g.name, sec.name
    ORDER BY 
        avg_score ASC
    LIMIT 1
";
$worst_grade_result = query_row($sql_worst_grade, [$academic_year_id]);
$worst_grade = ($worst_grade_result['grade_name'] ?? '') . ' - شعبة ' . ($worst_grade_result['section_name'] ?? '');
$worst_grade_score = number_format($worst_grade_result['avg_score'] ?? 0, 0);

/**
 * إحصائيات المواد الدراسية
 */
// استعلام للحصول على المعلومات التفصيلية لكل مادة
$sql_subjects_stats = "
    SELECT 
        s.id as subject_id,
        s.name as subject_name,
        (SELECT COUNT(*) FROM teacher_subjects WHERE subject_id = s.id) as teachers_count,
        (SELECT COUNT(*) FROM visits WHERE subject_id = s.id AND academic_year_id = ? " . $date_condition . ") as visits_count,
        (SELECT COUNT(DISTINCT teacher_id) FROM visits WHERE subject_id = s.id AND academic_year_id = ? " . $date_condition . ") as visited_teachers_count,
        (
            SELECT COALESCE(AVG(ve.score) * 25, 0)
            FROM visits v
            JOIN visit_evaluations ve ON v.id = ve.visit_id
            WHERE v.subject_id = s.id AND v.academic_year_id = ? " . $date_condition . "
        ) as avg_performance
    FROM 
        subjects s
    ORDER BY 
        s.name ASC
";

$subjects_stats = query($sql_subjects_stats, [$academic_year_id, $academic_year_id, $academic_year_id]);

// حساب الإجماليات
$total_subject_teachers = 0;
$total_subject_visits = 0;
$total_visited_teachers = 0;
$total_avg_performance = 0;
$subjects_count_with_data = 0;

foreach ($subjects_stats as $subject) {
    $total_subject_teachers += $subject['teachers_count'];
    $total_subject_visits += $subject['visits_count'];
    $total_visited_teachers += $subject['visited_teachers_count'];
    
    if ($subject['avg_performance'] > 0) {
        $total_avg_performance += $subject['avg_performance'];
        $subjects_count_with_data++;
    }
}

// حساب المتوسط العام لجميع المواد
$overall_avg_performance = ($subjects_count_with_data > 0) ? 
    number_format($total_avg_performance / $subjects_count_with_data, 1) : 0;

/**
 * الاحتياجات التدريبية
 */

// عدد المعلمين الذين لديهم مؤشرات ضعيفة
$sql_teachers_with_weak_indicators = "
    SELECT COUNT(DISTINCT v.teacher_id) as count
    FROM visit_evaluations ve
    JOIN visits v ON ve.visit_id = v.id
    WHERE v.academic_year_id = ? AND ve.score <= 2 " . $date_condition . "
    GROUP BY v.teacher_id
";
$weak_teachers_result = query_row($sql_teachers_with_weak_indicators, [$academic_year_id]);
$weak_teachers_count = $weak_teachers_result['count'] ?? 0;

// المجالات الأكثر ضعفًا
$sql_weak_domains = "
    SELECT 
        ed.name as domain_name, 
        AVG(ve.score) as avg_score
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        evaluation_indicators ei ON ve.indicator_id = ei.id
    JOIN 
        evaluation_domains ed ON ei.domain_id = ed.id
    WHERE 
        v.academic_year_id = ?" . $date_condition . "
    GROUP BY 
        ed.name
    ORDER BY 
        avg_score ASC
    LIMIT 2
";
$weak_domains = query($sql_weak_domains, [$academic_year_id]);

/**
 * الزيارات المجدولة القادمة
 */

// الزيارات المقبلة
$sql_upcoming_visits = "
    SELECT 
        v.id,
        v.visit_date,
        t.name as teacher_name,
        s.name as subject_name,
        visitor.name as visitor_name,
        sch.name as school_name
    FROM 
        visits v
    JOIN 
        teachers t ON v.teacher_id = t.id
    JOIN 
        subjects s ON v.subject_id = s.id
    JOIN 
        schools sch ON v.school_id = sch.id
    LEFT JOIN 
        teachers visitor ON v.visitor_person_id = visitor.id
    WHERE 
        v.visit_date >= CURDATE() AND v.academic_year_id = ?
    ORDER BY 
        v.visit_date ASC
    LIMIT 5
";
$upcoming_visits = query($sql_upcoming_visits, [$academic_year_id]); 