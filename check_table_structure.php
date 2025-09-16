<?php
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

echo "<h2>بنية جدول elearning_attendance</h2>";

try {
    $result = query('DESCRIBE elearning_attendance');
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    foreach($result as $row) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // عرض بيانات عينة
    echo "<h3>بيانات عينة من الجدول:</h3>";
    $sample = query('SELECT * FROM elearning_attendance LIMIT 1');
    if (!empty($sample)) {
        echo "<pre>";
        print_r($sample[0]);
        echo "</pre>";
    } else {
        echo "لا توجد بيانات في الجدول";
    }
    
} catch (Exception $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
