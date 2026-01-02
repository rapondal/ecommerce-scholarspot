<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['User_ID'];
$userName = $_SESSION['Name'] ?? 'User'; 
$message = '';
$message_type = '';

if (isset($_POST['claim_id'])) {
    $claim_id = intval($_POST['claim_id']);
    try {
        $check_stmt = $pdo->prepare("SELECT id FROM user_coupons WHERE user_id = ? AND coupon_id = ?");
        $check_stmt->execute([$userId, $claim_id]);

        if ($check_stmt->fetch()) {
            $message = "You have already claimed this coupon!";
            $message_type = 'error';
        } else {
            $coupon_stmt = $pdo->prepare("SELECT * FROM coupon WHERE promo_id = ? AND date_exp >= CURDATE() AND status = 0");
            $coupon_stmt->execute([$claim_id]);
            $coupon = $coupon_stmt->fetch(PDO::FETCH_ASSOC);

            if ($coupon) {
                $insert_stmt = $pdo->prepare("INSERT INTO user_coupons (user_id, coupon_id) VALUES (?, ?)");
                $insert_stmt->execute([$userId, $claim_id]);
                $message = "🎉 Coupon **" . htmlspecialchars($coupon['code']) . "** claimed! It will be applied at checkout.";
                $message_type = 'success';
            } else {
                $message = "Coupon is invalid or expired.";
                $message_type = 'error';
            }
        }
    } catch (PDOException $e) {
        $message = "Error: " . $e->getMessage();
        $message_type = 'error';
    }
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*, uc.id AS is_claimed
        FROM coupon c
        LEFT JOIN user_coupons uc ON c.promo_id = uc.coupon_id AND uc.user_id = ?
        WHERE c.date_exp >= CURDATE() AND c.status = 0
        ORDER BY c.date_exp ASC
    ");
    $stmt->execute([$userId]);
    $available_coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $available_coupons = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Coupons - ScholarSpot</title>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800&family=Hind:wght@400;500&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">
  
  <link rel="stylesheet" href="css/styles.css">

  <style>
    /* --- UPDATED BACKGROUND COLOR HERE --- */
    body.marketplace-page {
        background-color: #fef7ea; /* Matches Product Page Beige */
    }

    .page-header {
        background: #fff;
        padding: 40px 20px;
        text-align: center;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        margin-bottom: 30px;
    }

    .page-title {
        font-family: 'Montserrat', sans-serif;
        font-size: 2rem;
        color: #2b2a33;
        margin: 0;
    }

    .coupon-container {
        max-width: 900px;
        margin: 0 auto 50px auto;
        padding: 0 20px;
        min-height: 60vh;
    }

    .user-coupon-card {
        background-color: white;
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-left: 6px solid #d97719;
        transition: transform 0.2s;
    }

    .user-coupon-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0,0,0,0.1);
    }

    .coupon-details h3 {
        color: #2b2a33;
        margin: 0 0 5px 0;
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 1.2rem;
    }

    .coupon-details p {
        font-size: 0.95rem;
        color: #6b7280;
        margin: 3px 0;
        font-family: 'Hind', sans-serif;
    }

    .coupon-details strong {
        color: #d97719; 
    }

    .btn-claim {
        padding: 12px 25px;
        background-color: #10b981;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-family: 'Hind Guntur', sans-serif;
        transition: background 0.3s;
        min-width: 120px;
    }

    .btn-claim:hover {
        background-color: #059669;
    }

    .btn-claimed {
        padding: 12px 25px;
        background-color: #e5e7eb;
        color: #9ca3af;
        border: none;
        border-radius: 8px;
        cursor: not-allowed;
        font-weight: 600;
        font-family: 'Hind Guntur', sans-serif;
        min-width: 120px;
    }

    .alert {
        padding: 15px;
        margin-bottom: 25px;
        border-radius: 8px;
        font-weight: 600;
        text-align: center;
    }
    .alert-success { background-color: #d1fae5; color: #065f46; border: 1px solid #34d399; }
    .alert-error { background-color: #fee2e2; color: #991b1b; border: 1px solid #f87171; }

    .no-coupon-state {
        text-align: center;
        padding: 50px;
        color: #6b7280;
        background: white;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }

    @media (max-width: 600px) {
        .user-coupon-card { flex-direction: column; text-align: center; gap: 15px; }
        .user-coupon-card { border-left: none; border-top: 6px solid #d97719; }
        .btn-claim, .btn-claimed { width: 100%; }
    }
  </style>
</head>
<body class="marketplace-page">

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
        <li><a href="user_coupon.php" class="active">My Coupons</a></li>
        <li><a href="userprofile.php">Profile</a></li>
      </ul>
      <div class="nav-actions">
        <span class="user-greeting">Hi, <?= htmlspecialchars($userName) ?>!</span>
        <a href="cart.php" class="nav-cart">
          🛒 Cart
          <span id="cart-count" class="cart-badge">0</span>
        </a>
        <a href="logout.php" class="nav-logout">Logout</a>
      </div>
    </div>
  </nav>

  <div class="page-header">
    <h1 class="page-title">Available Vouchers</h1>
  </div>

  <div class="coupon-container">
    
    <?php if ($message): ?>
        <div class="alert <?= $message_type === 'success' ? 'alert-success' : 'alert-error' ?>">
            <?= $message ?>
        </div>
    <?php endif; ?>

    <?php if (empty($available_coupons)): ?>
        <div class="no-coupon-state">
            <div style="font-size: 3rem; margin-bottom: 10px;">🎟️</div>
            <h3>No coupons available</h3>
            <p>Check back later for new deals and discounts!</p>
        </div>
    <?php else: ?>
        <?php foreach ($available_coupons as $coupon): 
            $is_claimed = !empty($coupon['is_claimed']);
            $btn_class = $is_claimed ? 'btn-claimed' : 'btn-claim';
            $btn_text = $is_claimed ? 'Claimed' : 'Claim Now';
        ?>
            <div class="user-coupon-card">
                <div class="coupon-details">
                    <h3><?= htmlspecialchars($coupon['type']) ?></h3>
                    <p style="font-family: monospace; background: #f3f4f6; display: inline-block; padding: 2px 8px; border-radius: 4px; color: #d97719; font-weight: bold;">
                        <?= htmlspecialchars($coupon['code']) ?>
                    </p>
                    <p>
                        Discount: <strong>
                            <?php 
                                if ($coupon['discount_type'] == 'percent') {
                                    echo htmlspecialchars($coupon['amount']) . '% OFF';
                                } else {
                                    echo '₱' . number_format($coupon['amount'], 2) . ' Fixed OFF';
                                }
                            ?>
                        </strong>
                    </p>
                    <p>Valid until: <?= date('F j, Y', strtotime($coupon['date_exp'])) ?></p>
                    <p style="margin-top: 5px; font-style: italic;"><?= htmlspecialchars($coupon['description']) ?></p>
                </div>
                
                <form method="POST">
                    <input type="hidden" name="claim_id" value="<?= $coupon['promo_id'] ?>">
                    <button type="submit" class="<?= $btn_class ?>" <?= $is_claimed ? 'disabled' : '' ?>>
                        <?= $btn_text ?>
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

  </div>

  <footer class="marketplace-footer">
    <p>&copy; 2024 ScholarSpot. All rights reserved.</p>
  </footer>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
        const count = cart.filter(item => !item.removed).length;
        document.getElementById('cart-count').textContent = count;
    });
  </script>

</body>
</html>