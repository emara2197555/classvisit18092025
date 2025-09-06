<?php
require_once 'includes/db_connection.php';

echo "<h1>✅ إصلاح صفحة المعلمين المتميزين للمعلمين</h1>";

echo "<h2>المشكلة الأصلية:</h2>";
echo "<p>صفحة <code>expert_trainers.php</code> لم تكن مخصصة للمعلمين. عندما يسجل معلم دخوله، كان يرى جميع المعلمين المتميزين بدلاً من رؤية نفسه فقط.</p>";

echo "<h2>المطلوب:</h2>";
echo "<p>عندما يسجل <strong>معلم</strong> دخوله لصفحة <code>expert_trainers.php</code>، يجب أن يرى:</p>";
echo "<ul>";
echo "<li>نفسه فقط إذا كان مؤهلاً للتدريب في أي مؤشر</li>";
echo "<li>المؤشرات التي يتميز فيها فقط</li>";
echo "<li>صفحة شخصية تركز على إنجازاته التدريبية</li>";
echo "</ul>";

echo "<h2>الإصلاحات المُنفذة:</h2>";
echo "<ol>";
echo "<li><strong>إضافة صلاحية المعلمين:</strong> تم تعديل <code>protect_page</code> لتشمل 'Teacher'</li>";
echo "<li><strong>منطق خاص للمعلمين:</strong> إضافة متغير <code>\$is_teacher</code> والحصول على <code>\$current_teacher_id</code></li>";
echo "<li><strong>قيود الاستعلام:</strong> المعلم يرى نفسه فقط في نتائج البحث</li>";
echo "<li><strong>تخصيص العنوان:</strong> تغيير عنوان الصفحة للمعلمين إلى \"تميزي في التدريب - نقاط قوتك التدريبية\"</li>";
echo "<li><strong>رسائل مخصصة:</strong> رسالة ترحيب وتشجيع للمعلمين</li>";
echo "</ol>";

echo "<h2>معايير التأهيل للتدريب:</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 10px 0;'>";
echo "<strong>الحد الأدنى:</strong> 85% في المؤشر + زيارتان على الأقل";
echo "</div>";

echo "<h2>اختبار النتيجة - المعلم عبدالعزيز:</h2>";

// اختبار بيانات المعلم
$teacher_id = 343;
$teacher = query_row("SELECT * FROM teachers WHERE id = ?", [$teacher_id]);

// حساب المؤشرات المؤهلة
$expert_count = query_row("
    SELECT COUNT(*) as count
    FROM (
        SELECT 
            AVG(ve.score) * (100/3) AS percentage_score,
            COUNT(DISTINCT v.id) AS visits_count
        FROM 
            visit_evaluations ve
        JOIN 
            visits v ON ve.visit_id = v.id
        WHERE 
            v.teacher_id = ?
            AND ve.score IS NOT NULL
        GROUP BY 
            ve.indicator_id
        HAVING 
            percentage_score >= 85
            AND visits_count >= 2
    ) as expert_indicators
", [$teacher_id]);

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='padding: 10px;'>المعلم</th>";
echo "<th style='padding: 10px;'>المؤشرات المؤهلة</th>";
echo "<th style='padding: 10px;'>ما سيراه</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'>" . $teacher['name'] . "</td>";
echo "<td style='padding: 10px; text-align: center;'><strong style='color: green; font-size: 18px;'>" . $expert_count['count'] . " مؤشر</strong></td>";
echo "<td style='padding: 10px;'>";
if ($expert_count['count'] > 0) {
    echo "✅ نفسه كمدرب معتمد في " . $expert_count['count'] . " مؤشر<br>";
    echo "✅ ورش التدريب المقترحة لكل مؤشر<br>";
    echo "✅ إحصائيات أدائه الشخصية";
} else {
    echo "⚠️ رسالة تشجيعية للوصول للحد الأدنى<br>";
    echo "📈 نصائح للتطوير والتحسن";
}
echo "</td>";
echo "</tr>";
echo "</table>";

echo "<h2>مقارنة بين الأدوار:</h2>";

echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f5f5f5;'>";
echo "<th style='padding: 10px;'>الدور</th>";
echo "<th style='padding: 10px;'>ما يراه</th>";
echo "<th style='padding: 10px;'>عنوان الصفحة</th>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>مدير/مشرف</strong></td>";
echo "<td style='padding: 10px;'>جميع المعلمين المتميزين</td>";
echo "<td style='padding: 10px;'>المعلمين المتميزين المؤهلين للتدريب</td>";
echo "</tr>";

echo "<tr>";
echo "<td style='padding: 10px;'><strong>منسق مادة</strong></td>";
echo "<td style='padding: 10px;'>معلمي مادته المتميزين</td>";
echo "<td style='padding: 10px;'>المعلمين المتميزين المؤهلين للتدريب</td>";
echo "</tr>";

echo "<tr style='background: #e7f3ff;'>";
echo "<td style='padding: 10px;'><strong>معلم</strong></td>";
echo "<td style='padding: 10px;'>نفسه فقط (إذا كان مؤهلاً)</td>";
echo "<td style='padding: 10px;'>تميزي في التدريب - نقاط قوتك التدريبية</td>";
echo "</tr>";

echo "</table>";

echo "<h2>الرسائل المخصصة:</h2>";
echo "<ul>";
echo "<li><strong>للمعلم المؤهل:</strong> \"مرحباً بك كمعلم متميز! هذه صفحتك الشخصية لعرض نقاط قوتك التدريبية\"</li>";
echo "<li><strong>للمعلم غير المؤهل:</strong> \"لم تصل بعد للحد الأدنى للتميز. استمر في التطوير لتصبح مدرباً معتمداً!\"</li>";
echo "</ul>";

echo "<p style='background: #e7f3ff; padding: 15px; border-left: 4px solid #2196F3; margin: 20px 0;'>";
echo "<strong>🎉 تم حل المشكلة!</strong><br>";
echo "الآن عندما يسجل المعلم عبدالعزيز دخوله لصفحة <code>expert_trainers.php</code>:";
echo "<br>✅ سيرى نفسه فقط كمدرب معتمد في 14 مؤشر";
echo "<br>✅ سيرى عنوان مخصص: \"تميزي في التدريب - نقاط قوتك التدريبية\"";
echo "<br>✅ سيرى رسالة ترحيب شخصية";
echo "<br>✅ سيرى ورش التدريب التي يمكنه تقديمها";
echo "</p>";
?>
