<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $email = $_POST['admin_email'] ?? 'admin@viniverse.com';
    $password = $_POST['admin_password'] ?? 'Admin@123';
    $full_name = $_POST['admin_name'] ?? 'Super Administrator';
    
    try {
        // Include config and database
        require_once '../config/config.php';
        require_once '../includes/Database.php';
        
        $database = new Database();
        $db = $database->connect();
        
        // Check if admin already exists
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND role = 'admin'");
        $stmt->execute([$email]);
        
        if ($stmt->fetchColumn() > 0) {
            echo json_encode(['success' => false, 'message' => 'Admin account already exists']);
            exit;
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
        
        // Insert admin user
        $stmt = $db->prepare("
            INSERT INTO users (email, password, full_name, role, is_active, email_verified) 
            VALUES (?, ?, ?, 'admin', 1, 1)
        ");
        $stmt->execute([$email, $hashedPassword, $full_name]);
        
        // Create lock file to prevent re-installation
        file_put_contents(__DIR__ . '/installed.lock', 'Installation completed on ' . date('Y-m-d H:i:s'));
        
        // Create .htaccess to prevent access to install folder
        $htaccess = "Require all denied\n";
        file_put_contents(__DIR__ . '/.htaccess', $htaccess);
        
        echo json_encode(['success' => true, 'redirect' => '../admin/']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to create admin account: ' . $e->getMessage()]);
    }
    exit;
}
?>

<div class="text-center mb-4">
    <h3>Create Super Admin Account</h3>
    <p class="text-muted">Set up the primary administrator account</p>
</div>

<form id="adminForm">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="mb-3">
                <label for="admin_name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="admin_name" name="admin_name" value="Super Administrator" required>
            </div>
            
            <div class="mb-3">
                <label for="admin_email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="admin_email" name="admin_email" value="admin@viniverse.com" required>
                <small class="form-text text-muted">This will be your login email</small>
            </div>
            
            <div class="mb-3">
                <label for="admin_password" class="form-label">Password</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="admin_password" name="admin_password" value="Admin@123" required>
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword()">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <small class="form-text text-muted">Default: Admin@123 (change this after first login)</small>
            </div>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Important:</strong> Please change the default password after your first login for security purposes.
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Next Steps:</strong> After installation, you can:
                <ul class="mb-0 mt-2">
                    <li>Login to admin panel at <code>/admin</code></li>
                    <li>Configure payment gateways and SMTP settings</li>
                    <li>Add your OpenAI API key for exam generation</li>
                    <li>Create exam categories and generate exams</li>
                </ul>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <button type="submit" class="btn btn-success btn-lg">
            <span class="loading-spinner spinner-border spinner-border-sm me-2" role="status"></span>
            <span>Complete Installation</span>
        </button>
    </div>
</form>

<script>
function togglePassword() {
    const passwordInput = document.getElementById('admin_password');
    const icon = passwordInput.nextElementSibling.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        passwordInput.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>