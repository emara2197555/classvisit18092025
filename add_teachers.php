<?php
/**
 * ملف إضافة المعلمين لكل مادة
 * 
 * يقوم هذا الملف بإضافة 5 معلمين عشوائيين لكل مادة موجودة في قاعدة البيانات
 */

// تضمين ملف الاتصال بقاعدة البيانات
require_once 'includes/db_connection.php';

// عرض الترويسة
echo "<div dir='rtl' style='font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;'>";
echo "<h1 style='color: #0369a1;'>إضافة المعلمين للمواد الدراسية</h1>";

// متابعة التقدم
echo "<div id='progress' style='background-color: #dbeafe; padding: 10px; border-radius: 5px; margin-bottom: 15px; max-height: 400px; overflow-y: auto;'></div>";

// وظيفة لتحديث تقدم العملية
function update_progress($message) {
    echo "<script>
        var progressDiv = document.getElementById('progress');
        progressDiv.innerHTML += '<p>$message</p>';
        progressDiv.scrollTop = progressDiv.scrollHeight;
    </script>";
    // إجبار المتصفح على إظهار التحديث فوراً
    echo str_pad('', 1024);
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

// دالة لإنشاء اسم عشوائي
function generate_random_name() {
    // قوائم بالأسماء الأولى والأخيرة العربية الشائعة
    $first_names = [
        // أسماء ذكور
        "محمد", "أحمد", "علي", "عبدالله", "عمر", "خالد", "يوسف", "حسن", "إبراهيم", "فهد", 
        "سعد", "ناصر", "حمد", "سلطان", "راشد", "فيصل", "عبدالرحمن", "سعود", "مشعل", "طلال",
        "بدر", "ماجد", "سامي", "جاسم", "وليد", "هاني", "نايف", "سلمان", "تركي", "صالح",
        "عادل", "مهند", "محمود", "زياد", "عصام", "أنس", "أسامة", "رامي", "باسم", "زيد"
    ];
    
    $last_names = [
        "الشمري", "العتيبي", "المطيري", "الدوسري", "القحطاني", "الهاجري", "العنزي", "الحربي", 
        "السهلي", "المالكي", "الغامدي", "الزهراني", "الشهري", "البلوي", "الرشيدي", "المحمدي", 
        "السبيعي", "الحازمي", "العمري", "الجهني", "الزبيدي", "الصيعري", "المرواني", "البقمي", 
        "الحارثي", "الغانم", "اليامي", "البلادي", "المطرفي", "السلمي", "الصاعدي", "الثبيتي", 
        "الناصر", "الفضلي", "العامري", "المحمود", "الجاسر", "الفهد", "السعيد", "المنصور"
    ];
    
    // اختيار أسماء عشوائية
    $first_name = $first_names[array_rand($first_names)];
    $last_name = $last_names[array_rand($last_names)];
    
    return $first_name . " " . $last_name;
}

try {
    update_progress("جاري الاتصال بقاعدة البيانات...");
    
    // 1. الحصول على جميع المواد الدراسية
    $subjects = query("SELECT id, name FROM subjects ORDER BY id");
    
    if (count($subjects) == 0) {
        update_progress("<span style='color: red;'>لم يتم العثور على مواد دراسية في قاعدة البيانات</span>");
        echo "</div>";
        exit;
    }
    
    // 2. الحصول على قائمة المدارس
    $schools = query("SELECT id, name FROM schools ORDER BY id");
    
    if (count($schools) == 0) {
        update_progress("<span style='color: red;'>لم يتم العثور على مدارس في قاعدة البيانات</span>");
        echo "</div>";
        exit;
    }
    
    update_progress("تم العثور على " . count($subjects) . " مادة دراسية و " . count($schools) . " مدرسة.");
    
    // 3. إضافة المعلمين لكل مادة
    update_progress("<strong>جاري إضافة المعلمين...</strong>");
    
    $total_teachers = 0;
    $total_relations = 0;
    
    foreach ($subjects as $subject) {
        $subject_id = $subject['id'];
        $subject_name = $subject['name'];
        
        update_progress("<strong>إضافة معلمين لمادة: {$subject_name}</strong>");
        
        // إضافة 5 معلمين لكل مادة
        for ($i = 1; $i <= 5; $i++) {
            // اختيار مدرسة عشوائية
            $school = $schools[array_rand($schools)];
            $school_id = $school['id'];
            $school_name = $school['name'];
            
            // إنشاء اسم عشوائي
            $teacher_name = generate_random_name();
            
            // إضافة المعلم
            execute("INSERT INTO teachers (name, school_id) VALUES (?, ?)", [$teacher_name, $school_id]);
            $teacher_id = last_insert_id();
            
            // ربط المعلم بالمادة
            execute("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)", [$teacher_id, $subject_id]);
            
            update_progress("تمت إضافة المعلم: {$teacher_name} لمادة {$subject_name} في مدرسة {$school_name}");
            
            $total_teachers++;
            $total_relations++;
        }
    }
    
    // 4. عرض ملخص البيانات المضافة
    update_progress("<strong>عملية إضافة المعلمين اكتملت بنجاح</strong>");
    
    echo "<div style='background-color: #dbeafe; padding: 10px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h2>ملخص البيانات:</h2>";
    echo "<ul>";
    echo "<li>عدد المعلمين الذين تمت إضافتهم: <strong>{$total_teachers}</strong></li>";
    echo "<li>عدد علاقات المعلم بالمادة: <strong>{$total_relations}</strong></li>";
    echo "</ul>";
    echo "</div>";
    
    // إضافة بعض الإحصائيات لكل مدرسة
    echo "<div style='background-color: #dbeafe; padding: 10px; border-radius: 5px; margin-top: 20px;'>";
    echo "<h2>إحصائيات المعلمين حسب المدرسة:</h2>";
    echo "<ul>";
    
    foreach ($schools as $school) {
        $school_id = $school['id'];
        $school_name = $school['name'];
        
        $teacher_count = query_row("SELECT COUNT(*) as count FROM teachers WHERE school_id = ?", [$school_id]);
        echo "<li>مدرسة {$school_name}: <strong>" . $teacher_count['count'] . "</strong> معلم</li>";
    }
    
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background-color: #d1fae5; padding: 15px; border-radius: 5px; margin-top: 20px; text-align: center;'>";
    echo "<h2>تمت إضافة المعلمين بنجاح!</h2>";
    echo "<p>يمكنك الآن العودة إلى <a href='index.php' style='color: #0369a1; text-decoration: none;'>الصفحة الرئيسية</a>.</p>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<div style='background-color: #fee2e2; padding: 15px; border-radius: 5px; margin-bottom: 15px;'>";
    echo "<h2 style='color: #dc2626;'>حدث خطأ أثناء إضافة المعلمين</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</div>";
?> 