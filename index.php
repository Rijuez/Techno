<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DoughMain - Bread Rescue</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #fff;
        }

        .phone-frame {
            max-width: 375px;
            margin: 0 auto;
            background: white;
            min-height: 100vh;
            box-shadow: 0 0 40px rgba(0,0,0,0.1);
        }

        /* Auth Screens */
        .auth-screen {
            display: none;
            padding: 40px 30px;
            min-height: 100vh;
            background: linear-gradient(180deg, #FFF5E6 0%, #FFFFFF 50%);
        }

        .auth-screen.active {
            display: block;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 50px;
            padding-top: 60px;
        }

        .logo-icon {
            width: 120px;
            height: 120px;
            background: linear-gradient(135deg, #FF8C42 0%, #FF6B35 100%);
            border-radius: 30px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 60px;
            box-shadow: 0 10px 30px rgba(255, 107, 53, 0.3);
        }

        .logo-icon img {
    width: 100%;
    height: 100%;
    object-fit: contain;
}

        .logo-text h1 {
            font-size: 32px;
            color: #FF6B35;
            margin-bottom: 5px;
        }

        .logo-text p {
            font-size: 12px;
            color: #666;
        }

        .input-group {
            margin-bottom: 20px;
        }

        .input-group label {
            display: block;
            font-size: 14px;
            color: #333;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .input-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #E0E0E0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
        }

        .input-group input:focus {
            outline: none;
            border-color: #FF6B35;
        }

        .btn-auth {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 12px;
        }

        .btn-primary {
            background: #FF6B35;
            color: white;
        }

        .btn-primary:hover {
            background: #FF5722;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 107, 53, 0.3);
        }

        .btn-primary:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }

        .btn-secondary {
            background: white;
            color: #FF6B35;
            border: 2px solid #FF6B35;
        }

        .btn-secondary:hover {
            background: #FFF5F0;
        }

        /* Loading Spinner */
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }

        .loading.active {
            display: block;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #FF6B35;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Main App */
        .app-screen {
            display: none;
            background: #FAFAFA;
            min-height: 100vh;
        }

        .app-screen.active {
            display: block;
        }

        .top-bar {
            background: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .top-bar h2 {
            font-size: 24px;
            color: #333;
        }

        .top-bar-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.app-logo {
    width: 40px;
    height: 40px;
    object-fit: contain;
    border-radius: 8px;
}

        .profile-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FF8C42, #FF6B35);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            cursor: pointer;
        }

        .search-section {
            padding: 20px;
            background: white;
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            width: 100%;
            padding: 14px 20px 14px 45px;
            border: 2px solid #E0E0E0;
            border-radius: 25px;
            font-size: 15px;
        }

        .search-box:before {
            content: "🔍";
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
        }

        .categories {
            display: flex;
            gap: 10px;
            padding: 15px 20px;
            overflow-x: auto;
            background: white;
        }

        .category-chip {
            padding: 10px 20px;
            background: #F5F5F5;
            border-radius: 20px;
            font-size: 14px;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.3s;
        }

        .category-chip.active {
            background: #FF6B35;
            color: white;
        }

        .products-section {
            padding: 20px;
            padding-bottom: 80px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 15px;
            color: #333;
        }

        .product-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .product-item {
            background: white;
            border-radius: 16px;
            padding: 15px;
            display: flex;
            gap: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .product-image {
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #FFE5B4 0%, #FFD700 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            flex-shrink: 0;
        }

        .product-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
        }

        .product-name {
            font-size: 18px;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .bakery-name {
            font-size: 12px;
            color: #999;
            margin-bottom: 8px;
        }

        .heart-icon {
            width: 32px;
            height: 32px;
            background: #F5F5F5;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
        }

        .heart-icon.active {
            background: #FFE5E5;
        }

        .price-section {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .price-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .current-price {
            font-size: 22px;
            font-weight: 700;
            color: #FF6B35;
        }

        .original-price {
            font-size: 14px;
            color: #999;
            text-decoration: line-through;
        }

        .discount-badge {
            background: #FF6B35;
            color: white;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 700;
        }

        .add-cart-btn {
            width: 36px;
            height: 36px;
            background: #FF6B35;
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Bottom Navigation */
        .bottom-nav {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 375px;
            background: white;
            display: flex;
            justify-content: space-around;
            padding: 12px 0;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        .nav-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            padding: 8px 20px;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .nav-item.active {
            background: #FFF5F0;
        }

        .nav-icon {
            font-size: 24px;
        }

        .nav-item.active .nav-icon {
            filter: grayscale(0);
        }

        .nav-label {
            font-size: 11px;
            color: #666;
        }

        .nav-item.active .nav-label {
            color: #FF6B35;
            font-weight: 600;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        /* Cart Screen */
        .cart-list {
            padding: 20px;
            padding-bottom: 150px;
        }

        .cart-item {
            background: white;
            border-radius: 16px;
            padding: 15px;
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
        }

        .cart-image {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #FFE5B4 0%, #FFD700 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
        }

        .cart-info {
            flex: 1;
        }

        .quantity-control {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 10px;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            border: 2px solid #E0E0E0;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-value {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }

        .remove-btn {
            color: #FF6B35;
            font-size: 12px;
            cursor: pointer;
            margin-left: auto;
        }

        .checkout-summary {
            position: fixed;
            bottom: 70px;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 375px;
            background: white;
            padding: 20px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }

        .summary-label {
            color: #666;
            font-size: 15px;
        }

        .summary-value {
            font-weight: 600;
            font-size: 15px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding-top: 15px;
            border-top: 2px solid #F0F0F0;
            margin-bottom: 15px;
        }

        .total-label {
            font-size: 18px;
            font-weight: 700;
        }

        .total-value {
            font-size: 22px;
            font-weight: 700;
            color: #FF6B35;
        }

        .checkout-btn {
            width: 100%;
            padding: 16px;
            background: #FF6B35;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        /* Profile Screen */
        .profile-header {
            background: linear-gradient(135deg, #FF8C42, #FF6B35);
            padding: 40px 20px;
            text-align: center;
            color: white;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            margin: 0 auto 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: #FF6B35;
        }

        .profile-name {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .profile-email {
            font-size: 14px;
            opacity: 0.9;
        }

        .profile-menu {
            padding: 20px;
        }

        .menu-item {
            background: white;
            padding: 18px;
            border-radius: 12px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        }

        .menu-item-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .menu-icon {
            font-size: 24px;
        }

        .menu-text {
            font-size: 15px;
            font-weight: 500;
            color: #333;
        }

        .menu-arrow {
            color: #999;
            font-size: 18px;
        }

        .logout-btn {
            background: white;
            color: #FF6B35;
            border: 2px solid #FF6B35;
            padding: 16px;
            border-radius: 12px;
            width: calc(100% - 40px);
            margin: 20px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }

        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .empty-text {
            font-size: 16px;
            color: #999;
        }

        /* Checkout Screen */
        .checkout-screen {
            display: none;
            background: #FAFAFA;
            min-height: 100vh;
            padding-bottom: 100px;
        }

        .checkout-screen.active {
            display: block;
        }

        .checkout-form {
            padding: 20px;
        }

        .form-section {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .form-section h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #333;
        }

        .radio-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px;
            border: 2px solid #E0E0E0;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .radio-option.selected {
            border-color: #FF6B35;
            background: #FFF5F0;
        }

        .radio-circle {
            width: 20px;
            height: 20px;
            border: 2px solid #E0E0E0;
            border-radius: 50%;
            position: relative;
        }

        .radio-option.selected .radio-circle {
            border-color: #FF6B35;
        }

        .radio-option.selected .radio-circle:after {
            content: '';
            width: 10px;
            height: 10px;
            background: #FF6B35;
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .order-summary-box {
            background: white;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }

        .checkout-fixed-bottom {
            position: fixed;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            max-width: 375px;
            background: white;
            padding: 20px;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
        }

        .error-message {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            display: none;
        }

        .error-message.active {
            display: block;
        }

        .success-message {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            display: none;
        }

        .success-message.active {
            display: block;
        }
    </style>
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/additional-styles.css">
</head>
<body>
    <div class="phone-frame">
        <!-- Sign In Screen -->
        <div id="signinScreen" class="auth-screen active">
    <div class="logo-section">
        <div class="logo-icon" style="background: transparent; box-shadow: none;">
            <img src="assets/logo.png" alt="Logo" style="width: 200px; height: 220px;"> 
        </div>
        <div class="logo-text">
            <h1>DoughMain</h1>
            <p>Turning yesterday's bread into tomorrow's hope</p>
        </div>
    </div>
            
            <div class="error-message" id="loginError"></div>
            <div class="success-message" id="loginSuccess"></div>
            
            <div class="input-group">
                <label>Email</label>
                <input type="email" id="loginEmail" placeholder="Enter your email">
            </div>
            
            <div class="input-group">
                <label>Password</label>
                <input type="password" id="loginPassword" placeholder="Enter your password">
            </div>
            
            <button class="btn-auth btn-primary" id="loginBtn" onclick="login()">Sign In</button>
            <button class="btn-auth btn-secondary" onclick="showScreen('signupScreen')">Create Account</button>
            
            <div class="loading" id="loginLoading">
                <div class="spinner"></div>
                <p style="margin-top: 10px; color: #666;">Signing in...</p>
            </div>
        </div>

        <!-- Sign Up Screen -->
        <div id="signupScreen" class="auth-screen">
    <div class="logo-section">
        <div class="logo-icon" style="background: transparent; box-shadow: none;">
            <img src="assets/logo.png" alt="Logo" style="width: 200px; height: 220px;">
        </div>
        <div class="logo-text">
            <h1>Create Account</h1>
                    <p>Join us in reducing food waste</p>
                </div>
            </div>
            
            <div class="error-message" id="signupError"></div>
            <div class="success-message" id="signupSuccess"></div>
            
            <div class="input-group">
                <label>Full Name</label>
                <input type="text" id="signupName" placeholder="Enter your name">
            </div>
            
            <div class="input-group">
                <label>Email</label>
                <input type="email" id="signupEmail" placeholder="Enter your email">
            </div>
            
            <div class="input-group">
                <label>Password</label>
                <input type="password" id="signupPassword" placeholder="Create a password">
            </div>
            
            <div class="input-group">
                <label>Address</label>
                <input type="text" id="signupAddress" placeholder="Enter your address">
            </div>
            
            <div class="input-group">
                <label>Contact Number</label>
                <input type="tel" id="signupContact" placeholder="Enter your phone number">
            </div>
            
            <button class="btn-auth btn-primary" id="signupBtn" onclick="signup()">Create Account</button>
            <button class="btn-auth btn-secondary" onclick="showScreen('signinScreen')">Back to Sign In</button>
            
            <div class="loading" id="signupLoading">
                <div class="spinner"></div>
                <p style="margin-top: 10px; color: #666;">Creating account...</p>
            </div>
        </div>

        <!-- Main App Screen -->
        <div id="appScreen" class="app-screen">
            <!-- Browse Tab -->
            <div id="browseTab" class="tab-content active">
    <div class="top-bar">
        <h2>Browse</h2>
        <div class="top-bar-right">
            <img src="assets/logo.png" alt="DoughMain" class="app-logo">
            <div class="profile-icon" onclick="switchTab('profile')">👤</div>
        </div>
    </div>
                
                <div class="search-section">
                    <div class="search-box">
                        <input type="text" id="searchInput" placeholder="Search for bread..." oninput="searchProducts()">
                    </div>
                </div>
                
                <div class="categories" id="categoriesList">
                    <!-- Categories will be loaded here -->
                </div>
                
                <div class="products-section">
                    <div class="section-title">Available Today</div>
                    <div class="loading" id="productsLoading">
                        <div class="spinner"></div>
                    </div>
                    <div class="product-list" id="productList"></div>
                </div>
            </div>

            <!-- Favorites Tab -->
            <div id="favoritesTab" class="tab-content">
                <div class="top-bar">
                    <h2>Favorites</h2>
                </div>
                
                <div class="products-section">
                    <div class="loading" id="favoritesLoading">
                        <div class="spinner"></div>
                    </div>
                    <div class="product-list" id="favoritesList"></div>
                </div>
            </div>

            <!-- Cart Tab -->
            <div id="cartTab" class="tab-content">
                <div class="top-bar">
                    <h2>Cart</h2>
                </div>
                
                <div class="loading" id="cartLoading">
                    <div class="spinner"></div>
                </div>
                <div class="cart-list" id="cartList"></div>
                
                <div class="checkout-summary" id="checkoutSummary" style="display: none;">
                    <div class="summary-row">
                        <span class="summary-label">Subtotal</span>
                        <span class="summary-value" id="subtotalAmount">₱0.00</span>
                    </div>
                    <div class="total-row">
                        <span class="total-label">Total</span>
                        <span class="total-value" id="totalAmount">₱0.00</span>
                    </div>
                    <button class="checkout-btn" onclick="goToCheckout()">Proceed to Checkout</button>
                </div>
            </div>

           <!-- Profile Tab -->
<div id="profileTab" class="tab-content">
    <div class="profile-header">
        <div class="profile-avatar">👤</div>
        <div class="profile-name" id="profileName">User Name</div>
        <div class="profile-email" id="profileEmail">user@email.com</div>
    </div>
    
    <div class="profile-menu">
        <div class="menu-item" onclick="viewOrders()">
            <div class="menu-item-left">
                <span class="menu-icon">📋</span>
                <span class="menu-text">Order History</span>
            </div>
            <span class="menu-arrow">›</span>
        </div>
        
        <div class="menu-item" onclick="showProfileSection('addresses')">  
            <div class="menu-item-left">
                <span class="menu-icon">📍</span>
                <span class="menu-text">Addresses</span>
            </div>
            <span class="menu-arrow">›</span>
        </div>
        
        <div class="menu-item" onclick="showProfileSection('payment')">  
            <div class="menu-item-left">
                <span class="menu-icon">💳</span>
                <span class="menu-text">Payment Methods</span>
            </div>
            <span class="menu-arrow">›</span>
        </div>
        
        <div class="menu-item" onclick="showProfileSection('settings')">  
            <div class="menu-item-left">
                <span class="menu-icon">⚙️</span>
                <span class="menu-text">Settings</span>
            </div>
            <span class="menu-arrow">›</span>
        </div>
    </div>
    
    <button class="logout-btn" onclick="logout()">Sign Out</button>
</div>

            <!-- PROFILE SECTIONS - Add these after the profileTab closing </div> -->

<!-- Addresses Section -->
<div id="addressesSection" class="profile-section tab-content">
    <div class="top-bar">
        <span onclick="backToProfile()" style="cursor: pointer; font-size: 24px;">←</span>
        <h2>My Addresses</h2>
        <div style="width: 40px;"></div>
    </div>
    
    <div class="profile-section-content">
        <div class="error-message" id="addressError"></div>
        <div class="success-message" id="addressSuccess"></div>
        
        <div class="form-section">
            <h3>Delivery Address</h3>
            <div class="input-group">
                <label>Full Address</label>
                <textarea id="deliveryAddress" rows="3" placeholder="Enter complete delivery address"></textarea>
            </div>
            <div class="input-group">
                <label>Contact Number</label>
                <input type="tel" id="deliveryContact" placeholder="09xx xxx xxxx">
            </div>
            <button class="btn-auth btn-primary" onclick="saveAddress()">Save Address</button>
        </div>
    </div>
</div>

<!-- Payment Methods Section -->
<div id="paymentSection" class="profile-section tab-content">
    <div class="top-bar">
        <span onclick="backToProfile()" style="cursor: pointer; font-size: 24px;">←</span>
        <h2>Payment Methods</h2>
        <div style="width: 40px;"></div>
    </div>
    
    <div class="profile-section-content">
        <div class="error-message" id="paymentError"></div>
        <div class="success-message" id="paymentSuccess"></div>
        
        <div class="form-section">
            <h3>Preferred Payment Method</h3>
            <div class="radio-group">
                <div class="radio-option selected" data-value="cod" onclick="selectPreferredPayment(this)">
                    <div class="radio-circle"></div>
                    <div>
                        <div style="font-weight: 600;">Cash on Delivery (COD)</div>
                        <div style="font-size: 12px; color: #999;">Pay when you receive your order</div>
                    </div>
                </div>
                <div class="radio-option" data-value="gcash" onclick="selectPreferredPayment(this)">
                    <div class="radio-circle"></div>
                    <div>
                        <div style="font-weight: 600;">GCash</div>
                        <div style="font-size: 12px; color: #999;">Digital wallet payment</div>
                    </div>
                </div>
                <div class="radio-option" data-value="card" onclick="selectPreferredPayment(this)">
                    <div class="radio-circle"></div>
                    <div>
                        <div style="font-weight: 600;">Credit/Debit Card</div>
                        <div style="font-size: 12px; color: #999;">Pay with your card</div>
                    </div>
                </div>
            </div>
            
            <div id="gcashSection" style="display: none; margin-top: 20px;">
                <div class="input-group">
                    <label>GCash Number</label>
                    <input type="tel" id="gcashNumber" placeholder="09xx xxx xxxx">
                </div>
            </div>
            
            <div id="cardSection" style="display: none; margin-top: 20px;">
                <div class="input-group">
                    <label>Card Number</label>
                    <input type="text" id="cardNumber" placeholder="1234 5678 9012 3456" maxlength="19">
                </div>
                <div style="display: flex; gap: 10px;">
                    <div class="input-group" style="flex: 1;">
                        <label>Expiry Date</label>
                        <input type="text" id="cardExpiry" placeholder="MM/YY" maxlength="5">
                    </div>
                    <div class="input-group" style="flex: 1;">
                        <label>CVV</label>
                        <input type="text" id="cardCVV" placeholder="123" maxlength="3">
                    </div>
                </div>
            </div>
            
            <button class="btn-auth btn-primary" onclick="savePaymentMethod()">Save Payment Method</button>
        </div>
    </div>
</div>

<!-- Settings Section -->
<div id="settingsSection" class="profile-section tab-content">
    <div class="top-bar">
        <span onclick="backToProfile()" style="cursor: pointer; font-size: 24px;">←</span>
        <h2>Settings</h2>
        <div style="width: 40px;"></div>
    </div>
    
    <div class="profile-section-content">
        <div class="error-message" id="settingsError"></div>
        <div class="success-message" id="settingsSuccess"></div>
        
        <!-- Profile Information -->
        <div class="form-section">
            <h3>Profile Information</h3>
            <div class="input-group">
                <label>Full Name</label>
                <input type="text" id="settingsName" placeholder="Enter your name">
            </div>
            <div class="input-group">
                <label>Email Address</label>
                <input type="email" id="settingsEmail" placeholder="Enter your email" disabled style="background: #f5f5f5;">
            </div>
            <button class="btn-auth btn-primary" onclick="updateProfileInfo()">Update Profile</button>
        </div>
        
        <!-- Change Password -->
        <div class="form-section" style="margin-top: 15px;">
            <h3>Change Password</h3>
            <div class="input-group">
                <label>Current Password</label>
                <input type="password" id="currentPassword" placeholder="Enter current password">
            </div>
            <div class="input-group">
                <label>New Password</label>
                <input type="password" id="newPassword" placeholder="Enter new password">
            </div>
            <div class="input-group">
                <label>Confirm New Password</label>
                <input type="password" id="confirmPassword" placeholder="Confirm new password">
            </div>
            <button class="btn-auth btn-primary" onclick="changePassword()">Change Password</button>
        </div>
        
        <!-- Notifications -->
        <div class="form-section" style="margin-top: 15px;">
            <h3>Notifications</h3>
            <div class="setting-item">
                <div>
                    <div style="font-weight: 600; margin-bottom: 5px;">Order Updates</div>
                    <div style="font-size: 12px; color: #999;">Get notified about your order status</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notifOrders" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="setting-item">
                <div>
                    <div style="font-weight: 600; margin-bottom: 5px;">Promotions</div>
                    <div style="font-size: 12px; color: #999;">Receive special offers and discounts</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notifPromos" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <div class="setting-item">
                <div>
                    <div style="font-weight: 600; margin-bottom: 5px;">New Products</div>
                    <div style="font-size: 12px; color: #999;">Stay updated on new bread items</div>
                </div>
                <label class="toggle-switch">
                    <input type="checkbox" id="notifProducts" checked>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <button class="btn-auth btn-primary" onclick="saveNotificationSettings()">Save Settings</button>
        </div>
    </div>
</div>

            <!-- Bottom Navigation -->
            <div class="bottom-nav">
                <div class="nav-item active" onclick="switchTab('browse')">
                    <div class="nav-icon">🏠</div>
                    <div class="nav-label">Browse</div>
                </div>
                <div class="nav-item" onclick="switchTab('favorites')">
                    <div class="nav-icon">❤️</div>
                    <div class="nav-label">Favorites</div>
                </div>
                <div class="nav-item" onclick="switchTab('cart')">
                    <div class="nav-icon">🛒</div>
                    <div class="nav-label">Cart</div>
                </div>
                <div class="nav-item" onclick="switchTab('profile')">
                    <div class="nav-icon">👤</div>
                    <div class="nav-label">Profile</div>
                </div>
            </div>
        </div>

        <!-- Checkout Screen -->
        <div id="checkoutScreen" class="checkout-screen">
            <div class="top-bar">
                <h2>Checkout</h2>
            </div>
            
            <div class="checkout-form">
                <div class="form-section">
                    <h3>Delivery Option</h3>
                    <div class="radio-group" id="deliveryOptions">
                        <div class="radio-option selected" data-value="delivery" onclick="selectDeliveryOption(this)">
                            <div class="radio-circle"></div>
                            <div>
                                <div style="font-weight: 600;">Delivery</div>
                                <div style="font-size: 12px; color: #999;">Delivered to your address</div>
                            </div>
                        </div>
                        <div class="radio-option" data-value="pickup" onclick="selectDeliveryOption(this)">
                            <div class="radio-circle"></div>
                            <div>
                                <div style="font-weight: 600;">Pickup</div>
                                <div style="font-size: 12px; color: #999;">Pick up at bakery</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-section">
                    <h3>Payment Method</h3>
                    <div class="radio-group" id="paymentMethods">
                        <div class="radio-option selected" data-value="cod" onclick="selectPaymentMethod(this)">
                            <div class="radio-circle"></div>
                            <span>Cash on Delivery</span>
                        </div>
                        <div class="radio-option" data-value="gcash" onclick="selectPaymentMethod(this)">
                            <div class="radio-circle"></div>
                            <span>GCash</span>
                        </div>
                        <div class="radio-option" data-value="card" onclick="selectPaymentMethod(this)">
                            <div class="radio-circle"></div>
                            <span>Credit/Debit Card</span>
                        </div>
                    </div>
                </div>
                
                <div class="order-summary-box">
                    <h3 style="margin-bottom: 15px;">Order Summary</h3>
                    <div id="checkoutItems"></div>
                    <div style="border-top: 1px solid #E0E0E0; margin: 15px 0; padding-top: 15px;">
                        <div class="summary-item">
                            <span>Subtotal</span>
                            <span id="checkoutSubtotal">₱0.00</span>
                        </div>
                        <div class="summary-item">
                            <span>Delivery Fee</span>
                            <span id="deliveryFeeAmount">₱20.00</span>
                        </div>
                        <div class="summary-item" style="font-weight: 700; font-size: 16px; color: #FF6B35;">
                            <span>Total</span>
                            <span id="checkoutTotal">₱0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="checkout-fixed-bottom">
                <button class="checkout-btn" id="placeOrderBtn" onclick="placeOrder()">Place Order</button>
                <button class="btn-auth btn-secondary" style="margin-top: 10px;" onclick="backToCart()">Back to Cart</button>
            </div>
        </div>
    </div>

    <script src="js/api.js"></script>
    <script src="js/app.js"></script>
    <script src="js/profile.js"></script>
</body>
</html>