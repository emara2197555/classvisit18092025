<?php
require_once 'includes/db_connection.php';

echo "=== بنية جدول visits ===\n";
$result = query('DESCRIBE visits');
foreach($result as $row) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== آخر 5 زيارات ===\n";
$visits = query('SELECT * FROM visits ORDER BY visit_date DESC LIMIT 5');
foreach($visits as $visit) {
    echo "ID: " . $visit['id'] . ", Teacher: " . $visit['teacher_id'] . ", Date: " . $visit['visit_date'] . "\n";
    foreach($visit as $key => $value) {
        echo "  $key: $value\n";
    }
    echo "---\n";
}
?>
