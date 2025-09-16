<?php
require_once 'includes/db_connection.php';

echo "إضافة بيانات تجريبية للشعب...\n";

// إضافة شعب للمدرسة 1 والصفوف المختلفة
$sections_data = [
    ['school_id' => 1, 'grade_id' => 1, 'name' => 'أ'],
    ['school_id' => 1, 'grade_id' => 1, 'name' => 'ب'],
    ['school_id' => 1, 'grade_id' => 2, 'name' => 'أ'],
    ['school_id' => 1, 'grade_id' => 2, 'name' => 'ب'],
    ['school_id' => 1, 'grade_id' => 3, 'name' => 'أ'],
    ['school_id' => 1, 'grade_id' => 3, 'name' => 'ب'],
];

foreach ($sections_data as $section) {
    // التحقق من عدم وجود الشعبة مسبقاً
    $existing = query_row("SELECT id FROM sections WHERE school_id = ? AND grade_id = ? AND name = ?", 
                         [$section['school_id'], $section['grade_id'], $section['name']]);
    
    if (!$existing) {
        try {
            query("INSERT INTO sections (school_id, grade_id, name) VALUES (?, ?, ?)", 
                  [$section['school_id'], $section['grade_id'], $section['name']]);
            echo "✅ تم إضافة شعبة: الصف {$section['grade_id']} - شعبة {$section['name']}\n";
        } catch (Exception $e) {
            echo "❌ خطأ في إضافة شعبة: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠️ الشعبة موجودة مسبقاً: الصف {$section['grade_id']} - شعبة {$section['name']}\n";
    }
}

echo "\nاختبار البيانات بعد الإضافة:\n";
$sections = query("SELECT s.*, g.name as grade_name FROM sections s JOIN grades g ON s.grade_id = g.id WHERE s.school_id = 1 ORDER BY s.grade_id, s.name");
foreach($sections as $section) {
    echo "شعبة ID: {$section['id']} - {$section['grade_name']} - شعبة {$section['name']}\n";
}
?>
