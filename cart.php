<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

$userName = $_SESSION['Name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - ScholarSpot</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800&family=Hind:wght@400;500&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="css/styles.css">

    <style>
        /* --- Page Specific Styles --- */
        body.cart-page {
            background-color: #f9fafb; /* Light Gray Background */
        }

        .page-header {
            background: #fff;
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid #eee;
            margin-bottom: 30px;
        }

        .page-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 2rem;
            color: #2b2a33;
            margin: 0;
        }

        .cart-wrapper {
            max-width: 1200px;
            margin: 0 auto 50px auto;
            padding: 0 20px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        /* Cart Table Section */
        .cart-items-container {
            flex: 3;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            overflow: hidden;
        }

        .cart-table {
            width: 100%;
            border-collapse: collapse;
        }

        .cart-table th {
            background-color: #f3f4f6;
            color: #4b5563;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 15px 20px;
            text-align: left;
        }

        .cart-table td {
            padding: 20px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .cart-product-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .cart-product-img {
            width: 70px;
            height: 70px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #eee;
        }

        .cart-product-name {
            font-weight: 600;
            color: #2b2a33;
            font-size: 1rem;
        }

        /* Quantity Controls */
        .qty-group {
            display: flex;
            align-items: center;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            width: fit-content;
        }

        .qty-btn {
            background: none;
            border: none;
            padding: 5px 12px;
            font-size: 1.2rem;
            color: #6b7280;
            cursor: pointer;
            transition: color 0.2s;
        }

        .qty-btn:hover {
            color: #d97719;
        }
        
        .qty-btn[disabled] {
            cursor: not-allowed;
            opacity: 0.5;
        }

        .qty-input {
            width: 40px;
            text-align: center;
            border: none;
            font-family: 'Hind', sans-serif;
            font-weight: 600;
            color: #2b2a33;
        }
        
        .qty-input:focus { outline: none; }

        .price-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            color: #2b2a33;
        }

        .subtotal-text {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #d97719;
        }

        .remove-link {
            color: #ef4444;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
        }
        .remove-link:hover { text-decoration: underline; }

        /* Summary Sidebar */
        .cart-summary {
            flex: 1;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            position: sticky;
            top: 20px;
        }

        .summary-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.2rem;
            border-bottom: 2px solid #f3f4f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
            color: #2b2a33;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: #4b5563;
            font-size: 0.95rem;
        }

        .summary-total {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #f3f4f6;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 1.2rem;
            color: #2b2a33;
        }

        .btn-checkout {
            display: block;
            width: 100%;
            background-color: #d97719;
            color: white;
            text-align: center;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            font-family: 'Hind Guntur', sans-serif;
            font-size: 1rem;
            margin-top: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .btn-checkout:hover:not([disabled]) {
            background-color: #b45309;
        }
        
        .btn-checkout[disabled] {
            cursor: not-allowed;
            opacity: 0.7;
            background-color: #6b7280 !important; /* Force grey for disabled */
        }

        .btn-clear {
            display: block;
            width: 100%;
            background: none;
            border: 2px solid #ef4444;
            color: #ef4444;
            padding: 10px;
            border-radius: 8px;
            margin-top: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-clear:hover {
            background-color: #ef4444;
            color: white;
        }

        /* Checkbox styling */
        .custom-checkbox {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: #d97719;
        }

        /* Empty Cart State */
        .empty-cart-state {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-icon { font-size: 4rem; margin-bottom: 15px; display: block; }
        .btn-shop-now {
            display: inline-block;
            margin-top: 15px;
            background: #d97719;
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
        }
        
        /* Added style for stock warning in cart */
        .stock-warning {
            color: #ef4444;
            font-size: 0.8em;
            font-weight: 500;
            margin-top: 5px;
            display: block;
        }

        @media (max-width: 900px) {
            .cart-wrapper { flex-direction: column; }
            .cart-summary { width: 100%; }
            .cart-table th:nth-child(2), .cart-table td:nth-child(2) { display: none; /* Hide individual price on mobile */ }
        }
    </style>
</head>
<body class="cart-page">

    <nav class="marketplace-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="images/logonew.png" alt="ScholarSpot" width="50">
                <span>ScholarSpot</span>
            </div>
            <ul class="nav-menu">
                <li><a href="dashboard.php">Home</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orderdetails.php">My Orders</a></li>
                <li><a href="user_coupon.php">My Coupons</a></li>
                <li><a href="userprofile.php">Profile</a></li>
            </ul>
            <div class="nav-actions">
                <span class="user-greeting">Hi, <?= htmlspecialchars($userName) ?>!</span>
                <a href="cart.php" class="nav-cart active"> 🛒 Cart
                    <span id="cart-count" class="cart-badge">0</span>
                </a>
                <a href="logout.php" class="nav-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <h1 class="page-title">Shopping Cart</h1>
    </div>

    <div class="cart-wrapper">
        
        <div class="cart-items-container">
            <table class="cart-table" id="cartTable">
                <thead>
                    <tr>
                        <th style="width: 50px; text-align: center;">✓</th>
                        <th>Product Details</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cartBody">
                </tbody>
            </table>
        </div>

        <aside class="cart-summary">
            <h3 class="summary-title">Order Summary</h3>
            
            <div class="summary-row">
                <span>Selected Items</span>
                <span id="itemCount">0</span>
            </div>
            
            <div class="summary-row">
                <span>Subtotal</span>
                <span id="subTotal">₱0.00</span>
            </div>
            
            <div class="summary-row">
                <span>Estimated Shipping</span>
                <span>Calculated at checkout</span>
            </div>
            
            <div class="summary-total">
                <span>Total</span>
                <span id="total" style="color: #d97719;">₱0.00</span>
            </div>
            
            <button class="btn-checkout" id="checkoutBtn">Proceed to Checkout</button>
            <button class="btn-clear" onclick="clearCart()">Empty Cart</button>
            
            <div style="margin-top: 20px; text-align: center;">
                <a href="products.php" style="color: #6b7280; text-decoration: none; font-size: 0.9rem;">&larr; Continue Shopping</a>
            </div>
        </aside>

    </div>
    
    

    <script>
        let cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];

        // Initialize Page
        document.addEventListener('DOMContentLoaded', () => {
            renderCart();
            updateNavCartCount();
        });

        function updateNavCartCount() {
            const count = cart.filter(item => !item.removed).length;
            document.getElementById('cart-count').textContent = count;
        }

        function renderCart() {
            const tbody = document.getElementById('cartBody');
            tbody.innerHTML = '';

            const activeItems = cart.filter(item => !item.removed);

            if (activeItems.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="empty-cart-state">
                            <span class="empty-icon">🛒</span>
                            <h3>Your cart is empty</h3>
                            <p style="color:#6b7280;">Looks like you haven't added anything yet.</p>
                            <a href="products.php" class="btn-shop-now">Start Shopping</a>
                        </td>
                    </tr>
                `;
                // Disable buttons if empty
                document.getElementById('checkoutBtn').disabled = true;
                document.getElementById('checkoutBtn').style.opacity = '0.5';
                document.getElementById('checkoutBtn').style.cursor = 'not-allowed';
            } else {
                let hasOutOfStockIssue = false; // Track if any item is problematic

                cart.forEach((item, index) => {
                    if (item.removed) return;
                    
                    // Default selection state
                    if (item.selected === undefined) item.selected = false;
                    
                    // --- NEW STOCK LOGIC FOR DISPLAY ---
                    const stockLimitExceeded = item.qty > item.max_stock;
                    let stockMessage = '';
                    let checkoutDisabled = false; // Per-row disable state
                    
                    // The quantity displayed should be capped at max_stock if exceeded, for calculation purposes
                    const calculatedQty = stockLimitExceeded ? item.max_stock : item.qty;
                    const subtotal = item.price * calculatedQty;

                    if (item.max_stock <= 0) {
                         stockMessage = '⚠️ Out of Stock! Please remove this item.';
                         hasOutOfStockIssue = true;
                    } else if (stockLimitExceeded) {
                        stockMessage = `⚠️ Max Quantity: ${item.max_stock} available. Please reduce.`;
                        hasOutOfStockIssue = true;
                    }
                    
                    const plusButtonDisabled = item.qty >= item.max_stock ? 'disabled' : '';
                    const checkboxDisabled = stockLimitExceeded || item.max_stock <= 0 ? 'disabled' : '';
                    
                    // If stock is exceeded, deselect the item and prevent re-selection
                    if (stockLimitExceeded || item.max_stock <= 0) {
                        item.selected = false;
                    }


                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td style="text-align: center;">
                            <input type="checkbox" class="custom-checkbox" ${item.selected ? 'checked' : ''} ${checkboxDisabled} onchange="toggleSelect(${index}, this.checked)">
                        </td>
                        <td>
                            <div class="cart-product-info">
                                <img src="${item.img}" alt="${item.name}" class="cart-product-img" onerror="this.src='https://via.placeholder.com/70?text=Img'">
                                <div>
                                    <div class="cart-product-name">${item.name}</div>
                                    ${stockMessage ? `<span class="stock-warning">${stockMessage}</span>` : ''}
                                </div>
                            </div>
                        </td>
                        <td class="price-text">₱${item.price.toFixed(2)}</td>
                        <td>
                            <div class="qty-group">
                                <button class="qty-btn" onclick="changeQty(${index}, -1)">−</button>
                                <input type="text" class="qty-input" value="${item.qty}" readonly>
                                <button class="qty-btn" ${plusButtonDisabled} onclick="changeQty(${index}, 1)">+</button>
                            </div>
                        </td>
                        <td class="subtotal-text">₱${subtotal.toFixed(2)}</td>
                        <td style="text-align: right;">
                            <span class="remove-link" onclick="removeItem(${index})">Remove</span>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
                
                // Finalize cart state and update summary
                saveCart(); 
                updateSummary(hasOutOfStockIssue);
            }
        }

        function toggleSelect(index, checked) {
            const item = cart[index];
            // Only allow selection if the quantity is within the stock limit
            if (item.qty <= item.max_stock && item.max_stock > 0) {
                item.selected = checked;
                saveCart();
                updateSummary();
            }
        }

        function changeQty(index, delta) {
            const item = cart[index];
            const newQty = item.qty + delta;
            
            // 1. Check if quantity is valid (min 1)
            if (newQty < 1) {
                removeItem(index); // Remove item if quantity drops to 0 or below
                return;
            }
            
            // 2. Check against max stock
            if (newQty > item.max_stock) {
                alert(`⚠️ Cannot increase quantity.\nAvailable Stock for ${item.name}: ${item.max_stock}`);
                return;
            }

            // 3. Update if valid
            item.qty = newQty;
            item.selected = true; // Automatically select the item when quantity is changed
            saveCart();
            renderCart(); // Re-render to update the display and buttons
        }

        function removeItem(index) {
            if (confirm(`Remove ${cart[index].name} from your cart?`)) {
                cart.splice(index, 1);
                saveCart();
                renderCart();
                updateNavCartCount();
            }
        }

        function clearCart() {
            if (cart.filter(item => !item.removed).length === 0) return;
            
            if (confirm('Are you sure you want to remove all items?')) {
                localStorage.removeItem('scholarSpotCart');
                cart = [];
                renderCart();
                updateNavCartCount();
            }
        }

        function updateSummary(hasOutOfStockIssue = false) {
            let selectedCount = 0;
            let subtotal = 0;
            
            // If the rendering process already found an issue, we prioritize that.
            let canCheckout = !hasOutOfStockIssue;

            cart.forEach(item => {
                if (!item.removed && item.selected) {
                    // Recalculate based on current stock limit
                    const calculatedQty = item.qty > item.max_stock ? item.max_stock : item.qty;
                    
                    selectedCount += 1; 
                    subtotal += item.price * calculatedQty;
                }
            });

            document.getElementById('itemCount').textContent = selectedCount;
            document.getElementById('subTotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('total').textContent = `₱${subtotal.toFixed(2)}`;
            
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            if (hasOutOfStockIssue) {
                 checkoutBtn.textContent = 'Fix Stock Issues to Checkout';
                 checkoutBtn.disabled = true;
            } else if (selectedCount > 0) {
                checkoutBtn.textContent = `Checkout (${selectedCount} items)`;
                checkoutBtn.disabled = false;
            } else { // No stock issues, but nothing selected
                checkoutBtn.textContent = 'Proceed to Checkout';
                checkoutBtn.disabled = true;
            }
        }

        function saveCart() {
            localStorage.setItem('scholarSpotCart', JSON.stringify(cart));
        }

        // Checkout Handling
        document.getElementById('checkoutBtn').addEventListener('click', () => {
            const selectedItems = cart.filter(item => item.selected && !item.removed);
            
            if (selectedItems.length === 0) {
                alert('Please select at least one item to proceed.');
                return;
            }

            // Redundant, but necessary final check before checkout navigation
            const stockIssue = selectedItems.some(item => item.qty > item.max_stock || item.max_stock <= 0);
            if (stockIssue) {
                 alert('Error: One or more selected items have a quantity exceeding the available stock or are out of stock. Please remove/correct the items to proceed.');
                 renderCart();
                 return;
            }

            // Save selected items for the checkout page
            localStorage.setItem('selectedCheckoutItems', JSON.stringify(selectedItems));
            window.location.href = 'checkout.php';
        });

    </script>
</body>
</html>