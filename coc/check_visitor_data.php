<?php
require_once 'includes/db_connection.php';

echo "أنواع الزوار الموجودة:\n";
try {
    $visitor_types = query('SELECT id, name FROM visitor_types ORDER BY id');
    foreach ($visitor_types as $type) {
        echo "ID: {$type['id']} - النوع: {$type['name']}\n";
    }
    
    echo "\n\nعينة من المعلمين والمناصب:\n";
    $teachers = query('SELECT id, name, job_title, school_id FROM teachers ORDER BY id LIMIT 10');
    foreach ($teachers as $teacher) {
        echo "ID: {$teacher['id']} - {$teacher['name']} - المنصب: {$teacher['job_title']} - المدرسة: {$teacher['school_id']}\n";
    }
    
    echo "\n\nعينة من المواد:\n";
    $subjects = query('SELECT id, name FROM subjects ORDER BY id LIMIT 5');
    foreach ($subjects as $subject) {
        echo "ID: {$subject['id']} - المادة: {$subject['name']}\n";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
