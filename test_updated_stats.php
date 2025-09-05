<?php
require_once 'includes/db_connection.php';

echo "اختبار الإحصائيات المحدثة:\n";
echo "===========================\n\n";

// محاكاة المتغيرات
$academic_year_id = 1;
$date_condition = "";

// اختبار الإحصائيات المصححة
echo "الإحصائيات الرئيسية (بعد التصحيح):\n";
echo "=====================================\n";

// المعلمين فقط الذين تم تقييمهم
$teachers_evaluated = query_row("
    SELECT COUNT(DISTINCT v.teacher_id) as count 
    FROM visits v
    JOIN teachers t ON v.teacher_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'معلم'
", [$academic_year_id])['count'];

echo "عدد المعلمين الذين تم تقييمهم: " . $teachers_evaluated . "\n";

// متوسط أداء المعلمين فقط
$teachers_avg = query_row("
    SELECT (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 as avg_score
    FROM visit_evaluations ve
    JOIN visits v ON ve.visit_id = v.id
    JOIN teachers t ON v.teacher_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'معلم' AND ve.score IS NOT NULL
", [$academic_year_id])['avg_score'];

echo "متوسط أداء المعلمين فقط: " . number_format($teachers_avg, 1) . "%\n\n";

// اختبار إحصائيات المدير والنائب
echo "إحصائيات القيادة المدرسية:\n";
echo "============================\n";

// زيارات المدير
$principal_visits = query_row("
    SELECT COUNT(v.id) as visits_count
    FROM visits v
    JOIN teachers t ON v.visitor_person_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'مدير'
", [$academic_year_id])['visits_count'];

echo "زيارات المدير: " . $principal_visits . "\n";

// زيارات النائب الأكاديمي
$deputy_visits = query_row("
    SELECT COUNT(v.id) as visits_count
    FROM visits v
    JOIN teachers t ON v.visitor_person_id = t.id
    WHERE v.academic_year_id = ? AND t.job_title = 'النائب الأكاديمي'
", [$academic_year_id])['visits_count'];

echo "زيارات النائب الأكاديمي: " . $deputy_visits . "\n\n";

// تفاصيل زيارات المدير
if ($principal_visits > 0) {
    echo "تفاصيل زيارات المدير:\n";
    echo "----------------------\n";
    
    $principal_details = query("
        SELECT 
            t_visited.name as teacher_name, 
            t_visited.job_title, 
            s.name as subject_name, 
            v.visit_date
        FROM visits v
        JOIN teachers t_visitor ON v.visitor_person_id = t_visitor.id
        JOIN teachers t_visited ON v.teacher_id = t_visited.id
        JOIN subjects s ON v.subject_id = s.id
        WHERE v.academic_year_id = ? AND t_visitor.job_title = 'مدير'
        ORDER BY v.visit_date DESC
    ", [$academic_year_id]);
    
    foreach ($principal_details as $visit) {
        echo "- زار: " . $visit['teacher_name'] . " (" . $visit['job_title'] . ") في مادة " . $visit['subject_name'] . " بتاريخ " . $visit['visit_date'] . "\n";
    }
    echo "\n";
}

// تفاصيل زيارات النائب الأكاديمي
if ($deputy_visits > 0) {
    echo "تفاصيل زيارات النائب الأكاديمي:\n";
    echo "------------------------------\n";
    
    $deputy_details = query("
        SELECT 
            t_visited.name as teacher_name, 
            t_visited.job_title, 
            s.name as subject_name, 
            v.visit_date
        FROM visits v
        JOIN teachers t_visitor ON v.visitor_person_id = t_visitor.id
        JOIN teachers t_visited ON v.teacher_id = t_visited.id
        JOIN subjects s ON v.subject_id = s.id
        WHERE v.academic_year_id = ? AND t_visitor.job_title = 'النائب الأكاديمي'
        ORDER BY v.visit_date DESC
    ", [$academic_year_id]);
    
    foreach ($deputy_details as $visit) {
        echo "- زار: " . $visit['teacher_name'] . " (" . $visit['job_title'] . ") في مادة " . $visit['subject_name'] . " بتاريخ " . $visit['visit_date'] . "\n";
    }
}

?>
