<?php
require_once 'includes/auth_functions.php';
require_once 'includes/functions.php';

// Debug session and coordinator data
start_secure_session();
echo "<pre>";
echo "=== Session Debug ===\n";
print_r($_SESSION);

if (isset($_SESSION['user_id'])) {
    $coordinator_id = $_SESSION['user_id'];
    $subject_id = $_SESSION['subject_id'] ?? null;
    $school_id = $_SESSION['school_id'] ?? null;
    
    echo "\n=== Coordinator Variables ===\n";
    echo "Coordinator ID: " . $coordinator_id . "\n";
    echo "Subject ID: " . ($subject_id ?? 'NULL') . "\n";
    echo "School ID: " . ($school_id ?? 'NULL') . "\n";
    
    if ($subject_id && $school_id) {
        echo "\n=== Teacher Count Query ===\n";
        $teachers_count = query_row("
            SELECT COUNT(DISTINCT t.id) as count 
            FROM teacher_subjects ts 
            INNER JOIN teachers t ON ts.teacher_id = t.id 
            WHERE ts.subject_id = ? AND t.school_id = ?
        ", [$subject_id, $school_id]);
        echo "Teachers Count: " . $teachers_count['count'] . "\n";
        
        echo "\n=== Sample Teachers ===\n";
        $sample_teachers = query("
            SELECT t.id, t.name 
            FROM teacher_subjects ts 
            INNER JOIN teachers t ON ts.teacher_id = t.id 
            WHERE ts.subject_id = ? AND t.school_id = ?
            LIMIT 5
        ", [$subject_id, $school_id]);
        foreach ($sample_teachers as $teacher) {
            echo "- " . $teacher['name'] . " (ID: " . $teacher['id'] . ")\n";
        }
    } else {
        echo "\nMissing subject_id or school_id in session\n";
    }
} else {
    echo "No session found. Please login first.\n";
}
echo "</pre>";
?>
