<?php
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// Start session and check data
start_secure_session();

echo "<h2>Current Session Debug</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

if (isset($_SESSION['user_id'])) {
    $coordinator_id = $_SESSION['user_id'];
    $subject_id = $_SESSION['subject_id'] ?? null;
    $school_id = $_SESSION['school_id'] ?? null;
    
    echo "<h3>Session Variables</h3>";
    echo "Coordinator ID: " . $coordinator_id . "<br>";
    echo "Subject ID: " . ($subject_id ?? 'NULL') . "<br>";
    echo "School ID: " . ($school_id ?? 'NULL') . "<br>";
    
    // Get user data from database
    echo "<h3>Database User Data</h3>";
    $user_data = query_row("SELECT * FROM users WHERE id = ?", [$coordinator_id]);
    echo "User School ID from DB: " . ($user_data['school_id'] ?? 'NULL') . "<br>";
    echo "User Role ID: " . ($user_data['role_id'] ?? 'NULL') . "<br>";
    
    // Get coordinator data
    echo "<h3>Coordinator Data</h3>";
    $coordinator_data = query_row("SELECT * FROM coordinator_supervisors WHERE user_id = ?", [$coordinator_id]);
    echo "Coordinator Subject ID from DB: " . ($coordinator_data['subject_id'] ?? 'NULL') . "<br>";
    echo "Coordinator School ID from DB: " . ($coordinator_data['school_id'] ?? 'NULL') . "<br>";
    
    // Test teacher count with different scenarios
    echo "<h3>Teacher Count Tests</h3>";
    
    $effective_subject_id = $subject_id ?? $coordinator_data['subject_id'];
    $effective_school_id = $school_id ?? $user_data['school_id'];
    
    echo "Using Subject ID: " . $effective_subject_id . "<br>";
    echo "Using School ID: " . $effective_school_id . "<br>";
    
    if ($effective_subject_id && $effective_school_id) {
        // Test 1: With both filters
        $count1 = query_row("
            SELECT COUNT(DISTINCT t.id) as count 
            FROM teacher_subjects ts 
            INNER JOIN teachers t ON ts.teacher_id = t.id 
            WHERE ts.subject_id = ? AND t.school_id = ?
        ", [$effective_subject_id, $effective_school_id]);
        echo "Count with both filters: " . $count1['count'] . "<br>";
        
        // Test 2: Only subject filter
        $count2 = query_row("
            SELECT COUNT(DISTINCT t.id) as count 
            FROM teacher_subjects ts 
            INNER JOIN teachers t ON ts.teacher_id = t.id 
            WHERE ts.subject_id = ?
        ", [$effective_subject_id]);
        echo "Count with only subject filter: " . $count2['count'] . "<br>";
        
        // Show sample teachers
        echo "<h4>Sample Teachers:</h4>";
        $teachers = query("
            SELECT t.id, t.name, t.school_id, s.name as school_name
            FROM teacher_subjects ts 
            INNER JOIN teachers t ON ts.teacher_id = t.id 
            LEFT JOIN schools s ON t.school_id = s.id
            WHERE ts.subject_id = ? AND t.school_id = ?
            LIMIT 5
        ", [$effective_subject_id, $effective_school_id]);
        
        foreach ($teachers as $teacher) {
            echo "- " . $teacher['name'] . " (School: " . $teacher['school_name'] . ")<br>";
        }
    } else {
        echo "<strong style='color: red;'>Missing subject_id or school_id!</strong><br>";
    }
} else {
    echo "No session found - please login first.";
}
?>
