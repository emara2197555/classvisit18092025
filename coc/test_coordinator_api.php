<?php
// محاكاة جلسة منسق مادة لاختبار API
session_start();

// تعيين بيانات منسق مادة وهمية
$_SESSION['user_id'] = 339; // معرف أحد المنسقين من النتائج السابقة
$_SESSION['role_name'] = 'Subject Coordinator';
$_SESSION['school_id'] = 1;

// اختبار API
$visitor_type_id = 15; // منسق المادة

echo "<h2>اختبار API لمنسق المادة</h2>";
echo "<p>معرف المستخدم: " . $_SESSION['user_id'] . "</p>";
echo "<p>الدور: " . $_SESSION['role_name'] . "</p>";

// استدعاء API
$url = "http://localhost/classvisit/api/get_visitor_name.php?visitor_type_id=" . $visitor_type_id;
echo "<p>رابط API: " . $url . "</p>";

$context = stream_context_create([
    'http' => [
        'header' => "Cookie: " . session_name() . "=" . session_id()
    ]
]);

$response = file_get_contents($url, false, $context);
echo "<h3>الاستجابة:</h3>";
echo "<pre>" . $response . "</pre>";

$data = json_decode($response, true);
if ($data && $data['success']) {
    echo "<h3>الزوار المُرجعون:</h3>";
    foreach ($data['visitors'] as $visitor) {
        echo "<p>المعرف: " . $visitor['id'] . " - الاسم: " . $visitor['name'] . "</p>";
    }
} else {
    echo "<p style='color: red;'>فشل في جلب البيانات: " . ($data['message'] ?? 'خطأ غير معروف') . "</p>";
}
?>
