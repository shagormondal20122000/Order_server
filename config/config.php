<?php
// Project configuration
define('SITE_NAME', 'Digital Product Platform');
define('BASE_URL', 'http://localhost:8000'); // Adjust according to your server path
define('CUSTOM_PRODUCTS_ONLY', true);

// Database configuration
define('DB_PATH', __DIR__ . '/../database/database.sqlite');

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Role Definitions
define('ROLE_ADMIN', 1);
define('ROLE_SELLER', 2);
define('ROLE_RESELLER', 3);
define('ROLE_MODERATOR', 4);
define('ROLE_CUSTOMER', 5);

// Product Stock Status
define('STOCK_AVAILABLE', 'available');
define('STOCK_SOLD', 'sold');
define('STOCK_RESERVED', 'reserved');

// Order Status
define('ORDER_PENDING', 'pending');
define('ORDER_PROCESSING', 'processing');
define('ORDER_COMPLETED', 'completed');
define('ORDER_CANCELLED', 'cancelled');
define('ORDER_REFUNDED', 'refunded');

// Payment Status
define('PAYMENT_PENDING', 'pending');
define('PAYMENT_PAID', 'paid');
define('PAYMENT_FAILED', 'failed');
