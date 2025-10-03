<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $host = $_POST['db_host'] ?? 'localhost';
    $name = $_POST['db_name'] ?? 'online_exam_system';
    $user = $_POST['db_user'] ?? '';
    $pass = $_POST['db_pass'] ?? '';
    $prefix = $_POST['db_prefix'] ?? 'oes_';
    
    try {
        // Test connection
        $pdo = new PDO(
            "mysql:host=$host;charset=utf8mb4",
            $user,
            $pass,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        
        // Create database
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        
        // Use database
        $pdo->exec("USE `$name`");
        
        // Read and execute SQL file
        $sql = file_get_contents('../database/schema.sql');
        
        // Replace table prefixes if needed
        if ($prefix !== '') {
            $sql = str_replace('CREATE TABLE IF NOT EXISTS ', "CREATE TABLE IF NOT EXISTS `$prefix", $sql);
            $sql = str_replace('REFERENCES ', "REFERENCES `$prefix", $sql);
            $sql = str_replace('INSERT INTO ', "INSERT INTO `$prefix", $sql);
            $sql = str_replace('USE online_exam_system', "USE `$name`", $sql);
        }
        
        // Execute SQL
        $pdo->exec($sql);
        
        // Create .env file
        $envContent = "# Database Configuration\n";
        $envContent .= "DB_HOST=$host\n";
        $envContent .= "DB_NAME=$name\n";
        $envContent .= "DB_USER=$user\n";
        $envContent .= "DB_PASS=$pass\n\n";
        
        $envContent .= "# Application Configuration\n";
        $envContent .= "APP_URL=" . $_POST['app_url'] . "\n";
        $envContent .= "APP_ENV=production\n\n";
        
        $envContent .= "# API Keys (Add your keys here)\n";
        $envContent .= "OPENAI_API_KEY=\n";
        $envContent .= "RAZORPAY_KEY_ID=\n";
        $envContent .= "RAZORPAY_KEY_SECRET=\n";
        $envContent .= "PAYU_MERCHANT_KEY=\n";
        $envContent .= "PAYU_MERCHANT_SALT=\n\n";
        
        $envContent .= "# SMTP Configuration\n";
        $envContent .= "SMTP_HOST=\n";
        $envContent .= "SMTP_PORT=587\n";
        $envContent .= "SMTP_USERNAME=\n";
        $envContent .= "SMTP_PASSWORD=\n";
        
        file_put_contents('../config/.env', $envContent);
        
        // Update config file with database details
        $configContent = file_get_contents('../config/config.php');
        $configContent = str_replace(
            "define('DB_HOST', \$_ENV['DB_HOST'] ?? 'localhost');",
            "define('DB_HOST', \$_ENV['DB_HOST'] ?? '$host');",
            $configContent
        );
        $configContent = str_replace(
            "define('DB_NAME', \$_ENV['DB_NAME'] ?? 'online_exam_system');",
            "define('DB_NAME', \$_ENV['DB_NAME'] ?? '$name');",
            $configContent
        );
        $configContent = str_replace(
            "define('DB_USER', \$_ENV['DB_USER'] ?? 'root');",
            "define('DB_USER', \$_ENV['DB_USER'] ?? '$user');",
            $configContent
        );
        $configContent = str_replace(
            "define('DB_PASS', \$_ENV['DB_PASS'] ?? '');",
            "define('DB_PASS', \$_ENV['DB_PASS'] ?? '$pass');",
            $configContent
        );
        
        file_put_contents('../config/config.php', $configContent);
        
        echo json_encode(['success' => true, 'redirect' => '?step=3']);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    }
    exit;
}
?>

<div class="text-center mb-4">
    <h3>Database Configuration</h3>
    <p class="text-muted">Enter your database connection details</p>
</div>

<form id="databaseForm">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="db_host" class="form-label">Database Host</label>
                <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                <small class="form-text text-muted">Usually localhost</small>
            </div>
            
            <div class="mb-3">
                <label for="db_name" class="form-label">Database Name</label>
                <input type="text" class="form-control" id="db_name" name="db_name" value="online_exam_system" required>
                <small class="form-text text-muted">Database will be created if it doesn't exist</small>
            </div>
            
            <div class="mb-3">
                <label for="db_user" class="form-label">Database Username</label>
                <input type="text" class="form-control" id="db_user" name="db_user" required>
                <small class="form-text text-muted">Database user with create permissions</small>
            </div>
            
            <div class="mb-3">
                <label for="db_pass" class="form-label">Database Password</label>
                <input type="password" class="form-control" id="db_pass" name="db_pass">
                <small class="form-text text-muted">Leave empty if no password</small>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="mb-3">
                <label for="db_prefix" class="form-label">Table Prefix</label>
                <input type="text" class="form-control" id="db_prefix" name="db_prefix" value="">
                <small class="form-text text-muted">Optional prefix for table names (e.g., oes_)</small>
            </div>
            
            <div class="mb-3">
                <label for="app_url" class="form-label">Application URL</label>
                <input type="url" class="form-control" id="app_url" name="app_url" 
                       value="http://<?php echo $_SERVER['HTTP_HOST'] . str_replace('/install', '', $_SERVER['REQUEST_URI']); ?>" required>
                <small class="form-text text-muted">Full URL to the application root</small>
            </div>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i>
                <strong>Security Note:</strong> After installation, the install folder will be automatically locked for security.
            </div>
        </div>
    </div>
    
    <div class="text-center mt-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <span class="loading-spinner spinner-border spinner-border-sm me-2" role="status"></span>
            <span>Test Connection & Install Database</span>
        </button>
    </div>
</form>