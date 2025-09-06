<?php
require_once 'includes/db_connection.php';

// Check coordinator and user data
echo "=== Sample Coordinator Data ===\n";
$coordinator = query_row("SELECT * FROM coordinator_supervisors LIMIT 1");
print_r($coordinator);

if ($coordinator) {
    $user_id = $coordinator['user_id'];
    $subject_id = $coordinator['subject_id'];
    
    echo "\n=== User Data for Coordinator ===\n";
    $user = query_row("SELECT * FROM users WHERE id = ?", [$user_id]);
    print_r($user);
    
    echo "\n=== Subject Data ===\n";
    $subject = query_row("SELECT * FROM subjects WHERE id = ?", [$subject_id]);
    print_r($subject);
    
    $school_id = $user['school_id'];
    
    echo "\n=== Testing Query with School Filter ===\n";
    $teachers_with_school = query_row("
        SELECT COUNT(DISTINCT t.id) as count 
        FROM teacher_subjects ts 
        INNER JOIN teachers t ON ts.teacher_id = t.id 
        WHERE ts.subject_id = ? AND t.school_id = ?
    ", [$subject_id, $school_id]);
    echo "Count with school filter: " . $teachers_with_school['count'] . "\n";
    
    echo "\n=== Testing Query without School Filter ===\n";
    $teachers_without_school = query_row("
        SELECT COUNT(DISTINCT t.id) as count 
        FROM teacher_subjects ts 
        INNER JOIN teachers t ON ts.teacher_id = t.id 
        WHERE ts.subject_id = ?
    ", [$subject_id]);
    echo "Count without school filter: " . $teachers_without_school['count'] . "\n";
    
    echo "\n=== All Teachers for Subject ===\n";
    $all_teachers = query("
        SELECT t.id, t.name, t.school_id, s.name as school_name
        FROM teacher_subjects ts 
        INNER JOIN teachers t ON ts.teacher_id = t.id 
        LEFT JOIN schools s ON t.school_id = s.id
        WHERE ts.subject_id = ?
        LIMIT 10
    ", [$subject_id]);
    foreach ($all_teachers as $teacher) {
        echo "Teacher: " . $teacher['name'] . " - School: " . ($teacher['school_name'] ?? 'NULL') . " (ID: " . $teacher['school_id'] . ")\n";
    }
}
?>
