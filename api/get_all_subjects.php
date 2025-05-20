<?php
header('Content-Type: application/json');

// تضمين ملفات قاعدة البيانات والوظائف
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

// جلب جميع المواد
$subjects = query("SELECT * FROM subjects ORDER BY name");

echo json_encode($subjects); 