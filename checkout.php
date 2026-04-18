<?php
/**
 * Checkout Page
 * Requires user to be logged in
 */
require_once 'config.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

// Fetch user data to pre-fill the form
$stmt = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | Blackwell Co.</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>

<nav>
    <div class="logo"><strong><a href="index.html">BLACKWELL CO.</a></strong></div>
    <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="shop.html">Shop</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="logout.php" class="logout-link">Logout</a></li>
    </ul>
</nav>

<div class="checkout-container">
    <h2>Secure Checkout</h2>
    
    <div class="order-summary" id="order-summary-container">
        <!-- populated by JS -->
    </div>
    
    <form id="checkout-form">
        <h3 style="margin-bottom:15px; font-weight:500;">Shipping details</h3>
        
        <div class="form-group">
            <label>Full Name</label>
            <input type="text" required value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Jon Doe">
        </div>
        
        <div class="checkout-grid">
            <div class="form-group">
                <label>Email</label>
                <input type="email" required value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="jon@example.com">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" required placeholder="+91 ">
            </div>
        </div>

        <div class="form-group">
            <label>Shipping Address</label>
            <input type="text" required placeholder="123 Main St">
        </div>
        
        <h3 style="margin:25px 0 15px; font-weight:500;">Payment Details</h3>
        
        <div class="form-group">
            <label>Card Number</label>
            <input type="text" required placeholder="0000 0000 0000 0000" pattern="[0-9 ]+">
        </div>
        <div class="checkout-grid">
            <div class="form-group">
                <label>Expiry Date</label>
                <input type="text" required placeholder="MM/YY">
            </div>
            <div class="form-group">
                <label>CVV</label>
                <input type="text" required placeholder="123" pattern="[0-9]{3,4}">
            </div>
        </div>

        <button type="submit" class="shop-btn" style="width:100%; border:none; margin-top:10px; cursor:pointer;" id="place-order-btn">
            Place Order
        </button>
    </form>
</div>

<div class="success-modal" id="success-modal">
    <div class="success-icon">✓</div>
    <h2 style="font-family:'Playfair Display',serif; font-size:2rem; margin-bottom:10px;">Order Confirmed!</h2>
    <p style="color:#555; margin-bottom: 25px;">Thank you for shopping with Blackwell Co.<br>Your premium accessories are on the way.</p>
    <a href="index.html" class="shop-btn" onclick="clearCart()">Return to Home</a>
</div>

<!-- Scripts -->
<script src="js/data.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
    // We intentionally do not load cart.js to avoid the sidebar, 
    // but we can parse the cart locally for checkout.
    const cart = JSON.parse(localStorage.getItem('bw_cart')) || [];
    const summaryContainer = document.getElementById("order-summary-container");
    const placeOrderBtn = document.getElementById("place-order-btn");
    
    if(cart.length === 0) {
        summaryContainer.innerHTML = "<p>Your cart is empty. <a href='shop.html'>Go back to shop</a>.</p>";
        placeOrderBtn.style.display = 'none';
        return;
    }
    
    let html = '';
    let total = 0;
    
    cart.forEach(item => {
        const itemTotal = item.price * item.quantity;
        total += itemTotal;
        html += `
            <div class="order-summary-row">
                <span>${item.name} x ${item.quantity}</span>
                <span>₹${itemTotal.toLocaleString('en-IN')}</span>
            </div>
        `;
    });
    
    html += `
        <div class="order-summary-total">
            <span>Total to pay</span>
            <span>₹${total.toLocaleString('en-IN')}</span>
        </div>
    `;
    
    summaryContainer.innerHTML = html;
    
    // Hand Checkout Form Submit
    document.getElementById("checkout-form").addEventListener("submit", (e) => {
        e.preventDefault();
        
        // Show success modal
        const modal = document.getElementById("success-modal");
        
        // Darken background
        const overlay = document.createElement("div");
        overlay.style.position = "fixed";
        overlay.style.top = "0"; overlay.style.left = "0";
        overlay.style.width = "100%"; overlay.style.height = "100%";
        overlay.style.background = "rgba(0,0,0,0.5)";
        overlay.style.zIndex = "1999";
        document.body.appendChild(overlay);
        
        modal.classList.add("active");
    });
});

function clearCart() {
    localStorage.removeItem('bw_cart');
}
</script>

</body>
</html>
