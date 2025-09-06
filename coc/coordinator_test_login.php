<?php
// Simple coordinator dashboard test
session_start();

// Manual session setup for testing
$_SESSION['user_id'] = 240;
$_SESSION['username'] = 'm.ali0308';
$_SESSION['role_name'] = 'Subject Coordinator';
$_SESSION['role_id'] = 5;
$_SESSION['school_id'] = 1;
$_SESSION['full_name'] = 'محمد مصطفى عبداللطيف علي';
$_SESSION['subject_id'] = 3;

// Redirect to coordinator dashboard
header('Location: coordinator_dashboard.php');
exit;
?>
