<?php
require_once 'includes/db_connection.php';

// البحث عن بيانات المعلم عبدالعزيز
echo "=== البحث عن المعلم عبدالعزيز ===\n";
$teacher = query_row("SELECT * FROM teachers WHERE name LIKE '%عبدالعزيز معوض%'");
if ($teacher) {
    print_r($teacher);
    $teacher_id = $teacher['id'];
    
    echo "\n=== التحقق من user_id للمعلم ===\n";
    $user = query_row("SELECT * FROM users WHERE id = ?", [$teacher['user_id']]);
    if ($user) {
        echo "User found: " . $user['full_name'] . " (ID: " . $user['id'] . ")\n";
        echo "Teacher ID: " . $teacher_id . "\n";
    }
    
    echo "\n=== البحث عن زيارات هذا المعلم ===\n";
    $visits = query("SELECT * FROM visits WHERE teacher_id = ?", [$teacher_id]);
    echo "عدد الزيارات الموجودة: " . count($visits) . "\n";
    
    if (!empty($visits)) {
        echo "عينة من الزيارات:\n";
        foreach (array_slice($visits, 0, 3) as $visit) {
            echo "- زيارة رقم " . $visit['id'] . " بتاريخ " . $visit['visit_date'] . " (teacher_id: " . $visit['teacher_id'] . ")\n";
        }
    }
    
    echo "\n=== تجربة استعلام لوحة التحكم ===\n";
    $total_visits = query_row("SELECT COUNT(*) as count FROM visits WHERE teacher_id = ?", [$teacher_id]);
    echo "إجمالي الزيارات: " . $total_visits['count'] . "\n";
    
    $visits_this_month = query_row("
        SELECT COUNT(*) as count 
        FROM visits 
        WHERE teacher_id = ? 
        AND MONTH(visit_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(visit_date) = YEAR(CURRENT_DATE())
    ", [$teacher_id]);
    echo "زيارات هذا الشهر: " . $visits_this_month['count'] . "\n";
    
    $avg_performance = query_row("
        SELECT AVG(ve.score) as avg_score
        FROM visits v
        INNER JOIN visit_evaluations ve ON v.id = ve.visit_id
        WHERE v.teacher_id = ?
    ", [$teacher_id]);
    echo "متوسط الأداء: " . ($avg_performance['avg_score'] ?? 'NULL') . "\n";
    
} else {
    echo "لم يتم العثور على المعلم!\n";
    
    // البحث بطريقة أوسع
    echo "\n=== البحث عن جميع المعلمين بالاسم عبدالعزيز ===\n";
    $teachers = query("SELECT * FROM teachers WHERE name LIKE '%عبدالعزيز%'");
    foreach ($teachers as $t) {
        echo "- " . $t['name'] . " (ID: " . $t['id'] . ", user_id: " . $t['user_id'] . ")\n";
    }
}
?>
