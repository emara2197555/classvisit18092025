<?php
// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

try {
    // إضافة عمود العام الدراسي إلى جدول الزيارات إذا لم يكن موجودًا
    $check_column = query_row("SELECT COLUMN_NAME 
                               FROM INFORMATION_SCHEMA.COLUMNS 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'visits' 
                               AND COLUMN_NAME = 'academic_year_id'");
    
    if (!$check_column) {
        // إضافة العمود
        execute("ALTER TABLE `visits` 
                 ADD COLUMN `academic_year_id` int DEFAULT NULL AFTER `visit_date`");
        
        echo "<div style='direction: rtl; font-family: Arial; padding: 20px;'>";
        echo "<h2>تم إضافة عمود العام الدراسي إلى جدول الزيارات بنجاح!</h2>";
        
        // إنشاء مفتاح أجنبي للربط مع جدول الأعوام الدراسية
        try {
            execute("ALTER TABLE `visits`
                     ADD CONSTRAINT `visits_academic_year_id_foreign` FOREIGN KEY (`academic_year_id`) 
                     REFERENCES `academic_years` (`id`) ON DELETE RESTRICT");
            echo "<p>تم إنشاء المفتاح الأجنبي للربط مع جدول الأعوام الدراسية بنجاح.</p>";
        } catch (Exception $e) {
            echo "<p>ملاحظة: " . $e->getMessage() . "</p>";
        }
        
        // تحديث البيانات الموجودة لتعيين العام الدراسي النشط للزيارات الحالية
        $active_year = query_row("SELECT id FROM academic_years WHERE is_active = 1 LIMIT 1");
        
        if ($active_year) {
            // تنفيذ الاستعلام وتخزين عدد الصفوف المتأثرة مباشرة
            $affected_rows = execute("UPDATE `visits` v
                                      SET v.`academic_year_id` = ?
                                      WHERE v.`academic_year_id` IS NULL", [$active_year['id']]);
            
            // لا حاجة لاستخدام rowCount() حيث أن execute() تعيد عدد الصفوف المتأثرة مباشرة
            echo "<p>تم تحديث " . $affected_rows . " من سجلات الزيارات بالعام الدراسي النشط.</p>";
        } else {
            echo "<p class='text-warning'>تنبيه: لم يتم العثور على عام دراسي نشط لتحديث سجلات الزيارات.</p>";
        }
        
        echo "<p><a href='subject_performance_report.php' style='background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>العودة إلى تقرير أداء المواد</a></p>";
        echo "</div>";
    } else {
        echo "<div style='direction: rtl; font-family: Arial; padding: 20px;'>";
        echo "<h2>عمود العام الدراسي موجود بالفعل في جدول الزيارات.</h2>";
        echo "<p><a href='subject_performance_report.php' style='background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>العودة إلى تقرير أداء المواد</a></p>";
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div style='direction: rtl; font-family: Arial; padding: 20px;'>";
    echo "<h2>حدث خطأ أثناء تحديث هيكل قاعدة البيانات</h2>";
    echo "<p>تفاصيل الخطأ: " . $e->getMessage() . "</p>";
    echo "</div>";
} 