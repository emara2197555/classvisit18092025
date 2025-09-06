<?php
require_once 'includes/db_connection.php';

// Check users table structure
echo "=== Users table structure ===\n";
$result = query("DESCRIBE users");
foreach ($result as $row) {
    echo "{$row['Field']} - {$row['Type']}\n";
}

echo "\n=== Sample users ===\n";
$users = query("SELECT * FROM users LIMIT 5");
foreach ($users as $user) {
    print_r($user);
    echo "\n";
}
?>
