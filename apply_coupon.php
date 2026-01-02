<?php
session_start();
include 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$couponId = $data['coupon_id'] ?? '';
$userId = $data['user_id'] ?? 0;

if ($couponId && $userId) {
    $stmt = $pdo->prepare("
        SELECT c.amount 
        FROM coupon c
        JOIN user_coupons uc ON c.promo_id = uc.promo_id
        WHERE uc.user_id = ? 
          AND uc.promo_id = ? 
          AND uc.used = 0 
          AND c.date_exp >= CURDATE()
    ");
    $stmt->execute([$userId, $couponId]);
    $coupon = $stmt->fetch();

    if ($coupon) {
        echo json_encode(['success' => true, 'amount' => $coupon['amount']]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid, expired, or already used coupon.']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Coupon ID is required.']);
