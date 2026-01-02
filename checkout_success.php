<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['User_ID'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['order_id'] ?? '';
$userName = $_SESSION['Name'] ?? 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Order Confirmed - ScholarSpot</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800&family=Hind:wght@400;500&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/style.css">
  <style>
    body.success-page {
  font-family: 'Hind', sans-serif;
  background-color: #fef7ea; /* Light - Cararra */
  margin: 0;
  padding: 0;
  color: #23202a; /* Dark - Baltic Sea */
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

.success-container {
  background: white;
  border-radius: 16px;
  padding: 40px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.1);
  text-align: center;
  max-width: 600px;
  width: 90%;
}

.success-icon {
  font-size: 80px;
  color: #0e6d47; /* Optional Accent - Dark Green */
  margin-bottom: 20px;
  animation: bounceIn 0.6s;
}

@keyframes bounceIn {
  0% { transform: scale(0); }
  50% { transform: scale(1.2); }
  100% { transform: scale(1); }
}

.success-title {
  font-family: 'Montserrat', sans-serif;
  font-weight: 800;
  font-size: 2.5rem;
  color: #937a6b; /* Primary - Almond Frost */
  margin-bottom: 15px;
}

.success-message {
  font-size: 1.1rem;
  color: #808986; /* Light Accent - Gunsmoke */
  margin-bottom: 30px;
  line-height: 1.6;
}

.order-details {
  background: #fef7ea; /* Light - Cararra */
  border-radius: 12px;
  padding: 25px;
  margin: 30px 0;
  text-align: left;
  border: 1px solid #f7e9cc;
}

.order-details h3 {
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  color: #937a6b; /* Primary - Almond Frost */
  margin-bottom: 15px;
  text-align: center;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  margin: 10px 0;
  padding: 8px 0;
  border-bottom: 1px solid #f7e9cc;
}

.detail-row:last-child {
  border-bottom: none;
  font-weight: 600;
  font-size: 1.1rem;
  color: #23202a; /* Dark - Baltic Sea */
}

.detail-label {
  font-weight: 600;
  color: #808986; /* Light Accent - Gunsmoke */
  font-family: 'Hind Guntur', sans-serif;
}

.detail-value {
  color: #23202a; /* Dark - Baltic Sea */
  font-family: 'Hind', sans-serif;
}

.action-buttons {
  display: flex;
  gap: 15px;
  justify-content: center;
  margin-top: 30px;
  flex-wrap: wrap;
}

.btn {
  padding: 12px 24px;
  border: none;
  border-radius: 8px;
  font-family: 'Hind Guntur', sans-serif;
  font-weight: 600;
  font-size: 16px;
  cursor: pointer;
  text-decoration: none;
  display: inline-block;
  transition: all 0.3s;
}

.btn-primary {
  background: #937a6b; /* Primary - Almond Frost */
  color: white;
}

.btn-primary:hover {
  background: #836e6e; /* Dark Accent - Spicy Pink */
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(147, 122, 107, 0.3);
}

.btn-secondary {
  background: transparent;
  color: #937a6b; /* Primary - Almond Frost */
  border: 2px solid #937a6b; /* Primary - Almond Frost */
}

.btn-secondary:hover {
  background: #937a6b; /* Primary - Almond Frost */
  color: white;
  transform: translateY(-2px);
}

.next-steps {
  background: #f0f7f4; /* Light green background */
  border-radius: 12px;
  padding: 20px;
  margin: 30px 0;
  text-align: left;
  border: 1px solid #e0f0e8;
}

.next-steps h4 {
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  color: #0e6d47; /* Optional Accent - Dark Green */
  margin-bottom: 15px;
}

.next-steps ul {
  margin: 0;
  padding-left: 20px;
}

.next-steps li {
  margin: 8px 0;
  color: #0e6d47; /* Optional Accent - Dark Green */
  font-family: 'Hind', sans-serif;
}

/* Additional success elements */
.order-number {
  background: #937a6b; /* Primary - Almond Frost */
  color: white;
  padding: 8px 16px;
  border-radius: 20px;
  font-family: 'Montserrat', sans-serif;
  font-weight: 600;
  display: inline-block;
  margin: 10px 0;
}

.delivery-time {
  background: #0e6d47; /* Optional Accent - Dark Green */
  color: white;
  padding: 6px 12px;
  border-radius: 6px;
  font-family: 'Hind Guntur', sans-serif;
  font-weight: 600;
  display: inline-block;
  margin: 5px 0;
}

.confirmation-email {
  color: #937a6b; /* Primary - Almond Frost */
  font-weight: 600;
  margin: 15px 0;
}

@media (max-width: 768px) {
  .action-buttons {
    flex-direction: column;
    align-items: center;
  }

  .btn {
    width: 100%;
    max-width: 300px;
  }

  .detail-row {
    flex-direction: column;
    gap: 5px;
  }

  .success-container {
    padding: 30px 20px;
  }

  .success-title {
    font-size: 2rem;
  }
}

/* Print styles for order confirmation */
@media print {
  body.success-page {
    background: white;
  }
  
  .success-container {
    box-shadow: none;
    max-width: none;
  }
  
  .action-buttons {
    display: none;
  }
}
  </style>
</head>
<body class="success-page">
  <div class="success-container">
    <div class="success-icon">✅</div>
    <h1 class="success-title">Order Confirmed!</h1>
    <p class="success-message">
      Thank you for your order, <?= htmlspecialchars($userName) ?>! Your order has been successfully placed and is being processed.
    </p>

    <div class="order-details">
      <h3>Order Details</h3>
      <div class="detail-row">
        <span class="detail-label">Order ID:</span>
        <span class="detail-value">#<?= htmlspecialchars($order_id) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Order Date:</span>
        <span class="detail-value"><?= date('F j, Y \a\t g:i A') ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Status:</span>
        <span class="detail-value" style="color: #f59e0b; font-weight: 600;">Pending</span>
      </div>
      <div class="detail-row">
        <span class="detail-label">Estimated Delivery:</span>
        <span class="detail-value">3-5 business days</span>
      </div>
    </div>

    <div class="next-steps">
      <h4>What happens next?</h4>
      <ul>
        <li>Your order is being prepared for shipment</li>
        <li>You'll receive updates as your order status changes</li>
        <li>Track your order anytime in "My Orders"</li>
        <li>Contact us if you have any questions</li>
      </ul>
    </div>

    <div class="action-buttons">
      <a href="dashboard.php" class="btn btn-primary">Continue Shopping</a>
      <a href="orderdetails.php" class="btn btn-secondary">View My Orders</a>
    </div>
  </div>

  <script>
    // Clear cart from localStorage
    localStorage.removeItem('scholarSpotCart');
    
    // Show success animation
    document.addEventListener('DOMContentLoaded', function() {
      console.log('Order completed successfully!');
    });
  </script>
</body>
</html>