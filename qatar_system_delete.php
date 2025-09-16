<?php
require_once 'includes/auth_functions.php';
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// حماية الصفحة
protect_page(['E-Learning Coordinator', 'Admin', 'Director', 'Academic Deputy']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: qatar_system_reports.php?error=طلب غير صالح');
    exit;
}

if (!isset($_POST['id']) || !isset($_POST['confirm']) || $_POST['confirm'] !== '1') {
    header('Location: qatar_system_reports.php?error=معرف التقييم مفقود أو لم يتم التأكيد');
    exit;
}

$evaluation_id = (int)$_POST['id'];

try {
    // التحقق من وجود التقييم
    $evaluation = query("
        SELECT qsp.*, t.name as teacher_name, s.name as subject_name 
        FROM qatar_system_performance qsp
        JOIN teachers t ON qsp.teacher_id = t.id
        JOIN subjects s ON qsp.subject_id = s.id
        WHERE qsp.id = ?
    ", [$evaluation_id]);
    
    if (empty($evaluation)) {
        header('Location: qatar_system_reports.php?error=التقييم غير موجود');
        exit;
    }
    
    $eval = $evaluation[0];
    
    // تسجيل العملية للمراجعة
    $log_message = sprintf(
        "حذف تقييم نظام قطر - المعلم: %s، المادة: %s، التاريخ: %s، بواسطة: %s",
        $eval['teacher_name'],
        $eval['subject_name'],
        date('Y/m/d', strtotime($eval['evaluation_date'])),
        $_SESSION['full_name'] ?? 'غير معروف'
    );
    
    // حذف التقييم
    $deleted = query("DELETE FROM qatar_system_performance WHERE id = ?", [$evaluation_id]);
    
    if ($deleted) {
        // تسجيل العملية في سجل النظام (إذا كان متوفراً)
        try {
            query("INSERT INTO system_logs (user_id, action, details, created_at) VALUES (?, 'delete_qatar_evaluation', ?, NOW())", 
                  [$_SESSION['user_id'] ?? 0, $log_message]);
        } catch (Exception $e) {
            // تجاهل خطأ السجل إذا كان الجدول غير موجود
        }
        
        $success_message = sprintf(
            "تم حذف تقييم %s للمعلم %s بنجاح",
            $eval['subject_name'],
            $eval['teacher_name']
        );
        
        header('Location: qatar_system_reports.php?success=' . urlencode($success_message));
    } else {
        header('Location: qatar_system_reports.php?error=فشل في حذف التقييم');
    }
    
} catch (Exception $e) {
    error_log("Qatar system delete error: " . $e->getMessage());
    header('Location: qatar_system_reports.php?error=حدث خطأ أثناء حذف التقييم: ' . $e->getMessage());
}

exit;
?>
