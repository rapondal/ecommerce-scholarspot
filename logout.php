<?php
session_start();

// Unset all session variables except the success message
foreach ($_SESSION as $key => $value) {
    if ($key !== 'success') {
        unset($_SESSION[$key]);
    }
}

// Set the success message
$_SESSION['success'] = 'You have been logged out successfully.';

// Redirect back to login page
header('Location: login.php');
exit;
?>
