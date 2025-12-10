/**
 * Bakery API Functions
 * DoughMain - Bakery Management System
 */

const BAKERY_API_BASE = 'api/bakery.php';

// Helper function for API requests
async function bakeryApiRequest(action, method = 'GET', data = null) {
    const url = `${BAKERY_API_BASE}?action=${action}`;
    
    const options = {
        method: method,
        credentials: 'same-origin'
    };
    
    if (data && method !== 'GET') {
        if (data instanceof FormData) {
            options.body = data;
        } else {
            options.headers = {
                'Content-Type': 'application/json',
            };
            options.body = JSON.stringify(data);
        }
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

// Bakery Authentication API
const BakeryAuthAPI = {
    async login(username, password) {
        return await bakeryApiRequest('login', 'POST', { username, password });
    },
    
    async register(data) {
        return await bakeryApiRequest('register', 'POST', data);
    },
    
    async logout() {
        return await bakeryApiRequest('logout', 'POST');
    },
    
    async getProfile() {
        return await bakeryApiRequest('profile', 'GET');
    },
    
    async updateProfile(data) {
        return await bakeryApiRequest('profile_update', 'POST', data);
    }
};

// Bakery Product API
const BakeryProductAPI = {
    async getAll() {
        return await bakeryApiRequest('products', 'GET');
    },
    
    async add(productData) {
        return await bakeryApiRequest('product_add', 'POST', productData);
    },
    
    async update(productData) {
        return await bakeryApiRequest('product_update', 'POST', productData);
    },
    
    async delete(productId) {
        return await bakeryApiRequest('product_delete', 'POST', { product_id: productId });
    },
    
    async uploadImage(productId, imageFile) {
        const formData = new FormData();
        formData.append('product_id', productId);
        formData.append('image', imageFile);
        
        return await bakeryApiRequest('product_image', 'POST', formData);
    },
    
    async getDashboard() {
        return await bakeryApiRequest('dashboard', 'GET');
    }
};

// Helper functions
function showBakeryMessage(elementId, message, isError = false) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = message;
        element.className = isError ? 'error-message active' : 'success-message active';
        
        setTimeout(() => {
            element.className = isError ? 'error-message' : 'success-message';
        }, 5000);
    }
}

function showBakeryLoading(elementId, show = true) {
    const element = document.getElementById(elementId);
    if (element) {
        element.className = show ? 'loading active' : 'loading';
    }
}

function formatPrice(price) {
    return '₱' + parseFloat(price).toFixed(2);
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-PH');
}

function formatDateTime(dateString) {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toISOString().slice(0, 16);
}