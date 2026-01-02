<?php
session_start();
require_once __DIR__ . '/vendor/autoload.php';

// Check if user just signed up
if (empty($_SESSION['new_user_id']) || empty($_SESSION['new_user_secret'])) {
    header('Location: signup.php');
    exit;
}

$g = new PHPGangsta_GoogleAuthenticator();
$secret = $_SESSION['new_user_secret'];
$email = $_SESSION['new_user_email'];
$user_id = $_SESSION['new_user_id'];

// Generate QR code URL
$qrCodeUrl = $g->getQRCodeGoogleUrl('ScholarSpot', $secret, 'ScholarSpot (' . $email . ')');

// Handle verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    
    // Validate code format
    if (empty($code) || !preg_match('/^\d{6}$/', $code)) {
        $_SESSION['error'] = 'Please enter a valid 6-digit code.';
        header('Location: setup_2fa.php');
        exit;
    }
    
    // Verify the code
    if ($g->verifyCode($secret, $code, 2)) {
        // Success - clear temporary session variables
        unset($_SESSION['new_user_id']);
        unset($_SESSION['new_user_email']);
        unset($_SESSION['new_user_secret']);
        
        $_SESSION['success'] = 'Account verified successfully! You can now log in.';
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['error'] = 'Invalid 2FA code. Please try again.';
        header('Location: setup_2fa.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup 2FA - ScholarSpot</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&family=Hind:wght@400;500&family=Hind+Guntur:wght@600&display=swap" rel="stylesheet">
    <style>
        .setup-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 40px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            text-align: center;
        }
        .qr-code {
            margin: 30px 0;
            padding: 20px;
            background: #f8fafc;
            border-radius: 8px;
        }
        .qr-code img {
            max-width: 100%;
            height: auto;
        }
        .secret-key {
            background: #f1f5f9;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
            word-break: break-all;
            font-family: monospace;
            font-size: 14px;
        }
        .instructions {
            text-align: left;
            margin: 20px 0;
            line-height: 1.6;
        }
        .instructions ol {
            padding-left: 20px;
        }
        .instructions li {
            margin: 10px 0;
        }
        .verification-section {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }
        input[name="code"] {
            width: 100%;
            padding: 12px;
            margin: 15px 0;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            box-sizing: border-box;
            text-align: center;
            font-size: 1.2em;
            letter-spacing: 2px;
            transition: border-color 0.3s;
        }
        input[name="code"]:focus {
            border-color: #2563eb;
            outline: none;
        }
        button {
            background-color: #10b981;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            width: 100%;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #059669;
        }
        .warning {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            color: #92400e;
            padding: 12px;
            border-radius: 6px;
            margin: 20px 0;
            font-size: 14px;
        }
        .error {
            color: #ef4444;
            background-color: #fee2e2;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #fca5a5;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .verify-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 10px;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <img src="images/logo.png" width="100" style="margin-bottom: 20px;">
        <h2>Setup Two-Factor Authentication</h2>
        <p>Secure your account with 2FA</p>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="instructions">
            <ol>
                <li>Download a 2FA app like <strong>Google Authenticator</strong>, <strong>Authy</strong>, or <strong>Microsoft Authenticator</strong></li>
                <li>Open the app and scan the QR code below</li>
                <li>Or manually enter the secret key</li>
                <li>Enter the 6-digit code from your app to verify</li>
            </ol>
        </div>

        <div class="qr-code">
            <img src="<?= htmlspecialchars($qrCodeUrl) ?>" alt="2FA QR Code">
        </div>

        <div class="secret-key">
            <strong>Secret Key (Manual Entry):</strong><br>
            <?= htmlspecialchars($secret) ?>
        </div>

        <div class="warning">
            ⚠️ <strong>Important:</strong> Save this secret key in a safe place. You'll need it if you lose access to your authenticator app.
        </div>

        <div class="verification-section">
            <div class="verify-badge">
                🔒 Verify Your Setup
            </div>
            <p style="margin-bottom: 10px; font-size: 14px; color: #64748b;">
                Enter the 6-digit code from your authenticator app
            </p>
            
            <form method="POST">
                <input name="code" type="text" placeholder="000000" maxlength="6" pattern="\d{6}" required autofocus>
                <button type="submit">Verify & Complete Setup</button>
            </form>
        </div>
    </div>
</body>
</html>