<?php
// Payment Gateway Configuration
// This file should be included in config.php

// Razorpay Configuration
if (!defined('RAZORPAY_KEY_ID')) {
    define('RAZORPAY_KEY_ID', $_ENV['RAZORPAY_KEY_ID'] ?? '');
    define('RAZORPAY_KEY_SECRET', $_ENV['RAZORPAY_KEY_SECRET'] ?? '');
}

// PayU Configuration
if (!defined('PAYU_MERCHANT_KEY')) {
    define('PAYU_MERCHANT_KEY', $_ENV['PAYU_MERCHANT_KEY'] ?? '');
    define('PAYU_MERCHANT_SALT', $_ENV['PAYU_MERCHANT_SALT'] ?? '');
    define('PAYU_MODE', $_ENV['PAYU_MODE'] ?? 'test'); // test or live
}

// Payment URLs
define('PAYMENT_SUCCESS_URL', APP_URL . '/student/payment-success.php');
define('PAYMENT_FAILURE_URL', APP_URL . '/student/payment-failure.php');
define('PAYMENT_CANCEL_URL', APP_URL . '/student/payment-cancel.php');

// Email Configuration
if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? '');
    define('SMTP_PORT', $_ENV['SMTP_PORT'] ?? 587);
    define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
    define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
    define('SMTP_ENCRYPTION', $_ENV['SMTP_ENCRYPTION'] ?? 'tls');
}

// System Configuration
define('CURRENCY', 'INR');
define('CURRENCY_SYMBOL', '₹');
define('MINIMUM_EXAM_PRICE', 0);
define('MAXIMUM_EXAM_PRICE', 10000);

// Security Configuration
define('PAYMENT_TIMEOUT', 1800); // 30 minutes
define('PAYMENT_RETRY_ATTEMPTS', 3);
?>