<?php
header('Content-Type: application/json');
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

$database = new Database();
$db = $database->connect();

// Handle different API endpoints
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_exam':
        $exam_id = $_GET['id'] ?? 0;
        $stmt = $db->prepare("SELECT * FROM exams WHERE id = ? AND is_active = 1");
        $stmt->execute([$exam_id]);
        $exam = $stmt->fetch();
        
        if ($exam) {
            echo json_encode(['success' => true, 'data' => $exam]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Exam not found']);
        }
        break;
        
    case 'get_questions':
        $exam_id = $_GET['id'] ?? 0;
        $stmt = $db->prepare("
            SELECT q.*, GROUP_CONCAT(CONCAT(o.id, ':', o.option_text, ':', o.is_correct, ':', o.option_label) SEPARATOR '|') as options
            FROM questions q
            LEFT JOIN options o ON q.id = o.question_id
            WHERE q.exam_id = ?
            GROUP BY q.id
            ORDER BY q.order_number
        ");
        $stmt->execute([$exam_id]);
        $questions = $stmt->fetchAll();
        
        $formatted_questions = [];
        foreach ($questions as $q) {
            $options = [];
            if ($q['options']) {
                foreach (explode('|', $q['options']) as $opt) {
                    $parts = explode(':', $opt);
                    $options[] = [
                        'id' => $parts[0],
                        'text' => $parts[1],
                        'is_correct' => $parts[2],
                        'label' => $parts[3]
                    ];
                }
            }
            
            $formatted_questions[] = [
                'id' => $q['id'],
                'question' => $q['question_text'],
                'options' => $options,
                'explanation' => $q['explanation']
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $formatted_questions]);
        break;
        
    case 'submit_answer':
        // Handle answer submission
        $attempt_id = $_POST['attempt_id'] ?? 0;
        $question_id = $_POST['question_id'] ?? 0;
        $option_id = $_POST['option_id'] ?? 0;
        $user_id = $_SESSION['user_id'] ?? 0;
        
        // Validate and save answer
        $stmt = $db->prepare("
            INSERT INTO user_answers (attempt_id, question_id, selected_option_id) 
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE selected_option_id = VALUES(selected_option_id)
        ");
        $stmt->execute([$attempt_id, $question_id, $option_id]);
        
        echo json_encode(['success' => true, 'message' => 'Answer saved']);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>