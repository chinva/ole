<?php
class Auth {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function login($email, $password, $role = 'student') {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND role = ? AND is_active = 1");
            $stmt->execute([$email, $role]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $stmt = $this->db->prepare("UPDATE users SET updated_at = NOW() WHERE id = ?");
                $stmt->execute([$user['id']]);
                
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                $_SESSION['login_time'] = time();
                
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
    
    public function register($email, $password, $full_name, $phone = null) {
        try {
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already registered'];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            
            // Generate verification token
            $verificationToken = bin2hex(random_bytes(32));
            
            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (email, password, full_name, phone, verification_token) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$email, $hashedPassword, $full_name, $phone, $verificationToken]);
            
            $userId = $this->db->lastInsertId();
            
            // Send verification email (if SMTP is configured)
            $this->sendVerificationEmail($email, $full_name, $verificationToken);
            
            return ['success' => true, 'user_id' => $userId];
            
        } catch (Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Registration failed. Please try again.'];
        }
    }
    
    public function logout() {
        session_destroy();
        session_start();
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    public function hasRole($role) {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === $role;
    }
    
    public function requireRole($role) {
        if (!$this->hasRole($role)) {
            header('HTTP/1.1 403 Forbidden');
            exit('Access denied');
        }
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /login.php');
            exit;
        }
    }
    
    public function changePassword($userId, $oldPassword, $newPassword) {
        try {
            // Verify old password
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if (!password_verify($oldPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            
            // Update password
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            return ['success' => true, 'message' => 'Password changed successfully'];
            
        } catch (Exception $e) {
            error_log("Password change error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to change password'];
        }
    }
    
    public function resetPassword($email, $token, $newPassword) {
        try {
            // Verify token
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ? AND reset_token = ? AND reset_expires > NOW()");
            $stmt->execute([$email, $token]);
            
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Invalid or expired reset token'];
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
            
            // Update password and clear reset token
            $stmt = $this->db->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE email = ?");
            $stmt->execute([$hashedPassword, $email]);
            
            return ['success' => true, 'message' => 'Password reset successfully'];
            
        } catch (Exception $e) {
            error_log("Password reset error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
    
    public function generateResetToken($email) {
        try {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            $stmt = $this->db->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE email = ?");
            $stmt->execute([$token, $expires, $email]);
            
            return $stmt->rowCount() > 0 ? $token : null;
            
        } catch (Exception $e) {
            error_log("Token generation error: " . $e->getMessage());
            return null;
        }
    }
    
    public function verifyEmail($token) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM users WHERE verification_token = ?");
            $stmt->execute([$token]);
            
            if ($user = $stmt->fetch()) {
                $stmt = $this->db->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = ?");
                $stmt->execute([$user['id']]);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("Email verification error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendVerificationEmail($email, $fullName, $token) {
        // This will be implemented when Email class is created
        // For now, just log it
        error_log("Verification email should be sent to $email for user $fullName with token: $token");
    }
}
?>