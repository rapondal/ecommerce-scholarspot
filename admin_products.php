<?php
session_start();
require 'db_connect.php';

// ... [Keep your existing PHP logic for Add/Update/Delete/Upload here] ...
// (I am omitting the top PHP block to save space, assume it is the same as before)

// ... [Fetch products logic matches previous code] ...
try {
    $products_stmt = $pdo->query("SELECT p.*, c.Category_Name FROM product p 
                                   LEFT JOIN category c ON p.Category_ID = c.Category_ID 
                                   ORDER BY p.Product_ID DESC");
    $products = $products_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
    $error_message = "Error fetching products: " . $e->getMessage();
}

try {
    $categories_stmt = $pdo->query("SELECT * FROM category ORDER BY Category_Name");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $categories = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800&family=Hind:wght@400;500&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* ... [Keep your existing CSS styles] ... */
        
        /* --- NEW: QR Button Styles --- */
        .btn-qr {
            background: #2b2a33; /* Dark Grey */
            color: white;
            flex: 0 0 40px; /* Fixed width for icon button */
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        .btn-qr:hover {
            background: #000;
        }

        /* --- NEW: QR Modal Styles --- */
        .qr-display-container {
            text-align: center;
            padding: 20px;
        }
        .qr-image {
            width: 200px;
            height: 200px;
            margin-bottom: 15px;
            border: 2px solid #eee;
            border-radius: 8px;
        }
        .qr-instruction {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 20px;
        }
        .btn-print {
            background: #d97719;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }
    </style>
</head>
<body class="admin-products-page">

    <header class="admin-header">
        <div class="header-content">
            <h1>🛍️ Product Management</h1>
            <div class="header-actions">
                <button class="btn-header" onclick="openAddModal()">➕ Add Product</button>
                <a href="dashboard.php" class="btn-header">View Userside</a>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (empty($products)): ?>
            <div class="empty-state">
                </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <img src="<?= htmlspecialchars($product['Images']) ?>" 
                             alt="<?= htmlspecialchars($product['Name']) ?>" 
                             class="product-image"
                             onerror="this.src='https://via.placeholder.com/300x200?text=No+Image'">
                        
                        <div class="product-info">
                            <h3 class="product-name"><?= htmlspecialchars($product['Name']) ?></h3>
                            <p class="product-desc"><?= htmlspecialchars($product['Description']) ?></p>
                            
                            <div class="product-meta">
                                <span><strong>Cat:</strong> <?= htmlspecialchars($product['Category_Name'] ?? 'N/A') ?></span>
                                <span><strong>Stock:</strong> <?= htmlspecialchars($product['Stock']) ?></span>
                            </div>
                            <p class="product-price">₱<?= number_format($product['Price'], 2) ?></p>
                            
                            <div class="product-actions">
                                <button class="btn btn-qr" onclick="openQRModal(<?= $product['Product_ID'] ?>, '<?= htmlspecialchars(addslashes($product['Name'])) ?>')" title="View QR Code">
                                    📱
                                </button>

                                <button class="btn btn-edit" onclick='openEditModal(<?= json_encode($product) ?>)'>
                                    ✏️ Edit
                                </button>
                                <form method="POST" style="flex: 1;" onsubmit="return confirm('Delete this product?');">
                                    <input type="hidden" name="product_id" value="<?= $product['Product_ID'] ?>">
                                    <button type="submit" name="delete_product" class="btn btn-delete">
                                        🗑️ Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Add New Product</h2>
                <button class="close" onclick="closeModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="productForm" method="POST" enctype="multipart/form-data">
                    </form>
            </div>
        </div>
    </div>

    <div id="qrModal" class="modal">
        <div class="modal-content" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Product QR Code</h2>
                <button class="close" onclick="closeQRModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="qr-display-container">
                    <h3 id="qrProductName" style="margin-top:0; color:#d97719;"></h3>
                    
                    <img id="qrImage" class="qr-image" src="" alt="QR Code">
                    
                    <p class="qr-instruction">Scan to view product details on user device.</p>
                    
                    <button class="btn-print" onclick="printQR()">🖨️ Print QR</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ... [Keep your existing openAddModal, openEditModal, closeModal scripts] ...

        // --- NEW: QR Code Logic ---
        function openQRModal(productId, productName) {
            // 1. Define the URL you want the user to visit when scanning
            // Change 'localhost/ecommerce' to your actual domain if hosting online
            // The URL points to a product detail page (e.g., product_view.php)
            const baseUrl = window.location.origin + window.location.pathname.replace('admindashboard.php', '').replace('admin_products.php', '');
            const targetUrl = `${baseUrl}product_details.php?id=${productId}`;
            
            // 2. Use Google Charts API to generate QR code image
            const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(targetUrl)}`;

            // 3. Update Modal Content
            document.getElementById('qrProductName').textContent = productName;
            document.getElementById('qrImage').src = qrApiUrl;
            
            // 4. Show Modal
            document.getElementById('qrModal').style.display = 'block';
        }

        function closeQRModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

        function printQR() {
            const printWindow = window.open('', '', 'height=500,width=500');
            const imgSrc = document.getElementById('qrImage').src;
            const prodName = document.getElementById('qrProductName').textContent;
            
            printWindow.document.write('<html><head><title>Print QR</title>');
            printWindow.document.write('</head><body style="text-align:center; font-family:sans-serif;">');
            printWindow.document.write(`<h2>${prodName}</h2>`);
            printWindow.document.write(`<img src="${imgSrc}" style="width:300px;">`);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('productModal')) closeModal();
            if (event.target == document.getElementById('qrModal')) closeQRModal();
        }
    </script>

</body>
</html>