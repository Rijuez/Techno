<?php
/**
 * Bakery Product Controller
 * Handles product management for bakery users
 */

class BakeryProductController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get all products for logged-in bakery
     */
    public function getMyProducts() {
        session_start();
        
        if (!isset($_SESSION['bakery_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Not logged in'
            ]);
            return;
        }
        
        $bakeryId = $_SESSION['bakery_id'];
        
        try {
            $query = "SELECT 
                        p.product_id,
                        p.name,
                        p.description,
                        p.original_price,
                        p.discounted_price,
                        p.discount_percentage,
                        p.image_url,
                        p.stock_quantity,
                        p.expiry_date,
                        p.is_available,
                        p.is_on_sale,
                        p.sale_start_date,
                        p.sale_end_date,
                        p.created_at,
                        p.updated_at,
                        c.name as category_name,
                        c.category_id
                      FROM products p
                      JOIN categories c ON p.category_id = c.category_id
                      WHERE p.bakery_id = :bakery_id
                      ORDER BY p.created_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':bakery_id', $bakeryId);
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
     * Add new product
     */
    public function addProduct() {
        session_start();
        
        if (!isset($_SESSION['bakery_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Not logged in'
            ]);
            return;
        }
        
        $bakeryId = $_SESSION['bakery_id'];
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        if (!isset($data['name']) || !isset($data['category_id']) || !isset($data['original_price'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Name, category, and original price are required'
            ]);
            return;
        }
        
        try {
            // Calculate discounted price and discount percentage
            $originalPrice = floatval($data['original_price']);
            $discountedPrice = isset($data['discounted_price']) ? floatval($data['discounted_price']) : $originalPrice;
            $discountPercentage = 0;
            
            if ($discountedPrice < $originalPrice) {
                $discountPercentage = round((($originalPrice - $discountedPrice) / $originalPrice) * 100);
            }
            
            $query = "INSERT INTO products (
                        bakery_id, category_id, name, description, 
                        original_price, discounted_price, discount_percentage,
                        stock_quantity, expiry_date, is_available, is_on_sale,
                        sale_start_date, sale_end_date
                      ) VALUES (
                        :bakery_id, :category_id, :name, :description,
                        :original_price, :discounted_price, :discount_percentage,
                        :stock_quantity, :expiry_date, :is_available, :is_on_sale,
                        :sale_start_date, :sale_end_date
                      )";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':bakery_id', $bakeryId);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':original_price', $originalPrice);
            $stmt->bindParam(':discounted_price', $discountedPrice);
            $stmt->bindParam(':discount_percentage', $discountPercentage);
            
            $stockQuantity = isset($data['stock_quantity']) ? intval($data['stock_quantity']) : 0;
            $stmt->bindParam(':stock_quantity', $stockQuantity);
            
            $expiryDate = isset($data['expiry_date']) ? $data['expiry_date'] : null;
            $stmt->bindParam(':expiry_date', $expiryDate);
            
            $isAvailable = isset($data['is_available']) ? intval($data['is_available']) : 1;
            $stmt->bindParam(':is_available', $isAvailable);
            
            $isOnSale = isset($data['is_on_sale']) ? intval($data['is_on_sale']) : 0;
            $stmt->bindParam(':is_on_sale', $isOnSale);
            
            $saleStartDate = isset($data['sale_start_date']) ? $data['sale_start_date'] : null;
            $stmt->bindParam(':sale_start_date', $saleStartDate);
            
            $saleEndDate = isset($data['sale_end_date']) ? $data['sale_end_date'] : null;
            $stmt->bindParam(':sale_end_date', $saleEndDate);
            
            if ($stmt->execute()) {
                $productId = $this->db->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added successfully',
                    'product_id' => $productId
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to add product'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error adding product: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update product
     */
    public function updateProduct() {
        session_start();
        
        if (!isset($_SESSION['bakery_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Not logged in'
            ]);
            return;
        }
        
        $bakeryId = $_SESSION['bakery_id'];
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['product_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
            return;
        }
        
        try {
            // Verify product belongs to bakery
            $checkQuery = "SELECT product_id FROM products WHERE product_id = :product_id AND bakery_id = :bakery_id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':product_id', $data['product_id']);
            $checkStmt->bindParam(':bakery_id', $bakeryId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product not found or unauthorized'
                ]);
                return;
            }
            
            // Calculate discount percentage
            $originalPrice = floatval($data['original_price']);
            $discountedPrice = floatval($data['discounted_price']);
            $discountPercentage = 0;
            
            if ($discountedPrice < $originalPrice) {
                $discountPercentage = round((($originalPrice - $discountedPrice) / $originalPrice) * 100);
            }
            
            $query = "UPDATE products SET
                        category_id = :category_id,
                        name = :name,
                        description = :description,
                        original_price = :original_price,
                        discounted_price = :discounted_price,
                        discount_percentage = :discount_percentage,
                        stock_quantity = :stock_quantity,
                        expiry_date = :expiry_date,
                        is_available = :is_available,
                        is_on_sale = :is_on_sale,
                        sale_start_date = :sale_start_date,
                        sale_end_date = :sale_end_date
                      WHERE product_id = :product_id AND bakery_id = :bakery_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':category_id', $data['category_id']);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':original_price', $originalPrice);
            $stmt->bindParam(':discounted_price', $discountedPrice);
            $stmt->bindParam(':discount_percentage', $discountPercentage);
            $stmt->bindParam(':stock_quantity', $data['stock_quantity']);
            $stmt->bindParam(':expiry_date', $data['expiry_date']);
            $stmt->bindParam(':is_available', $data['is_available']);
            $stmt->bindParam(':is_on_sale', $data['is_on_sale']);
            $stmt->bindParam(':sale_start_date', $data['sale_start_date']);
            $stmt->bindParam(':sale_end_date', $data['sale_end_date']);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->bindParam(':bakery_id', $bakeryId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update product'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating product: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Delete product
     */
    public function deleteProduct() {
        session_start();
        
        if (!isset($_SESSION['bakery_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Not logged in'
            ]);
            return;
        }
        
        $bakeryId = $_SESSION['bakery_id'];
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['product_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID is required'
            ]);
            return;
        }
        
        try {
            $query = "DELETE FROM products WHERE product_id = :product_id AND bakery_id = :bakery_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':product_id', $data['product_id']);
            $stmt->bindParam(':bakery_id', $bakeryId);
            
            if ($stmt->execute()) {
                if ($stmt->rowCount() > 0) {
                    echo json_encode([
                        'success' => true,
                        'message' => 'Product deleted successfully'
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Product not found'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to delete product'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error deleting product: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Upload product image
     */
    public function uploadImage() {
        session_start();
        
        if (!isset($_SESSION['bakery_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Not logged in'
            ]);
            return;
        }
        
        $bakeryId = $_SESSION['bakery_id'];
        
        if (!isset($_POST['product_id']) || !isset($_FILES['image'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Product ID and image are required'
            ]);
            return;
        }
        
        $productId = $_POST['product_id'];
        
        try {
            // Verify product belongs to bakery
            $checkQuery = "SELECT product_id FROM products WHERE product_id = :product_id AND bakery_id = :bakery_id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':product_id', $productId);
            $checkStmt->bindParam(':bakery_id', $bakeryId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product not found or unauthorized'
                ]);
                return;
            }
            
            $file = $_FILES['image'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileSize = $file['size'];
            $fileError = $file['error'];
            
            // Get file extension
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($fileExt, $allowed)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp'
                ]);
                return;
            }
            
            if ($fileError !== 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Error uploading file'
                ]);
                return;
            }
            
            if ($fileSize > 5000000) { // 5MB max
                echo json_encode([
                    'success' => false,
                    'message' => 'File too large. Maximum size is 5MB'
                ]);
                return;
            }
            
            // Create upload directory if it doesn't exist
            $uploadDir = '../uploads/products/';
            if (!file_exists($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            // Generate unique filename
            $newFileName = 'product_' . $productId . '_' . time() . '.' . $fileExt;
            $destination = $uploadDir . $newFileName;
            
            if (move_uploaded_file($fileTmpName, $destination)) {
                // Update product image URL
                $imageUrl = 'uploads/products/' . $newFileName;
                
                $updateQuery = "UPDATE products SET image_url = :image_url WHERE product_id = :product_id";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindParam(':image_url', $imageUrl);
                $updateStmt->bindParam(':product_id', $productId);
                $updateStmt->execute();
                
                // Log activity
                $logQuery = "INSERT INTO bakery_activity_log (bakery_id, action_type, action_details) 
                            VALUES (:bakery_id, 'image_upload', :details)";
                $logStmt = $this->db->prepare($logQuery);
                $logStmt->bindParam(':bakery_id', $bakeryId);
                $details = "Uploaded image for product ID: " . $productId;
                $logStmt->bindParam(':details', $details);
                $logStmt->execute();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Image uploaded successfully',
                    'image_url' => $imageUrl
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to move uploaded file'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error uploading image: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get dashboard statistics
     */
    public function getDashboard() {
        session_start();
        
        if (!isset($_SESSION['bakery_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Not logged in'
            ]);
            return;
        }
        
        $bakeryId = $_SESSION['bakery_id'];
        
        try {
            $query = "SELECT * FROM vw_bakery_dashboard WHERE bakery_id = :bakery_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':bakery_id', $bakeryId);
            $stmt->execute();
            
            $dashboard = $stmt->fetch();
            
            echo json_encode([
                'success' => true,
                'dashboard' => $dashboard
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching dashboard: ' . $e->getMessage()
            ]);
        }
    }
}
?>