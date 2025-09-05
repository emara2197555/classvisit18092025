<?php
// اختبار عرض النقاط NULL مقابل 0

require_once 'includes/db_connection.php';

echo "<h1>اختبار عرض المؤشرات NULL مقابل 0</h1>";

// اختبار القيم المختلفة
$test_values = [
    null,
    0,
    1,
    2,
    3
];

echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
echo "<tr><th>القيمة الأصلية</th><th>النوع</th><th>العرض المُعالج</th><th>CSS Class</th></tr>";

foreach ($test_values as $score) {
    echo "<tr>";
    echo "<td>" . ($score === null ? 'NULL' : $score) . "</td>";
    echo "<td>" . gettype($score) . "</td>";
    
    // معالجة العرض كما في view_visit.php
    $score_text = '';
    $score_class = '';
    
    if ($score === null) {
        $score_text = 'لم يتم قياسه';
        $score_class = 'score-null';
    } else {
        $score = (int)$score;
        switch ($score) {
            case 3:
                $score_text = 'ممتاز';
                $score_class = 'score-3';
                break;
            case 2:
                $score_text = 'جيد';
                $score_class = 'score-2';
                break;
            case 1:
                $score_text = 'مقبول';
                $score_class = 'score-1';
                break;
            case 0:
                $score_text = 'ضعيف';
                $score_class = 'score-0';
                break;
            default:
                $score_text = 'غير مقاس';
                $score_class = 'score-null';
                break;
        }
    }
    
    echo "<td>$score_text</td>";
    echo "<td>$score_class</td>";
    echo "</tr>";
}

echo "</table>";

// اختبار مع بيانات فعلية من قاعدة البيانات
echo "<h2>اختبار مع بيانات فعلية:</h2>";

try {
    $test_sql = "
        SELECT ve.score, ei.name as indicator_name
        FROM visit_evaluations ve
        JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
        WHERE ve.score IS NULL OR ve.score = 0
        LIMIT 5
    ";
    
    $results = query($test_sql);
    
    if ($results) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>اسم المؤشر</th><th>القيمة في قاعدة البيانات</th><th>النوع</th><th>العرض</th></tr>";
        
        foreach ($results as $row) {
            $score = $row['score'];
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['indicator_name']) . "</td>";
            echo "<td>" . ($score === null ? 'NULL' : $score) . "</td>";
            echo "<td>" . gettype($score) . "</td>";
            
            // معالجة العرض
            if ($score === null) {
                $display = 'لم يتم قياسه';
            } else {
                $score = (int)$score;
                switch ($score) {
                    case 3: $display = 'ممتاز'; break;
                    case 2: $display = 'جيد'; break;
                    case 1: $display = 'مقبول'; break;
                    case 0: $display = 'ضعيف'; break;
                    default: $display = 'غير مقاس'; break;
                }
            }
            
            echo "<td style='font-weight: bold; color: " . ($score === null ? 'blue' : 'red') . ";'>$display</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>لا توجد بيانات للاختبار</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}

echo "<p><a href='view_visit.php'>العودة لعرض الزيارات</a></p>";
?>
