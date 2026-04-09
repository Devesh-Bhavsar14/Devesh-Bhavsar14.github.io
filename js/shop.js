document.addEventListener("DOMContentLoaded", () => {
    const grid = document.getElementById("shop-grid");
    const searchInput = document.getElementById("search-input");
    const filterBtns = document.querySelectorAll(".filter-btn");

    // Check for category in URL
    const urlParams = new URLSearchParams(window.location.search);
    let currentCategory = urlParams.get('category') || 'all';
    let searchQuery = '';

    // Set initial active button
    filterBtns.forEach(btn => {
        if (btn.dataset.category === currentCategory) {
            btn.classList.add("active");
        } else {
            btn.classList.remove("active");
        }
    });

    // Render products
    function renderProducts() {
        if (!grid) return;
        grid.innerHTML = '';

        const filteredProducts = products.filter(p => {
            const matchCategory = currentCategory === 'all' || p.category === currentCategory;
            const matchSearch = p.name.toLowerCase().includes(searchQuery.toLowerCase());
            return matchCategory && matchSearch;
        });

        if (filteredProducts.length === 0) {
            grid.innerHTML = '<div style="grid-column: 1 / -1; font-size:1.2rem; color:#777; margin-top:40px;">No products found matching your search.</div>';
            return;
        }

        filteredProducts.forEach((product, index) => {
            // Adding a slight delay to animation for staggered effect
            const delay = index * 0.1;

            const card = document.createElement("div");
            card.className = "product-card";
            card.style.animationDelay = `${delay}s`;
            
            card.innerHTML = `
                <img src="${product.image}" alt="${product.name}">
                <div class="product-info">
                    <div>
                        <div class="product-name">${product.name}</div>
                        <div class="product-price">₹${product.price.toLocaleString('en-IN')}</div>
                    </div>
                    <button class="add-to-cart-btn" onclick="addToCart(${product.id})">Add to Cart</button>
                </div>
            `;
            grid.appendChild(card);
        });
    }

    // Event Listeners
    if (searchInput) {
        searchInput.addEventListener("input", (e) => {
            searchQuery = e.target.value;
            renderProducts();
        });
    }

    filterBtns.forEach(btn => {
        btn.addEventListener("click", (e) => {
            // Update active states
            filterBtns.forEach(b => b.classList.remove("active"));
            e.target.classList.add("active");

            currentCategory = e.target.dataset.category;
            
            // Update URL gently without reload
            const newUrl = currentCategory === 'all' 
                ? window.location.pathname 
                : `${window.location.pathname}?category=${currentCategory}`;
            window.history.pushState({path:newUrl}, '', newUrl);

            renderProducts();
            
            // scroll slightly down if grid is out of view
            const scrollPos = grid.getBoundingClientRect().top + window.scrollY - 150;
            if (window.scrollY > scrollPos + 400 || window.scrollY < scrollPos - 400) {
                 window.scrollTo({top: scrollPos, behavior: 'smooth'});
            }
        });
    });

    // Initial render
    renderProducts();
});
