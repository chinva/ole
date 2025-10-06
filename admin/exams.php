<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../api/OpenAIHelper.php';

$database = new Database();
$db = $database->connect();
$auth = new Auth($db);

$auth->requireLogin();
$auth->requireRole('admin');

$message = '';
$error = '';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                // Add exam functionality
                $title = $_POST['title'] ?? '';
                $category_id = $_POST['category_id'] ?? 0;
                $description = $_POST['description'] ?? '';
                $duration = $_POST['duration'] ?? 30;
                $price = $_POST['price'] ?? 0;
                $is_paid = isset($_POST['is_paid']) ? 1 : 0;
                
                // Implementation for adding exam
                $message = 'Exam management - to be implemented';
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                $stmt = $db->prepare("UPDATE exams SET is_active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Exam deleted successfully';
                break;
        }
    }
}

// Fetch all exams with categories
$stmt = $db->prepare("
    SELECT e.*, c.name as category_name, u.full_name as created_by_name
    FROM exams e
    JOIN exam_categories c ON e.category_id = c.id
    JOIN users u ON e.created_by = u.id
    WHERE e.is_active = 1
    ORDER BY e.created_at DESC
");
$stmt->execute();
$exams = $stmt->fetchAll();

// Fetch categories for dropdown
$stmt = $db->prepare("SELECT * FROM exam_categories WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exams - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4><i class="fas fa-file-alt"></i> Manage Exams</h4>
                        <div>
                            <a href="generate-exam.php" class="btn btn-primary">
                                <i class="fas fa-robot"></i> Generate with AI
                            </a>
                            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addModal">
                                <i class="fas fa-plus"></i> Add Manual
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Title</th>
                                        <th>Category</th>
                                        <th>Duration</th>
                                        <th>Questions</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($exams as $exam): ?>
                                    <tr>
                                        <td><?php echo $exam['id']; ?></td>
                                        <td><?php echo htmlspecialchars($exam['title']); ?></td>
                                        <td><?php echo htmlspecialchars($exam['category_name']); ?></td>
                                        <td><?php echo $exam['duration']; ?> min</td>
                                        <td><?php echo $exam['total_questions']; ?></td>
                                        <td>₹<?php echo number_format($exam['price'], 2); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $exam['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $exam['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="exam-details.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $exam['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
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
</body>
</html>