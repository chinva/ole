<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../api/PaymentGateway.php';

$database = new Database();
$db = $database->connect();
$auth = new Auth($db);

// Check if user is logged in and is student
$auth->requireLogin();
$auth->requireRole('student');

// Get student info
$student = $auth->getCurrentUser();
$paymentGateway = new PaymentGateway($db);

// Get student dashboard data
try {
    // Available exams
    $stmt = $db->prepare("
        SELECT e.*, c.name as category_name, 
               CASE WHEN p.id IS NOT NULL THEN 1 ELSE 0 END as is_purchased
        FROM exams e
        JOIN exam_categories c ON e.category_id = c.id
        LEFT JOIN purchases p ON e.id = p.exam_id AND p.user_id = ? AND p.payment_status = 'completed'
        WHERE e.is_active = 1
        ORDER BY e.created_at DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $availableExams = $stmt->fetchAll();

    // Completed exams
    $stmt = $db->prepare("
        SELECT ea.*, e.title, e.category_id, c.name as category_name
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.id
        JOIN exam_categories c ON e.category_id = c.id
        WHERE ea.user_id = ? AND ea.status = 'completed'
        ORDER BY ea.end_time DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $completedExams = $stmt->fetchAll();

    // Statistics
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT e.id) as total_exams,
            COUNT(DISTINCT CASE WHEN p.id IS NOT NULL THEN e.id END) as purchased_exams,
            COUNT(DISTINCT CASE WHEN ea.status = 'completed' THEN e.id END) as completed_exams,
            COALESCE(AVG(CASE WHEN ea.status = 'completed' THEN ea.percentage END), 0) as avg_score
        FROM exams e
        LEFT JOIN purchases p ON e.id = p.exam_id AND p.user_id = ? AND p.payment_status = 'completed'
        LEFT JOIN exam_attempts ea ON e.id = ea.exam_id AND ea.user_id = ? AND ea.status = 'completed'
        WHERE e.is_active = 1
    ");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $stats = $stmt->fetch();

} catch (Exception $e) {
    error_log("Student dashboard error: " . $e->getMessage());
    $availableExams = [];
    $completedExams = [];
    $stats = ['total_exams' => 0, 'purchased_exams' => 0, 'completed_exams' => 0, 'avg_score' => 0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Online Exam System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            height: 100%;
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
        
        .exam-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
            height: 100%;
        }
        
        .exam-card:hover {
            transform: translateY(-5px);
        }
        
        .exam-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            padding: 15px;
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
        
        .progress {
            height: 8px;
            border-radius: 10px;
        }
        
        .badge {
            font-size: 0.75em;
            padding: 0.5em 0.75em;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar">
        <div class="px-3 py-2">
            <h4 class="text-white text-center mb-4">
                <i class="fas fa-user-graduate"></i> Student Panel
            </h4>
        </div>
        <ul class="nav flex-column px-2">
            <li class="nav-item">
                <a class="nav-link active" href="index.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="available-exams.php">
                    <i class="fas fa-file-alt"></i> Available Exams
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="my-exams.php">
                    <i class="fas fa-book-open"></i> My Exams
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="results.php">
                    <i class="fas fa-chart-line"></i> Results
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profile.php">
                    <i class="fas fa-user"></i> Profile
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
            <h5 class="mb-0">Student Dashboard</h5>
        </div>
        <div class="dropdown">
            <button class="btn btn-link dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($student['full_name']); ?>
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
            <!-- Welcome Message -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-hand-wave"></i> Welcome, <?php echo htmlspecialchars($student['full_name']); ?>!</h5>
                        <p class="mb-0">Ready to test your knowledge? Choose from our wide range of exams.</p>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-primary">
                            <i class="fas fa-book"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['total_exams']); ?></h3>
                        <p class="text-muted mb-0">Total Exams</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-success">
                            <i class="fas fa-shopping-cart"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['purchased_exams']); ?></h3>
                        <p class="text-muted mb-0">Purchased Exams</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-info">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['completed_exams']); ?></h3>
                        <p class="text-muted mb-0">Completed Exams</p>
                    </div>
                </div>
                <div class="col-md-3 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon bg-warning">
                            <i class="fas fa-percentage"></i>
                        </div>
                        <h3 class="mb-1"><?php echo number_format($stats['avg_score'], 1); ?>%</h3>
                        <p class="text-muted mb-0">Average Score</p>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-rocket"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="available-exams.php" class="btn btn-primary w-100">
                                        <i class="fas fa-search"></i> Browse Exams
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="my-exams.php" class="btn btn-success w-100">
                                        <i class="fas fa-play"></i> Start Exam
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="results.php" class="btn btn-info w-100">
                                        <i class="fas fa-chart-bar"></i> View Results
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="profile.php" class="btn btn-secondary w-100">
                                        <i class="fas fa-user"></i> Edit Profile
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Available Exams -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5><i class="fas fa-star"></i> Featured Exams</h5>
                            <a href="available-exams.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php 
                                $featuredExams = array_slice($availableExams, 0, 3);
                                foreach ($featuredExams as $exam): 
                                ?>
                                <div class="col-md-4 mb-3">
                                    <div class="exam-card">
                                        <div class="card-header">
                                            <h6 class="mb-0"><?php echo htmlspecialchars($exam['title']); ?></h6>
                                            <small><?php echo htmlspecialchars($exam['category_name']); ?></small>
                                        </div>
                                        <div class="card-body">
                                            <p class="small text-muted mb-2">
                                                <?php echo substr(htmlspecialchars($exam['description']), 0, 80); ?>...
                                            </p>
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="badge bg-primary"><?php echo $exam['duration']; ?> min</span>
                                                <span class="badge bg-success"><?php echo $exam['total_questions']; ?> questions</span>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="fw-bold">
                                                    <?php if ($exam['is_paid']): ?>
                                                        ₹<?php echo number_format($exam['price'], 2); ?>
                                                    <?php else: ?>
                                                        <span class="text-success">Free</span>
                                                    <?php endif; ?>
                                                </span>
                                                <?php if ($exam['is_purchased']): ?>
                                                    <a href="start-exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-success">
                                                        Start Exam
                                                    </a>
                                                <?php else: ?>
                                                    <a href="purchase-exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary">
                                                        <?php echo $exam['is_paid'] ? 'Purchase' : 'Start Free'; ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Results -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-history"></i> Recent Results</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($completedExams)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">No exams completed yet. Start your first exam today!</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Exam</th>
                                                <th>Category</th>
                                                <th>Score</th>
                                                <th>Percentage</th>
                                                <th>Status</th>
                                                <th>Completed</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($completedExams, 0, 5) as $exam): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                                <td><?php echo htmlspecialchars($exam['category_name']); ?></td>
                                                <td><?php echo $exam['correct_answers']; ?>/<?php echo $exam['total_questions']; ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="progress me-2" style="width: 100px;">
                                                            <div class="progress-bar bg-<?php echo $exam['percentage'] >= 60 ? 'success' : ($exam['percentage'] >= 40 ? 'warning' : 'danger'); ?>" 
                                                                 style="width: <?php echo $exam['percentage']; ?>%">
                                                            </div>
                                                        </div>
                                                        <span><?php echo number_format($exam['percentage'], 1); ?>%</span>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $exam['percentage'] >= 60 ? 'success' : ($exam['percentage'] >= 40 ? 'warning' : 'danger'); ?>">
                                                        <?php echo $exam['percentage'] >= 60 ? 'Pass' : ($exam['percentage'] >= 40 ? 'Average' : 'Fail'); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($exam['end_time'])); ?></td>
                                                <td>
                                                    <a href="result-details.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-primary">
                                                        View Details
                                                    </a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
    </script>
</body>
</html>