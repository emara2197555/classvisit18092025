<?php
// سكريبت تحديث استعلامات متوسط الأداء لتكون أكثر دقة

echo "<h2>تحديث استعلامات متوسط الأداء</h2>";

$files_to_update = [
    'training_needs.php',
    'subject_performance_report.php', 
    'section_report.php',
    'teacher_report.php',
    'class_performance_report.php'
];

$patterns_to_replace = [
    // استبدال AVG(ve.score) * 25 بـ (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100
    '/AVG\(ve\.score\)\s*\*\s*25/' => '(SUM(ve.score) / (COUNT(ve.score) * 3)) * 100',
    
    // استبدال AVG(ve.score) AS avg_score مع إضافة النسبة المئوية
    '/AVG\(ve\.score\)\s+AS\s+avg_score/' => '(SUM(ve.score) / (COUNT(ve.score) * 3)) AS avg_score, (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 AS percentage_score',
    
    // استبدال AVG(ve.score) < 3 بـ (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 < 50
    '/AVG\(ve\.score\)\s*<\s*3/' => '(SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 < 50',
    
    // استبدال AVG(ve.score) >= 3 بـ (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 >= 67
    '/AVG\(ve\.score\)\s*>=\s*3/' => '(SUM(ve.score) / (COUNT(ve.score) * 3)) * 100 >= 67',
    
    // استبدال AVG(ve.score) في SELECT العامة
    '/SELECT\s+AVG\(ve\.score\)/' => 'SELECT (SUM(ve.score) / (COUNT(ve.score) * 3)) * 100',
];

foreach ($files_to_update as $file) {
    if (file_exists($file)) {
        echo "<h3>تحديث ملف: $file</h3>";
        $content = file_get_contents($file);
        $original_content = $content;
        
        foreach ($patterns_to_replace as $pattern => $replacement) {
            $count = 0;
            $content = preg_replace($pattern, $replacement, $content, -1, $count);
            if ($count > 0) {
                echo "<p style='color: orange;'>تم العثور على $count حالة من النمط '$pattern' وتعديلها</p>";
            }
        }
        
        // تأكد من إضافة WHERE ve.score IS NOT NULL في الاستعلامات المناسبة
        if (strpos($content, 'SUM(ve.score)') !== false && strpos($content, 've.score IS NOT NULL') === false) {
            // البحث عن WHERE clauses وإضافة الشرط
            $content = preg_replace(
                '/WHERE\s+(.*?)(\s+GROUP\s+BY|\s+ORDER\s+BY|\s+HAVING|$)/s',
                'WHERE $1 AND ve.score IS NOT NULL$2',
                $content
            );
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

echo "<h3>تحديث خاص للملفات المعقدة</h3>";

// تحديثات يدوية للملفات المعقدة
$manual_updates = [
    'training_needs.php' => [
        'old' => 'percentage_score,',
        'new' => '-- percentage_score calculated in SQL,',
    ],
    'subject_performance_report.php' => [
        'old' => '* 25',
        'new' => '/ 3 * 100',
    ]
];

foreach ($manual_updates as $file => $update) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, $update['old']) !== false) {
            $content = str_replace($update['old'], $update['new'], $content);
            file_put_contents($file, $content);
            echo "<p style='color: green;'>✓ تم تطبيق تحديث يدوي على $file</p>";
        }
    }
}

echo "<h3 style='color: green;'>انتهاء تحديث استعلامات متوسط الأداء</h3>";
echo "<p><strong>الفائدة:</strong> الاستعلامات الآن تحسب المتوسط على البنود المقاسة فقط (تستثني NULL) وتعطي نسب أكثر دقة.</p>";
echo "<p><a href='index.php'>الصفحة الرئيسية</a> | <a href='evaluation_form.php'>نموذج التقييم</a></p>";
?>
