<?php



ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800); 

session_start();




$servername = "localhost";
$username = "root";
$password = "";
$dbname = "collaborative_dev";




$conn = mysqli_connect($servername, $username, $password, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}




mysqli_set_charset($conn, "utf8mb4");
?>