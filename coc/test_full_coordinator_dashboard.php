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

echo "Testing full coordinator dashboard...\n";

// Capture output to prevent HTML rendering in terminal
ob_start();

try {
    include 'coordinator_dashboard.php';
    $output = ob_get_contents();
    echo "✓ Coordinator dashboard loaded successfully!\n";
    echo "✓ Generated " . strlen($output) . " bytes of HTML output\n";
} catch (Exception $e) {
    ob_end_clean();
    echo "✗ Error loading coordinator dashboard: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    ob_end_clean();
    echo "✗ Fatal error loading coordinator dashboard: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "Test completed.\n";
?>
