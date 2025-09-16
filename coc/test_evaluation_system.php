<?php
/**
 * اختبار وتشخيص نظام التقييم
 */

require_once 'includes/db_connection.php';

echo "<h2>اختبار نظام التقييم</h2>";

// 1. فحص الجداول
echo "<h3>1. فحص الجداول:</h3>";
$tables = ['qatar_system_criteria', 'qatar_system_performance', 'teachers', 'subjects', 'academic_years'];
foreach ($tables as $table) {
    $result = query_row("SHOW TABLES LIKE '$table'");
    echo "جدول $table: " . ($result ? "<span style='color:green'>موجود</span>" : "<span style='color:red'>غير موجود</span>") . "<br>";
}

// 2. فحص هيكل جدول qatar_system_performance
echo "<h3>2. هيكل جدول qatar_system_performance:</h3>";
try {
    $structure = query("DESCRIBE qatar_system_performance");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>الحقل</th><th>النوع</th><th>فارغ</th><th>المفتاح</th><th>افتراضي</th></tr>";
    foreach ($structure as $field) {
        echo "<tr>";
        echo "<td>" . $field['Field'] . "</td>";
        echo "<td>" . $field['Type'] . "</td>";
        echo "<td>" . $field['Null'] . "</td>";
        echo "<td>" . $field['Key'] . "</td>";
        echo "<td>" . $field['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<span style='color:red'>خطأ في فحص الهيكل: " . $e->getMessage() . "</span>";
}

// 3. فحص البيانات الأساسية
echo "<h3>3. البيانات الأساسية:</h3>";

$criteria_count = query_row("SELECT COUNT(*) as count FROM qatar_system_criteria WHERE is_active = 1");
echo "عدد المعايير النشطة: " . $criteria_count['count'] . "<br>";

$teachers_count = query_row("SELECT COUNT(*) as count FROM teachers");
echo "عدد المعلمين: " . $teachers_count['count'] . "<br>";

$subjects_count = query_row("SELECT COUNT(*) as count FROM subjects");
echo "عدد المواد: " . $subjects_count['count'] . "<br>";

$years_count = query_row("SELECT COUNT(*) as count FROM academic_years");
echo "عدد السنوات الدراسية: " . $years_count['count'] . "<br>";

// 4. اختبار إدراج بيانات وهمية
echo "<h3>4. اختبار الإدراج:</h3>";

try {
    // محاولة إدراج تقييم تجريبي
    $test_data = [
        'academic_year_id' => 1,
        'term' => 'first',
        'teacher_id' => 1,
        'subject_id' => 1,
        'evaluation_date' => date('Y-m-d'),
        'evaluator_id' => 1,
        'criteria_scores' => json_encode([1 => 4, 2 => 5, 3 => 3]),
        'total_score' => 4.0,
        'performance_level' => 'very_good',
        'strengths' => 'اختبار نقاط القوة',
        'improvement_areas' => 'اختبار جوانب التحسين',
        'recommendations' => 'اختبار التوصيات',
        'follow_up_date' => null,
        'notes' => 'اختبار الملاحظات'
    ];
    
    $sql = "INSERT INTO qatar_system_performance 
            (academic_year_id, term, teacher_id, subject_id, evaluation_date, 
             evaluator_id, criteria_scores, total_score, performance_level,
             strengths, improvement_areas, recommendations, follow_up_date, notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $result = query($sql, array_values($test_data));
    
    if ($result !== false) {
        echo "<span style='color:green'>✓ تم إدراج البيانات التجريبية بنجاح</span><br>";
        
        // حذف البيانات التجريبية
        $delete_sql = "DELETE FROM qatar_system_performance WHERE evaluation_date = ? AND notes = ?";
        query($delete_sql, [date('Y-m-d'), 'اختبار الملاحظات']);
        echo "<span style='color:blue'>تم حذف البيانات التجريبية</span><br>";
    } else {
        echo "<span style='color:red'>✗ فشل في إدراج البيانات التجريبية</span><br>";
    }
    
} catch (Exception $e) {
    echo "<span style='color:red'>خطأ في الاختبار: " . $e->getMessage() . "</span><br>";
    echo "تفاصيل الخطأ: " . $e->getTraceAsString() . "<br>";
}

// 5. فحص إعدادات قاعدة البيانات
echo "<h3>5. إعدادات قاعدة البيانات:</h3>";
try {
    $sql_mode = query_row("SELECT @@sql_mode as mode");
    echo "SQL Mode: " . $sql_mode['mode'] . "<br>";
    
    $charset = query_row("SELECT @@character_set_database as charset, @@collation_database as collation");
    echo "Character Set: " . $charset['charset'] . "<br>";
    echo "Collation: " . $charset['collation'] . "<br>";
} catch (Exception $e) {
    echo "<span style='color:red'>خطأ في فحص الإعدادات: " . $e->getMessage() . "</span>";
}

echo "<hr>";
echo "<p><strong>انتهى التشخيص</strong></p>";
?>
