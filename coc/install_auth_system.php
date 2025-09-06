<?php
/**
 * تنفيذ ملفات SQL لإنشاء نظام الصلاحيات والبيانات التجريبية
 */

require_once 'includes/db_connection.php';

function execute_sql_file($file_path, $file_name) {
    global $pdo;
    
    echo "<h3>تنفيذ ملف: $file_name</h3>";
    
    if (!file_exists($file_path)) {
        echo "<p style='color: red;'>❌ ملف SQL غير موجود: $file_path</p>";
        return false;
    }
    
    $sql_content = file_get_contents($file_path);
    
    if ($sql_content === false) {
        echo "<p style='color: red;'>❌ خطأ في قراءة ملف SQL</p>";
        return false;
    }
    
    // تقسيم الاستعلامات
    $statements = array_filter(
        array_map('trim', explode(';', $sql_content)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt) && !preg_match('/^\/\*/', $stmt);
        }
    );
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($statements as $index => $statement) {
        try {
            // تجاهل التعليقات والأسطر الفارغة
            if (empty(trim($statement)) || preg_match('/^(--|\/\*|\*)/', trim($statement))) {
                continue;
            }
            
            $pdo->exec($statement . ';');
            $success_count++;
            
            // عرض نوع الاستعلام
            if (stripos($statement, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE.*?`([^`]+)`/i', $statement, $matches);
                $table_name = $matches[1] ?? 'غير محدد';
                echo "<p style='color: green;'>✅ تم إنشاء الجدول: <strong>$table_name</strong></p>";
            } elseif (stripos($statement, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO.*?`([^`]+)`/i', $statement, $matches);
                $table_name = $matches[1] ?? 'غير محدد';
                echo "<p style='color: blue;'>📝 تم إدراج البيانات في: <strong>$table_name</strong></p>";
            } elseif (stripos($statement, 'CREATE INDEX') !== false) {
                preg_match('/CREATE INDEX.*?`([^`]+)`/i', $statement, $matches);
                $index_name = $matches[1] ?? 'غير محدد';
                echo "<p style='color: orange;'>🔍 تم إنشاء فهرس: <strong>$index_name</strong></p>";
            }
            
        } catch (PDOException $e) {
            $error_count++;
            $error_code = $e->getCode();
            $error_message = $e->getMessage();
            
            // معالجة خاصة لأخطاء المفاتيح الأجنبية
            if (strpos($error_message, 'foreign key constraint') !== false) {
                echo "<p style='color: orange;'>⚠️ تجاهل خطأ المفتاح الأجنبي (عادي في أول تثبيت): " . $error_message . "</p>";
                $error_count--; // لا نعتبر هذا خطأ فادح
            } else {
                echo "<p style='color: red;'>❌ خطأ في الاستعلام: " . $error_message . "</p>";
                
                // عرض جزء من الاستعلام للمساعدة في التشخيص
                $preview = substr(trim($statement), 0, 100) . '...';
                echo "<p style='color: red; font-size: 12px; margin-right: 20px;'>الاستعلام: $preview</p>";
            }
        }
    }
    
    echo "<div style='background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<strong>ملخص ملف $file_name:</strong><br>";
    echo "الاستعلامات الناجحة: <span style='color: green;'>$success_count</span><br>";
    echo "الاستعلامات الفاشلة: <span style='color: red;'>$error_count</span>";
    echo "</div>";
    
    return $error_count === 0;
}

try {
    echo "<div style='font-family: Arial; direction: rtl; padding: 20px; max-width: 800px; margin: 0 auto;'>";
    echo "<h1>🚀 تثبيت نظام الصلاحيات والمستخدمين</h1>";
    echo "<hr>";
    
    // فحص الجداول الأساسية المطلوبة
    echo "<h3>🔍 فحص الجداول الأساسية المطلوبة</h3>";
    $required_tables = ['schools', 'subjects', 'teachers', 'visitor_types'];
    $missing_tables = [];
    
    foreach ($required_tables as $table) {
        try {
            $result = $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
            echo "<p style='color: green;'>✅ الجدول موجود: <strong>$table</strong></p>";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ الجدول مفقود: <strong>$table</strong></p>";
            $missing_tables[] = $table;
        }
    }
    
    if (!empty($missing_tables)) {
        echo "<div style='background: #ffe6e6; padding: 15px; border: 1px solid #ff0000; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>⚠️ تحذير:</strong> الجداول التالية مفقودة ومطلوبة لعمل النظام:<br>";
        echo implode(', ', $missing_tables);
        echo "<br><br>يرجى التأكد من إعداد قاعدة البيانات الأساسية أولاً.";
        echo "</div>";
    }
    
    echo "<hr>";
    
    $files_to_execute = [
        [
            'path' => __DIR__ . '/database/user_roles_system_fixed.sql',
            'name' => 'الجداول الأساسية (user_roles_system_fixed.sql)'
        ],
        [
            'path' => __DIR__ . '/database/sample_data_fixed.sql',
            'name' => 'البيانات التجريبية (sample_data_fixed.sql)'
        ]
    ];
    
    $total_success = true;
    
    foreach ($files_to_execute as $file) {
        $result = execute_sql_file($file['path'], $file['name']);
        if (!$result) {
            $total_success = false;
        }
        echo "<hr>";
    }
    
    echo "<h2>النتيجة النهائية:</h2>";
    
    if ($total_success) {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='margin: 0 0 10px 0;'>🎉 تم إنشاء نظام الصلاحيات بنجاح!</h3>";
        echo "<p><strong>يمكنك الآن استخدام النظام مع الحسابات التالية:</strong></p>";
        echo "<table style='width: 100%; border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr style='background: #c3e6cb;'><th style='border: 1px solid #999; padding: 8px;'>النوع</th><th style='border: 1px solid #999; padding: 8px;'>اسم المستخدم</th><th style='border: 1px solid #999; padding: 8px;'>كلمة المرور</th><th style='border: 1px solid #999; padding: 8px;'>الوصف</th></tr>";
        echo "<tr><td style='border: 1px solid #999; padding: 8px;'>مدير النظام</td><td style='border: 1px solid #999; padding: 8px;'><code>admin</code></td><td style='border: 1px solid #999; padding: 8px;'><code>admin123</code></td><td style='border: 1px solid #999; padding: 8px;'>صلاحيات كاملة</td></tr>";
        echo "<tr><td style='border: 1px solid #999; padding: 8px;'>مدير المدرسة</td><td style='border: 1px solid #999; padding: 8px;'><code>director1</code></td><td style='border: 1px solid #999; padding: 8px;'><code>admin123</code></td><td style='border: 1px solid #999; padding: 8px;'>صلاحيات كاملة</td></tr>";
        echo "<tr><td style='border: 1px solid #999; padding: 8px;'>النائب الأكاديمي</td><td style='border: 1px solid #999; padding: 8px;'><code>academic1</code></td><td style='border: 1px solid #999; padding: 8px;'><code>admin123</code></td><td style='border: 1px solid #999; padding: 8px;'>صلاحيات كاملة</td></tr>";
        echo "<tr><td style='border: 1px solid #999; padding: 8px;'>منسق الرياضيات</td><td style='border: 1px solid #999; padding: 8px;'><code>coord_math</code></td><td style='border: 1px solid #999; padding: 8px;'><code>admin123</code></td><td style='border: 1px solid #999; padding: 8px;'>إدارة مادة الرياضيات</td></tr>";
        echo "<tr><td style='border: 1px solid #999; padding: 8px;'>منسق العلوم</td><td style='border: 1px solid #999; padding: 8px;'><code>coord_science</code></td><td style='border: 1px solid #999; padding: 8px;'><code>admin123</code></td><td style='border: 1px solid #999; padding: 8px;'>إدارة مادة العلوم</td></tr>";
        echo "<tr><td style='border: 1px solid #999; padding: 8px;'>معلم</td><td style='border: 1px solid #999; padding: 8px;'><code>teacher1</code></td><td style='border: 1px solid #999; padding: 8px;'><code>admin123</code></td><td style='border: 1px solid #999; padding: 8px;'>عرض البيانات الشخصية فقط</td></tr>";
        echo "</table>";
        echo "<p><a href='login.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>🔐 انتقل لصفحة تسجيل الدخول</a></p>";
        echo "<p style='color: #856404; background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px;'><strong>هام:</strong> يرجى تغيير كلمات المرور الافتراضية بعد أول تسجيل دخول!</p>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        echo "<h3 style='margin: 0 0 10px 0;'>⚠️ حدثت بعض الأخطاء أثناء التثبيت</h3>";
        echo "<p>يرجى مراجعة الأخطاء أعلاه وإصلاحها قبل المتابعة</p>";
        echo "<p>تأكد من:</p>";
        echo "<ul>";
        echo "<li>اتصال قاعدة البيانات يعمل بشكل صحيح</li>";
        echo "<li>وجود الجداول الأساسية (schools, subjects, teachers, visitor_types)</li>";
        echo "<li>صلاحيات الكتابة في قاعدة البيانات</li>";
        echo "</ul>";
        echo "</div>";
    }
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color: red; font-family: Arial; direction: rtl; padding: 20px;'>";
    echo "<h2>خطأ عام:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}
?>
