<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// بدء الجلسة للاختبار
start_secure_session();

echo "<h2>اختبار API المعلمين المحدث</h2>";

// إعداد معلمات الاختبار
$school_id = 1;
$subject_id = 1;

echo "<h3>اختبار API المعلمين:</h3>";
echo "المدرسة: $school_id, المادة: $subject_id<br><br>";

// محاكاة الطلب
$_GET['school_id'] = $school_id;
$_GET['subject_id'] = $subject_id;

// تغيير المجلد للوصول للـ API بشكل صحيح
$original_dir = getcwd();
chdir('api');

// استدعاء API مباشرة
try {
    ob_start();
    include 'get_teachers_by_school_subject.php';
    $api_output = ob_get_clean();
    
    // العودة للمجلد الأصلي
    chdir($original_dir);
    
    echo "<strong>مخرجات API:</strong><br>";
    echo "<pre>" . htmlspecialchars($api_output) . "</pre>";
    
    // تحليل JSON
    $api_data = json_decode($api_output, true);
    if ($api_data) {
        echo "<strong>البيانات المحللة:</strong><br>";
        echo "Success: " . ($api_data['success'] ? 'نعم' : 'لا') . "<br>";
        echo "عدد المعلمين: " . count($api_data['teachers'] ?? []) . "<br>";
        echo "الرسالة: " . htmlspecialchars($api_data['message'] ?? 'لا توجد رسالة') . "<br>";
        
        if (!empty($api_data['teachers'])) {
            echo "<strong>المعلمين:</strong><br>";
            foreach ($api_data['teachers'] as $teacher) {
                echo "- " . htmlspecialchars($teacher['name']) . " (ID: " . $teacher['id'] . ")<br>";
            }
        }
    } else {
        echo "خطأ في تحليل JSON";
    }
    
} catch (Exception $e) {
    echo "خطأ في الاختبار: " . $e->getMessage();
}

echo "<hr>";
echo "<h3>اختبار عبر cURL:</h3>";

// اختبار عبر cURL
$url = "http://localhost/classvisit/api/get_teachers_by_school_subject.php?school_id=$school_id&subject_id=$subject_id";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_HEADER, false);

$curl_result = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "<strong>نتيجة cURL:</strong><br>";
echo "<pre>" . htmlspecialchars($curl_result) . "</pre>";

$curl_data = json_decode($curl_result, true);
if ($curl_data) {
    echo "<strong>البيانات من cURL:</strong><br>";
    echo "Success: " . ($curl_data['success'] ? 'نعم' : 'لا') . "<br>";
    echo "عدد المعلمين: " . count($curl_data['teachers'] ?? []) . "<br>";
}

?>

<script>
// اختبار عبر JavaScript أيضاً
console.log('بدء اختبار JavaScript...');

fetch('api/get_teachers_by_school_subject.php?school_id=1&subject_id=1')
    .then(response => {
        console.log('HTTP Status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('بيانات JavaScript:', data);
        if (data.success) {
            console.log('عدد المعلمين:', data.teachers.length);
            data.teachers.forEach(teacher => {
                console.log('- معلم:', teacher.name);
            });
        } else {
            console.log('خطأ:', data.message);
        }
    })
    .catch(error => {
        console.error('خطأ JavaScript:', error);
    });
</script>
