<?php
/**
 * Unified Authentication Controller
 * Handles login for BOTH customers and bakeries
 * Automatically detects user type and redirects to appropriate interface
 */

class AuthController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Unified login - handles both customers and bakeries
     * Customers login with email, Bakeries login with username
     */
    public function login() {
        session_start();
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['email']) || !isset($data['password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Email/Username and password are required'
            ]);
            return;
        }
        
        $identifier = $data['email']; // Can be email or username
        $password = $data['password'];
        
        try {
            // STEP 1: Try to login as a CUSTOMER (using email)
            $query = "SELECT user_id, name, email, password, address, contact_number, is_active 
                     FROM users WHERE email = :identifier";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':identifier', $identifier);
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
                
                // Verify password (plain text comparison)
                if ($password === $user['password']) {
                    // Remove password from response
                    unset($user['password']);
                    
                    // Set session for CUSTOMER
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_type'] = 'customer';
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Welcome back!',
                        'user_type' => 'customer',
                        'redirect' => 'customer',
                        'user' => $user
                    ]);
                    return;
                }
            }
            
            // STEP 2: If not found as customer, try to login as BAKERY (using username)
            $bakeryQuery = "SELECT bakery_id, name, username, email, password, address, contact_number, 
                           description, opening_hours, logo_image, is_active, is_verified 
                           FROM bakeries WHERE username = :identifier";
            $bakeryStmt = $this->db->prepare($bakeryQuery);
            $bakeryStmt->bindParam(':identifier', $identifier);
            $bakeryStmt->execute();
            
            if ($bakeryStmt->rowCount() > 0) {
                $bakery = $bakeryStmt->fetch();
                
                if (!$bakery['is_active']) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Bakery account is deactivated'
                    ]);
                    return;
                }
                
                if (!$bakery['is_verified']) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Bakery account is not verified. Please contact administrator.'
                    ]);
                    return;
                }
                
                // Verify password
                if ($password === $bakery['password']) {
                    // Remove password from response
                    unset($bakery['password']);
                    
                    // Set session for BAKERY
                    $_SESSION['bakery_id'] = $bakery['bakery_id'];
                    $_SESSION['bakery_name'] = $bakery['name'];
                    $_SESSION['bakery_username'] = $bakery['username'];
                    $_SESSION['user_type'] = 'bakery';
                    
                    // Update last login
                    $updateQuery = "UPDATE bakeries SET last_login = CURRENT_TIMESTAMP WHERE bakery_id = :bakery_id";
                    $updateStmt = $this->db->prepare($updateQuery);
                    $updateStmt->bindParam(':bakery_id', $bakery['bakery_id']);
                    $updateStmt->execute();
                    
                    // Log activity
                    $logQuery = "INSERT INTO bakery_activity_log (bakery_id, action_type, action_details) 
                                VALUES (:bakery_id, 'login', 'Successful login via unified portal')";
                    $logStmt = $this->db->prepare($logQuery);
                    $logStmt->bindParam(':bakery_id', $bakery['bakery_id']);
                    $logStmt->execute();
                    
                    echo json_encode([
                        'success' => true,
                        'message' => 'Welcome to Bakery Portal!',
                        'user_type' => 'bakery',
                        'redirect' => 'bakery',
                        'bakery' => $bakery
                    ]);
                    return;
                }
            }
            
            // If neither customer nor bakery found/matched
            echo json_encode([
                'success' => false,
                'message' => 'Invalid email/username or password'
            ]);
            
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Login error: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * User registration (CUSTOMERS ONLY)
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
            
            // Store password in plain text (NOT RECOMMENDED FOR PRODUCTION)
            $plainPassword = $password;
            
            // Insert new user
            $query = "INSERT INTO users (name, email, password, address, contact_number) 
                     VALUES (:name, :email, :password, :address, :contact)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $plainPassword);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':contact', $contact);
            
            if ($stmt->execute()) {
                $userId = $this->db->lastInsertId();
                
                // Start session
                session_start();
                $_SESSION['user_id'] = $userId;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_type'] = 'customer';
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Registration successful',
                    'user_type' => 'customer',
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
     * Unified logout - handles both customers and bakeries
     */
    public function logout() {
        session_start();
        
        $userType = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'unknown';
        
        session_destroy();
        
        echo json_encode([
            'success' => true,
            'message' => 'Logged out successfully',
            'was_type' => $userType
        ]);
    }
    
    /**
     * Check current session and return user type
     */
    public function checkSession() {
        session_start();
        
        if (isset($_SESSION['user_type'])) {
            if ($_SESSION['user_type'] === 'customer' && isset($_SESSION['user_id'])) {
                echo json_encode([
                    'success' => true,
                    'logged_in' => true,
                    'user_type' => 'customer',
                    'user_id' => $_SESSION['user_id'],
                    'user_name' => $_SESSION['user_name']
                ]);
            } elseif ($_SESSION['user_type'] === 'bakery' && isset($_SESSION['bakery_id'])) {
                echo json_encode([
                    'success' => true,
                    'logged_in' => true,
                    'user_type' => 'bakery',
                    'bakery_id' => $_SESSION['bakery_id'],
                    'bakery_name' => $_SESSION['bakery_name']
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'logged_in' => false
                ]);
            }
        } else {
            echo json_encode([
                'success' => true,
                'logged_in' => false
            ]);
        }
    }
}
?>