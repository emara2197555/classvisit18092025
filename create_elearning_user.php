<?php
/**
 * إنشاء مستخدم تجريبي لمنسق التعليم الإلكتروني
 */

require_once 'includes/db_connection.php';

// بيانات المستخدم التجريبي
$username = 'elearning_coordinator';
$email = 'elearning@school.edu';
$password = 'elearning123'; // كلمة مرور مؤقتة
$full_name = 'منسق التعليم الإلكتروني';
$role_id = 7; // دور منسق التعليم الإلكتروني
$school_id = 1; // المدرسة الافتراضية

// تشفير كلمة المرور
$password_hash = password_hash($password, PASSWORD_DEFAULT);

try {
    // التحقق من وجود الدور أولاً
    $role_check = query_row("SELECT id FROM user_roles WHERE id = ?", [$role_id]);
    if (!$role_check) {
        echo "خطأ: دور منسق التعليم الإلكتروني غير موجود في قاعدة البيانات!\n";
        echo "يرجى تشغيل سكريپت الإعداد أولاً.\n";
        exit;
    }
    
    // التحقق من عدم وجود المستخدم مسبقاً
    $existing_user = query_row("SELECT id FROM users WHERE username = ? OR email = ?", [$username, $email]);
    
    if ($existing_user) {
        echo "المستخدم موجود مسبقاً!\n";
        echo "اسم المستخدم: $username\n";
        echo "كلمة المرور: $password\n";
    } else {
        // إنشاء المستخدم الجديد
        $sql = "INSERT INTO users (username, email, password_hash, full_name, role_id, school_id, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, 1)";
        
        $params = [$username, $email, $password_hash, $full_name, $role_id, $school_id];
        echo "محاولة إنشاء المستخدم بالبيانات التالية:\n";
        echo "اسم المستخدم: $username\n";
        echo "البريد الإلكتروني: $email\n";
        echo "الاسم الكامل: $full_name\n";
        echo "معرف الدور: $role_id\n";
        echo "معرف المدرسة: $school_id\n\n";
        
        $result = query($sql, $params);
        
        if ($result) {
            echo "تم إنشاء مستخدم منسق التعليم الإلكتروني بنجاح!\n\n";
            echo "=== بيانات تسجيل الدخول ===\n";
            echo "اسم المستخدم: $username\n";
            echo "كلمة المرور: $password\n";
            echo "الدور: منسق التعليم الإلكتروني\n\n";
            echo "يمكنك الآن تسجيل الدخول واختبار النظام.\n";
        } else {
            echo "فشل في إنشاء المستخدم!\n";
            echo "تحقق من قاعدة البيانات والصلاحيات.\n";
        }
    }
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage() . "\n";
    echo "التفاصيل: " . $e->getTraceAsString() . "\n";
}
?>
