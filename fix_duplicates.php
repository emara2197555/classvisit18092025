<?php
/**
 * سكريپت قوي لحذف التوصيات المكررة
 * يحذف التوصيات المكررة بشكل نهائي
 */

require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "<h2>حذف التوصيات المكررة - نسخة محسّنة</h2>";

if (isset($_POST['confirm_delete'])) {
    try {
        echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0;'>";
        echo "<h3>بدء عملية الحذف...</h3>";
        
        // طريقة أكثر دقة لحذف المكررات
        $sql = "
        DELETE r1 FROM recommendations r1
        INNER JOIN recommendations r2 
        WHERE r1.id > r2.id 
        AND r1.indicator_id = r2.indicator_id 
        AND r1.text = r2.text
        ";
        
        $deleted_count = execute($sql);
        echo "<p>✅ تم حذف التوصيات المكررة بنجاح</p>";
        echo "<p>عدد الصفوف المحذوفة: " . $deleted_count . "</p>";
        
        // عرض الإحصائيات النهائية
        $final_count = query_row("SELECT COUNT(*) as count FROM recommendations");
        echo "<p>عدد التوصيات المتبقية: " . $final_count['count'] . "</p>";
        
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #f44336; margin: 10px 0;'>";
        echo "<p>❌ خطأ: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    // عرض التحقق أولاً
    try {
        $duplicates = query("
            SELECT r1.indicator_id, r1.text, COUNT(*) as count
            FROM recommendations r1
            INNER JOIN recommendations r2 
            WHERE r1.id > r2.id 
            AND r1.indicator_id = r2.indicator_id 
            AND r1.text = r2.text
            GROUP BY r1.indicator_id, r1.text
        ");
        
        if (empty($duplicates)) {
            echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0;'>";
            echo "<p>✅ لا توجد توصيات مكررة للحذف</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 10px 0;'>";
            echo "<h3>⚠️ تم العثور على توصيات مكررة:</h3>";
            echo "<p>عدد المجموعات المكررة: " . count($duplicates) . "</p>";
            
            echo "<form method='post' style='margin-top: 15px;'>";
            echo "<button type='submit' name='confirm_delete' style='background: #f44336; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>";
            echo "تأكيد حذف التوصيات المكررة";
            echo "</button>";
            echo "</form>";
            echo "</div>";
        }
        
        // عرض إحصائيات عامة
        $total = query_row("SELECT COUNT(*) as count FROM recommendations");
        echo "<div style='background: #e3f2fd; padding: 15px; border: 1px solid #2196f3; margin: 10px 0;'>";
        echo "<h3>📊 إحصائيات:</h3>";
        echo "<p>إجمالي التوصيات: " . $total['count'] . "</p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #f44336; margin: 10px 0;'>";
        echo "<p>❌ خطأ في الفحص: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; direction: rtl; }
h2, h3 { color: #333; }
</style>

<p><a href="recommendations_management.php">العودة إلى إدارة التوصيات</a></p>
<p><a href="debug_recommendations.php">فحص مفصل للتوصيات</a></p>
