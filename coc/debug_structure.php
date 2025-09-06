<?php
require_once 'includes/db_connection.php';

echo "=== Checking teacher_subjects table structure ===\n";
$tables = query("SHOW TABLES LIKE 'teacher_subjects'");
if (!empty($tables)) {
    $structure = query("DESCRIBE teacher_subjects");
    foreach ($structure as $field) {
        echo $field['Field'] . ' - ' . $field['Type'] . ' - ' . $field['Key'] . "\n";
    }
} else {
    echo "teacher_subjects table does not exist\n";
}

echo "\n=== Checking coordinator tables ===\n";
$coordinator_tables = query("SHOW TABLES LIKE '%coordinator%'");
foreach ($coordinator_tables as $table) {
    $table_name = array_values($table)[0];
    echo "Table: " . $table_name . "\n";
}

echo "\n=== Checking coordinator_supervisors table ===\n";
$coord_structure = query("DESCRIBE coordinator_supervisors");
foreach ($coord_structure as $field) {
    echo $field['Field'] . ' - ' . $field['Type'] . ' - ' . $field['Key'] . "\n";
}

echo "\n=== Sample data from coordinator_supervisors ===\n";
$sample_coord = query("SELECT * FROM coordinator_supervisors LIMIT 5");
foreach ($sample_coord as $row) {
    print_r($row);
}

echo "\n=== Sample data from teacher_subjects ===\n";
$sample_ts = query("SELECT * FROM teacher_subjects LIMIT 5");
foreach ($sample_ts as $row) {
    print_r($row);
}

echo "\n=== Current session data ===\n";
session_start();
print_r($_SESSION);
?>
