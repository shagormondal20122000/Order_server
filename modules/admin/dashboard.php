<?php
use Core\Database;
use Core\Helpers;
use Core\Auth;

if (!Auth::hasRole(ROLE_ADMIN)) {
    Helpers::redirect('/login');
}

$db = Database::getInstance();

// Quick Stats
$total_users = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_products = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$total_orders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $db->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?: 0;
$pending_orders = $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();

// Recent Orders
$stmt = $db->query("
    SELECT o.*, u.name as customer_name 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC LIMIT 5
");
$recent_orders = $stmt->fetchAll();

$page_title = 'Admin Dashboard';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Total Users</h6>
                            <h2 class="mb-0"><?php echo $total_users; ?></h2>
                        </div>
                        <i class="fas fa-users fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Revenue</h6>
                            <h2 class="mb-0"><?php echo Helpers::formatCurrency($total_revenue); ?></h2>
                        </div>
                        <i class="fas fa-money-bill-wave fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Total Orders</h6>
                            <h2 class="mb-0"><?php echo $total_orders; ?></h2>
                        </div>
                        <i class="fas fa-shopping-cart fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-uppercase mb-1">Pending Orders</h6>
                            <h2 class="mb-0"><?php echo $pending_orders; ?></h2>
                        </div>
                        <i class="fas fa-clock fa-3x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-dark text-white">
                    <h6 class="m-0 font-weight-bold">Recent Orders</h6>
                    <a href="<?php echo BASE_URL; ?>/admin/orders" class="btn btn-sm btn-outline-light">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td><?php echo $order['customer_name']; ?></td>
                                        <td><?php echo Helpers::formatCurrency($order['total_amount']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $order['status'] == 'completed' ? 'success' : 'warning'; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-dark text-white">
                    <h6 class="m-0 font-weight-bold">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="<?php echo BASE_URL; ?>/admin/products" class="btn btn-outline-primary text-start"><i class="fas fa-plus me-2"></i> Add New Product</a>
                        <a href="<?php echo BASE_URL; ?>/admin/categories" class="btn btn-outline-secondary text-start"><i class="fas fa-list me-2"></i> Manage Categories</a>
                        <a href="<?php echo BASE_URL; ?>/admin/users" class="btn btn-outline-info text-start"><i class="fas fa-user-plus me-2"></i> View All Users</a>
                        <a href="<?php echo BASE_URL; ?>/admin/settings" class="btn btn-outline-dark text-start"><i class="fas fa-cog me-2"></i> Site Settings</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/admin_footer.php'; ?>
