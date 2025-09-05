<?php
require_once '../includes/db_connection.php';

echo "=== بنية جدول visit_evaluations ===\n";
$result = query('DESCRIBE visit_evaluations');
foreach($result as $row) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}

echo "\n=== عينة من البيانات ===\n";
$sample = query('SELECT * FROM visit_evaluations LIMIT 3');
foreach($sample as $row) {
    echo "ID: " . $row['id'] . "\n";
    foreach($row as $key => $value) {
        echo "  $key: $value\n";
    }
    echo "---\n";
}
?>
