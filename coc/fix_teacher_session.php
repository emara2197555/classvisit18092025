<?php
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// بدء الجلسة للتحقق من البيانات
start_secure_session();

echo "<h2>فحص جلسة المعلم الحالية</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    echo "<h3>بيانات المستخدم</h3>";
    $user = query_row("SELECT * FROM users WHERE id = ?", [$user_id]);
    print_r($user);
    
    echo "<h3>البحث عن المعلم المرتبط بهذا المستخدم</h3>";
    $teacher = query_row("SELECT * FROM teachers WHERE user_id = ?", [$user_id]);
    if ($teacher) {
        echo "تم العثور على المعلم:<br>";
        print_r($teacher);
        
        $teacher_id = $teacher['id'];
        echo "<h3>زيارات هذا المعلم</h3>";
        $visits = query("SELECT * FROM visits WHERE teacher_id = ?", [$teacher_id]);
        echo "عدد الزيارات: " . count($visits) . "<br>";
        
    } else {
        echo "<strong style='color: red;'>لم يتم العثور على معلم مرتبط بهذا المستخدم!</strong><br>";
        
        // البحث عن المعلم باستخدام الاسم
        echo "<h3>البحث عن المعلم باستخدام الاسم من الجلسة</h3>";
        $full_name = $_SESSION['full_name'];
        echo "الاسم في الجلسة: " . $full_name . "<br>";
        
        $teacher_by_name = query_row("SELECT * FROM teachers WHERE name = ?", [$full_name]);
        if ($teacher_by_name) {
            echo "تم العثور على المعلم بالاسم:<br>";
            print_r($teacher_by_name);
            
            echo "<h3>تحديث user_id في جدول المعلمين</h3>";
            $update_result = query("UPDATE teachers SET user_id = ? WHERE id = ?", [$user_id, $teacher_by_name['id']]);
            echo "تم تحديث البيانات!<br>";
            
            // تحديث الجلسة
            $_SESSION['teacher_id'] = $teacher_by_name['id'];
            echo "تم تحديث teacher_id في الجلسة: " . $teacher_by_name['id'] . "<br>";
            
        } else {
            echo "لم يتم العثور على المعلم حتى بالاسم!<br>";
        }
    }
} else {
    echo "لا توجد جلسة نشطة. يرجى تسجيل الدخول أولاً.";
}
?>
