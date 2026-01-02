<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Get form data and sanitize
        $customer_name = trim($_POST['name'] ?? '');
        $customer_email = trim($_POST['email'] ?? '');
        $customer_phone = trim($_POST['phone'] ?? '');
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $postal_code = trim($_POST['postal_code'] ?? '');
        $payment_method = trim($_POST['payment_method'] ?? '');
        $order_notes = trim($_POST['order_notes'] ?? '');
        $user_id = intval($_POST['user_id'] ?? 0);
        
        // Coupon data passed from checkout.php
        $coupon_id = intval($_POST['coupon_id'] ?? 0);
        
        $cart_data = json_decode($_POST['cart_data'] ?? '{}', true);

        // Validate required fields
        if (empty($customer_name) || empty($customer_email) || empty($customer_phone) || 
            empty($shipping_address) || empty($city) || empty($postal_code) || 
            empty($payment_method) || empty($cart_data['items']) || $user_id === 0) {
            throw new Exception('All required fields must be filled.');
        }

        // Validate email
        if (!filter_var($customer_email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format.');
        }

        // Start transaction
        $pdo->beginTransaction();

        // Calculate totals based on client data
        $subtotal = floatval($cart_data['subtotal'] ?? 0); // Original Subtotal
        $shipping = floatval($cart_data['shipping'] ?? 0);
        $discount = floatval($cart_data['discount'] ?? 0); // Discount value sent from client

        // =======================================================
        // ⭐ SERVER-SIDE COUPON VALIDATION AND APPLICATION ⭐
        // =======================================================
        
        $final_subtotal_for_db = $subtotal;
        
        // 1. Validate and calculate discount on the server-side
        if ($coupon_id > 0) {
            $stmt = $pdo->prepare("SELECT amount, discount_type, code FROM coupon WHERE promo_id = ? AND date_exp >= CURDATE() AND status = 0");
            $stmt->execute([$coupon_id]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($coupon) {
                $coupon_amount = floatval($coupon['amount']);
                $calculated_discount = 0;
                
                if ($coupon['discount_type'] === 'percent') {
                    $calculated_discount = $subtotal * ($coupon_amount / 100);
                } elseif ($coupon['discount_type'] === 'fixed') {
                    $calculated_discount = $coupon_amount;
                }
                $calculated_discount = min($calculated_discount, $subtotal);
                
                // For security, use the server-calculated discount
                $discount = $calculated_discount; 

                // 2. Mark Coupon as Used (status=1)
                $update_coupon_stmt = $pdo->prepare("UPDATE coupon SET status = 1, claimed_by = ? WHERE promo_id = ?");
                $update_coupon_stmt->execute([$user_id, $coupon_id]);
                
            } else {
                // If validation fails (expired/used/invalid), reset discount
                $discount = 0;
                error_log("Attempted to use invalid coupon ID: $coupon_id by user $user_id. Discount reset to 0.");
            }
        } 
        
        // 3. Finalize totals after discount
        $final_subtotal_for_db = $subtotal - $discount;
        $tax_calculated = $final_subtotal_for_db * 0;
        $total_amount_to_save = $final_subtotal_for_db + $shipping + $tax_calculated;
        
        // Combine full shipping address
        $full_shipping_address = $shipping_address . ', ' . $city . ' ' . $postal_code;

        // Insert orders for each item in cart
        $order_ids = [];
        
        // Prepare stock check and update statements once
        $check_stock_stmt = $pdo->prepare("SELECT Stock, Name FROM product WHERE Product_ID = ?");
        $update_stock_stmt = $pdo->prepare("UPDATE product SET Stock = Stock - ? WHERE Product_ID = ?");

        foreach ($cart_data['items'] as $item) {
            
            // ⭐ FIX FOR UNDEFINED VARIABLE: Define $product_id and $quantity here
            $product_id_raw = $item['id'];
            
            // Extract numeric ID
            if (!is_numeric($product_id_raw)) {
                preg_match('/\d+/', $product_id_raw, $matches);
                $product_id = !empty($matches) ? intval($matches[0]) : 0;
            } else {
                $product_id = intval($product_id_raw);
            }
            
            $quantity = intval($item['qty']);
            $item_total = floatval($item['price']) * $quantity;
            
            if ($product_id === 0 || $quantity === 0) {
                error_log("Skipping item due to invalid product ID or quantity: " . $item['name']);
                continue; 
            }
            // ⭐ END FIX

            // =======================================================
            // 🛑 NEW STOCK DEDUCTION LOGIC ADDED HERE 🛑
            // =======================================================
            
            // 1. Check current stock in database to prevent overselling
            $check_stock_stmt->execute([$product_id]);
            $product_db = $check_stock_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product_db) {
                throw new Exception("Product not found: " . $item['name']);
            }

            if ($product_db['Stock'] < $quantity) {
                // Rollback happens automatically in catch block
                throw new Exception("Insufficient stock for item: " . $item['name'] . ". Available: " . $product_db['Stock']);
            }

            // 2. Deduct the stock immediately
            $update_stock_stmt->execute([$quantity, $product_id]);

            // =======================================================
            // 🛑 END STOCK DEDUCTION LOGIC 🛑
            // =======================================================

            // Insert into tbl_order
            $order_sql = "INSERT INTO tbl_order 
                          (User_ID, Product_ID, Quantity, Status, Total_Amount, 
                           Customer_Name, Customer_Email, Customer_Phone, 
                           Shipping_Address, City, Postal_Code, Payment_Method, 
                           Order_Notes, Subtotal, Shipping, Tax, Order_Date) 
                           VALUES (?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $order_stmt = $pdo->prepare($order_sql);
            $order_stmt->execute([
                $user_id, $product_id, $quantity, $total_amount_to_save, // FINAL TOTAL
                $customer_name, $customer_email, $customer_phone,
                $full_shipping_address, $city, $postal_code, $payment_method,
                $order_notes, $final_subtotal_for_db, $shipping, $tax_calculated // FINAL SUBTOTAL and TAX
            ]);

            $order_id = $pdo->lastInsertId();
            $order_ids[] = $order_id;

            // Insert into order_details 
            $detail_sql = "INSERT INTO order_details (Order_ID, Product_ID, Quantity, Price) 
                           VALUES (?, ?, ?, ?)";
            $detail_stmt = $pdo->prepare($detail_sql);
            $detail_stmt->execute([$order_id, $product_id, $quantity, floatval($item['price'])]);
            
            // Insert into payment table
            $reference_num = 'REF' . date('Ymd') . rand(100000, 999999);
            $payment_sql = "INSERT INTO payment 
                            (Order_ID, User_ID, Customer_Name, Method, Quantity, Price, 
                             Amount, Total_Amount, Status, Order_Status, Reference_Num, 
                             Order_Details, Shipping_Address, Order_Date) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', 'Pending', ?, ?, ?, NOW())";
            
            $payment_stmt = $pdo->prepare($payment_sql);
            $payment_stmt->execute([
                $order_id,
                $user_id,
                $customer_name,
                $payment_method,
                $quantity,
                floatval($item['price']),
                $item_total,
                $total_amount_to_save, // FINAL TOTAL
                $reference_num,
                json_encode($item),
                $full_shipping_address
            ]);
        }
        
        // Commit transaction
        $pdo->commit();

        // Redirect to success page
        $_SESSION['checkout_success'] = 'Your order has been placed successfully!';
        $_SESSION['order_ids'] = $order_ids;
        
        header('Location: checkout_success.php?order_id=' . $order_ids[0]);
        exit;

    } catch (Exception $e) {
        // Rollback and error handling
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Checkout error: " . $e->getMessage());
        
        $_SESSION['checkout_error'] = 'Checkout failed: ' . $e->getMessage();
        header('Location: checkout.php');
        exit;
    }
} else {
    // If not POST request, redirect to cart instead
    header('Location: cart.php');
    exit;
}
?>