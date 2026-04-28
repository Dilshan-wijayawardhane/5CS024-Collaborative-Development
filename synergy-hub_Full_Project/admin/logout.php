<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/functions.php';

if (isset($_SESSION['user_id'])) {
    logActivity($conn, $_SESSION['user_id'], 'ADMIN_LOGOUT');
}

session_destroy();
header("Location: ../login.php");
exit();
?>