<?php
session_start();
require 'db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

$g = new PHPGangsta_GoogleAuthenticator();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $_SESSION['error'] = 'All fields are required.';
        header('Location: signup.php');
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = 'Invalid email format.';
        header('Location: signup.php');
        exit;
    }

    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match.';
        header('Location: signup.php');
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE Email = ?");
        $stmt->execute([$email]);
        $existing = $stmt->fetch();

        if ($existing) {
            $_SESSION['error'] = 'This email is already registered.';
            header('Location: signup.php');
            exit;
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Generate 2FA secret key
        $secret = $g->createSecret();

        // Insert new user with 2FA secret
        $insert = $pdo->prepare("INSERT INTO user (Name, Email, Password, user_secret_key, Role) VALUES (?, ?, ?, ?, 'customer')");
        $insert->execute([$name, $email, $hashed_password, $secret]);
        
        // Get the newly created user ID
        $user_id = $pdo->lastInsertId();

        // Store user info in session for QR code display
        $_SESSION['new_user_id'] = $user_id;
        $_SESSION['new_user_email'] = $email;
        $_SESSION['new_user_secret'] = $secret;

        // Redirect to setup 2FA page
        header('Location: setup_2fa.php');
        exit;

    } catch (PDOException $e) {
        $_SESSION['error'] = 'Database error: ' . $e->getMessage();
        header('Location: signup.php');
        exit;
    }
} else {
    header('Location: signup.php');
    exit;
}
?>