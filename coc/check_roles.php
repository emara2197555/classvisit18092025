<?php
require_once 'includes/db_connection.php';

// Check roles table
echo "=== Roles table ===\n";
$roles = query("SELECT * FROM roles");
foreach ($roles as $role) {
    echo "ID: {$role['id']}, Name: {$role['name']}\n";
}

echo "\n=== Users with coordinator role ===\n";
$coordinators = query("
    SELECT u.id, u.username, u.full_name, r.name as role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE r.name LIKE '%coordinator%' OR r.name LIKE '%منسق%'
");

foreach ($coordinators as $coord) {
    echo "ID: {$coord['id']}, Username: {$coord['username']}, Name: {$coord['full_name']}, Role: {$coord['role_name']}\n";
}

echo "\n=== Coordinator supervisors table ===\n";
$coord_data = query("SELECT * FROM coordinator_supervisors LIMIT 5");
foreach ($coord_data as $data) {
    print_r($data);
    echo "\n";
}
?>
