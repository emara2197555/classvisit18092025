<?php
require_once 'includes/db_connection.php';

echo "=== فحص البيانات للمعلم 57 ===\n";

$visits = query('
    SELECT 
        v.id, 
        v.total_score, 
        (SELECT COUNT(DISTINCT indicator_id) FROM visit_evaluations WHERE visit_id = v.id) as indicators,
        v.visit_date
    FROM visits v 
    WHERE teacher_id = 57
    ORDER BY v.visit_date
');

$total_percentage = 0;
$count = 0;

foreach($visits as $v) {
    $max_score = $v['indicators'] * 3;
    $percentage = $max_score > 0 ? ($v['total_score'] / $max_score) : 0;
    echo "زيارة {$v['id']} ({$v['visit_date']}): درجة {$v['total_score']} / {$max_score} = " . round($percentage * 100, 2) . "%\n";
    
    if ($max_score > 0) {
        $total_percentage += $percentage;
        $count++;
    }
}

$average_percentage = $count > 0 ? ($total_percentage / $count) : 0;
echo "\nمتوسط الأداء المحسوب يدوياً: " . round($average_percentage * 100, 2) . "%\n";
echo "عدد الزيارات المحسوبة: $count\n";
?>
