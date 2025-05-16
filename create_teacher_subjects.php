<?php
// تضمين ملفات قاعدة البيانات والوظائف
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

try {
    // إنشاء جدول teacher_subjects إذا لم يكن موجوداً
    $sql = "CREATE TABLE IF NOT EXISTS `teacher_subjects` (
        `id` int NOT NULL AUTO_INCREMENT,
        `teacher_id` int NOT NULL,
        `subject_id` int NOT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `teacher_id` (`teacher_id`),
        KEY `subject_id` (`subject_id`),
        CONSTRAINT `teacher_subjects_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `teachers` (`id`) ON DELETE CASCADE,
        CONSTRAINT `teacher_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `subjects` (`id`) ON DELETE CASCADE,
        UNIQUE KEY `teacher_subject_unique` (`teacher_id`, `subject_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    execute($sql);
    
    echo "<div style='direction: rtl; font-family: Arial; padding: 20px;'>";
    echo "<h2>تم إنشاء جدول العلاقات بين المعلمين والمواد بنجاح!</h2>";
    
    // حذف العلاقات القديمة إن وجدت
    execute("TRUNCATE TABLE `teacher_subjects`");
    echo "<p>تم تهيئة جدول العلاقات.</p>";
    
    // إضافة العلاقات بين المنسقين والمواد (منسق المادة)
    $coordinators = query("SELECT id FROM teachers WHERE job_title = 'منسق المادة'");
    $subjects = query("SELECT id FROM subjects");
    
    // ربط كل منسق مع مادة واحدة (توزيع المواد على المنسقين)
    if (count($coordinators) > 0 && count($subjects) > 0) {
        foreach ($coordinators as $index => $coordinator) {
            // حساب رقم المادة المناسبة (توزيع دائري)
            $subject_index = $index % count($subjects);
            $subject = $subjects[$subject_index];
            
            execute("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)", 
                    [$coordinator['id'], $subject['id']]);
            
            echo "<p>تم ربط المنسق رقم " . $coordinator['id'] . " بالمادة رقم " . $subject['id'] . "</p>";
        }
    }
    
    // إضافة العلاقات بين المُوجهين والمواد (موجه المادة)
    $supervisors = query("SELECT id FROM teachers WHERE job_title = 'موجه المادة'");
    
    // ربط كل موجه مع مادة واحدة (توزيع المواد على الموجهين)
    if (count($supervisors) > 0 && count($subjects) > 0) {
        foreach ($supervisors as $index => $supervisor) {
            // حساب رقم المادة المناسبة (توزيع دائري)
            $subject_index = $index % count($subjects);
            $subject = $subjects[$subject_index];
            
            execute("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)", 
                    [$supervisor['id'], $subject['id']]);
            
            echo "<p>تم ربط الموجه رقم " . $supervisor['id'] . " بالمادة رقم " . $subject['id'] . "</p>";
        }
    }
    
    // إضافة العلاقات بين المعلمين والمواد
    $teachers = query("SELECT id FROM teachers WHERE job_title = 'معلم'");
    
    // ربط كل معلم بمادة واحدة أو أكثر (توزيع عشوائي)
    if (count($teachers) > 0 && count($subjects) > 0) {
        foreach ($teachers as $teacher) {
            // اختيار عدد عشوائي من المواد لكل معلم (1-3)
            $subjects_count = rand(1, min(3, count($subjects)));
            
            // اختيار مواد عشوائية للمعلم
            $selected_subjects = array_rand(array_flip(array_column($subjects, 'id')), $subjects_count);
            if (!is_array($selected_subjects)) {
                $selected_subjects = [$selected_subjects];
            }
            
            foreach ($selected_subjects as $subject_id) {
                execute("INSERT INTO teacher_subjects (teacher_id, subject_id) VALUES (?, ?)", 
                        [$teacher['id'], $subject_id]);
                
                echo "<p>تم ربط المعلم رقم " . $teacher['id'] . " بالمادة رقم " . $subject_id . "</p>";
            }
        }
    }
    
    echo "<p><a href='evaluation_form.php' style='background: #4CAF50; color: white; padding: 10px 15px; text-decoration: none; border-radius: 4px;'>العودة إلى نموذج التقييم</a></p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='direction: rtl; font-family: Arial; padding: 20px;'>";
    echo "<h2>حدث خطأ أثناء إنشاء وتحديث جدول العلاقات</h2>";
    echo "<p>تفاصيل الخطأ: " . $e->getMessage() . "</p>";
    echo "</div>";
} 