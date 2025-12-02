/**
 * Main Application Logic
 * DoughMain - Bread Rescue Application
 * Updated with Image Support and Sale Feature
 * FIXED: Tab switching navigation
 */

let currentUser = null;
let currentCart = [];
let currentFavorites = [];
let allProducts = [];
let selectedDeliveryOption = 'delivery';
let selectedPaymentMethod = 'cod';
let currentView = 'all'; // 'all' or 'sale'

// Initialize app on page load
window.addEventListener('DOMContentLoaded', () => {
    init();
});

async function init() {
    // Check if user is logged in by trying to get profile
    try {
        const result = await UserAPI.getProfile();
        if (result.success) {
            currentUser = result.user;
            showScreen('appScreen');
            await loadAppData();
        }
    } catch (error) {
        // User not logged in, stay on login screen
        console.log('No active session');
    }
}

async function loadAppData() {
    if (!currentUser) return;
    
    updateProfile();
    await loadProducts();
    await loadCategories();
    await loadFavorites();
}

// Authentication Functions
async function login() {
    const email = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    if (!email || !password) {
        showMessage('loginError', 'Please fill in all fields', true);
        return;
    }
    
    showLoading('loginLoading', true);
    enableButton('loginBtn', false);
    
    const result = await AuthAPI.login(email, password);
    
    showLoading('loginLoading', false);
    enableButton('loginBtn', true);
    
    if (result.success) {
        currentUser = result.user;
        showMessage('loginSuccess', result.message, false);
        
        setTimeout(() => {
            showScreen('appScreen');
            loadAppData();
        }, 1000);
    } else {
        showMessage('loginError', result.message, true);
    }
}

async function signup() {
    const name = document.getElementById('signupName').value.trim();
    const email = document.getElementById('signupEmail').value.trim();
    const password = document.getElementById('signupPassword').value;
    const address = document.getElementById('signupAddress').value.trim();
    const contact = document.getElementById('signupContact').value.trim();
    
    if (!name || !email || !password) {
        showMessage('signupError', 'Please fill in all required fields', true);
        return;
    }
    
    showLoading('signupLoading', true);
    enableButton('signupBtn', false);
    
    const result = await AuthAPI.register(name, email, password, address, contact);
    
    showLoading('signupLoading', false);
    enableButton('signupBtn', true);
    
    if (result.success) {
        currentUser = result.user;
        showMessage('signupSuccess', result.message, false);
        
        setTimeout(() => {
            showScreen('appScreen');
            loadAppData();
        }, 1000);
    } else {
        showMessage('signupError', result.message, true);
    }
}

async function logout() {
    if (!confirm('Are you sure you want to sign out?')) {
        return;
    }
    
    const result = await AuthAPI.logout();
    
    if (result.success) {
        currentUser = null;
        currentCart = [];
        currentFavorites = [];
        allProducts = [];
        
        // Clear input fields
        document.getElementById('loginEmail').value = '';
        document.getElementById('loginPassword').value = '';
        
        showScreen('signinScreen');
    }
}

function updateProfile() {
    if (currentUser) {
        document.getElementById('profileName').textContent = currentUser.name;
        document.getElementById('profileEmail').textContent = currentUser.email;
    }
}

// Product Functions
async function loadProducts() {
    showLoading('productsLoading', true);
    
    const result = await ProductAPI.getAll();
    
    showLoading('productsLoading', false);
    
    if (result.success) {
        allProducts = result.products;
        renderProducts(allProducts);
    } else {
        document.getElementById('productList').innerHTML = 
            '<div class="empty-state"><div class="empty-icon">😕</div><div class="empty-text">Failed to load products</div></div>';
    }
}

async function loadSaleProducts() {
    showLoading('productsLoading', true);
    
    const result = await ProductAPI.getSaleProducts();
    
    showLoading('productsLoading', false);
    
    if (result.success) {
        if (result.products.length === 0) {
            document.getElementById('productList').innerHTML = 
                '<div class="empty-state"><div class="empty-icon">🏷️</div><div class="empty-text">No items on sale right now</div></div>';
        } else {
            renderProducts(result.products);
        }
    } else {
        document.getElementById('productList').innerHTML = 
            '<div class="empty-state"><div class="empty-icon">😕</div><div class="empty-text">Failed to load sale products</div></div>';
    }
}

async function loadCategories() {
    const result = await ProductAPI.getCategories();
    
    if (result.success) {
        renderCategories(result.categories);
    }
}

function renderCategories(categories) {
    const container = document.getElementById('categoriesList');
    
    // Add Sale category at the beginning
    let categoriesHtml = `
        <div class="category-chip ${currentView === 'sale' ? 'active sale-chip' : 'sale-chip'}" 
             onclick="showSaleProducts()">
            🏷️ Sale
        </div>
    `;
    
    categoriesHtml += categories.map(cat => `
        <div class="category-chip ${cat.name === 'All' && currentView === 'all' ? 'active' : ''}" 
             onclick="filterByCategory('${cat.name}')">
            ${cat.name}
        </div>
    `).join('');
    
    container.innerHTML = categoriesHtml;
}

async function showSaleProducts() {
    currentView = 'sale';
    
    // Update active category
    document.querySelectorAll('.category-chip').forEach(chip => {
        chip.classList.remove('active');
    });
    document.querySelector('.sale-chip').classList.add('active');
    
    // Update section title
    document.querySelector('.section-title').textContent = 'Sale Items';
    
    await loadSaleProducts();
}

function filterByCategory(categoryName) {
    currentView = 'all';
    
    // Update active category
    document.querySelectorAll('.category-chip').forEach(chip => {
        chip.classList.toggle('active', chip.textContent.trim() === categoryName);
    });
    
    // Update section title
    document.querySelector('.section-title').textContent = categoryName === 'All' ? 'Available Today' : categoryName + ' Bread';
    
    // Filter products
    if (categoryName === 'All') {
        renderProducts(allProducts);
    } else {
        const filtered = allProducts.filter(p => p.category_name === categoryName);
        renderProducts(filtered);
    }
}

function renderProducts(products) {
    const list = document.getElementById('productList');
    
    if (products.length === 0) {
        list.innerHTML = '<div class="empty-state"><div class="empty-icon">🔍</div><div class="empty-text">No products found</div></div>';
        return;
    }
    
    list.innerHTML = products.map(p => {
        const imageUrl = getProductImage(p.image_url);
        const saleTag = p.is_on_sale ? '<span class="sale-tag">SALE</span>' : '';
        
        return `
        <div class="product-item ${p.is_on_sale ? 'on-sale' : ''}">
            <div class="product-image-container">
                <img src="${imageUrl}" alt="${p.name}" class="product-image" onerror="this.src='assets/placeholder-bread.png'">
                ${saleTag}
            </div>
            <div class="product-details">
                <div class="product-header">
                    <div>
                        <div class="product-name">${p.name}</div>
                        <div class="bakery-name">${p.bakery_name}</div>
                    </div>
                    <div class="heart-icon ${isFavorite(p.product_id) ? 'active' : ''}" 
                         onclick="toggleFavorite(${p.product_id})">
                        ${isFavorite(p.product_id) ? '❤️' : '🤍'}
                    </div>
                </div>
                <div class="price-section">
                    <div class="price-info">
                        <span class="current-price">₱${parseFloat(p.discounted_price).toFixed(2)}</span>
                        <span class="original-price">₱${parseFloat(p.original_price).toFixed(2)}</span>
                        <span class="discount-badge">-${p.discount_percentage}%</span>
                    </div>
                    <button class="add-cart-btn" onclick="addToCart(${p.product_id})">+</button>
                </div>
            </div>
        </div>
    `}).join('');
}

async function searchProducts() {
    const query = document.getElementById('searchInput').value.trim();
    
    if (query.length === 0) {
        if (currentView === 'sale') {
            await loadSaleProducts();
        } else {
            renderProducts(allProducts);
        }
        return;
    }
    
    if (query.length < 2) return;
    
    const result = await ProductAPI.search(query);
    
    if (result.success) {
        renderProducts(result.products);
    }
}

// Cart Functions
async function addToCart(productId) {
    const result = await CartAPI.add(productId, 1);
    
    if (result.success) {
        const product = allProducts.find(p => p.product_id === productId);
        alert(`${product.name} added to cart!`);
        
        // Reload cart if on cart tab
        if (document.getElementById('cartTab').classList.contains('active')) {
            await loadCart();
        }
    } else {
        alert(result.message);
    }
}

async function loadCart() {
    showLoading('cartLoading', true);
    
    const result = await CartAPI.get();
    
    showLoading('cartLoading', false);
    
    if (result.success) {
        currentCart = result.cart;
        renderCart(result.cart, result.total);
    } else {
        document.getElementById('cartList').innerHTML = 
            '<div class="empty-state"><div class="empty-icon">🛒</div><div class="empty-text">Your cart is empty</div></div>';
        document.getElementById('checkoutSummary').style.display = 'none';
    }
}

function renderCart(cartItems, total) {
    const list = document.getElementById('cartList');
    const summary = document.getElementById('checkoutSummary');
    
    if (cartItems.length === 0) {
        list.innerHTML = '<div class="empty-state"><div class="empty-icon">🛒</div><div class="empty-text">Your cart is empty</div></div>';
        summary.style.display = 'none';
        return;
    }
    
    summary.style.display = 'block';
    
    list.innerHTML = cartItems.map(item => {
        const imageUrl = getProductImage(item.image_url);
        
        return `
        <div class="cart-item">
            <img src="${imageUrl}" alt="${item.name}" class="cart-image" onerror="this.src='assets/placeholder-bread.png'">
            <div class="cart-info">
                <div class="product-name">${item.name}</div>
                <div class="bakery-name">${item.bakery_name}</div>
                <div class="current-price">₱${parseFloat(item.discounted_price).toFixed(2)}</div>
                <div class="quantity-control">
                    <button class="qty-btn" onclick="updateCartQuantity(${item.product_id}, ${item.quantity - 1})">−</button>
                    <span class="qty-value">${item.quantity}</span>
                    <button class="qty-btn" onclick="updateCartQuantity(${item.product_id}, ${item.quantity + 1})">+</button>
                    <span class="remove-btn" onclick="removeFromCart(${item.product_id})">Remove</span>
                </div>
            </div>
        </div>
    `}).join('');
    
    document.getElementById('subtotalAmount').textContent = '₱' + parseFloat(total).toFixed(2);
    document.getElementById('totalAmount').textContent = '₱' + parseFloat(total).toFixed(2);
}

async function updateCartQuantity(productId, newQuantity) {
    if (newQuantity < 1) {
        await removeFromCart(productId);
        return;
    }
    
    const result = await CartAPI.update(productId, newQuantity);
    
    if (result.success) {
        await loadCart();
    } else {
        alert(result.message);
    }
}

async function removeFromCart(productId) {
    if (!confirm('Remove this item from cart?')) {
        return;
    }
    
    const result = await CartAPI.remove(productId);
    
    if (result.success) {
        await loadCart();
    } else {
        alert(result.message);
    }
}

// Favorite Functions
async function loadFavorites() {
    showLoading('favoritesLoading', true);
    
    const result = await FavoriteAPI.getAll();
    
    showLoading('favoritesLoading', false);
    
    if (result.success) {
        currentFavorites = result.favorites;
        renderFavorites(result.favorites);
    }
}

function renderFavorites(favorites) {
    const list = document.getElementById('favoritesList');
    
    if (favorites.length === 0) {
        list.innerHTML = '<div class="empty-state"><div class="empty-icon">❤️</div><div class="empty-text">No favorites yet</div></div>';
        return;
    }
    
    list.innerHTML = favorites.map(p => {
        const imageUrl = getProductImage(p.image_url);
        const saleTag = p.is_on_sale ? '<span class="sale-tag">SALE</span>' : '';
        
        return `
        <div class="product-item ${p.is_on_sale ? 'on-sale' : ''}">
            <div class="product-image-container">
                <img src="${imageUrl}" alt="${p.name}" class="product-image" onerror="this.src='assets/placeholder-bread.png'">
                ${saleTag}
            </div>
            <div class="product-details">
                <div class="product-header">
                    <div>
                        <div class="product-name">${p.name}</div>
                        <div class="bakery-name">${p.bakery_name}</div>
                    </div>
                    <div class="heart-icon active" onclick="toggleFavorite(${p.product_id})">❤️</div>
                </div>
                <div class="price-section">
                    <div class="price-info">
                        <span class="current-price">₱${parseFloat(p.discounted_price).toFixed(2)}</span>
                        <span class="original-price">₱${parseFloat(p.original_price).toFixed(2)}</span>
                        <span class="discount-badge">-${p.discount_percentage}%</span>
                    </div>
                    <button class="add-cart-btn" onclick="addToCart(${p.product_id})">+</button>
                </div>
            </div>
        </div>
    `}).join('');
}

function isFavorite(productId) {
    return currentFavorites.some(fav => fav.product_id === productId);
}

async function toggleFavorite(productId) {
    const isCurrentlyFavorite = isFavorite(productId);
    
    const result = isCurrentlyFavorite 
        ? await FavoriteAPI.remove(productId)
        : await FavoriteAPI.add(productId);
    
    if (result.success) {
        await loadFavorites();
        await loadProducts(); // Refresh to update heart icons
    } else {
        alert(result.message);
    }
}

// Checkout Functions
async function goToCheckout() {
    if (currentCart.length === 0) {
        alert('Your cart is empty');
        return;
    }
    
    // Prepare checkout summary
    let itemsHtml = '';
    let subtotal = 0;
    
    currentCart.forEach(item => {
        const itemTotal = item.discounted_price * item.quantity;
        subtotal += parseFloat(itemTotal);
        itemsHtml += `
            <div class="summary-item">
                <span>${item.name} × ${item.quantity}</span>
                <span>₱${parseFloat(itemTotal).toFixed(2)}</span>
            </div>
        `;
    });
    
    document.getElementById('checkoutItems').innerHTML = itemsHtml;
    document.getElementById('checkoutSubtotal').textContent = '₱' + subtotal.toFixed(2);
    document.getElementById('checkoutTotal').textContent = '₱' + (subtotal + 20).toFixed(2);
    
    showScreen('checkoutScreen');
}

function selectDeliveryOption(element) {
    const parent = element.closest('.radio-group');
    parent.querySelectorAll('.radio-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    selectedDeliveryOption = element.dataset.value;
    
    // Update delivery fee
    const deliveryFee = selectedDeliveryOption === 'delivery' ? 20 : 0;
    document.getElementById('deliveryFeeAmount').textContent = '₱' + deliveryFee.toFixed(2);
    
    // Update total
    const subtotal = parseFloat(document.getElementById('checkoutSubtotal').textContent.replace('₱', ''));
    document.getElementById('checkoutTotal').textContent = '₱' + (subtotal + deliveryFee).toFixed(2);
}

function selectPaymentMethod(element) {
    const parent = element.closest('.radio-group');
    parent.querySelectorAll('.radio-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    selectedPaymentMethod = element.dataset.value;
}

async function placeOrder() {
    if (!currentUser) {
        alert('Please login to place an order');
        return;
    }
    
    enableButton('placeOrderBtn', false);
    
    const result = await OrderAPI.create(
        selectedDeliveryOption,
        selectedPaymentMethod,
        currentUser.address || '',
        currentUser.contact_number || ''
    );
    
    enableButton('placeOrderBtn', true);
    
    if (result.success) {
        alert(`Order placed successfully! 🎉\n\nOrder Number: ${result.order.order_number}\n\nYour order is being processed and will be delivered soon.`);
        
        currentCart = [];
        showScreen('appScreen');
        navigateToTab('browse');
        
        // Reload products to update stock
        await loadProducts();
    } else {
        alert('Failed to place order: ' + result.message);
    }
}

function backToCart() {
    showScreen('appScreen');
    navigateToTab('cart');
}

// Navigation Functions
function showScreen(screenId) {
    document.querySelectorAll('.auth-screen, .app-screen, .checkout-screen').forEach(s => {
        s.classList.remove('active');
    });
    document.getElementById(screenId).classList.add('active');
}

// FIXED: New function to handle tab switching programmatically
async function navigateToTab(tab) {
    // Update nav items
    document.querySelectorAll('.nav-item').forEach(item => {
        const label = item.querySelector('.nav-label');
        if (label && label.textContent.toLowerCase() === tab.toLowerCase()) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    
    // Update content
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + 'Tab').classList.add('active');
    
    // Load data for specific tabs
    if (tab === 'cart') {
        await loadCart();
    } else if (tab === 'favorites') {
        await loadFavorites();
    } else if (tab === 'browse') {
        // Reset to all products view
        if (currentView === 'sale') {
            currentView = 'all';
            document.querySelector('.section-title').textContent = 'Available Today';
            await loadProducts();
        }
    }
}

// FIXED: Updated switchTab for bottom navigation clicks
async function switchTab(tab) {
    await navigateToTab(tab);
}

async function viewOrders() {
    const result = await OrderAPI.getAll();
    
    if (result.success) {
        if (result.orders.length === 0) {
            alert('You have no orders yet');
        } else {
            let orderList = 'Your Orders:\n\n';
            result.orders.forEach(order => {
                orderList += `Order #${order.order_number}\n`;
                orderList += `Status: ${order.order_status}\n`;
                orderList += `Total: ₱${parseFloat(order.total_amount).toFixed(2)}\n`;
                orderList += `Date: ${new Date(order.ordered_at).toLocaleDateString()}\n\n`;
            });
            alert(orderList);
        }
    } else {
        alert('Failed to load orders');
    }
}