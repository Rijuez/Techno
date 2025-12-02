/**
 * API Configuration and Helper Functions
 * DoughMain - Bread Rescue Application
 * Updated with Sale Products Support
 */

const API_BASE_URL = 'api/index.php';

// Helper function to make API requests
async function apiRequest(action, method = 'GET', data = null) {
    const url = `${API_BASE_URL}?action=${action}`;
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    };
    
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        return result;
    } catch (error) {
        console.error('API Request Error:', error);
        return {
            success: false,
            message: 'Network error. Please check your connection.'
        };
    }
}

// Authentication API calls
const AuthAPI = {
    async login(email, password) {
        return await apiRequest('login', 'POST', { email, password });
    },
    
    async register(name, email, password, address, contact) {
        return await apiRequest('register', 'POST', {
            name,
            email,
            password,
            address,
            contact
        });
    },
    
    async logout() {
        return await apiRequest('logout', 'POST');
    }
};

// Product API calls
const ProductAPI = {
    async getAll() {
        return await apiRequest('products', 'GET');
    },
    
    async getSaleProducts() {
        return await apiRequest('sale_products', 'GET');
    },
    
    async getOne(productId) {
        return await apiRequest(`product&id=${productId}`, 'GET');
    },
    
    async search(query) {
        return await apiRequest(`search&query=${encodeURIComponent(query)}`, 'GET');
    },
    
    async getCategories() {
        return await apiRequest('categories', 'GET');
    }
};

// Cart API calls
const CartAPI = {
    async get() {
        return await apiRequest('cart', 'GET');
    },
    
    async add(productId, quantity = 1) {
        return await apiRequest('cart_add', 'POST', { product_id: productId, quantity });
    },
    
    async update(productId, quantity) {
        return await apiRequest('cart_update', 'POST', { product_id: productId, quantity });
    },
    
    async remove(productId) {
        return await apiRequest('cart_remove', 'POST', { product_id: productId });
    },
    
    async clear() {
        return await apiRequest('cart_clear', 'POST');
    }
};

// Order API calls
const OrderAPI = {
    async create(deliveryOption, paymentMethod, deliveryAddress, contactNumber, notes = '') {
        return await apiRequest('create_order', 'POST', {
            delivery_option: deliveryOption,
            payment_method: paymentMethod,
            delivery_address: deliveryAddress,
            contact_number: contactNumber,
            notes: notes
        });
    },
    
    async getAll() {
        return await apiRequest('orders', 'GET');
    },
    
    async getOne(orderId) {
        return await apiRequest(`order&order_id=${orderId}`, 'GET');
    }
};

// Favorite API calls
const FavoriteAPI = {
    async getAll() {
        return await apiRequest('favorites', 'GET');
    },
    
    async add(productId) {
        return await apiRequest('favorite_add', 'POST', { product_id: productId });
    },
    
    async remove(productId) {
        return await apiRequest('favorite_remove', 'POST', { product_id: productId });
    }
};

// User API calls
const UserAPI = {
    async getProfile() {
        return await apiRequest('user', 'GET');
    },
    
    async updateProfile(name, address, contact) {
        return await apiRequest('user_update', 'POST', {
            name,
            address,
            contact_number: contact
        });
    }
};

// Helper functions for error/success messages
function showMessage(elementId, message, isError = false) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = message;
        element.className = isError ? 'error-message active' : 'success-message active';
        
        // Hide after 5 seconds
        setTimeout(() => {
            element.className = isError ? 'error-message' : 'success-message';
        }, 5000);
    }
}

function showLoading(elementId, show = true) {
    const element = document.getElementById(elementId);
    if (element) {
        element.className = show ? 'loading active' : 'loading';
    }
}

function enableButton(elementId, enable = true) {
    const button = document.getElementById(elementId);
    if (button) {
        button.disabled = !enable;
    }
}

// Image helper functions
function getProductImage(imageUrl) {
    if (!imageUrl || imageUrl === '' || imageUrl === null) {
        return 'assets/placeholder-bread.png';
    }
    return imageUrl;
}

function formatImageUrl(url) {
    // If it's already a full URL, return as is
    if (url.startsWith('http://') || url.startsWith('https://')) {
        return url;
    }
    // Otherwise, prepend base URL
    return url;
}