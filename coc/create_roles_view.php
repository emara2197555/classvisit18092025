<?php
require_once 'includes/db_connection.php';

echo "Creating roles table as a view to user_roles...\n";

try {
    // Drop the view if it exists
    execute("DROP VIEW IF EXISTS roles");
    
    // Create a view that maps user_roles to roles
    execute("CREATE VIEW roles AS SELECT * FROM user_roles");
    
    echo "✓ Successfully created 'roles' view pointing to 'user_roles' table\n";
    
    // Test the view
    $roles = query("SELECT id, name, display_name FROM roles LIMIT 3");
    echo "✓ Test query successful. Found " . count($roles) . " roles:\n";
    foreach ($roles as $role) {
        echo "  - {$role['name']} ({$role['display_name']})\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error creating roles view: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";
?>
