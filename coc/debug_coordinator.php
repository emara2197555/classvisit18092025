<?php
require_once 'includes/auth_functions.php';
require_once 'includes/db_connection.php';

// Start session to check current coordinator data
start_secure_session();

echo "=== Current Session Data ===\n";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Role: " . $_SESSION['role_name'] . "\n";
    echo "Subject ID: " . ($_SESSION['subject_id'] ?? 'NULL') . "\n";
    echo "School ID: " . ($_SESSION['school_id'] ?? 'NULL') . "\n";
    echo "Full Name: " . $_SESSION['full_name'] . "\n";
} else {
    echo "No session data found\n";
}

// Check coordinator data in database
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $subject_id = $_SESSION['subject_id'];
    $school_id = $_SESSION['school_id'];
    
    echo "\n=== Coordinator Data from Database ===\n";
    $coordinator_data = query_row("SELECT * FROM coordinator_supervisors WHERE user_id = ?", [$user_id]);
    print_r($coordinator_data);
    
    echo "\n=== User Data from Database ===\n";
    $user_data = query_row("SELECT * FROM users WHERE id = ?", [$user_id]);
    print_r($user_data);
    
    if ($subject_id && $school_id) {
        echo "\n=== Testing Current Query (with school_id filter) ===\n";
        $teachers_count = query_row("
            SELECT COUNT(DISTINCT t.id) as count 
            FROM teacher_subjects ts 
            INNER JOIN teachers t ON ts.teacher_id = t.id 
            WHERE ts.subject_id = ? AND t.school_id = ?
        ", [$subject_id, $school_id]);
        echo "Teachers count with school filter: " . $teachers_count['count'] . "\n";
        
        echo "\n=== Testing Modified Query (without school_id filter) ===\n";
        $teachers_count_no_filter = query_row("
            SELECT COUNT(DISTINCT t.id) as count 
            FROM teacher_subjects ts 
            INNER JOIN teachers t ON ts.teacher_id = t.id 
            WHERE ts.subject_id = ?
        ", [$subject_id]);
        echo "Teachers count without school filter: " . $teachers_count_no_filter['count'] . "\n";
        
        echo "\n=== All teachers for subject $subject_id ===\n";
        $all_teachers = query("
            SELECT t.id, t.name, t.school_id, s.name as school_name
            FROM teacher_subjects ts 
            INNER JOIN teachers t ON ts.teacher_id = t.id 
            LEFT JOIN schools s ON t.school_id = s.id
            WHERE ts.subject_id = ?
        ", [$subject_id]);
        foreach ($all_teachers as $teacher) {
            echo "Teacher: " . $teacher['name'] . " (ID: " . $teacher['id'] . ") - School: " . $teacher['school_name'] . " (ID: " . $teacher['school_id'] . ")\n";
        }
    }
}
?>
