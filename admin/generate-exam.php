<?php
session_start();
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';
require_once '../api/OpenAIHelper.php';

$database = new Database();
$db = $database->connect();
$auth = new Auth($db);

// Check if user is logged in and is admin
$auth->requireLogin();
$auth->requireRole('admin');

$error = '';
$success = '';
$generatedQuestions = [];

// Get categories for dropdown
try {
    $stmt = $db->prepare("SELECT * FROM exam_categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (Exception $e) {
    $error = 'Failed to load categories';
    $categories = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryId = $_POST['category_id'] ?? 0;
    $examTitle = $_POST['exam_title'] ?? '';
    $description = $_POST['description'] ?? '';
    $duration = (int)($_POST['duration'] ?? 30);
    $numQuestions = (int)($_POST['num_questions'] ?? 10);
    $price = (float)($_POST['price'] ?? 0);
    $isPaid = isset($_POST['is_paid']) ? 1 : 0;
    $instructions = $_POST['instructions'] ?? '';
    
    // Validate inputs
    if (empty($examTitle)) {
        $error = 'Exam title is required';
    } elseif ($categoryId <= 0) {
        $error = 'Please select a category';
    } elseif ($numQuestions < 5 || $numQuestions > 50) {
        $error = 'Number of questions must be between 5 and 50';
    } elseif ($duration < 5 || $duration > 180) {
        $error = 'Duration must be between 5 and 180 minutes';
    } else {
        // Get category name
        $stmt = $db->prepare("SELECT name FROM exam_categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $category = $stmt->fetch();
        
        if ($category) {
            // Generate questions using OpenAI
            $openAI = new OpenAIHelper();
            $result = $openAI->generateExamQuestions($category['name'], $numQuestions);
            
            if ($result['success']) {
                $generatedQuestions = $result['questions'];
                
                // Save exam to database
                try {
                    $db->beginTransaction();
                    
                    // Insert exam
                    $stmt = $db->prepare("
                        INSERT INTO exams 
                        (title, description, category_id, duration, total_questions, total_marks, passing_marks, price, is_paid, instructions, created_by) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    
                    $totalMarks = $numQuestions;
                    $passingMarks = max(1, floor($totalMarks * 0.4));
                    
                    $stmt->execute([
                        $examTitle,
                        $description,
                        $categoryId,
                        $duration,
                        $numQuestions,
                        $totalMarks,
                        $passingMarks,
                        $price,
                        $isPaid,
                        $instructions,
                        $_SESSION['user_id']
                    ]);
                    
                    $examId = $db->lastInsertId();
                    
                    // Insert questions and options
                    foreach ($generatedQuestions as $index => $question) {
                        $stmt = $db->prepare("
                            INSERT INTO questions (exam_id, question_text, marks, explanation, order_number) 
                            VALUES (?, ?, ?, ?, ?)
                        ");
                        
                        $stmt->execute([
                            $examId,
                            $question['question'],
                            1,
                            $question['explanation'],
                            $index + 1
                        ]);
                        
                        $questionId = $db->lastInsertId();
                        
                        // Insert options
                        foreach ($question['options'] as $option) {
                            $stmt = $db->prepare("
                                INSERT INTO options (question_id, option_text, is_correct, option_label) 
                                VALUES (?, ?, ?, ?)
                            ");
                            
                            $stmt->execute([
                                $questionId,
                                $option['text'],
                                $option['correct'] ? 1 : 0,
                                $option['label']
                            ]);
                        }
                    }
                    
                    $db->commit();
                    $success = "Exam generated successfully with $numQuestions questions!";
                    
                } catch (Exception $e) {
                    $db->rollBack();
                    $error = 'Failed to save exam: ' . $e->getMessage();
                }
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Invalid category selected';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Exam Generator - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .loading-spinner {
            display: none;
        }
        
        .question-preview {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .option-item {
            padding: 8px;
            margin: 5px 0;
            background: white;
            border-radius: 5px;
            border-left: 3px solid #dee2e6;
        }
        
        .option-item.correct {
            border-left-color: #28a745;
            background-color: #d4edda;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-robot"></i> AI Exam Generator</h4>
                        <p class="mb-0">Generate exams automatically using ChatGPT AI</p>
                    </div>
                    <div class="card-body">
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
                        
                        <form method="POST" id="generateForm">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Category</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Select a category</option>
                                            <?php foreach ($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="exam_title" class="form-label">Exam Title</label>
                                        <input type="text" class="form-control" id="exam_title" name="exam_title" 
                                               placeholder="e.g., Advanced JavaScript Quiz" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="description" class="form-label">Description</label>
                                        <textarea class="form-control" id="description" name="description" 
                                                  rows="3" placeholder="Brief description of the exam"></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="instructions" class="form-label">Instructions</label>
                                        <textarea class="form-control" id="instructions" name="instructions" 
                                                  rows="3" placeholder="Exam instructions for students"></textarea>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="num_questions" class="form-label">Number of Questions</label>
                                        <select class="form-select" id="num_questions" name="num_questions">
                                            <?php for ($i = 5; $i <= 50; $i += 5): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == 10 ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?> questions
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="duration" class="form-label">Duration (minutes)</label>
                                        <select class="form-select" id="duration" name="duration">
                                            <?php for ($i = 5; $i <= 180; $i += 5): ?>
                                                <option value="<?php echo $i; ?>" <?php echo $i == 30 ? 'selected' : ''; ?>>
                                                    <?php echo $i; ?> minutes
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="is_paid" name="is_paid">
                                            <label class="form-check-label" for="is_paid">
                                                Paid Exam
                                            </label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3" id="priceContainer" style="display: none;">
                                        <label for="price" class="form-label">Price (₹)</label>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               min="0" step="0.01" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <span class="loading-spinner spinner-border spinner-border-sm me-2" role="status"></span>
                                    <span>Generate Exam</span>
                                </button>
                            </div>
                        </form>
                        
                        <?php if (!empty($generatedQuestions)): ?>
                            <hr class="my-4">
                            <h5>Generated Questions Preview</h5>
                            <div class="question-preview-container">
                                <?php foreach ($generatedQuestions as $index => $question): ?>
                                    <div class="question-preview">
                                        <strong>Q<?php echo $index + 1; ?>:</strong> <?php echo htmlspecialchars($question['question']); ?>
                                        <div class="mt-2">
                                            <?php foreach ($question['options'] as $option): ?>
                                                <div class="option-item <?php echo $option['correct'] ? 'correct' : ''; ?>">
                                                    <strong><?php echo $option['label']; ?>.</strong> <?php echo htmlspecialchars($option['text']); ?>
                                                    <?php if ($option['correct']): ?>
                                                        <i class="fas fa-check text-success float-end"></i>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php if ($question['explanation']): ?>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <strong>Explanation:</strong> <?php echo htmlspecialchars($question['explanation']); ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle price input based on paid exam checkbox
        document.getElementById('is_paid').addEventListener('change', function() {
            const priceContainer = document.getElementById('priceContainer');
            const priceInput = document.getElementById('price');
            
            if (this.checked) {
                priceContainer.style.display = 'block';
                priceInput.setAttribute('required', 'required');
            } else {
                priceContainer.style.display = 'none';
                priceInput.removeAttribute('required');
                priceInput.value = '0';
            }
        });
        
        // Form submission
        document.getElementById('generateForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            const spinner = submitBtn.querySelector('.loading-spinner');
            const text = submitBtn.querySelector('span:last-child');
            
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            text.textContent = 'Generating...';
        });
    </script>
</body>
</html>