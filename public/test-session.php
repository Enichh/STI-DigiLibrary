<?php
// Quick test script to simulate login session
// Place this in: public/test-session.php

session_start();

// Simulate a logged-in user
$_SESSION['user_id'] = 1;
$_SESSION['user_name'] = 'Test User';
$_SESSION['user_email'] = 'test@example.com';
$_SESSION['role'] = 'student';

// Redirect to catalog
header('Location: /catalog');
exit;
?>
