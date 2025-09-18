<?php
/**
 * ملف إحصائيات لوحة القيادة
 * 
 * يستخدم لاستخراج الإحصائيات التي تعرض في الصفحة الرئيسية
 * محدث لاستخدام القوانين الموحدة من visit_rules.php
 * 
 * @version 2.0 - محدث للقوانين الموحدة
 */

// تضمين القوانين الموحدة
require_once __DIR__ . '/../visit_rules.php';

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

// عدد المعلمين الذين تم تقييمهم (المعلمين فقط حسب الوظيفة)
$sql_evaluated_teachers = "
    SELECT COUNT(DISTINCT v.teacher_id) as count 
    FROM visits v
    JOIN teachers t ON v.teacher_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'معلم'" . $date_condition;
$evaluated_teachers_result = query_row($sql_evaluated_teachers, [$academic_year_id]);
$evaluated_teachers_count = $evaluated_teachers_result['count'] ?? 0;

// 🎯 متوسط الأداء العام للمعلمين - باستخدام الدالة الموحدة الوحيدة
$avg_performance = calculateUnifiedOverallPerformance($academic_year_id, $date_condition);

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

// عدد المعلمين المسجلين (حسب الوظيفة)
$sql_total_teachers = "SELECT COUNT(*) as count FROM teachers WHERE job_title = 'معلم'";
$total_teachers_result = query_row($sql_total_teachers);
$total_teachers_count = $total_teachers_result['count'] ?? 0;

/**
 * أداء المعلمين
 */

// أفضل المعلمين أداءً - باستخدام الثوابت الموحدة
$sql_best_teachers = "
    SELECT 
        t.id,
        t.name as teacher_name,
        s.name as subject_name,
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 as avg_score
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
    LIMIT " . TOP_TEACHERS_LIMIT . "
";
$best_teachers = query($sql_best_teachers, [$academic_year_id]);

// أقل المعلمين أداءً - باستخدام الثوابت الموحدة
$sql_worst_teachers = "
    SELECT 
        t.id,
        t.name as teacher_name,
        s.name as subject_name,
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 as avg_score
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
    LIMIT " . NEEDS_IMPROVEMENT_LIMIT . "
";
$worst_teachers = query($sql_worst_teachers, [$academic_year_id]);

/**
 * أداء المدارس / الصفوف
 */

// أفضل مدرسة من حيث نتائج التقييم - باستخدام الثوابت الموحدة
$sql_best_school = "
    SELECT 
        sch.name as school_name, 
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 as avg_score
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

// الصف الأعلى أداءً - باستخدام الثوابت الموحدة
$sql_best_grade = "
    SELECT 
        g.name as grade_name, 
        sec.name as section_name,
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 as avg_score
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

// الصف الأقل أداءً - باستخدام الثوابت الموحدة
$sql_worst_grade = "
    SELECT 
        g.name as grade_name, 
        sec.name as section_name,
        (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 as avg_score
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
        (SELECT COUNT(*) FROM teacher_subjects ts JOIN teachers t ON ts.teacher_id = t.id WHERE ts.subject_id = s.id AND t.job_title = 'معلم') as teachers_count,
        (SELECT COUNT(*) FROM visits WHERE subject_id = s.id AND academic_year_id = ? " . $date_condition . ") as visits_count,
        (SELECT COUNT(DISTINCT v.teacher_id) FROM visits v JOIN teachers t ON v.teacher_id = t.id WHERE v.subject_id = s.id AND v.academic_year_id = ? AND t.job_title = 'معلم' " . $date_condition . ") as visited_teachers_count,
        (
            SELECT COALESCE((AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100, 0)
            FROM visits v
            JOIN visit_evaluations ve ON v.id = ve.visit_id
            JOIN teachers t ON v.teacher_id = t.id
            WHERE v.subject_id = s.id AND v.academic_year_id = ? AND t.job_title = 'معلم' " . $date_condition . "
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

/**
 * إحصائيات على مستوى الوظائف
 */

// عدد المعلمين الذين تم تقييمهم
$sql_teachers_evaluated = "
    SELECT COUNT(DISTINCT v.teacher_id) as count
    FROM visits v
    JOIN teachers t ON v.teacher_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'معلم'" . $date_condition . "
";
$teachers_evaluated_result = query_row($sql_teachers_evaluated, [$academic_year_id]);
$teachers_evaluated_count = $teachers_evaluated_result['count'] ?? 0;

// عدد المنسقين الذين تم تقييمهم
$sql_coordinators_evaluated = "
    SELECT COUNT(DISTINCT v.teacher_id) as count
    FROM visits v
    JOIN teachers t ON v.teacher_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'منسق المادة'" . $date_condition . "
";
$coordinators_evaluated_result = query_row($sql_coordinators_evaluated, [$academic_year_id]);
$coordinators_evaluated_count = $coordinators_evaluated_result['count'] ?? 0;

// عدد الموجهين الذين قاموا بالزيارة
$sql_supervisors_visiting = "
    SELECT COUNT(DISTINCT v.visitor_person_id) as count
    FROM visits v
    JOIN teachers t ON v.visitor_person_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'موجه المادة'" . $date_condition . "
";
$supervisors_visiting_result = query_row($sql_supervisors_visiting, [$academic_year_id]);
$supervisors_visiting_count = $supervisors_visiting_result['count'] ?? 0;

// 🎯 متوسط أداء المعلمين - باستخدام الدالة الموحدة الوحيدة
$teachers_avg_performance = calculateUnifiedJobPerformance($academic_year_id, 'معلم', $date_condition);

// 🎯 متوسط أداء المنسقين - باستخدام الدالة الموحدة الوحيدة  
$coordinators_avg_performance = calculateUnifiedJobPerformance($academic_year_id, 'منسق المادة', $date_condition);

/**
 * إحصائيات على مستوى المواد
 */

// المواد الأكثر زيارة (أفضل 3)
$sql_most_visited_subjects = "
    SELECT s.name as subject_name, COUNT(v.id) as visits_count
    FROM visits v
    JOIN subjects s ON v.subject_id = s.id
    WHERE v.academic_year_id = ?" . $date_condition . "
    GROUP BY s.id, s.name
    ORDER BY visits_count DESC
    LIMIT 3
";
$most_visited_subjects = query($sql_most_visited_subjects, [$academic_year_id]);

// المواد التي تحتاج اهتمام (أقل زيارة)
$sql_least_visited_subjects = "
    SELECT s.name as subject_name, COUNT(v.id) as visits_count
    FROM subjects s
    LEFT JOIN visits v ON s.id = v.subject_id AND v.academic_year_id = ?" . $date_condition . "
    GROUP BY s.id, s.name
    HAVING visits_count <= 2
    ORDER BY visits_count ASC
    LIMIT 3
";
$least_visited_subjects = query($sql_least_visited_subjects, [$academic_year_id]);

// أفضل المواد أداءً - باستخدام الثوابت الموحدة
$sql_best_subjects_performance = "
    SELECT s.name as subject_name, (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 as avg_score
    FROM visit_evaluations ve
    JOIN visits v ON ve.visit_id = v.id
    JOIN subjects s ON v.subject_id = s.id
    WHERE v.academic_year_id = ? AND ve.score IS NOT NULL" . $date_condition . "
    GROUP BY s.id, s.name
    HAVING COUNT(v.id) >= " . MIN_VISITS_FOR_REPORTS . "
    ORDER BY avg_score DESC
    LIMIT " . TOP_SUBJECTS_LIMIT . "
";
$best_subjects_performance = query($sql_best_subjects_performance, [$academic_year_id]);

/**
 * إحصائيات الجودة والتميز
 */

// نسبة المعلمين المتميزين - باستخدام الثوابت الموحدة
$sql_excellent_teachers = "
    SELECT COUNT(DISTINCT v.teacher_id) as count
    FROM visit_evaluations ve
    JOIN visits v ON ve.visit_id = v.id
    WHERE v.academic_year_id = ? AND ve.score IS NOT NULL" . $date_condition . "
    GROUP BY v.teacher_id
    HAVING (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 >= " . EXCELLENT_THRESHOLD . "
";
$excellent_teachers_result = query($sql_excellent_teachers, [$academic_year_id]);
$excellent_teachers_count = count($excellent_teachers_result);

// نسبة المعلمين المحتاجين تطوير - باستخدام الثوابت الموحدة
$sql_needs_improvement_teachers = "
    SELECT COUNT(DISTINCT v.teacher_id) as count
    FROM visit_evaluations ve
    JOIN visits v ON ve.visit_id = v.id
    WHERE v.academic_year_id = ? AND ve.score IS NOT NULL" . $date_condition . "
    GROUP BY v.teacher_id
    HAVING (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 < " . GOOD_THRESHOLD . "
";
$needs_improvement_result = query($sql_needs_improvement_teachers, [$academic_year_id]);
$needs_improvement_count = count($needs_improvement_result);

// أكثر الزوار نشاطاً
$sql_most_active_visitors = "
    SELECT 
        vt.name as visitor_type,
        t.name as visitor_name,
        COUNT(v.id) as visits_count
    FROM visits v
    JOIN visitor_types vt ON v.visitor_type_id = vt.id
    JOIN teachers t ON v.visitor_person_id = t.id
    WHERE v.academic_year_id = ?" . $date_condition . "
    GROUP BY vt.name, t.name
    ORDER BY visits_count DESC
    LIMIT 3
";
$most_active_visitors = query($sql_most_active_visitors, [$academic_year_id]);

// إحصائيات إضافية مفيدة
$total_coordinators_count = query_row("SELECT COUNT(*) as count FROM teachers WHERE job_title = 'منسق المادة'")['count'] ?? 0;
$total_supervisors_count = query_row("SELECT COUNT(*) as count FROM teachers WHERE job_title = 'موجه المادة'")['count'] ?? 0;

// نسبة التغطية
$teachers_coverage_percentage = $total_teachers_count > 0 ? round(($teachers_evaluated_count / $total_teachers_count) * 100, 1) : 0;
$coordinators_coverage_percentage = $total_coordinators_count > 0 ? round(($coordinators_evaluated_count / $total_coordinators_count) * 100, 1) : 0;

/**
 * إحصائيات المدير والنائب الأكاديمي
 */

// عدد الزيارات التي قام بها المدير
$sql_principal_visits = "
    SELECT COUNT(v.id) as visits_count
    FROM visits v
    JOIN teachers t ON v.visitor_person_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'مدير'" . $date_condition . "
";
$principal_visits_result = query_row($sql_principal_visits, [$academic_year_id]);
$principal_visits_count = $principal_visits_result['visits_count'] ?? 0;

// عدد الزيارات التي قام بها النائب الأكاديمي
$sql_academic_deputy_visits = "
    SELECT COUNT(v.id) as visits_count
    FROM visits v
    JOIN teachers t ON v.visitor_person_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'النائب الأكاديمي'" . $date_condition . "
";
$academic_deputy_visits_result = query_row($sql_academic_deputy_visits, [$academic_year_id]);
$academic_deputy_visits_count = $academic_deputy_visits_result['visits_count'] ?? 0;

// زيارات المدير حسب المادة
$sql_principal_visits_by_subject = "
    SELECT s.name as subject_name, COUNT(v.id) as visits_count
    FROM visits v
    JOIN teachers t ON v.visitor_person_id = t.id
    JOIN subjects s ON v.subject_id = s.id
    WHERE v.academic_year_id = ? AND t.job_title = 'مدير'" . $date_condition . "
    GROUP BY s.id, s.name
    ORDER BY visits_count DESC
";
$principal_visits_by_subject = query($sql_principal_visits_by_subject, [$academic_year_id]);

// زيارات النائب الأكاديمي حسب المادة
$sql_academic_deputy_visits_by_subject = "
    SELECT s.name as subject_name, COUNT(v.id) as visits_count
    FROM visits v
    JOIN teachers t ON v.visitor_person_id = t.id
    JOIN subjects s ON v.subject_id = s.id
    WHERE v.academic_year_id = ? AND t.job_title = 'النائب الأكاديمي'" . $date_condition . "
    GROUP BY s.id, s.name
    ORDER BY visits_count DESC
";
$academic_deputy_visits_by_subject = query($sql_academic_deputy_visits_by_subject, [$academic_year_id]);

// المعلمين الذين زارهم المدير
$sql_teachers_visited_by_principal = "
    SELECT t_visited.name as teacher_name, t_visited.job_title, s.name as subject_name, COUNT(v.id) as visits_count
    FROM visits v
    JOIN teachers t_visitor ON v.visitor_person_id = t_visitor.id
    JOIN teachers t_visited ON v.teacher_id = t_visited.id
    JOIN subjects s ON v.subject_id = s.id
    WHERE v.academic_year_id = ? AND t_visitor.job_title = 'مدير'" . $date_condition . "
    GROUP BY t_visited.id, t_visited.name, t_visited.job_title, s.id, s.name
    ORDER BY visits_count DESC
";
$teachers_visited_by_principal = query($sql_teachers_visited_by_principal, [$academic_year_id]);

// المعلمين الذين زارهم النائب الأكاديمي
$sql_teachers_visited_by_deputy = "
    SELECT t_visited.name as teacher_name, t_visited.job_title, s.name as subject_name, COUNT(v.id) as visits_count
    FROM visits v
    JOIN teachers t_visitor ON v.visitor_person_id = t_visitor.id
    JOIN teachers t_visited ON v.teacher_id = t_visited.id
    JOIN subjects s ON v.subject_id = s.id
    WHERE v.academic_year_id = ? AND t_visitor.job_title = 'النائب الأكاديمي'" . $date_condition . "
    GROUP BY t_visited.id, t_visited.name, t_visited.job_title, s.id, s.name
    ORDER BY visits_count DESC
";
$teachers_visited_by_deputy = query($sql_teachers_visited_by_deputy, [$academic_year_id]);

// متوسط أداء المعلمين الذين زارهم المدير - باستخدام الثوابت الموحدة
$sql_principal_visited_teachers_avg = "
    SELECT (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 as avg_score
    FROM visit_evaluations ve
    JOIN visits v ON ve.visit_id = v.id
    JOIN teachers t_visitor ON v.visitor_person_id = t_visitor.id
    WHERE v.academic_year_id = ? AND t_visitor.job_title = 'مدير' AND ve.score IS NOT NULL" . $date_condition . "
";
$principal_visited_teachers_avg_result = query_row($sql_principal_visited_teachers_avg, [$academic_year_id]);
$principal_visited_teachers_avg = number_format($principal_visited_teachers_avg_result['avg_score'] ?? 0, 1);

// متوسط أداء المعلمين الذين زارهم النائب الأكاديمي - باستخدام الثوابت الموحدة
$sql_deputy_visited_teachers_avg = "
    SELECT (AVG(ve.score) / " . MAX_INDICATOR_SCORE . ") * 100 as avg_score
    FROM visit_evaluations ve
    JOIN visits v ON ve.visit_id = v.id
    JOIN teachers t_visitor ON v.visitor_person_id = t_visitor.id
    WHERE v.academic_year_id = ? AND t_visitor.job_title = 'النائب الأكاديمي' AND ve.score IS NOT NULL" . $date_condition . "
";
$deputy_visited_teachers_avg_result = query_row($sql_deputy_visited_teachers_avg, [$academic_year_id]);
$deputy_visited_teachers_avg = number_format($deputy_visited_teachers_avg_result['avg_score'] ?? 0, 1);

?> 
