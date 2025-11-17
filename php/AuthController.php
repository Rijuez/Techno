<?php
/**
 * Authentication Controller
 * Handles user login, registration, and logout
 */

class AuthController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * User login
     */
    public function login() {
        session_start();
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Email and password are required'
            ]);
            return;
        }
        
        $email = $data['email'];
        $password = $data['password'];
        
        try {
            $query = "SELECT user_id, name, email, password, address, contact_number, is_active 
                     FROM users WHERE email = :email";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                
                if (!$user['is_active']) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Account is deactivated'
                    ]);
                    return;
                }
                
                // Verify password
                if (password_verify($password, $user['password'])) {
                    // Remove password from response
                    unset($user['password']);
                    
                    // Set session
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Login successful',
                        'user' => $user
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invalid email or password'
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invalid email or password'
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
     * User registration
     */
    public function register() {
        $data = json_decode(file_get_contents("php://input"), true);
        
        // Validate required fields
        if (!isset($data['name']) || !isset($data['email']) || !isset($data['password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Name, email, and password are required'
            ]);
            return;
        }
        
        $name = $data['name'];
        $email = $data['email'];
        $password = $data['password'];
        $address = isset($data['address']) ? $data['address'] : '';
        $contact = isset($data['contact']) ? $data['contact'] : '';
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email format'
            ]);
            return;
        }
        
        try {
            // Check if email already exists
            $checkQuery = "SELECT user_id FROM users WHERE email = :email";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->bindParam(':email', $email);
            $checkStmt->execute();
            
            if ($checkStmt->rowCount() > 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Email already exists'
                ]);
                return;
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert new user
            $query = "INSERT INTO users (name, email, password, address, contact_number) 
                     VALUES (:name, :email, :password, :address, :contact)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':contact', $contact);
            
            if ($stmt->execute()) {
                $userId = $this->db->lastInsertId();
                
                // Start session
                session_start();
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful',
                    'user' => [
                        'user_id' => $userId,
                        'name' => $name,
                        'email' => $email,
                        'address' => $address,
                        'contact_number' => $contact
                    ]
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
     * User logout
     */
    public function logout() {
        session_start();
        session_destroy();
        
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }
}
?>