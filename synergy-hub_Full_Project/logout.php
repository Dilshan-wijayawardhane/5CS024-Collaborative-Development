<?php


session_start(); 
require_once 'functions.php';



if (isset($_SESSION['user_id'])) {
    
    require_once 'config.php';
    logActivity($conn, $_SESSION['user_id'], 'LOGOUT');
}



session_destroy();



header("Location: login.php");
exit();
?>