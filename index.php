<?php
session_start();

// Autoloading
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/Helpers.php';
require_once __DIR__ . '/core/Auth.php';
require_once __DIR__ . '/core/Product.php';
require_once __DIR__ . '/core/Stock.php';
require_once __DIR__ . '/core/Order.php';
require_once __DIR__ . '/core/Wallet.php';
require_once __DIR__ . '/core/User.php';
require_once __DIR__ . '/core/Support.php';
require_once __DIR__ . '/core/Report.php';

use Core\Database;
use Core\Helpers;
use Core\Auth;

// Check if database exists, if not, it will be created by Database class
$pdo = Database::getInstance();
$auth = new Auth();

// Generate CSRF Token for the session
Helpers::generateCsrfToken();

// Simple Router
$request_uri = $_SERVER['REQUEST_URI'];
$base_path = parse_url(BASE_URL, PHP_URL_PATH) ?? '';
$route = str_replace($base_path, '', $request_uri);
$route = parse_url($route, PHP_URL_PATH);
$route = trim($route, '/');

// Default route
if (empty($route)) {
    if (Auth::check()) {
        if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_MODERATOR)) {
            Helpers::redirect('/admin/dashboard');
        }
        Helpers::redirect('/dashboard');
    }
    $route = 'home';
}

// Basic Route Handling
switch ($route) {
    case 'home':
        if (Auth::check()) {
            if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_MODERATOR)) {
                Helpers::redirect('/admin/dashboard');
            }
            Helpers::redirect('/dashboard');
        }
        include 'includes/header.php';
        echo "<div class='container mt-5'><h1>Welcome to " . SITE_NAME . "</h1></div>";
        include 'includes/footer.php';
        break;
    case 'dashboard':
        include 'modules/user/dashboard.php';
        break;
    case 'login':
        include 'modules/auth/login.php';
        break;
    case 'register':
        include 'modules/auth/register.php';
        break;
    case 'logout':
        Auth::logout();
        Helpers::redirect('/login');
        break;
    case 'admin/dashboard':
        if (Auth::hasRole(ROLE_ADMIN)) {
            include 'modules/admin/dashboard.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/products':
        if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_SELLER)) {
            include 'modules/products/admin_products.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/edit-product':
        if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_SELLER)) {
            include 'modules/products/edit_product.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/categories':
        if (Auth::hasRole(ROLE_ADMIN)) {
            include 'modules/products/categories.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/stock':
        if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_SELLER)) {
            include 'modules/products/stock.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'products':
        if (Auth::check()) {
            include 'modules/products/list.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'checkout':
        include 'modules/orders/checkout.php';
        break;
    case 'order-history':
        include 'modules/orders/history.php';
        break;
    case 'deposit':
        include 'modules/wallet/deposit.php';
        break;
    case 'admin/orders':
        if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_MODERATOR)) {
            include 'modules/admin/orders.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/deposits':
        if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_MODERATOR)) {
            include 'modules/admin/deposits.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/users':
        if (Auth::hasRole(ROLE_ADMIN)) {
            include 'modules/admin/users.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/settings':
        if (Auth::hasRole(ROLE_ADMIN)) {
            include 'modules/admin/settings.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/tickets':
        if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_MODERATOR)) {
            include 'modules/admin/tickets.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/ticket':
        if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_MODERATOR)) {
            include 'modules/admin/ticket_view.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'admin/reports':
        if (Auth::hasRole(ROLE_ADMIN)) {
            include 'modules/admin/reports.php';
        } else {
            Helpers::redirect('/login');
        }
        break;
    case 'support/tickets':
        include 'modules/support/tickets.php';
        break;
    case 'support/ticket':
        include 'modules/support/ticket_view.php';
        break;
    default:
        http_response_code(404);
        echo "<h1>404 Not Found</h1>";
        break;
}
