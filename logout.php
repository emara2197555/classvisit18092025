<?php
/**
 * صفحة تسجيل الخروج
 */

// تضمين ملفات النظام
require_once 'includes/auth_functions.php';

// تسجيل خروج المستخدم
logout_user();

// إعادة توجيه لصفحة تسجيل الدخول
header('Location: login.php?message=' . urlencode('تم تسجيل الخروج بنجاح'));
exit;
?>
