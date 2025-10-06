<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../api/PaymentGateway.php';

$database = new Database();
$db = $database->connect();
$auth = new Auth($db);
$paymentGateway = new PaymentGateway($db);

$auth->requireLogin();
$auth->requireRole('student');

// Fetch available exams
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
$exams = $stmt->fetchAll();

// Fetch categories for filtering
$stmt = $db->prepare("SELECT * FROM exam_categories WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Exams - Student Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h2><i class="fas fa-search"></i> Available Exams</h2>
        <p class="text-muted">Browse and purchase exams to test your knowledge</p>
        
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5>Filter Exams</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-select" id="category_filter">
                                    <option value="">All Categories</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="price_filter">
                                    <option value="">All Prices</option>
                                    <option value="free">Free</option>
                                    <option value="paid">Paid</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary" onclick="filterExams()">
                                    <i class="fas fa-filter"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row" id="exams-container">
            <?php foreach ($exams as $exam): ?>
            <div class="col-md-4 mb-4 exam-card" 
                 data-category="<?php echo $exam['category_id']; ?>" 
                 data-price="<?php echo $exam['is_paid'] ? 'paid' : 'free'; ?>">
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
                            <span class="badge bg-warning"><?php echo $exam['passing_marks']; ?> passing marks</span>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">
                                <?php if ($exam['is_paid']): ?>
                                    ₹<?php echo number_format($exam['price'], 2); ?>
                                <?php else: ?>
                                    <span class="text-success">FREE</span>
                                <?php endif; ?>
                            </span>
                            
                            <?php if ($exam['is_purchased']): ?>
                                <a href="start-exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-success btn-sm">
                                    <i class="fas fa-play"></i> Start Exam
                                </a>
                            <?php else: ?>
                                <a href="purchase-exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-primary btn-sm">
                                    <?php echo $exam['is_paid'] ? 'Purchase' : 'Start Free'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($exams)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4>No exams available</h4>
            <p class="text-muted">Please check back later for new exams.</p>
        </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function filterExams() {
            const categoryFilter = document.getElementById('category_filter').value;
            const priceFilter = document.getElementById('price_filter').value;
            const cards = document.querySelectorAll('.exam-card');
            
            cards.forEach(card => {
                const cardCategory = card.getAttribute('data-category');
                const cardPrice = card.getAttribute('data-price');
                
                let show = true;
                
                if (categoryFilter && cardCategory !== categoryFilter) show = false;
                if (priceFilter && cardPrice !== priceFilter) show = false;
                
                card.style.display = show ? 'block' : 'none';
            });
        }
    </script>
</body>
</html>