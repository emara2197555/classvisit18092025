<?php
// سكريبت فحص وإصلاح النسب المئوية في جميع الملفات

echo "<h2>فحص وإصلاح النسب المئوية</h2>";

$files_to_check = [
    'evaluation_form.php',
    'view_visit.php', 
    'class_performance_report.php',
    'teacher_report.php',
    'subject_performance_report.php',
    'section_report.php',
    'training_needs.php',
    'api/get_dashboard_data.php',
    'api/get_previous_visits.php'
];

$patterns_to_fix = [
    '* 25' => '/ 3 * 100',
    'score > 0' => 'score IS NOT NULL',
    '* 4\)' => '* 3)',
    '/4\)' => '/3)',
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        echo "<h3>فحص ملف: $file</h3>";
        $content = file_get_contents($file);
        $original_content = $content;
        
        foreach ($patterns_to_fix as $old => $new) {
            $count = 0;
            $content = preg_replace('/' . preg_quote($old, '/') . '/', $new, $content, -1, $count);
            if ($count > 0) {
                echo "<p style='color: orange;'>تم العثور على $count حالة من النمط '$old' وإصلاحها</p>";
            }
        }
        
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            echo "<p style='color: green;'>✓ تم حفظ التحديثات في $file</p>";
        } else {
            echo "<p style='color: blue;'>ℹ️ الملف $file لا يحتاج تحديث</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ الملف $file غير موجود</p>";
    }
}

echo "<h3 style='color: green;'>انتهاء الفحص والإصلاح</h3>";
echo "<p><a href='evaluation_form.php'>اختبار نموذج التقييم</a> | <a href='visits.php'>عرض الزيارات</a></p>";
?>
