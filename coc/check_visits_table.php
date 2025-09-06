<?php
require_once 'includes/db_connection.php';

echo "=== Visits Table Structure ===\n";
$structure = query("DESCRIBE visits");
foreach ($structure as $field) {
    echo $field['Field'] . ' - ' . $field['Type'] . ' - ' . $field['Key'] . "\n";
}

echo "\n=== Sample Visit Data ===\n";
$sample = query("SELECT * FROM visits LIMIT 2");
foreach ($sample as $row) {
    print_r($row);
}
?>
