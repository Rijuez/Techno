<?php
/**
 * Cart Controller
 * Handles shopping cart operations
 */

class CartController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get user's cart
     */
    public function getCart() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        try {
            $query = "SELECT 
                        c.cart_id,
                        c.quantity,
                        p.product_id,
                        p.name,
                        p.discounted_price,
                        p.emoji,
                        b.name as bakery_name,
                        (p.discounted_price * c.quantity) as subtotal
                      FROM cart c
                      JOIN products p ON c.product_id = p.product_id
                      JOIN bakeries b ON p.bakery_id = b.bakery_id
                      WHERE c.user_id = :user_id
                      ORDER BY c.added_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $cartItems = $stmt->fetchAll();
            
            // Calculate total
            $total = 0;
            foreach ($cartItems as $item) {
                $total += $item['subtotal'];
            }
            
            echo json_encode([
                'success' => true,
                'cart' => $cartItems,
                'total' => $total
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching cart: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Add item to cart
     */
    public function addToCart() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['product_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $productId = $data['product_id'];
        $quantity = isset($data['quantity']) ? $data['quantity'] : 1;
        
        try {
            // Check if product exists and is available
            $checkQuery = "SELECT stock_quantity, is_available FROM products WHERE product_id = :product_id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':product_id', $productId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product not found'
                ]);
                return;
            }
            
            $product = $checkStmt->fetch();
            
            if (!$product['is_available']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product is not available'
                ]);
                return;
            }
            
            if ($product['stock_quantity'] < $quantity) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Insufficient stock'
                ]);
                return;
            }
            
            // Use stored procedure to add to cart
            $query = "CALL sp_add_to_cart(:user_id, :product_id, :quantity)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'Item added to cart'
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error adding to cart: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update cart item quantity
     */
    public function updateCart() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['product_id']) || !isset($data['quantity'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID and quantity are required'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $productId = $data['product_id'];
        $quantity = $data['quantity'];
        
        if ($quantity < 1) {
            echo json_encode([
                'success' => false,
                'message' => 'Quantity must be at least 1'
            ]);
            return;
        }
        
        try {
            $query = "UPDATE cart 
                     SET quantity = :quantity, updated_at = CURRENT_TIMESTAMP
                     WHERE user_id = :user_id AND product_id = :product_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart updated'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update cart'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating cart: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Remove item from cart
     */
    public function removeFromCart() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['product_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $productId = $data['product_id'];
        
        try {
            $query = "DELETE FROM cart WHERE user_id = :user_id AND product_id = :product_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Item removed from cart'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to remove item'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error removing from cart: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Clear entire cart
     */
    public function clearCart() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        try {
            $query = "DELETE FROM cart WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Cart cleared'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to clear cart'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error clearing cart: ' . $e->getMessage()
            ]);
        }
    }
}
?>