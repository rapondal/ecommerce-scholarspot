<?php
function requireLogin() {
    if (empty($_SESSION['uid'])) {
        header('Location: dashboard.php'); exit;
    }
}
function requireVerified() {
    requireLogin();
    if (empty($_SESSION['verified'])) {
        header('Location: verify_2fa.php'); exit;
    }
}
?>