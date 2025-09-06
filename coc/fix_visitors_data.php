<?php
require_once 'includes/db_connection.php';

echo "<h2>إضافة بيانات الموجهين ومنسقي المواد</h2>";

try {
    // التحقق من وجود موجهي المواد
    $supervisors_count = query_row("SELECT COUNT(*) as count FROM teachers WHERE job_title = 'موجه المادة'");
    echo "عدد موجهي المواد الحاليين: " . $supervisors_count['count'] . "<br>";
    
    if ($supervisors_count['count'] == 0) {
        echo "إضافة موجهين للمواد...<br>";
        
        // إضافة موجهين للمواد المختلفة
        $subjects = query("SELECT id, name FROM subjects LIMIT 5");
        
        foreach ($subjects as $subject) {
            $supervisor_name = "موجه " . $subject['name'];
            
            // إضافة الموجه إلى جدول teachers
            $teacher_id = execute("
                INSERT INTO teachers (name, email, job_title, school_id) 
                VALUES (?, ?, 'موجه المادة', 1)
            ", [$supervisor_name, strtolower(str_replace(' ', '', $subject['name'])), 'موجه المادة']);
            
            // ربط الموجه بالمادة
            execute("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)", 
                   [$teacher_id, $subject['id']]);
            
            echo "تم إضافة $supervisor_name للمادة {$subject['name']}<br>";
        }
    }
    
    // التحقق من منسقي المواد
    $coordinators_count = query_row("SELECT COUNT(*) as count FROM teachers WHERE job_title = 'منسق المادة'");
    echo "<br>عدد منسقي المواد الحاليين: " . $coordinators_count['count'] . "<br>";
    
    // التحقق من ربط المنسقين بالمواد
    $coordinator_subjects = query("
        SELECT t.id, t.name, ts.subject_id, s.name as subject_name
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        JOIN subjects s ON ts.subject_id = s.id
        WHERE t.job_title = 'منسق المادة'
    ");
    
    echo "<br>منسقو المواد المربوطين:<br>";
    foreach ($coordinator_subjects as $cs) {
        echo "المنسق: {$cs['name']} - المادة: {$cs['subject_name']}<br>";
    }
    
    // إذا لم يكن محمد منسقاً للرياضيات، أضفه
    $math_coordinator = query_row("
        SELECT t.id 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.job_title = 'منسق المادة' 
        AND ts.subject_id = 3 
        AND t.name LIKE '%محمد مصطفى%'
    ");
    
    if (!$math_coordinator) {
        echo "<br>ربط محمد مصطفى كمنسق للرياضيات...<br>";
        
        $mohammed_id = query_row("SELECT id FROM teachers WHERE name LIKE '%محمد مصطفى%'");
        if ($mohammed_id) {
            // تحديث المنصب
            execute("UPDATE teachers SET job_title = 'منسق المادة' WHERE id = ?", [$mohammed_id['id']]);
            
            // ربط بالمادة إذا لم يكن مربوطاً
            $existing = query_row("SELECT id FROM teacher_subjects WHERE teacher_id = ? AND subject_id = 3", [$mohammed_id['id']]);
            if (!$existing) {
                execute("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, 3)", [$mohammed_id['id']]);
            }
            
            echo "تم ربط محمد مصطفى بمادة الرياضيات<br>";
        }
    }
    
    echo "<br><strong>اختبار API الآن:</strong><br>";
    
    // اختبار API للموجهين
    $supervisors = query("
        SELECT t.id, t.name 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.job_title = 'موجه المادة' 
        AND ts.subject_id = 3
    ");
    
    echo "موجهو الرياضيات: " . count($supervisors) . "<br>";
    foreach ($supervisors as $sup) {
        echo "- {$sup['name']} (ID: {$sup['id']})<br>";
    }
    
    // اختبار API للمنسقين
    $coordinators = query("
        SELECT t.id, t.name 
        FROM teachers t
        JOIN teacher_subjects ts ON t.id = ts.teacher_id
        WHERE t.job_title = 'منسق المادة' 
        AND ts.subject_id = 3 
        AND t.school_id = 1
    ");
    
    echo "<br>منسقو الرياضيات في المدرسة 1: " . count($coordinators) . "<br>";
    foreach ($coordinators as $coord) {
        echo "- {$coord['name']} (ID: {$coord['id']})<br>";
    }

} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
