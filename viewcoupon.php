<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

// Get user info from session
$userName = $_SESSION['Name'] ?? 'User';
$userId = $_SESSION['User_ID'] ?? 0;

// Database connection
include 'db_connect.php';

// Fetch all coupons
try {
    $stmt = $pdo->query("SELECT * FROM coupon ORDER BY date_exp DESC");
    $coupons = $stmt->fetchAll();
} catch (PDOException $e) {
    $coupons = [];
    $errorMsg = "Error fetching coupons: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Coupons - ScholarSpot</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .coupon-card {
            background-color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0px 6px 18px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .coupon-card h3 { margin-bottom:10px; color: var(--primary-accent);}
        .coupon-card p { margin:6px 0; }
        .claim-btn {
            display:inline-block; margin-top:12px; padding:10px 16px;
            background: var(--secondary-accent); color:white; text-decoration:none;
            border-radius:8px; font-weight:bold; transition:0.3s;
        }
        .claim-btn:hover { background:#28a745; }
        .status { font-weight:bold; padding:6px 10px; border-radius:6px; display:inline-block; margin-top:8px; }
        .active-status { background-color: var(--success-bg); color: var(--success-text); }
        .expired-status { background-color: var(--error-bg); color: var(--error-text); }
        .no-coupon { text-align:center; color:var(--neutral); padding:20px; }
        .disabled-btn { opacity:0.5; cursor:not-allowed; }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="marketplace-nav">
    <div class="nav-container">
      <div class="nav-logo">
        <img src="images/logonew.png" alt="ScholarSpot" width="50">
        <span>ScholarSpot</span>
      </div>
      <ul class="nav-menu">
        <li><a href="dashboard.php">Home</a></li>
        <li><a href="#products">Products</a></li>
        <li><a href="#categories">Categories</a></li>
        <li><a href="orderdetails.php">My Orders</a></li>
        <li><a href="viewcoupon.php" class="active">View Coupons</a></li>
      </ul>
      <div class="nav-actions">
        <span class="user-greeting">Hi, <?= htmlspecialchars($userName) ?>!</span>
        <a href="cart.php" class="nav-cart">
          🛒 Cart <span id="cart-count" class="cart-badge">0</span>
        </a>
        <a href="logout.php" class="nav-logout">Logout</a>
      </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Available Promo Vouchers</h1>
    </div>

    <?php
    if (!empty($errorMsg)) {
        echo "<p class='no-coupon'>" . htmlspecialchars($errorMsg) . "</p>";
    } elseif ($coupons) {
        $today = date('Y-m-d');
        foreach ($coupons as $row) {
            $expiryDate = $row['date_exp'];
            $isExpired = ($expiryDate < $today);
            $statusLabel = $isExpired ? "Expired" : "Active";

            echo "<div class='coupon-card'>";
            echo "<h3>" . htmlspecialchars($row['type']) . "</h3>";
            echo "<p><strong>Description:</strong> " . htmlspecialchars($row['description']) . "</p>";
            echo "<p><strong>Amount:</strong> " . htmlspecialchars($row['amount']) . "</p>";
            echo "<p><strong>Expiry:</strong> " . htmlspecialchars($expiryDate) . "</p>";

            echo $isExpired 
                ? "<span class='status expired-status'>Expired</span>"
                : "<span class='status active-status'>Active</span>";

            if ($isExpired) {
                echo "<br><button class='claim-btn disabled-btn' disabled>Expired</button>";
            } elseif ((int)$row['status'] === 0) {  // Active
                echo "<br><a class='claim-btn' href='claim.php?id=" . $row['promo_id'] . "'>Claim</a>";
            } else {  // Claimed
                echo "<br><button class='claim-btn disabled-btn' disabled>Already Claimed</button>";
            }

            echo "</div>";
        }
    } else {
        echo "<p class='no-coupon'>No coupons available right now.</p>";
    }
    ?>
</div>

</body>
</html>
