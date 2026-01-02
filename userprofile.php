<?php
session_start();
require 'db_connect.php';

// 1. Security Check
if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['User_ID'];
$message = '';
$msg_type = '';

// 2. Handle Password Update Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    // Fetch current hash
    $stmt = $pdo->prepare("SELECT Password FROM user WHERE User_ID = ?");
    $stmt->execute([$user_id]);
    $current_hash = $stmt->fetchColumn();

    if (password_verify($current_pass, $current_hash)) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 6) {
                $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
                $update = $pdo->prepare("UPDATE user SET Password = ? WHERE User_ID = ?");
                $update->execute([$new_hash, $user_id]);
                $message = "Password updated successfully!";
                $msg_type = "success";
            } else {
                $message = "New password must be at least 6 characters long.";
                $msg_type = "error";
            }
        } else {
            $message = "New passwords do not match.";
            $msg_type = "error";
        }
    } else {
        $message = "Incorrect current password.";
        $msg_type = "error";
    }
}

// 3. Fetch User Data for Display
$stmt = $pdo->prepare("SELECT Name, Email, Role, Profile_Picture FROM user WHERE User_ID = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Default avatar if none exists
$avatar = !empty($user['Profile_Picture']) ? $user['Profile_Picture'] : 'https://ui-avatars.com/api/?name='.urlencode($user['Name']).'&background=d97719&color=fff';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - ScholarSpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800&family=Hind:wght@400;500&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* Reuse core variables */
        :root {
            --color-primary: #d97719;
            --color-bg: #f1efea;
            --color-text: #2b2a33;
        }

        body {
            background-color: var(--color-bg);
            color: var(--color-text);
            font-family: 'Hind', sans-serif;
        }

        /* Profile Container */
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
        }

        /* Card Styles */
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }

        /* Left Column: Identity */
        .profile-identity {
            text-align: center;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fef7ea;
            margin-bottom: 15px;
        }

        .profile-name {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0;
            color: var(--color-primary);
        }

        .profile-role {
            display: inline-block;
            background: #f3f4f6;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            color: #666;
            margin-top: 5px;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Right Column: Settings */
        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.2rem;
            border-bottom: 2px solid #f1efea;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #2b2a33;
        }

        .info-group {
            margin-bottom: 20px;
        }

        .info-label {
            display: block;
            font-size: 0.9rem;
            font-weight: 600;
            color: #888;
            margin-bottom: 5px;
        }

        .info-value {
            font-size: 1.1rem;
            font-weight: 500;
            padding: 10px;
            background: #fafafa;
            border-radius: 6px;
            border: 1px solid #eee;
            color: #555;
        }

        /* Form Styles */
        .form-group {
            margin-bottom: 15px;
        }

        .form-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: 'Hind', sans-serif;
            font-size: 1rem;
            transition: 0.3s;
            box-sizing: border-box;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--color-primary);
        }

        .btn-save {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            width: 100%;
            transition: 0.3s;
        }

        .btn-save:hover {
            background-color: #b45309;
        }

        /* Alerts */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }
        .alert-success { background: #d1fae5; color: #065f46; }
        .alert-error { background: #fee2e2; color: #991b1b; }

        @media (max-width: 768px) {
            .profile-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <nav class="marketplace-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="images/logonew.png" alt="ScholarSpot" width="50">
                <span>ScholarSpot</span>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Home</a></li>
                <li><a href="products.php">Products</a></li> 
                <li><a href="orderdetails.php">My Orders</a></li>
                <li><a href="user_coupon.php">My Coupons</a></li>
                <li><a href="userprofile.php" class="active">Profile</a></li>
            </ul>
            <div class="nav-actions">
                <span class="user-greeting">Hi, <?= htmlspecialchars($user['Name']) ?>!</span>
                <a href="cart.php" class="nav-cart">
                    🛒 Cart <span id="cart-count" class="cart-badge">0</span>
                </a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="profile-container">
        
        <div class="profile-card profile-identity">
            <img src="<?= htmlspecialchars($avatar) ?>" alt="Profile" class="profile-avatar">
            <h2 class="profile-name"><?= htmlspecialchars($user['Name']) ?></h2>
            <span class="profile-role"><?= htmlspecialchars($user['Role']) ?></span>
            
            <div style="margin-top: 30px; text-align: left;">
                <div class="info-group">
                    <label class="info-label">Email Address</label>
                    <div class="info-value"><?= htmlspecialchars($user['Email']) ?></div>
                </div>
                <div class="info-group">
                    <label class="info-label">Password</label>
                    <div class="info-value">••••••••••••</div>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <h3 class="section-title">Security Settings</h3>

            <?php if($message): ?>
                <div class="alert alert-<?= $msg_type ?>"><?= $message ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label class="info-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required placeholder="Enter current password">
                </div>

                <div class="form-group">
                    <label class="info-label">New Password</label>
                    <input type="password" name="new_password" class="form-input" required minlength="6" placeholder="At least 6 characters">
                </div>

                <div class="form-group">
                    <label class="info-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-input" required placeholder="Re-enter new password">
                </div>

                <button type="submit" name="update_password" class="btn-save">Update Password</button>
            </form>
        </div>
    </div>
    

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
            const count = cart.filter(item => !item.removed).length;
            document.getElementById('cart-count').textContent = count;
        });
    </script>

</body>
</html>