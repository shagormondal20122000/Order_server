<?php
$current_route = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$base_path = trim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
$page = str_replace($base_path, '', $current_route);
$page = trim($page, '/');

$wallet = new \Core\Wallet();
$balance = $wallet->getBalance($_SESSION['user_id']);
?>
<div class="admin-sidebar d-flex flex-column flex-shrink-0 p-3 text-white bg-dark">
    <div class="mb-3">
        <div class="fw-semibold"><?php echo $_SESSION['user_name']; ?></div>
        <div class="small text-white-50"><?php echo \Core\Helpers::formatCurrency($balance); ?></div>
    </div>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/dashboard" class="nav-link text-white <?php echo ($page === 'dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/products?type=auto" class="nav-link text-white <?php echo ($page === 'products' && isset($_GET['type']) && $_GET['type'] === 'auto') ? 'active' : ''; ?>">
                <i class="fas fa-bolt me-2 text-warning"></i> Auto Services
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/products?type=manual" class="nav-link text-white <?php echo ($page === 'products' && isset($_GET['type']) && $_GET['type'] === 'manual') ? 'active' : ''; ?>">
                <i class="fas fa-user-cog me-2"></i> Manual Services
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/products" class="nav-link text-white <?php echo ($page === 'products' && !isset($_GET['type'])) ? 'active' : ''; ?>">
                <i class="fas fa-box-open me-2"></i> All Products
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/order-history" class="nav-link text-white <?php echo ($page === 'order-history') ? 'active' : ''; ?>">
                <i class="fas fa-receipt me-2"></i> My Orders
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/deposit" class="nav-link text-white <?php echo ($page === 'deposit') ? 'active' : ''; ?>">
                <i class="fas fa-wallet me-2"></i> Add Balance
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/support/tickets" class="nav-link text-white <?php echo ($page === 'support/tickets' || $page === 'support/ticket') ? 'active' : ''; ?>">
                <i class="fas fa-life-ring me-2"></i> Support Tickets
            </a>
        </li>
        <?php if (\Core\Auth::hasRole(ROLE_ADMIN) || \Core\Auth::hasRole(ROLE_MODERATOR)): ?>
            <li>
                <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="nav-link text-white">
                    <i class="fas fa-shield-alt me-2"></i> Admin Panel
                </a>
            </li>
        <?php endif; ?>
        <li>
            <a href="<?php echo BASE_URL; ?>/logout" class="nav-link text-white">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </li>
    </ul>
</div>

