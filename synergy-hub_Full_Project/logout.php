<?php
/**
 * Handles user logout process
 * 
 * Security Notes:
 *  - Starts session to access user data for logging
 *  - Logs activity before killing session
 *  No output should occur before header() - safe here
 */

session_start(); // Must start session to access and destroy it
require_once 'functions.php';

// Log logout if user was logged in
if (isset($_SESSION['user_id'])) {
    
    require_once 'config.php';
    logActivity($conn, $_SESSION['user_id'], 'LOGOUT');
}

// Kill session and redirect
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?>