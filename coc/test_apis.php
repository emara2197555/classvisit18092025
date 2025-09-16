<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

session_start();

echo "<h2>اختبار API الصفوف والمعلمين</h2>";

// اختبار API الصفوف
echo "<h3>1. اختبار API الصفوف لمدرسة ID=1:</h3>";
$school_id = 1;

try {
    $sql = "SELECT DISTINCT g.id, g.name 
            FROM grades g 
            INNER JOIN sections s ON g.id = s.grade_id 
            WHERE s.school_id = ? 
            ORDER BY g.id";
    
    $grades = query($sql, [$school_id]);
    echo "عدد الصفوف المتاحة: " . count($grades) . "<br>";
    foreach ($grades as $grade) {
        echo "- " . $grade['name'] . " (ID: " . $grade['id'] . ")<br>";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}

// اختبار API المعلمين  
echo "<h3>2. اختبار API المعلمين لمدرسة ID=1 ومادة ID=1:</h3>";
$subject_id = 1;

try {
    $sql = "SELECT DISTINCT t.id, t.name 
            FROM teachers t
            INNER JOIN teacher_subjects ts ON t.id = ts.teacher_id
            WHERE ts.school_id = ? AND ts.subject_id = ?
            ORDER BY t.name";
            
    $teachers = query($sql, [$school_id, $subject_id]);
    echo "عدد المعلمين المتاحين: " . count($teachers) . "<br>";
    foreach ($teachers as $teacher) {
        echo "- " . $teacher['name'] . " (ID: " . $teacher['id'] . ")<br>";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}

// عرض بيانات المدارس والمواد المتاحة
echo "<h3>3. المدارس المتاحة:</h3>";
$schools = query("SELECT id, name FROM schools LIMIT 5");
foreach ($schools as $school) {
    echo "- " . $school['name'] . " (ID: " . $school['id'] . ")<br>";
}

echo "<h3>4. المواد المتاحة:</h3>";
$subjects = query("SELECT id, name FROM subjects LIMIT 5");
foreach ($subjects as $subject) {
    echo "- " . $subject['name'] . " (ID: " . $subject['id'] . ")<br>";
}

?>

<script>
// اختبار استدعاء API عبر JavaScript
console.log('اختبار API عبر JavaScript');

// اختبار API الصفوف
fetch('api/get_grades_by_school.php?school_id=1')
    .then(response => response.json())
    .then(data => {
        console.log('API الصفوف:', data);
    })
    .catch(error => console.error('خطأ API الصفوف:', error));

// اختبار API المعلمين
fetch('api/get_teachers_by_school_subject.php?school_id=1&subject_id=1')
    .then(response => response.json())
    .then(data => {
        console.log('API المعلمين:', data);
    })
    .catch(error => console.error('خطأ API المعلمين:', error));
</script>
