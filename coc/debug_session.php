<?php
session_start();

echo "<h3>Session Debug Information:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['role'])) {
    echo "<p>Current Role: " . $_SESSION['role'] . "</p>";
    echo "<p>Is Coordinator: " . (($_SESSION['role'] === 'Subject Coordinator') ? 'YES' : 'NO') . "</p>";
}

if (isset($_SESSION['user_id'])) {
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    
    // Check coordinator data
    require_once 'includes/db_connection.php';
    $coordinator = query("
        SELECT cs.subject_id, t.school_id, s.name as subject_name, sc.name as school_name
        FROM coordinator_supervisors cs
        JOIN teachers t ON cs.user_id = t.id
        JOIN subjects s ON cs.subject_id = s.id
        JOIN schools sc ON t.school_id = sc.id
        WHERE cs.user_id = ?
    ", [$_SESSION['user_id']]);
    
    if (!empty($coordinator)) {
        echo "<h4>Coordinator Data:</h4>";
        echo "<pre>";
        print_r($coordinator[0]);
        echo "</pre>";
    } else {
        echo "<p>No coordinator data found</p>";
    }
}
?>
