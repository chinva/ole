<?php
// Database Configuration
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'online_exam_system');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');

// Application Configuration
define('APP_NAME', 'Online Examination System');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/online-exam-system');
define('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? 'admin@viniverse.com');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Security Configuration
define('BCRYPT_COST', 12);
define('SESSION_LIFETIME', 3600); // 1 hour
define('CSRF_TOKEN_LIFETIME', 3600);

// OpenAI Configuration
define('OPENAI_API_KEY', $_ENV['OPENAI_API_KEY'] ?? '');
define('OPENAI_MODEL', 'gpt-3.5-turbo');
define('OPENAI_MAX_TOKENS', 2000);

// SMTP Configuration
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');

// Payment Gateway Configuration
// Razorpay
define('RAZORPAY_KEY_ID', $_ENV['RAZORPAY_KEY_ID'] ?? '');
define('RAZORPAY_KEY_SECRET', $_ENV['RAZORPAY_KEY_SECRET'] ?? '');

// PayU
define('PAYU_MERCHANT_KEY', $_ENV['PAYU_MERCHANT_KEY'] ?? '');
define('PAYU_MERCHANT_SALT', $_ENV['PAYU_MERCHANT_SALT'] ?? '');
define('PAYU_MODE', $_ENV['PAYU_MODE'] ?? 'test'); // test or live

// File Upload Configuration
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Error Reporting
if ($_ENV['APP_ENV'] ?? 'development' === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>