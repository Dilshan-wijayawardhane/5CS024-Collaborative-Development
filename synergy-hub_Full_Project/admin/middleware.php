<?php
// admin/middleware.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: ../login.php");
    exit();
}

// Check if user is admin
if ($_SESSION['user_role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}
?>