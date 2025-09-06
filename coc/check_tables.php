<?php
require_once 'includes/db_connection.php';

echo "=== Checking database tables ===\n";

try {
    $tables = query("SHOW TABLES");
    echo "Tables found:\n";
    foreach ($tables as $table) {
        $table_name = array_values($table)[0];
        echo "- $table_name\n";
    }
    
    // Check if user_roles table exists
    $user_roles_exists = false;
    $roles_exists = false;
    
    foreach ($tables as $table) {
        $table_name = array_values($table)[0];
        if ($table_name === 'user_roles') {
            $user_roles_exists = true;
        }
        if ($table_name === 'roles') {
            $roles_exists = true;
        }
    }
    
    echo "\nTable existence check:\n";
    echo "user_roles: " . ($user_roles_exists ? "EXISTS" : "NOT FOUND") . "\n";
    echo "roles: " . ($roles_exists ? "EXISTS" : "NOT FOUND") . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
