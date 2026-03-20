<?php
/**
 * Central configuration file for database connection and session settings.
 * 
 * Security features:
 *  - HTTPOnly cookies (prevents JavaScript access)
 *  - Cookies only (no URL session IDs)
 *  - SameSite=Strict (strong CSRF protection)
 *  - 30-minutes session lifetime
 */

ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800); 

session_start();

// Database Connection Settings
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "collaborative_dev";

// Establish MySQLi Connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set character encoding to support full Unicode
mysqli_set_charset($conn, "utf8mb4");
?>