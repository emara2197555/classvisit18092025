<?php
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "اختبار حفظ التقييم:\n\n";

// محاكاة بيانات التقييم
$test_data = [
    'academic_year_id' => 2,
    'term' => 'first',
    'teacher_id' => 334,
    'subject_id' => 1,
    'evaluation_date' => '2024-12-19',
    'evaluator_id' => 325,
    'criteria_scores' => json_encode(['1' => 4, '2' => 5, '3' => 3]),
    'total_score' => 4.0,
    'performance_level' => 'good',
    'strengths' => 'معلم متميز',
    'improvement_areas' => 'يحتاج تطوير',
    'recommendations' => 'المتابعة',
    'follow_up_date' => null,
    'notes' => 'ملاحظات'
];

try {
    $sql = "INSERT INTO qatar_system_performance 
            (academic_year_id, term, teacher_id, subject_id, evaluation_date, 
             evaluator_id, criteria_scores, total_score, performance_level,
             strengths, improvement_areas, recommendations, follow_up_date, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $result = query($sql, [
        $test_data['academic_year_id'], $test_data['term'], $test_data['teacher_id'], 
        $test_data['subject_id'], $test_data['evaluation_date'], $test_data['evaluator_id'],
        $test_data['criteria_scores'], $test_data['total_score'], $test_data['performance_level'],
        $test_data['strengths'], $test_data['improvement_areas'], $test_data['recommendations'],
        $test_data['follow_up_date'], $test_data['notes']
    ]);
    
    if ($result !== false) {
        echo "تم حفظ التقييم بنجاح!\n";
        echo "ID للتقييم المحفوظ: " . $result . "\n";
        
        // التحقق من البيانات المحفوظة
        $saved = query_row("SELECT * FROM qatar_system_performance WHERE id = ?", [$result]);
        echo "البيانات المحفوظة:\n";
        print_r($saved);
    } else {
        echo "فشل في حفظ التقييم\n";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
}
?>
