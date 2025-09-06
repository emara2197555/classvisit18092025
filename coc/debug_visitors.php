<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';

session_start();

echo "<h2>اختبار API الزوار</h2>";

// محاكاة منسق مادة
$_SESSION['role_name'] = 'Subject Coordinator';
$_SESSION['user_id'] = 1; // مثال

echo "<p><strong>المستخدم الحالي:</strong> " . ($_SESSION['role_name'] ?? 'غير محدد') . "</p>";
echo "<p><strong>معرف المستخدم:</strong> " . ($_SESSION['user_id'] ?? 'غير محدد') . "</p>";

// اختبار API للمنسق
echo "<h3>اختبار API للمنسق:</h3>";
$url1 = "http://localhost/classvisit/api/get_visitor_name.php?visitor_type_id=15&subject_id=1&school_id=1";
echo "<p>URL: <a href='$url1' target='_blank'>$url1</a></p>";

// اختبار API للموجه
echo "<h3>اختبار API للموجه:</h3>";
$url2 = "http://localhost/classvisit/api/get_visitor_name.php?visitor_type_id=16&subject_id=1&school_id=1";
echo "<p>URL: <a href='$url2' target='_blank'>$url2</a></p>";

// فحص بيانات coordinator_supervisors
echo "<h3>بيانات coordinator_supervisors:</h3>";
try {
    $coordinators = query("SELECT * FROM coordinator_supervisors");
    if ($coordinators) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Subject ID</th></tr>";
        foreach ($coordinators as $coord) {
            echo "<tr><td>{$coord['id']}</td><td>{$coord['user_id']}</td><td>{$coord['subject_id']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>لا توجد بيانات في coordinator_supervisors</p>";
    }
} catch (Exception $e) {
    echo "<p>خطأ: " . $e->getMessage() . "</p>";
}

// فحص بيانات المعلمين
echo "<h3>المعلمين الذين لديهم وظيفة منسق أو موجه:</h3>";
try {
    $teachers = query("SELECT id, name, job_title FROM teachers WHERE job_title IN ('منسق المادة', 'موجه المادة')");
    if ($teachers) {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Name</th><th>Job Title</th></tr>";
        foreach ($teachers as $teacher) {
            echo "<tr><td>{$teacher['id']}</td><td>{$teacher['name']}</td><td>{$teacher['job_title']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p>لا توجد معلمين بوظيفة منسق أو موجه</p>";
    }
} catch (Exception $e) {
    echo "<p>خطأ: " . $e->getMessage() . "</p>";
}
?>
