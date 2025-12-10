<?php
/**
 * Bakery API Router
 * Handles all API requests for bakery management
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../controllers/BakeryAuthController.php';
require_once '../controllers/BakeryProductController.php';

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];

// Get the action from URL
$endpoint = isset($_GET['action']) ? $_GET['action'] : '';

// Handle OPTIONS request for CORS
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Initialize database
$database = new Database();
$db = $database->getConnection();

// Route requests to appropriate controllers
try {
    switch ($endpoint) {
        // Authentication endpoints
        case 'login':
            $controller = new BakeryAuthController($db);
            $controller->login();
            break;
            
        case 'register':
            $controller = new BakeryAuthController($db);
            $controller->register();
            break;
            
        case 'logout':
            $controller = new BakeryAuthController($db);
            $controller->logout();
            break;
            
        case 'profile':
            $controller = new BakeryAuthController($db);
            $controller->getProfile();
            break;
            
        case 'profile_update':
            $controller = new BakeryAuthController($db);
            $controller->updateProfile();
            break;
            
        // Product endpoints
        case 'products':
            $controller = new BakeryProductController($db);
            $controller->getMyProducts();
            break;
            
        case 'product_add':
            $controller = new BakeryProductController($db);
            $controller->addProduct();
            break;
            
        case 'product_update':
            $controller = new BakeryProductController($db);
            $controller->updateProduct();
            break;
            
        case 'product_delete':
            $controller = new BakeryProductController($db);
            $controller->deleteProduct();
            break;
            
        case 'product_image':
            $controller = new BakeryProductController($db);
            $controller->uploadImage();
            break;
            
        case 'dashboard':
            $controller = new BakeryProductController($db);
            $controller->getDashboard();
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid endpoint'
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}

$database->closeConnection();
?>