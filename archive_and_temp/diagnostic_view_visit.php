<?php
// أداة تشخيص مشكلة النسب في view_visit.php

require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// الحصول على معرف الزيارة من الرابط
$visit_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$visit_id) {
    echo "<p>يرجى إضافة ?id=رقم_الزيارة إلى الرابط</p>";
    echo "<p>مثال: diagnostic_view_visit.php?id=3</p>";
    exit;
}

echo "<h1>تشخيص مشكلة النسب للزيارة رقم $visit_id</h1>";

try {
    // جلب بيانات الزيارة الأساسية
    $visit_sql = "SELECT * FROM visits WHERE id = ?";
    $visit = query_row($visit_sql, [$visit_id]);
    
    if (!$visit) {
        echo "<p style='color: red;'>الزيارة غير موجودة!</p>";
        exit;
    }
    
    echo "<h2>1. بيانات الزيارة الأساسية</h2>";
    echo "<ul>";
    echo "<li><strong>معرف الزيارة:</strong> {$visit['id']}</li>";
    echo "<li><strong>الدرجة الإجمالية المحفوظة:</strong> {$visit['total_score']}</li>";
    echo "<li><strong>تاريخ الزيارة:</strong> {$visit['visit_date']}</li>";
    echo "</ul>";
    
    // جلب جميع التقييمات لهذه الزيارة
    $evaluations_sql = "
        SELECT 
            ve.id,
            ve.indicator_id,
            ve.score,
            ei.name as indicator_name
        FROM visit_evaluations ve
        JOIN evaluation_indicators ei ON ve.indicator_id = ei.id
        WHERE ve.visit_id = ?
        ORDER BY ve.indicator_id
    ";
    $evaluations = query($evaluations_sql, [$visit_id]);
    
    echo "<h2>2. تفاصيل التقييمات</h2>";
    if ($evaluations) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>اسم المؤشر</th><th>الدرجة</th><th>نوع الدرجة</th></tr>";
        
        $total_scores = 0;
        $valid_count = 0;
        $null_count = 0;
        $zero_count = 0;
        
        foreach ($evaluations as $eval) {
            $score = $eval['score'];
            $score_type = '';
            
            if ($score === null) {
                $score_type = 'NULL (لم يتم قياسه)';
                $null_count++;
            } else {
                $score = (float)$score;
                if ($score == 0) {
                    $zero_count++;
                    $score_type = 'صفر (ضعيف)';
                } else {
                    $score_type = 'رقمي';
                }
                $total_scores += $score;
                $valid_count++;
            }
            
            echo "<tr>";
            echo "<td>{$eval['indicator_id']}</td>";
            echo "<td>{$eval['indicator_name']}</td>";
            echo "<td>" . ($score === null ? 'NULL' : $score) . "</td>";
            echo "<td>$score_type</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>إحصائيات التقييمات:</h3>";
        echo "<ul>";
        echo "<li><strong>إجمالي المؤشرات:</strong> " . count($evaluations) . "</li>";
        echo "<li><strong>المؤشرات المقاسة:</strong> $valid_count</li>";
        echo "<li><strong>المؤشرات NULL:</strong> $null_count</li>";
        echo "<li><strong>المؤشرات صفر:</strong> $zero_count</li>";
        echo "<li><strong>مجموع الدرجات:</strong> $total_scores</li>";
        echo "</ul>";
        
    } else {
        echo "<p style='color: red;'>لا توجد تقييمات لهذه الزيارة!</p>";
    }
    
    // حساب النسب بطرق مختلفة
    echo "<h2>3. حسابات النسب المختلفة</h2>";
    
    if ($valid_count > 0) {
        // الطريقة الحالية في view_visit.php
        $current_avg = $total_scores / $valid_count;
        $current_percentage = ($total_scores / ($valid_count * 3)) * 100;
        $current_grade = get_grade($current_avg);
        
        // الطريقة القديمة (للمقارنة)
        $old_percentage = $current_avg * 25;
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>الطريقة</th><th>المتوسط</th><th>النسبة المئوية</th><th>التقدير</th></tr>";
        echo "<tr>";
        echo "<td>الحالية (صحيحة)</td>";
        echo "<td>" . number_format($current_avg, 2) . "</td>";
        echo "<td style='color: green;'>" . number_format($current_percentage, 2) . "%</td>";
        echo "<td>$current_grade</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>القديمة (للمقارنة)</td>";
        echo "<td>" . number_format($current_avg, 2) . "</td>";
        echo "<td style='color: red;'>" . number_format($old_percentage, 2) . "%</td>";
        echo "<td>-</td>";
        echo "</tr>";
        echo "</table>";
    }
    
    // فحص استعلام view_visit.php
    echo "<h2>4. اختبار استعلام view_visit.php</h2>";
    
    $test_sql = "
        SELECT 
            ei.id as indicator_id,
            ei.name as indicator_text,
            ei.domain_id,
            ed.name as domain_name,
            MAX(ve.score) as score
        FROM 
            evaluation_indicators ei
        JOIN 
            evaluation_domains ed ON ei.domain_id = ed.id
        LEFT JOIN 
            visit_evaluations ve ON ve.indicator_id = ei.id AND ve.visit_id = ?
        WHERE 
            EXISTS (SELECT 1 FROM visit_evaluations WHERE visit_id = ? AND indicator_id = ei.id)
        GROUP BY
            ei.id, ei.name, ei.domain_id, ed.name
        ORDER BY
            ei.domain_id, ei.id
    ";
    
    $test_results = query($test_sql, [$visit_id, $visit_id]);
    
    if ($test_results) {
        echo "<p style='color: green;'>✓ استعلام view_visit.php يعمل بشكل صحيح</p>";
        echo "<p>عدد النتائج: " . count($test_results) . "</p>";
    } else {
        echo "<p style='color: red;'>✗ مشكلة في استعلام view_visit.php</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}

echo "<h2>5. التوصيات</h2>";
echo "<ul>";
echo "<li>إذا كانت النسب خاطئة، تأكد من تشغيل: <a href='fix_score_null_error.php'>fix_score_null_error.php</a></li>";
echo "<li>إذا كانت الدرجات لا تزال بالنظام القديم (4,3,2,1,0)، تحتاج تحديث البيانات</li>";
echo "<li>تأكد من أن دالة get_grade() تستخدم النسب الصحيحة</li>";
echo "</ul>";

echo "<p><a href='view_visit.php?id=$visit_id'>العودة لعرض الزيارة</a></p>";
?>
