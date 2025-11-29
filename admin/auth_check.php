<?php
require_once '../includes/config.php';

// Check if user is logged in
if(!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Session timeout (8 hours)
$session_timeout = 8 * 60 * 60; // 8 jam
if(isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $session_timeout) {
    session_destroy();
    header('Location: login.php');
    exit;
}

// Update session time
$_SESSION['login_time'] = time();
?>
