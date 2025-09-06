<?php
require_once 'includes/db_connection.php';

echo "=== اختبار استعلام API ===\n";

$teacher_id = 57;

// نفس الاستعلام المستخدم في API
$query = "
    SELECT 
        AVG(v.total_score / (
            NULLIF((SELECT COUNT(DISTINCT ve2.indicator_id) FROM visit_evaluations ve2 WHERE ve2.visit_id = v.id AND ve2.score IS NOT NULL), 0) * 3
        )) as avg_score,
        COUNT(DISTINCT v.id) as visits_used_in_calculation
    FROM visits v 
    WHERE v.teacher_id = ?
    AND EXISTS (SELECT 1 FROM visit_evaluations ve WHERE ve.visit_id = v.id AND ve.score IS NOT NULL)
";

$result = query_row($query, [$teacher_id]);

echo "نتيجة الاستعلام:\n";
echo "  avg_score: " . $result['avg_score'] . "\n";
echo "  visits_used_in_calculation: " . $result['visits_used_in_calculation'] . "\n";
echo "  النسبة المئوية: " . round($result['avg_score'] * 100, 2) . "%\n";

// حساب يدوي لكل زيارة
echo "\n=== حساب يدوي ===\n";
$manual_query = "
    SELECT 
        v.id,
        v.total_score,
        (SELECT COUNT(DISTINCT ve2.indicator_id) FROM visit_evaluations ve2 WHERE ve2.visit_id = v.id AND ve2.score IS NOT NULL) as indicators,
        (v.total_score / ((SELECT COUNT(DISTINCT ve2.indicator_id) FROM visit_evaluations ve2 WHERE ve2.visit_id = v.id AND ve2.score IS NOT NULL) * 3)) as individual_avg
    FROM visits v 
    WHERE v.teacher_id = ?
    AND EXISTS (SELECT 1 FROM visit_evaluations ve WHERE ve.visit_id = v.id AND ve.score IS NOT NULL)
";

$visits = query($manual_query, [$teacher_id]);
$sum = 0;
$count = 0;

foreach($visits as $visit) {
    echo "زيارة {$visit['id']}: {$visit['total_score']} / ({$visit['indicators']} × 3) = " . round($visit['individual_avg'] * 100, 2) . "%\n";
    $sum += $visit['individual_avg'];
    $count++;
}

$manual_avg = $count > 0 ? ($sum / $count) : 0;
echo "\nمتوسط يدوي: " . round($manual_avg * 100, 2) . "%\n";
?>
