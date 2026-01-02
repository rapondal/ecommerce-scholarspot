<?php
session_start();
include 'db_connect.php';
date_default_timezone_set('Asia/Manila'); 

// Check if user is logged in
if (!isset($_SESSION['User_ID'])) {
    header("Location: login.php");
    exit;
}

 $user_id = $_SESSION['User_ID'];
 $userName = $_SESSION['Name'] ?? 'User';

// --- Handle Order Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Cancel Order
    if (isset($_POST['cancel_order'])) {
        $order_id = $_POST['order_id'];
        try {
            $cancel_sql = "UPDATE payment SET Order_Status = 'Cancelled' 
                           WHERE Order_ID = :order_id AND User_ID = :user_id 
                           AND (Order_Status = 'Pending' OR Order_Status = 'Pending Confirmation')";
            $stmt = $pdo->prepare($cancel_sql);
            $stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
            
            $pdo->prepare("UPDATE tbl_order SET Status = 'Cancelled' WHERE Order_ID = :order_id")->execute([':order_id' => $order_id]);
            $pdo->prepare("UPDATE shipment SET Status = 'Cancelled' WHERE Order_ID = :order_id")->execute([':order_id' => $order_id]);
            
            $_SESSION['success_message'] = "Order #{$order_id} has been cancelled.";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Failed to cancel order: " . $e->getMessage();
        }
    }
    
    // 2. Order Received
    if (isset($_POST['order_received'])) {
        $order_id = $_POST['order_id'];
        try {
            $receive_sql = "UPDATE payment SET Order_Status = 'Delivered' 
                              WHERE Order_ID = :order_id AND User_ID = :user_id AND Order_Status = 'Shipped'";
            $stmt = $pdo->prepare($receive_sql);
            $stmt->execute([':order_id' => $order_id, ':user_id' => $user_id]);
            
            $pdo->prepare("UPDATE tbl_order SET Status = 'Delivered' WHERE Order_ID = :order_id")->execute([':order_id' => $order_id]);
            $pdo->prepare("UPDATE shipment SET Status = 'Delivered' WHERE Order_ID = :order_id")->execute([':order_id' => $order_id]);
            
            $_SESSION['success_message'] = "Order #{$order_id} marked as Received. Thank you!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Action failed: " . $e->getMessage();
        }
    }
    
    header("Location: orderdetails.php");
    exit;
}

// --- Fetch User Orders ---
 $orders = [];
try {
    // NOTE: Added s.From_Address and s.To_Address from shipment table for map logic
    $sql = "SELECT p.*, o.Status as TblStatus, u.Name AS UserName, u.Email AS UserEmail, p.Shipping_Address,
                     s.Estimated_Delivery_Date, s.Driver_Start_Timestamp, s.From_Address, s.To_Address
            FROM payment p
            LEFT JOIN tbl_order o ON p.Order_ID = o.Order_ID
            LEFT JOIN user u ON p.User_ID = u.User_ID
            LEFT JOIN shipment s ON o.Order_ID = s.Order_ID
            WHERE p.User_ID = :user_id
            ORDER BY p.Order_Date DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Could not fetch orders: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - ScholarSpot</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800&family=Hind:wght@400;500&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    
    <style>
        /* BASE STYLES RETAINED */
        body.order-history-page { background-color: #fef7ea; }
        .page-header { background: #fff; padding: 40px 20px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .page-title { font-family: 'Montserrat', sans-serif; font-size: 2rem; color: #2b2a33; margin: 0; }
        .orders-container { max-width: 1100px; margin: 0 auto 50px auto; padding: 0 20px; min-height: 60vh; }
        .order-card { background: white; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px; overflow: hidden; border: 1px solid #eee; transition: transform 0.2s; }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
        .order-header { background-color: #fff; padding: 20px; border-bottom: 1px solid #f0f0f0; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .order-meta div { display: inline-block; margin-right: 25px; font-size: 0.9rem; color: #6b7280; }
        .order-meta strong { display: block; color: #2b2a33; font-family: 'Montserrat', sans-serif; font-size: 1rem; margin-top: 2px; }
        .order-body { padding: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; }
        .status-badge { padding: 6px 14px; border-radius: 50px; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; display: inline-block; }
        .status-pending-confirmation { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .status-confirmed { background-color: #ecfdf5; color: #047857; border: 1px solid #d1fae5; }
        .status-shipped { background-color: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
        .status-delivered { background-color: #f5f3ff; color: #6d28d9; border: 1px solid #ede9fe; }
        .status-cancelled { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
        .status-pending { background-color: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
        .btn { padding: 10px 20px; border-radius: 8px; font-weight: 600; font-family: 'Hind Guntur', sans-serif; border: none; cursor: pointer; transition: 0.3s; text-decoration: none; display: inline-block; }
        .btn-view { background: #d97719; color: white; } .btn-view:hover { background: #b45309; }
        .btn-cancel { background: #ef4444; color: white; } .btn-cancel:hover { background: #dc2626; }
        .btn-received { background: #10b981; color: white; } .btn-received:hover { background: #059669; }
        .btn-received:disabled { background: #a7f3d0; cursor: not-allowed; color: #fff; }
        .empty-orders { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; color: #6b7280; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .btn-shop { display: inline-block; margin-top: 15px; background: #d97719; color: white; padding: 12px 25px; border-radius: 50px; text-decoration: none; font-weight: 700; }
        .modal-overlay { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); backdrop-filter: blur(3px); align-items: center; justify-content: center; }
        .modal-box { background: #fff; padding: 30px; border-radius: 12px; width: 90%; max-width: 600px; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #fef7ea; padding-bottom: 10px; }
        .modal-header h2 { font-family: 'Montserrat', sans-serif; color: #d97719; margin: 0; }
        .modal-close { font-size: 28px; cursor: pointer; color: #9ca3af; border: none; background: none; }
        .detail-item { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #f9fafb; font-size: 0.95rem; }
        .detail-item strong { color: #6b7280; }
        .map-placeholder { margin-top: 20px; height: 300px; background: #f3f4f6; border-radius: 8px; border: 2px dashed #e5e7eb; overflow: hidden; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 8px; text-align: center; font-weight: 600; }
        .alert-success { background: #d1fae5; color: #065f46; border: 1px solid #34d399; }
        .alert-error { background: #fee2e2; color: #991b1c; border: 1px solid #f87171; }
        
        /* === ICON STYLES FOR CONSISTENCY === */
        /* Note: This SVG uses the primary color (RGB 217, 119, 25) which is defined in your admin CSS variable --color-primary. */
        .car-icon { 
            background-image: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path fill="rgb(217, 119, 25)" d="M400 0H176c-35.3 0-64 28.7-64 64v80c0 35.3 28.7 64 64 64h224c35.3 0 64-28.7 64-64V64c0-35.3-28.7-64-64-64zm-16 112c-8.8 0-16-7.2-16-16s7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16h-32zm-128 0c-8.8 0-16-7.2-16-16s7.2-16 16-16h32c8.8 0 16 7.2 16 16s-7.2 16-16 16h-32zM0 288c0-35.3 28.7-64 64-64h448c35.3 0 64 28.7 64 64v96c0 35.3-28.7 64-64 64H64c-35.3 0-64-28.7-64-64V288zm224 16c0 8.8-7.2 16-16 16h-32c-8.8 0-16-7.2-16-16v-32c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32zm160 0c0 8.8-7.2 16-16 16h-32c-8.8 0-16-7.2-16-16v-32c0-8.8 7.2-16 16-16h32c8.8 0 16 7.2 16 16v32zM64 480c-17.7 0-32-14.3-32-32s14.3-32 32-32c10.7 0 20.9 5.3 27 13.9l16 22.1c11.6 16.2 30 26 49.6 26h28.8c19.6 0 38-9.8 49.6-26l16-22.1c6.1-8.6 16.3-13.9 27-13.9s16.3-13.9 27-13.9l16 22.1c11.6 16.2 30 26 49.6 26h28.8c19.6 0 38-9.8 49.6-26l16-22.1c6.1-8.6 16.3-13.9 27-13.9s32 14.3 32 32-14.3 32-32 32H64z"/></svg>'); 
            background-repeat: no-repeat; 
            background-position: center; 
            background-size: 30px 30px; 
            width: 32px; 
            height: 32px; 
            border-radius: 50%; 
            border: 2px solid white; 
            box-shadow: 0 0 5px rgba(0,0,0,0.5); 
        }
        .destination-icon { 
            background-color: #ef4444; 
            width: 15px; 
            height: 15px; 
            border-radius: 50%; 
            border: 3px solid white; 
            box-shadow: 0 0 5px rgba(0,0,0,0.5); 
        }
        /* === END ICON STYLES === */

        @media (max-width: 768px) { .order-header { flex-direction: column; align-items: flex-start; gap: 10px; } .order-body { flex-direction: column; align-items: flex-start; } .btn { width: 100%; text-align: center; margin-bottom: 5px; } }
    </style>
</head>
<body class="order-history-page">

    <nav class="marketplace-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="images/logonew.png" alt="ScholarSpot" width="50">
                <span>ScholarSpot</span>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orderdetails.php" class="active">My Orders</a></li>
                <li><a href="user_coupon.php">My Coupons</a></li>
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
        <h1 class="page-title">Order History</h1>
    </div>

    <div class="orders-container">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                ✅ <?= htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error">
                ❌ <?= htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <div style="font-size: 4rem; margin-bottom: 15px;">📦</div>
                <h3 style="font-family:'Montserrat', sans-serif;">No orders found</h3>
                <p style="margin-bottom:20px;">You haven't placed any orders yet.</p>
                <a href="products.php" class="btn-shop">Start Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): 
                $status = $order['Order_Status'] ?? 'Unknown';
                $statusClass = 'status-' . strtolower(str_replace(' ', '-', $status));
                $deliveryTimestamp = 0;
                
                // Get delivery timestamp for JS
                if (!empty($order['Estimated_Delivery_Date'])) {
                    $deliveryTimestamp = strtotime($order['Estimated_Delivery_Date']) * 1000;
                }
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-meta">
                            <div>DATE PLACED <strong><?= date('M j, Y', strtotime($order['Order_Date'])) ?></strong></div>
                            <div>TOTAL AMOUNT <strong>₱<?= number_format($order['Total_Amount'] ?? 0, 2) ?></strong></div>
                            <div>ORDER ID <strong style="color:#d97719; font-family:monospace;">#<?= htmlspecialchars($order['Order_ID']) ?></strong></div>
                        </div>
                    </div>

                    <div class="order-body">
                        <div>
                            <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                            <div style="margin-top: 5px; color: #6b7280; font-size: 0.9rem;">Payment: <?= htmlspecialchars($order['Method'] ?? 'N/A') ?></div>
                        </div>

                        <div style="display: flex; gap: 10px;">
                            <button class="btn btn-view" onclick='openDetailsModal(<?= json_encode($order) ?>)'>View Details</button>

                            <?php if ($status === 'Pending' || $status === 'Pending Confirmation'): ?>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['Order_ID']) ?>">
                                    <button type="submit" name="cancel_order" class="btn btn-cancel">Cancel Order</button>
                                </form>
                            <?php endif; ?>

                            <?php if ($status === 'Shipped'): ?>
                                <form method="POST" onsubmit="return confirm('Have you received this order?');">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['Order_ID']) ?>">
                                    <button type="submit" 
                                            name="order_received" 
                                            class="btn btn-received auto-enable-btn" 
                                            data-arrival-time="<?= $deliveryTimestamp ?>"
                                            disabled>
                                        Arriving Soon...
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div id="orderDetailsModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h2 id="modalTitle">Order Details</h2> 
                <button class="modal-close" onclick="closeModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="detail-item"><strong>Order ID:</strong> <span id="modalOrderId"></span></div>
                <div class="detail-item"><strong>Status:</strong> <span id="modalStatus"></span></div>
                <div class="detail-item"><strong>Date:</strong> <span id="modalDate"></span></div>
                <div class="detail-item"><strong>Address:</strong> <span id="modalAddress"></span></div>
                <h4 style="margin-top:20px; border-bottom:1px solid #eee; padding-bottom:5px;">Items</h4>
                <ul id="modalOrderItems" style="list-style:none; padding:0; margin-top:10px;"></ul>
                <h4 style="margin-top:20px; border-bottom:1px solid #eee; padding-bottom:5px;">Delivery Location</h4>
                <div id="shipmentStatus" style="font-weight:bold; margin-bottom:10px;"></div>
                <div id="orderMap" class="map-placeholder"></div>
            </div>
        </div>
    </div>

    <footer class="marketplace-footer">
        <p>&copy; 2024 ScholarSpot. All rights reserved.</p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

    <script>
        // --- MAP LOGIC (COPIED AND MODIFIED FROM ADMIN DASHBOARD) ---
        let mapInstance = null, markerInstance = null, carMarker = null, animationInterval = null, routingControl = null;
        
        // API Key provided by the user in the previous turn
        const LOCATIONIQ_API_KEY = 'pk.2b04464964abd703bca47d716d8f7990'; 
        const GEOCODE_URL_BASE = `https://us1.locationiq.com/v1/search.php?key=${LOCATIONIQ_API_KEY}&format=json&limit=1&q=`;
        
        const DEFAULT_CENTER_COORD = [10.65, 122.95]; // Default Bacolod center/fallback

        const CarIcon = L.DivIcon.extend({ options: { iconSize: [32, 32], className: 'car-icon', html: '' } });
        const DestinationIcon = L.DivIcon.extend({ options: { iconSize: [15, 15], className: 'destination-icon', html: '' } });


        async function geocodeAddress(address) {
            if (!address || !LOCATIONIQ_API_KEY) return null;
            
            const encodedAddress = encodeURIComponent(address);
            const url = `${GEOCODE_URL_BASE}${encodedAddress}`;

            try {
                const response = await fetch(url);
                const data = await response.json();
                
                if (response.ok && data && data.length > 0) {
                    return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
                } else {
                    console.warn("Geocoding failed for address:", address);
                    return null;
                }
            } catch (error) {
                console.error(`Geocoding request failed:`, error);
                return null;
            }
        }
        
        function clearMapAnimation() {
            if (animationInterval) { clearInterval(animationInterval); animationInterval = null; }
            if (carMarker) { carMarker.remove(); carMarker = null; }
            if (markerInstance) { markerInstance.remove(); markerInstance = null; }
            if (routingControl) { routingControl.remove(); routingControl = null; } // Remove routing control
            if (mapInstance) { mapInstance.remove(); mapInstance = null; }
        }

        function initStaticMarker(lat, lng, popupText) {
            if (markerInstance) markerInstance.remove();
            markerInstance = L.marker([lat, lng], { icon: new DestinationIcon() }).addTo(mapInstance).bindPopup(popupText).openPopup();
            mapInstance.setView([lat, lng], 14);
        }

        // FIXED: This function now properly animates the car along the route path
        function animateCar(routePath, startMs, endMs) {
            if (animationInterval) clearInterval(animationInterval);
            if (!carMarker) return;

            const update = () => {
                const now = Date.now();
                const total = endMs - startMs;
                const elapsed = now - startMs;
                let prog = Math.min(1, Math.max(0, elapsed / total));
                
                if (prog >= 1) {
                    clearInterval(animationInterval);
                    carMarker.setLatLng(routePath[routePath.length - 1]);
                    document.getElementById('shipmentStatus').innerHTML = `<span style="color:#6d28d9;">📦 Order Arrived!</span>`;
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
                    
                    const totalSec = Math.ceil((endMs - now) / 1000);
                    const minutes = Math.floor(totalSec / 60);
                    const seconds = totalSec % 60;
                    
                    document.getElementById('shipmentStatus').innerHTML = `<span style="color:#1d4ed8;">🚚 In Transit.</span> ETA: ${minutes}m ${seconds}s`;
                }
            };
            
            animationInterval = setInterval(update, 500);
            update(); // Initial call
        }

        async function initOrderMap(order) {
            clearMapAnimation();
            const mapContainer = document.getElementById('orderMap');
            mapContainer.innerHTML = ''; // Clear container for fresh map initialization
            
            const statusDiv = document.getElementById('shipmentStatus');
            
            const isShipped = order.Order_Status === 'Shipped';
            
            // 1. Determine Addresses
            const destAddress = order.To_Address || order.Shipping_Address || 'Bacolod, Philippines';
            const startAddress = order.From_Address || 'Warehouse, Bacolod'; 
            
            // 2. Geocode Addresses
            const startCoords = await geocodeAddress(startAddress) || DEFAULT_CENTER_COORD;
            const endCoords = await geocodeAddress(destAddress) || DEFAULT_CENTER_COORD;

            try {
                // 3. Initialize Map (centered on destination)
                mapInstance = L.map('orderMap').setView(endCoords, 14);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '© OpenStreetMap' }).addTo(mapInstance);
                setTimeout(() => { if(mapInstance) mapInstance.invalidateSize(); }, 100);
            } catch (e) {
                statusDiv.innerHTML = `Map Error. Status: ${order.Order_Status}`;
                return;
            }

            const start = L.latLng(startCoords[0], startCoords[1]);
            const end = L.latLng(endCoords[0], endCoords[1]);

            if (isShipped && order.Estimated_Delivery_Date && order.Driver_Start_Timestamp) {
                const delTime = new Date(order.Estimated_Delivery_Date).getTime();
                const startTime = new Date(order.Driver_Start_Timestamp).getTime();
                
                if (delTime - Date.now() > -5000) { // Start animation if delivery hasn't just passed
                    // Set up markers for animation
                    markerInstance = L.marker(end, { icon: new DestinationIcon() }).addTo(mapInstance).bindPopup("Destination").openPopup();
                    carMarker = L.marker(start, { icon: new CarIcon() }).addTo(mapInstance);
                    
                    // === DRAW ORANGE ROUTE LINE USING LEAFLET ROUTING MACHINE ===
                    routingControl = L.Routing.control({
                        waypoints: [start, end],
                        routeWhileDragging: false,
                        show: false, // Do not show directions panel
                        addWaypoints: false,
                        draggableWaypoints: false,
                        // Orange path color matching the admin dashboard primary color (#d97719)
                        lineOptions: {styles: [{color: '#d97719', opacity: 1, weight: 5}]}, 
                        createMarker: function() { return null; } // Only use our custom markers
                    }).addTo(mapInstance);
                    
                    // Use the routesfound event to start the animation with the actual route path
                    routingControl.on('routesfound', function(e) {
                        const route = e.routes[0];
                        const routePath = route.coordinates;
                        animateCar(routePath, startTime, delTime);
                    });
                    // =========================================================
                    
                    mapInstance.fitBounds([start, end], { padding: [50, 50] });
                    return;
                }
            } 
            
            // Fallback for non-shipped or completed/cancelled orders: static marker
            statusDiv.innerHTML = `Status: ${order.Order_Status}`;
            initStaticMarker(endCoords[0], endCoords[1], "Delivery Location: " + destAddress);
        }
        
        // 4. Modal Logic
        function openDetailsModal(order) {
            document.getElementById('modalTitle').textContent = `Order #${order.Order_ID}`; 
            document.getElementById('modalOrderId').textContent = order.Order_ID || 'N/A';
            document.getElementById('modalStatus').textContent = order.Order_Status;
            document.getElementById('modalDate').textContent = new Date(order.Order_Date).toLocaleString();
            document.getElementById('modalAddress').textContent = order.Shipping_Address || 'N/A';
            
            const list = document.getElementById('modalOrderItems'); 
            list.innerHTML = '';
            
            // NOTE: Order_Details handling is retained but untested without sample data
            try {
                let items = typeof order.Order_Details === 'string' ? JSON.parse(order.Order_Details) : order.Order_Details;
                if (items && !Array.isArray(items)) items = [items];
                items.forEach(i => {
                    const li = document.createElement('li');
                    li.style.cssText = "display:flex; justify-content:space-between; padding:5px 0; border-bottom:1px dashed #eee;";
                    li.innerHTML = `<span>${i.qty}x ${i.name}</span> <span>₱${((i.price||0)*(i.qty||1)).toFixed(2)}</span>`;
                    list.appendChild(li);
                });
            } catch (e) {
                console.error("Failed to parse Order_Details:", e);
                list.innerHTML = '<li>Item details unavailable.</li>';
            }
            
            document.getElementById('orderDetailsModal').style.display = 'flex';
            document.body.style.overflow = 'hidden';
            initOrderMap(order);
        }

        function closeModal() {
            document.getElementById('orderDetailsModal').style.display = 'none';
            document.body.style.overflow = 'auto';
            clearMapAnimation();
        }

        window.onclick = function(e) { if (e.target == document.getElementById('orderDetailsModal')) closeModal(); }
        
        // 5. Cart Logic and Auto Enable "Order Received" Button
        document.addEventListener('DOMContentLoaded', function() {
            const cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
            const count = cart.filter(item => !item.removed).length;
            document.getElementById('cart-count').textContent = count;
            
            // Start Checking for Button Enable
            setInterval(checkArrivalTimes, 1000);
        });

        function checkArrivalTimes() {
            const buttons = document.querySelectorAll('.auto-enable-btn');
            const now = Date.now();

            buttons.forEach(btn => {
                const arrivalTime = parseInt(btn.getAttribute('data-arrival-time'));
                if (arrivalTime && now >= arrivalTime) {
                    btn.removeAttribute('disabled');
                    btn.textContent = 'Order Received ✓';
                    btn.style.cursor = 'pointer';
                    btn.classList.remove('auto-enable-btn'); // Stop checking this one
                }
            });
        }
    </script>
</body>
</html>