<?php
session_start();

// إعداد جلسة مؤقتة للاختبار
$_SESSION['user_id'] = 343;
$_SESSION['role_name'] = 'Subject Coordinator';

require_once 'includes/db_connection.php';

echo "<h2>اختبار مشكلة evaluation_form.php</h2>";

echo "<h3>1. أنواع الزوار الموجودة:</h3>";
$visitor_types = query('SELECT id, name FROM visitor_types ORDER BY id');
foreach ($visitor_types as $type) {
    echo "ID: {$type['id']} - النوع: {$type['name']}<br>";
}

echo "<h3>2. اختبار API get_visitor_name.php مباشرة:</h3>";

// محاكاة استدعاءات API
$test_cases = [
    ['visitor_type_id' => 15, 'subject_id' => 3, 'school_id' => 1], // منسق المادة
    ['visitor_type_id' => 16, 'subject_id' => 3, 'school_id' => 1], // موجه المادة
    ['visitor_type_id' => 17, 'school_id' => 1], // النائب الأكاديمي
    ['visitor_type_id' => 18, 'school_id' => 1], // مدير
];

foreach ($test_cases as $index => $params) {
    echo "<h4>اختبار " . ($index + 1) . ":</h4>";
    echo "المعاملات: " . json_encode($params) . "<br>";
    
    // تضمين ملف API مباشرة مع محاكاة $_GET
    $_GET = $params;
    
    ob_start();
    include 'api/get_visitor_name.php';
    $response = ob_get_clean();
    
    echo "الاستجابة: <pre>$response</pre>";
    echo "<hr>";
}

echo "<h3>3. التحقق من بيانات المعلمين:</h3>";

$coordinators = query("
    SELECT t.id, t.name, t.school_id, ts.subject_id 
    FROM teachers t
    JOIN teacher_subjects ts ON t.id = ts.teacher_id
    WHERE t.job_title = 'منسق المادة' 
    AND ts.subject_id = 3 
    AND t.school_id = 1
");

echo "منسقو الرياضيات في المدرسة 1:<br>";
foreach ($coordinators as $coord) {
    echo "ID: {$coord['id']} - {$coord['name']} - المادة: {$coord['subject_id']}<br>";
}

$supervisors = query("
    SELECT t.id, t.name, t.school_id, ts.subject_id 
    FROM teachers t
    JOIN teacher_subjects ts ON t.id = ts.teacher_id
    WHERE t.job_title = 'موجه المادة' 
    AND ts.subject_id = 3
");

echo "<br>موجهو الرياضيات:<br>";
foreach ($supervisors as $sup) {
    echo "ID: {$sup['id']} - {$sup['name']} - المادة: {$sup['subject_id']}<br>";
}
?>
