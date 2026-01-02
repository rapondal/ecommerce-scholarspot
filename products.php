<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

// Get user info
$userName = $_SESSION['Name'] ?? 'User';

// 1. Fetch All Active Products
try {
    $stmt = $pdo->query("SELECT p.*, c.Category_Name 
                         FROM product p 
                         LEFT JOIN category c ON p.Category_ID = c.Category_ID 
                         WHERE p.Availability = 'In Stock'
                         ORDER BY p.Name ASC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $products = [];
}

// 2. Extract Unique Categories for the Filter Dropdown
$categories = [];
foreach ($products as $p) {
    if (!empty($p['Category_Name']) && !in_array($p['Category_Name'], $categories)) {
        $categories[] = $p['Category_Name'];
    }
}
sort($categories);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>All Products - ScholarSpot</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800&family=Hind:wght@400;500&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/styles.css" />

    <style>
        /* --- QR Modal Specifics --- */
        .qr-display-container {
            text-align: center;
            padding: 10px;
        }

        .qr-image {
            width: 200px;
            height: 200px;
            margin-bottom: 15px;
            border: 2px solid #eee;
            border-radius: 8px;
        }

        .btn-print {
            background: #d97719;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            margin-top: 10px;
        }

        .btn-qr {
            background: #2b2a33;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-left: 10px;
            z-index: 2;
            position: relative;
            /* Ensure click doesn't bubble easily */
        }

        /* --- Page Headers --- */
        .products-page-header {
            background-color: #fff;
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .page-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: #2b2a33;
            margin-bottom: 20px;
        }

        /* --- Search and Filter --- */
        .search-filter-container {
            display: flex;
            justify-content: center;
            gap: 15px;
            max-width: 800px;
            margin: 0 auto;
            flex-wrap: wrap;
        }

        .search-wrapper {
            flex: 2;
            min-width: 250px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e5e7eb;
            border-radius: 50px;
            font-family: 'Hind', sans-serif;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #d97719;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #9ca3af;
        }

        .category-select {
            flex: 1;
            min-width: 200px;
            padding: 12px 20px;
            border: 2px solid #e5e7eb;
            border-radius: 50px;
            font-family: 'Hind', sans-serif;
            font-size: 1rem;
            background-color: #fff;
            cursor: pointer;
            transition: border-color 0.3s;
        }

        .category-select:focus {
            outline: none;
            border-color: #d97719;
        }

        /* --- Grid Layout --- */
        .products-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            min-height: 60vh;
        }

        .no-results {
            display: none;
            text-align: center;
            padding: 50px;
            color: #6b7280;
        }

        .no-results h3 {
            font-family: 'Montserrat', sans-serif;
            margin-top: 15px;
            font-size: 1.5rem;
        }

        /* Make product clickable */
        .product-image-wrapper,
        .product-name {
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .product-image-wrapper:hover,
        .product-name:hover {
            opacity: 0.8;
        }

        /* --- General Modal Overlay --- */
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
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #fef7ea;
            padding-bottom: 10px;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 28px;
            color: #9ca3af;
            cursor: pointer;
        }

        /* --- Product Details Modal Specifics (New) --- */
        .details-box {
            max-width: 700px;
        }

        /* Wider for details */
        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        .details-img-container img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
        }

        .details-info h2 {
            font-family: 'Montserrat', sans-serif;
            color: #2b2a33;
            margin-top: 0;
        }

        .details-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: #d97719;
            margin: 10px 0;
        }

        .details-meta {
            margin-bottom: 15px;
            font-size: 0.9rem;
            color: #666;
        }

        .details-meta span {
            background: #f3f4f6;
            padding: 4px 10px;
            border-radius: 20px;
            margin-right: 5px;
            display: inline-block;
            margin-bottom: 5px;
        }

        .details-desc {
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 25px;
            max-height: 200px;
            overflow-y: auto;
        }

        .btn-details-action {
            width: 100%;
            background: #d97719;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            font-size: 1rem;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-details-action:hover {
            background: #b45309;
        }

        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }

            .details-img-container img {
                height: 200px;
            }
        }
    </style>
</head>

<body class="marketplace-page">

    <nav class="marketplace-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="images/logonew.png" alt="ScholarSpot" width="50">
                <span>ScholarSpot</span>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Home</a></li>
                <li><a href="products.php" class="active">Products</a></li>
                <li><a href="orderdetails.php">My Orders</a></li>
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

    <section class="products-page-header">
        <h1 class="page-title">Explore Our Collection</h1>

        <div class="search-filter-container">
            <div class="search-wrapper">
                <span class="search-icon"></span>
                <input type="text" id="searchInput" class="search-input" placeholder="Search for books, gadgets, supplies...">
            </div>

            <select id="categorySelect" class="category-select">
                <option value="all">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </section>

    <section class="products-container">
        <div class="products-grid" id="productsGrid">
            <?php if (empty($products)): ?>
                <div class="no-results" style="display:block;">
                    <div style="font-size: 3rem;">📦</div>
                    <h3>No products found in the database.</h3>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="product-card"
                        data-name="<?= strtolower(htmlspecialchars($product['Name'])) ?>"
                        data-category="<?= htmlspecialchars($product['Category_Name']) ?>">

                        <div class="product-image-wrapper" onclick="openDetailsModal(<?= $product['Product_ID'] ?>)">
                            <img src="<?= htmlspecialchars($product['Images']) ?>"
                                alt="<?= htmlspecialchars($product['Name']) ?>"
                                onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
                        </div>

                        <div class="product-info">
                            <h3 class="product-name" onclick="openDetailsModal(<?= $product['Product_ID'] ?>)"><?= htmlspecialchars($product['Name']) ?></h3>

                            <p class="product-price">₱<?= number_format($product['Price'], 2) ?>
                                <span class="price-unit">/ <?= htmlspecialchars($product['Category_Name']) ?></span>
                                <button class="btn-qr" onclick="event.stopPropagation(); openQRModal(<?= $product['Product_ID'] ?>, '<?= htmlspecialchars(addslashes($product['Name'])) ?>')" title="View QR Code">📱</button>
                            </p>

                            <button class="btn-add-cart" onclick="event.stopPropagation(); openQuantityModal(<?= $product['Product_ID'] ?>)">Add to Cart</button>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div id="searchNoResults" class="no-results">
                    <div style="font-size: 3rem;">🔍</div>
                    <h3>No matches found</h3>
                    <p>Try adjusting your search or category filter.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <div class="modal-overlay" id="quantityModal">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Select Quantity</h3>
                <button class="modal-close" onclick="closeQuantityModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="modal-product-info">
                    <img id="modal-product-image" src="" alt="Product">
                    <div>
                        <h4 id="modal-product-name"></h4>
                        <p id="modal-product-price"></p>
                    </div>
                </div>
                <div class="quantity-selector">
                    <label>Quantity:</label>
                    <input type="number" id="modal-qty-input" min="1" value="1" oninput="updateModalTotal()">
                    <div id="modal-stock-msg" style="font-size: 0.85rem; color: #666; margin-top: 5px;"></div>
                </div>
                <div class="modal-total">
                    Total: <strong id="modal-total-price"></strong>
                </div>
                <button class="btn-confirm-cart" onclick="confirmAddToCart()">Add to Cart</button>
            </div>
        </div>
    </div>

    <div id="qrModal" class="modal-overlay">
        <div class="modal-box" style="max-width: 400px;">
            <div class="modal-header">
                <h2>Product QR Code</h2>
                <button class="modal-close" onclick="closeQRModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="qr-display-container">
                    <h3 id="qrProductName" style="margin-top:0; color:#d97719;"></h3>
                    <img id="qrImage" class="qr-image" src="" alt="QR Code">
                    <p class="qr-instruction">Scan to view product details.</p>
                    <button class="btn-print" onclick="printQR()">🖨️ Print QR</button>
                </div>
            </div>
        </div>
    </div>

    <div id="detailsModal" class="modal-overlay">
        <div class="modal-box details-box">
            <div class="modal-header">
                <h3>Product Details</h3>
                <button class="modal-close" onclick="closeDetailsModal()">×</button>
            </div>
            <div class="modal-body">
                <div class="details-grid">
                    <div class="details-img-container">
                        <img id="det-img" src="" alt="Product">
                    </div>
                    <div class="details-info">
                        <h2 id="det-name"></h2>
                        <div class="details-meta">
                            <span id="det-category"></span>
                            <span id="det-stock"></span>
                            <span id="det-avail"></span>
                        </div>
                        <div class="details-price" id="det-price"></div>
                        <div class="details-desc">
                            <h4>Description</h4>
                            <p id="det-desc"></p>
                        </div>
                        <button id="det-add-btn" class="btn-details-action">Add to Cart</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="marketplace-footer">
        <p>&copy; 2024 ScholarSpot. All rights reserved.</p>
    </footer>

    <script>
        // --- 1. Data Loading ---
        // We ensure 'stock' is passed from PHP to JS here
        const productsData = <?= json_encode(array_reduce($products, function ($carry, $item) {
                                    $carry[$item['Product_ID']] = [
                                        'id' => $item['Product_ID'],
                                        'name' => $item['Name'],
                                        'price' => floatval($item['Price']),
                                        'image' => $item['Images'],
                                        'category' => $item['Category_Name'],
                                        'description' => $item['Description'],
                                        'stock' => intval($item['Stock']), // Ensure this is an integer
                                        'availability' => $item['Availability']
                                    ];
                                    return $carry;
                                }, [])) ?>;

        let currentProductId = null;

        // --- 2. Initialization ---
        document.addEventListener('DOMContentLoaded', function() {
            updateCartCount();

            // Attach Event Listeners for Search/Filter
            const searchInput = document.getElementById('searchInput');
            const categorySelect = document.getElementById('categorySelect');

            searchInput.addEventListener('keyup', filterProducts);
            categorySelect.addEventListener('change', filterProducts);
        });

        // --- 3. Search & Filter Logic ---
        function filterProducts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const selectedCategory = document.getElementById('categorySelect').value;
            const productCards = document.querySelectorAll('.product-card');
            const noResultsMsg = document.getElementById('searchNoResults');
            let visibleCount = 0;

            productCards.forEach(card => {
                const productName = card.getAttribute('data-name');
                const productCategory = card.getAttribute('data-category');

                const matchesSearch = productName.includes(searchTerm);
                const matchesCategory = selectedCategory === 'all' || productCategory === selectedCategory;

                if (matchesSearch && matchesCategory) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                noResultsMsg.style.display = 'block';
            } else {
                noResultsMsg.style.display = 'none';
            }
        }

        // --- 4. Cart & Modal Logic (UPDATED WITH STOCK CHECK) ---
        function updateCartCount() {
            const cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
            // Count total unique items, excluding removed ones
            const count = cart.filter(item => !item.removed).length;
            document.getElementById('cart-count').textContent = count;
        }

        function openQuantityModal(productId) {
            currentProductId = productId;
            const product = productsData[productId];

            if (!product) return;

            // Visual updates
            document.getElementById('modal-product-image').src = product.image;
            document.getElementById('modal-product-name').textContent = product.name;
            document.getElementById('modal-product-price').textContent = `₱${product.price.toFixed(2)}`;

            // Stock Validation UI
            const input = document.getElementById('modal-qty-input');
            input.value = 1;
            input.min = 1;
            input.max = product.stock; // Set HTML max attribute to actual stock

            // Helper text to show user remaining stock
            const stockMsg = document.getElementById('modal-stock-msg');
            stockMsg.textContent = `Available Stock: ${product.stock}`;

            updateModalTotal();
            document.getElementById('quantityModal').style.display = 'flex';
        }

        function closeQuantityModal() {
            document.getElementById('quantityModal').style.display = 'none';
        }

        function updateModalTotal() {
            const input = document.getElementById('modal-qty-input');
            let qty = parseInt(input.value) || 0;
            const product = productsData[currentProductId];

            // UI Force limit (visual only, logic check is in confirmAddToCart)
            if (product && qty > product.stock) {
                input.value = product.stock;
                qty = product.stock;
            }

            if (product) {
                const total = product.price * qty;
                document.getElementById('modal-total-price').textContent = `₱${total.toFixed(2)}`;
            }
        }

        function confirmAddToCart() {
            const qtyToAdd = parseInt(document.getElementById('modal-qty-input').value);
            const product = productsData[currentProductId];

            // 1. Basic Validation
            if (!product || qtyToAdd < 1) {
                alert('Invalid quantity');
                return;
            }

            if (product.stock <= 0) {
                alert('⚠️ This product is currently out of stock.');
                return;
            }

            // 2. STOCK VALIDATION LOGIC
            let cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
            const existingItem = cart.find(item => item.id === currentProductId && !item.removed);

            // Calculate how many they ALREADY have in the cart
            const currentCartQty = existingItem ? parseInt(existingItem.qty) : 0;

            // Check: (Cart Qty + New Qty) vs (Actual Database Stock)
            if ((currentCartQty + qtyToAdd) > product.stock) {
                // Calculate the maximum quantity they can still add
                const maxAddable = product.stock - currentCartQty;

                alert(`⚠️ Cannot add that quantity to cart.\n\nStock Available: ${product.stock}\nYou currently have in cart: ${currentCartQty}\nYou can only add ${maxAddable} more item(s).`);
                return; // Stop the function here
            }

            // 3. Add/Update Cart
            if (existingItem) {
                existingItem.qty += qtyToAdd;
                existingItem.removed = false;
            } else {
                cart.push({
                    id: currentProductId,
                    name: product.name,
                    price: product.price,
                    qty: qtyToAdd,
                    img: product.image,
                    category: product.category,
                    max_stock: product.stock, // Save max stock to verify in cart page too
                    removed: false,
                    selected: true // Default to selected when adding a new item
                });
            }

            localStorage.setItem('scholarSpotCart', JSON.stringify(cart));
            updateCartCount();
            closeQuantityModal();
            closeDetailsModal();
            showMessage(`${product.name} added to cart!`);
        }

        // --- 5. Toast Notification ---
        function showMessage(text) {
            const msg = document.createElement('div');
            msg.style.cssText = `
            position: fixed; top: 20px; right: 20px;
            background: #10b981; color: white;
            padding: 12px 24px; border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000; font-family: 'Hind Guntur', sans-serif;
            font-weight: 600; animation: slideIn 0.3s ease;
        `;
            msg.textContent = '✅ ' + text;
            document.body.appendChild(msg);

            setTimeout(() => {
                msg.style.animation = 'slideOut 0.3s ease';
                setTimeout(() => msg.remove(), 300);
            }, 3000);
        }

        // Add CSS animations
        const style = document.createElement('style');
        style.textContent = `
        @keyframes slideIn { from { transform: translateX(400px); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @keyframes slideOut { from { transform: translateX(0); opacity: 1; } to { transform: translateX(400px); opacity: 0; } }
    `;
        document.head.appendChild(style);

        // Close modals when clicking outside
        window.onclick = function(e) {
            if (e.target.classList.contains('modal-overlay')) {
                e.target.style.display = "none";
            }
        }

        // --- 6. QR CODE FUNCTIONS ---
        function openQRModal(productId, productName) {
            const baseUrl = window.location.origin + window.location.pathname.replace('products.php', '');
            const targetUrl = `${baseUrl}product_details.php?id=${productId}`;
            const qrApiUrl = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(targetUrl)}`;

            document.getElementById('qrProductName').textContent = productName;
            document.getElementById('qrImage').src = qrApiUrl;
            document.getElementById('qrModal').style.display = 'flex';
        }

        function closeQRModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

        function printQR() {
            const printWindow = window.open('', '', 'height=500,width=500');
            const imgSrc = document.getElementById('qrImage').src;
            const prodName = document.getElementById('qrProductName').textContent;

            printWindow.document.write(`
            <html>
                <head><title>Print QR</title></head>
                <body style="text-align:center; font-family:sans-serif;">
                    <h2>${prodName}</h2>
                    <img src="${imgSrc}" style="width:300px;">
                </body>
            </html>
        `);
            printWindow.document.close();
            printWindow.print();
        }

        // --- 7. DETAILS MODAL FUNCTIONS ---
        function openDetailsModal(id) {
            const p = productsData[id];
            if (!p) return;

            document.getElementById('det-img').src = p.image;
            document.getElementById('det-name').textContent = p.name;
            document.getElementById('det-price').textContent = `₱${p.price.toFixed(2)}`;
            document.getElementById('det-category').textContent = p.category;
            document.getElementById('det-stock').textContent = `Stock: ${p.stock}`;
            document.getElementById('det-avail').textContent = p.availability;

            // Handle Description
            document.getElementById('det-desc').innerHTML = p.description ? p.description.replace(/\n/g, '<br>') : 'No description available.';

            // Configure Add to Cart Button inside details
            const btn = document.getElementById('det-add-btn');

            // Disable button if out of stock
            if (p.stock <= 0) {
                btn.textContent = "Out of Stock";
                btn.disabled = true;
                btn.style.background = "#ccc";
                btn.style.cursor = "not-allowed";
            } else {
                btn.textContent = "Add to Cart";
                btn.disabled = false;
                btn.style.background = "#d97719";
                btn.style.cursor = "pointer";
                btn.onclick = function() {
                    // Clicking the button in the details modal opens the quantity modal
                    openQuantityModal(id);
                };
            }

            document.getElementById('detailsModal').style.display = 'flex';
        }

        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
    </script>
</body>

</html>