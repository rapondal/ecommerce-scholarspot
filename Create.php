<?php
include 'db_connect.php'; // PDO connection

// Automatically mark expired coupons as status = 2
try {
    $pdo->exec("UPDATE coupon SET status = 2 WHERE date_exp < CURDATE() AND status != 2");
} catch (PDOException $e) {
    error_log("Error updating expired coupons: " . $e->getMessage());
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $pdo->prepare("DELETE FROM coupon WHERE promo_id = ?");
    $stmt->execute([$delete_id]);
    header("Location: Create.php");
    exit;
}

// Handle create coupon form submission
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = trim($_POST['type']);
    $code = strtoupper(trim($_POST['code'])); // make uppercase
    $description = trim($_POST['description']);
    $amount = floatval($_POST['amount']);
    $date_exp = $_POST['date_exp'];

    if (!$type || !$code || !$description || !$amount || !$date_exp) {
        $errors[] = "Please fill in all fields.";
    }

    if (empty($errors)) {
        // 1. Check if Code exists BEFORE trying to insert
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM coupon WHERE code = ?");
        $checkStmt->execute([$code]);
        if ($checkStmt->fetchColumn() > 0) {
            $errors[] = "❌ Error: The coupon code '<strong>$code</strong>' already exists. Please use a unique code.";
        } else {
            // 2. Insert if unique
            try {
                $stmt = $pdo->prepare("INSERT INTO coupon (type, code, description, amount, date_exp, status) VALUES (?, ?, ?, ?, ?, 0)");
                $stmt->execute([$type, $code, $description, $amount, $date_exp]);
                $success = "✅ Coupon created successfully!";
            } catch (PDOException $e) {
                $errors[] = "Error creating coupon: " . $e->getMessage();
            }
        }
    }
}

// Fetch all coupons
try {
    $stmt = $pdo->query("SELECT * FROM coupon ORDER BY date_exp DESC");
    $coupons = $stmt->fetchAll();
} catch (PDOException $e) {
    $coupons = [];
    $errors[] = "Error fetching coupons: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Coupon Management - ScholarSpot</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="marketplace-nav">
    <div class="nav-container">
        <a href="Create.php" class="nav-logo">Coupon System</a>
        <ul class="nav-menu">
        </ul>
    </div>
</nav>

<div class="container">

    <!-- Header -->
    <div class="page-header">
        <h1>Coupon Management System</h1>
    </div>

    <!-- Form Card -->
    <div class="form-card">
        <h2>Create a New Coupon</h2>

        <?php if ($errors): ?>
            <div class="msg error"><?php echo implode("<br>", $errors); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="msg success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="type">Coupon Type</label>
                <input type="text" id="type" name="type" required>
            </div>

            <div class="form-group">
                <label for="code">Coupon Code</label>
                <input type="text" id="code" name="code" placeholder="e.g., SCHOLAR20" required>
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" required></textarea>
            </div>

            <div class="form-group">
                <label for="amount">Amount</label>
                <input type="number" id="amount" name="amount" min="0" step="0.01" required>
            </div>

            <div class="form-group">
                <label for="date_exp">Expiry Date</label>
                <input type="date" id="date_exp" name="date_exp" required>
            </div>

            <button type="submit">Create Coupon</button>
        </form>
    </div>

    <!-- Coupons List -->
    <div class="page-header" style="margin-top:40px;">
        <h2>Available Promo Vouchers</h2>
    </div>

    <?php if ($coupons): ?>
        <?php foreach($coupons as $row): 
            $statusLabel = "Active"; 
            $statusClass = "active-status";
            if ($row['status'] == 1){ $statusLabel = "Claimed"; $statusClass = "claimed-status"; }
            elseif($row['status'] == 2){ $statusLabel = "Expired"; $statusClass = "expired-status"; }
        ?>
            <div class="coupon-card">
                <div class="card-header">
                    <h3><?php echo htmlspecialchars($row['type']); ?></h3>
                    <p><strong>Code:</strong> <?php echo htmlspecialchars($row['code']); ?></p>
                <p><strong>Description:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
                <p><strong>Amount:</strong> <?php echo htmlspecialchars($row['amount']); ?></p>
                <p><strong>Expiry:</strong> <?php echo htmlspecialchars($row['date_exp']); ?></p>
                <span class="status <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                    <form method="get" onsubmit="return confirm('Are you sure you want to delete this coupon?');">
                        <button class="delete-btn" type="submit">Delete</button>
                        <input type="hidden" name="delete_id" value="<?php echo $row['promo_id']; ?>">
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-coupon">No coupons available right now.</p>
    <?php endif; ?>

</div>

</body>
</html>
