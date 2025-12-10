/**
 * Bakery Management Application
 * DoughMain - Bakery Interface
 */

let currentBakery = null;
let allProducts = [];
let categories = [];
let editingProductId = null;

// Initialize app
window.addEventListener('DOMContentLoaded', () => {
    initBakeryApp();
});

async function initBakeryApp() {
    try {
        const result = await BakeryAuthAPI.getProfile();
        if (result.success) {
            currentBakery = result.bakery;
            showScreen('appScreen');
            await loadBakeryData();
        }
    } catch (error) {
        console.log('No active session');
    }
    
    // Load categories for product modal
    await loadCategories();
}

async function loadBakeryData() {
    if (!currentBakery) return;
    
    updateBakeryProfile();
    await loadProducts();
    await loadDashboard();
}

// Authentication Functions
async function login() {
    const username = document.getElementById('loginUsername').value.trim();
    const password = document.getElementById('loginPassword').value;
    
    if (!username || !password) {
        showBakeryMessage('loginError', 'Please fill in all fields', true);
        return;
    }
    
    showBakeryLoading('loginLoading', true);
    
    const result = await BakeryAuthAPI.login(username, password);
    
    showBakeryLoading('loginLoading', false);
    
    if (result.success) {
        currentBakery = result.bakery;
        showBakeryMessage('loginSuccess', result.message, false);
        
        setTimeout(() => {
            showScreen('appScreen');
            loadBakeryData();
        }, 1000);
    } else {
        showBakeryMessage('loginError', result.message, true);
    }
}

async function register() {
    const name = document.getElementById('registerName').value.trim();
    const username = document.getElementById('registerUsername').value.trim();
    const email = document.getElementById('registerEmail').value.trim();
    const password = document.getElementById('registerPassword').value;
    const address = document.getElementById('registerAddress').value.trim();
    const contact = document.getElementById('registerContact').value.trim();
    const description = document.getElementById('registerDescription').value.trim();
    const hours = document.getElementById('registerHours').value.trim();
    
    if (!name || !username || !email || !password) {
        showBakeryMessage('registerError', 'Please fill in all required fields', true);
        return;
    }
    
    showBakeryLoading('registerLoading', true);
    
    const result = await BakeryAuthAPI.register({
        name,
        username,
        email,
        password,
        address,
        contact_number: contact,
        description,
        opening_hours: hours
    });
    
    showBakeryLoading('registerLoading', false);
    
    if (result.success) {
        showBakeryMessage('registerSuccess', result.message, false);
        
        setTimeout(() => {
            showScreen('loginScreen');
        }, 2000);
    } else {
        showBakeryMessage('registerError', result.message, true);
    }
}

async function logout() {
    if (!confirm('Are you sure you want to sign out?')) {
        return;
    }
    
    await BakeryAuthAPI.logout();
    
    currentBakery = null;
    allProducts = [];
    
    document.getElementById('loginUsername').value = '';
    document.getElementById('loginPassword').value = '';
    
    showScreen('loginScreen');
}

function updateBakeryProfile() {
    if (currentBakery) {
        document.getElementById('profileName').textContent = currentBakery.name || 'N/A';
        document.getElementById('profileEmail').textContent = currentBakery.email || 'N/A';
        document.getElementById('profileContact').textContent = currentBakery.contact_number || 'N/A';
        document.getElementById('profileAddress').textContent = currentBakery.address || 'N/A';
        document.getElementById('profileHours').textContent = currentBakery.opening_hours || 'N/A';
    }
}

// Dashboard Functions
async function loadDashboard() {
    const result = await BakeryProductAPI.getDashboard();
    
    if (result.success && result.dashboard) {
        const dash = result.dashboard;
        document.getElementById('totalProducts').textContent = dash.total_products || 0;
        document.getElementById('availableProducts').textContent = dash.available_products || 0;
        document.getElementById('saleProducts').textContent = dash.sale_products || 0;
        document.getElementById('totalStock').textContent = dash.total_stock || 0;
    }
}

// Product Functions
async function loadProducts() {
    showBakeryLoading('productsLoading', true);
    
    const result = await BakeryProductAPI.getAll();
    
    showBakeryLoading('productsLoading', false);
    
    if (result.success) {
        allProducts = result.products;
        renderProducts(result.products);
    } else {
        document.getElementById('productList').innerHTML = 
            '<div class="empty-state"><div class="empty-icon">😕</div><div class="empty-text">Failed to load products</div></div>';
    }
}

function renderProducts(products) {
    const list = document.getElementById('productList');
    
    if (products.length === 0) {
        list.innerHTML = '<div class="empty-state"><div class="empty-icon">🍞</div><div class="empty-text">No products yet. Add your first product!</div></div>';
        return;
    }
    
    list.innerHTML = products.map(p => {
        const imageUrl = p.image_url || 'assets/placeholder-bread.png';
        const saleTag = p.is_on_sale ? '<span class="sale-badge">ON SALE</span>' : '';
        
        return `
        <div class="product-item">
            ${saleTag}
            <div class="product-image-container">
                <img src="${imageUrl}" alt="${p.name}" class="product-image" onerror="this.src='assets/placeholder-bread.png'">
            </div>
            <div class="product-details">
                <div class="product-name">${p.name}</div>
                <div class="product-price">${formatPrice(p.discounted_price)} ${p.discounted_price < p.original_price ? '<span style="text-decoration: line-through; color: #999; font-size: 12px;">' + formatPrice(p.original_price) + '</span>' : ''}</div>
                <div class="product-stock">Stock: ${p.stock_quantity} | ${p.is_available ? '✅ Available' : '❌ Unavailable'}</div>
                <div class="product-actions">
                    <button class="btn-small btn-image" onclick="openImageModal(${p.product_id})">📷 Image</button>
                    <button class="btn-small btn-edit" onclick="editProduct(${p.product_id})">✏️ Edit</button>
                    <button class="btn-small btn-delete" onclick="deleteProduct(${p.product_id})">🗑️ Delete</button>
                </div>
            </div>
        </div>
    `}).join('');
}

async function loadCategories() {
    try {
        const response = await fetch('api/index.php?action=categories');
        const result = await response.json();
        
        if (result.success) {
            categories = result.categories;
            
            const select = document.getElementById('productCategory');
            select.innerHTML = '<option value="">Select category</option>' + 
                categories.map(cat => {
                    if (cat.name !== 'All') {
                        return `<option value="${cat.category_id}">${cat.name}</option>`;
                    }
                    return '';
                }).join('');
        }
    } catch (error) {
        console.error('Error loading categories:', error);
    }
}

// Product Modal Functions
function openAddProductModal() {
    editingProductId = null;
    document.getElementById('modalTitle').textContent = 'Add Product';
    document.getElementById('editProductId').value = '';
    
    // Clear form
    document.getElementById('productName').value = '';
    document.getElementById('productCategory').value = '';
    document.getElementById('productDescription').value = '';
    document.getElementById('productOriginalPrice').value = '';
    document.getElementById('productDiscountedPrice').value = '';
    document.getElementById('productStock').value = '0';
    document.getElementById('productExpiry').value = '';
    document.getElementById('productAvailable').checked = true;
    document.getElementById('productOnSale').checked = false;
    document.getElementById('saleStartDate').value = '';
    document.getElementById('saleEndDate').value = '';
    document.getElementById('saleDatesSection').style.display = 'none';
    
    document.getElementById('productModal').classList.add('active');
}

function editProduct(productId) {
    const product = allProducts.find(p => p.product_id === productId);
    if (!product) return;
    
    editingProductId = productId;
    document.getElementById('modalTitle').textContent = 'Edit Product';
    document.getElementById('editProductId').value = productId;
    
    // Fill form
    document.getElementById('productName').value = product.name;
    document.getElementById('productCategory').value = product.category_id;
    document.getElementById('productDescription').value = product.description || '';
    document.getElementById('productOriginalPrice').value = product.original_price;
    document.getElementById('productDiscountedPrice').value = product.discounted_price;
    document.getElementById('productStock').value = product.stock_quantity;
    document.getElementById('productExpiry').value = product.expiry_date || '';
    document.getElementById('productAvailable').checked = product.is_available == 1;
    document.getElementById('productOnSale').checked = product.is_on_sale == 1;
    
    if (product.is_on_sale == 1) {
        document.getElementById('saleDatesSection').style.display = 'block';
        document.getElementById('saleStartDate').value = formatDateTime(product.sale_start_date);
        document.getElementById('saleEndDate').value = formatDateTime(product.sale_end_date);
    }
    
    document.getElementById('productModal').classList.add('active');
}

function closeProductModal() {
    document.getElementById('productModal').classList.remove('active');
    editingProductId = null;
}

function toggleSaleDates() {
    const isOnSale = document.getElementById('productOnSale').checked;
    document.getElementById('saleDatesSection').style.display = isOnSale ? 'block' : 'none';
}

async function saveProduct() {
    const name = document.getElementById('productName').value.trim();
    const categoryId = document.getElementById('productCategory').value;
    const description = document.getElementById('productDescription').value.trim();
    const originalPrice = document.getElementById('productOriginalPrice').value;
    const discountedPrice = document.getElementById('productDiscountedPrice').value;
    const stock = document.getElementById('productStock').value;
    const expiry = document.getElementById('productExpiry').value;
    const isAvailable = document.getElementById('productAvailable').checked ? 1 : 0;
    const isOnSale = document.getElementById('productOnSale').checked ? 1 : 0;
    const saleStart = document.getElementById('saleStartDate').value;
    const saleEnd = document.getElementById('saleEndDate').value;
    
    if (!name || !categoryId || !originalPrice || !discountedPrice) {
        showBakeryMessage('productError', 'Please fill in all required fields', true);
        return;
    }
    
    if (parseFloat(discountedPrice) > parseFloat(originalPrice)) {
        showBakeryMessage('productError', 'Discounted price cannot be higher than original price', true);
        return;
    }
    
    const productData = {
        name,
        category_id: categoryId,
        description,
        original_price: originalPrice,
        discounted_price: discountedPrice,
        stock_quantity: stock,
        expiry_date: expiry || null,
        is_available: isAvailable,
        is_on_sale: isOnSale,
        sale_start_date: isOnSale ? saleStart : null,
        sale_end_date: isOnSale ? saleEnd : null
    };
    
    let result;
    if (editingProductId) {
        productData.product_id = editingProductId;
        result = await BakeryProductAPI.update(productData);
    } else {
        result = await BakeryProductAPI.add(productData);
    }
    
    if (result.success) {
        showBakeryMessage('productSuccess', result.message, false);
        
        setTimeout(async () => {
            closeProductModal();
            await loadProducts();
            await loadDashboard();
        }, 1000);
    } else {
        showBakeryMessage('productError', result.message, true);
    }
}

async function deleteProduct(productId) {
    if (!confirm('Are you sure you want to delete this product?')) {
        return;
    }
    
    const result = await BakeryProductAPI.delete(productId);
    
    if (result.success) {
        alert(result.message);
        await loadProducts();
        await loadDashboard();
    } else {
        alert('Failed to delete: ' + result.message);
    }
}

// Image Upload Functions
function openImageModal(productId) {
    document.getElementById('imageProductId').value = productId;
    document.getElementById('productImage').value = '';
    document.getElementById('imagePreview').classList.remove('active');
    document.getElementById('imageModal').classList.add('active');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.remove('active');
}

function previewImage() {
    const file = document.getElementById('productImage').files[0];
    const preview = document.getElementById('imagePreview');
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.add('active');
        };
        reader.readAsDataURL(file);
    }
}

async function uploadImage() {
    const productId = document.getElementById('imageProductId').value;
    const fileInput = document.getElementById('productImage');
    
    if (!fileInput.files || !fileInput.files[0]) {
        showBakeryMessage('imageError', 'Please select an image', true);
        return;
    }
    
    const result = await BakeryProductAPI.uploadImage(productId, fileInput.files[0]);
    
    if (result.success) {
        showBakeryMessage('imageSuccess', result.message, false);
        
        setTimeout(async () => {
            closeImageModal();
            await loadProducts();
        }, 1000);
    } else {
        showBakeryMessage('imageError', result.message, true);
    }
}

// Navigation Functions
function showScreen(screenId) {
    document.querySelectorAll('.auth-screen, .app-screen').forEach(s => {
        s.classList.remove('active');
    });
    document.getElementById(screenId).classList.add('active');
}

async function switchTab(tab) {
    document.querySelectorAll('.nav-item').forEach(item => {
        const label = item.querySelector('.nav-label');
        if (label && label.textContent.toLowerCase() === tab.toLowerCase()) {
            item.classList.add('active');
        } else {
            item.classList.remove('active');
        }
    });
    
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + 'Tab').classList.add('active');
    
    if (tab === 'products') {
        await loadProducts();
    } else if (tab === 'dashboard') {
        await loadDashboard();
    }
}