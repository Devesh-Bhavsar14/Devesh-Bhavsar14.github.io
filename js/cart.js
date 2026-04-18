// Initialize Cart from Session/LocalStorage
let cart = JSON.parse(localStorage.getItem('bw_cart')) || [];

// Inject Cart HTML if not exists
document.addEventListener("DOMContentLoaded", () => {
    if (!document.getElementById("cart-overlay")) {
        const cartHTML = `
            <div class="cart-overlay" id="cart-overlay"></div>
            <div class="cart-sidebar" id="cart-sidebar">
                <div class="cart-header">
                    <h2>Your Cart</h2>
                    <button class="close-cart" id="close-cart">&times;</button>
                </div>
                <div class="cart-items" id="cart-items-container">
                    <!-- Cart items will be injected here -->
                </div>
                <div class="cart-footer">
                    <div class="cart-total">
                        <span>Total:</span>
                        <span id="cart-total-price">₹0</span>
                    </div>
                    <a href="checkout.php" class="checkout-btn">Proceed to Checkout</a>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', cartHTML);
    }

    // Attach event listeners for cart toggling
    const cartIcon = document.getElementById("cart-icon");
    if(cartIcon) {
        cartIcon.addEventListener("click", (e) => {
            e.preventDefault();
            toggleCart();
        });
    }

    document.getElementById("close-cart")?.addEventListener("click", toggleCart);
    document.getElementById("cart-overlay")?.addEventListener("click", toggleCart);

    // Initial Render
    updateCartUI();
});

// Toggle Sidebar
function toggleCart() {
    document.getElementById("cart-sidebar").classList.toggle("active");
    document.getElementById("cart-overlay").classList.toggle("active");
}

// Add Item
function addToCart(productId) {
    const product = products.find(p => p.id === productId);
    if (!product) return;

    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({ ...product, quantity: 1 });
    }

    saveCart();
    updateCartUI();
    
    // Open cart automatically when item added
    if (!document.getElementById("cart-sidebar").classList.contains("active")) {
        toggleCart();
    }
}

// Update Quantity
function changeQuantity(productId, change) {
    const item = cart.find(i => i.id === productId);
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(productId);
        } else {
            saveCart();
            updateCartUI();
        }
    }
}

// Remove from Cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCart();
    updateCartUI();
}

// Save & Sync
function saveCart() {
    localStorage.setItem('bw_cart', JSON.stringify(cart));
}

// Update Cart UI (Pill, Sidebar Items, Total)
function updateCartUI() {
    // 1. Update pill count
    const cartCountEl = document.getElementById("cart-count");
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (cartCountEl) {
        cartCountEl.innerText = totalItems;
        cartCountEl.style.display = totalItems > 0 ? "flex" : "none";
    }

    // 2. Update sidebar content
    const container = document.getElementById("cart-items-container");
    if(!container) return;

    if (cart.length === 0) {
        container.innerHTML = `<div class="cart-empty-msg">Your cart is currently empty.</div>`;
    } else {
        container.innerHTML = cart.map(item => `
            <div class="cart-item">
                <img src="${item.image}" alt="${item.name}">
                <div class="cart-item-info">
                    <div class="cart-item-title">${item.name}</div>
                    <div class="cart-item-price">₹${item.price}</div>
                    <div class="cart-item-controls">
                        <button class="qty-btn" onclick="changeQuantity(${item.id}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button class="qty-btn" onclick="changeQuantity(${item.id}, 1)">+</button>
                        <button class="remove-btn" onclick="removeFromCart(${item.id})">Remove</button>
                    </div>
                </div>
            </div>
        `).join("");
    }

    // 3. Update total price
    const totalPrice = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const totalEl = document.getElementById("cart-total-price");
    if (totalEl) {
        // format nicely
        totalEl.innerText = `₹${totalPrice.toLocaleString('en-IN')}`;
    }

    // 4. Update checkout button state
    const checkoutBtn = document.querySelector(".checkout-btn");
    if(checkoutBtn) {
        if(cart.length === 0) {
            checkoutBtn.style.opacity = '0.5';
            checkoutBtn.style.pointerEvents = 'none';
        } else {
            checkoutBtn.style.opacity = '1';
            checkoutBtn.style.pointerEvents = 'auto';
        }
    }
}

// Ensure globally accessible
window.addToCart = addToCart;
window.changeQuantity = changeQuantity;
window.removeFromCart = removeFromCart;
window.toggleCart = toggleCart;
