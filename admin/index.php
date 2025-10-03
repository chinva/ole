<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$database = new Database();
$db = $database->connect();
$auth = new Auth($db);

// Check if user is logged in and is admin
$auth->requireLogin();
$auth->requireRole('admin');

// Get dashboard statistics
$stats = [
    'total_students' => 0,
    'total_exams' => 0,
    'total_payments' => 0,
    'total_revenue' => 0,
    'recent_payments' => [],
    'recent_exams' => []
];

try {
    // Total students
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'student'");
    $stmt->execute();
    $stats['total_students'] = $stmt->fetchColumn();

    // Total exams
    $stmt = $db->prepare("SELECT COUNT(*) FROM exams");
    $stmt->execute();
    $stats['total_exams'] = $stmt->fetchColumn();

    // Total payments and revenue
    $stmt = $db->prepare("
        SELECT COUNT(*) as total_payments, COALESCE(SUM(final_amount), 0) as total_revenue 
        FROM purchases 
        WHERE payment_status = 'completed'
    ");
    $stmt->execute();
    $paymentStats = $stmt->fetch();
    $stats['total_payments'] = $paymentStats['total_payments'];
    $stats['total_revenue'] = $paymentStats['total_revenue'];

    // Recent payments
    $stmt = $db->prepare("
        SELECT p.*, u.full_name, e.title as exam_title 
        FROM purchases p 
        JOIN users u ON p.user_id = u.id 
        JOIN exams e ON p.exam_id = e.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $stats['recent_payments'] = $stmt->fetchAll();

    // Recent exams
    $stmt = $db->prepare("
        SELECT e.*, c.name as category_name, u.full_name as created_by_name 
        FROM exams e 
        JOIN exam_categories c ON e.category_id = c.id 
        JOIN users u ON e.created_by = u.id 
        ORDER BY e.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $stats['recent_exams'] = $stmt->fetchAll();

} catch (Exception $e) {
    error_log("Dashboard stats error: " . $e->getMessage());
}

// Get current admin info
$admin = $auth->getCurrentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Online Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f8f9fa;
        }
        
        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            padding-top: var(--header-height);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            margin: 2px 0;
            border-radius: 0 25px 25px 0;
            transition: all 0.3s;
        }
        
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            margin-left: var(--sidebar-width);
            padding-top: var(--header-height);
        }
        
        .top-bar {
            height: var(--header-height);
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            right: 0;
            left: var(--sidebar-width);
            z-index: 999;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
        }
        
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
            margin-bottom: 15px;
        }
        
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .sidebar-toggle {
            display: none;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-bar {
                left: 0;
            }
            
            .sidebar-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="px-3 py-2">
            <h4 class="text-white text-center mb-4">
                <i class="fas fa-graduation-cap"></i> Admin Panel
            </h4>
        </div>
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a class="nav-link active" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="categories.php">
                    <i class="fas fa-folder"></i> Categories
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="exams.php">
                    <i class="fas fa-file-alt"></i> Exams
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="students.php">
                    <i class="fas fa-users"></i> Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="coupons.php">
                    <i class="fas fa-tags"></i> Coupons
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="payments.php">
                    <i class="fas fa-credit-card"></i> Payments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="settings.php">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="generate-exam.php">
                    <i class="fas fa-robot"></i> AI Exam Generator
                </a>
            </li>
            <li class="nav-item mt-5">
                <a class="nav-link text-danger" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </nav>

    <!-- Top Bar -->
    <div class="top-bar">
        <div class="d-flex align-items-center">
            <button class="btn btn-link sidebar-toggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            <h5 class="mb-0">Admin Dashboard</h5>
        </div>
        <div class="dropdown">
            <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($admin['full_name']); ?>
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid px-4 py-4">
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['total_students']); ?></h3>
                        <p class="text-muted mb-0">Total Students</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['total_exams']); ?></h3>
                        <p class="text-muted mb-0">Total Exams</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['total_payments']); ?></h3>
                        <p class="text-muted mb-0">Total Payments</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-rupee-sign"></i>
                        </div>
                        <h3 class="mb-1">₹<?php echo number_format($stats['total_revenue'], 2); ?></h3>
                        <p class="text-muted mb-0">Total Revenue</p>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3">Payment Trends</h5>
                        <canvas id="paymentChart" height="200"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3">Exam Performance</h5>
                        <canvas id="examChart" height="200"></canvas>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3">Recent Payments</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Exam</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['recent_payments'] as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($payment['exam_title']); ?></td>
                                        <td>₹<?php echo number_format($payment['final_amount'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $payment['payment_status'] === 'completed' ? 'success' : ($payment['payment_status'] === 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo ucfirst($payment['payment_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="chart-container">
                        <h5 class="mb-3">Recent Exams</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['recent_exams'] as $exam): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['category_name']); ?></td>
                                        <td>₹<?php echo number_format($exam['price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $exam['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $exam['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }

        // Payment Trends Chart
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Revenue (₹)',
                    data: [12000, 19000, 15000, 25000, 22000, 30000],
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });

        // Exam Performance Chart
        const examCtx = document.getElementById('examChart').getContext('2d');
        new Chart(examCtx, {
            type: 'doughnut',
            data: {
                labels: ['Passed', 'Failed', 'Pending'],
                datasets: [{
                    data: [65, 25, 10],
                    backgroundColor: ['#28a745', '#dc3545', '#ffc107']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false
            }
        });
    </script>
</body>
</html>