<?php
session_start();
require 'db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

$userName = $_SESSION['Name'] ?? 'User';
$userEmail = $_SESSION['Email'] ?? '';
$userId = $_SESSION['User_ID'] ?? '';

// NOTE: Automatic coupon fetching removed. 
// We now rely on the manual input field below.
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - ScholarSpot</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800&family=Hind:wght@400;500&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/styles.css">

    <script src="https://js.stripe.com/v3/"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* --- Checkout Specific Styles --- */
        body.checkout-page {
            background-color: #f9fafb;
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

        .checkout-wrapper {
            max-width: 1200px;
            margin: 0 auto 50px auto;
            padding: 0 20px;
            display: flex;
            gap: 30px;
            align-items: flex-start;
        }

        .checkout-main {
            flex: 2;
        }

        .form-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            padding: 30px;
            margin-bottom: 25px;
            border: 1px solid #eee;
        }

        .section-title {
            font-family: 'Montserrat', sans-serif;
            font-size: 1.25rem;
            color: #2b2a33;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f3f4f6;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #4b5563;
            font-family: 'Hind Guntur', sans-serif;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-family: 'Hind', sans-serif;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #d97719;
        }

        /* Manual Coupon Styling */
        .coupon-input-group {
            display: flex;
            gap: 10px;
        }

        .coupon-input-group input {
            flex: 1;
        }

        .btn-apply {
            background: #2b2a33;
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-apply:hover {
            background: #000;
        }

        .coupon-message {
            font-size: 0.9rem;
            margin-top: 5px;
            display: none;
            font-weight: 600;
        }

        .coupon-message.success {
            color: #10b981;
        }

        .coupon-message.error {
            color: #ef4444;
        }

        .remove-coupon {
            color: #ef4444;
            cursor: pointer;
            font-size: 0.85rem;
            text-decoration: underline;
            margin-left: 10px;
        }

        /* Payment Methods Grid */
        .payment-methods {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 15px;
        }

        .payment-option {
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
            background: #fff;
        }

        .payment-option:hover {
            border-color: #d97719;
            background-color: #fff7ed;
        }

        .payment-option.selected {
            border-color: #d97719;
            background-color: #fff7ed;
            color: #d97719;
            border-width: 3px;
        }

        .payment-option input {
            display: none;
        }

        .payment-icon {
            font-size: 24px;
            margin-bottom: 5px;
            display: block;
        }

        .payment-label {
            font-weight: 600;
            font-size: 0.9rem;
        }

        /* Sidebar */
        .checkout-sidebar {
            flex: 1;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 20px;
            border: 1px solid #eee;
        }

        .cart-item-preview {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .cart-item-preview img {
            width: 50px;
            height: 50px;
            border-radius: 6px;
            object-fit: cover;
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

        .btn-place-order {
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

        .btn-place-order:hover {
            background-color: #b45309;
        }

        .btn-place-order:disabled {
            background-color: #9ca3af;
            cursor: not-allowed;
        }

        .discount-row {
            color: #10b981;
            font-weight: 600;
            padding-top: 10px;
            border-top: 1px dashed #e5e7eb;
            display: none;
        }

        /* Hidden by default */

        /* Modal Styles (Same as before) */
        .modal {
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

        .modal-content {
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
        }

        .close-button {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #9ca3af;
        }

        .modal-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 25px;
        }

        .modal-btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }

        .cancel-btn {
            background: #f3f4f6;
            color: #4b5563;
        }

        .confirm-btn {
            background: #d97719;
            color: white;
        }

        /* Stripe UI */
        .stripe-form-container {
            margin-top: 15px;
        }

        .stripe-field {
            margin-bottom: 15px;
        }

        .stripe-input-box {
            border: 1px solid #d1d5db;
            border-radius: 4px;
            padding: 12px;
            background: white;
            transition: border-color 0.2s ease;
        }

        .stripe-input-box:focus-within {
            border-color: #3b82f6;
            box-shadow: 0 0 0 1px #3b82f6;
        }

        .stripe-row-split {
            display: flex;
            gap: 15px;
        }

        .stripe-row-split>div {
            flex: 1;
        }

        .save-card-check {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9rem;
            color: #4b5563;
            margin-bottom: 20px;
        }

        .stripe-error {
            color: #991b1b;
            font-size: 14px;
            margin-top: 10px;
            font-weight: 600;
            display: none;
        }

        .confirm-btn-stripe {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 8px;
            width: 100%;
            margin-top: 20px;
            padding: 18px;
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.3s ease;
            box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
        }

        .confirm-btn-stripe:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        @media (max-width: 900px) {
            .checkout-wrapper {
                flex-direction: column;
            }

            .checkout-sidebar {
                width: 100%;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body class="checkout-page">

    <nav class="marketplace-nav">
        <div class="nav-container">
            <div class="nav-logo">
                <img src="images/logonew.png" alt="ScholarSpot" width="50">
                <span>ScholarSpot</span>
            </div>
            <ul class="nav-menu">
                <li><a href="cart.php">Return to Cart</a></li>
            </ul>
            <div class="nav-actions">
                <span class="user-greeting">Hi, <?= htmlspecialchars($userName) ?>!</span>
                <a href="logout.php" class="nav-logout">Logout</a>
            </div>
        </div>
    </nav>

    <div class="page-header">
        <h1 class="page-title">Secure Checkout</h1>
    </div>

    <div class="checkout-wrapper">

        <div class="checkout-main">
            <?php if (isset($_SESSION['checkout_error'])): ?>
                <div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <?= htmlspecialchars($_SESSION['checkout_error']);
                    unset($_SESSION['checkout_error']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['checkout_success'])): ?>
                <div style="background:#d1fae5; color:#065f46; padding:15px; border-radius:8px; margin-bottom:20px;">
                    <?= htmlspecialchars($_SESSION['checkout_success']);
                    unset($_SESSION['checkout_success']); ?>
                </div>
                <script>
                    document.addEventListener('DOMContentLoaded', clearLocalCartAfterSuccess);
                </script>
            <?php endif; ?>

            <form id="checkoutForm" method="POST" action="checkout_process.php">

                <div class="form-card">
                    <h3 class="section-title">Customer Information</h3>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($userName) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" value="<?= htmlspecialchars($userEmail) ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Phone Number *</label>
                        <input type="tel" id="customer_phone" name="phone" placeholder="+63 9XX XXX XXXX" required>
                    </div>
                </div>

                <div class="form-card">
                    <h3 class="section-title">Shipping Address</h3>
                    <div class="form-group">
                        <label>Complete Address *</label>
                        <textarea name="shipping_address" rows="3" placeholder="Street, Barangay, City, Province" required></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label>City *</label><input type="text" name="city" required></div>
                        <div class="form-group"><label>Postal Code *</label><input type="text" name="postal_code" required></div>
                    </div>
                </div>

                <div class="form-card">
                    <h3 class="section-title">Extras</h3>
                    <div class="form-group">
                        <label>Coupon Code (Optional)</label>
                        <div class="coupon-input-group">
                            <input type="text" id="coupon_code_input" name="coupon_code_input" placeholder="Enter your claimed code">
                            <button type="button" class="btn-apply" onclick="applyCoupon()">Apply</button>
                        </div>
                        <div id="couponMessage" class="coupon-message"></div>
                        <input type="hidden" id="coupon_id" name="coupon_id" value="0">
                    </div>
                    <div class="form-group">
                        <label>Order Notes (Optional)</label>
                        <textarea name="order_notes" rows="2" placeholder="Special instructions..."></textarea>
                    </div>
                </div>

                <div class="form-card">
                    <h3 class="section-title">Payment Method</h3>
                    <div class="payment-methods">
                        <label class="payment-option"><input type="radio" name="payment_method" value="Cash on Delivery" required><span class="payment-icon">💰</span><span class="payment-label">COD</span></label>
                        <label class="payment-option"><input type="radio" name="payment_method" value="Credit Card" required><span class="payment-icon">💳</span><span class="payment-label">Card</span></label>
                    </div>
                </div>

                <input type="hidden" id="cart_data" name="cart_data">
                <input type="hidden" id="user_id" name="user_id" value="<?= htmlspecialchars($userId) ?>">
                <input type="hidden" id="order_id" name="order_id" value="<?= 'ORD' . date('Ymd') . rand(1000, 9999) ?>">
                <input type="hidden" id="stripeToken" name="stripeToken">

            </form>
        </div>

        <aside class="checkout-sidebar">
            <h3 class="section-title">Order Summary</h3>
            <div id="orderItems"></div>
            <div style="margin-top: 20px;">
                <div class="summary-row"><span>Subtotal</span><span id="subtotal">₱0.00</span></div>

                <div class="summary-row discount-row" id="couponRow">
                    <span>Coupon (<span id="couponCode"></span>) <span class="remove-coupon" onclick="removeCoupon()">[Remove]</span></span>
                    <span id="discountValue">- ₱0.00</span>
                </div>

                <div class="summary-row"><span>Shipping</span><span id="shipping">₱0.00</span></div>
                <div class="summary-row"><span>Tax (0%)</span><span id="tax">₱0.00</span></div>
                <div class="summary-total"><span>Total</span><span id="total" style="color: #d97719;">₱0.00</span></div>
            </div>
            <button class="btn-place-order" id="placeOrderBtn" onclick="showConfirmationModal()">Place Order</button>
        </aside>

    </div>

    <div id="confirmationModal" class="modal">
        <div class="modal-content">
            <span class="close-button" id="closeModalBtn">&times;</span>
            <h3 style="font-family:'Montserrat', sans-serif; color:#d97719; margin-bottom:15px;">Confirm Order</h3>
            <div id="modalOrderSummary" style="background:#f9fafb; padding:15px; border-radius:8px; margin:15px 0; border:1px solid #e5e7eb; font-size:0.9rem;"></div>
            <div class="modal-actions">
                <button id="cancelOrderBtn" class="modal-btn cancel-btn">Cancel</button>
                <button id="confirmOrderBtn" class="modal-btn confirm-btn">Confirm & Pay</button>
            </div>
        </div>
    </div>

    <div id="stripeModal" class="modal">
        <div class="modal-content" style="max-width: 450px; padding: 40px;">
            <span class="close-button" id="closeStripeBtn">&times;</span>
            <div class="stripe-form-container">
                <center><h3> STRIPE PAYMENT </h3></center>
                <br>
                <div class="stripe-field"><input type="text" id="stripe-name" class="stripe-input-box" placeholder="Card Holder Name" style="width: 100%; border: 1px solid #d1d5db; padding: 12px; border-radius: 4px; font-family: inherit;"></div>
                <div class="stripe-field">
                    <div id="card-number-element" class="stripe-input-box"></div>
                </div>
                <div class="stripe-row-split">
                    <div class="stripe-field">
                        <div id="card-expiry-element" class="stripe-input-box"></div>
                    </div>
                    <div class="stripe-field">
                        <div id="card-cvc-element" class="stripe-input-box"></div>
                    </div>
                </div>
                <div id="card-errors" class="stripe-error"></div>
                <button id="confirmCardBtn" class="confirm-btn-stripe"><i class="fas fa-lock"></i> <span id="btn-text">PAY</span></button>
            </div>
        </div>
    </div>

    

    <script>
        // --- GLOBAL VARIABLES ---
        let cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
        let orderData = {};
        let currentCoupon = null; // Holds currently applied coupon object

        const SHIPPING_FEE = 0.00;
        const TAX_RATE = 0.00;

        // --- STRIPE CONFIG ---
        const stripe = Stripe('pk_test_51SMibVDPmEZPpjiS8RmOfDeEMNbtRDYIFNeZ7bSeWJldyXK1EKkcyzAIbkBtPIwScSAftwKgXQ3FYY8xDklsgttc00h0YC4LRX');
        const elements = stripe.elements();
        const style = {
            base: {
                color: '#32325d',
                fontFamily: '"Hind", sans-serif',
                fontSize: '16px',
                '::placeholder': {
                    color: '#aab7c4'
                }
            },
            invalid: {
                color: '#991b1b',
                iconColor: '#991b1b'
            }
        };
        const cardNumber = elements.create('cardNumber', {
            style: style,
            showIcon: true
        });
        cardNumber.mount('#card-number-element');
        const cardExpiry = elements.create('cardExpiry', {
            style: style
        });
        cardExpiry.mount('#card-expiry-element');
        const cardCvc = elements.create('cardCvc', {
            style: style
        });
        cardCvc.mount('#card-cvc-element');

        document.addEventListener('DOMContentLoaded', function() {
            loadCartData();
            setupPaymentMethodSelection();
            validateForm();
            setupModalListeners();
        });

        // --- CORE CART FUNCTIONS ---
        function loadCartData() {
            const orderItemsContainer = document.getElementById('orderItems');
            const selectedItems = cart.filter(item => item.selected && !item.removed);

            if (selectedItems.length === 0) {
                orderItemsContainer.innerHTML = `<div style="text-align:center; padding:20px; color:#6b7280;"><p>No items selected.</p><a href="cart.php" style="color:#d97719; font-weight:600;">Back to Cart</a></div>`;
                document.getElementById('placeOrderBtn').disabled = true;
                return;
            }

            let itemsHtml = '';
            let subtotal = 0;

            selectedItems.forEach(item => {
                const itemTotal = item.price * item.qty;
                subtotal += itemTotal;
                itemsHtml += `<div class="cart-item-preview"><img src="${item.img}" onerror="this.src='https://via.placeholder.com/50'"><div class="cart-item-details"><h4>${item.name}</h4><p>${item.qty} × ₱${item.price.toFixed(2)}</p></div><div style="margin-left:auto; font-weight:600; font-size:0.95rem;">₱${itemTotal.toFixed(2)}</div></div>`;
            });

            orderItemsContainer.innerHTML = itemsHtml;

            // Calculations with Manual Coupon
            let discount = 0;
            if (currentCoupon) {
                const amount = parseFloat(currentCoupon.amount);
                if (currentCoupon.discount_type === 'percent') {
                    discount = subtotal * (amount / 100);
                } else {
                    discount = amount;
                }
                discount = Math.min(discount, subtotal);
            }

            const final_subtotal = subtotal - discount;
            const total = final_subtotal + SHIPPING_FEE + (final_subtotal * TAX_RATE);

            // Update UI
            document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
            document.getElementById('shipping').textContent = `₱${SHIPPING_FEE.toFixed(2)}`;
            document.getElementById('tax').textContent = `₱${(final_subtotal * TAX_RATE).toFixed(2)}`;
            document.getElementById('total').textContent = `₱${total.toFixed(2)}`;

            // Update Coupon UI
            const couponRow = document.getElementById('couponRow');
            if (currentCoupon) {
                couponRow.style.display = 'flex';
                document.getElementById('couponCode').textContent = currentCoupon.code;
                document.getElementById('discountValue').textContent = `- ₱${discount.toFixed(2)}`;
                document.getElementById('coupon_id').value = currentCoupon.promo_id;
            } else {
                couponRow.style.display = 'none';
                document.getElementById('coupon_id').value = 0;
            }

            // Save Data
            orderData = {
                items: selectedItems,
                subtotal: subtotal,
                shipping: SHIPPING_FEE,
                total: total,
                discount: discount
            };
            document.getElementById('cart_data').value = JSON.stringify(orderData);
        }

        // --- MANUAL COUPON LOGIC ---
        function applyCoupon() {
            const code = document.getElementById('coupon_code_input').value.trim();
            const msgDiv = document.getElementById('couponMessage');

            if (!code) {
                msgDiv.textContent = "Please enter a code.";
                msgDiv.className = "coupon-message error";
                msgDiv.style.display = 'block';
                return;
            }

            // AJAX Call
            fetch('validate_coupon.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'code=' + encodeURIComponent(code)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        currentCoupon = data.coupon;
                        msgDiv.textContent = "Coupon applied successfully!";
                        msgDiv.className = "coupon-message success";
                        loadCartData(); // Recalculate totals
                    } else {
                        currentCoupon = null;
                        msgDiv.textContent = data.message;
                        msgDiv.className = "coupon-message error";
                        loadCartData();
                    }
                    msgDiv.style.display = 'block';
                })
                .catch(err => {
                    console.error(err);
                    msgDiv.textContent = "Error validating coupon.";
                    msgDiv.style.display = 'block';
                });
        }

        function removeCoupon() {
            currentCoupon = null;
            document.getElementById('coupon_code_input').value = '';
            document.getElementById('couponMessage').style.display = 'none';
            loadCartData();
        }

        function clearLocalCartAfterSuccess() {
            let remainingCart = cart.filter(item => !item.selected);
            localStorage.setItem('scholarSpotCart', JSON.stringify(remainingCart));
            loadCartData();
            const btn = document.getElementById('placeOrderBtn');
            if (btn) {
                btn.disabled = true;
                btn.textContent = "Order Placed";
            }
        }

        // --- PAYMENT & FORM LOGIC ---
        function setupPaymentMethodSelection() {
            const paymentOptions = document.querySelectorAll('.payment-option');
            paymentOptions.forEach(option => {
                option.addEventListener('click', function() {
                    paymentOptions.forEach(opt => opt.classList.remove('selected'));
                    this.classList.add('selected');
                    const radio = this.querySelector('input[type="radio"]');
                    radio.checked = true;
                    if (radio.value === 'Credit Card') {
                        showStripeModal();
                    }
                    validateForm();
                });
            });
        }

        function validateForm() {
            const form = document.getElementById('checkoutForm');
            const requiredFields = form.querySelectorAll('[required]');
            const placeOrderBtn = document.getElementById('placeOrderBtn');
            const selectedItems = cart.filter(item => item.selected && !item.removed);
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) isValid = false;
            });
            if (!form.querySelector('input[name="payment_method"]:checked')) isValid = false;

            placeOrderBtn.disabled = !isValid || selectedItems.length === 0;
        }

        document.getElementById('checkoutForm').addEventListener('change', validateForm);
        document.getElementById('checkoutForm').addEventListener('input', validateForm);

        // --- MODAL LOGIC ---
        function showConfirmationModal() {
            const form = document.getElementById('checkoutForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const shipping = `${formData.get('shipping_address')}, ${formData.get('city')}, ${formData.get('postal_code')}`;

            let html = `
            <p><strong>Name:</strong> ${formData.get('name')}</p>
            <p><strong>Address:</strong> ${shipping}</p>
            <p><strong>Payment:</strong> ${formData.get('payment_method')}</p>
            <hr style="margin:10px 0; border:0; border-top:1px dashed #ccc;">
            <p style="display:flex; justify-content:space-between; font-weight:bold; font-size:1.1rem; color:#d97719;">
                <span>Total:</span> <span>₱${orderData.total.toFixed(2)}</span>
            </p>
        `;
            document.getElementById('modalOrderSummary').innerHTML = html;
            document.getElementById('confirmationModal').style.display = 'flex';
        }

        function hideConfirmationModal() {
            document.getElementById('confirmationModal').style.display = 'none';
        }

        document.getElementById('confirmOrderBtn').addEventListener('click', function() {
            this.textContent = 'Processing...';
            this.disabled = true;
            document.getElementById('checkoutForm').submit();
        });

        // --- STRIPE LOGIC ---
        function showStripeModal() {
            document.getElementById('stripeModal').style.display = 'flex';
        }

        function closeStripeModal(success) {
            document.getElementById('stripeModal').style.display = 'none';
            if (!success) {
                document.querySelector('input[value="Credit Card"]').checked = false;
                document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
                validateForm();
            }
        }

        document.getElementById('confirmCardBtn').addEventListener('click', async function(e) {
            e.preventDefault();
            const name = document.getElementById('stripe-name').value;
            if (!name) {
                document.getElementById('card-errors').textContent = "Enter name";
                document.getElementById('card-errors').style.display = 'block';
                return;
            }

            const {
                token,
                error
            } = await stripe.createToken(cardNumber, {
                name: name
            });
            if (error) {
                document.getElementById('card-errors').textContent = error.message;
                document.getElementById('card-errors').style.display = 'block';
            } else {
                document.getElementById('stripeToken').value = token.id;
                closeStripeModal(true);
            }
        });

        // --- EVENT LISTENERS ---
        function setupModalListeners() {
            document.getElementById('closeModalBtn').addEventListener('click', hideConfirmationModal);
            document.getElementById('cancelOrderBtn').addEventListener('click', hideConfirmationModal);
            document.getElementById('closeStripeBtn').addEventListener('click', () => closeStripeModal(false));
            document.getElementById('placeOrderBtn').addEventListener('click', showConfirmationModal);
        }

        // Phone formatting
        document.getElementById('customer_phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 12) value = value.substring(0, 12);
            if (value.length > 0) {
                if (value.startsWith('63')) e.target.value = '+' + value;
                else if (value.startsWith('0')) e.target.value = '+63' + value.substring(1);
                else e.target.value = '+63' + value;
            }
        });
    </script>
</body>

</html>