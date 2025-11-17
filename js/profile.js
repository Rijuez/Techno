/**
 * Profile Features JavaScript
 * Address, Payment Methods, and Settings functionality
 */

let userPreferences = {
    payment_method: 'cod',
    gcash_number: '',
    card_number: '',
    card_expiry: '',
    card_cvv: '',
    notifications: {
        orders: true,
        promos: true,
        products: true
    }
};

// Show Profile Sections
function showProfileSection(section) {
    // Hide all profile sections
    document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active'));
    document.getElementById('profileTab').classList.remove('active');
    
    // Show selected section
    const sectionMap = {
        'addresses': 'addressesSection',
        'payment': 'paymentSection',
        'settings': 'settingsSection'
    };
    
    const sectionId = sectionMap[section];
    if (sectionId) {
        document.getElementById(sectionId).classList.add('active');
        
        // Load data for the section
        if (section === 'addresses') {
            loadUserAddress();
        } else if (section === 'payment') {
            loadPaymentMethods();
        } else if (section === 'settings') {
            loadSettings();
        }
    }
}

function backToProfile() {
    document.querySelectorAll('.profile-section').forEach(s => s.classList.remove('active'));
    document.getElementById('profileTab').classList.add('active');
}

// ============================================
// ADDRESS MANAGEMENT
// ============================================

async function loadUserAddress() {
    if (!currentUser) return;
    
    // Load user's current address
    document.getElementById('deliveryAddress').value = currentUser.address || '';
    document.getElementById('deliveryContact').value = currentUser.contact_number || '';
}

async function saveAddress() {
    const address = document.getElementById('deliveryAddress').value.trim();
    const contact = document.getElementById('deliveryContact').value.trim();
    
    if (!address) {
        showMessage('addressError', 'Please enter your delivery address', true);
        return;
    }
    
    if (!contact) {
        showMessage('addressError', 'Please enter your contact number', true);
        return;
    }
    
    // Validate phone number format (Philippine format)
    const phoneRegex = /^(09|\+639)\d{9}$/;
    if (!phoneRegex.test(contact.replace(/\s/g, ''))) {
        showMessage('addressError', 'Please enter a valid Philippine mobile number', true);
        return;
    }
    
    try {
        const result = await UserAPI.updateProfile(currentUser.name, address, contact);
        
        if (result.success) {
            currentUser.address = address;
            currentUser.contact_number = contact;
            showMessage('addressSuccess', 'Address saved successfully!', false);
            
            // Update profile display
            updateProfile();
        } else {
            showMessage('addressError', result.message, true);
        }
    } catch (error) {
        showMessage('addressError', 'Failed to save address. Please try again.', true);
    }
}

// ============================================
// PAYMENT METHODS
// ============================================

function loadPaymentMethods() {
    // Load saved payment preferences from localStorage
    const saved = localStorage.getItem('paymentPreferences');
    if (saved) {
        userPreferences = JSON.parse(saved);
        
        // Set preferred payment method
        document.querySelectorAll('#paymentSection .radio-option').forEach(opt => {
            opt.classList.toggle('selected', opt.dataset.value === userPreferences.payment_method);
        });
        
        // Load saved details
        if (userPreferences.gcash_number) {
            document.getElementById('gcashNumber').value = userPreferences.gcash_number;
        }
        if (userPreferences.card_number) {
            document.getElementById('cardNumber').value = userPreferences.card_number;
            document.getElementById('cardExpiry').value = userPreferences.card_expiry;
            // Don't show CVV for security
        }
        
        // Show relevant section
        showPaymentSection(userPreferences.payment_method);
    }
}

function selectPreferredPayment(element) {
    const parent = element.closest('.radio-group');
    parent.querySelectorAll('.radio-option').forEach(opt => opt.classList.remove('selected'));
    element.classList.add('selected');
    
    const paymentType = element.dataset.value;
    showPaymentSection(paymentType);
}

function showPaymentSection(paymentType) {
    // Hide all payment detail sections
    document.getElementById('gcashSection').style.display = 'none';
    document.getElementById('cardSection').style.display = 'none';
    
    // Show relevant section
    if (paymentType === 'gcash') {
        document.getElementById('gcashSection').style.display = 'block';
    } else if (paymentType === 'card') {
        document.getElementById('cardSection').style.display = 'block';
    }
}

async function savePaymentMethod() {
    const selectedOption = document.querySelector('#paymentSection .radio-option.selected');
    const paymentMethod = selectedOption.dataset.value;
    
    userPreferences.payment_method = paymentMethod;
    
    // Validate and save based on payment method
    if (paymentMethod === 'gcash') {
        const gcashNumber = document.getElementById('gcashNumber').value.trim();
        
        if (!gcashNumber) {
            showMessage('paymentError', 'Please enter your GCash number', true);
            return;
        }
        
        const phoneRegex = /^(09|\+639)\d{9}$/;
        if (!phoneRegex.test(gcashNumber.replace(/\s/g, ''))) {
            showMessage('paymentError', 'Please enter a valid GCash number', true);
            return;
        }
        
        userPreferences.gcash_number = gcashNumber;
        
    } else if (paymentMethod === 'card') {
        const cardNumber = document.getElementById('cardNumber').value.trim().replace(/\s/g, '');
        const cardExpiry = document.getElementById('cardExpiry').value.trim();
        const cardCVV = document.getElementById('cardCVV').value.trim();
        
        if (!cardNumber || !cardExpiry || !cardCVV) {
            showMessage('paymentError', 'Please fill in all card details', true);
            return;
        }
        
        // Basic card number validation (13-19 digits)
        if (!/^\d{13,19}$/.test(cardNumber)) {
            showMessage('paymentError', 'Please enter a valid card number', true);
            return;
        }
        
        // Expiry validation (MM/YY format)
        if (!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
            showMessage('paymentError', 'Expiry must be in MM/YY format', true);
            return;
        }
        
        // CVV validation (3-4 digits)
        if (!/^\d{3,4}$/.test(cardCVV)) {
            showMessage('paymentError', 'Please enter a valid CVV', true);
            return;
        }
        
        // Mask card number (show only last 4 digits)
        const maskedCard = '•••• •••• •••• ' + cardNumber.slice(-4);
        userPreferences.card_number = maskedCard;
        userPreferences.card_expiry = cardExpiry;
        // Don't save CVV for security
    }
    
    // Save to localStorage
    localStorage.setItem('paymentPreferences', JSON.stringify(userPreferences));
    
    showMessage('paymentSuccess', 'Payment method saved successfully!', false);
}

// Format card number input
document.addEventListener('DOMContentLoaded', () => {
    const cardNumberInput = document.getElementById('cardNumber');
    if (cardNumberInput) {
        cardNumberInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\s/g, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    }
    
    const expiryInput = document.getElementById('cardExpiry');
    if (expiryInput) {
        expiryInput.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.slice(0, 2) + '/' + value.slice(2, 4);
            }
            e.target.value = value;
        });
    }
});

// ============================================
// SETTINGS
// ============================================

async function loadSettings() {
    if (!currentUser) return;
    
    // Load user profile info
    document.getElementById('settingsName').value = currentUser.name || '';
    document.getElementById('settingsEmail').value = currentUser.email || '';
    
    // Load notification preferences
    const saved = localStorage.getItem('notificationPreferences');
    if (saved) {
        const prefs = JSON.parse(saved);
        document.getElementById('notifOrders').checked = prefs.orders;
        document.getElementById('notifPromos').checked = prefs.promos;
        document.getElementById('notifProducts').checked = prefs.products;
    }
}

async function updateProfileInfo() {
    const name = document.getElementById('settingsName').value.trim();
    
    if (!name) {
        showMessage('settingsError', 'Please enter your name', true);
        return;
    }
    
    try {
        const result = await UserAPI.updateProfile(
            name,
            currentUser.address || '',
            currentUser.contact_number || ''
        );
        
        if (result.success) {
            currentUser.name = name;
            showMessage('settingsSuccess', 'Profile updated successfully!', false);
            updateProfile();
        } else {
            showMessage('settingsError', result.message, true);
        }
    } catch (error) {
        showMessage('settingsError', 'Failed to update profile. Please try again.', true);
    }
}

async function changePassword() {
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!currentPassword || !newPassword || !confirmPassword) {
        showMessage('settingsError', 'Please fill in all password fields', true);
        return;
    }
    
    if (newPassword.length < 6) {
        showMessage('settingsError', 'New password must be at least 6 characters', true);
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showMessage('settingsError', 'New passwords do not match', true);
        return;
    }
    
    try {
        const response = await fetch('api/index.php?action=change_password', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                current_password: currentPassword,
                new_password: newPassword
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('settingsSuccess', 'Password changed successfully!', false);
            
            // Clear password fields
            document.getElementById('currentPassword').value = '';
            document.getElementById('newPassword').value = '';
            document.getElementById('confirmPassword').value = '';
        } else {
            showMessage('settingsError', result.message, true);
        }
    } catch (error) {
        showMessage('settingsError', 'Failed to change password. Please try again.', true);
    }
}

function saveNotificationSettings() {
    const preferences = {
        orders: document.getElementById('notifOrders').checked,
        promos: document.getElementById('notifPromos').checked,
        products: document.getElementById('notifProducts').checked
    };
    
    localStorage.setItem('notificationPreferences', JSON.stringify(preferences));
    showMessage('settingsSuccess', 'Notification settings saved!', false);
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function formatPhoneNumber(phone) {
    // Format as 09XX XXX XXXX
    const cleaned = phone.replace(/\D/g, '');
    if (cleaned.length === 11) {
        return cleaned.replace(/(\d{4})(\d{3})(\d{4})/, '$1 $2 $3');
    }
    return phone;
}

function maskCardNumber(cardNumber) {
    if (cardNumber.length < 4) return cardNumber;
    return '•••• •••• •••• ' + cardNumber.slice(-4);
}

// Export functions for use in main app
window.showProfileSection = showProfileSection;
window.backToProfile = backToProfile;
window.saveAddress = saveAddress;
window.selectPreferredPayment = selectPreferredPayment;
window.savePaymentMethod = savePaymentMethod;
window.updateProfileInfo = updateProfileInfo;
window.changePassword = changePassword;
window.saveNotificationSettings = saveNotificationSettings;