<?php
session_start();
define('INSTALL_PATH', __DIR__);
define('ROOT_PATH', dirname(__DIR__));

// Check if already installed
if (file_exists(__DIR__ . '/installed.lock')) {
    header('Location: ../');
    exit;
}

$step = $_GET['step'] ?? 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Exam System - Installation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 20px 0;
        }
        .installation-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
            max-width: 800px;
            margin: 0 auto;
        }
        .installation-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .installation-body {
            padding: 40px;
        }
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 30px;
        }
        .step {
            display: flex;
            align-items: center;
            margin: 0 10px;
        }
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 10px;
        }
        .step.active .step-number {
            background: #667eea;
            color: white;
        }
        .step.completed .step-number {
            background: #28a745;
            color: white;
        }
        .step-text {
            font-size: 14px;
            color: #6c757d;
        }
        .step.active .step-text {
            color: #667eea;
            font-weight: bold;
        }
        .step.completed .step-text {
            color: #28a745;
        }
        .requirement-item {
            display: flex;
            align-items: center;
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
        }
        .requirement-item.success {
            background-color: #d4edda;
            color: #155724;
        }
        .requirement-item.danger {
            background-color: #f8d7da;
            color: #721c24;
        }
        .requirement-item.warning {
            background-color: #fff3cd;
            color: #856404;
        }
        .loading-spinner {
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="installation-container">
            <div class="installation-header">
                <h1><i class="fas fa-graduation-cap"></i> Online Exam System</h1>
                <p>Installation Wizard</p>
            </div>
            
            <div class="installation-body">
                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?>">
                        <div class="step-number">1</div>
                        <div class="step-text">Requirements</div>
                    </div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?>">
                        <div class="step-number">2</div>
                        <div class="step-text">Database</div>
                    </div>
                    <div class="step <?php echo $step >= 3 ? 'active' : ''; ?>">
                        <div class="step-number">3</div>
                        <div class="step-text">Admin Account</div>
                    </div>
                </div>

                <?php
                switch ($step) {
                    case 1:
                        include 'step1-requirements.php';
                        break;
                    case 2:
                        include 'step2-database.php';
                        break;
                    case 3:
                        include 'step3-admin.php';
                        break;
                    default:
                        include 'step1-requirements.php';
                }
                ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showLoading() {
            document.querySelector('.loading-spinner').style.display = 'block';
            document.querySelectorAll('button').forEach(btn => btn.disabled = true);
        }

        function hideLoading() {
            document.querySelector('.loading-spinner').style.display = 'none';
            document.querySelectorAll('button').forEach(btn => btn.disabled = false);
        }

        // Form submission with AJAX
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                showLoading();
                
                const formData = new FormData(this);
                
                fetch(this.action, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    
                    if (data.success) {
                        if (data.redirect) {
                            window.location.href = data.redirect;
                        } else {
                            showAlert('success', data.message);
                        }
                    } else {
                        showAlert('danger', data.message);
                    }
                })
                .catch(error => {
                    hideLoading();
                    showAlert('danger', 'An error occurred. Please try again.');
                });
            });
        });

        function showAlert(type, message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.querySelector('.installation-body').insertBefore(alertDiv, document.querySelector('.installation-body').firstChild);
            
            setTimeout(() => alertDiv.remove(), 5000);
        }
    </script>
</body>
</html>