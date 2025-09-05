<?php
require_once '../includes/db_connection.php';

$teacher_id = 45;
$visitor_person_id = 1;

echo "=== اختبار استعلام آخر زيارة للزائر الحالي ===\n";

$query = "
    SELECT 
        v.id, v.visit_date, v.general_notes, v.recommendation_notes, v.appreciation_notes, v.total_score,
        g.name as grade_name, s.name as section_name,
        (v.total_score / (
            SELECT COUNT(DISTINCT ve2.indicator_id) * 3 
            FROM visit_evaluations ve2 
            WHERE ve2.visit_id = v.id
        )) as average_score,
        (SELECT COUNT(DISTINCT ve3.indicator_id) FROM visit_evaluations ve3 WHERE ve3.visit_id = v.id) as total_indicators
    FROM visits v 
    LEFT JOIN grades g ON v.grade_id = g.id 
    LEFT JOIN sections s ON v.section_id = s.id
    WHERE v.teacher_id = ?
    AND v.visitor_person_id = ?
    ORDER BY v.visit_date DESC 
    LIMIT 1
";

try {
    $result = query($query, [$teacher_id, $visitor_person_id]);
    
    if (!empty($result)) {
        $visit = $result[0];
        echo "نتيجة الاستعلام:\n";
        foreach($visit as $key => $value) {
            echo "  $key: $value\n";
        }
        
        // اختبار تقييمات هذه الزيارة
        echo "\n=== تقييمات هذه الزيارة ===\n";
        $evaluations = query("SELECT * FROM visit_evaluations WHERE visit_id = ?", [$visit['id']]);
        echo "عدد التقييمات: " . count($evaluations) . "\n";
        foreach($evaluations as $eval) {
            echo "  المؤشر " . $eval['indicator_id'] . ": " . $eval['score'] . "\n";
        }
    } else {
        echo "لا توجد زيارات لهذا الزائر والمعلم\n";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
