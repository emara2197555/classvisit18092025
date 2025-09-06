<?php
require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

echo "أنواع الزوار:\n";
$result = $pdo->query('SELECT * FROM visitor_types');
foreach ($result as $row) {
    echo 'ID: ' . $row['id'] . ' - الاسم: ' . $row['name'] . "\n";
}
?>
