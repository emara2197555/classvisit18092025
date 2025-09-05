<?php
require_once 'includes/db_connection.php';

echo "اختبار الاستعلامات في class_performance_report.php:\n";
echo "===============================================\n\n";

// محاكاة المتغيرات
$academic_year_id = 1;
$visitor_type_id = 0;
$subject_id = 0;
$date_filter = "";
$date_params = [];

// اختبار الاستعلام الأول (مع الزيارات)
echo "اختبار الاستعلام الأول (مع الزيارات):\n";
echo "=====================================\n";

$sql_with_visits = "
    SELECT 
        t.id AS teacher_id,
        t.name AS teacher_name,
        t.job_title,
        s.id AS subject_id,
        s.name AS subject_name,
        COUNT(DISTINCT v.id) AS visits_count
    FROM 
        teachers t
    JOIN 
        teacher_subjects ts ON t.id = ts.teacher_id
    JOIN 
        subjects s ON ts.subject_id = s.id
    JOIN 
        visits v ON t.id = v.teacher_id 
        AND v.academic_year_id = ?";

$sql_with_visits .= "
    WHERE
        t.job_title IN ('معلم', 'منسق المادة')";

$sql_with_visits .= "
    GROUP BY 
        t.id, t.name, t.job_title, s.id, s.name
    ORDER BY 
        s.name, t.name";

$with_visits_params = [$academic_year_id];

echo "SQL: " . $sql_with_visits . "\n";
echo "Parameters: " . json_encode($with_visits_params) . "\n\n";

try {
    $teachers_with_visits = query($sql_with_visits, $with_visits_params);
    
    echo "النتائج:\n";
    foreach ($teachers_with_visits as $teacher) {
        echo "ID: " . $teacher['teacher_id'] . 
             " - الاسم: " . $teacher['teacher_name'] . 
             " - الوظيفة: " . ($teacher['job_title'] ?? 'غير محدد') . 
             " - المادة: " . $teacher['subject_name'] . 
             " - الزيارات: " . $teacher['visits_count'] . "\n";
    }
    
    echo "\nتفاصيل أول صف:\n";
    if (!empty($teachers_with_visits)) {
        print_r($teachers_with_visits[0]);
    }
    
} catch (Exception $e) {
    echo "خطأ في الاستعلام: " . $e->getMessage() . "\n";
}

echo "\n";

// اختبار الاستعلام الثاني (بدون زيارات)
echo "اختبار الاستعلام الثاني (بدون زيارات):\n";
echo "======================================\n";

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
        AND v.academic_year_id = ?";

$sql_without_visits .= "
    WHERE
        t.job_title IN ('معلم', 'منسق المادة')
        AND v.id IS NULL";

$sql_without_visits .= "
    GROUP BY 
        t.id, t.name, t.job_title, s.id, s.name
    ORDER BY 
        s.name, t.name";

$without_visits_params = [$academic_year_id];

echo "SQL: " . $sql_without_visits . "\n";
echo "Parameters: " . json_encode($without_visits_params) . "\n\n";

try {
    $teachers_without_visits = query($sql_without_visits, $without_visits_params);
    
    echo "النتائج (أول 5):\n";
    for ($i = 0; $i < min(5, count($teachers_without_visits)); $i++) {
        $teacher = $teachers_without_visits[$i];
        echo "ID: " . $teacher['teacher_id'] . 
             " - الاسم: " . $teacher['teacher_name'] . 
             " - الوظيفة: " . ($teacher['job_title'] ?? 'غير محدد') . 
             " - المادة: " . $teacher['subject_name'] . 
             " - الزيارات: " . $teacher['visits_count'] . "\n";
    }
    
    echo "\nتفاصيل أول صف:\n";
    if (!empty($teachers_without_visits)) {
        print_r($teachers_without_visits[0]);
    }
    
} catch (Exception $e) {
    echo "خطأ في الاستعلام: " . $e->getMessage() . "\n";
}

?>
