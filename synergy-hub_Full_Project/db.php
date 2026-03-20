<?php
/**
 * Central database connection file for Synergy Hub project.
 */

$host = "localhost";
$user = "root";
$pass = "";
$db   = "collaborative_dev";

// Establish connection to MySQL database
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>