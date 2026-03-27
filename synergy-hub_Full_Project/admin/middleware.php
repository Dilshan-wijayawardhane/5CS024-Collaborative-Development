<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../functions.php';


if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: ../login.php");
    exit();
}


if ($_SESSION['user_role'] !== 'Admin') {
    header("Location: ../index.php");
    exit();
}
?>