<?php
session_start();
include 'db_connect.php';

// Security: Check if user is logged in
if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

// Fetch all orders from the 'payment' table
$orders = [];
try {
    $sql = "SELECT p.*, u.Name AS UserName, u.Email AS UserEmail
            FROM payment p
            LEFT JOIN user u ON p.User_ID = u.User_ID
            ORDER BY p.Order_Date DESC";
    $stmt = $pdo->query($sql);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $error_message = "Could not load orders. Database error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link href="https://fonts.googleapis.com/css2?family=Hind+Guntur:wght@400;600&family=Montserrat:wght@800&display=swap" rel="stylesheet" />
    <title>Order Dashboard - Admin Panel</title>
    <style>
      :root {
        --color-primary: #d97719;
        --color-accent: #b45309;
        --color-bg: #f1efea;
        --color-muted: #808986;
        --color-dark: #2b2a33;
        --color-white: #ffffff;
        --shadow-dark: rgba(35,32,42,0.10);
        --status-pending: #f59e0b;
        --status-confirmed: #10b981;
        --status-shipped: #3b82f6;
        --status-cancelled: #ef4444;
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
        line-height: 1.6;
      }

      .dashboard-header {
        background-color: var(--color-dark);
        color: var(--color-white);
        padding: 20px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px var(--shadow-dark);
      }

      .header-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: 1.8rem;
      }

      .back-link {
        color: var(--color-primary);
        text-decoration: none;
        background-color: white;
        padding: 10px 20px;
        border-radius: 8px;
        font-weight: 600;
        transition: all 0.3s;
      }

      .back-link:hover {
        background-color: var(--color-primary);
        color: white;
        transform: translateY(-2px);
      }

      .dashboard-container {
        max-width: 1400px;
        margin: 30px auto;
        padding: 0 20px;
      }

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

      .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
      }

      .stat-card {
        background: var(--color-white);
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px var(--shadow-dark);
        transition: transform 0.3s;
      }

      .stat-card:hover {
        transform: translateY(-5px);
      }

      .stat-title {
        color: var(--color-muted);
        font-size: 0.95rem;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
      }

      .stat-value {
        font-family: 'Montserrat', sans-serif;
        font-weight: 800;
        font-size: 2.2rem;
        color: var(--color-dark);
      }

      .stat-card.pending { border-left: 5px solid var(--status-pending); }
      .stat-card.confirmed { border-left: 5px solid var(--status-confirmed); }
      .stat-card.shipped { border-left: 5px solid var(--status-shipped); }
      .stat-card.total { border-left: 5px solid var(--color-primary); }

      .order-table-section {
        background: var(--color-white);
        padding: 25px;
        border-radius: 12px;
        box-shadow: 0 4px 15px var(--shadow-dark);
      }

      .table-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
      }

      .table-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 700;
        font-size: 1.6rem;
      }

      .filter-group {
        display: flex;
        align-items: center;
        gap: 10px;
      }

      .filter-group label {
        font-weight: 600;
        color: var(--color-muted);
      }

      .filter-group select {
        padding: 10px 15px;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        font-family: 'Hind Guntur', sans-serif;
        cursor: pointer;
        transition: all 0.3s;
      }

      .filter-group select:focus {
        outline: none;
        border-color: var(--color-primary);
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
      }

      .order-table td {
        padding: 15px 12px;
        background: #f9f9f9;
      }

      .order-table tbody tr {
        transition: all 0.3s;
      }

      .order-table tbody tr:hover {
        transform: translateX(5px);
      }

      .order-table tbody tr:hover td {
        background: var(--color-bg);
      }

      .order-table tbody tr td:first-child {
        border-radius: 8px 0 0 8px;
      }

      .order-table tbody tr td:last-child {
        border-radius: 0 8px 8px 0;
      }

      .status-tag {
        display: inline-block;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        color: var(--color-primary);
      }

      .status-pending-confirmation { background-color: var(--status-pending); }
      .status-confirmed { background-color: var(--status-confirmed); }
      .status-shipped { background-color: var(--status-shipped); }
      .status-cancelled { background-color: var(--status-cancelled); }

      .btn-view {
        background-color: var(--color-primary);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        font-family: 'Hind Guntur', sans-serif;
        transition: all 0.3s;
      }

      .btn-view:hover {
        background-color: var(--color-accent);
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
      }

      /* Modal Styles - Similar to User Side */
      .modal-overlay {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.6);
        backdrop-filter: blur(3px);
        animation: fadeIn 0.3s ease;
      }

      @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
      }

      .modal-box {
        background: #fff;
        margin: 3% auto;
        border-radius: 16px;
        width: 90%;
        max-width: 700px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.3);
        border: 2px solid var(--color-primary);
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        animation: slideUp 0.3s ease;
      }

      @keyframes slideUp {
        from {
          transform: translateY(50px);
          opacity: 0;
        }
        to {
          transform: translateY(0);
          opacity: 1;
        }
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

      .modal-header h2 {
        font-family: 'Montserrat', sans-serif;
        color: var(--color-primary);
        margin: 0;
        font-size: 1.5rem;
      }

      .modal-close {
        background: none;
        border: none;
        font-size: 28px;
        color: var(--color-muted);
        cursor: pointer;
        transition: all 0.3s ease;
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
      }

      .modal-close:hover {
        color: var(--color-primary);
        background: rgba(217, 119, 25, 0.15);
        transform: rotate(90deg);
      }

      .modal-body {
        padding: 25px;
        overflow-y: auto;
        flex: 1;
        max-height: calc(92vh - 100px);
      }

      .detail-section {
        margin-bottom: 25px;
      }

      .section-title {
        font-family: 'Montserrat', sans-serif;
        font-weight: 600;
        font-size: 1.2rem;
        color: var(--color-primary);
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid var(--color-bg);
      }

      .detail-item {
        display: flex;
        justify-content: space-between;
        padding: 12px 0;
        border-bottom: 1px solid var(--color-bg);
      }

      .detail-item:last-child {
        border-bottom: none;
      }

      .detail-item strong {
        color: var(--color-muted);
        font-family: 'Hind Guntur', sans-serif;
        font-weight: 600;
        min-width: 160px;
      }

      .detail-item span {
        color: var(--color-dark);
        font-family: 'Hind', sans-serif;
        text-align: right;
        flex: 1;
      }

      .item-list {
        background: var(--color-bg);
        padding: 15px;
        border-radius: 8px;
        margin-top: 10px;
      }

      .item-list li {
        list-style: none;
        padding: 10px;
        background: white;
        margin-bottom: 8px;
        border-radius: 6px;
        font-size: 0.95rem;
        border-left: 3px solid var(--color-primary);
      }

      .item-list li:last-child {
        margin-bottom: 0;
      }

      .map-placeholder {
        margin-top: 20px;
        height: 220px;
        background: linear-gradient(135deg, var(--color-bg) 0%, #e8e3d9 100%);
        border-radius: 12px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        color: var(--color-muted);
        font-weight: bold;
        border: 2px dashed var(--color-primary);
        gap: 10px;
      }

      .map-icon {
        font-size: 3rem;
      }

      .modal-body::-webkit-scrollbar {
        width: 8px;
      }

      .modal-body::-webkit-scrollbar-track {
        background: var(--color-bg);
        border-radius: 4px;
      }

      .modal-body::-webkit-scrollbar-thumb {
        background: var(--color-primary);
        border-radius: 4px;
      }

      .modal-body::-webkit-scrollbar-thumb:hover {
        background: var(--color-accent);
      }

      @media (max-width: 768px) {
        .dashboard-header {
          flex-direction: column;
          gap: 15px;
          text-align: center;
        }

        .dashboard-container {
          padding: 0 10px;
        }

        .stats-grid {
          grid-template-columns: 1fr;
        }

        .table-header {
          flex-direction: column;
          gap: 15px;
        }

        .order-table {
          font-size: 0.85rem;
        }

        .order-table th,
        .order-table td {
          padding: 10px 8px;
        }

        .modal-box {
          width: 95%;
          margin: 2% auto;
        }

        .detail-item {
          flex-direction: column;
          gap: 5px;
        }

        .detail-item strong {
          min-width: auto;
        }

        .detail-item span {
          text-align: left;
        }
      }
    </style>
</head>
<body>
    <div class="dashboard-header">
      <span class="header-title">📊 Admin Order Dashboard</span>
              <a href="dashboard.php" style="color: #23202a; text-decoration:none; background-color: white; padding: 5px 10px; border-radius: 6px; font-weight: 600;">&larr; Back to Shop</a>
    </div>

    <div class="dashboard-container">

        <?php if (isset($error_message)): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

      <div class="stats-grid">
        <div class="stat-card total">
          <div class="stat-title">Total Orders</div>
          <div class="stat-value" id="totalCount">0</div>
        </div>
        <div class="stat-card pending">
          <div class="stat-title">Pending Confirmation</div>
          <div class="stat-value" id="pendingCount">0</div>
        </div>
        <div class="stat-card confirmed">
          <div class="stat-title">Confirmed Orders</div>
          <div class="stat-value" id="confirmedCount">0</div>
        </div>
        <div class="stat-card shipped">
          <div class="stat-title">Total Revenue</div>
          <div class="stat-value" id="revenueTotal">₱0.00</div>
        </div>
      </div>

      <div class="order-table-section">
        <div class="table-header">
          <h2 class="table-title">Recent Orders</h2>
          <div class="filter-group">
            <label for="statusFilter">Filter:</label>
            <select id="statusFilter" onchange="applyFilters()">
              <option value="All">All Statuses</option>
              <option value="Pending Confirmation">Pending Confirmation</option>
              <option value="Confirmed">Confirmed</option>
              <option value="Shipped">Shipped</option>
              <option value="Delivered">Delivered</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
        </div>

        <table class="order-table">
          <thead>
            <tr>
              <th>ID</th>
              <th>Customer</th>
              <th>Date</th>
              <th>Amount</th>
              <th>Payment & Items</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody id="orderTableBody">
            <?php if (!empty($orders)): ?>
                <?php foreach ($orders as $order): 
                    $order_details = json_decode($order['Order_Details'] ?? '[]', true);
                    $item_count = is_array($order_details) ? count($order_details) : 0;
                    $order_status = $order['Order_Status'] ?? 'Unknown';
                    $status_class = strtolower(str_replace(' ', '-', $order_status));
                    
                    $order_date = 'N/A';
                    if (!empty($order['Order_Date'])) {
                        try {
                            $date_obj = new DateTime($order['Order_Date']);
                            $order_date = $date_obj->format('M d, Y');
                        } catch (Exception $e) {
                            $order_date = $order['Order_Date'];
                        }
                    }
                    
                    $customer_name = $order['Customer_Name'] ?? $order['UserName'] ?? 'Unknown';
                ?>
                <tr data-status="<?= htmlspecialchars($order_status) ?>">
                    <td><strong>#<?= htmlspecialchars($order['Payment_ID']) ?></strong></td>
                    <td><?= htmlspecialchars($customer_name) ?></td>
                    <td><?= htmlspecialchars($order_date) ?></td>
                    <td><strong>₱<?= number_format($order['Total_Amount'] ?? 0, 2) ?></strong></td>
                    <td>
                        <small style="display: block;">Payment: <?= htmlspecialchars($order['Method'] ?? 'N/A') ?></small>
                        <small style="display: block; color: var(--color-muted);">Items: <?= htmlspecialchars($item_count) ?></small>
                    </td>
                    <td>
                        <span class="status-tag status-<?= $status_class ?>">
                            <?= htmlspecialchars($order_status) ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn-view" onclick='openDetailsModal(<?= htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8') ?>)'>
                             View
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: var(--color-muted);">
                        <div style="font-size: 3rem; margin-bottom: 10px;">📭</div>
                        No orders found in the database.
                    </td>
                </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal -->
    <div id="orderDetailsModal" class="modal-overlay">
      <div class="modal-box">
        <div class="modal-header">
          <h2 id="modalTitle">Order Details</h2>
          <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="detail-section">
              <h3 class="section-title">Order Information</h3>
              <div class="detail-item"><strong>Payment ID:</strong> <span id="modalPaymentId"></span></div>
              <div class="detail-item"><strong>Order ID:</strong> <span id="modalOrderId"></span></div>
              <div class="detail-item"><strong>Order Date:</strong> <span id="modalOrderDate"></span></div>
              <div class="detail-item"><strong>Order Status:</strong> <span id="modalStatus"></span></div>
            </div>

            <div class="detail-section">
              <h3 class="section-title">Customer Information</h3>
              <div class="detail-item"><strong>Customer Name:</strong> <span id="modalCustomerName"></span></div>
              <div class="detail-item"><strong>Shipping Address:</strong> <span id="modalAddress"></span></div>
            </div>

            <div class="detail-section">
              <h3 class="section-title">Payment Details</h3>
              <div class="detail-item"><strong>Payment Method:</strong> <span id="modalPaymentMethod"></span></div>
              <div class="detail-item"><strong>Reference Number:</strong> <span id="modalReferenceNum"></span></div>
              <div class="detail-item"><strong>Total Amount:</strong> <span id="modalTotalAmount" style="color: var(--color-primary); font-weight: 700; font-size: 1.2rem;"></span></div>
            </div>

            <div class="detail-section">
              <h3 class="section-title">Order Items</h3>
              <ul id="modalOrderItems" class="item-list"></ul>
            </div>

            <div class="map-placeholder">
              <div class="map-icon">🗺️</div>
              <div>MAP PLACEHOLDER (API MAP HERE)</div>
              <small style="color: var(--color-muted); font-weight: normal;">Delivery tracking will be integrated here</small>
            </div>
        </div>
      </div>
    </div>

    <script>
      function openDetailsModal(order) {
        const modal = document.getElementById('orderDetailsModal');
        const modalItemsList = document.getElementById('modalOrderItems');

        document.getElementById('modalTitle').textContent = `Order #${order.Order_ID || 'N/A'} Details`;
        document.getElementById('modalPaymentId').textContent = `#${order.Payment_ID || 'N/A'}`;
        document.getElementById('modalOrderId').textContent = `#${order.Order_ID || 'N/A'}`;
        document.getElementById('modalCustomerName').textContent = order.Customer_Name || order.UserName || 'Unknown';
        document.getElementById('modalTotalAmount').textContent = `₱${parseFloat(order.Total_Amount || 0).toFixed(2)}`;
        document.getElementById('modalStatus').textContent = order.Order_Status || 'Unknown';
        document.getElementById('modalPaymentMethod').textContent = order.Method || 'N/A';
        document.getElementById('modalReferenceNum').textContent = order.Reference_Num || 'N/A';
        document.getElementById('modalAddress').textContent = order.Shipping_Address || 'N/A';

        if (order.Order_Date) {
          try {
            const dateObj = new Date(order.Order_Date.replace(' ', 'T'));
            document.getElementById('modalOrderDate').textContent = dateObj.toLocaleString('en-US', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
              hour: '2-digit',
              minute: '2-digit'
            });
          } catch (e) {
            document.getElementById('modalOrderDate').textContent = order.Order_Date;
          }
        } else {
          document.getElementById('modalOrderDate').textContent = 'N/A';
        }

        modalItemsList.innerHTML = '';
        try {
            const items = order.Order_Details ? JSON.parse(order.Order_Details) : [];
            if (Array.isArray(items) && items.length > 0) {
                items.forEach(item => {
                    const li = document.createElement('li');
                    const subtotal = (item.price || 0) * (item.qty || 0);
                    li.innerHTML = `<strong>${item.qty}x ${item.name}</strong><br>
                                    <small>₱${parseFloat(item.price || 0).toFixed(2)} each = <strong>₱${subtotal.toFixed(2)}</strong></small>`;
                    modalItemsList.appendChild(li);
                });
            } else {
                modalItemsList.innerHTML = '<li style="text-align: center; color: var(--color-muted);">No detailed items found.</li>';
            }
        } catch (e) {
            modalItemsList.innerHTML = '<li style="text-align: center; color: var(--status-cancelled);">Error loading item details.</li>';
            console.error("Error parsing Order_Details JSON:", e);
        }

        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
      }

      function closeModal() {
        document.getElementById('orderDetailsModal').style.display = 'none';
        document.body.style.overflow = 'auto';
      }

      window.onclick = function (event) {
        const modal = document.getElementById('orderDetailsModal');
        if (event.target === modal) {
          closeModal();
        }
      };

      // Close modal with Escape key
      document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
      });

      function applyFilters() {
        const selectedStatus = document.getElementById('statusFilter').value;
        const rows = document.getElementById('orderTableBody').getElementsByTagName('tr');

        let totalCount = 0;
        let pendingCount = 0;
        let confirmedCount = 0;
        let revenueTotal = 0;

        for (let row of rows) {
          const status = row.getAttribute('data-status');

          if (!status || row.cells.length < 4) continue;

          const isFiltered = (selectedStatus === 'All' || status === selectedStatus);
          row.style.display = isFiltered ? '' : 'none';

          if (isFiltered) {
            totalCount++;
            const amountCell = row.cells[3].textContent.replace('₱', '').replace(',', '');
            const amount = parseFloat(amountCell);

            if (!isNaN(amount)) {
                revenueTotal += amount;
            }

            if (status === 'Pending Confirmation') {
                pendingCount++;
            } else if (status === 'Confirmed' || status === 'Shipped' || status === 'Delivered') {
                confirmedCount++;
            }
          }
        }

        document.getElementById('totalCount').textContent = totalCount;
        document.getElementById('pendingCount').textContent = pendingCount;
        document.getElementById('confirmedCount').textContent = confirmedCount;
        document.getElementById('revenueTotal').textContent = `₱${revenueTotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",")}`;
      }

      document.addEventListener('DOMContentLoaded', () => {
        applyFilters();
      });
    </script>
  </body>
</html>)