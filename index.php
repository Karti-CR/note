<?php
require_once 'config.php';

session_start();

// If logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
} else {
    // Otherwise go to login
    header('Location: login.php');
}
exit;
?>
