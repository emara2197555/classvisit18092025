<?php
require_once 'includes/db_connection.php';
require_once 'includes/auth_functions.php';

echo "Checking for coordinator users...\n";

// Check if any users exist with Subject Coordinator role
$coordinators = query("
    SELECT u.id, u.username, u.full_name, r.name as role_name, r.display_name 
    FROM users u 
    LEFT JOIN user_roles r ON u.role_id = r.id 
    WHERE r.name = 'Subject Coordinator' 
    AND u.is_active = 1
");

echo "Found " . count($coordinators) . " coordinator users:\n";
foreach ($coordinators as $coord) {
    echo "- ID: {$coord['id']}, Username: {$coord['username']}, Name: {$coord['full_name']}, Role: {$coord['role_name']}\n";
}

// Check if there are any coordinator_supervisors entries
$coord_supervisors = query("SELECT * FROM coordinator_supervisors LIMIT 5");
echo "\nCoordinator supervisors entries: " . count($coord_supervisors) . "\n";
foreach ($coord_supervisors as $cs) {
    echo "- User ID: {$cs['user_id']}, Subject ID: {$cs['subject_id']}\n";
}

// Try to simulate the coordinator dashboard access
if (!empty($coordinators)) {
    $coord = $coordinators[0];
    echo "\nTesting coordinator session simulation for user: {$coord['username']}\n";
    
    // Simulate session variables
    $_SESSION['user_id'] = $coord['id'];
    $_SESSION['role_name'] = $coord['role_name'];
    $_SESSION['full_name'] = $coord['full_name'];
    
    // Try to get coordinator subject
    $coordinator_data = query_row("SELECT subject_id FROM coordinator_supervisors WHERE user_id = ?", [$coord['id']]);
    if ($coordinator_data) {
        $_SESSION['subject_id'] = $coordinator_data['subject_id'];
        echo "Subject ID: {$coordinator_data['subject_id']}\n";
        
        // Try to get school_id from user table
        $user_data = query_row("SELECT school_id FROM users WHERE id = ?", [$coord['id']]);
        if ($user_data) {
            $_SESSION['school_id'] = $user_data['school_id'];
            echo "School ID: {$user_data['school_id']}\n";
        }
        
        // Now try the problematic query
        try {
            echo "Testing teacher count query...\n";
            $subject_id = $_SESSION['subject_id'];
            $school_id = $_SESSION['school_id'];
            
            $teachers_count = query_row("
                SELECT COUNT(*) as count 
                FROM teacher_subjects ts 
                INNER JOIN teachers t ON ts.teacher_id = t.id 
                WHERE ts.subject_id = ? AND t.school_id = ?
            ", [$subject_id, $school_id]);
            echo "Teachers count: " . $teachers_count['count'] . "\n";
            
        } catch (Exception $e) {
            echo "Error in teacher count query: " . $e->getMessage() . "\n";
        }
        
        // Test the get_coordinator_supervisors function
        try {
            echo "Testing get_coordinator_supervisors function...\n";
            $supervisors = get_coordinator_supervisors($coord['id']);
            echo "Supervisors found: " . count($supervisors) . "\n";
        } catch (Exception $e) {
            echo "Error in get_coordinator_supervisors: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "No coordinator_supervisors entry found for this user\n";
    }
}

echo "\nTest completed.\n";
?>
