-- Online Examination System Database Schema
-- Created: 2025-10-02

-- Create database (will be created during installation)
CREATE DATABASE IF NOT EXISTS online_exam_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE online_exam_system;

-- Users table (for both admins and students)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    role ENUM('admin', 'student') DEFAULT 'student',
    profile_image VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    email_verified BOOLEAN DEFAULT FALSE,
    verification_token VARCHAR(255),
    reset_token VARCHAR(255),
    reset_expires DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Exam categories table
CREATE TABLE IF NOT EXISTS exam_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    slug VARCHAR(255) UNIQUE NOT NULL,
    icon VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Exams table
CREATE TABLE IF NOT EXISTS exams (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category_id INT NOT NULL,
    duration INT NOT NULL, -- in minutes
    total_questions INT NOT NULL,
    total_marks INT NOT NULL,
    passing_marks INT NOT NULL,
    price DECIMAL(10,2) DEFAULT 0.00,
    is_paid BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    instructions TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES exam_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Questions table
CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exam_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'single_choice') DEFAULT 'single_choice',
    marks INT DEFAULT 1,
    explanation TEXT,
    order_number INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- Options table
CREATE TABLE IF NOT EXISTS options (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text VARCHAR(500) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    option_label VARCHAR(1) NOT NULL, -- A, B, C, D
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Exam attempts table
CREATE TABLE IF NOT EXISTS exam_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    start_time DATETIME NOT NULL,
    end_time DATETIME,
    total_questions INT NOT NULL,
    attempted_questions INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    wrong_answers INT DEFAULT 0,
    score DECIMAL(5,2) DEFAULT 0.00,
    percentage DECIMAL(5,2) DEFAULT 0.00,
    status ENUM('started', 'completed', 'abandoned') DEFAULT 'started',
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE
);

-- User answers table
CREATE TABLE IF NOT EXISTS user_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_option_id INT,
    is_correct BOOLEAN DEFAULT FALSE,
    marks_obtained DECIMAL(5,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (attempt_id) REFERENCES exam_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (selected_option_id) REFERENCES options(id) ON DELETE SET NULL
);

-- Purchases table
CREATE TABLE IF NOT EXISTS purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    exam_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    discount DECIMAL(10,2) DEFAULT 0.00,
    final_amount DECIMAL(10,2) NOT NULL,
    coupon_id INT,
    payment_gateway ENUM('razorpay', 'payu') NOT NULL,
    payment_id VARCHAR(255),
    order_id VARCHAR(255),
    payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    payment_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE SET NULL
);

-- Coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_percentage DECIMAL(5,2) NOT NULL,
    max_discount DECIMAL(10,2),
    min_purchase DECIMAL(10,2) DEFAULT 0.00,
    max_usage INT DEFAULT 1,
    used_count INT DEFAULT 0,
    valid_from DATETIME NOT NULL,
    valid_until DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Settings table
CREATE TABLE IF NOT EXISTS settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(255) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Email templates table
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    template_key VARCHAR(255) UNIQUE NOT NULL,
    subject VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    variables JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Audit logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    table_name VARCHAR(255),
    record_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Online Examination System', 'string', 'Website name'),
('site_email', 'noreply@viniverse.com', 'string', 'System email address'),
('smtp_host', '', 'string', 'SMTP server hostname'),
('smtp_port', '587', 'integer', 'SMTP server port'),
('smtp_username', '', 'string', 'SMTP username'),
('smtp_password', '', 'string', 'SMTP password'),
('smtp_encryption', 'tls', 'string', 'SMTP encryption method'),
('razorpay_key_id', '', 'string', 'Razorpay API Key ID'),
('razorpay_key_secret', '', 'string', 'Razorpay API Key Secret'),
('payu_merchant_key', '', 'string', 'PayU Merchant Key'),
('payu_merchant_salt', '', 'string', 'PayU Merchant Salt'),
('payu_mode', 'test', 'string', 'PayU Mode (test/live)'),
('openai_api_key', '', 'string', 'OpenAI API Key for exam generation'),
('exam_result_email', 'true', 'boolean', 'Send exam results via email'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode');

-- Insert default email templates
INSERT INTO email_templates (template_key, subject, body, variables) VALUES
('welcome_email', 'Welcome to Online Examination System', 
'Dear {{full_name}},\n\nWelcome to Online Examination System! Your account has been successfully created.\n\nEmail: {{email}}\n\nThank you for joining us!\n\nBest regards,\nOnline Examination System Team',
'["full_name", "email"]'),

('exam_result', 'Your Exam Results - {{exam_title}}',
'Dear {{full_name}},\n\nYour exam "{{exam_title}}" has been completed.\n\nScore: {{score}}%\nStatus: {{status}}\nTotal Questions: {{total_questions}}\nCorrect Answers: {{correct_answers}}\n\nYou can view detailed results by logging into your account.\n\nBest regards,\nOnline Examination System Team',
'["full_name", "exam_title", "score", "status", "total_questions", "correct_answers"]'),

('payment_success', 'Payment Successful - {{exam_title}}',
'Dear {{full_name}},\n\nYour payment for "{{exam_title}}" has been successfully processed.\n\nAmount: ₹{{amount}}\nPayment ID: {{payment_id}}\nOrder ID: {{order_id}}\n\nYou can now start your exam from your dashboard.\n\nBest regards,\nOnline Examination System Team',
'["full_name", "exam_title", "amount", "payment_id", "order_id"]');

-- Insert default exam categories
INSERT INTO exam_categories (name, description, slug, icon) VALUES
('General Knowledge', 'Test your general knowledge across various topics', 'general-knowledge', 'fas fa-globe'),
('Mathematics', 'Mathematical problems and calculations', 'mathematics', 'fas fa-calculator'),
('Science', 'Physics, Chemistry, and Biology questions', 'science', 'fas fa-flask'),
('Computer Science', 'Programming and computer fundamentals', 'computer-science', 'fas fa-laptop-code'),
('English', 'Grammar, vocabulary, and comprehension', 'english', 'fas fa-book'),
('History', 'World and Indian history questions', 'history', 'fas fa-landmark');