<?php
require_once 'includes/db_connection.php';

echo "<h1>تشخيص مشكلة منسق المادة</h1>";

echo "<h2>1. التحقق من بيانات منسقي المواد في قاعدة البيانات</h2>";

// البحث عن جميع منسقي المواد
$coordinators = query("
    SELECT t.id, t.name, t.job_title, t.school_id,
           GROUP_CONCAT(s.name) as subjects,
           GROUP_CONCAT(ts.subject_id) as subject_ids
    FROM teachers t
    LEFT JOIN teacher_subjects ts ON t.id = ts.teacher_id
    LEFT JOIN subjects s ON ts.subject_id = s.id
    WHERE t.job_title = 'منسق المادة'
    GROUP BY t.id
    ORDER BY t.name
");

echo "عدد منسقي المواد الموجودين: " . count($coordinators) . "<br><br>";

foreach ($coordinators as $coord) {
    echo "<strong>{$coord['name']}</strong> (ID: {$coord['id']})<br>";
    echo "المدرسة: {$coord['school_id']}<br>";
    echo "المواد المسؤول عنها: " . ($coord['subjects'] ?: 'لا توجد مواد مربوطة') . "<br>";
    echo "معرفات المواد: " . ($coord['subject_ids'] ?: 'لا توجد') . "<br>";
    echo "<hr>";
}

echo "<h2>2. اختبار API منسق المادة للرياضيات (المادة 3)</h2>";

// اختبار API مباشرة
$test_url = "http://localhost/classvisit/includes/get_subject_coordinator.php?subject_id=3&school_id=1";
echo "رابط الاختبار: $test_url<br><br>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 10
    ]
]);

$response = @file_get_contents($test_url, false, $context);

if ($response === false) {
    echo "<span style='color: red;'>فشل في الوصول لـ API</span><br>";
    echo "الأخطاء: " . print_r(error_get_last(), true) . "<br>";
} else {
    echo "استجابة API: <pre>$response</pre>";
    
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        if (isset($data['error'])) {
            echo "<span style='color: red;'>خطأ في API: {$data['error']}</span><br>";
        } else {
            echo "عدد منسقي الرياضيات المُرجعين: " . count($data) . "<br>";
            foreach ($data as $coord) {
                echo "- {$coord['name']} (ID: {$coord['id']})<br>";
            }
        }
    } else {
        echo "<span style='color: red;'>خطأ في تحليل JSON: " . json_last_error_msg() . "</span><br>";
    }
}

echo "<h2>3. اختبار API منسق المادة مباشرة من نفس السكريپت</h2>";

try {
    $subject_id = 3;
    $school_id = 1;
    
    $sql = "SELECT DISTINCT t.id, t.name 
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE t.job_title = 'منسق المادة' 
            AND ts.subject_id = ? 
            AND t.school_id = ?
            ORDER BY t.name";
    
    $direct_coordinators = query($sql, [$subject_id, $school_id]);
    
    echo "النتيجة المباشرة: " . json_encode($direct_coordinators) . "<br>";
    echo "عدد النتائج: " . count($direct_coordinators) . "<br>";
    
    foreach ($direct_coordinators as $coord) {
        echo "- {$coord['name']} (ID: {$coord['id']})<br>";
    }
    
} catch (Exception $e) {
    echo "<span style='color: red;'>خطأ في الاستعلام المباشر: " . $e->getMessage() . "</span><br>";
}

echo "<h2>4. التحقق من ربط المعلمين بالمواد</h2>";

$teacher_subjects = query("
    SELECT t.name, t.job_title, s.name as subject_name, ts.subject_id, t.school_id
    FROM teachers t
    JOIN teacher_subjects ts ON t.id = ts.teacher_id
    JOIN subjects s ON ts.subject_id = s.id
    WHERE t.job_title IN ('منسق المادة', 'موجه المادة')
    ORDER BY t.job_title, s.name
");

echo "ربط منسقي وموجهي المواد:<br>";
foreach ($teacher_subjects as $ts) {
    echo "{$ts['name']} ({$ts['job_title']}) - {$ts['subject_name']} (ID: {$ts['subject_id']}) - المدرسة: {$ts['school_id']}<br>";
}

echo "<h2>5. إصلاح المشكلة إذا وُجدت</h2>";

// التحقق من وجود منسق للرياضيات
$math_coordinator = query_row("
    SELECT t.id, t.name 
    FROM teachers t
    JOIN teacher_subjects ts ON t.id = ts.teacher_id
    WHERE t.job_title = 'منسق المادة' 
    AND ts.subject_id = 3 
    AND t.school_id = 1
");

if (!$math_coordinator) {
    echo "<span style='color: orange;'>لا يوجد منسق للرياضيات في المدرسة 1. سأحاول إصلاح ذلك...</span><br>";
    
    // البحث عن معلم رياضيات ليصبح منسقاً
    $math_teacher = query_row("
        SELECT t.id, t.name 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE ts.subject_id = 3 
        AND t.school_id = 1 
        AND t.job_title = 'معلم'
        LIMIT 1
    ");
    
    if ($math_teacher) {
        // تحديث المعلم ليصبح منسقاً
        execute("UPDATE teachers SET job_title = 'منسق المادة' WHERE id = ?", [$math_teacher['id']]);
        echo "تم تحديث {$math_teacher['name']} ليصبح منسق مادة الرياضيات<br>";
        
        // إعادة اختبار API
        echo "<br><strong>إعادة اختبار بعد الإصلاح:</strong><br>";
        $fixed_coordinators = query("
            SELECT DISTINCT t.id, t.name 
            FROM teachers t
            JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE t.job_title = 'منسق المادة' 
            AND ts.subject_id = 3 
            AND t.school_id = 1
        ");
        
        echo "عدد منسقي الرياضيات بعد الإصلاح: " . count($fixed_coordinators) . "<br>";
        foreach ($fixed_coordinators as $coord) {
            echo "- {$coord['name']} (ID: {$coord['id']})<br>";
        }
    } else {
        echo "<span style='color: red;'>لا يوجد معلمين للرياضيات ليصبحوا منسقين!</span><br>";
    }
} else {
    echo "<span style='color: green;'>منسق الرياضيات موجود: {$math_coordinator['name']}</span><br>";
}
?>
