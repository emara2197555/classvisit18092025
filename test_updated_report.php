<?php
require_once 'includes/db_connection.php';

echo "اختبار تقرير الأداء بعد التعديل:\n";
echo "=================================\n\n";

// محاكاة المتغيرات المطلوبة
$academic_year_id = 1;
$subject_id = 0;
$visitor_type_id = 0;
$date_filter = "";
$date_params = [];

// اختبار استعلام المعلمين والمنسقين مع الزيارات
$sql_with_visits = "
    SELECT 
        t.id AS teacher_id,
        t.name AS teacher_name,
        t.job_title,
        s.id AS subject_id,
        s.name AS subject_name,
        COUNT(v.id) AS visits_count,
        AVG(ve.score) AS avg_score
    FROM 
        teachers t
    JOIN 
        teacher_subjects ts ON t.id = ts.teacher_id
    JOIN 
        subjects s ON ts.subject_id = s.id
    JOIN 
        visits v ON t.id = v.teacher_id
    LEFT JOIN 
        visit_evaluations ve ON v.id = ve.visit_id
    WHERE 
        v.academic_year_id = ?
        AND t.job_title IN ('معلم', 'منسق المادة')
    GROUP BY 
        t.id, t.name, s.id, s.name
    ORDER BY 
        s.name, t.name";

$params = [$academic_year_id];
$results_with_visits = query($sql_with_visits, $params);

echo "المعلمين والمنسقين مع الزيارات:\n";
echo "-------------------------------\n";
foreach ($results_with_visits as $row) {
    $percentage = $row['avg_score'] ? round(($row['avg_score'] / 3) * 100, 1) : 0;
    echo "- " . $row['teacher_name'] . " (" . $row['job_title'] . ") - " . $row['subject_name'] . 
         " - زيارات: " . $row['visits_count'] . 
         " - متوسط النقاط: " . round($row['avg_score'], 2) . 
         " - النسبة: " . $percentage . "%\n";
}

echo "\n";

// اختبار استعلام المعلمين والمنسقين بدون زيارات
$sql_without_visits = "
    SELECT 
        t.id AS teacher_id,
        t.name AS teacher_name,
        t.job_title,
        s.id AS subject_id,
        s.name AS subject_name,
        0 AS visits_count
    FROM 
        teachers t
    JOIN 
        teacher_subjects ts ON t.id = ts.teacher_id
    JOIN 
        subjects s ON ts.subject_id = s.id
    LEFT JOIN 
        visits v ON t.id = v.teacher_id 
        AND v.academic_year_id = ?
    WHERE
        t.job_title IN ('معلم', 'منسق المادة')
        AND v.id IS NULL
    GROUP BY 
        t.id, t.name, s.id, s.name
    ORDER BY 
        s.name, t.name";

$results_without_visits = query($sql_without_visits, $params);

echo "المعلمين والمنسقين بدون زيارات:\n";
echo "-------------------------------\n";
foreach ($results_without_visits as $row) {
    echo "- " . $row['teacher_name'] . " (" . $row['job_title'] . ") - " . $row['subject_name'] . " - لا توجد زيارات\n";
}

echo "\n";
echo "إجمالي العدد:\n";
echo "- مع زيارات: " . count($results_with_visits) . "\n";
echo "- بدون زيارات: " . count($results_without_visits) . "\n";
echo "- الإجمالي: " . (count($results_with_visits) + count($results_without_visits)) . "\n";

?>
