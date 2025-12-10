<?php
/**
 * Bakery Authentication Controller
 * Handles bakery user login, registration, and session management
 */

class BakeryAuthController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Bakery login
     */
    public function login() {
        session_start();
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['username']) || !isset($data['password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Username and password are required'
            ]);
            return;
        }
        
        $username = $data['username'];
        $password = $data['password'];
        
        try {
            // Use stored procedure for login
            $query = "CALL sp_bakery_login(:username, :password, @bakery_id, @success, @message)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->execute();
            
            // Get output parameters
            $result = $this->db->query("SELECT @bakery_id as bakery_id, @success as success, @message as message")->fetch();
            
            if ($result['success']) {
                // Get bakery details
                $bakeryQuery = "SELECT bakery_id, name, username, email, address, contact_number, description, opening_hours, logo_image 
                               FROM bakeries WHERE bakery_id = :bakery_id";
                $bakeryStmt = $this->db->prepare($bakeryQuery);
                $bakeryStmt->bindParam(':bakery_id', $result['bakery_id']);
                $bakeryStmt->execute();
                $bakery = $bakeryStmt->fetch();
                
                // Set session
                $_SESSION['bakery_id'] = $bakery['bakery_id'];
                $_SESSION['bakery_name'] = $bakery['name'];
                $_SESSION['bakery_username'] = $bakery['username'];
                $_SESSION['user_type'] = 'bakery';
                
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'bakery' => $bakery
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => $result['message']
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Login error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Bakery registration
     */
    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        if (!isset($data['name']) || !isset($data['username']) || !isset($data['password']) || !isset($data['email'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Name, username, email, and password are required'
            ]);
            return;
        }
        
        $name = $data['name'];
        $username = $data['username'];
        $password = $data['password'];
        $email = $data['email'];
        $address = isset($data['address']) ? $data['address'] : '';
        $contact = isset($data['contact_number']) ? $data['contact_number'] : '';
        $description = isset($data['description']) ? $data['description'] : '';
        $openingHours = isset($data['opening_hours']) ? $data['opening_hours'] : '';
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email format'
            ]);
            return;
        }
        
        try {
            // Check if username already exists
            $checkQuery = "SELECT bakery_id FROM bakeries WHERE username = :username";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':username', $username);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Username already exists'
                ]);
                return;
            }
            
            // Check if email already exists
            $emailCheckQuery = "SELECT bakery_id FROM bakeries WHERE email = :email";
            $emailCheckStmt = $this->db->prepare($emailCheckQuery);
            $emailCheckStmt->bindParam(':email', $email);
            $emailCheckStmt->execute();
            
            if ($emailCheckStmt->rowCount() > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Email already exists'
                ]);
                return;
            }
            
            // Insert new bakery (awaiting verification)
            $query = "INSERT INTO bakeries (name, username, password, email, address, contact_number, description, opening_hours, is_verified) 
                     VALUES (:name, :username, :password, :email, :address, :contact, :description, :opening_hours, 0)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':contact', $contact);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':opening_hours', $openingHours);
            
            if ($stmt->execute()) {
                $bakeryId = $this->db->lastInsertId();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful! Your account is pending verification.',
                    'bakery_id' => $bakeryId
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Registration failed'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Registration error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Bakery logout
     */
    public function logout() {
        session_start();
        session_destroy();
        
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
    
    /**
     * Get bakery profile
     */
    public function getProfile() {
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
            $query = "SELECT bakery_id, name, username, email, address, contact_number, description, opening_hours, logo_image, rating, created_at 
                     FROM bakeries WHERE bakery_id = :bakery_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':bakery_id', $bakeryId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $bakery = $stmt->fetch();
                echo json_encode([
                    'success' => true,
                    'bakery' => $bakery
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Bakery not found'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching profile: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update bakery profile
     */
    public function updateProfile() {
        session_start();
        
        if (!isset($_SESSION['bakery_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Not logged in'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        $bakeryId = $_SESSION['bakery_id'];
        
        try {
            $query = "UPDATE bakeries 
                     SET name = :name, 
                         email = :email,
                         address = :address, 
                         contact_number = :contact,
                         description = :description,
                         opening_hours = :opening_hours
                     WHERE bakery_id = :bakery_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $data['name']);
            $stmt->bindParam(':email', $data['email']);
            $stmt->bindParam(':address', $data['address']);
            $stmt->bindParam(':contact', $data['contact_number']);
            $stmt->bindParam(':description', $data['description']);
            $stmt->bindParam(':opening_hours', $data['opening_hours']);
            $stmt->bindParam(':bakery_id', $bakeryId);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to update profile'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error updating profile: ' . $e->getMessage()
            ]);
        }
    }
}
?>