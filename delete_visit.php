<?php
// بدء التخزين المؤقت للمخرجات
ob_start();

// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// بدء الجلسة
session_start();

// التحقق من صلاحيات المستخدم
if (!isset($_SESSION['user_id'])) {
    $_SESSION['alert_message'] = 'يجب تسجيل الدخول أولاً';
    $_SESSION['alert_type'] = 'error';
    header('Location: login.php');
    exit;
}

// التحقق من صلاحيات الحذف (المدراء والمشرفين فقط)
$user_role = $_SESSION['role_name'] ?? '';
if (!in_array($user_role, ['Admin', 'Director', 'Academic Deputy', 'Supervisor'])) {
    $_SESSION['alert_message'] = 'ليس لديك صلاحية لحذف الزيارات';
    $_SESSION['alert_type'] = 'error';
    header('Location: visits.php');
    exit;
}

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
    $visit = query_row("SELECT id, visitor_person_id, teacher_id FROM visits WHERE id = ?", [$visit_id]);
    
    if (!$visit) {
        throw new Exception('الزيارة غير موجودة');
    }
    
    // التحقق من صلاحية المستخدم لحذف هذه الزيارة
    if ($user_role === 'Supervisor') {
        // المشرف يمكنه حذف زياراته فقط (visitor_person_id يحتوي على معرف المشرف)
        if ($visit['visitor_person_id'] != $_SESSION['user_id']) {
            throw new Exception('ليس لديك صلاحية لحذف هذه الزيارة');
        }
    }
    
    // بدء معاملة قاعدة البيانات
    $pdo->beginTransaction();
    
    // حذف الزيارة (التقييمات ستُحذف تلقائياً بسبب ON DELETE CASCADE)
    $deleted_rows = execute("DELETE FROM visits WHERE id = ?", [$visit_id]);
    
    if ($deleted_rows === 0) {
        throw new Exception('فشل في حذف الزيارة');
    }
    
    // تأكيد المعاملة
    $pdo->commit();
    
    $_SESSION['alert_message'] = 'تم حذف الزيارة بنجاح';
    $_SESSION['alert_type'] = 'success';
    
} catch (Exception $e) {
    // إلغاء المعاملة في حالة الخطأ
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    $_SESSION['alert_message'] = 'حدث خطأ أثناء حذف الزيارة: ' . $e->getMessage();
    $_SESSION['alert_type'] = 'error';
}

// إعادة التوجيه إلى صفحة الزيارات
header('Location: visits.php');
exit;
?>
