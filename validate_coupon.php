<?php
session_start();
require 'db_connect.php';
header('Content-Type: application/json');

// 1. Check User Login
if (!isset($_SESSION['User_ID'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

$userId = $_SESSION['User_ID'];
$code = $_POST['code'] ?? '';

// 2. Validate Input
if (empty($code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code.']);
    exit;
}

try {
    // 3. Query: Check if Coupon exists, is Active, Not Expired, AND Claimed by this User
    // We join 'coupon' with 'user_coupons' to ensure ownership
    $stmt = $pdo->prepare("
        SELECT c.promo_id, c.code, c.amount, c.discount_type 
        FROM coupon c
        JOIN user_coupons uc ON c.promo_id = uc.coupon_id
        WHERE c.code = :code 
        AND uc.user_id = :userId 
        AND c.date_exp >= CURDATE() 
        AND c.status = 0 
        LIMIT 1
    ");

    $stmt->execute(['code' => $code, 'userId' => $userId]);
    $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($coupon) {
        // Valid Coupon Found
        echo json_encode([
            'success' => true, 
            'coupon' => $coupon,
            'message' => 'Coupon applied successfully!'
        ]);
    } else {
        // Coupon invalid or not claimed
        echo json_encode([
            'success' => false, 
            'message' => 'Invalid code, expired, or you haven\'t claimed this voucher yet.'
        ]);
    }

} catch (Exception $e) {
    error_log("Coupon validation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'System error. Please try again.']);
}
?>