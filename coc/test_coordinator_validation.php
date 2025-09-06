<?php
require_once 'includes/functions.php';
require_once 'includes/auth_functions.php';

// محاكاة جلسة منسق مادة
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role_name'] = 'Subject Coordinator';
$_SESSION['school_id'] = 1;

echo "<h2>اختبار قيود المنسق في إنشاء الزيارات</h2>";

// بيانات وهمية لاختبار الحفظ
$current_user_role = 'Subject Coordinator';
$current_user_id = 1;
$school_id = 1;
$teacher_id = 384; // معلم من مادة اللغة العربية
$subject_id = 1; // اللغة العربية
$visitor_type_id = 15; // منسق المادة
$visitor_person_id = 383; // منسق اللغة العربية

echo "<h3>بيانات الاختبار:</h3>";
echo "<p>المعلم المستهدف: $teacher_id</p>";
echo "<p>المادة: $subject_id</p>";
echo "<p>نوع الزائر: منسق المادة ($visitor_type_id)</p>";
echo "<p>الزائر: $visitor_person_id</p>";

// محاكاة نفس منطق evaluation_form.php
try {
    // جلب بيانات منسق المادة
    $coordinator_data = query_row("
        SELECT subject_id 
        FROM coordinator_supervisors 
        WHERE user_id = ?
    ", [$current_user_id]);
    
    if (!$coordinator_data) {
        throw new Exception("بيانات منسق المادة غير موجودة");
    }
    
    echo "<p>مادة المنسق الحالي: {$coordinator_data['subject_id']}</p>";
    
    // التحقق من أن المعلم يُدرس المادة التي يُنسقها المستخدم الحالي
    $teacher_check = query_row("
        SELECT t.id, t.name 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.id = ? AND ts.subject_id = ?
    ", [$teacher_id, $coordinator_data['subject_id']]);
    
    $teacher_allowed = false;
    if ($teacher_check) {
        $teacher_allowed = true;
        echo "<p style='color: green;'>✓ المعلم مناسب: {$teacher_check['name']}</p>";
    } else {
        echo "<p style='color: red;'>✗ المعلم غير مناسب</p>";
    }
    
    // التحقق من نوع الزائر
    $visitor_type = query_row("SELECT name FROM visitor_types WHERE id = ?", [$visitor_type_id]);
    $visitor_allowed = false;
    
    if ($visitor_type) {
        echo "<p>نوع الزائر: {$visitor_type['name']}</p>";
        
        if ($visitor_type['name'] === 'منسق المادة') {
            // التحقق من أن المنسق الزائر يُدرس نفس المادة
            $coordinator_visitor_check = query_row("
                SELECT t.id, t.name 
                FROM teachers t
                JOIN teacher_subjects ts ON t.id = ts.teacher_id
                WHERE t.id = ? AND t.job_title = 'منسق المادة' AND ts.subject_id = ?
            ", [$visitor_person_id, $coordinator_data['subject_id']]);
            
            if ($coordinator_visitor_check) {
                $visitor_allowed = true;
                echo "<p style='color: green;'>✓ الزائر مناسب: {$coordinator_visitor_check['name']}</p>";
            } else {
                echo "<p style='color: red;'>✗ الزائر غير مناسب</p>";
            }
        }
    }
    
    if (!$visitor_allowed) {
        throw new Exception("الزائر المختار غير مناسب");
    }
    
    if (!$teacher_allowed) {
        throw new Exception("المعلم المختار خارج نطاق مادتك");
    }
    
    echo "<h3 style='color: green;'>✅ جميع التحققات نجحت - يمكن إنشاء الزيارة</h3>";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>❌ خطأ: " . $e->getMessage() . "</h3>";
}
?>
