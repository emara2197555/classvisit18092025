<?php
require_once 'includes/db_connection.php';

echo "=== Users with Subject Coordinator role ===\n";
$coordinators = query("
    SELECT u.id, u.username, u.role, t.name as teacher_name, s.name as subject_name, sc.name as school_name
    FROM users u
    LEFT JOIN teachers t ON u.id = t.id  
    LEFT JOIN coordinator_supervisors cs ON u.id = cs.user_id
    LEFT JOIN subjects s ON cs.subject_id = s.id
    LEFT JOIN schools sc ON t.school_id = sc.id
    WHERE u.role = 'Subject Coordinator'
");

foreach ($coordinators as $coord) {
    echo "ID: {$coord['id']}, Username: {$coord['username']}, Teacher: {$coord['teacher_name']}, Subject: {$coord['subject_name']}, School: {$coord['school_name']}\n";
}
?>
