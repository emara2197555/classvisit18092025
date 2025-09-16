<?php
/**
 * صفحة حذف سجل حضور التعليم الإلكتروني
 */

require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// حماية الصفحة لمنسقي التعليم الإلكتروني
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

// التحقق من طريقة الطلب
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: elearning_attendance_reports.php");
    exit;
}

// التحقق من وجود معرف السجل
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    header("Location: elearning_attendance_reports.php?error=" . urlencode('معرف السجل غير صحيح'));
    exit;
}

$attendance_id = (int)$_POST['id'];

try {
    // التحقق من وجود السجل أولاً
    $attendance_result = query("
        SELECT 
            ea.*,
            t.name as teacher_name,
            s.name as subject_name
        FROM elearning_attendance ea
        JOIN teachers t ON ea.teacher_id = t.id
        JOIN subjects s ON ea.subject_id = s.id
        WHERE ea.id = ?
    ", [$attendance_id]);
    
    $attendance = !empty($attendance_result) ? $attendance_result[0] : null;
    
    if (!$attendance) {
        header("Location: elearning_attendance_reports.php?error=" . urlencode('السجل غير موجود'));
        exit;
    }
    
    // حذف السجل
    $delete_result = query("DELETE FROM elearning_attendance WHERE id = ?", [$attendance_id]);
    
    if ($delete_result !== false) {
        // نجح الحذف
        header("Location: elearning_attendance_reports.php?success=" . urlencode('تم حذف سجل الحضور بنجاح'));
    } else {
        // فشل الحذف
        header("Location: elearning_attendance_reports.php?error=" . urlencode('حدث خطأ أثناء حذف السجل'));
    }
    
} catch (Exception $e) {
    // في حالة حدوث خطأ
    error_log("Error deleting attendance record: " . $e->getMessage());
    header("Location: elearning_attendance_reports.php?error=" . urlencode('حدث خطأ أثناء حذف السجل'));
}

exit;
?>
