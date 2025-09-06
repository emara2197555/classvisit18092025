<?php
// Debug script to test coordinator data
session_start();

// Manual session setup for testing
$_SESSION['user_id'] = 240;
$_SESSION['username'] = 'm.ali0308';
$_SESSION['role_name'] = 'Subject Coordinator';
$_SESSION['role_id'] = 5;
$_SESSION['school_id'] = 1;
$_SESSION['full_name'] = 'محمد مصطفى عبداللطيف علي';
$_SESSION['subject_id'] = 3;

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db_connection.php';

echo "<h1>بيانات تجريبية للمنسق</h1>";
echo "<h2>متغيرات الجلسة</h2>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

echo "<h2>المعلمين في المادة رقم 3 (رياضيات)</h2>";
$teachers = query("
    SELECT t.id, t.name, t.email, t.school_id, ts.subject_id
    FROM teachers t
    INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id
    WHERE ts.subject_id = ? AND t.school_id = ?
", [3, 1]);

echo "<pre>";
var_dump($teachers);
echo "</pre>";

echo "<h2>المواد المتاحة</h2>";
$subjects = query("SELECT * FROM subjects");
echo "<pre>";
var_dump($subjects);
echo "</pre>";

echo "<h2>المدارس المتاحة</h2>";
$schools = query("SELECT * FROM schools");
echo "<pre>";
var_dump($schools);
echo "</pre>";
?>
