<?php
/**
 * Order Controller
 * Handles order creation and management
 */

class OrderController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Create new order
     */
    public function createOrder() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        if (!isset($data['delivery_option']) || !isset($data['payment_method'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Delivery option and payment method are required'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $deliveryOption = $data['delivery_option'];
        $paymentMethod = $data['payment_method'];
        $deliveryAddress = isset($data['delivery_address']) ? $data['delivery_address'] : '';
        $contactNumber = isset($data['contact_number']) ? $data['contact_number'] : '';
        $notes = isset($data['notes']) ? $data['notes'] : '';
        
        try {
            // Use stored procedure to create order
            $query = "CALL sp_create_order(
                :user_id, 
                :delivery_option, 
                :payment_method, 
                :delivery_address, 
                :contact_number,
                @order_id
            )";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':delivery_option', $deliveryOption);
            $stmt->bindParam(':payment_method', $paymentMethod);
            $stmt->bindParam(':delivery_address', $deliveryAddress);
            $stmt->bindParam(':contact_number', $contactNumber);
            $stmt->execute();
            
            // Get the order_id from output parameter
            $result = $this->db->query("SELECT @order_id as order_id")->fetch();
            $orderId = $result['order_id'];
            
            // Add notes if provided
            if (!empty($notes)) {
                $updateQuery = "UPDATE orders SET notes = :notes WHERE order_id = :order_id";
                $updateStmt = $this->db->prepare($updateQuery);
                $updateStmt->bindParam(':notes', $notes);
                $updateStmt->bindParam(':order_id', $orderId);
                $updateStmt->execute();
            }
            
            // Get order details
            $orderQuery = "SELECT order_id, order_number, total_amount, order_status 
                          FROM orders WHERE order_id = :order_id";
            $orderStmt = $this->db->prepare($orderQuery);
            $orderStmt->bindParam(':order_id', $orderId);
            $orderStmt->execute();
            $order = $orderStmt->fetch();
            
            echo json_encode([
                'success' => true,
                'message' => 'Order created successfully',
                'order' => $order
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error creating order: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get user's orders
     */
    public function getUserOrders() {
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
                        o.order_id,
                        o.order_number,
                        o.subtotal,
                        o.delivery_fee,
                        o.total_amount,
                        o.delivery_option,
                        o.payment_method,
                        o.payment_status,
                        o.order_status,
                        o.ordered_at,
                        COUNT(oi.order_item_id) as total_items
                      FROM orders o
                      LEFT JOIN order_items oi ON o.order_id = oi.order_id
                      WHERE o.user_id = :user_id
                      GROUP BY o.order_id
                      ORDER BY o.ordered_at DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $orders = $stmt->fetchAll();
            
            echo json_encode([
                'success' => true,
                'orders' => $orders
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching orders: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get single order details
     */
    public function getOrder() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        if (!isset($_GET['order_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Order ID is required'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $orderId = $_GET['order_id'];
        
        try {
            // Get order details
            $orderQuery = "SELECT 
                            o.order_id,
                            o.order_number,
                            o.subtotal,
                            o.delivery_fee,
                            o.total_amount,
                            o.delivery_option,
                            o.payment_method,
                            o.payment_status,
                            o.order_status,
                            o.delivery_address,
                            o.contact_number,
                            o.notes,
                            o.ordered_at,
                            o.completed_at
                          FROM orders o
                          WHERE o.order_id = :order_id AND o.user_id = :user_id";
            
            $orderStmt = $this->db->prepare($orderQuery);
            $orderStmt->bindParam(':order_id', $orderId);
            $orderStmt->bindParam(':user_id', $userId);
            $orderStmt->execute();
            
            if ($orderStmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
                return;
            }
            
            $order = $orderStmt->fetch();
            
            // Get order items
            $itemsQuery = "SELECT 
                            oi.order_item_id,
                            oi.quantity,
                            oi.unit_price,
                            oi.subtotal,
                            p.name as product_name,
                            p.emoji,
                            b.name as bakery_name
                          FROM order_items oi
                          JOIN products p ON oi.product_id = p.product_id
                          JOIN bakeries b ON p.bakery_id = b.bakery_id
                          WHERE oi.order_id = :order_id";
            
            $itemsStmt = $this->db->prepare($itemsQuery);
            $itemsStmt->bindParam(':order_id', $orderId);
            $itemsStmt->execute();
            
            $items = $itemsStmt->fetchAll();
            
            $order['items'] = $items;
            
            echo json_encode([
                'success' => true,
                'order' => $order
            ]);
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching order: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Cancel order
     */
    public function cancelOrder() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['order_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Order ID is required'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $orderId = $data['order_id'];
        
        try {
            // Check if order belongs to user and can be cancelled
            $checkQuery = "SELECT order_status FROM orders 
                          WHERE order_id = :order_id AND user_id = :user_id";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':order_id', $orderId);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
                return;
            }
            
            $order = $checkStmt->fetch();
            
            if (in_array($order['order_status'], ['completed', 'cancelled'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order cannot be cancelled'
                ]);
                return;
            }
            
            // Cancel order
            $query = "UPDATE orders SET order_status = 'cancelled' 
                     WHERE order_id = :order_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':order_id', $orderId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Order cancelled successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to cancel order'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error cancelling order: ' . $e->getMessage()
            ]);
        }
    }
}
?>