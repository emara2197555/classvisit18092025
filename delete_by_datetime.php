<?php
/**
 * حذف التوصيات المنشأة في وقت محدد
 * الهدف: حذف التوصيات المنشأة في 2025-09-04 18:24:04
 */

require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// التوقيت المحدد لحذف التوصيات
$target_datetime = '2025-09-04 18:24:04';

echo "<h2>حذف التوصيات المنشأة في وقت محدد</h2>";
echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 10px 0; border-radius: 5px;'>";
echo "<strong>⚠️ تحذير:</strong> سيتم حذف جميع التوصيات المنشأة في: <strong>$target_datetime</strong>";
echo "</div>";

if (isset($_POST['confirm_delete'])) {
    try {
        // البحث عن التوصيات المنشأة في هذا التوقيت
        echo "<h3>🔍 البحث عن التوصيات...</h3>";
        
        $recommendations_to_delete = query("
            SELECT id, indicator_id, text, created_at 
            FROM recommendations 
            WHERE created_at = ?
        ", [$target_datetime]);
        
        if (empty($recommendations_to_delete)) {
            echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0; border-radius: 5px;'>";
            echo "<p>✅ لم يتم العثور على توصيات منشأة في هذا التوقيت المحدد</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #e3f2fd; padding: 15px; border: 1px solid #2196f3; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>📋 التوصيات المراد حذفها:</h4>";
            echo "<ul>";
            foreach ($recommendations_to_delete as $rec) {
                echo "<li><strong>ID:</strong> " . $rec['id'] . " | <strong>المؤشر:</strong> " . $rec['indicator_id'] . " | <strong>النص:</strong> " . htmlspecialchars(substr($rec['text'], 0, 50)) . "...</li>";
            }
            echo "</ul>";
            echo "<p><strong>إجمالي عدد التوصيات:</strong> " . count($recommendations_to_delete) . "</p>";
            echo "</div>";
            
            // التحقق من استخدام التوصيات في الزيارات
            echo "<h3>🔗 فحص الاستخدام في الزيارات...</h3>";
            $used_recommendations = [];
            foreach ($recommendations_to_delete as $rec) {
                $usage = query_row("SELECT COUNT(*) as count FROM visit_evaluations WHERE recommendation_id = ?", [$rec['id']]);
                if ($usage['count'] > 0) {
                    $used_recommendations[] = [
                        'id' => $rec['id'],
                        'usage_count' => $usage['count']
                    ];
                }
            }
            
            if (!empty($used_recommendations)) {
                echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #f44336; margin: 10px 0; border-radius: 5px;'>";
                echo "<h4>⚠️ تحذير: بعض التوصيات مستخدمة في الزيارات:</h4>";
                echo "<ul>";
                foreach ($used_recommendations as $used) {
                    echo "<li>التوصية ID: " . $used['id'] . " مستخدمة في " . $used['usage_count'] . " زيارة</li>";
                }
                echo "</ul>";
                echo "<p><strong>هل تريد المتابعة؟ (سيؤثر على بيانات الزيارات)</strong></p>";
                echo "</div>";
            }
            
            // تنفيذ الحذف
            echo "<h3>🗑️ تنفيذ عملية الحذف...</h3>";
            
            $deleted_count = 0;
            foreach ($recommendations_to_delete as $rec) {
                execute("DELETE FROM recommendations WHERE id = ?", [$rec['id']]);
                $deleted_count++;
            }
            
            echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>✅ تمت العملية بنجاح!</h4>";
            echo "<p>تم حذف <strong>$deleted_count</strong> توصية</p>";
            
            // عرض الإحصائيات النهائية
            $final_count = query_row("SELECT COUNT(*) as count FROM recommendations");
            echo "<p>عدد التوصيات المتبقية في النظام: <strong>" . $final_count['count'] . "</strong></p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #f44336; margin: 10px 0; border-radius: 5px;'>";
        echo "<p>❌ خطأ: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
} else {
    // عرض التحقق أولاً
    try {
        echo "<h3>🔍 فحص التوصيات المنشأة في التوقيت المحدد...</h3>";
        
        $recommendations_preview = query("
            SELECT id, indicator_id, text, created_at,
                   (SELECT name FROM evaluation_indicators WHERE id = recommendations.indicator_id) as indicator_name
            FROM recommendations 
            WHERE created_at = ?
            ORDER BY id
        ", [$target_datetime]);
        
        if (empty($recommendations_preview)) {
            echo "<div style='background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; margin: 10px 0; border-radius: 5px;'>";
            echo "<p>✅ لم يتم العثور على توصيات منشأة في التوقيت: <strong>$target_datetime</strong></p>";
            echo "</div>";
        } else {
            echo "<div style='background: #fff3cd; padding: 15px; border: 1px solid #ffc107; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>📋 التوصيات المراد حذفها:</h4>";
            echo "<p><strong>العدد:</strong> " . count($recommendations_preview) . " توصية</p>";
            echo "</div>";
            
            // عرض التوصيات في جدول
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 15px 0;'>";
            echo "<tr style='background: #f5f5f5;'>";
            echo "<th style='padding: 10px; text-align: right;'>ID</th>";
            echo "<th style='padding: 10px; text-align: right;'>معرف المؤشر</th>";
            echo "<th style='padding: 10px; text-align: right;'>اسم المؤشر</th>";
            echo "<th style='padding: 10px; text-align: right;'>نص التوصية</th>";
            echo "<th style='padding: 10px; text-align: right;'>تاريخ الإنشاء</th>";
            echo "</tr>";
            
            foreach ($recommendations_preview as $rec) {
                echo "<tr>";
                echo "<td style='padding: 8px;'>" . $rec['id'] . "</td>";
                echo "<td style='padding: 8px;'>" . $rec['indicator_id'] . "</td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars($rec['indicator_name']) . "</td>";
                echo "<td style='padding: 8px;'>" . htmlspecialchars(substr($rec['text'], 0, 80)) . "...</td>";
                echo "<td style='padding: 8px;'>" . $rec['created_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            // زر التأكيد
            echo "<form method='post' style='margin-top: 20px;'>";
            echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #f44336; margin: 10px 0; border-radius: 5px;'>";
            echo "<p><strong>تأكيد الحذف:</strong></p>";
            echo "<p>هل أنت متأكد من حذف <strong>" . count($recommendations_preview) . "</strong> توصية منشأة في <strong>$target_datetime</strong>؟</p>";
            echo "<button type='submit' name='confirm_delete' style='background: #f44336; color: white; padding: 12px 25px; border: none; border-radius: 5px; cursor: pointer; font-size: 16px;'>";
            echo "🗑️ تأكيد الحذف";
            echo "</button>";
            echo "</div>";
            echo "</form>";
        }
        
        // إحصائيات عامة
        $total_recommendations = query_row("SELECT COUNT(*) as count FROM recommendations");
        echo "<div style='background: #e3f2fd; padding: 15px; border: 1px solid #2196f3; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>📊 إحصائيات عامة:</h4>";
        echo "<p>إجمالي التوصيات في النظام: <strong>" . $total_recommendations['count'] . "</strong></p>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #f44336; margin: 10px 0; border-radius: 5px;'>";
        echo "<p>❌ خطأ في الفحص: " . $e->getMessage() . "</p>";
        echo "</div>";
    }
}
?>

<style>
body { 
    font-family: Arial, sans-serif; 
    margin: 20px; 
    direction: rtl; 
    background-color: #f8f9fa;
}
h2, h3, h4 { 
    color: #333; 
    margin-top: 20px;
}
table { 
    background: white;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
th, td { 
    border: 1px solid #ddd; 
    text-align: right;
}
th { 
    background: #f8f9fa; 
    font-weight: bold;
}
button:hover {
    background: #d32f2f !important;
    transform: translateY(-1px);
}
</style>

<div style='margin-top: 30px; padding: 15px; background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);'>
    <h4>🔗 روابط مفيدة:</h4>
    <p><a href="recommendations_management.php" style="color: #2196f3; text-decoration: none;">↩️ العودة إلى إدارة التوصيات</a></p>
    <p><a href="debug_recommendations.php" style="color: #2196f3; text-decoration: none;">🔍 فحص قاعدة البيانات</a></p>
    <p><a href="test_display.php" style="color: #2196f3; text-decoration: none;">📊 اختبار العرض</a></p>
</div>
