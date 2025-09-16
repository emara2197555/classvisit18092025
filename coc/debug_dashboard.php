<?php
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "تشخيص أخطاء elearning_coordinator_dashboard.php:\n\n";

// تشغيل كل استعلام بشكل منفصل للعثور على المشكلة

$current_year_id = 2;

echo "1. اختبار إحصائيات الحضور...\n";
try {
    $attendance_stats = query_row("
        SELECT 
            COUNT(*) as total_sessions,
            COUNT(DISTINCT teacher_id) as active_teachers,
            COUNT(CASE WHEN attendance_type = 'direct' THEN 1 END) as direct_sessions,
            COUNT(CASE WHEN attendance_type = 'remote' THEN 1 END) as remote_sessions,
            COUNT(CASE WHEN attendance_rating = 'excellent' THEN 1 END) as excellent_sessions,
            COUNT(CASE WHEN attendance_rating = 'very_good' THEN 1 END) as very_good_sessions,
            COUNT(CASE WHEN attendance_rating = 'good' THEN 1 END) as good_sessions,
            COUNT(CASE WHEN attendance_rating = 'acceptable' THEN 1 END) as acceptable_sessions,
            COUNT(CASE WHEN attendance_rating = 'poor' THEN 1 END) as poor_sessions
        FROM elearning_attendance 
        WHERE academic_year_id = ?
    ", [$current_year_id]);
    echo "✅ إحصائيات الحضور - نجح\n";
    print_r($attendance_stats);
} catch (Exception $e) {
    echo "❌ إحصائيات الحضور - فشل: " . $e->getMessage() . "\n";
}

echo "\n2. اختبار إحصائيات قطر...\n";
try {
    $qatar_stats = query_row("
        SELECT 
            COUNT(*) as total_evaluations,
            COUNT(DISTINCT teacher_id) as evaluated_teachers,
            AVG(total_score) as avg_score,
            COUNT(CASE WHEN performance_level = 'excellent' THEN 1 END) as excellent_count,
            COUNT(CASE WHEN performance_level = 'very_good' THEN 1 END) as very_good_count,
            COUNT(CASE WHEN performance_level = 'good' THEN 1 END) as good_count,
            COUNT(CASE WHEN performance_level = 'needs_improvement' THEN 1 END) as needs_improvement_count,
            COUNT(CASE WHEN performance_level = 'poor' THEN 1 END) as poor_count
        FROM qatar_system_performance 
        WHERE academic_year_id = ?
    ", [$current_year_id]);
    echo "✅ إحصائيات قطر - نجح\n";
    print_r($qatar_stats);
} catch (Exception $e) {
    echo "❌ إحصائيات قطر - فشل: " . $e->getMessage() . "\n";
}

echo "\n3. اختبار الحضور الحديث...\n";
try {
    $recent_attendance = query("
        SELECT 
            DATE(lesson_date) as attendance_date,
            COUNT(*) as sessions_count,
            COUNT(CASE WHEN attendance_rating = 'excellent' THEN 1 END) as excellent_sessions
        FROM elearning_attendance 
        WHERE academic_year_id = ? 
            AND lesson_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(lesson_date)
        ORDER BY attendance_date DESC
        LIMIT 7
    ", [$current_year_id]);
    echo "✅ الحضور الحديث - نجح\n";
    print_r($recent_attendance);
} catch (Exception $e) {
    echo "❌ الحضور الحديث - فشل: " . $e->getMessage() . "\n";
}

echo "\n4. اختبار التقييمات الحديثة...\n";
try {
    $recent_evaluations = query("
        SELECT 
            qsp.*,
            t.name as teacher_name,
            s.name as subject_name,
            DATE(qsp.evaluation_date) as eval_date
        FROM qatar_system_performance qsp
        JOIN teachers t ON qsp.teacher_id = t.id
        JOIN subjects s ON qsp.subject_id = s.id
        WHERE qsp.academic_year_id = ?
        ORDER BY qsp.evaluation_date DESC
        LIMIT 5
    ", [$current_year_id]);
    echo "✅ التقييمات الحديثة - نجح\n";
    print_r($recent_evaluations);
} catch (Exception $e) {
    echo "❌ التقييمات الحديثة - فشل: " . $e->getMessage() . "\n";
}

echo "\nتم الانتهاء من التشخيص.\n";
?>
