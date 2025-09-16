<?php
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "<h2>اختبار ملف تقارير التعليم الإلكتروني</h2>";

try {
    // اختبار الاستعلام الأساسي
    echo "<h3>1. اختبار الاستعلام الأساسي للتقارير:</h3>";
    
    $test_query = "
        SELECT 
            ea.*,
            t.name as teacher_name,
            s.name as subject_name,
            ay.name as academic_year_name,
            (ea.attendance_students * 100.0 / ea.num_students) as attendance_percentage
        FROM elearning_attendance ea
        JOIN teachers t ON ea.teacher_id = t.id
        JOIN subjects s ON ea.subject_id = s.id
        JOIN academic_years ay ON ea.academic_year_id = ay.id
        ORDER BY ea.lesson_date DESC
        LIMIT 5
    ";
    
    $test_result = query($test_query);
    echo "✅ الاستعلام نجح - عدد السجلات: " . count($test_result) . "<br>";
    
    if (!empty($test_result)) {
        echo "أول سجل:<br>";
        $first_record = $test_result[0];
        echo "- التاريخ: " . $first_record['lesson_date'] . "<br>";
        echo "- المعلم: " . $first_record['teacher_name'] . "<br>";
        echo "- المادة: " . $first_record['subject_name'] . "<br>";
        echo "- العام الدراسي: " . $first_record['academic_year_name'] . "<br>";
        echo "- عدد الطلاب: " . $first_record['num_students'] . "<br>";
        echo "- الحضور: " . $first_record['attendance_students'] . "<br>";
        echo "- نسبة الحضور: " . number_format($first_record['attendance_percentage'], 2) . "%<br>";
    }
    
    // اختبار إحصائيات الملخص
    echo "<h3>2. اختبار إحصائيات الملخص:</h3>";
    
    $total_students = array_sum(array_column($test_result, 'num_students'));
    $total_present = array_sum(array_column($test_result, 'attendance_students'));
    $avg_attendance = $total_students > 0 ? ($total_present / $total_students) * 100 : 0;
    
    echo "✅ إجمالي الطلاب: " . $total_students . "<br>";
    echo "✅ إجمالي الحضور: " . $total_present . "<br>";
    echo "✅ متوسط الحضور: " . number_format($avg_attendance, 2) . "%<br>";
    
    // اختبار فلتر التاريخ
    echo "<h3>3. اختبار فلتر التاريخ:</h3>";
    
    $date_filter_query = "
        SELECT COUNT(*) as count
        FROM elearning_attendance ea
        WHERE ea.lesson_date >= '2024-01-01'
    ";
    
    $date_result = query($date_filter_query);
    echo "✅ فلتر التاريخ نجح - عدد السجلات من 2024: " . $date_result[0]['count'] . "<br>";
    
    echo "<h3>✅ جميع الاختبارات نجحت!</h3>";
    echo "<p><a href='elearning_attendance_reports.php'>الانتقال إلى صفحة التقارير</a></p>";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "<br>";
    echo "تفاصيل الخطأ: " . $e->getTraceAsString();
}
?>
