<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// بدء الجلسة
start_secure_session();

echo "<h2>اختبار API المعلمين عبر الطلبات المباشرة</h2>";

// إعداد معلمات الاختبار
$school_id = 1;
$subject_id = 1;

// محاكاة تسجيل الدخول للاختبار
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'Admin';

echo "<h3>1. اختبار الاستعلام المباشر:</h3>";

try {
    // تشغيل نفس الاستعلام المستخدم في API
    $query = "
        SELECT DISTINCT t.id, t.name
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.school_id = ? AND ts.subject_id = ?
        ORDER BY t.name
    ";
    $teachers = query($query, [$school_id, $subject_id]);
    
    echo "عدد المعلمين: " . count($teachers) . "<br>";
    foreach ($teachers as $teacher) {
        echo "- " . htmlspecialchars($teacher['name']) . " (ID: " . $teacher['id'] . ")<br>";
    }
    
    // تحضير النتيجة بنفس تنسيق API
    $api_result = [
        'success' => true,
        'teachers' => $teachers,
        'message' => count($teachers) > 0 ? 'تم جلب المعلمين بنجاح' : 'لا توجد معلمين متاحين'
    ];
    
    echo "<br><strong>تنسيق API المتوقع:</strong><br>";
    echo "<pre>" . json_encode($api_result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}

echo "<hr>";
echo "<h3>2. اختبار API عبر cURL مع Session:</h3>";

// إنشاء ملف cookie مؤقت للـ session
$cookie_file = tempnam(sys_get_temp_dir(), 'test_cookie');

// تسجيل دخول أولاً
$login_url = "http://localhost/classvisit/login.php";
$api_url = "http://localhost/classvisit/api/get_teachers_by_school_subject.php?school_id=$school_id&subject_id=$subject_id";

$ch = curl_init();

// إعداد cURL
curl_setopt_array($ch, [
    CURLOPT_COOKIEJAR => $cookie_file,
    CURLOPT_COOKIEFILE => $cookie_file,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HEADER => false,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_URL => $api_url
]);

$api_result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "<strong>نتيجة API:</strong><br>";
echo "<pre>" . htmlspecialchars($api_result) . "</pre>";

// تنظيف ملف cookie
unlink($cookie_file);

// تحليل JSON
$api_data = json_decode($api_result, true);
if ($api_data) {
    echo "<strong>تحليل البيانات:</strong><br>";
    if (isset($api_data['success'])) {
        echo "Success: " . ($api_data['success'] ? 'نعم' : 'لا') . "<br>";
        if (isset($api_data['teachers'])) {
            echo "عدد المعلمين: " . count($api_data['teachers']) . "<br>";
        }
    } else if (isset($api_data['error'])) {
        echo "خطأ: " . $api_data['error'] . "<br>";
    }
}

?>

<script>
// اختبار JavaScript
console.log('اختبار JavaScript API...');

// جعل المتصفح يرسل cookies
fetch('api/get_teachers_by_school_subject.php?school_id=1&subject_id=1', {
    credentials: 'include'
})
.then(response => {
    console.log('HTTP Status:', response.status);
    return response.text();
})
.then(text => {
    console.log('Raw response:', text);
    try {
        const data = JSON.parse(text);
        console.log('Parsed data:', data);
        
        if (data.success) {
            console.log('✅ نجح:', data.message);
            console.log('عدد المعلمين:', data.teachers.length);
        } else {
            console.log('❌ فشل:', data.message || data.error);
        }
    } catch (e) {
        console.log('خطأ في تحليل JSON:', e.message);
    }
})
.catch(error => {
    console.error('خطأ الشبكة:', error);
});
</script>
