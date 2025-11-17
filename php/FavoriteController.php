<?php
/**
 * Favorite Controller
 * Handles favorite products operations
 */

class FavoriteController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get user's favorite products
     */
    public function getFavorites() {
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
                        f.favorite_id,
                        p.product_id,
                        p.name,
                        p.description,
                        p.original_price,
                        p.discounted_price,
                        p.discount_percentage,
                        p.emoji,
                        p.stock_quantity,
                        p.is_available,
                        b.name as bakery_name,
                        c.name as category_name
                      FROM favorites f
                      JOIN products p ON f.product_id = p.product_id
                      JOIN bakeries b ON p.bakery_id = b.bakery_id
                      JOIN categories c ON p.category_id = c.category_id
                      WHERE f.user_id = :user_id
                      ORDER BY f.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $favorites = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'favorites' => $favorites
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching favorites: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Add product to favorites
     */
    public function addFavorite() {
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
            // Check if already favorited
            $checkQuery = "SELECT favorite_id FROM favorites 
                          WHERE user_id = :user_id AND product_id = :product_id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->bindParam(':product_id', $productId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product already in favorites'
                ]);
                return;
            }
            
            // Add to favorites
            $query = "INSERT INTO favorites (user_id, product_id) 
                     VALUES (:user_id, :product_id)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Added to favorites'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add to favorites'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error adding to favorites: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Remove product from favorites
     */
    public function removeFavorite() {
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
            $query = "DELETE FROM favorites 
                     WHERE user_id = :user_id AND product_id = :product_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Removed from favorites'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to remove from favorites'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error removing from favorites: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Check if product is in favorites
     */
    public function isFavorite() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        if (!isset($_GET['product_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $productId = $_GET['product_id'];
        
        try {
            $query = "SELECT favorite_id FROM favorites 
                     WHERE user_id = :user_id AND product_id = :product_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            
            $isFavorite = $stmt->rowCount() > 0;
            
            echo json_encode([
                'success' => true,
                'is_favorite' => $isFavorite
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error checking favorite status: ' . $e->getMessage()
            ]);
        }
    }
}
?>