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
$subject_id = $_GET['subject_id'] ?? null;

if (!$subject_id) {
    die('معرف المادة مطلوب');
}

echo "<h2>تصحيح حسابات لوحة المنسق</h2>";
echo "<p>المدرسة: {$school_id}, المادة: {$subject_id}</p>";

// فحص المعلمين وزياراتهم
echo "<h3>معلمي المادة:</h3>";
$teachers = query("
    SELECT t.id, t.name 
    FROM teachers t
    INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id
    WHERE ts.subject_id = ? AND t.school_id = ?
", [$subject_id, $school_id]);

foreach ($teachers as $teacher) {
    echo "<h4>المعلم: {$teacher['name']} (ID: {$teacher['id']})</h4>";
    
    // عدد الزيارات
    $visits = query("
        SELECT v.id, v.visit_date, ve.score
        FROM visits v
        LEFT JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE v.teacher_id = ? AND v.subject_id = ?
        ORDER BY v.visit_date DESC
    ", [$teacher['id'], $subject_id]);
    
    echo "<p>عدد الزيارات: " . count($visits) . "</p>";
    
    if (!empty($visits)) {
        $scores = [];
        echo "<ul>";
        foreach ($visits as $visit) {
            $score = $visit['score'] ?? 'لا يوجد تقييم';
            if ($visit['score']) {
                $scores[] = $visit['score'];
            }
            echo "<li>الزيارة {$visit['id']} - {$visit['visit_date']} - النتيجة: {$score}</li>";
        }
        echo "</ul>";
        
        if (!empty($scores)) {
            $avg = array_sum($scores) / count($scores);
            $percentage = round(($avg / 4) * 100, 1);
            echo "<p>متوسط النتائج: {$avg} / 4 = {$percentage}%</p>";
        }
    }
    
    echo "<hr>";
}

// فحص الاستعلام الأصلي المُحدث
echo "<h3>نتائج الاستعلام المُحدث:</h3>";
$teachers_performance = query("
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

echo "<table border='1'>";
echo "<tr><th>الاسم</th><th>عدد الزيارات</th><th>متوسط النتيجة</th><th>النسبة المئوية</th></tr>";
foreach ($teachers_performance as $teacher) {
    $percentage = $teacher['avg_score'] ? round(($teacher['avg_score'] / 4) * 100, 1) : 0;
    echo "<tr>";
    echo "<td>{$teacher['name']}</td>";
    echo "<td>{$teacher['visits_count']}</td>";
    echo "<td>{$teacher['avg_score']}</td>";
    echo "<td>{$percentage}%</td>";
    echo "</tr>";
}
echo "</table>";
?>
