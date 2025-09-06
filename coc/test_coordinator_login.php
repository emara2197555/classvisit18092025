<?php
session_start();

require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';

// Test login as a coordinator
$result = authenticate_user('m.ali0308', '123456');

if ($result['success']) {
    echo "Login successful! Redirecting to coordinator dashboard...\n";
    echo "User role: " . $_SESSION['role_name'] . "\n";
    echo "Subject ID: " . $_SESSION['subject_id'] . "\n";
    echo "School ID: " . $_SESSION['school_id'] . "\n";
} else {
    echo "Login failed: " . $result['message'] . "\n";
}
?>
