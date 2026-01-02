<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit;
}

if (isset($_GET['id'])) {
    $couponId = intval($_GET['id']);
    $userId = intval($_SESSION['User_ID']);

    try {
        // Check if coupon is valid
        $stmt = $pdo->prepare("SELECT * FROM coupon WHERE promo_id = :couponId AND status = 0 AND date_exp >= CURDATE()");
        $stmt->execute(['couponId' => $couponId]);
        $coupon = $stmt->fetch();

        if ($coupon) {
            // Mark as claimed by user
            $insert = $pdo->prepare("INSERT INTO user_coupons (user_id, coupon_id) VALUES (:userId, :couponId)");
            $insert->execute([
                'userId' => $userId,
                'couponId' => $couponId
            ]);

            // Optionally update coupon status to claimed (if single claim)
            $update = $pdo->prepare("UPDATE coupon SET status = 1 WHERE promo_id = :couponId");
            $update->execute(['couponId' => $couponId]);

            header("Location: viewcoupon.php?claimed=1");
        } else {
            header("Location: viewcoupon.php?claimed=0");
        }

        exit;

    } catch (PDOException $e) {
        error_log("Coupon claim error: " . $e->getMessage());
        header("Location: viewcoupon.php?claimed=0");
        exit;
    }
} else {
    header("Location: viewcoupon.php?claimed=0");
    exit;
}
