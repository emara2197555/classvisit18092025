<?php
/**
 * Test file to reproduce the coordinator dashboard error
 */

// تضمين ملفات النظام
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

echo "Testing coordinator dashboard queries...\n";

// Test database connection
try {
    $test_query = query_row("SELECT COUNT(*) as count FROM users");
    echo "Database connection: OK\n";
    echo "User count: " . $test_query['count'] . "\n";
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "\n";
}

// Test session data if exists
start_secure_session();
if (isset($_SESSION['user_id'])) {
    echo "Session user_id: " . $_SESSION['user_id'] . "\n";
    echo "Session role: " . ($_SESSION['role_name'] ?? 'not set') . "\n";
    echo "Session subject_id: " . ($_SESSION['subject_id'] ?? 'not set') . "\n";
    echo "Session school_id: " . ($_SESSION['school_id'] ?? 'not set') . "\n";
    
    // Try the exact queries from coordinator dashboard
    if (isset($_SESSION['subject_id']) && isset($_SESSION['school_id'])) {
        $subject_id = $_SESSION['subject_id'];
        $school_id = $_SESSION['school_id'];
        
        try {
            echo "Testing teacher count query...\n";
            $teachers_count = query_row("
                SELECT COUNT(*) as count 
                FROM teacher_subjects ts 
                INNER JOIN teachers t ON ts.teacher_id = t.id 
                WHERE ts.subject_id = ? AND t.school_id = ?
            ", [$subject_id, $school_id]);
            echo "Teachers count: " . $teachers_count['count'] . "\n";
        } catch (Exception $e) {
            echo "Teacher count query error: " . $e->getMessage() . "\n";
        }
    }
} else {
    echo "No active session found\n";
}

echo "Test completed.\n";
?>
