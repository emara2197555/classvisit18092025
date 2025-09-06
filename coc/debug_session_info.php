<?php
// Debug session variables
session_start();

echo "<h1>معلومات الجلسة الحالية</h1>";

echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";

echo "<h2>المعلمين في المادة الحالية</h2>";

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db_connection.php';

if (isset($_SESSION['subject_id']) && isset($_SESSION['school_id'])) {
    $subject_id = $_SESSION['subject_id'];
    $school_id = $_SESSION['school_id'];
    
    echo "<p>معلمي المادة رقم {$subject_id} في المدرسة رقم {$school_id}:</p>";
    
    $teachers = query("
        SELECT t.id, t.name, t.personal_id, t.email, t.job_title
        FROM teachers t
        INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE ts.subject_id = ? AND t.school_id = ?
        ORDER BY t.name
    ", [$subject_id, $school_id]);
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr><th>المعرف</th><th>الاسم</th><th>البريد الإلكتروني</th><th>المسمى الوظيفي</th></tr>";
    
    foreach ($teachers as $teacher) {
        echo "<tr>";
        echo "<td>{$teacher['id']}</td>";
        echo "<td>{$teacher['name']}</td>";
        echo "<td>{$teacher['email']}</td>";
        echo "<td>{$teacher['job_title']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
} else {
    echo "<p>لم يتم تحديد مادة أو مدرسة في الجلسة الحالية</p>";
}

echo "<hr>";
echo "<p><a href='javascript:history.back()'>العودة للخلف</a></p>";
?>
