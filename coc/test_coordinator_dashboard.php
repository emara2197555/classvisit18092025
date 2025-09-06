<?php
session_start();

// Simulate a coordinator login
$_SESSION['user_id'] = 240;
$_SESSION['username'] = 'm.ali0308';
$_SESSION['role_name'] = 'Subject Coordinator';
$_SESSION['role_id'] = 5;
$_SESSION['school_id'] = 1;
$_SESSION['full_name'] = 'محمد مصطفى عبداللطيف علي';
$_SESSION['subject_id'] = 3;

echo "Testing coordinator dashboard step by step...\n";

// Include the system files individually
try {
    echo "1. Including db_connection...\n";
    require_once 'includes/db_connection.php';
    echo "   ✓ DB connection OK\n";
    
    echo "2. Including auth_functions...\n";
    require_once 'includes/auth_functions.php';
    echo "   ✓ Auth functions OK\n";
    
    echo "3. Including functions...\n";
    require_once 'includes/functions.php';
    echo "   ✓ Functions OK\n";
    
    echo "4. Testing protect_page...\n";
    protect_page(['Subject Coordinator']);
    echo "   ✓ protect_page OK\n";
    
    echo "5. Getting coordinator data...\n";
    $coordinator_id = $_SESSION['user_id'];
    $coordinator_name = $_SESSION['full_name'];
    $subject_id = $_SESSION['subject_id'];
    $school_id = $_SESSION['school_id'];
    echo "   ✓ Coordinator data OK\n";
    
    echo "6. Getting subject name...\n";
    $subject = query_row("SELECT name FROM subjects WHERE id = ?", [$subject_id]);
    $subject_name = $subject['name'] ?? 'غير محدد';
    echo "   ✓ Subject name: {$subject_name}\n";
    
    echo "7. Getting school name...\n";
    $school = query_row("SELECT name FROM schools WHERE id = ?", [$school_id]);
    $school_name = $school['name'] ?? 'غير محدد';
    echo "   ✓ School name: {$school_name}\n";
    
    echo "8. Testing teacher count query...\n";
    $teachers_count = query_row("
        SELECT COUNT(*) as count 
        FROM teacher_subjects ts 
        INNER JOIN teachers t ON ts.teacher_id = t.id 
        WHERE ts.subject_id = ? AND t.school_id = ?
    ", [$subject_id, $school_id]);
    echo "   ✓ Teachers count: {$teachers_count['count']}\n";
    
    echo "9. Testing get_coordinator_supervisors...\n";
    $supervisors = get_coordinator_supervisors($coordinator_id);
    echo "   ✓ Supervisors count: " . count($supervisors) . "\n";
    
    echo "10. Testing header inclusion...\n";
    $page_title = 'لوحة تحكم المنسق - نظام الزيارات الصفية';
    require_once 'includes/header.php';
    echo "   ✓ Header included successfully\n";
    
    echo "\nAll tests passed! The error must be elsewhere.\n";
    
} catch (Exception $e) {
    echo "   ✗ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
