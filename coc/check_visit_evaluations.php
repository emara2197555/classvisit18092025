<?php
require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

echo "هيكل جدول visit_evaluations:\n";
$result = $pdo->query('DESCRIBE visit_evaluations');
foreach ($result as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . ' - ' . $row['Key'] . "\n";
}

echo "\nعينة من البيانات:\n";
$sample = $pdo->query('SELECT * FROM visit_evaluations LIMIT 5');
foreach ($sample as $row) {
    print_r($row);
}
?>
