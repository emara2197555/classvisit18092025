<?php
require_once 'includes/db_connection.php';

echo "=== اختبار تحميل المعلمين حسب المادة ===\n\n";

// جلب المواد المتوفرة
$subjects = query("SELECT * FROM subjects LIMIT 3");
echo "المواد المتوفرة:\n";
foreach ($subjects as $subject) {
    echo "- ID: {$subject['id']}, Name: {$subject['name']}\n";
}

echo "\n=== اختبار الربط بين المعلمين والمواد ===\n";

// اختبار الربط
$teacher_subjects = query("SELECT ts.*, t.name as teacher_name, s.name as subject_name 
                          FROM teacher_subjects ts 
                          JOIN teachers t ON ts.teacher_id = t.id 
                          JOIN subjects s ON ts.subject_id = s.id 
                          LIMIT 5");

if (empty($teacher_subjects)) {
    echo "❌ لا يوجد ربط بين المعلمين والمواد في جدول teacher_subjects\n";
    echo "سنستخدم بديل...\n";
} else {
    echo "✅ يوجد ربط بين المعلمين والمواد:\n";
    foreach ($teacher_subjects as $ts) {
        echo "- {$ts['teacher_name']} يدرس {$ts['subject_name']}\n";
    }
}

echo "\n=== اختبار الحل البديل ===\n";

// الحل البديل: استخدام visits table
$alternative = query("SELECT DISTINCT t.id, t.name as teacher_name, s.name as subject_name
                     FROM visits v 
                     JOIN teachers t ON v.teacher_id = t.id 
                     JOIN subjects s ON v.subject_id = s.id 
                     LIMIT 5");

if (!empty($alternative)) {
    echo "✅ يمكن استخدام جدول visits كبديل:\n";
    foreach ($alternative as $alt) {
        echo "- ID: {$alt['id']}, {$alt['teacher_name']} درّس {$alt['subject_name']}\n";
    }
}
?>
