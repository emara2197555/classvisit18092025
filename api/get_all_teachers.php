<?php
header('Content-Type: application/json');

// تضمين ملفات قاعدة البيانات والوظائف
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// جلب جميع المعلمين
$teachers = query("SELECT * FROM teachers WHERE job_title = 'معلم' ORDER BY name");

echo json_encode($teachers); 