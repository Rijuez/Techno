<?php
/**
 * API Router
 * Handles all API requests for DoughMain application
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../controllers/AuthController.php';
require_once '../controllers/ProductController.php';
require_once '../controllers/CartController.php';
require_once '../controllers/OrderController.php';
require_once '../controllers/FavoriteController.php';
require_once '../controllers/UserController.php';

// Get request method and endpoint
$method = $_SERVER['REQUEST_METHOD'];
$request = $_SERVER['REQUEST_URI'];
$params = explode('/', trim(parse_url($request, PHP_URL_PATH), '/'));

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
            $controller = new AuthController($db);
            $controller->login();
            break;
            
        case 'register':
            $controller = new AuthController($db);
            $controller->register();
            break;
            
        case 'logout':
            $controller = new AuthController($db);
            $controller->logout();
            break;
            
        // Product endpoints
        case 'products':
            $controller = new ProductController($db);
            $controller->getAllProducts();
            break;
            
        case 'product':
            $controller = new ProductController($db);
            $controller->getProduct();
            break;
            
        case 'search':
            $controller = new ProductController($db);
            $controller->searchProducts();
            break;
            
        case 'categories':
            $controller = new ProductController($db);
            $controller->getCategories();
            break;
            
        // Cart endpoints
        case 'cart':
            $controller = new CartController($db);
            if ($method === 'GET') {
                $controller->getCart();
            }
            break;
            
        case 'cart_add':
            $controller = new CartController($db);
            $controller->addToCart();
            break;
            
        case 'cart_update':
            $controller = new CartController($db);
            $controller->updateCart();
            break;
            
        case 'cart_remove':
            $controller = new CartController($db);
            $controller->removeFromCart();
            break;
            
        case 'cart_clear':
            $controller = new CartController($db);
            $controller->clearCart();
            break;
            
        // Order endpoints
        case 'orders':
            $controller = new OrderController($db);
            $controller->getUserOrders();
            break;
            
        case 'order':
            $controller = new OrderController($db);
            $controller->getOrder();
            break;
            
        case 'create_order':
            $controller = new OrderController($db);
            $controller->createOrder();
            break;
            
        // Favorite endpoints
        case 'favorites':
            $controller = new FavoriteController($db);
            $controller->getFavorites();
            break;
            
        case 'favorite_add':
            $controller = new FavoriteController($db);
            $controller->addFavorite();
            break;
            
        case 'favorite_remove':
            $controller = new FavoriteController($db);
            $controller->removeFavorite();
            break;
            
        // User endpoints
        case 'user':
            $controller = new UserController($db);
            $controller->getUserProfile();
            break;
            
        case 'user_update':
            $controller = new UserController($db);
            $controller->updateProfile();
            break;
            
        case 'change_password':
            $controller = new UserController($db);
            $controller->changePassword();
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