<?php
require_once 'includes/db_connection.php';

echo "<h2>إصلاح مشكلة ربط المعلم بالمستخدم</h2>";

// البحث عن المعلم عبدالعزيز
$teacher_name = "عبدالعزيز معوض عبدالعزيز  علي"; // لاحظ المسافة الإضافية
echo "<h3>البحث عن المعلم: " . $teacher_name . "</h3>";

$teacher = query_row("SELECT * FROM teachers WHERE name = ?", [$teacher_name]);
if ($teacher) {
    echo "تم العثور على المعلم:<br>";
    echo "ID: " . $teacher['id'] . "<br>";
    echo "الاسم: " . $teacher['name'] . "<br>";
    echo "User ID: " . ($teacher['user_id'] ?? 'NULL') . "<br><br>";
    
    // البحث عن حساب مستخدم بنفس الاسم
    echo "<h3>البحث عن حساب مستخدم بنفس الاسم</h3>";
    $user = query_row("SELECT * FROM users WHERE full_name = ?", [$teacher_name]);
    
    if ($user) {
        echo "تم العثور على حساب مستخدم:<br>";
        echo "User ID: " . $user['id'] . "<br>";
        echo "الاسم: " . $user['full_name'] . "<br>";
        echo "الدور: " . $user['role'] . "<br><br>";
        
        if ($teacher['user_id'] == null) {
            echo "<h3>ربط المعلم بالمستخدم</h3>";
            $update_result = execute("UPDATE teachers SET user_id = ? WHERE id = ?", [$user['id'], $teacher['id']]);
            if ($update_result) {
                echo "<strong style='color: green;'>تم ربط المعلم بالمستخدم بنجاح!</strong><br>";
                
                // التحقق من النتيجة
                $updated_teacher = query_row("SELECT * FROM teachers WHERE id = ?", [$teacher['id']]);
                echo "User ID الجديد: " . $updated_teacher['user_id'] . "<br>";
            } else {
                echo "<strong style='color: red;'>فشل في ربط المعلم بالمستخدم!</strong><br>";
            }
        } else {
            echo "المعلم مربوط بالفعل بمستخدم (ID: " . $teacher['user_id'] . ")<br>";
        }
        
    } else {
        echo "<strong style='color: orange;'>لم يتم العثور على حساب مستخدم بنفس الاسم!</strong><br>";
        echo "إنشاء حساب مستخدم جديد للمعلم:<br>";
        
        // إنشاء حساب مستخدم جديد للمعلم
        $username = str_replace(' ', '', $teacher_name); // إزالة المسافات
        $password = password_hash('123456', PASSWORD_DEFAULT); // كلمة مرور مؤقتة
        
        $insert_user = execute("INSERT INTO users (username, password_hash, full_name, role, created_at) VALUES (?, ?, ?, 'teacher', NOW())", 
                           [$username, $password, $teacher_name]);
        
        if ($insert_user) {
            $new_user_id = last_insert_id();
            echo "تم إنشاء حساب مستخدم جديد (ID: " . $new_user_id . ")<br>";
            
            // ربط المعلم بالمستخدم الجديد
            $update_result = execute("UPDATE teachers SET user_id = ? WHERE id = ?", [$new_user_id, $teacher['id']]);
            if ($update_result) {
                echo "<strong style='color: green;'>تم ربط المعلم بالحساب الجديد بنجاح!</strong><br>";
                echo "اسم المستخدم: " . $username . "<br>";
                echo "كلمة المرور المؤقتة: 123456<br>";
            }
        } else {
            echo "<strong style='color: red;'>فشل في إنشاء حساب المستخدم!</strong><br>";
        }
    }
    
    // التحقق من عدد الزيارات بعد الإصلاح
    echo "<h3>التحقق من زيارات المعلم</h3>";
    $visits = query("SELECT COUNT(*) as visit_count FROM visits WHERE teacher_id = ?", [$teacher['id']]);
    $visit_count = $visits[0]['visit_count'];
    echo "عدد الزيارات الكلي: " . $visit_count . "<br>";
    
} else {
    echo "<strong style='color: red;'>لم يتم العثور على المعلم!</strong><br>";
}

// التحقق من جميع المعلمين الذين ليس لديهم user_id
echo "<h3>المعلمون الذين لا يملكون حساب مستخدم</h3>";
$teachers_without_users = query("SELECT id, name FROM teachers WHERE user_id IS NULL LIMIT 10");
echo "عدد المعلمين بدون حساب: " . count($teachers_without_users) . "<br>";
foreach ($teachers_without_users as $t) {
    echo "- " . $t['name'] . " (ID: " . $t['id'] . ")<br>";
}
?>
