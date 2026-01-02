// Product data - using numeric IDs that match database
const products = {
  1: {
    name: 'Fluorescent Highlighters',
    price: 6.99,
    category: 'academic',
    image: 'https://scontent.fceb6-1.fna.fbcdn.net/v/t1.15752-9/495074449_707677128304149_2974533695102338437_n.jpg?_nc_cat=104&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeFyZ7Ov3FFdnJVU0TNlBzksJoblMFFSbYMmhuUwUVJtg0iS1DIFF7fwBQuALUP4asSAYgdlexg-uzF0nthW4kkO&_nc_ohc=-eVZGicXoZgQ7kNvwF_l8SO&_nc_oc=Adkz63q2LfiGbL1ncxi_S-3oKe4pUe0O2oD-Ov5-BYNdJy40GgArkNlVpuzaeqVwjy8&_nc_zt=23&_nc_ht=scontent.fceb6-1.fna&oh=03_Q7cD3QGBNkT6xHS4JmlE12VRhLi1-H92iMGTERA_IQ89Tvnu5g&oe=69041935'
  },
  2: {
    name: 'Premium Pen Set',
    price: 12.50,
    category: 'academic',
    image: 'https://scontent.fceb2-2.fna.fbcdn.net/v/t1.15752-9/552906579_1196489862535886_8199895423158032287_n.jpg?_nc_cat=110&ccb=1-7&_nc_sid=9f807c&_nc_eui2=AeE4LPfhsdn0nU4KNxZl_G7QWbk36__56htZuTfr__nqG0hYpEBsS7XRBVrSs8Z2IRCTr7me4C2M6gyBMmx_KYn1&_nc_ohc=0Aw8Yq1cx9YQ7kNvwGBhN37&_nc_oc=AdlsnFppWiRi8KS3a_H5dCj_T8qv24_vnZWRqfXMYchHR36R9nYcq-UQxPAZb7Ucqdc&_nc_zt=23&_nc_ht=scontent.fceb2-2.fna&oh=03_Q7cD3QFgi0RB6iT4XQK-sSDluqZWJNXt4wV2vyAkYRoBcNyTuQ&oe=69040904'
  },
  3: {
    name: 'Scientific Calculator',
    price: 25.99,
    category: 'academic',
    image: 'https://i.pinimg.com/1200x/61/ca/e7/61cae79087ca77bbe785463d59f46cab.jpg'
  },
  4: {
    name: 'Student Laptop',
    price: 799.00,
    category: 'tech',
    image: 'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?w=400&h=300&fit=crop'
  },
  5: {
    name: 'Noise-Cancelling Headphones',
    price: 129.99,
    category: 'tech',
    image: 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=400&h=300&fit=crop'
  },
  6: {
    name: 'Laptop Backpack',
    price: 55.00,
    category: 'personal',
    image: 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=400&h=300&fit=crop'
  }
};

let currentProductId = null;

// Initialize
document.addEventListener('DOMContentLoaded', function() {
  updateCartCount();
});

// Update cart count
function updateCartCount() {
  const cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
  const count = cart.filter(item => !item.removed).length;
  document.getElementById('cart-count').textContent = count;
}

// Filter products
function filterProducts(category) {
  const allProducts = document.querySelectorAll('.product-card');
  const filterButtons = document.querySelectorAll('.filter-btn');
  
  // Update active button
  filterButtons.forEach(btn => btn.classList.remove('active'));
  event.target.classList.add('active');
  
  // Show/hide products
  allProducts.forEach(card => {
    if (category === 'all' || card.dataset.category === category) {
      card.style.display = 'block';
    } else {
      card.style.display = 'none';
    }
  });
}

// Scroll to section
function scrollToSection(sectionId) {
  const section = document.getElementById(sectionId);
  if (section) {
    section.scrollIntoView({ behavior: 'smooth' });
  }
}

// Open quantity modal
function openQuantityModal(productId) {
  currentProductId = productId;
  const product = products[productId];
  
  if (!product) {
    alert('Product not found');
    return;
  }
  
  document.getElementById('modal-product-image').src = product.image;
  document.getElementById('modal-product-name').textContent = product.name;
  document.getElementById('modal-product-price').textContent = `₱${product.price.toFixed(2)} / item`;
  document.getElementById('modal-qty-input').value = 1;
  
  updateModalTotal();
  
  document.getElementById('quantityModal').style.display = 'flex';
}

// Close quantity modal
function closeQuantityModal() {
  document.getElementById('quantityModal').style.display = 'none';
  currentProductId = null;
}

// Update modal total
function updateModalTotal() {
  const qty = parseInt(document.getElementById('modal-qty-input').value) || 1;
  const product = products[currentProductId];
  
  if (product) {
    const total = product.price * qty;
    document.getElementById('modal-total-price').textContent = `₱${total.toFixed(2)}`;
  }
}

// Confirm add to cart
function confirmAddToCart() {
  const qty = parseInt(document.getElementById('modal-qty-input').value) || 1;
  const product = products[currentProductId];
  
  if (!product || qty < 1) {
    alert('Invalid quantity');
    return;
  }
  
  // Get existing cart
  let cart = JSON.parse(localStorage.getItem('scholarSpotCart')) || [];
  
  // Check if product already in cart
  const existingIndex = cart.findIndex(item => item.id === currentProductId);
  
  if (existingIndex > -1) {
    cart[existingIndex].qty += qty;
    cart[existingIndex].removed = false;
  } else {
    cart.push({
      id: currentProductId,
      name: product.name,
      price: product.price,
      qty: qty,
      img: product.image,
      category: product.category,
      removed: false
    });
  }
  
  // Save cart
  localStorage.setItem('scholarSpotCart', JSON.stringify(cart));
  
  // Update UI
  updateCartCount();
  closeQuantityModal();
  
  // Show success message
  showMessage(`${product.name} added to cart!`);
}

// Show success message
function showMessage(text) {
  const msg = document.createElement('div');
  msg.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #10b981;
    color: white;
    padding: 12px 24px;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    z-index: 10000;
    font-family: 'Hind Guntur', sans-serif;
    font-weight: 600;
    animation: slideIn 0.3s ease;
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
  @keyframes slideIn {
    from { transform: translateX(400px); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
  }
  @keyframes slideOut {
    from { transform: translateX(0); opacity: 1; }
    to { transform: translateX(400px); opacity: 0; }
  }
`;
document.head.appendChild(style);

// Close modal when clicking outside
document.getElementById('quantityModal').addEventListener('click', function(e) {
  if (e.target === this) {
    closeQuantityModal();
  }
});