<?php
require_once 'includes/db_connection.php';

echo "اختبار البيانات المتاحة:\n\n";

// فحص المدارس
echo "=== المدارس ===\n";
$schools = query("SELECT * FROM schools LIMIT 5");
foreach($schools as $school) {
    echo "ID: " . $school['id'] . " - اسم: " . $school['name'] . "\n";
}

// فحص المواد
echo "\n=== المواد ===\n";
$subjects = query("SELECT * FROM subjects LIMIT 5");
foreach($subjects as $subject) {
    echo "ID: " . $subject['id'] . " - اسم: " . $subject['name'] . "\n";
}

// فحص المعلمين
echo "\n=== المعلمين (أول 3) ===\n";
$teachers = query("SELECT * FROM teachers LIMIT 3");
foreach($teachers as $teacher) {
    echo "ID: " . $teacher['id'] . " - اسم: " . $teacher['name'] . "\n";
}

// فحص الصفوف
echo "\n=== الصفوف ===\n";
$grades = query("SELECT * FROM grades LIMIT 5");
foreach($grades as $grade) {
    echo "ID: " . $grade['id'] . " - اسم: " . $grade['name'] . "\n";
}

// فحص الشعب (للمدرسة الأولى والصف الأول)
if (!empty($schools) && !empty($grades)) {
    $school_id = $schools[0]['id'];
    $grade_id = $grades[0]['id'];
    echo "\n=== الشعب للمدرسة {$school_id} والصف {$grade_id} ===\n";
    $sections = query("SELECT * FROM sections WHERE school_id = ? AND grade_id = ? LIMIT 3", [$school_id, $grade_id]);
    if (!empty($sections)) {
        foreach($sections as $section) {
            echo "ID: " . $section['id'] . " - اسم: " . $section['name'] . "\n";
        }
    } else {
        echo "لا توجد شعب لهذه المدرسة والصف\n";
    }
}

// اختبار API للمعلمين حسب المادة
if (!empty($schools) && !empty($subjects)) {
    $school_id = $schools[0]['id'];
    $subject_id = $subjects[0]['id'];
    echo "\n=== اختبار API المعلمين للمدرسة {$school_id} والمادة {$subject_id} ===\n";
    
    // محاكاة استدعاء API
    $teachers_by_subject = query("
        SELECT DISTINCT t.id, t.name 
        FROM teachers t 
        INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id 
        WHERE ts.subject_id = ? 
        LIMIT 3
    ", [$subject_id]);
    
    if (!empty($teachers_by_subject)) {
        foreach($teachers_by_subject as $teacher) {
            echo "معلم ID: " . $teacher['id'] . " - اسم: " . $teacher['name'] . "\n";
        }
    } else {
        echo "لا توجد معلمين لهذه المادة (قد نحتاج لإضافة بيانات تجريبية)\n";
    }
}
?>
