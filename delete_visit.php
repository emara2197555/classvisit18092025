<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// بدء الجلسة
session_start();

// التحقق من وجود معرف الزيارة
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['alert_message'] = 'معرف الزيارة غير صحيح';
    $_SESSION['alert_type'] = 'error';
    header('Location: visits.php');
    exit;
}

$visit_id = (int)$_GET['id'];

try {
    // التحقق من وجود الزيارة
    $visit = query_row("SELECT id FROM visits WHERE id = ?", [$visit_id]);
    
    if (!$visit) {
        throw new Exception('الزيارة غير موجودة');
    }
    
    // حذف تقييمات الزيارة أولاً
    execute("DELETE FROM visit_evaluations WHERE visit_id = ?", [$visit_id]);
    
    // حذف الزيارة
    execute("DELETE FROM visits WHERE id = ?", [$visit_id]);
    
    $_SESSION['alert_message'] = 'تم حذف الزيارة بنجاح';
    $_SESSION['alert_type'] = 'success';
} catch (Exception $e) {
    $_SESSION['alert_message'] = 'حدث خطأ أثناء حذف الزيارة: ' . $e->getMessage();
    $_SESSION['alert_type'] = 'error';
}

// إعادة التوجيه إلى صفحة الزيارات
header('Location: visits.php');
exit;
?> 