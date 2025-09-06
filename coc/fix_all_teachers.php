<?php
require_once 'includes/db_connection.php';

echo "<h2>إصلاح جميع المعلمين بدون حسابات مستخدمين</h2>";

// البحث عن جميع المعلمين الذين لا يملكون user_id
$teachers_without_users = query("SELECT id, name, school_id FROM teachers WHERE user_id IS NULL");

echo "عدد المعلمين الذين يحتاجون إصلاح: " . count($teachers_without_users) . "<br><br>";

$fixed_count = 0;
$errors = [];

foreach ($teachers_without_users as $teacher) {
    echo "<h3>معالجة المعلم: " . $teacher['name'] . " (ID: " . $teacher['id'] . ")</h3>";
    
    // البحث عن حساب مستخدم بنفس الاسم
    $user = query_row("SELECT * FROM users WHERE full_name = ?", [$teacher['name']]);
    
    if ($user) {
        // إذا وُجد المستخدم، قم بربطه
        echo "تم العثور على حساب مستخدم موجود (ID: " . $user['id'] . ")<br>";
        $update_result = execute("UPDATE teachers SET user_id = ? WHERE id = ?", [$user['id'], $teacher['id']]);
        
        if ($update_result) {
            echo "<span style='color: green;'>تم الربط بنجاح!</span><br>";
            $fixed_count++;
        } else {
            echo "<span style='color: red;'>فشل في الربط!</span><br>";
            $errors[] = "فشل في ربط المعلم " . $teacher['name'];
        }
    } else {
        // إنشاء حساب مستخدم جديد
        echo "لا يوجد حساب مستخدم، سيتم إنشاء حساب جديد...<br>";
        
        // إنشاء اسم مستخدم فريد
        $username = 'teacher_' . $teacher['id']; // استخدام ID المعلم لضمان الفرادة
        $password_hash = password_hash('123456', PASSWORD_DEFAULT); // كلمة مرور مؤقتة
        
        $insert_user = execute("INSERT INTO users (username, password_hash, full_name, role_id, school_id, is_active, created_at) VALUES (?, ?, ?, 3, ?, 1, NOW())", 
                             [$username, $password_hash, $teacher['name'], $teacher['school_id']]);
        
        if ($insert_user) {
            $new_user_id = last_insert_id();
            echo "تم إنشاء حساب مستخدم جديد (ID: " . $new_user_id . ")<br>";
            
            // ربط المعلم بالحساب الجديد
            $update_result = execute("UPDATE teachers SET user_id = ? WHERE id = ?", [$new_user_id, $teacher['id']]);
            
            if ($update_result) {
                echo "<span style='color: green;'>تم إنشاء الحساب والربط بنجاح!</span><br>";
                echo "اسم المستخدم: " . $username . "<br>";
                echo "كلمة المرور المؤقتة: 123456<br>";
                $fixed_count++;
            } else {
                echo "<span style='color: red;'>فشل في ربط المعلم بالحساب الجديد!</span><br>";
                $errors[] = "فشل في ربط المعلم " . $teacher['name'] . " بالحساب الجديد";
            }
        } else {
            echo "<span style='color: red;'>فشل في إنشاء الحساب!</span><br>";
            $errors[] = "فشل في إنشاء حساب للمعلم " . $teacher['name'];
        }
    }
    
    echo "<hr>";
}

echo "<h2>النتائج النهائية</h2>";
echo "<p style='color: green;'><strong>تم إصلاح " . $fixed_count . " معلم بنجاح</strong></p>";

if (!empty($errors)) {
    echo "<p style='color: red;'><strong>الأخطاء:</strong></p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li style='color: red;'>" . $error . "</li>";
    }
    echo "</ul>";
}

// التحقق النهائي
$remaining_teachers = query("SELECT COUNT(*) as count FROM teachers WHERE user_id IS NULL");
echo "<p><strong>المعلمون المتبقون بدون حساب: " . $remaining_teachers[0]['count'] . "</strong></p>";

if ($remaining_teachers[0]['count'] == 0) {
    echo "<p style='color: green; font-size: 18px;'><strong>🎉 تم إصلاح جميع المعلمين بنجاح!</strong></p>";
}
?>
