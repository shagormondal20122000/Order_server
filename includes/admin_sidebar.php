<?php
$current_route = trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '', '/');
$base_path = trim(parse_url(BASE_URL, PHP_URL_PATH) ?? '', '/');
$page = str_replace($base_path, '', $current_route);
$page = trim($page, '/');
?>
<div class="admin-sidebar d-flex flex-column flex-shrink-0 p-3 text-white bg-dark">
    <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto text-white text-decoration-none">
        <i class="fas fa-tachometer-alt me-2"></i>
        <span class="fs-4">Admin Panel</span>
    </a>
    <hr>
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="<?php echo BASE_URL; ?>/admin/dashboard" class="nav-link text-white <?php echo ($page === 'admin/dashboard') ? 'active' : ''; ?>">
                <i class="fas fa-home me-2"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/products" class="nav-link text-white <?php echo ($page === 'admin/products') ? 'active' : ''; ?>">
                <i class="fas fa-box-open me-2"></i> Products
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/categories" class="nav-link text-white <?php echo ($page === 'admin/categories') ? 'active' : ''; ?>">
                <i class="fas fa-sitemap me-2"></i> Categories
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/orders" class="nav-link text-white <?php echo ($page === 'admin/orders') ? 'active' : ''; ?>">
                <i class="fas fa-shopping-cart me-2"></i> Orders
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/deposits" class="nav-link text-white <?php echo ($page === 'admin/deposits') ? 'active' : ''; ?>">
                <i class="fas fa-money-check-alt me-2"></i> Deposits
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/tickets" class="nav-link text-white <?php echo ($page === 'admin/tickets') ? 'active' : ''; ?>">
                <i class="fas fa-life-ring me-2"></i> Support Tickets
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/reports" class="nav-link text-white <?php echo ($page === 'admin/reports') ? 'active' : ''; ?>">
                <i class="fas fa-chart-line me-2"></i> Reports
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/users" class="nav-link text-white <?php echo ($page === 'admin/users') ? 'active' : ''; ?>">
                <i class="fas fa-users me-2"></i> Users
            </a>
        </li>
        <li>
            <a href="<?php echo BASE_URL; ?>/admin/settings" class="nav-link text-white <?php echo ($page === 'admin/settings') ? 'active' : ''; ?>">
                <i class="fas fa-cog me-2"></i> Settings
            </a>
        </li>
    </ul>
    <hr>
    <div class="small text-white-50 mb-2"><?php echo $_SESSION['user_name']; ?></div>
    <a class="nav-link text-white px-0" href="<?php echo BASE_URL; ?>/" target="_blank">
        <i class="fas fa-external-link-alt me-2"></i> View Site
    </a>
    <a class="nav-link text-white px-0" href="<?php echo BASE_URL; ?>/logout">
        <i class="fas fa-sign-out-alt me-2"></i> Logout
    </a>
</div>
