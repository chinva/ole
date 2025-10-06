<?php
class Email {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function sendEmail($to, $subject, $body, $template_key = null, $variables = []) {
        try {
            if ($template_key) {
                $stmt = $this->db->prepare("SELECT subject, body FROM email_templates WHERE template_key = ?");
                $stmt->execute([$template_key]);
                $template = $stmt->fetch();
                
                if ($template) {
                    $subject = $template['subject'];
                    $body = $template['body'];
                    
                    // Replace variables
                    foreach ($variables as $key => $value) {
                        $subject = str_replace('{{' . $key . '}}', $value, $subject);
                        $body = str_replace('{{' . $key . '}}', $value, $body);
                    }
                }
            }
            
            // Simple mail function (replace with PHPMailer for production)
            $headers = "From: " . ADMIN_EMAIL . "\r\n";
            $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            return mail($to, $subject, $body, $headers);
            
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendWelcomeEmail($email, $full_name) {
        $subject = "Welcome to Online Examination System";
        $body = "Dear {$full_name},\n\nWelcome to our online examination platform! Your account has been successfully created.\n\nYou can now browse and purchase exams to test your knowledge.\n\nBest regards,\nOnline Examination System Team";
        
        return $this->sendEmail($email, $subject, $body);
    }
    
    public function sendExamResult($email, $full_name, $exam_title, $score, $status) {
        $subject = "Your Exam Results - {$exam_title}";
        $body = "Dear {$full_name},\n\nYour exam '{$exam_title}' has been completed.\n\nScore: {$score}%\nStatus: {$status}\n\nYou can view detailed results by logging into your account.\n\nBest regards,\nOnline Examination System Team";
        
        return $this->sendEmail($email, $subject, $body);
    }
}
?>