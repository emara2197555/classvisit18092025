<?php
/**
 * حذف زيارة - نسخة بديلة مبسطة
 */

session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// التحقق من وجود معرف الزيارة
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['alert_message'] = 'معرف الزيارة غير صحيح';
    $_SESSION['alert_type'] = 'error';
    header('Location: visits.php');
    exit;
}

$visit_id = (int)$_GET['id'];
$user_role = $_SESSION['role_name'] ?? '';

// التحقق من الصلاحيات
$allowed_roles = ['Admin', 'Director', 'Academic Deputy', 'Supervisor'];
if (!in_array($user_role, $allowed_roles)) {
    $_SESSION['alert_message'] = 'ليس لديك صلاحية لحذف الزيارات';
    $_SESSION['alert_type'] = 'error';
    header('Location: visits.php');
    exit;
}

// تضمين قاعدة البيانات
require_once 'includes/db_connection.php';

try {
    // التحقق من وجود الزيارة
    $stmt = $pdo->prepare("SELECT id, visitor_person_id FROM visits WHERE id = ?");
    $stmt->execute([$visit_id]);
    $visit = $stmt->fetch();
    
    if (!$visit) {
        throw new Exception('الزيارة غير موجودة');
    }
    
    // إذا كان مشرف، تحقق من أنها زيارته
    if ($user_role === 'Supervisor' && $visit['visitor_person_id'] != $_SESSION['user_id']) {
        throw new Exception('لا يمكنك حذف زيارات المشرفين الآخرين');
    }
    
    // حذف الزيارة (التقييمات ستُحذف تلقائياً)
    $stmt = $pdo->prepare("DELETE FROM visits WHERE id = ?");
    $result = $stmt->execute([$visit_id]);
    
    if ($result && $stmt->rowCount() > 0) {
        $_SESSION['alert_message'] = 'تم حذف الزيارة بنجاح';
        $_SESSION['alert_type'] = 'success';
    } else {
        throw new Exception('فشل في حذف الزيارة');
    }
    
} catch (Exception $e) {
    $_SESSION['alert_message'] = 'خطأ: ' . $e->getMessage();
    $_SESSION['alert_type'] = 'error';
}

header('Location: visits.php');
exit;
?>
