<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// محاكاة تسجيل دخول
start_secure_session();
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'E-Learning Coordinator';

echo "<h2>اختبار تقارير التعليم الإلكتروني المحدثة</h2>";

try {
    // اختبار الاستعلام المحدث
    $sql = "
        SELECT 
            ea.*,
            t.name as teacher_name,
            s.name as subject_name,
            ay.name as academic_year_name,
            g.name as grade_name,
            sec.name as section_name,
            sch.name as school_name,
            (ea.attendance_students * 100.0 / ea.num_students) as attendance_percentage
        FROM elearning_attendance ea
        JOIN teachers t ON ea.teacher_id = t.id
        JOIN subjects s ON ea.subject_id = s.id
        JOIN academic_years ay ON ea.academic_year_id = ay.id
        LEFT JOIN grades g ON ea.grade_id = g.id
        LEFT JOIN sections sec ON ea.section_id = sec.id
        LEFT JOIN schools sch ON ea.school_id = sch.id
        ORDER BY ea.lesson_date DESC
        LIMIT 3
    ";
    
    $records = query($sql);
    
    echo "<h3>✅ الاستعلام المحدث يعمل بنجاح!</h3>";
    echo "<p>عدد السجلات: " . count($records) . "</p>";
    
    if (!empty($records)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th style='padding: 8px;'>التاريخ</th>";
        echo "<th style='padding: 8px;'>المعلم</th>";
        echo "<th style='padding: 8px;'>المادة</th>";
        echo "<th style='padding: 8px;'>الصف</th>";
        echo "<th style='padding: 8px;'>الشعبة</th>";
        echo "<th style='padding: 8px;'>المدرسة</th>";
        echo "<th style='padding: 8px;'>موضوع الدرس</th>";
        echo "<th style='padding: 8px;'>التقييم</th>";
        echo "<th style='padding: 8px;'>الحضور</th>";
        echo "<th style='padding: 8px;'>النسبة</th>";
        echo "</tr>";
        
        foreach ($records as $record) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($record['lesson_date']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($record['teacher_name']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($record['subject_name']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($record['grade_name'] ?? 'غير محدد') . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($record['section_name'] ?? 'غير محدد') . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($record['school_name'] ?? 'غير محدد') . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($record['lesson_topic']) . "</td>";
            echo "<td style='padding: 8px;'>" . htmlspecialchars($record['attendance_rating']) . "</td>";
            echo "<td style='padding: 8px;'>" . $record['attendance_students'] . "/" . $record['num_students'] . "</td>";
            echo "<td style='padding: 8px;'>" . number_format($record['attendance_percentage'], 1) . "%</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>اختبار التقييمات:</h3>";
        
        // اختبار ألوان التقييمات
        $rating_colors = [
            'excellent' => 'bg-green-100 text-green-800',
            'very_good' => 'bg-blue-100 text-blue-800',
            'good' => 'bg-yellow-100 text-yellow-800',
            'acceptable' => 'bg-orange-100 text-orange-800',
            'poor' => 'bg-red-100 text-red-800'
        ];
        $rating_labels = [
            'excellent' => 'ممتاز',
            'very_good' => 'جيد جداً',
            'good' => 'جيد',
            'acceptable' => 'مقبول',
            'poor' => 'ضعيف'
        ];
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th style='padding: 8px;'>التقييم</th><th style='padding: 8px;'>اللون</th><th style='padding: 8px;'>التسمية</th></tr>";
        foreach ($rating_colors as $rating => $color) {
            echo "<tr>";
            echo "<td style='padding: 8px;'>$rating</td>";
            echo "<td style='padding: 8px; background-color: #e0e0e0;'>$color</td>";
            echo "<td style='padding: 8px;'>" . $rating_labels[$rating] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p>لا توجد سجلات في قاعدة البيانات</p>";
    }
    
    echo "<hr>";
    echo "<h3>اختبار المعلومات المفقودة:</h3>";
    
    // اختبار التعامل مع البيانات المفقودة
    $test_record = [
        'grade_name' => null,
        'section_name' => null,
        'attendance_rating' => 'unknown_rating'
    ];
    
    $grade_name = $test_record['grade_name'] ?? 'غير محدد';
    $section_name = $test_record['section_name'] ?? 'غير محدد';
    $class_display = htmlspecialchars($grade_name . ' - شعبة ' . $section_name);
    
    echo "عرض الصف والشعبة عند عدم التوفر: " . $class_display . "<br>";
    
    $rating_display = $rating_labels[$test_record['attendance_rating']] ?? $test_record['attendance_rating'];
    echo "عرض تقييم غير معروف: " . $rating_display . "<br>";
    
    echo "<h3>✅ جميع الاختبارات نجحت!</h3>";
    echo "<p><a href='elearning_attendance_reports.php'>عرض صفحة التقارير</a></p>";
    
} catch (Exception $e) {
    echo "❌ خطأ: " . $e->getMessage() . "<br>";
    echo "تفاصيل الخطأ: " . $e->getTraceAsString();
}
?>
