<?php
// Start session
session_start();

// 1) DB connection
require __DIR__ . '/db_connect.php';

// 2) Composer autoload (Necessary for GoogleAuthenticator)
require __DIR__ . '/vendor/autoload.php';

use PHPGangsta\GoogleAuthenticator;
// Initialize Google Authenticator class
$g = new PHPGangsta_GoogleAuthenticator(); 

// Use this for 2FA verification form messages (kept for consistency, though unused in the new flow)
$message_2fa = '';

// --- Check if user is logged in ---
if (empty($_SESSION['uid'])) {
    // If not logged in, redirect them to the login page
    header('Location: dashboard.php');
    exit;
}

$uid = (int)$_SESSION['uid'];

// Fetch user data: email and existing secret key
$stmt = $mysqli->prepare('SELECT user_email, user_secret_key, user_is_verified FROM tbl_users WHERE user_id = ?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();
$stmt->close();

// If the user is already verified, send them to the main page
if (isset($u['user_is_verified']) && $u['user_is_verified'] == 1) {
    // We keep this to prevent re-setup, but after setup, the login path handles verification
    header('Location: index.php');
    exit;
}

$secret = '';

if (empty($u['user_secret_key'])) {
    // Generate and store new secret key only if one doesn't exist
    $secret = $g->createSecret();

    $stmt2 = $mysqli->prepare('UPDATE tbl_users SET user_secret_key = ? WHERE user_id = ?');
    $stmt2->bind_param('si', $secret, $uid);
    $stmt2->execute();
    $stmt2->close();
} else {
    $secret = $u['user_secret_key'];
}

// --- Handle Continue to Login POST Request (NEW LOGIC) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'continue_to_login') {
    
    // 1. Mark user as verified in DB (since the key is now generated and saved)
    $stmt3 = $mysqli->prepare('UPDATE tbl_users SET user_is_verified = 1 WHERE user_id = ?');
    $stmt3->bind_param('i', $uid);
    $stmt3->execute();
    $stmt3->close();
    
    // 2. Clear session (force login/re-authentication with 2FA)
    session_unset();
    session_destroy();
    
    // 3. Set success flash message and redirect to login page
    session_start(); // Restart session to hold flash message
    $_SESSION['flash'] = 'Two-Factor Authentication secret key saved! Please log in and verify with your new 2FA code.';
    header('Location: index.php');
    exit;
}


// Label for the authenticator (account name); keep rawurlencode
$label = rawurlencode('Local2FA: ' . $u['user_email']);
$qrUrl = $g->getQRCodeGoogleUrl($label, $secret);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set up 2FA</title>
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #3441527a; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .container { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 100%; max-width: 450px; text-align: center; }
        h2 { color: #334155; margin-bottom: 10px; font-weight: 600; }
        p { margin-bottom: 15px; text-align: left; color: #4b5563; }
        img { display: block; margin: 20px auto; border: 4px solid #cbd5e1; padding: 5px; border-radius: 8px; }
        code { background-color: #f3f4f6; color: #1f2937; padding: 4px 8px; border-radius: 4px; font-weight: bold; display: inline-block; margin-top: 5px; }
        .success-message {
            background-color: #d1e7dd;
            color: #0f5132;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-weight: 500;
            text-align: center;
        }
        /* Adjusted .error style to use a safer red color for better contrast */
        .error { 
            color: #ef4444; 
            background-color: #fee2e2; 
            padding: 10px; 
            border-radius: 6px; 
            border: 1px solid #fca5a5; 
            margin-bottom: 20px; 
            font-size: 14px;
        }
        /* New style for the Continue button container */
        .continue-form { 
            margin-top: 25px; 
            padding-top: 15px; 
            border-top: 1px solid #e2e8f0; 
        }
        /* Style for the Continue button */
        .continue-form button {
            background-color: #0037afff; 
            color: white; 
            padding: 12px 20px; 
            border: none; 
            border-radius: 8px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 16px; 
            font-weight: 600;
            transition: background-color 0.3s;
            margin-top: 15px;
        }
        .continue-form button:hover { background-color: #1d4ed8; }
        
        .important-note {
            text-align: center; 
            color: #0f5132;
            background-color: #d1e7dd;
            padding: 10px;
            border-radius: 6px;
            font-weight: 500;
            margin: 15px 0;
        }

    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['status']) && $_GET['status'] === 'new'): ?>
            <div class="success-message">
                Account created successfully! Now let's secure it by setting up Two-Factor Authentication.
            </div>
        <?php endif; ?>

        <h2>Set up Two-Factor Authentication (2FA)</h2>
        <p><strong>1. Get your Authenticator App:</strong> Download Google Authenticator or Authy on your phone.</p>
        <p><strong>2. Scan the QR code:</strong> Open the app, select 'Add' (usually a + icon), and choose 'Scan a QR code'.</p>
        
        <img src="<?= htmlspecialchars($qrUrl) ?>" alt="QR Code for 2FA Setup">
        
        <p><strong>3. Save the Secret Key:</strong> Write down this key and store it safely. It's your backup if you lose your phone:</p>
        <p style="text-align: center;"><code><?= htmlspecialchars($secret) ?></code></p>
        
        <div class="continue-form">
            <p class="important-note"><strong>IMPORTANT</strong>: You must save the QR code or the secret key above. You will need it every time you log in.</p>
            <form method="post">
                <input type="hidden" name="action" value="continue_to_login">
                <button type="submit">Continue to Login</button>
            </form>
        </div>
        
    </div>
</body>
</html>
<?php
// Ensure all database connections are closed at the end of the script
$mysqli->close();
?>