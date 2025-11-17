<?php
/**
 * User Controller
 * Handles user profile operations
 */

class UserController {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    /**
     * Get user profile
     */
    public function getUserProfile() {
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
            $query = "SELECT user_id, name, email, address, contact_number, created_at 
                     FROM users WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $user = $stmt->fetch();
                echo json_encode([
                    'success' => true,
                    'user' => $user
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'User not found'
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
     * Update user profile
     */
    public function updateProfile() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['name'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Name is required'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $name = $data['name'];
        $address = isset($data['address']) ? $data['address'] : '';
        $contact = isset($data['contact_number']) ? $data['contact_number'] : '';
        
        try {
            $query = "UPDATE users 
                     SET name = :name, address = :address, contact_number = :contact
                     WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':contact', $contact);
            $stmt->bindParam(':user_id', $userId);
            
            if ($stmt->execute()) {
                // Update session
                $_SESSION['user_name'] = $name;
                
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
    
    /**
     * Change password
     */
    public function changePassword() {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'User not logged in'
            ]);
            return;
        }
        
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!isset($data['current_password']) || !isset($data['new_password'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Current and new password are required'
            ]);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $currentPassword = $data['current_password'];
        $newPassword = $data['new_password'];
        
        try {
            // Get current password
            $query = "SELECT password FROM users WHERE user_id = :user_id";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $user = $stmt->fetch();
            
            // Verify current password (plain text comparison)
            if ($currentPassword !== $user['password']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Current password is incorrect'
                ]);
                return;
            }
            
            // Store new password in plain text
            $plainPassword = $newPassword;
            
            // Update password
            $updateQuery = "UPDATE users SET password = :password WHERE user_id = :user_id";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->bindParam(':password', $plainPassword);
            $updateStmt->bindParam(':user_id', $userId);
            
            if ($updateStmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Password changed successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Failed to change password'
                ]);
            }
        } catch (PDOException $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error changing password: ' . $e->getMessage()
            ]);
        }
    }
}
?>