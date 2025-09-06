<?php
// تسجيل دخول وهمي كمدير لاختبار API
session_start();

// تعيين جلسة مدير
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'Admin';
$_SESSION['school_id'] = 1;

echo "<h2>اختبار API منسق المادة</h2>";

// اختبار API مع بيانات مختلفة
$test_cases = [
    'بدون مدرسة أو مادة' => 'visitor_type_id=15',
    'مع المدرسة' => 'visitor_type_id=15&school_id=1',
    'مع المادة' => 'visitor_type_id=15&subject_id=1',
    'مع المدرسة والمادة' => 'visitor_type_id=15&school_id=1&subject_id=1'
];

foreach ($test_cases as $description => $params) {
    echo "<h3>$description</h3>";
    $url = "http://localhost/classvisit/api/get_visitor_name.php?$params";
    echo "<p>URL: $url</p>";
    
    $context = stream_context_create([
        'http' => [
            'header' => "Cookie: " . session_name() . "=" . session_id()
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    echo "<pre style='background: #f5f5f5; padding: 10px;'>$response</pre>";
    
    $data = json_decode($response, true);
    if ($data && $data['success'] && !empty($data['visitors'])) {
        echo "<p style='color: green;'>✓ نجح - تم إرجاع " . count($data['visitors']) . " زائر</p>";
    } else {
        echo "<p style='color: red;'>✗ فشل - " . ($data['message'] ?? 'لا يوجد زوار') . "</p>";
    }
    echo "<hr>";
}

// فحص إضافي - هل هناك منسقين في قاعدة البيانات؟
require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

echo "<h3>فحص وجود منسقين في قاعدة البيانات:</h3>";
$count = $pdo->query("SELECT COUNT(*) FROM teachers WHERE job_title = 'منسق المادة'")->fetchColumn();
echo "<p>عدد المنسقين في قاعدة البيانات: $count</p>";

if ($count > 0) {
    $sample = $pdo->query("SELECT id, name, school_id FROM teachers WHERE job_title = 'منسق المادة' LIMIT 3")->fetchAll();
    echo "<table border='1'>";
    echo "<tr><th>المعرف</th><th>الاسم</th><th>المدرسة</th></tr>";
    foreach ($sample as $coord) {
        echo "<tr><td>{$coord['id']}</td><td>{$coord['name']}</td><td>{$coord['school_id']}</td></tr>";
    }
    echo "</table>";
}
?>
