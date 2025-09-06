<?php
require_once 'includes/functions.php';
require_once 'includes/db_connection.php';

echo "هيكل جدول users:\n";
$result = $pdo->query('DESCRIBE users');
foreach ($result as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
