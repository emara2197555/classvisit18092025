<?php
/**
 * ملف إنشاء وتهيئة قاعدة البيانات للنظام
 * 
 * هذا الملف يقوم بإنشاء قاعدة البيانات وإضافة الجداول والبيانات الأولية
 */

// تحديد معلومات الاتصال بقاعدة البيانات
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'classvisit_db';

// عرض الترويسة
echo "<div dir='rtl' style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h1 style='color: #0369a1;'>أداة إنشاء قاعدة بيانات نظام الزيارات الصفية</h1>";

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db_connection.php';

try {
    // تنفيذ ملف تعديل جدول academic_years أولاً
    $sql = file_get_contents('fix_academic_years.sql');
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($commands as $command) {
        if (!empty($command)) {
            try {
                execute($command);
                echo "تم تنفيذ الأمر بنجاح: " . substr($command, 0, 100) . "...<br>";
            } catch (Exception $e) {
                // تجاهل أخطاء عدم وجود العمود عند محاولة حذفه
                if (!strpos($e->getMessage(), "Can't DROP")) {
                    throw $e;
                }
                echo "تم تجاهل الخطأ: " . $e->getMessage() . "<br>";
            }
        }
    }
    
    // تنفيذ ملف قاعدة البيانات الرئيسي
    $sql = file_get_contents('classvisit_db.sql');
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($commands as $command) {
        if (!empty($command)) {
            try {
                execute($command);
                echo "تم تنفيذ الأمر بنجاح: " . substr($command, 0, 100) . "...<br>";
            } catch (Exception $e) {
                // تجاهل أخطاء وجود الجداول
                if (strpos($e->getMessage(), "already exists") !== false) {
                    echo "تم تجاهل الخطأ: " . $e->getMessage() . "<br>";
                    continue;
                }
                throw $e;
            }
        }
    }
    
    // تنفيذ ملف إنشاء جدول visits
    $sql = file_get_contents('create_visits.sql');
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($commands as $command) {
        if (!empty($command)) {
            try {
                execute($command);
                echo "تم تنفيذ الأمر بنجاح: " . substr($command, 0, 100) . "...<br>";
            } catch (Exception $e) {
                echo "حدث خطأ: " . $e->getMessage() . "<br>";
                // إذا كان الخطأ يتعلق بوجود الجدول، نتجاهله
                if (strpos($e->getMessage(), "Table 'visits' already exists") !== false) {
                    continue;
                }
                throw $e;
            }
        }
    }
    
    // تنفيذ ملف إنشاء جدول visit_evaluations
    $sql = file_get_contents('create_visit_evaluations.sql');
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($commands as $command) {
        if (!empty($command)) {
            try {
                execute($command);
                echo "تم تنفيذ الأمر بنجاح: " . substr($command, 0, 100) . "...<br>";
            } catch (Exception $e) {
                echo "حدث خطأ: " . $e->getMessage() . "<br>";
                // إذا كان الخطأ يتعلق بوجود الجدول، نتجاهله
                if (strpos($e->getMessage(), "Table 'visit_evaluations' already exists") !== false) {
                    continue;
                }
                throw $e;
            }
        }
    }
    
    echo "<br>تم إنشاء وإصلاح قاعدة البيانات بنجاح!";
    
} catch (Exception $e) {
    echo "حدث خطأ: " . $e->getMessage();
}

echo "</div>"; 