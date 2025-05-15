<?php
/**
 * ملف إضافة البيانات الأساسية للنظام
 * 
 * هذا الملف يقوم بإضافة الشعب والمواد الدراسية والمدارس إلى قاعدة البيانات
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db_connection.php';

// عرض الترويسة
echo "<div dir='rtl' style='font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h1 style='color: #0369a1;'>إضافة البيانات الأساسية للنظام</h1>";

// متابعة التقدم
echo "<div id='progress' style='background-color: #dbeafe; padding: 10px; border-radius: 5px; margin-bottom: 15px;'></div>";

// وظيفة لتحديث تقدم العملية
function update_progress($message) {
    echo "<script>document.getElementById('progress').innerHTML += '<p>$message</p>';</script>";
    // إجبار المتصفح على إظهار التحديث فوراً
    echo str_pad('', 4096);
    ob_flush();
    flush();
}

try {
    update_progress("جاري الاتصال بقاعدة البيانات...");
    
    // 1. إضافة الشعب من 1 إلى 30 لجميع الصفوف
    update_progress("<strong>إضافة الشعب الدراسية...</strong>");
    
    // أولاً: الحصول على جميع الصفوف
    $grades = query("SELECT id, name FROM grades ORDER BY id");
    
    if (count($grades) > 0) {
        foreach ($grades as $grade) {
            $grade_id = $grade['id'];
            $grade_name = $grade['name'];
            
            // إضافة الشعب من 1 إلى 30 لهذا الصف
            for ($i = 1; $i <= 30; $i++) {
                $section_name = $i;
                
                // التحقق ما إذا كانت الشعبة موجودة بالفعل
                $existing = query_row("SELECT id FROM sections WHERE grade_id = ? AND name = ?", [$grade_id, $section_name]);
                
                if (!$existing) {
                    execute("INSERT INTO sections (name, grade_id) VALUES (?, ?)", [$section_name, $grade_id]);
                    update_progress("تمت إضافة الشعبة $section_name للصف {$grade_name}");
                } else {
                    update_progress("الشعبة $section_name موجودة بالفعل للصف {$grade_name}");
                }
            }
        }
    } else {
        update_progress("<span style='color: red;'>لم يتم العثور على صفوف في قاعدة البيانات</span>");
    }
    
    // 2. إضافة المواد الدراسية
    update_progress("<strong>إضافة المواد الدراسية...</strong>");
    
    $subjects = [
        // المواد العامة
        "اللغة العربية",
        "اللغة الإنجليزية",
        "الرياضيات",
        "الفيزياء",
        "الكيمياء",
        "الأحياء",
        "علوم الأرض والبيئة",
        "الحوسبة وتكنولوجيا المعلومات",
        "التربية الإسلامية",
        "الدراسات الاجتماعية",
        "التربية البدنية",
        "الفنون البصرية",
        "العلوم العامة",
        "التاريخ",
        "الجغرافيا",
        "الفلسفة",
        "علم النفس",
        "المهارات الحياتية"
    ];
    
    // إضافة المواد بدون تكرار
    foreach ($subjects as $subject) {
        $existing = query_row("SELECT id FROM subjects WHERE name = ?", [$subject]);
        
        if (!$existing) {
            execute("INSERT INTO subjects (name) VALUES (?)", [$subject]);
            update_progress("تمت إضافة المادة: {$subject}");
        } else {
            update_progress("المادة {$subject} موجودة بالفعل");
        }
    }
    
    // 3. إضافة المدارس
    update_progress("<strong>إضافة المدارس...</strong>");
    
    $schools = [
        "عبد الله بن على المسند الثانوية للبنين",
        "عبد الله بن على المسند الإعدادية للبنين",
        "سميسمه الثانوية للبنين",
        "سميسمه الإعدادية للبنين",
        "دخان الإبتدائية الإعدادية الثانوية للبنين",
        "الكعبان الإبتدائية الإعدادية الثانوية للبنين",
        "محمد بن جاسم الإعدادية للبنين"
    ];
    
    foreach ($schools as $school) {
        $existing = query_row("SELECT id FROM schools WHERE name = ?", [$school]);
        
        if (!$existing) {
            execute("INSERT INTO schools (name) VALUES (?)", [$school]);
            update_progress("تمت إضافة المدرسة: {$school}");
        } else {
            update_progress("المدرسة {$school} موجودة بالفعل");
        }
    }
    
    // 4. عرض ملخص البيانات المضافة
    update_progress("<strong>عملية إضافة البيانات اكتملت بنجاح</strong>");
    
    echo "<div style='background-color: #dbeafe; padding: 10px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h2>ملخص البيانات:</h2>";
    echo "<ul>";
    
    // إحصائيات الشعب
    $sections_count = query_row("SELECT COUNT(*) as count FROM sections");
    echo "<li>عدد الشعب: <strong>" . $sections_count['count'] . "</strong></li>";
    
    // إحصائيات المواد
    $subjects_count = query_row("SELECT COUNT(*) as count FROM subjects");
    echo "<li>عدد المواد الدراسية: <strong>" . $subjects_count['count'] . "</strong></li>";
    
    // إحصائيات المدارس
    $schools_count = query_row("SELECT COUNT(*) as count FROM schools");
    echo "<li>عدد المدارس: <strong>" . $schools_count['count'] . "</strong></li>";
    
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background-color: #d1fae5; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: center;'>";
    echo "<h2>تمت إضافة البيانات بنجاح!</h2>";
    echo "<p>يمكنك الآن العودة إلى <a href='index.php' style='color: #0369a1; text-decoration: none;'>الصفحة الرئيسية</a>.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background-color: #fee2e2; padding: 15px; border-radius: 5px; margin-bottom: 15px;'>";
    echo "<h2 style='color: #dc2626;'>حدث خطأ أثناء إضافة البيانات</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
// إيقاف التنفيذ ليتم عرض الخرج بشكل كامل
ob_end_flush(); 