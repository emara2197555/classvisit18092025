<?php
require_once 'includes/db_connection.php';

echo "<h1>اختبار وإصلاح مشكلة منسق المادة</h1>";

// إصلاح البيانات أولاً
echo "<h2>إصلاح البيانات</h2>";

// التحقق من وجود منسق للرياضيات
$math_coordinators = query("
    SELECT t.id, t.name, t.school_id
    FROM teachers t
    JOIN teacher_subjects ts ON t.id = ts.teacher_id
    WHERE t.job_title = 'منسق المادة' 
    AND ts.subject_id = 3
");

if (count($math_coordinators) == 0) {
    echo "<strong>⚠️ لا يوجد منسق للرياضيات، سأقوم بتعيين واحد...</strong><br>";
    
    // البحث عن معلم رياضيات ليصبح منسقاً
    $math_teacher = query_row("
        SELECT t.id, t.name 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE ts.subject_id = 3 AND t.school_id = 1 
        AND t.job_title = 'معلم'
        LIMIT 1
    ");
    
    if ($math_teacher) {
        execute("UPDATE teachers SET job_title = 'منسق المادة' WHERE id = ?", [$math_teacher['id']]);
        echo "✅ تم تعيين <strong>{$math_teacher['name']}</strong> كمنسق للرياضيات<br>";
        
        // إعادة جلب البيانات
        $math_coordinators = query("
            SELECT t.id, t.name, t.school_id
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE t.job_title = 'منسق المادة' 
            AND ts.subject_id = 3
        ");
    } else {
        echo "❌ لا يوجد معلمين رياضيات للترقية!<br>";
    }
}

if (count($math_coordinators) > 0) {
    echo "<span style='color: green;'>✅ يوجد " . count($math_coordinators) . " منسق للرياضيات:</span><br>";
    foreach ($math_coordinators as $coord) {
        echo "- <strong>{$coord['name']}</strong> (ID: {$coord['id']}, المدرسة: {$coord['school_id']})<br>";
    }
}

echo "<h2>اختبار API منسق المادة</h2>";

// اختبار API مباشرة
$api_url = "http://localhost/classvisit/includes/get_subject_coordinator.php?subject_id=3&school_id=1";
echo "🔗 رابط API: <a href='$api_url' target='_blank'>$api_url</a><br><br>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10,
        'ignore_errors' => true
    ]
]);

$response = @file_get_contents($api_url, false, $context);

if ($response !== false) {
    echo "📥 استجابة API:<br>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px;'>$response</pre>";
    
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($data['error'])) {
            echo "<span style='color: red;'>❌ خطأ في API: {$data['error']}</span><br>";
        } else if (is_array($data)) {
            echo "<span style='color: green;'>✅ عدد المنسقين المُرجعين: " . count($data) . "</span><br>";
            foreach ($data as $coord) {
                echo "- {$coord['name']} (ID: {$coord['id']})<br>";
            }
        }
    } else {
        echo "<span style='color: red;'>❌ خطأ في تحليل JSON: " . json_last_error_msg() . "</span><br>";
    }
} else {
    echo "<span style='color: red;'>❌ فشل في الوصول لـ API</span><br>";
    echo "الأخطاء: " . print_r(error_get_last(), true) . "<br>";
}

echo "<h2>اختبار استعلام مباشر</h2>";

try {
    $direct_result = query("
        SELECT DISTINCT t.id, t.name 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.job_title = 'منسق المادة' 
        AND ts.subject_id = 3 
        AND t.school_id = 1
        ORDER BY t.name
    ");
    
    echo "✅ نتيجة الاستعلام المباشر: " . count($direct_result) . " منسق<br>";
    foreach ($direct_result as $coord) {
        echo "- {$coord['name']} (ID: {$coord['id']})<br>";
    }
    
} catch (Exception $e) {
    echo "❌ خطأ في الاستعلام المباشر: " . $e->getMessage() . "<br>";
}

echo "<h2>🧪 اختبار evaluation_form.php</h2>";
echo "<p>الآن يمكنك اختبار النموذج:</p>";
echo "<a href='evaluation_form.php' target='_blank' style='background: #2196F3; color: white; padding: 15px 20px; border-radius: 5px; text-decoration: none; font-weight: bold;'>🔗 فتح نموذج التقييم</a>";

echo "<h2>📋 تعليمات الاختبار</h2>";
echo "<ol>";
echo "<li>اختر المدرسة (مدرسة عبد الله بن على المسند...)</li>";
echo "<li>اختر المادة (رياضيات)</li>";
echo "<li>اختر نوع الزائر (موجه المادة)</li>";
echo "<li>تحقق من ظهور اسم منسق المادة في قائمة المعلمين</li>";
echo "</ol>";
?>
