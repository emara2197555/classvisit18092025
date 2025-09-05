<?php
// أداة مبسطة لتحويل المؤشرات غير المقاسة من 0 إلى NULL

require_once 'includes/db_connection.php';

echo "<h1 style='color: #2563eb;'>إصلاح البيانات: تحويل المؤشرات غير المقاسة من 0 إلى NULL</h1>";

try {
    // إحصائيات البيانات الحالية
    echo "<div style='background: #f8fafc; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>📊 إحصائيات البيانات الحالية:</h2>";
    
    $stats_sql = "
        SELECT 
            COUNT(*) as total_evaluations,
            COUNT(CASE WHEN score = 0 THEN 1 END) as zero_scores,
            COUNT(CASE WHEN score IS NULL THEN 1 END) as null_scores,
            COUNT(CASE WHEN score > 0 THEN 1 END) as positive_scores
        FROM visit_evaluations
    ";
    $stats = query_row($stats_sql);
    
    echo "<ul style='font-size: 16px; line-height: 1.6;'>";
    echo "<li><strong>إجمالي التقييمات:</strong> <span style='color: #1f2937;'>{$stats['total_evaluations']}</span></li>";
    echo "<li><strong>التقييمات بدرجة 0:</strong> <span style='color: #dc2626;'>{$stats['zero_scores']}</span></li>";
    echo "<li><strong>التقييمات NULL:</strong> <span style='color: #059669;'>{$stats['null_scores']}</span></li>";
    echo "<li><strong>التقييمات الإيجابية:</strong> <span style='color: #2563eb;'>{$stats['positive_scores']}</span></li>";
    echo "</ul>";
    echo "</div>";
    
    // فحص الملاحظات المخصصة
    echo "<div style='background: #fef3c7; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>⚠️ فحص الملاحظات المخصصة:</h2>";
    
    $custom_notes_sql = "
        SELECT COUNT(*) as count
        FROM visit_evaluations 
        WHERE score = 0 AND custom_recommendation IS NOT NULL AND custom_recommendation != ''
    ";
    $custom_count = query_row($custom_notes_sql)['count'];
    
    if ($custom_count > 0) {
        echo "<p style='color: #b45309;'><strong>تحذير:</strong> هناك $custom_count تقييمات بدرجة 0 لها ملاحظات مخصصة.</p>";
        echo "<p>هذه قد تكون تقييمات فعلية (ضعيف) وليست مؤشرات غير مقاسة.</p>";
    } else {
        echo "<p style='color: #059669;'>✓ لا توجد ملاحظات مخصصة مرتبطة بالتقييمات ذات الدرجة 0</p>";
    }
    echo "</div>";
    
    // عمليات التحديث
    if (isset($_GET['action'])) {
        echo "<div style='background: #ecfdf5; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #10b981;'>";
        
        if ($_GET['action'] == 'update_all') {
            echo "<h2>🔄 تحديث جميع التقييمات ذات الدرجة 0 إلى NULL...</h2>";
            
            $update_sql = "UPDATE visit_evaluations SET score = NULL WHERE score = 0";
            $affected_rows = execute($update_sql);
            
            echo "<p style='color: #065f46; font-size: 18px;'><strong>✓ تم تحديث $affected_rows تقييم من الدرجة 0 إلى NULL</strong></p>";
            
        } elseif ($_GET['action'] == 'update_safe') {
            echo "<h2>🔄 تحديث آمن للتقييمات بدون ملاحظات مخصصة...</h2>";
            
            $safe_update_sql = "
                UPDATE visit_evaluations 
                SET score = NULL 
                WHERE score = 0 
                AND (custom_recommendation IS NULL OR custom_recommendation = '')
            ";
            $affected_rows = execute($safe_update_sql);
            
            echo "<p style='color: #065f46; font-size: 18px;'><strong>✓ تم تحديث $affected_rows تقييم آمن من الدرجة 0 إلى NULL</strong></p>";
        }
        
        // عرض الإحصائيات الجديدة
        echo "<h3>📈 الإحصائيات بعد التحديث:</h3>";
        $new_stats = query_row($stats_sql);
        echo "<ul style='font-size: 16px; line-height: 1.6;'>";
        echo "<li><strong>إجمالي التقييمات:</strong> <span style='color: #1f2937;'>{$new_stats['total_evaluations']}</span></li>";
        echo "<li><strong>التقييمات بدرجة 0:</strong> <span style='color: #dc2626;'>{$new_stats['zero_scores']}</span></li>";
        echo "<li><strong>التقييمات NULL:</strong> <span style='color: #059669;'>{$new_stats['null_scores']}</span></li>";
        echo "<li><strong>التقييمات الإيجابية:</strong> <span style='color: #2563eb;'>{$new_stats['positive_scores']}</span></li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<div style='background: #dbeafe; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h3>🎉 تم الإصلاح بنجاح!</h3>";
        echo "<p>الآن المؤشرات غير المقاسة ستظهر كـ NULL في قاعدة البيانات ولن تؤثر على حساب النسب.</p>";
        echo "<p><strong>الخطوة التالية:</strong> اختبر إنشاء زيارة جديدة والتأكد من أن النسب صحيحة.</p>";
        echo "</div>";
        
    } else {
        // عرض خيارات التحديث
        echo "<div style='background: #f1f5f9; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
        echo "<h2>🎯 خيارات الإصلاح:</h2>";
        
        echo "<div style='display: flex; gap: 20px; flex-wrap: wrap;'>";
        
        if ($custom_count == 0) {
            echo "<a href='?action=update_all' onclick='return confirm(\"هل أنت متأكد من تحديث جميع التقييمات ذات الدرجة 0؟\")' style='display: inline-block; background: #2563eb; color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; font-weight: bold;'>";
            echo "🚀 تحديث جميع التقييمات (آمن)";
            echo "</a>";
        } else {
            echo "<a href='?action=update_safe' onclick='return confirm(\"هل أنت متأكد من التحديث الآمن؟\")' style='display: inline-block; background: #059669; color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; font-weight: bold;'>";
            echo "✅ تحديث آمن (بدون ملاحظات)";
            echo "</a>";
            
            echo "<a href='?action=update_all' onclick='return confirm(\"تحذير: سيتم تحديث جميع التقييمات بما في ذلك التي لها ملاحظات. هل أنت متأكد؟\")' style='display: inline-block; background: #dc2626; color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; font-weight: bold;'>";
            echo "⚠️ تحديث جميع التقييمات";
            echo "</a>";
        }
        
        echo "</div>";
        
        echo "<div style='margin-top: 15px; padding: 15px; background: #fef3c7; border-radius: 6px;'>";
        echo "<p><strong>الفرق بين الخيارين:</strong></p>";
        echo "<ul>";
        echo "<li><strong>التحديث الآمن:</strong> يحول فقط التقييمات 0 التي لا تحتوي على ملاحظات مخصصة</li>";
        echo "<li><strong>تحديث جميع التقييمات:</strong> يحول جميع التقييمات 0 إلى NULL (قد يشمل تقييمات فعلية ضعيفة)</li>";
        echo "</ul>";
        echo "</div>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #fef2f2; color: #991b1b; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h2>❌ خطأ:</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='visits.php' style='display: inline-block; background: #6b7280; color: white; padding: 12px 20px; text-decoration: none; border-radius: 6px;'>← العودة لعرض الزيارات</a>";
echo "</div>";
?>
