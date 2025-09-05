<?php
// إصلاح عاجل لخطأ NULL في حقل score

require_once 'includes/db_connection.php';

echo "<h2>إصلاح عاجل لخطأ حقل score</h2>";

try {
    // تعديل حقل score ليسمح بـ NULL
    echo "<p>جاري تعديل حقل score ليسمح بـ NULL...</p>";
    $pdo->exec("ALTER TABLE `visit_evaluations` MODIFY COLUMN `score` DECIMAL(5,2) NULL DEFAULT NULL");
    echo "<p style='color: green;'>✓ تم تعديل حقل score بنجاح</p>";
    
    // فحص البيانات الحالية
    $check_query = "SELECT COUNT(*) as total, 
                           COUNT(CASE WHEN score = 0 THEN 1 END) as zero_scores,
                           COUNT(CASE WHEN score IS NULL THEN 1 END) as null_scores
                    FROM visit_evaluations";
    $result = $pdo->query($check_query)->fetch();
    
    echo "<h3>إحصائيات البيانات الحالية:</h3>";
    echo "<ul>";
    echo "<li>إجمالي السجلات: {$result['total']}</li>";
    echo "<li>الدرجات صفر: {$result['zero_scores']}</li>";
    echo "<li>الدرجات NULL: {$result['null_scores']}</li>";
    echo "</ul>";
    
    // تحديث البيانات إذا لزم الأمر
    if ($result['zero_scores'] > 0 && $result['null_scores'] == 0) {
        echo "<p>جاري تحديث البيانات الموجودة لتتوافق مع النظام الجديد...</p>";
        
        // تحويل الدرجات بناءً على النظام الجديد
        $update_query = "
            UPDATE `visit_evaluations` 
            SET `score` = CASE 
                WHEN `score` = 4 THEN 3
                WHEN `score` = 3 THEN 2
                WHEN `score` = 2 THEN 1
                WHEN `score` = 1 THEN 0
                WHEN `score` = 0 THEN NULL
                ELSE `score`
            END
        ";
        
        $affected = $pdo->exec($update_query);
        echo "<p style='color: green;'>✓ تم تحديث $affected سجل</p>";
    }
    
    // فحص نهائي
    $final_check = $pdo->query($check_query)->fetch();
    echo "<h3>الإحصائيات بعد التحديث:</h3>";
    echo "<ul>";
    echo "<li>إجمالي السجلات: {$final_check['total']}</li>";
    echo "<li>الدرجات صفر: {$final_check['zero_scores']}</li>";
    echo "<li>الدرجات NULL: {$final_check['null_scores']}</li>";
    echo "</ul>";
    
    echo "<h3 style='color: green;'>تم الإصلاح بنجاح!</h3>";
    echo "<p>يمكنك الآن استخدام نموذج التقييم بدون مشاكل.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>حدث خطأ: " . $e->getMessage() . "</p>";
    
    // حل بديل إذا فشل التعديل
    echo "<h3>حل بديل:</h3>";
    echo "<p>إذا استمر الخطأ، سنقوم بتعديل منطق النموذج ليرسل 0 بدلاً من NULL مؤقتاً.</p>";
}

echo "<p><a href='evaluation_form.php'>اختبار نموذج التقييم</a></p>";
?>
