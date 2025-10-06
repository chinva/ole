<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

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
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $icon = $_POST['icon'] ?? 'fas fa-folder';
                
                if (empty($name)) {
                    $error = 'Category name is required';
                } else {
                    $stmt = $db->prepare("INSERT INTO exam_categories (name, description, slug, icon) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $description, strtolower(str_replace(' ', '-', $name)), $icon]);
                    $message = 'Category added successfully';
                }
                break;
                
            case 'edit':
                $id = $_POST['id'] ?? 0;
                $name = $_POST['name'] ?? '';
                $description = $_POST['description'] ?? '';
                $icon = $_POST['icon'] ?? 'fas fa-folder';
                
                if (empty($name)) {
                    $error = 'Category name is required';
                } else {
                    $stmt = $db->prepare("UPDATE exam_categories SET name = ?, description = ?, icon = ? WHERE id = ?");
                    $stmt->execute([$name, $description, $icon, $id]);
                    $message = 'Category updated successfully';
                }
                break;
                
            case 'delete':
                $id = $_POST['id'] ?? 0;
                $stmt = $db->prepare("UPDATE exam_categories SET is_active = 0 WHERE id = ?");
                $stmt->execute([$id]);
                $message = 'Category deleted successfully';
                break;
        }
    }
}

// Fetch all categories
$stmt = $db->prepare("SELECT * FROM exam_categories WHERE is_active = 1 ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-folder"></i> Exam Categories</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($message): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        
                        <button class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#addModal">
                            <i class="fas fa-plus"></i> Add Category
                        </button>
                        
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Icon</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo $category['id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($category['description'], 0, 50)); ?>...</td>
                                        <td><i class="<?php echo $category['icon']; ?>"></i></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary" onclick="editCategory(<?php echo htmlspecialchars(json_encode($category)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
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

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        <div class="mb-3">
                            <label>Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label>Icon</label>
                            <select name="icon" class="form-select">
                                <option value="fas fa-folder">Folder</option>
                                <option value="fas fa-book">Book</option>
                                <option value="fas fa-graduation-cap">Graduation</option>
                                <option value="fas fa-globe">Globe</option>
                                <option value="fas fa-calculator">Calculator</option>
                                <option value="fas fa-flask">Flask</option>
                                <option value="fas fa-laptop-code">Code</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editCategory(category) {
            // Implementation for edit modal
            alert('Edit functionality to be implemented');
        }
    </script>
</body>
</html>