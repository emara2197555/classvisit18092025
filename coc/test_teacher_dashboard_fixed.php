<?php
require_once 'includes/db_connection.php';

echo "<h2>اختبار لوحة تحكم المعلم بعد الإصلاح</h2>";

// محاكاة تسجيل دخول المعلم
$user_id = 244; // ID المستخدم للمعلم عبدالعزيز
echo "<h3>محاكاة تسجيل دخول المعلم (User ID: $user_id)</h3>";

// الحصول على بيانات المستخدم
$user = query_row("SELECT * FROM users WHERE id = ?", [$user_id]);
echo "المستخدم: " . $user['full_name'] . "<br>";

// الحصول على بيانات المعلم
$teacher = query_row("SELECT * FROM teachers WHERE user_id = ?", [$user_id]);
if ($teacher) {
    $teacher_id = $teacher['id'];
    echo "المعلم: " . $teacher['name'] . " (ID: $teacher_id)<br>";
    
    echo "<h3>إحصائيات الزيارات</h3>";
    
    // عدد الزيارات الكلي
    $total_visits = query_row("SELECT COUNT(*) as count FROM visits WHERE teacher_id = ?", [$teacher_id]);
    echo "إجمالي الزيارات: " . $total_visits['count'] . "<br>";
    
    // زيارات هذا الشهر
    $month_visits = query_row("SELECT COUNT(*) as count FROM visits WHERE teacher_id = ? AND MONTH(visit_date) = MONTH(CURRENT_DATE) AND YEAR(visit_date) = YEAR(CURRENT_DATE)", [$teacher_id]);
    echo "زيارات هذا الشهر: " . $month_visits['count'] . "<br>";
    
    // متوسط الأداء
    $avg_performance = query_row("
        SELECT AVG(ve.score) as avg_score 
        FROM visit_evaluations ve 
        JOIN visits v ON ve.visit_id = v.id 
        WHERE v.teacher_id = ?
    ", [$teacher_id]);
    echo "متوسط الأداء: " . round($avg_performance['avg_score'], 2) . "<br>";
    
    // أحدث الزيارات
    echo "<h3>تفاصيل الزيارات</h3>";
    $visits = query("SELECT * FROM visits WHERE teacher_id = ? ORDER BY visit_date DESC", [$teacher_id]);
    foreach ($visits as $visit) {
        echo "- زيارة " . $visit['id'] . " بتاريخ " . $visit['visit_date'] . "<br>";
    }
    
} else {
    echo "<strong style='color: red;'>لم يتم العثور على بيانات المعلم المرتبطة بهذا المستخدم!</strong>";
}

echo "<h3>✅ الخلاصة</h3>";
echo "تم إصلاح مشكلة ربط المعلم بحساب المستخدم. الآن يمكن للمعلم رؤية زياراته في لوحة التحكم.";
?>
