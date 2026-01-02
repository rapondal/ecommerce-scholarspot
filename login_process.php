<?php
session_start();
require_once 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    if (empty($email) || empty($password)) {
        $_SESSION['error'] = "Please enter both email and password.";
        header("Location: login.php");
        exit;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM user WHERE Email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verify password
            if (password_verify($password, $user['Password'])) {

                // --- CHECK STATUS (NEW) ---
                // If the admin disabled this user, don't let them in.
                if (isset($user['Status']) && $user['Status'] === 'Disabled') {
                    $_SESSION['error'] = "Your account has been disabled. Please contact the administrator.";
                    header("Location: login.php");
                    exit;
                }

                // --- 2FA CHECK ---
                if (!empty($user['user_secret_key'])) {
                    $requires_2fa = true;
                    if (!empty($user['last_2fa_verification'])) {
                        $hours = (time() - strtotime($user['last_2fa_verification'])) / 3600;
                        if ($hours < 24) $requires_2fa = false;
                    }
                    if ($requires_2fa) {
                        $_SESSION['pending_2fa_user_id'] = $user['User_ID'];
                        header("Location: verify_2fa.php");
                        exit;
                    }
                }

                // --- SUCCESSFUL LOGIN ---
                $_SESSION['User_ID'] = $user['User_ID'];
                $_SESSION['Name'] = $user['Name'];
                $_SESSION['Email'] = $user['Email'];
                $_SESSION['Role'] = $user['Role'];

                // --- ACTIVITY LOG (NEW) ---
                $logStmt = $pdo->prepare("INSERT INTO activity_log (User_ID, Action, Details) VALUES (?, ?, ?)");
                $logStmt->execute([$user['User_ID'], 'Login', 'User logged in successfully']);

                if (isset($_POST['remember_me'])) {
                    setcookie('user_login', $user['Email'], time() + (86400 * 30), "/");
                }

                if ($user['Role'] === 'Admin') {
                    header("Location: admindashboard.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit;

            } else {
                $_SESSION['error'] = "Incorrect password.";
                header("Location: login.php");
                exit;
            }
        } else {
            $_SESSION['error'] = "No account found with that email.";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        $_SESSION['error'] = "Database error occurred. Please try again.";
        header("Location: login.php");
        exit;
    }
} else {
    header("Location: login.php");
    exit;
}
?>