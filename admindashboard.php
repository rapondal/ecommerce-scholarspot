<?php

session_start();
include 'db_connect.php';

// ====================================================================
// 1. SECURITY & AUTHENTICATION
// ====================================================================

if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['Role']) || $_SESSION['Role'] !== 'Admin') {
    header('Location: dashboard.php');
    exit;
}

 $userName = $_SESSION['Name'] ?? 'Admin';
 $page = $_GET['page'] ?? 'dashboard';

// ====================================================================
// 2. GLOBAL HELPERS
// ====================================================================

function handleImageUpload($fileInputName, $existingImagePath = '')
{
    $uploadDir = 'images/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$fileInputName];
        $fileExt = strtolower(pathinfo(basename($file['name']), PATHINFO_EXTENSION));
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($fileExt, $allowedTypes)) {
            throw new Exception('Invalid file type. Only JPG, PNG, GIF, WEBP allowed.');
        }

        $newFileName = uniqid('product_', true) . '.' . $fileExt;
        $uploadPath = $uploadDir . $newFileName;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            if ($existingImagePath && file_exists($existingImagePath) && strpos($existingImagePath, 'http') === false) {
                unlink($existingImagePath);
            }
            return $uploadPath;
        } else {
            throw new Exception('Failed to move uploaded file.');
        }
    }
    return $existingImagePath;
}

try {
    $pdo->exec("UPDATE coupon SET status = 2 WHERE date_exp < CURDATE() AND status != 2");
} catch (PDOException $e) {
    error_log("Error updating expired coupons: " . $e->getMessage());
}

// ====================================================================
// 3. PAGE SPECIFIC LOGIC
// ====================================================================

 $stats = ['total_revenue' => 0, 'total_orders' => 0, 'total_products' => 0, 'total_users' => 0];
 $recent_orders = [];
 $recent_products = [];
 $low_stock_products = [];
 $shipment_orders = [];
 $users = [];
 $logs = [];
 $report_sales = [];
 $report_status = [];
 $report_inventory = [];
 $error_message = '';
 $success_message = '';
 $product_success = '';
 $product_error = '';
 $coupon_success = '';

// --- GLOBAL SEARCH HANDLING ---
 $search_term = trim($_GET['search'] ?? '');

if ($page === 'dashboard') {
    try {
        // Ensure accurate revenue: Sum Total_Amount only for orders that were NOT cancelled.
        $stats['total_revenue'] = $pdo->query("SELECT SUM(Total_Amount) FROM payment WHERE Order_Status != 'Cancelled'")->fetchColumn();

        // Ensure accurate total orders count: Count all payment records.
        $stats['total_orders'] = $pdo->query("SELECT COUNT(Payment_ID) FROM payment")->fetchColumn();

        // Count total unique products
        $stats['total_products'] = $pdo->query("SELECT COUNT(Product_ID) FROM product")->fetchColumn();

        // Count total users
        $stats['total_users'] = $pdo->query("SELECT COUNT(User_ID) FROM user")->fetchColumn();

        $recent_orders = $pdo->query("SELECT Payment_ID, Customer_Name, Total_Amount, Order_Status, Order_Date FROM payment ORDER BY Order_Date DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $recent_products = $pdo->query("SELECT Product_ID, Name, Price, Stock, Images FROM product ORDER BY Product_ID DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        $low_stock_products = $pdo->query("SELECT Product_ID, Name, Stock, Images FROM product WHERE Stock <= 10 ORDER BY Stock ASC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "DB Error: " . $e->getMessage();
    }
} elseif ($page === 'orders') {
    
    // --- START FILTERING LOGIC ---
    $filter_status = strtolower($_GET['status'] ?? 'all');
    $valid_filters = [
        'all', 
        'pending', 
        'confirmed', 
        'shipped', 
        'delivered', 
        'cancelled'
    ];
    
    // Sanitize filter input
    if (!in_array($filter_status, $valid_filters)) {
        $filter_status = 'all';
    }
    
    $where_clause = "";
    $params = [];
    
    if ($filter_status !== 'all') {
        if ($filter_status === 'pending') {
            // FIX: Use IN clause for multiple pending statuses
            $where_clause = " WHERE p.Order_Status IN ('Pending', 'Pending Confirmation')";
        } else {
             // Capitalize first letter for SQL lookup (Confirmed, Shipped, etc.)
             $sql_status = ucfirst($filter_status);
             $where_clause = " WHERE p.Order_Status = :status";
             $params[':status'] = $sql_status;
        }
    }
    // --- END FILTERING LOGIC ---

    // --- START SEARCH LOGIC ---
    if (!empty($search_term)) {
        // If WHERE clause already exists, append AND, otherwise start WHERE
        $prefix = ($where_clause === "") ? " WHERE " : " AND ";
        $where_clause .= $prefix . " (p.Order_ID LIKE :search OR p.Customer_Name LIKE :search)";
        $params[':search'] = "%$search_term%";
    }
    // --- END SEARCH LOGIC ---

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['confirm_order'])) {
            try {
                $input_order_id = $_POST['order_id'];
                $findPayment = $pdo->prepare("SELECT Payment_ID FROM payment WHERE Order_ID = ?");
                $findPayment->execute([$input_order_id]);
                $linkedPaymentId = $findPayment->fetchColumn();

                if (!$linkedPaymentId) throw new Exception("Error: Could not find linked Payment ID for Order #{$input_order_id}.");

                $stmt = $pdo->prepare("UPDATE payment SET Order_Status = 'Confirmed' WHERE Order_ID = ?");
                $stmt->execute([$input_order_id]);

                $pdo->prepare("UPDATE tbl_order SET Status = 'Confirmed' WHERE Order_ID = ?")->execute([$input_order_id]);

                $success_message = "Order #{$input_order_id} confirmed successfully.";
                header("Location: admindashboard.php?page=orders&status=" . urlencode($filter_status));
                exit;
            } catch (Exception $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        } elseif (isset($_POST['reject_order'])) {
            try {
                $input_order_id = $_POST['order_id'];
                $findPayment = $pdo->prepare("SELECT Payment_ID FROM payment WHERE Order_ID = ?");
                $findPayment->execute([$input_order_id]);
                $linkedPaymentId = $findPayment->fetchColumn();

                if (!$linkedPaymentId) throw new Exception("Error: Could not find linked Payment ID for Order #{$input_order_id}.");

                $stmt = $pdo->prepare("UPDATE payment SET Order_Status = 'Cancelled' WHERE Order_ID = ?");
                $stmt->execute([$input_order_id]);

                $pdo->prepare("UPDATE tbl_order SET Status = 'Cancelled' WHERE Order_ID = ?")->execute([$input_order_id]);

                $success_message = "Order #{$input_order_id} cancelled.";
                header("Location: admindashboard.php?page=orders&status=" . urlencode($filter_status));
                exit;
            } catch (Exception $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        }
    }
    try {
        // Fetch Order_ID explicitly, applying the filter
        $sql = "SELECT p.*, u.Name AS UserName 
                FROM payment p 
                LEFT JOIN user u ON p.User_ID = u.User_ID 
                {$where_clause} 
                ORDER BY p.Order_Date DESC"; // Latest on top
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        $error_message = "DB Error: " . $e->getMessage();
    }
} elseif ($page === 'shipment') {
    
    // --- SEARCH LOGIC FOR SHIPMENT ---
    $where_clause = "";
    $params = [];
    if (!empty($search_term)) {
        $where_clause = " WHERE (p.Order_ID LIKE :search OR p.Customer_Name LIKE :search)";
        $params[':search'] = "%$search_term%";
    }
    // -----------------------------

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $linkedOrderId = $_POST['order_id'];
        $new_status = '';
        $msg = '';
        try {
            $findPayment = $pdo->prepare("SELECT Payment_ID FROM payment WHERE Order_ID = ?");
            $findPayment->execute([$linkedOrderId]);
            $payment_id = $findPayment->fetchColumn();
            if (!$payment_id) throw new Exception("Error: Could not find linked Payment ID for Order #{$linkedOrderId}.");

            if (isset($_POST['create_shipment_map'])) {
                $from_addr = trim($_POST['from_address']);
                $to_addr = trim($_POST['to_address']);

                if (empty($from_addr) || empty($to_addr)) {
                    throw new Exception("Error: Both FROM and TO addresses are required to create a shipment.");
                }

                $new_status = 'Shipped';
                $msg = "Order #$linkedOrderId marked as Shipped. Shipment created from $from_addr to $to_addr!";

                $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
                $deliveryTime = clone $now;
                $deliveryTime->modify('+2 minutes'); 
                $start_ts = $now->format('Y-m-d H:i:s');
                $delivery_ts = $deliveryTime->format('Y-m-d H:i:s');

                $pdo->prepare("UPDATE payment SET Order_Status = ? WHERE Order_ID = ?")->execute([$new_status, $linkedOrderId]);
                $pdo->prepare("UPDATE tbl_order SET Status = ? WHERE Order_ID = ?")->execute([$new_status, $linkedOrderId]);

                $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM shipment WHERE Order_ID = ?");
                $checkStmt->execute([$linkedOrderId]);

                if ($checkStmt->fetchColumn() == 0) {
                    $pdo->prepare("INSERT INTO shipment (Order_ID, Tracking_Num, Courier, Status, Estimated_Delivery_Date, Driver_Start_Timestamp, From_Address, To_Address) VALUES (?, 10000 + ?, 'In-house Delivery', 'In Transit', ?, ?, ?, ?)")->execute([$linkedOrderId, $linkedOrderId, $delivery_ts, $start_ts, $from_addr, $to_addr]);
                } else {
                    $pdo->prepare("UPDATE shipment SET Status = 'In Transit', Estimated_Delivery_Date = ?, Driver_Start_Timestamp = ?, From_Address = ?, To_Address = ? WHERE Order_ID = ?")->execute([$delivery_ts, $start_ts, $from_addr, $to_addr, $linkedOrderId]);
                }
            } elseif (isset($_POST['cancel_shipment'])) {
                $new_status = 'Confirmed';
                $msg = "Order #$linkedOrderId reverted to Confirmed. Route information cleared.";

                $pdo->prepare("UPDATE payment SET Order_Status = ? WHERE Order_ID = ?")->execute([$new_status, $linkedOrderId]);
                $pdo->prepare("UPDATE tbl_order SET Status = ? WHERE Order_ID = ?")->execute([$new_status, $linkedOrderId]);

                $pdo->prepare("UPDATE shipment SET Status = 'Confirmed', Driver_Start_Timestamp = NULL, Estimated_Delivery_Date = NULL, From_Address = NULL, To_Address = NULL WHERE Order_ID = ?")->execute([$linkedOrderId]);
            }

            if (!empty($msg)) {
                $success_message = $msg;
            }
        } catch (Exception $e) {
            $error_message = "Action failed: " . $e->getMessage();
        }
    }
    try {
        // Fetch Order_ID explicitly, including the new address columns
        // CHANGED ORDER BY TO DESC AS REQUESTED
        $sql = "SELECT p.*, u.Name AS UserName, u.Email AS UserEmail, p.Order_ID, s.Estimated_Delivery_Date, s.Driver_Start_Timestamp, s.From_Address, s.To_Address, p.Shipping_Address FROM payment p LEFT JOIN user u ON p.User_ID = u.User_ID LEFT JOIN tbl_order o ON p.Order_ID = o.Order_ID LEFT JOIN shipment s ON o.Order_ID = s.Order_ID {$where_clause} ORDER BY p.Order_Date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $shipment_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Error: " . $e->getMessage();
    }
} elseif ($page === 'coupons') {
    if (isset($_GET['delete_id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM coupon WHERE promo_id = ?");
            $stmt->execute([intval($_GET['delete_id'])]);
            header("Location: admindashboard.php?page=coupons");
            exit;
        } catch (PDOException $e) {
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_coupon'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO coupon (type, code, description, amount, date_exp, status) VALUES (?, ?, ?, ?, ?, 0)");
            $stmt->execute([$_POST['type'], strtoupper($_POST['code']), $_POST['description'], $_POST['amount'], $_POST['date_exp']]);
            $coupon_success = "Coupon created!";
        } catch (PDOException $e) {
            $error_message = "Error: " . $e->getMessage();
        }
    }
    try {
        $coupons = $pdo->query("SELECT * FROM coupon ORDER BY date_exp DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
    }
} elseif ($page === 'products') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['add_product'])) {
            try {
                $imagePath = handleImageUpload('image_file');
                if (empty($imagePath)) throw new Exception('Image required.');
                $stmt = $pdo->prepare("INSERT INTO product (Category_ID, Name, Description, Price, Stock, Availability, Images) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_POST['category_id'], $_POST['name'], $_POST['description'], $_POST['price'], $_POST['stock'], $_POST['availability'], $imagePath]);
                $_SESSION['product_msg'] = ['type' => 'success', 'text' => 'Product added!'];
                header('Location: admindashboard.php?page=products');
                exit;
            } catch (Exception $e) {
                $product_error = $e->getMessage();
            }
        } elseif (isset($_POST['update_product'])) {
            try {
                $currentImagePath = $_POST['current_image_path'] ?? '';
                $imagePath = handleImageUpload('image_file', $currentImagePath);
                $stmt = $pdo->prepare("UPDATE product SET Category_ID=?, Name=?, Description=?, Price=?, Stock=?, Availability=?, Images=? WHERE Product_ID=?");
                $stmt->execute([$_POST['category_id'], $_POST['name'], $_POST['description'], $_POST['price'], $_POST['stock'], $_POST['availability'], $imagePath, $_POST['product_id']]);
                $_SESSION['product_msg'] = ['type' => 'success', 'text' => 'Product updated!'];
                header('Location: admindashboard.php?page=products');
                exit;
            } catch (Exception $e) {
                $product_error = $e->getMessage();
            }
        } elseif (isset($_POST['delete_product'])) {
            try {
                $stmt = $pdo->prepare("SELECT Images FROM product WHERE Product_ID = ?");
                $stmt->execute([$_POST['product_id']]);
                $image = $stmt->fetchColumn();
                if ($image && file_exists($image) && strpos($image, 'http') === false) unlink($image);
                $pdo->prepare("DELETE FROM product WHERE Product_ID = ?")->execute([$_POST['product_id']]);
                $_SESSION['product_msg'] = ['type' => 'success', 'text' => 'Product deleted!'];
                header('Location: admindashboard.php?page=products');
                exit;
            } catch (Exception $e) {
                $product_error = $e->getMessage();
            }
        }
    }
    if (isset($_SESSION['product_msg'])) {
        if ($_SESSION['product_msg']['type'] === 'success') $product_success = $_SESSION['product_msg']['text'];
        unset($_SESSION['product_msg']);
    }
    try {
        $products = $pdo->query("SELECT p.*, c.Category_Name FROM product p LEFT JOIN category c ON p.Category_ID = c.Category_ID ORDER BY p.Product_ID DESC")->fetchAll(PDO::FETCH_ASSOC);
        $categories = $pdo->query("SELECT * FROM category ORDER BY Category_Name")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = $e->getMessage();
    }
} elseif ($page === 'users') {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manage_user_id'])) {
        $uid = $_POST['manage_user_id'];
        $status = $_POST['status'];
        $new_pass = trim($_POST['new_password']);
        try {
            $sql = "UPDATE user SET Status = :status";
            $params = [':status' => $status, ':id' => $uid];
            if (!empty($new_pass)) {
                $sql .= ", Password = :pwd";
                $params[':pwd'] = password_hash($new_pass, PASSWORD_DEFAULT);
            }
            $sql .= " WHERE User_ID = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $success_message = "User updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Update failed: " . $e->getMessage();
        }
    }
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
        $idToDelete = intval($_POST['delete_user_id']);
        if ($idToDelete == $_SESSION['User_ID']) {
            $error_message = "Security Warning: You cannot delete your own account.";
        } else {
            try {
                $pdo->prepare("DELETE FROM user WHERE User_ID = ?")->execute([$idToDelete]);
                $success_message = "User deleted successfully.";
            } catch (PDOException $e) {
                $error_message = "Error: " . $e->getMessage();
            }
        }
    }
    try {
        $users = $pdo->query("SELECT * FROM user ORDER BY Role ASC, User_ID DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "DB Error: " . $e->getMessage();
    }
} elseif ($page === 'activity_logs') {
    try {
        $sql = "SELECT a.*, u.Name, u.Email, u.Role FROM activity_log a LEFT JOIN user u ON a.User_ID = u.User_ID ORDER BY a.Timestamp DESC LIMIT 100";
        $logs = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "DB Error: " . $e->getMessage();
    }
} elseif ($page === 'reports') {
    try {
        $report_sales = $pdo->query("SELECT DATE_FORMAT(Order_Date, '%Y-%m-%d') as date, SUM(Total_Amount) as total FROM payment WHERE Order_Status != 'Cancelled' AND Order_Date >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY DATE(Order_Date) ORDER BY Order_Date ASC")->fetchAll(PDO::FETCH_ASSOC);
        $report_status = $pdo->query("SELECT Order_Status, COUNT(*) as count FROM payment GROUP BY Order_Status")->fetchAll(PDO::FETCH_ASSOC);
        $report_inventory = $pdo->query("SELECT Name, Category_ID, Stock, Price FROM product ORDER BY Stock ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error_message = "Reports Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= ucfirst(str_replace('_', ' ', $page)) ?> - Admin Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin="" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <style>
        /* CSS VARIABLES */
        :root {
            --color-primary: #d97719;
            --color-accent: #b45309;
            --color-bg: #f1efea;
            --color-dark: #2b2a33;
            --color-white: #ffffff;
            --color-muted: #808986;
            --shadow-light: rgba(35, 32, 42, 0.05);
            --shadow-dark: rgba(35, 32, 42, 0.15);
            --status-pending: #f59e0b;
            --status-confirmed: #10b981;
            --status-cancelled: #ef4444;
            --status-shipped: #3b82f6;
            --status-delivered: #8b5cf6;
            --status-disabled: #6b7280;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Hind Guntur', sans-serif;
            background-color: var(--color-bg);
            color: var(--color-dark);
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 260px;
            background-color: var(--color-dark);
            color: var(--color-white);
            display: flex;
            flex-direction: column;
            padding: 20px;
            flex-shrink: 0;
        }

        .sidebar-header {
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header img {
            width: 40px;
        }

        .sidebar-header h2 {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.5rem;
        }

        .sidebar-nav {
            list-style: none;
            margin-top: 30px;
            flex-grow: 1;
        }

        .sidebar-nav li a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 10px;
            border-radius: 8px;
            text-decoration: none;
            color: #e5e7eb;
            font-weight: 600;
            transition: all 0.3s;
        }

        .sidebar-nav li a:hover,
        .sidebar-nav li a.active {
            background-color: var(--color-primary);
            color: var(--color-white);
        }

        .sidebar-footer {
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* MAIN CONTENT */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .main-header h1 {
            font-family: 'Montserrat', sans-serif;
            font-size: 2.2rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: var(--color-dark);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }

        .logout-btn:hover {
            background: var(--color-accent);
        }

        /* COMPONENTS */
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
        }

        .alert-error {
            background-color: #fee2e2;
            color: #991b1b;
            border: 1px solid #f87171;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border: 1px solid #34d399;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--color-white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
            display: flex;
            align-items: center;
            gap: 20px;
            border-left: 5px solid var(--color-primary);
        }

        .stat-icon {
            font-size: 2.5rem;
            color: var(--color-primary);
        }

        .stat-info .title {
            color: var(--color-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            margin-bottom: 5px;
            font-weight: 600;
        }

        .stat-info .value {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: 2rem;
            color: var(--color-dark);
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }

        .content-card {
            background: var(--color-white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
        }

        .content-card h3 {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.4rem;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--color-bg);
        }

        .data-list li {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 5px;
            border-bottom: 1px solid var(--color-bg);
        }

        .product-item {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .product-item img {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }

        .order-table-section {
            background: var(--color-white);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-dark);
        }

        /* Scrollable Table Container */
        .scrollable-table-container {
            max-height: 500px;
            overflow-y: auto;
            position: relative;
        }

        .order-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 10px;
        }

        .order-table th {
            text-align: left;
            padding: 15px 12px;
            color: var(--color-muted);
            font-size: 0.85rem;
            text-transform: uppercase;
            font-weight: 600;
            border-bottom: 2px solid var(--color-bg);
            position: sticky;
            top: 0;
            background: var(--color-white);
            z-index: 10;
        }

        .order-table td {
            padding: 15px 12px;
            background: #f9f9f9;
            vertical-align: middle;
        }

        .order-table tbody tr td:first-child {
            border-radius: 8px 0 0 8px;
        }

        .order-table tbody tr td:last-child {
            border-radius: 0 8px 8px 0;
        }

        /* BUTTONS */
        .btn {
            padding: 8px 14px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.90rem;
            color: white !important;
            display: inline-block;
            text-decoration: none;
            margin-bottom: 10px;
        }

        .btn-view {
            background-color: #3b82f6;
        }

        .btn-confirm {
            background-color: #10b981;
        }

        .btn-reject {
            background-color: #ef4444;
        }

        .btn-ship {
            background-color: #d97719;
        }

        .btn-blue {
            background-color: #3b82f6;
        }

        .btn-add-product {
            background: var(--color-primary);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            margin-bottom: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-submit {
            background-color: var(--color-primary);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 700;
            width: 100%;
        }
        
        /* --- SEARCH BAR STYLES --- */
        .search-bar-container {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 15px;
            gap: 10px;
        }

        .search-input {
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: inherit;
            width: 250px;
        }

        /* --- FILTER BAR STYLES --- */
        .filter-bar {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .filter-bar a {
            padding: 8px 15px;
            border-radius: 20px;
            text-decoration: none;
            font-weight: 600;
            color: var(--color-dark);
            border: 1px solid #ccc;
            background: #fff;
            transition: all 0.2s;
        }

        .filter-bar a.active-filter,
        .filter-bar a:hover {
            background: var(--color-primary);
            color: white;
            border-color: var(--color-primary);
        }

        /* STATUS TAGS */
        .status-tag {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending,
        .status-pending-confirmation {
            background: var(--status-pending);
            color: var(--color-dark);
        }

        .status-confirmed {
            background: var(--status-confirmed);
            color: white;
        }

        .status-cancelled {
            background: var(--status-cancelled);
            color: white;
        }

        .status-shipped {
            background: var(--status-shipped);
            color: white;
        }

        .status-delivered {
            background: var(--status-delivered);
            color: white;
        }

        .status-active {
            background: var(--status-confirmed);
            color: white;
        }

        .status-disabled {
            background: var(--status-disabled);
            color: white;
        }

        /* GRIDS & CARDS */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: 0.3s;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .product-card-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }

        .product-card-body {
            padding: 15px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .product-actions {
            margin-top: auto;
            display: flex;
            gap: 10px;
        }

        .coupon-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 20px;
        }

        .coupon-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            border-top: 5px solid var(--color-primary);
            box-shadow: 0 4px 15px var(--shadow-light);
        }

        .coupon-code {
            background: #f3f4f6;
            padding: 5px 10px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: bold;
            color: var(--color-primary);
            border: 1px dashed var(--color-primary);
        }

        /* FORMS */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: 'Hind Guntur', sans-serif;
        }

        /* MODAL */
        .modal-overlay {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(3px);
            align-items: center;
            justify-content: center;
        }

        .modal-box {
            background: #fff;
            margin: 3% auto;
            border-radius: 16px;
            width: 90%;
            max-width: 650px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            border: 2px solid var(--color-primary);
            display: flex;
            flex-direction: column;
            max-height: 90vh;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 25px;
            background: var(--color-bg);
            border-radius: 14px 14px 0 0;
            border-bottom: 2px solid var(--color-primary);
        }

        .modal-body {
            padding: 25px;
            overflow-y: auto;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
        }

        /* MAP */
        #adminOrderMap {
            margin-top: 20px;
            height: 250px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .car-icon {
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="rgb(217, 119, 25)" d="M400 0H176c-35.3 0-64 28.7-64 64v80c0 35.3 28.7 64 64 64h224c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm-16 112c-8.8 0-16-7.2-16-16s7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16h-32zm-128 0c-8.8 0-16-7.2-16-16s7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16h-32zM0 288c0-35.3 28.7-64 64-64h448c35.3 0 64 28.7 64 64v96c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V288zm224 16c0 8.8-7.2 16-16 16h-32c-8.8 0-16-7.2-16-16v-32c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32zm160 0c0 8.8-7.2 16-16 16h-32c-8.8 0-16-7.2-16-16v-32c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32zM64 480c-17.7 0-32-14.3-32-32s14.3-32 32-32c10.7 0 20.9 5.3 27 13.9l16 22.1c11.6 16.2 30 26 49.6 26h28.8c19.6 0 38-9.8 49.6-26l16-22.1c6.1-8.6 16.3-13.9 27-13.9s16.3-13.9 27-13.9l16 22.1c11.6 16.2 30 26 49.6 26h28.8c19.6 0 38-9.8 49.6-26l16-22.1c6.1-8.6 16.3-13.9 27-13.9s32 14.3 32 32-14.3 32-32 32H64z"/></svg>');
            background-repeat: no-repeat;
            background-position: center;
            background-size: 30px 30px;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            border: 2px solid white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }

        .destination-icon {
            background-color: #ef4444;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.5);
        }

        /* REPORTS & PRINT STYLES */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .charts-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .chart-wrapper {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 15px var(--shadow-light);
            height: 400px;
            position: relative;
        }

        .report-header-print,
        .signature-section {
            display: none;
        }

        /* Print Only Tables (For accurate tabular report) */
        .print-only-table {
            display: none;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        @media print {
            @page {
                margin: 0.5cm;
                size: A4;
            }

            body {
                background: white;
                -webkit-print-color-adjust: exact;
                font-size: 11pt;
            }

            /* Hide UI Elements */
            .sidebar,
            .main-header .user-info,
            .no-print,
            .logout-btn,
            .btn,
            .search-bar-container,
            .filter-bar {
                display: none !important;
            }

            .main-content {
                margin: 0;
                padding: 0;
                width: 100%;
                height: auto;
                overflow: visible;
            }

            .main-header h1 {
                display: none;
            }

            /* Formal Header */
            .report-header-print {
                display: block !important;
                text-align: center;
                margin-bottom: 20px;
                border-bottom: 2px solid #000;
                padding-bottom: 10px;
            }

            .report-logo {
                width: 50px;
                margin-bottom: 5px;
            }

            .report-title {
                font-size: 18pt;
                font-weight: bold;
                font-family: 'Montserrat', sans-serif;
                text-transform: uppercase;
                margin: 5px 0;
            }

            .report-meta {
                font-size: 9pt;
                color: #555;
            }

            /* Hide Charts in Print to ensure Tabular Form */
            .charts-container {
                display: none;
            }

            /* Show Print Tables */
            .print-only-table {
                display: table;
                border: 1px solid #000;
                margin-bottom: 20px;
            }

            .print-only-table th, .print-only-table td {
                border: 1px solid #000;
                padding: 8px;
                text-align: left;
            }

            .print-only-table th {
                background-color: #eee !important;
                font-weight: bold;
                text-transform: uppercase;
            }

            /* Card styling reset for print */
            .content-card {
                box-shadow: none;
                border: none;
                padding: 0;
                margin-top: 10px;
            }

            .order-table {
                border-collapse: collapse;
                width: 100%;
                border-spacing: 0;
            }

            .order-table th {
                background-color: #eee !important;
                color: #000 !important;
                border: 1px solid #000;
                font-weight: bold;
                padding: 8px;
                font-size: 10pt;
                position: static; /* remove sticky */
            }

            .order-table td {
                border: 1px solid #000;
                color: #000;
                padding: 8px;
                font-size: 10pt;
                background: #fff !important;
            }

            /* Signature Section */
            .signature-section {
                display: flex !important;
                justify-content: space-between;
                margin-top: 40px;
                page-break-inside: avoid;
            }

            .signature-box {
                border-top: 1px solid #000;
                width: 40%;
                padding-top: 5px;
                text-align: center;
                font-weight: bold;
                font-size: 10pt;
            }
        }

        @media (max-width: 992px) {
            body {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }

            .sidebar-nav {
                display: flex;
                gap: 10px;
                margin-top: 0;
            }

            .sidebar-nav li a span {
                display: none;
            }

            .sidebar-footer {
                display: none;
            }

            .charts-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <aside class="sidebar">
        <div>
            <div class="sidebar-header"><img src="images/logonew.png" alt="Logo">
                <h2>ScholarSpot</h2>
            </div>
            <ul class="sidebar-nav">
                <li><a href="admindashboard.php?page=dashboard" class="<?= $page === 'dashboard' ? 'active' : '' ?>"><span>Dashboard</span></a></li>
                <li><a href="admindashboard.php?page=orders" class="<?= $page === 'orders' ? 'active' : '' ?>"> <span>Orders</span></a></li>
                <li><a href="admindashboard.php?page=products" class="<?= $page === 'products' ? 'active' : '' ?>"><span>Products</span></a></li>
                <li><a href="admindashboard.php?page=coupons" class="<?= $page === 'coupons' ? 'active' : '' ?>"><span>Coupons</span></a></li>
                <li><a href="admindashboard.php?page=shipment" class="<?= $page === 'shipment' ? 'active' : '' ?>"><span>Shipment</span></a></li>
                <li><a href="admindashboard.php?page=users" class="<?= $page === 'users' ? 'active' : '' ?>"><span>Manage Users</span></a></li>
                <li><a href="admindashboard.php?page=activity_logs" class="<?= $page === 'activity_logs' ? 'active' : '' ?>"><span>Activity Logs</span></a></li>
                <li><a href="admindashboard.php?page=reports" class="<?= $page === 'reports' ? 'active' : '' ?>"><span>Reports</span></a></li>
            </ul>
        </div>
        <div class="sidebar-footer"><a href="dashboard.php" class="logout-btn" style="background: var(--color-primary); display: block; text-align: center;"> View Shop</a></div>
    </aside>

    <main class="main-content">
        <header class="main-header">
            <h1 data-date="<?= date('F j, Y') ?>"><?= ucfirst(str_replace('_', ' ', $page)) ?> Management</h1>
            <div class="user-info"><span>Welcome, <strong><?= htmlspecialchars($userName) ?></strong></span><a href="logout.php" class="logout-btn">Logout</a></div>
        </header>

        <div class="report-header-print">
            <img src="images/logonew.png" class="report-logo" alt="Logo">
            <div class="report-title">ScholarSpot Monthly Report</div>
            <div class="report-meta">Generated on: <?= date('F j, Y, g:i A') ?> <br> Generated by: <?= htmlspecialchars($userName) ?> (Administrator)</div>
        </div>

        <?php if (!empty($error_message)): ?><div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>
        <?php if (!empty($success_message)): ?><div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>

        <?php if ($page === 'dashboard'): ?>
            <section class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-info">
                        <div class="title">Total Revenue</div>
                        <div class="value">₱<?= number_format($stats['total_revenue'], 2) ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-info">
                        <div class="title">Total Orders</div>
                        <div class="value"><?= $stats['total_orders'] ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">🛍️</div>
                    <div class="stat-info">
                        <div class="title">Total Products</div>
                        <div class="value"><?= $stats['total_products'] ?></div>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-info">
                        <div class="title">Total Users</div>
                        <div class="value"><?= $stats['total_users'] ?></div>
                    </div>
                </div>
            </section>
            <section class="dashboard-grid">
                <div class="content-card">
                    <h3>Recent Orders</h3>
                    <ul class="data-list"><?php if (!empty($recent_orders)): foreach ($recent_orders as $order):
                                                     $status_class = strtolower(str_replace(' ', '-', $order['Order_Status'] ?? 'unknown')); ?>
                                <li>
                                    <div class="order-item">
                                        <div class="id">#<?= htmlspecialchars($order['Payment_ID']) ?></div>
                                        <div class="customer"><?= htmlspecialchars($order['Customer_Name']) ?></div>
                                    </div>
                                    <div class="item-value"><span class="status-tag status-<?= $status_class ?>"><?= htmlspecialchars($order['Order_Status']) ?></span> ₱<?= number_format($order['Total_Amount'], 2) ?></div>
                                </li><?php endforeach;
                                                else: ?><li>No recent orders found.</li><?php endif; ?>
                    </ul>
                </div>
                <div class="content-card">
                    <h3>Recently Added Products</h3>
                    <ul class="data-list"><?php if (!empty($recent_products)): foreach ($recent_products as $product): ?>
                                <li>
                                    <div class="product-item"><img src="<?= htmlspecialchars($product['Images']) ?>" onerror="this.src='https://via.placeholder.com/50x50'">
                                        <div>
                                            <div class="name"><?= htmlspecialchars($product['Name']) ?></div>
                                            <div class="stock">Stock: <?= htmlspecialchars($product['Stock']) ?></div>
                                        </div>
                                    </div>
                                    <div class="item-value">₱<?= number_format($product['Price'], 2) ?></div>
                                </li><?php endforeach;
                                                else: ?><li>No recent products found.</li><?php endif; ?>
                    </ul>
                </div>
                <div class="content-card">
                    <h3>⚠️ Low Stock Alerts</h3>
                    <ul class="data-list">
                        <?php if (!empty($low_stock_products)): foreach ($low_stock_products as $prod): ?>
                                <li>
                                    <div class="product-item">
                                        <img src="<?= htmlspecialchars($prod['Images']) ?>" onerror="this.src='https://via.placeholder.com/50x50'">
                                        <div>
                                            <div class="name"><?= htmlspecialchars($prod['Name']) ?></div>
                                            <div class="stock" style="color: #ef4444; font-weight:bold;">Only <?= $prod['Stock'] ?> left</div>
                                        </div>
                                    </div>
                                    <div class="item-value"><a href="?page=products" class="btn btn-view" style="text-decoration:none; font-size:0.8rem;">Restock</a></div>
                                </li>
                            <?php endforeach;
                        else: ?><li><span style="color:#10b981;">All stock levels are healthy.</span></li><?php endif; ?>
                    </ul>
                </div>
            </section>

        <?php elseif ($page === 'orders'): ?>
            <!-- Added Search Bar -->
            <div class="search-bar-container">
                <form method="GET" style="display:flex; gap:5px;">
                    <input type="hidden" name="page" value="orders">
                    <input type="text" name="search" class="search-input" placeholder="Search Order ID or Customer Name..." value="<?= htmlspecialchars($search_term) ?>">
                    <button type="submit" class="btn btn-blue">Search</button>
                    <a href="?page=orders" class="btn" style="background:#6b7280;">Clear</a>
                </form>
            </div>

            <div class="filter-bar">
                <?php
                $status_filters = [
                    'all' => 'All Orders',
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'shipped' => 'Shipped',
                    'delivered' => 'Delivered',
                    'cancelled' => 'Cancelled',
                ];

                foreach ($status_filters as $status_key => $status_label) {
                    $isActive = ($filter_status === $status_key) ? 'active-filter' : '';
                    $filter_url = "admindashboard.php?page=orders&status=" . urlencode($status_key);
                    echo "<a href=\"{$filter_url}\" class=\"{$isActive}\">{$status_label}</a>";
                }
                ?>
            </div>
            
            <!-- Added Scrollable Container -->
            <div class="order-table-section">
                <div class="scrollable-table-container">
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody><?php if (!empty($orders)): foreach ($orders as $order):
                                            $status_class_map = [
                                                'Pending' => 'status-pending',
                                                'Pending Confirmation' => 'status-pending-confirmation',
                                                'Confirmed' => 'status-confirmed',
                                                'Shipped' => 'status-shipped',
                                                'Delivered' => 'status-delivered',
                                                'Cancelled' => 'status-cancelled'
                                            ];
                                            $status_key = $order['Order_Status'] ?? 'unknown';
                                            $status_class = $status_class_map[$status_key] ?? 'status-disabled';
                                            
                                            $order_with_tracking = $order;
                                ?>
                                <tr>
                                    <td><strong>#<?= htmlspecialchars($order['Order_ID']) ?></strong></td>
                                    <td><?= htmlspecialchars($order['Customer_Name'] ?? $order['UserName']) ?></td>
                                    <td><?= !empty($order['Order_Date']) ? (new DateTime($order['Order_Date']))->format('M d, Y') : 'N/A' ?></td>
                                    <td><strong>₱<?= number_format($order['Total_Amount'], 2) ?></strong></td>
                                    <td><span class="status-tag <?= $status_class ?>"><?= htmlspecialchars($order['Order_Status']) ?></span></td>
                                    <td>
                                        <div class="action-btn-group">
                                            <button class="btn btn-view" onclick='openDetailsModal(<?= htmlspecialchars(json_encode($order_with_tracking), ENT_QUOTES, 'UTF-8') ?>)'>View</button>
                                            <?php if ($order['Order_Status'] == 'Pending' || $order['Order_Status'] == 'Pending Confirmation'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Confirm?');"><input type="hidden" name="order_id" value="<?= $order['Order_ID'] ?>"><button type="submit" name="confirm_order" class="btn btn-confirm">✓</button></form>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Reject?');"><input type="hidden" name="order_id" value="<?= $order['Order_ID'] ?>"><button type="submit" name="reject_order" class="btn btn-reject">✗</button></form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr><?php endforeach;
                                else: ?><tr>
                                <td colspan="6" style="text-align:center;">No orders found <?= $filter_status !== 'all' ? " with status: " . htmlspecialchars(ucwords($filter_status)) : "" ?>.</td>
                            </tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page === 'shipment'): ?>
            <!-- Added Search Bar -->
            <div class="search-bar-container">
                <form method="GET" style="display:flex; gap:5px;">
                    <input type="hidden" name="page" value="shipment">
                    <input type="text" name="search" class="search-input" placeholder="Search Order ID or Customer Name..." value="<?= htmlspecialchars($search_term) ?>">
                    <button type="submit" class="btn btn-blue">Search</button>
                    <a href="?page=shipment" class="btn" style="background:#6b7280;">Clear</a>
                </form>
            </div>

            <div class="content-card">
                <h3>Ready for Shipment</h3>
                <div class="order-table-section">
                    <div class="scrollable-table-container">
                        <table class="order-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody><?php if (!empty($shipment_orders)): foreach ($shipment_orders as $order): ?>
                                            <tr>
                                                <td><strong>#<?= htmlspecialchars($order['Order_ID']) ?></strong></td>
                                                <td><?= htmlspecialchars($order['Customer_Name']) ?></td>
                                                <td><?= (new DateTime($order['Order_Date']))->format('M d, Y') ?></td>
                                                <td><span class="status-tag status-<?= strtolower(str_replace(' ', '-', $order['Order_Status'])) ?>"><?= htmlspecialchars($order['Order_Status']) ?></span></td>
                                                <td>
                                                    <div class="action-btn-group">
                                                        <button class="btn btn-view" onclick='openDetailsModal(<?= htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8') ?>)'>View</button>

                                                        <?php if ($order['Order_Status'] == 'Shipped'): ?>
                                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Revert shipment to Confirmed? This will clear the route data.');">
                                                                <input type="hidden" name="order_id" value="<?= $order['Order_ID'] ?>">
                                                                <button type="submit" name="cancel_shipment" class="btn btn-reject">Revert ↺</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr><?php endforeach;
                                        else: ?><tr>
                                        <td colspan="6" style="text-align:center;">No shipments pending.</td>
                                    </tr><?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        <?php elseif ($page === 'products'): ?>
            <?php if ($product_success): ?><div class="alert alert-success">✅ <?= htmlspecialchars($product_success) ?></div><?php endif; ?>
            <button class="btn-add-product" onclick="openProductModal()">➕ Add New Product</button>
            <div class="products-grid">
                <?php if (!empty($products)): foreach ($products as $prod): ?>
                            <div class="product-card">
                                <img src="<?= htmlspecialchars($prod['Images']) ?>" class="product-card-img" onerror="this.src='https://via.placeholder.com/300x200'">
                                <div class="product-card-body">
                                    <h4><?= htmlspecialchars($prod['Name']) ?></h4>
                                    <div class="product-price-tag">₱<?= number_format($prod['Price'], 2) ?></div>
                                    <div style="font-size:0.8rem; margin-bottom:10px;">Stock: <?= $prod['Stock'] ?></div>
                                    <div class="product-actions">
                                        <button class="btn-qr" onclick="openQRModal(<?= $prod['Product_ID'] ?>, '<?= htmlspecialchars(addslashes($prod['Name'])) ?>')">📱</button>
                                        <button class="btn-edit" onclick='openEditProductModal(<?= json_encode($prod) ?>)' style="flex:1; background:#3b82f6; color:white; border:none; border-radius:6px;">Edit</button>
                                        <form method="POST" onsubmit="return confirm('Delete?');" style="flex:1;"><input type="hidden" name="product_id" value="<?= $prod['Product_ID'] ?>"><button type="submit" name="delete_product" class="btn-delete" style="width:100%; background:#ef4444; color:white; border:none; padding:8px; border-radius:6px;">Delete</button></form>
                                    </div>
                                </div>
                            </div>
                <?php endforeach;
                endif; ?>
            </div>

        <?php elseif ($page === 'coupons'): ?>
            <div class="content-card">
                <h3>Create Coupon</h3>
                <form method="post" action="admindashboard.php?page=coupons"><input type="hidden" name="create_coupon" value="1">
                    <div class="form-grid">
                        <div class="form-group"><label>Type</label><input type="text" name="type" required></div>
                        <div class="form-group"><label>Code</label><input type="text" name="code" required></div>
                    </div>
                    <div class="form-group"><label>Description</label><textarea name="description" rows="2" required></textarea></div>
                    <div class="form-grid">
                        <div class="form-group"><label>Amount</label><input type="number" name="amount" step="0.01" required></div>
                        <div class="form-group"><label>Expiry</label><input type="date" name="date_exp" required></div>
                    </div><button type="submit" class="btn-submit">Create Coupon</button>
                </form>
            </div>
            <div class="coupon-grid">
                <?php if (!empty($coupons)): foreach ($coupons as $row): $statusClass = $row['status'] == 2 ? "status-cancelled" : "status-confirmed"; ?>
                            <div class="coupon-card">
                                <div style="display:flex; justify-content:space-between;">
                                    <h3><?= htmlspecialchars($row['type']); ?></h3><span class="status-tag <?= $statusClass ?>"><?= $row['status'] == 2 ? 'Expired' : 'Active' ?></span>
                                </div>
                                <div class="coupon-code"><?= htmlspecialchars($row['code']); ?></div>
                                <p><?= htmlspecialchars($row['description']); ?></p>
                                <form method="get" onsubmit="return confirm('Delete?');" style="margin-top:10px;"><input type="hidden" name="page" value="coupons"><input type="hidden" name="delete_id" value="<?= $row['promo_id']; ?>"><button class="btn-delete" style="background:#ef4444; color:white; border:none; padding:5px 10px; border-radius:4px;">Delete</button></form>
                            </div>
                <?php endforeach;
                endif; ?>
            </div>

        <?php elseif ($page === 'users'): ?>
            <div class="content-card">
                <h3>Registered Users</h3>
                <div class="order-table-section">
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody><?php if (!empty($users)): foreach ($users as $u):
                                            $roleClass = strtolower($u['Role']) === 'admin' ? 'status-delivered' : 'status-confirmed';
                                            $statusClass = ($u['Status'] ?? 'Active') === 'Active' ? 'status-active' : 'status-disabled'; ?>
                                        <tr>
                                            <td>#<?= $u['User_ID'] ?></td>
                                            <td style="font-weight:bold;"><?= htmlspecialchars($u['Name']) ?></td>
                                            <td><?= htmlspecialchars($u['Email']) ?></td>
                                            <td><span class="status-tag <?= $roleClass ?>"><?= htmlspecialchars($u['Role']) ?></span></td>
                                            <td><span class="status-tag <?= $statusClass ?>"><?= htmlspecialchars($u['Status'] ?? 'Active') ?></span></td>
                                            <td>
                                                <?php if ($u['User_ID'] != $_SESSION['User_ID']): ?>
                                                    <button class="btn btn-blue" onclick='openManageUser(<?= json_encode($u) ?>)'>Manage</button>
                                                    <form method="POST" style="display:inline-block; margin-left:5px;" onsubmit="return confirm('Delete this user?');"><input type="hidden" name="delete_user_id" value="<?= $u['User_ID'] ?>"><button type="submit" class="btn btn-reject">Delete</button></form>
                                                <?php else: ?><span style="color:#888;">(You)</span><?php endif; ?>
                                            </td>
                                        </tr><?php endforeach;
                                    else: ?><tr>
                                    <td colspan="6" style="text-align:center;">No users found.</td>
                                </tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page === 'activity_logs'): ?>
            <div class="content-card">
                <h3>System Activity Logs</h3>
                <div class="order-table-section">
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>User</th>
                                <th>Role</th>
                                <th>Action</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody><?php if (!empty($logs)): foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?= date('M d, Y h:i A', strtotime($log['Timestamp'])) ?></td>
                                            <td><?= htmlspecialchars($log['Name']) ?></td>
                                            <td><?= htmlspecialchars($log['Role']) ?></td>
                                            <td style="font-weight:bold; color:var(--color-primary);"><?= htmlspecialchars($log['Action']) ?></td>
                                            <td><?= htmlspecialchars($log['Details']) ?></td>
                                        </tr>
                                    <?php endforeach;
                                else: ?><tr>
                                    <td colspan="5" style="text-align:center;">No activity recorded yet.</td>
                                </tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($page === 'reports'): ?>
            <div class="report-header">
                <h3>Analytics & Reports</h3>
                <button onclick="window.print()" class="btn btn-ship no-print">🖨️ Print Report</button>
            </div>

            <!-- Printable Sales Summary Table (Tabular Form) -->
            <table class="print-only-table">
                <thead>
                    <tr>
                        <th colspan="2">Sales Performance (Last 7 Days)</th>
                    </tr>
                    <tr>
                        <th>Date</th>
                        <th>Total Revenue (₱)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_sales = 0;
                    foreach ($report_sales as $sale): 
                        $total_sales += $sale['total'];
                    ?>
                        <tr>
                            <td><?= htmlspecialchars($sale['date']) ?></td>
                            <td><?= number_format($sale['total'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td style="font-weight:bold;">Total (7 Days)</td>
                        <td style="font-weight:bold;"><?= number_format($total_sales, 2) ?></td>
                    </tr>
                </tbody>
            </table>

            <!-- Printable Status Summary Table (Tabular Form) -->
            <table class="print-only-table">
                <thead>
                    <tr>
                        <th colspan="2">Order Status Distribution</th>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report_status as $status): ?>
                        <tr>
                            <td><?= htmlspecialchars($status['Order_Status']) ?></td>
                            <td><?= htmlspecialchars($status['count']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- Visual Charts (Screen Only) -->
            <div class="charts-container no-print">
                <div class="chart-wrapper">
                    <canvas id="salesChart"></canvas>
                </div>
                <div class="chart-wrapper">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>

            <!-- Inventory Table (Screen & Print) -->
            <div class="content-card">
                <h3>Inventory Status</h3>
                <div class="order-table-section">
                    <table class="order-table">
                        <thead>
                            <tr>
                                <th>Item</th>
                                <th>Price</th>
                                <th>Current Stock</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report_inventory as $item):
                                $stock = intval($item['Stock']);
                                $status_color = $stock > 20 ? '#10b981' : ($stock > 5 ? '#f59e0b' : '#ef4444');
                                $status_text = $stock > 20 ? 'Healthy' : ($stock > 5 ? 'Low' : 'Critical');
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['Name']) ?></td>
                                    <td>₱<?= number_format($item['Price'], 2) ?></td>
                                    <td><?= $stock ?></td>
                                    <td style="color:<?= $status_color ?>; font-weight:bold;"><?= $status_text ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="signature-section">
                <div class="signature-box">Prepared By: <br><br><br>__________________________<br><?= htmlspecialchars($userName) ?></div>
                <div class="signature-box">Approved By: <br><br><br>__________________________<br>Administrator</div>
            </div>
        <?php endif; ?>
    </main>

    <div id="orderDetailsModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalTitle">Order Details</h2><button class="modal-close" onclick="closeOrderModal()">×</button>
            </div>
            <div class="modal-body">
                <p><strong>Order ID:</strong> <span id="modalOrderId"></span></p>
                <p><strong>Date Placed:</strong> <span id="modalOrderDate"></span></p>
                <p><strong>Customer:</strong> <span id="modalCustomerName"></span></p>
                <p><strong>Total:</strong> <span id="modalTotalAmount"></span></p>
                <p><strong>Status:</strong> <span id="modalStatus"></span></p>
                <p><strong>Shipping Address:</strong> <span id="modalShippingAddress"></span></p>
                <hr style="margin:10px 0;">



                <?php if ($page === 'shipment'): ?>
                    <div id="mapAddressInput" style="margin-bottom: 20px;">
                        <h3>Shipment Route Planning</h3>
                        <div class="form-group"><label for="from_address">FROM (Starting Point)</label><input type="text" id="from_address" name="from_address" placeholder="e.g., Warehouse, Alijis" required></div>
                        <div class="form-group"><label for="to_address">TO (Destination)</label><input type="text" id="to_address" name="to_address" placeholder="e.g., Villamonte, Bacolod 6100" required></div>
                        <button class="btn btn-blue" onclick="previewRoute()" type="button" style="width: 100%; margin-bottom: 10px;">Preview Route</button>
                    </div>
                <?php endif; ?>



                <div id="adminOrderMap"></div>

                <?php if ($page === 'shipment'): ?>
                    <form id="createShipmentForm" method="POST" style="margin-top:20px;">
                        <input type="hidden" name="order_id" id="shipment_order_id">
                        <input type="hidden" name="from_address" id="shipment_from_address">
                        <input type="hidden" name="to_address" id="shipment_to_address">
                        <button type="submit" name="create_shipment_map" id="create_shipment_btn" class="btn-submit" style="width:100%; display:none;" onclick="return confirm('Start the shipment and set status to Shipped?');">Create Shipment</button>
                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <div id="productModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="productModalTitle">Add Product</h2><button class="modal-close" onclick="closeProductModal()">×</button>
            </div>
            <div class="modal-body">
                <form id="productForm" method="POST" enctype="multipart/form-data"><input type="hidden" name="product_id" id="prod_id"><input type="hidden" name="current_image_path" id="prod_current_image">
                    <div class="form-group"><label>Name</label><input type="text" name="name" id="prod_name" required></div>
                    <div class="form-group"><label>Description</label><textarea name="description" id="prod_desc" required></textarea></div>
                    <div class="form-grid">
                        <div class="form-group"><label>Category</label><select name="category_id" id="prod_cat" required>
                                <option value="">Select</option><?php foreach ($categories as $cat): ?><option value="<?= $cat['Category_ID'] ?>"><?= htmlspecialchars($cat['Category_Name']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="form-group"><label>Price</label><input type="number" name="price" id="prod_price" required></div>
                    </div>
                    <div class="form-group"><label>Stock</label><input type="number" name="stock" id="prod_stock" required></div>
                    <div class="form-group"><label>Availability</label><select name="availability" id="prod_avail">
                            <option>In Stock</option>
                            <option>Out of Stock</option>
                        </select></div>
                    <div class="form-group"><label>Image</label><input type="file" name="image_file" id="prod_image_input"></div>
                    <button type="submit" id="prod_submit_btn" class="btn-submit">Save</button>
                </form>
            </div>
        </div>
    </div>

    <div id="qrModal" class="modal-overlay">
        <div class="modal-box" style="max-width:400px; text-align:center;">
            <div class="modal-header">
                <h2>QR Code</h2><button class="modal-close" onclick="document.getElementById('qrModal').style.display='none'">×</button>
            </div>
            <div class="modal-body">
                <h3 id="qrProductName"></h3><img id="qrImage" style="width:200px; margin:15px 0;">
            </div>
        </div>
    </div>

    <div id="manageUserModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="manageModalTitle">Manage User</h2><button onclick="document.getElementById('manageUserModal').style.display='none'" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST"><input type="hidden" name="manage_user_id" id="manage_uid">
                    <div class="form-group"><label>Account Status</label><select name="status" id="manage_status" style="width:100%; padding:10px; border:2px solid #eee; border-radius:6px;">
                            <option value="Active">Active (Can Login)</option>
                            <option value="Disabled">Disabled (Block Login)</option>
                        </select></div>
                    <div class="form-group"><label>Reset Password</label><input type="password" name="new_password" placeholder="Leave blank to keep current password" style="width:100%; padding:10px; border:2px solid #eee; border-radius:6px;"><small style="color:#666;">Enter a value only if you want to change it.</small></div>
                    <button type="submit" class="btn-submit">Save Changes</button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // --- MAP LOGIC ---
        let map, carMarker, interval, routingControl;

        // API Key provided by the user
        const LOCATIONIQ_API_KEY = 'pk.2b04464964abd703bca47d716d8f7990';

        // Base URL for LocationIQ Geocoding API
        const GEOCODE_URL_BASE = `https://us1.locationiq.com/v1/search.php?key=${LOCATIONIQ_API_KEY}&format=json&limit=1&q=`;

        // Default coordinates for Bacolod (fallback)
        const DEFAULT_CENTER_COORD = [10.65, 122.95];

        // Custom Icon for the Car
        const CarIcon = L.DivIcon.extend({
            options: {
                className: 'car-icon',
                html: ''
            }
        });

        // Custom Icon for the Destination
        const DestIcon = L.DivIcon.extend({
            options: {
                className: 'destination-icon',
                html: ''
            }
        });

        /**
         * Geocoding function using LocationIQ API.
         */
        async function geocodeAddress(address) {
            if (!address || !LOCATIONIQ_API_KEY) {
                return null;
            }

            const encodedAddress = encodeURIComponent(address);
            const url = `${GEOCODE_URL_BASE}${encodedAddress}`;

            try {
                const response = await fetch(url);
                const data = await response.json();

                if (response.ok && data && data.length > 0) {
                    return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                } else {
                    console.error("Geocoding failed for address:", address, data.error || 'Unknown API error');
                    return null;
                }

            } catch (error) {
                console.error(`Geocoding request failed:`, error);
                return null;
            }
        }

        // Simulates the car movement along a pre-calculated route path
        function animateCar(routePath, startMs, endMs) {
            if (interval) clearInterval(interval);
            if (!carMarker) return;

            const update = () => {
                const now = Date.now();
                const total = endMs - startMs;
                const elapsed = now - startMs;
                let prog = Math.min(1, Math.max(0, elapsed / total));

                if (prog >= 1) {
                    clearInterval(interval);
                    carMarker.setLatLng(routePath[routePath.length - 1]);
                } else {
                    const totalDistance = routePath.length - 1;
                    const traveledDistance = prog * totalDistance;
                    const segmentIndex = Math.floor(traveledDistance);
                    const localProgress = traveledDistance - segmentIndex;

                    if (segmentIndex < routePath.length - 1) {
                        const startPoint = routePath[segmentIndex];
                        const endPoint = routePath[segmentIndex + 1];

                        const lat = startPoint.lat + (endPoint.lat - startPoint.lat) * localProgress;
                        const lng = startPoint.lng + (endPoint.lng - startPoint.lng) * localProgress;
                        carMarker.setLatLng([lat, lng]);
                    }
                }
            };

            interval = setInterval(update, 500);
            update(); // Initial call
        }





        /**
         * Initializes the map, placing the destination marker accurately based on the order's address.
         */
        async function initMap(order) {
            if (interval) clearInterval(interval);
            if (map) {
                map.off();
                map.remove();
            }
            if (routingControl) {
                try {
                    map.removeControl(routingControl);
                } catch (e) {}
            }

            const container = document.getElementById('adminOrderMap');
            container.innerHTML = ""; // Clear container

            let startCoords = DEFAULT_CENTER_COORD;
            let endCoords = DEFAULT_CENTER_COORD;

            // 1. Determine the destination address for geocoding
            let destAddress = order.Shipping_Address || 'Bacolod, Philippines';
            let startAddress = 'Warehouse, Bacolod';



            if (order.Order_Status === 'Shipped' || order.Order_Status === 'Delivered') {
                destAddress = order.To_Address || destAddress;
                startAddress = order.From_Address || startAddress;
            }

            // 2. Resolve coordinates for destination (Red Dot)
            const endGeoResult = await geocodeAddress(destAddress);
            if (endGeoResult) endCoords = endGeoResult;

            // 3. Resolve coordinates for start point
            const startGeoResult = await geocodeAddress(startAddress);
            if (startGeoResult) startCoords = startGeoResult;

            // Initialize map, centered on destination
            map = L.map(container).setView(endCoords, 14); // Set zoom higher for better location focus
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

            const start = L.latLng(startCoords[0], startCoords[1]);
            const end = L.latLng(endCoords[0], endCoords[1]);

            if (order.Order_Status === 'Shipped' && order.Estimated_Delivery_Date && order.Driver_Start_Timestamp) {

                // Plot the route and start animation
                routingControl = L.Routing.control({
                    waypoints: [start, end],
                    routeWhileDragging: false,
                    show: false,
                    addWaypoints: false,
                    draggableWaypoints: false,
                    lineOptions: {
                        styles: [{
                            color: '#d97719',
                            opacity: 1,
                            weight: 5
                        }]
                    },
                    createMarker: function(i, waypoint, n) {
                        if (i === n - 1) { // Destination marker (Red Dot)
                            return L.marker(waypoint.latLng, {
                                icon: new DestIcon()
                            }).bindPopup("Destination: " + destAddress);
                        }
                        return null; // Suppress default start marker
                    }
                }).addTo(map);

                // Initialize car marker at the starting point
                carMarker = L.marker(start, {
                    icon: new CarIcon()
                }).addTo(map);

                routingControl.on('routesfound', function(e) {
                    const route = e.routes[0];
                    const routePath = route.coordinates;
                    const endMs = new Date(order.Estimated_Delivery_Date).getTime();
                    const startMs = new Date(order.Driver_Start_Timestamp).getTime();
                    animateCar(routePath, startMs, endMs);
                });

                map.fitBounds(L.latLngBounds(start, end).pad(0.5));

            } else {
                // For Confirmed/Pending/Delivered: show only the destination marker (red dot)
                L.marker(endCoords, {
                    icon: new DestIcon()
                }).addTo(map).bindPopup("Delivery Location: " + destAddress).openPopup();
                map.setView(endCoords, 14);
            }

            map.invalidateSize();
        }

        // Function to preview the route without starting the shipment
        async function previewRoute() {
            if (interval) clearInterval(interval);
            if (carMarker) carMarker.remove();
            if (routingControl) map.removeControl(routingControl);

            const fromAddr = document.getElementById('from_address').value.trim();
            const toAddr = document.getElementById('to_address').value.trim();

            const createBtn = document.getElementById('create_shipment_btn');

            if (!fromAddr || !toAddr) {
                alert('Please enter both FROM and TO addresses for the preview.');
                createBtn.style.display = 'none';
                return;
            }

            // Geocode the addresses using LocationIQ
            const fromCoords = await geocodeAddress(fromAddr);
            const toCoords = await geocodeAddress(toAddr);

            if (!fromCoords || !toCoords) {
                alert('Geocoding Failed: Could not resolve one or both addresses. Please try again with a more specific location (e.g., include the city/province/postal code).');
                createBtn.style.display = 'none';
                return;
            }

            const start = L.latLng(fromCoords[0], fromCoords[1]);
            const end = L.latLng(toCoords[0], toCoords[1]);

            // Update hidden fields for form submission
            document.getElementById('shipment_from_address').value = fromAddr;
            document.getElementById('shipment_to_address').value = toAddr;

            // Plot the route
            routingControl = L.Routing.control({
                waypoints: [start, end],
                routeWhileDragging: false,
                show: true,
                addWaypoints: false,
                draggableWaypoints: false,
                lineOptions: {
                    styles: [{
                        color: '#3b82f6',
                        opacity: 0.8,
                        weight: 5,
                        dashArray: '5, 10'
                    }]
                }, // dashed line for preview
                createMarker: function(i, waypoint, n) {
                    if (i === 0) { // Start marker (Car)
                        return L.marker(waypoint.latLng, {
                            icon: new CarIcon()
                        }).bindPopup("Start: " + fromAddr).openPopup();
                    } else if (i === n - 1) { // Destination marker (Red Dot)
                        return L.marker(waypoint.latLng, {
                            icon: new DestIcon()
                        }).bindPopup("Destination: " + toAddr);
                    }
                    return null;
                }
            }).addTo(map);

            // Fit map bounds to the route
            map.fitBounds(L.latLngBounds(start, end).pad(0.5));

            // Show the final Create Shipment button (as route is ready)
            createBtn.style.display = 'block';
        }





        // --- MODAL LOGIC (Updated) ---
        async function openDetailsModal(o) {
            const dateStr = new Date(o.Order_Date).toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });

            document.getElementById('modalTitle').textContent = `Order #${o.Order_ID}`;
            document.getElementById('modalOrderId').textContent = o.Order_ID || 'N/A';
            document.getElementById('modalOrderDate').textContent = dateStr || 'N/A';
            document.getElementById('modalCustomerName').textContent = o.Customer_Name || o.UserName;
            document.getElementById('modalTotalAmount').textContent = '₱' + o.Total_Amount;
            document.getElementById('modalStatus').textContent = o.Order_Status;
            document.getElementById('modalShippingAddress').textContent = o.Shipping_Address || 'N/A';

            // --- Shipment Page Specific Logic ---
            const isShipmentPage = '<?= $page ?>' === 'shipment';
            const isConfirmed = o.Order_Status === 'Confirmed';
            const isShipped = o.Order_Status === 'Shipped';
            const isDelivered = o.Order_Status === 'Delivered'; // Check for delivered status

            const mapInputDiv = document.getElementById('mapAddressInput');
            const createBtn = document.getElementById('create_shipment_btn');

            if (isShipmentPage) {
                // Set shipment order ID
                document.getElementById('shipment_order_id').value = o.Order_ID;

                // Populate address inputs based on status/stored data
                const fromInput = document.getElementById('from_address');
                const toInput = document.getElementById('to_address');

                // If Confirmed, prioritize the database Shipping_Address for 'TO' input
                if (isConfirmed) {
                    fromInput.value = 'Warehouse, Bacolod';
                    toInput.value = o.Shipping_Address || 'Customer Address, Bacolod';
                } else {
                    // If Shipped/Delivered, use the saved From/To from the shipment table
                    fromInput.value = o.From_Address || 'Warehouse, Bacolod';
                    toInput.value = o.To_Address || o.Shipping_Address || 'Customer Address, Bacolod';
                }

                // Reset hidden address inputs for the form submission
                document.getElementById('shipment_from_address').value = fromInput.value;
                document.getElementById('shipment_to_address').value = toInput.value;

                if (isConfirmed && !isDelivered) {
                    // Only confirmed orders (that are NOT yet delivered) can be set for shipment
                    mapInputDiv.style.display = 'block';
                    createBtn.style.display = 'none';
                } else if (isShipped || isDelivered) {
                    // Shipped or Delivered: Hide input section and button. Show map/animation only.
                    mapInputDiv.style.display = 'none';
                    createBtn.style.display = 'none';
                }
            }





            document.getElementById('orderDetailsModal').style.display = 'flex';
            setTimeout(() => {
                // Initialize map with current order object data
                initMap(o);
            }, 200);
        }

        function closeOrderModal() {
            if (interval) clearInterval(interval);
            if (routingControl) {
                // Safely remove routing control if it was initialized
                try {
                    map.removeControl(routingControl);
                } catch (e) {}
            }
            document.getElementById('orderDetailsModal').style.display = 'none';
        }

        function openProductModal() {
            document.getElementById('productForm').reset();
            document.getElementById('prod_submit_btn').name = 'add_product';
            document.getElementById('productModal').style.display = 'flex';
        }

        function openEditProductModal(p) {
            document.getElementById('productModalTitle').textContent = 'Edit Product';
            document.getElementById('prod_id').value = p.Product_ID;
            document.getElementById('prod_name').value = p.Name;
            document.getElementById('prod_desc').value = p.Description;
            document.getElementById('prod_cat').value = p.Category_ID;
            document.getElementById('prod_price').value = p.Price;
            document.getElementById('prod_stock').value = p.Stock;
            document.getElementById('prod_current_image').value = p.Images;
            document.getElementById('prod_submit_btn').name = 'update_product';
            document.getElementById('productModal').style.display = 'flex';
        }

        function closeProductModal() {
            document.getElementById('productModal').style.display = 'none';
        }

        function openQRModal(id, name) {
            document.getElementById('qrProductName').textContent = name;
            document.getElementById('qrImage').src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${window.location.origin}/product_details.php?id=${id}`;
            document.getElementById('qrModal').style.display = 'flex';
        }

        function openManageUser(user) {
            document.getElementById('manageModalTitle').textContent = 'Manage ' + user.Name;
            document.getElementById('manage_uid').value = user.User_ID;
            document.getElementById('manage_status').value = user.Status || 'Active';
            document.getElementById('manageUserModal').style.display = 'flex';
        }
        window.onclick = e => {
            if (e.target.classList.contains('modal-overlay')) e.target.style.display = 'none';
        };



        // --- REPORTS CHART LOGIC ---
        <?php if ($page === 'reports'): ?>
            document.addEventListener('DOMContentLoaded', function() {
                // 1. Sales Chart
                const salesCtx = document.getElementById('salesChart').getContext('2d');
                new Chart(salesCtx, {
                    type: 'line',
                    data: {
                        labels: <?= json_encode(array_column($report_sales, 'date')) ?>,
                        datasets: [{
                            label: 'Daily Revenue (₱)',
                            data: <?= json_encode(array_column($report_sales, 'total')) ?>,
                            borderColor: '#d97719',
                            backgroundColor: 'rgba(217, 119, 25, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: 20
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            title: {
                                display: true,
                                text: 'Sales Performance (Last 7 Days)',
                                padding: {
                                    bottom: 20
                                }
                            }
                        }
                    }
                });

                // 2. Status Chart with CUSTOM COLORS
                const statusCtx = document.getElementById('statusChart').getContext('2d');
                const rawLabels = <?= json_encode(array_column($report_status, 'Order_Status')) ?>;
                const rawData = <?= json_encode(array_column($report_status, 'count')) ?>;

                // FIXED COLOR MAP: Pending=Yellow, Cancelled=Red
                const colorMap = {
                    'Pending': '#f59e0b', // Yellow/Orange
                    'Cancelled': '#ef4444', // Red
                    'Confirmed': '#10b981', // Green
                    'Delivered': '#8b5cf6', // Purple (was green in initial code, updated to match CSS variable)
                    'Shipped': '#3b82f6', // Blue
                    'Pending Confirmation': '#f59e0b' // Same as Pending
                };

                const bgColors = rawLabels.map(label => colorMap[label] || '#999999');

                new Chart(statusCtx, {
                    type: 'doughnut',
                    data: {
                        labels: rawLabels,
                        datasets: [{
                            data: rawData,
                            backgroundColor: bgColors
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: 20
                        },
                        plugins: {
                            legend: {
                                position: 'bottom'
                            },
                            title: {
                                display: true,
                                text: 'Order Status Distribution',
                                padding: {
                                    bottom: 20
                                }
                            }
                        }
                    }
                });
            });
        <?php endif; ?>
    </script>
</body>

</html>