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
$userRole = $_SESSION['Role'] ?? 'customer';

// 1. Fetch data for "Browse by Category" (Keep this to count all items)
try {
  $stmt = $pdo->query("SELECT p.Category_ID, c.Category_Name, COUNT(p.Product_ID) as item_count 
                         FROM product p 
                         LEFT JOIN category c ON p.Category_ID = c.Category_ID 
                         WHERE p.Availability = 'In Stock'
                         GROUP BY p.Category_ID");
  $category_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Returns [Category Name => Count]
} catch (PDOException $e) {
  $category_counts = [];
}

// 2. Fetch "Best Sellers" (Top 8 items based on sales quantity)
try {
  // This query joins order_details to count actual sales. 
  // If no sales exist, it defaults to 0 and sorts by Product ID.
  $sql = "SELECT p.*, c.Category_Name, COALESCE(SUM(od.Quantity), 0) as total_sold
            FROM product p 
            LEFT JOIN category c ON p.Category_ID = c.Category_ID 
            LEFT JOIN order_details od ON p.Product_ID = od.Product_ID
            WHERE p.Availability = 'In Stock'
            GROUP BY p.Product_ID
            ORDER BY total_sold DESC, p.Product_ID ASC
            LIMIT 8"; // Limit to 8 items to keep the dashboard clean

  $stmt = $pdo->query($sql);
  $best_sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log("Error fetching best sellers: " . $e->getMessage());
  $best_sellers = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>ScholarSpot Marketplace</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800&family=Hind:wght@400;500&family=Hind+Guntur:wght@400;600&display=swap" rel="stylesheet">

  <link rel="stylesheet" href="css/styles.css" />
  <style>
    /* Minor overrides for Dashboard specific layout */
    .view-all-container {
      text-align: center;
      margin-top: 40px;
    }

    .btn-view-all {
      display: inline-block;
      padding: 12px 30px;
      border: 2px solid #d97719;
      color: #d97719;
      text-decoration: none;
      border-radius: 50px;
      font-weight: 700;
      transition: all 0.3s;
    }

    .btn-view-all:hover {
      background-color: #d97719;
      color: white;
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
        <li><a href="dashboard.php" class="active">Home</a></li>
        <li><a href="products.php">Products</a></li>
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

  <section class="hero-section">
    <div class="hero-container">
      <div class="hero-content">
        <h1 class="hero-title">The Pride of ScholarSpot Marketplace</h1>
        <p class="hero-description">
          Your one-stop shop for academic supplies, tech gadgets, and student essentials.
          Quality products at unbeatable prices, delivered right to your dorm.
        </p>
        <div class="hero-buttons">
          <button class="btn-primary" onclick="window.location.href='products.php'">Order Now</button>
          <button class="btn-secondary" onclick="scrollToSection('categories')">Explore Categories</button>
        </div>
      </div>
      <div class="hero-image">
        <img src="https://images.unsplash.com/photo-1456735190827-d1262f71b8a3?w=600&h=400&fit=crop" alt="Student Supplies">
      </div>
    </div>
  </section>

  <section class="products-section" id="products">
    <div class="section-container">
      <div class="section-header">
        <h2 class="section-title">Best Sellers</h2>
      </div>

      <div class="products-grid" id="productsGrid">

        <?php if (empty($best_sellers)): ?>
          <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
            <div style="font-size: 3rem; margin-bottom: 1rem;">📦</div>
            <h3>No Products Available</h3>
            <p>Check back soon for new products!</p>
          </div>
        <?php else: ?>
          <?php foreach ($best_sellers as $product): ?>
            <div class="product-card">
              <div class="product-image-wrapper" onclick="window.location.href='products.php'">
                <img src="<?= htmlspecialchars($product['Images']) ?>"
                  alt="<?= htmlspecialchars($product['Name']) ?>"
                  onerror="this.src='https://via.placeholder.com/400x300?text=No+Image'">
              </div>
              <div class="product-info">
                <h3 class="product-name" onclick="window.location.href='products.php'"><?= htmlspecialchars($product['Name']) ?></h3>
                <p class="product-price">₱<?= number_format($product['Price'], 2) ?>
                  <span class="price-unit">/ <?= $product['Category_Name'] === 'Tech Gadgets' ? 'pc' : 'pack' ?></span>
                </p>
                <button class="btn-add-cart" onclick="quickAddToCart(<?= htmlspecialchars(json_encode([
                                                                        'id' => $product['Product_ID'],
                                                                        'name' => $product['Name'],
                                                                        'price' => floatval($product['Price']),
                                                                        'img' => $product['Images'],
                                                                        'category' => $product['Category_Name'],
                                                                        'stock' => intval($product['Stock'])
                                                                      ])) ?>)">
                  Add to Cart
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>

      <div class="view-all-container">
        <a href="products.php" class="btn-view-all">View All Products &rarr;</a>
      </div>

    </div>
  </section>

  <footer class="marketplace-footer">
    <p>&copy; 2024 ScholarSpot. All rights reserved.</p>
  </footer>

  <script>
    // Initialize Cart Count
    document.addEventListener('DOMContentLoaded', function() {
      updateCartCount();
    });

    function updateCartCount() {
      const cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
      const count = cart.filter(item => !item.removed).length;
      document.getElementById('cart-count').textContent = count;
    }

    function scrollToSection(sectionId) {
      const section = document.getElementById(sectionId);
      if (section) {
        section.scrollIntoView({
          behavior: 'smooth'
        });
      }
    }

    // Quick Add to Cart (Simple version for Dashboard)
    function quickAddToCart(product) {
      if (!product) return;

      let cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
      const existingItem = cart.find(item => item.id === product.id && !item.removed);

      // Stock Check Logic
      const currentQty = existingItem ? existingItem.qty : 0;
      if (currentQty + 1 > product.stock) {
        alert(`⚠️ Cannot add more. Stock limit reached (${product.stock}).`);
        return;
      }

      if (existingItem) {
        existingItem.qty += 1;
        existingItem.removed = false;
      } else {
        cart.push({
          id: product.id,
          name: product.name,
          price: product.price,
          qty: 1,
          img: product.img,
          category: product.category,
          max_stock: product.stock,
          removed: false
        });
      }

      localStorage.setItem('scholarSpotCart', JSON.stringify(cart));
      updateCartCount();

      // Simple Toast
      const msg = document.createElement('div');
      msg.style.cssText = `position: fixed; top: 20px; right: 20px; background: #10b981; color: white; padding: 12px 24px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 10000; font-family: 'Hind Guntur', sans-serif; font-weight: 600;`;
      msg.textContent = '✅ ' + product.name + ' added to cart!';
      document.body.appendChild(msg);
      setTimeout(() => msg.remove(), 3000);
    }
  </script>

</body>

</html>