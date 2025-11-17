<?php
/**
 * Product Controller
 * Handles product-related operations
 */

class ProductController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all available products
     */
    public function getAllProducts() {
        try {
            $query = "SELECT 
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
                        b.address as bakery_address,
                        c.name as category_name
                      FROM products p
                      JOIN bakeries b ON p.bakery_id = b.bakery_id
                      JOIN categories c ON p.category_id = c.category_id
                      WHERE p.is_available = TRUE
                      ORDER BY p.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $products = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'products' => $products
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching products: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get single product
     */
    public function getProduct() {
        if (!isset($_GET['id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
            return;
        }
        
        $productId = $_GET['id'];
        
        try {
            $query = "SELECT 
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
                        b.address as bakery_address,
                        b.contact_number as bakery_contact,
                        c.name as category_name
                      FROM products p
                      JOIN bakeries b ON p.bakery_id = b.bakery_id
                      JOIN categories c ON p.category_id = c.category_id
                      WHERE p.product_id = :product_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':product_id', $productId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $product = $stmt->fetch();
                echo json_encode([
                    'success' => true,
                    'product' => $product
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product not found'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching product: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Search products
     */
    public function searchProducts() {
        if (!isset($_GET['query'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Search query is required'
            ]);
            return;
        }
        
        $searchQuery = '%' . $_GET['query'] . '%';
        
        try {
            $query = "SELECT 
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
                      FROM products p
                      JOIN bakeries b ON p.bakery_id = b.bakery_id
                      JOIN categories c ON p.category_id = c.category_id
                      WHERE p.is_available = TRUE 
                      AND (p.name LIKE :search OR p.description LIKE :search)
                      ORDER BY p.name";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':search', $searchQuery);
            $stmt->execute();
            
            $products = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'products' => $products
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Search error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get all categories
     */
    public function getCategories() {
        try {
            $query = "SELECT category_id, name, description 
                     FROM categories 
                     ORDER BY display_order";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $categories = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'categories' => $categories
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching categories: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get products by category
     */
    public function getProductsByCategory() {
        if (!isset($_GET['category_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Category ID is required'
            ]);
            return;
        }
        
        $categoryId = $_GET['category_id'];
        
        try {
            $query = "SELECT 
                        p.product_id,
                        p.name,
                        p.description,
                        p.original_price,
                        p.discounted_price,
                        p.discount_percentage,
                        p.emoji,
                        p.stock_quantity,
                        b.name as bakery_name,
                        c.name as category_name
                      FROM products p
                      JOIN bakeries b ON p.bakery_id = b.bakery_id
                      JOIN categories c ON p.category_id = c.category_id
                      WHERE p.is_available = TRUE 
                      AND p.category_id = :category_id
                      ORDER BY p.name";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':category_id', $categoryId);
            $stmt->execute();
            
            $products = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'products' => $products
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching products: ' . $e->getMessage()
            ]);
        }
    }
}
?>