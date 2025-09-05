<?php
// أداة لإصلاح البيانات الموجودة وتحويل القيم 0 إلى NULL للمؤشرات غير المقاسة

require_once 'includes/db_connection.php';

echo "<h1>تحديث البيانات الموجودة - تحويل المؤشرات غير المقاسة من 0 إلى NULL</h1>";

try {
    // أولاً: إحصائيات البيانات الحالية
    echo "<h2>1. إحصائيات البيانات الحالية:</h2>";
    
    $stats_sql = "
        SELECT 
            COUNT(*) as total_evaluations,
            COUNT(CASE WHEN score = 0 THEN 1 END) as zero_scores,
            COUNT(CASE WHEN score IS NULL THEN 1 END) as null_scores,
            COUNT(CASE WHEN score > 0 THEN 1 END) as positive_scores
        FROM visit_evaluations
    ";
    $stats = query_row($stats_sql);
    
    echo "<ul>";
    echo "<li><strong>إجمالي التقييمات:</strong> {$stats['total_evaluations']}</li>";
    echo "<li><strong>التقييمات بدرجة 0:</strong> {$stats['zero_scores']}</li>";
    echo "<li><strong>التقييمات NULL:</strong> {$stats['null_scores']}</li>";
    echo "<li><strong>التقييمات الإيجابية:</strong> {$stats['positive_scores']}</li>";
    echo "</ul>";
    
    // فحص ما إذا كان هناك توصيات مرتبطة بالدرجات 0
    echo "<h2>2. فحص التوصيات المرتبطة بالدرجة 0:</h2>";
    
    // التحقق من وجود جدول visit_recommendations
    $table_check_sql = "SHOW TABLES LIKE 'visit_recommendations'";
    $table_exists = query($table_check_sql);
    
    if ($table_exists) {
        $recommendations_sql = "
            SELECT 
                ve.id, ve.visit_id, ve.indicator_id, ve.score,
                vr.recommendation_id, er.text as recommendation_text
            FROM visit_evaluations ve
            LEFT JOIN visit_recommendations vr ON ve.id = vr.evaluation_id
            LEFT JOIN evaluation_recommendations er ON vr.recommendation_id = er.id
            WHERE ve.score = 0 AND vr.recommendation_id IS NOT NULL
            LIMIT 10
        ";
        $recs_with_zero = query($recommendations_sql);
        
        if ($recs_with_zero) {
            echo "<p style='color: orange;'>تحذير: هناك " . count($recs_with_zero) . " تقييمات بدرجة 0 مرتبطة بتوصيات.</p>";
            echo "<p>هذا يعني أن هذه التقييمات كانت فعلاً ضعيفة وليست غير مقاسة.</p>";
            
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>معرف التقييم</th><th>معرف الزيارة</th><th>معرف المؤشر</th><th>النص</th></tr>";
            foreach ($recs_with_zero as $rec) {
                echo "<tr>";
                echo "<td>{$rec['id']}</td>";
                echo "<td>{$rec['visit_id']}</td>";
                echo "<td>{$rec['indicator_id']}</td>";
                echo "<td>" . htmlspecialchars($rec['recommendation_text']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: green;'>✓ لا توجد توصيات مرتبطة بالتقييمات ذات الدرجة 0</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ جدول visit_recommendations غير موجود - تخطي فحص التوصيات</p>";
    }
    
    // فحص ما إذا كان هناك ملاحظات مخصصة مرتبطة بالدرجات 0
    echo "<h2>3. فحص الملاحظات المخصصة المرتبطة بالدرجة 0:</h2>";
    
    $custom_notes_sql = "
        SELECT COUNT(*) as count
        FROM visit_evaluations 
        WHERE score = 0 AND custom_recommendation IS NOT NULL AND custom_recommendation != ''
    ";
    $custom_count = query_row($custom_notes_sql)['count'];
    
    if ($custom_count > 0) {
        echo "<p style='color: orange;'>تحذير: هناك $custom_count تقييمات بدرجة 0 لها ملاحظات مخصصة.</p>";
        
        $custom_examples_sql = "
            SELECT id, visit_id, indicator_id, custom_recommendation
            FROM visit_evaluations 
            WHERE score = 0 AND custom_recommendation IS NOT NULL AND custom_recommendation != ''
            LIMIT 5
        ";
        $custom_examples = query($custom_examples_sql);
        
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>معرف التقييم</th><th>معرف الزيارة</th><th>معرف المؤشر</th><th>الملاحظة المخصصة</th></tr>";
        foreach ($custom_examples as $example) {
            echo "<tr>";
            echo "<td>{$example['id']}</td>";
            echo "<td>{$example['visit_id']}</td>";
            echo "<td>{$example['indicator_id']}</td>";
            echo "<td>" . htmlspecialchars($example['custom_recommendation']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>✓ لا توجد ملاحظات مخصصة مرتبطة بالتقييمات ذات الدرجة 0</p>";
    }
    
    // خيارات التحديث
    echo "<h2>4. خيارات التحديث:</h2>";
    
    if (isset($_GET['action']) && $_GET['action'] == 'update_all') {
        echo "<h3>تحديث جميع التقييمات ذات الدرجة 0 إلى NULL...</h3>";
        
        $update_sql = "
            UPDATE visit_evaluations 
            SET score = NULL 
            WHERE score = 0
        ";
        $affected_rows = execute($update_sql);
        
        echo "<p style='color: green;'>✓ تم تحديث $affected_rows تقييم من الدرجة 0 إلى NULL</p>";
        
        // عرض الإحصائيات الجديدة
        $new_stats = query_row($stats_sql);
        echo "<h3>الإحصائيات بعد التحديث:</h3>";
        echo "<ul>";
        echo "<li><strong>إجمالي التقييمات:</strong> {$new_stats['total_evaluations']}</li>";
        echo "<li><strong>التقييمات بدرجة 0:</strong> {$new_stats['zero_scores']}</li>";
        echo "<li><strong>التقييمات NULL:</strong> {$new_stats['null_scores']}</li>";
        echo "<li><strong>التقييمات الإيجابية:</strong> {$new_stats['positive_scores']}</li>";
        echo "</ul>";
        
    } elseif (isset($_GET['action']) && $_GET['action'] == 'update_safe') {
        echo "<h3>تحديث التقييمات ذات الدرجة 0 التي لا تحتوي على ملاحظات مخصصة...</h3>";
        
        // التحقق من وجود جدول visit_recommendations
        $table_check_sql = "SHOW TABLES LIKE 'visit_recommendations'";
        $table_exists = query($table_check_sql);
        
        if ($table_exists) {
            $safe_update_sql = "
                UPDATE visit_evaluations ve
                SET score = NULL 
                WHERE ve.score = 0 
                AND NOT EXISTS (
                    SELECT 1 FROM visit_recommendations vr WHERE vr.evaluation_id = ve.id
                )
                AND (ve.custom_recommendation IS NULL OR ve.custom_recommendation = '')
            ";
        } else {
            // إذا لم يكن جدول visit_recommendations موجوداً، نستثني فقط الملاحظات المخصصة
            $safe_update_sql = "
                UPDATE visit_evaluations ve
                SET score = NULL 
                WHERE ve.score = 0 
                AND (ve.custom_recommendation IS NULL OR ve.custom_recommendation = '')
            ";
        }
        
        $affected_rows = execute($safe_update_sql);
        
        echo "<p style='color: green;'>✓ تم تحديث $affected_rows تقييم آمن من الدرجة 0 إلى NULL</p>";
        
        // عرض الإحصائيات الجديدة
        $new_stats = query_row($stats_sql);
        echo "<h3>الإحصائيات بعد التحديث الآمن:</h3>";
        echo "<ul>";
        echo "<li><strong>إجمالي التقييمات:</strong> {$new_stats['total_evaluations']}</li>";
        echo "<li><strong>التقييمات بدرجة 0:</strong> {$new_stats['zero_scores']}</li>";
        echo "<li><strong>التقييمات NULL:</strong> {$new_stats['null_scores']}</li>";
        echo "<li><strong>التقييمات الإيجابية:</strong> {$new_stats['positive_scores']}</li>";
        echo "</ul>";
        
    } else {
        echo "<div style='background: #f0f0f0; padding: 15px; border-radius: 5px;'>";
        echo "<p><strong>اختر نوع التحديث:</strong></p>";
        echo "<ul>";
        echo "<li><a href='?action=update_safe' style='color: green; text-decoration: none;'>تحديث آمن (فقط التقييمات بدون توصيات أو ملاحظات)</a></li>";
        echo "<li><a href='?action=update_all' style='color: red; text-decoration: none;'>تحديث جميع التقييمات ذات الدرجة 0</a></li>";
        echo "</ul>";
        echo "<p><em>ملاحظة: التحديث الآمن أفضل إذا كان هناك تقييمات فعلية بدرجة 0 (ضعيف)</em></p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>خطأ: " . $e->getMessage() . "</p>";
}

echo "<p><a href='view_visit.php'>العودة لعرض الزيارات</a></p>";
?>
