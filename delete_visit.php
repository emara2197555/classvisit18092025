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

// التحقق من صلاحيات الحذف 
$user_role = $_SESSION['role_name'] ?? '';
if (!in_array($user_role, ['Admin', 'Director', 'Academic Deputy', 'Supervisor', 'Subject Coordinator'])) {
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
    // التحقق من وجود الزيارة مع معلومات إضافية
    $visit = query_row("
        SELECT 
            v.id, 
            v.visitor_person_id, 
            v.teacher_id, 
            v.subject_id,
            v.visitor_type_id,
            vt.name as visitor_type_name,
            s.name as subject_name,
            t.name as teacher_name
        FROM visits v
        JOIN visitor_types vt ON v.visitor_type_id = vt.id
        JOIN subjects s ON v.subject_id = s.id
        JOIN teachers t ON v.teacher_id = t.id
        WHERE v.id = ?
    ", [$visit_id]);
    
    if (!$visit) {
        throw new Exception('الزيارة غير موجودة');
    }
    
    // التحقق من صلاحية المستخدم لحذف هذه الزيارة
    if ($user_role === 'Supervisor') {
        // المشرف يمكنه حذف زياراته فقط
        if ($visit['visitor_person_id'] != $_SESSION['user_id']) {
            throw new Exception('ليس لديك صلاحية لحذف هذه الزيارة');
        }
    } elseif ($user_role === 'Subject Coordinator') {
        // المنسق يمكنه حذف زيارات مادته فقط (زيارات المنسق والموجه)
        
        // التحقق من أن المنسق مسؤول عن هذه المادة
        $coordinator_subject = query_row("
            SELECT subject_id 
            FROM coordinator_supervisors 
            WHERE user_id = ?
        ", [$_SESSION['user_id']]);
        
        if (!$coordinator_subject || $coordinator_subject['subject_id'] != $visit['subject_id']) {
            throw new Exception('يمكنك حذف زيارات مادتك فقط');
        }
        
        // التحقق من أن الزيارة من نوع منسق أو موجه فقط
        if (!in_array($visit['visitor_type_name'], ['منسق المادة', 'موجه المادة'])) {
            throw new Exception('يمكنك حذف زيارات المنسق والموجه فقط. هذه الزيارة من نوع: ' . $visit['visitor_type_name']);
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
