<?php
session_start();
require_once 'functions.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    // We need to include config.php here because functions.php doesn't have it
    require_once 'config.php';
    logActivity($conn, $_SESSION['user_id'], 'LOGOUT');
}

// Destroy session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>