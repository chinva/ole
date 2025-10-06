<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$database = new Database();
$db = $database->connect();
$auth = new Auth($db);

$auth->requireLogin();
$auth->requireRole('student');

// Fetch purchased exams
$stmt = $db->prepare("
    SELECT e.*, c.name as category_name, p.payment_date, p.payment_status,
           CASE WHEN ea.id IS NOT NULL THEN 1 ELSE 0 END as has_attempt,
           CASE WHEN ea.status = 'completed' THEN ea.percentage ELSE NULL END as score
    FROM purchases p
    JOIN exams e ON p.exam_id = e.id
    JOIN exam_categories c ON e.category_id = c.id
    LEFT JOIN exam_attempts ea ON e.id = ea.exam_id AND ea.user_id = p.user_id
    WHERE p.user_id = ? AND p.payment_status = 'completed'
    ORDER BY p.payment_date DESC
");
$stmt->execute([$_SESSION['user_id']]);
$myExams = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Exams - Student Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container mt-4">
        <h2><i class="fas fa-book-open"></i> My Exams</h2>
        <p class="text-muted">Your purchased exams and their status</p>
        
        <?php if (empty($myExams)): ?>
        <div class="text-center py-5">
            <i class="fas fa-book fa-3x text-muted mb-3"></i>
            <h4>No exams purchased yet</h4>
            <p class="text-muted">Browse available exams and purchase your first exam!</p>
            <a href="available-exams.php" class="btn btn-primary">
                <i class="fas fa-search"></i> Browse Exams
            </a>
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($myExams as $exam): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><?php echo htmlspecialchars($exam['title']); ?></h5>
                        <small><?php echo htmlspecialchars($exam['category_name']); ?></small>
                    </div>
                    <div class="card-body">
                        <p class="text-muted"><?php echo htmlspecialchars(substr($exam['description'], 0, 100)); ?>...</p>
                        
                        <div class="mb-3">
                            <span class="badge bg-info"><?php echo $exam['duration']; ?> min</span>
                            <span class="badge bg-success"><?php echo $exam['total_questions']; ?> questions</span>
                            <span class="badge bg-warning"><?php echo $exam['passing_marks']; ?> passing</span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar"></i> Purchased: <?php echo date('M j, Y', strtotime($exam['payment_date'])); ?>
                            </small>
                        </div>
                        
                        <?php if ($exam['has_attempt']): ?>
                        <div class="alert alert-info">
                            <?php if ($exam['score'] !== null): ?>
                                <strong>Completed</strong><br>
                                Score: <?php echo number_format($exam['score'], 1); ?>%
                            <?php else: ?>
                                <strong>In Progress</strong>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold">
                                <?php if ($exam['is_paid']): ?>
                                    ₹<?php echo number_format($exam['price'], 2); ?>
                                <?php else: ?>
                                    <span class="text-success">FREE</span>
                                <?php endif; ?>
                            </span>
                            
                            <?php if (!$exam['has_attempt'] || $exam['score'] === null): ?>
                            <a href="start-exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-success">
                                <i class="fas fa-play"></i> Start Exam
                            </a>
                            <?php else: ?>
                            <a href="result-details.php?id=<?php echo $exam['id']; ?>" class="btn btn-info">
                                <i class="fas fa-eye"></i> View Results
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>