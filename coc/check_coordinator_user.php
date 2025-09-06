<?php
require_once 'includes/db_connection.php';

echo "Checking coordinator user details...\n";

$coordinator = query_row("
    SELECT u.id, u.username, u.full_name, u.password_hash, r.name as role_name
    FROM users u 
    LEFT JOIN user_roles r ON u.role_id = r.id 
    WHERE u.username = 'm.ali0308'
");

if ($coordinator) {
    echo "Found user:\n";
    echo "- ID: {$coordinator['id']}\n";
    echo "- Username: {$coordinator['username']}\n";
    echo "- Name: {$coordinator['full_name']}\n";
    echo "- Role: {$coordinator['role_name']}\n";
    echo "- Password hash exists: " . (!empty($coordinator['password_hash']) ? 'Yes' : 'No') . "\n";
    
    // Update password to 123456
    echo "\nUpdating password to '123456' for this user...\n";
    $hash = password_hash('123456', PASSWORD_DEFAULT);
    execute("UPDATE users SET password_hash = ? WHERE id = ?", [$hash, $coordinator['id']]);
    echo "✓ Password updated successfully\n";
} else {
    echo "User not found\n";
}

echo "\nTest completed.\n";
?>
