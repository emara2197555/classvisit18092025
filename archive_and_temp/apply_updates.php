<?php
// سكريبت لتطبيق التحديثات على قاعدة البيانات

require_once 'includes/db_connection.php';

echo "<h2>تطبيق تحديثات نظام التقييم الجديد</h2>";

try {
    // قراءة ملف SQL
    $sql_content = file_get_contents('update_score_field.sql');
    
    // تقسيم الأوامر
    $statements = explode(';', $sql_content);
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement) && !preg_match('/^--/', $statement)) {
            try {
                $pdo->exec($statement);
                echo "<p style='color: green;'>✓ تم تنفيذ: " . substr($statement, 0, 50) . "...</p>";
            } catch (PDOException $e) {
                echo "<p style='color: red;'>✗ خطأ في تنفيذ: " . substr($statement, 0, 50) . "...<br>الخطأ: " . $e->getMessage() . "</p>";
            }
        }
    }
    
    echo "<h3 style='color: green;'>تم الانتهاء من تطبيق التحديثات!</h3>";
    echo "<p><strong>ملاحظة:</strong> نظام التقييم الآن يستخدم:</p>";
    echo "<ul>";
    echo "<li>NULL: لم يتم قياسه (المؤشر لا ينطبق على هذا الدرس)</li>";
    echo "<li>0: الأدلة غير متوفرة أو محدودة (المؤشر غير متحقق نهائياً)</li>";
    echo "<li>1: تتوفر بعض الأدلة (تحقق جزئي بسيط للمؤشر)</li>";
    echo "<li>2: تتوفر معظم الأدلة (تحقق جيد للمؤشر مع بعض النقص)</li>";
    echo "<li>3: الأدلة مستكملة وفاعلة (تحقق ممتاز وكامل للمؤشر)</li>";
    echo "</ul>";
    
    echo "<p><strong>التقديرات النهائية:</strong></p>";
    echo "<ul>";
    echo "<li>ممتاز (90% فأكثر): أداء استثناري يفوق التوقعات</li>";
    echo "<li>جيد جداً (80-89%): أداء عالي مع تحقق معظم المعايير</li>";
    echo "<li>جيد (65-79%): أداء مرضي مع وجود مجال للتحسين</li>";
    echo "<li>مقبول (50-64%): أداء متوسط يحتاج إلى تطوير</li>";
    echo "<li>يحتاج إلى تحسين (أقل من 50%): أداء ضعيف يتطلب تدخل فوري</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>حدث خطأ: " . $e->getMessage() . "</p>";
}
?>

<a href="evaluation_form.php">اختبار نموذج التقييم الجديد</a> | 
<a href="visits.php">عرض الزيارات</a>
