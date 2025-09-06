<?php
require_once 'includes/db_connection.php';

echo "<h2>اختبار API الزوار</h2>";

// اختبار كل نوع زائر
$visitor_types = [15, 16, 17, 18]; // IDs من البيانات السابقة

foreach ($visitor_types as $type_id) {
    echo "<h3>اختبار نوع الزائر ID: $type_id</h3>";
    
    // استدعاء API مباشرة
    $url = "http://localhost/classvisit/api/get_visitor_name.php?visitor_type_id=$type_id&subject_id=3&school_id=1";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'Cookie: ' . session_name() . '=' . session_id()
            ]
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    
    echo "الاستجابة: <pre>" . print_r($data, true) . "</pre>";
    echo "<hr>";
}

echo "<h2>اختبار المعلمين حسب المنصب</h2>";

$job_titles = ['مدير', 'النائب الأكاديمي', 'منسق المادة', 'موجه المادة'];

foreach ($job_titles as $job) {
    echo "<h3>المنصب: $job</h3>";
    $teachers = query("SELECT id, name, school_id FROM teachers WHERE job_title = ? LIMIT 5", [$job]);
    foreach ($teachers as $teacher) {
        echo "ID: {$teacher['id']} - {$teacher['name']} - المدرسة: {$teacher['school_id']}<br>";
    }
    echo "<hr>";
}
?>
