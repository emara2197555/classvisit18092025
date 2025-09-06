<?php
require_once 'includes/db_connection.php';

echo "=== فحص تفصيلي لزيارة 18 ===\n";

$visit_id = 18;

$evaluations = query("
    SELECT indicator_id, score 
    FROM visit_evaluations 
    WHERE visit_id = ? 
    ORDER BY indicator_id
", [$visit_id]);

echo "التقييمات:\n";
foreach($evaluations as $eval) {
    echo "  مؤشر {$eval['indicator_id']}: {$eval['score']}\n";
}

$distinct_count = query_row("
    SELECT COUNT(DISTINCT indicator_id) as count 
    FROM visit_evaluations 
    WHERE visit_id = ?
", [$visit_id]);

$total_count = query_row("
    SELECT COUNT(*) as count 
    FROM visit_evaluations 
    WHERE visit_id = ?
", [$visit_id]);

echo "\nعدد المؤشرات الفريدة: " . $distinct_count['count'] . "\n";
echo "العدد الكلي للتقييمات: " . $total_count['count'] . "\n";

$visit = query_row("SELECT total_score FROM visits WHERE id = ?", [$visit_id]);
echo "الدرجة الكلية: " . $visit['total_score'] . "\n";

$calculated_score = $distinct_count['count'] * 3;
echo "الدرجة القصوى المحسوبة: " . $calculated_score . "\n";
echo "النسبة: " . round(($visit['total_score'] / $calculated_score) * 100, 2) . "%\n";
?>
