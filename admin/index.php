<?php
/**
 * Redirect ke dashboard
 */
require_once '../includes/auth.php';
Auth::requireLogin();

header('Location: dashboard.php');
exit;
?>
