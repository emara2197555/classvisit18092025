<?php
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "<h2>فحص جدول الصفوف</h2>";

try {
    $result = query('SELECT * FROM grades ORDER BY id');
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Name</th><th>Name AR</th></tr>";
    foreach($result as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['name'] . "</td>";
        echo "<td>" . ($row['name_ar'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>جدول الشعب (عينة):</h3>";
    $sections = query('SELECT s.*, g.name as grade_name, sc.name as school_name FROM sections s LEFT JOIN grades g ON s.grade_id = g.id LEFT JOIN schools sc ON s.school_id = sc.id LIMIT 10');
    echo "<table border='1'>";
    echo "<tr><th>Section ID</th><th>Section Name</th><th>Grade ID</th><th>Grade Name</th><th>School</th></tr>";
    foreach($sections as $section) {
        echo "<tr>";
        echo "<td>" . $section['id'] . "</td>";
        echo "<td>" . $section['name'] . "</td>";
        echo "<td>" . $section['grade_id'] . "</td>";
        echo "<td>" . $section['grade_name'] . "</td>";
        echo "<td>" . $section['school_name'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
