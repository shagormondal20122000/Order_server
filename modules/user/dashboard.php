<?php
use Core\Auth;
use Core\Helpers;
use Core\Wallet;

if (!Auth::check()) {
    Helpers::redirect('/login');
}

$walletModel = new Wallet();
$balance = $walletModel->getBalance($_SESSION['user_id']);

$page_title = 'Dashboard';
include 'includes/app_header.php';
?>

<div class="container-fluid">
    <div class="row g-4">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">Wallet Balance</div>
                            <div class="h4 mb-0"><?php echo Helpers::formatCurrency($balance); ?></div>
                        </div>
                        <i class="fas fa-wallet fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="mb-3">Quick Links</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/products">Browse Products</a>
                        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/order-history">My Orders</a>
                        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/deposit">Add Balance</a>
                        <a class="btn btn-outline-secondary" href="<?php echo BASE_URL; ?>/support/tickets">Support Tickets</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/app_footer.php'; ?>

