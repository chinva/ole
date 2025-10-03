<?php
session_start();
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/Auth.php';

$database = new Database();
$db = $database->connect();
$auth = new Auth($db);

$error = '';
$success = '';

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($auth->login($email, $password, 'student')) {
        header('Location: student/index.php');
        exit;
    } else {
        $error = 'Invalid email or password';
    }
}

// Handle registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $phone = $_POST['phone'] ?? '';
    
    $result = $auth->register($email, $password, $full_name, $phone);
    
    if ($result['success']) {
        $success = 'Registration successful! You can now login.';
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Online Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .auth-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
            min-height: 500px;
        }
        
        .auth-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .auth-body {
            padding: 40px;
        }
        
        .form-control {
            border-radius: 10px;
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .auth-tabs .nav-link {
            border: none;
            border-radius: 10px 10px 0 0;
            color: #6c757d;
            font-weight: 500;
        }
        
        .auth-tabs .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .tab-content {
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 10px 10px;
            padding: 30px;
        }
        
        .loading-spinner {
            display: none;
        }
        
        .floating-label {
            position: relative;
            margin-bottom: 20px;
        }
        
        .floating-label .form-control {
            padding-top: 20px;
        }
        
        .floating-label label {
            position: absolute;
            top: 15px;
            left: 15px;
            color: #6c757d;
            font-size: 14px;
            pointer-events: none;
            transition: all 0.3s;
        }
        
        .floating-label .form-control:focus ~ label,
        .floating-label .form-control:not(:placeholder-shown) ~ label {
            top: 5px;
            left: 15px;
            font-size: 12px;
            color: #667eea;
        }
        
        .password-toggle {
            cursor: pointer;
            position: absolute;
            right: 15px;
            top: 15px;
            color: #6c757d;
        }
        
        .password-toggle:hover {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="row g-0">
            <div class="col-md-5">
                <div class="auth-header h-100 d-flex flex-column justify-content-center">
                    <h2><i class="fas fa-graduation-cap"></i> Online Exam System</h2>
                    <p>Start your learning journey with our comprehensive online examination platform.</p>
                    <div class="mt-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <span>Wide range of exams</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <span>AI-generated questions</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <i class="fas fa-check-circle me-2"></i>
                            <span>Instant results</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle me-2"></i>
                            <span>Secure payment gateway</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="auth-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs auth-tabs mb-0" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#login-tab" type="button">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#register-tab" type="button">
                                <i class="fas fa-user-plus"></i> Register
                            </button>
                        </li>
                    </ul>
                    
                    <div class="tab-content">
                        <!-- Login Tab -->
                        <div class="tab-pane fade show active" id="login-tab">
                            <form method="POST" id="loginForm">
                                <div class="floating-label">
                                    <input type="email" class="form-control" id="login_email" name="email" placeholder=" " required>
                                    <label for="login_email">Email Address</label>
                                </div>
                                
                                <div class="floating-label">
                                    <input type="password" class="form-control" id="login_password" name="password" placeholder=" " required>
                                    <label for="login_password">Password</label>
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('login_password', this)"></i>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                                        <label class="form-check-label" for="remember">
                                            Remember me
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" name="login" class="btn btn-primary w-100 mb-3">
                                    <span class="loading-spinner spinner-border spinner-border-sm me-2" role="status"></span>
                                    <span>Login</span>
                                </button>
                                
                                <div class="text-center">
                                    <a href="#" class="text-decoration-none">Forgot password?</a>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Register Tab -->
                        <div class="tab-pane fade" id="register-tab">
                            <form method="POST" id="registerForm">
                                <div class="floating-label">
                                    <input type="text" class="form-control" id="full_name" name="full_name" placeholder=" " required>
                                    <label for="full_name">Full Name</label>
                                </div>
                                
                                <div class="floating-label">
                                    <input type="email" class="form-control" id="register_email" name="email" placeholder=" " required>
                                    <label for="register_email">Email Address</label>
                                </div>
                                
                                <div class="floating-label">
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder=" ">
                                    <label for="phone">Phone Number (Optional)</label>
                                </div>
                                
                                <div class="floating-label">
                                    <input type="password" class="form-control" id="register_password" name="password" placeholder=" " required>
                                    <label for="register_password">Password</label>
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('register_password', this)"></i>
                                </div>
                                
                                <div class="floating-label">
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder=" " required>
                                    <label for="confirm_password">Confirm Password</label>
                                    <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password', this)"></i>
                                </div>
                                
                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                                        <label class="form-check-label" for="terms">
                                            I agree to the <a href="#" class="text-decoration-none">terms and conditions</a>
                                        </label>
                                    </div>
                                </div>
                                
                                <button type="submit" name="register" class="btn btn-primary w-100 mb-3">
                                    <span class="loading-spinner spinner-border spinner-border-sm me-2" role="status"></span>
                                    <span>Register</span>
                                </button>
                                
                                <div class="text-center text-muted">
                                    <small>Already have an account? <a href="#" onclick="switchTab('login-tab')">Login here</a></small>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function togglePassword(inputId, icon) {
            const input = document.getElementById(inputId);
            const isPassword = input.type === 'password';
            
            input.type = isPassword ? 'text' : 'password';
            icon.className = isPassword ? 'fas fa-eye-slash password-toggle' : 'fas fa-eye password-toggle';
        }
        
        function switchTab(tabId) {
            const tab = new bootstrap.Tab(document.querySelector(`[data-bs-target="#${tabId}"]`));
            tab.show();
        }
        
        // Form submission handlers
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.loading-spinner');
            const text = submitBtn.querySelector('span:last-child');
            
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            text.textContent = 'Logging in...';
        });
        
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('register_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.loading-spinner');
            const text = submitBtn.querySelector('span:last-child');
            
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            text.textContent = 'Creating Account...';
        });
        
        // Auto-focus first field
        document.querySelector('#login_email').focus();
    </script>
</body>
</html>