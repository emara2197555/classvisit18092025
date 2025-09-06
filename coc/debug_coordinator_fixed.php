<?php
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// التحقق من الجلسة
if (!is_logged_in()) {
    header('Location: index.php');
    exit();
}

$user = get_current_user();
if ($user['role'] !== 'coordinator') {
    die('غير مخول للوصول');
}

$school_id = $user['school_id'];
$subject_id = $_GET['subject_id'] ?? 1;

echo "<h2>مقارنة حسابات المنسق والمدير</h2>";
echo "<p>المدرسة: {$school_id}, المادة: {$subject_id}</p>";

// حساب المنسق الجديد (من 0 إلى 3)
echo "<h3>حساب المنسق الجديد (النطاق 0-3):</h3>";
$teachers_performance_coordinator = query("
    SELECT t.id, t.name, 
           COUNT(DISTINCT v.id) as visits_count,
           AVG(visit_scores.avg_score) as avg_score
    FROM teachers t
    INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id
    LEFT JOIN visits v ON t.id = v.teacher_id AND v.subject_id = ?
    LEFT JOIN (
        SELECT ve.visit_id, AVG(ve.score) as avg_score
        FROM visit_evaluations ve
        GROUP BY ve.visit_id
    ) visit_scores ON v.id = visit_scores.visit_id
    WHERE ts.subject_id = ? AND t.school_id = ?
    GROUP BY t.id, t.name
    ORDER BY avg_score DESC
", [$subject_id, $subject_id, $school_id]);

echo "<table border='1' style='margin: 10px 0;'>";
echo "<tr><th>المعلم</th><th>عدد الزيارات</th><th>متوسط النتيجة</th><th>النسبة المئوية (/3)</th></tr>";
foreach ($teachers_performance_coordinator as $teacher) {
    $percentage = $teacher['avg_score'] ? round(($teacher['avg_score'] / 3) * 100, 1) : 0;
    echo "<tr>";
    echo "<td>{$teacher['name']}</td>";
    echo "<td>{$teacher['visits_count']}</td>";
    echo "<td>" . round($teacher['avg_score'], 2) . "</td>";
    echo "<td>{$percentage}%</td>";
    echo "</tr>";
}
echo "</table>";

// حساب المدير (للمقارنة)
echo "<h3>حساب المدير للمقارنة (النطاق 0-3):</h3>";
$teachers_performance_admin = query("
    SELECT 
        t.id,
        t.name as teacher_name,
        COUNT(DISTINCT v.id) as visits_count,
        AVG(ve.score) as avg_score
    FROM 
        visit_evaluations ve
    JOIN 
        visits v ON ve.visit_id = v.id
    JOIN 
        teachers t ON v.teacher_id = t.id
    WHERE 
        v.subject_id = ? AND v.school_id = ?
    GROUP BY 
        t.id, t.name
    ORDER BY 
        avg_score DESC
", [$subject_id, $school_id]);

echo "<table border='1' style='margin: 10px 0;'>";
echo "<tr><th>المعلم</th><th>عدد الزيارات</th><th>متوسط النتيجة</th><th>النسبة المئوية (*100/3)</th></tr>";
foreach ($teachers_performance_admin as $teacher) {
    $percentage = round($teacher['avg_score'] * (100/3), 1);
    echo "<tr>";
    echo "<td>{$teacher['teacher_name']}</td>";
    echo "<td>{$teacher['visits_count']}</td>";
    echo "<td>" . round($teacher['avg_score'], 2) . "</td>";
    echo "<td>{$percentage}%</td>";
    echo "</tr>";
}
echo "</table>";

// فحص نتيجة معينة
echo "<h3>فحص تفصيلي لمعلم معين:</h3>";
$teacher_detail = query("
    SELECT v.id as visit_id, v.visit_date, ve.score, ve.indicator_id
    FROM visits v
    LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
    WHERE v.teacher_id = (SELECT id FROM teachers WHERE school_id = ? LIMIT 1)
    AND v.subject_id = ?
    ORDER BY v.visit_date DESC, ve.indicator_id
    LIMIT 20
", [$school_id, $subject_id]);

if (!empty($teacher_detail)) {
    echo "<table border='1' style='margin: 10px 0;'>";
    echo "<tr><th>رقم الزيارة</th><th>التاريخ</th><th>رقم المؤشر</th><th>النتيجة</th></tr>";
    foreach ($teacher_detail as $detail) {
        echo "<tr>";
        echo "<td>{$detail['visit_id']}</td>";
        echo "<td>{$detail['visit_date']}</td>";
        echo "<td>{$detail['indicator_id']}</td>";
        echo "<td>{$detail['score']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>
