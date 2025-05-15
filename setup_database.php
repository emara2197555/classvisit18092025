<?php
/**
 * ملف إنشاء وتهيئة قاعدة البيانات للنظام
 * 
 * هذا الملف يقوم بإنشاء قاعدة البيانات وإضافة الجداول والبيانات الأولية
 */

// تحديد معلومات الاتصال بقاعدة البيانات
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'classvisit_db';

// عرض الترويسة
echo "<div dir='rtl' style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h1 style='color: #0369a1;'>أداة إنشاء قاعدة بيانات نظام الزيارات الصفية</h1>";

try {
    // الاتصال بالسيرفر بدون تحديد قاعدة بيانات
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
    
    echo "<div style='background-color: #d1fae5; padding: 10px; border-radius: 5px; margin-bottom: 15px;'>تم الاتصال بخادم قاعدة البيانات بنجاح.</div>";
    
    // محاولة إنشاء قاعدة البيانات إذا لم تكن موجودة
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<div style='background-color: #d1fae5; padding: 10px; border-radius: 5px; margin-bottom: 15px;'>تم إنشاء قاعدة البيانات <strong>$db_name</strong> بنجاح.</div>";
    
    // اختيار قاعدة البيانات
    $pdo->exec("USE `$db_name`");
    
    // قراءة ملف السكيما
    $schema_file = 'database/db_schema.sql';
    
    if (file_exists($schema_file)) {
        echo "<div style='background-color: #dbeafe; padding: 10px; border-radius: 5px; margin-bottom: 15px;'>تم العثور على ملف السكيما، جاري تنفيذ الاستعلامات...</div>";
        
        // قراءة محتوى الملف
        $sql = file_get_contents($schema_file);
        
        // تقسيم الاستعلامات بناءً على علامة ;
        $queries = explode(';', $sql);
        
        // عداد للاستعلامات التي تم تنفيذها بنجاح
        $success_count = 0;
        
        // تنفيذ كل استعلام منفصل
        foreach ($queries as $query) {
            $query = trim($query);
            
            // تجاهل الاستعلامات الفارغة والتعليقات
            if (empty($query) || strpos($query, '--') === 0) continue;
            
            // تنفيذ الاستعلام
            $pdo->exec($query);
            $success_count++;
        }
        
        echo "<div style='background-color: #d1fae5; padding: 10px; border-radius: 5px; margin-bottom: 15px;'>تم تنفيذ <strong>$success_count</strong> استعلام بنجاح.</div>";
        
        // التحقق من وجود الجداول الرئيسية
        $tables = [
            'schools',
            'educational_levels',
            'grades',
            'sections',
            'subjects',
            'teachers',
            'visitor_types',
            'evaluation_domains',
            'evaluation_indicators',
            'recommendations'
        ];
        
        $missing_tables = [];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
            if ($stmt->rowCount() == 0) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            echo "<div style='background-color: #d1fae5; padding: 10px; border-radius: 5px; margin-bottom: 15px;'>تم التحقق من وجود جميع الجداول المطلوبة.</div>";
        } else {
            echo "<div style='background-color: #fee2e2; padding: 10px; border-radius: 5px; margin-bottom: 15px;'>هناك جداول مفقودة: " . implode(', ', $missing_tables) . "</div>";
        }
    } else {
        echo "<div style='background-color: #fee2e2; padding: 10px; border-radius: 5px; margin-bottom: 15px;'>خطأ: لم يتم العثور على ملف السكيما في المسار: $schema_file</div>";
    }
    
    echo "<div style='background-color: #dbeafe; padding: 10px; border-radius: 5px; margin-bottom: 15px;'>";
    echo "<h2>ملخص العملية:</h2>";
    echo "<ul>";
    echo "<li>تم إنشاء قاعدة البيانات: <strong>$db_name</strong></li>";
    
    // عرض معلومات عن الجداول المنشأة
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<li>عدد الجداول التي تم إنشاؤها: <strong>" . count($tables) . "</strong></li>";
    echo "<li>أسماء الجداول: <strong>" . implode(', ', $tables) . "</strong></li>";
    
    // التحقق من وجود بيانات في الجداول الرئيسية
    $data_status = [];
    $key_tables = [
        'schools' => 'المدارس',
        'subjects' => 'المواد الدراسية',
        'visitor_types' => 'أنواع الزائرين',
        'evaluation_domains' => 'مجالات التقييم'
    ];
    
    foreach ($key_tables as $table => $label) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        $data_status[] = "$label: $count سجل";
    }
    
    echo "<li>البيانات الأولية: <strong>" . implode(', ', $data_status) . "</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background-color: #d1fae5; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: center;'>";
    echo "<h2>تم إنشاء قاعدة البيانات بنجاح!</h2>";
    echo "<p>يمكنك الآن استخدام النظام بشكل كامل. قم بزيارة <a href='index.php' style='color: #0369a1; text-decoration: none;'>الصفحة الرئيسية</a>.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background-color: #fee2e2; padding: 15px; border-radius: 5px; margin-bottom: 15px;'>";
    echo "<h2 style='color: #dc2626;'>حدث خطأ أثناء إنشاء قاعدة البيانات</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    
    // اقتراحات لإصلاح المشكلة
    echo "<h3>اقتراحات للحل:</h3>";
    echo "<ul>";
    echo "<li>تأكد من تشغيل خادم قاعدة البيانات MySQL/MariaDB.</li>";
    echo "<li>تأكد من صحة اسم المستخدم وكلمة المرور لقاعدة البيانات.</li>";
    echo "<li>تأكد من وجود التصاريح اللازمة لإنشاء قاعدة بيانات جديدة.</li>";
    echo "<li>تحقق من وجود الملف 'database/db_schema.sql'.</li>";
    echo "</ul>";
    echo "</div>";
}

echo "</div>"; 