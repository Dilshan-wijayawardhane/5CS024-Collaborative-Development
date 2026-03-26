<?php



$host = "localhost";
$user = "root";
$pass = "";
$db   = "collaborative_dev";




$conn = new mysqli($host, $user, $pass, $db);




if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>