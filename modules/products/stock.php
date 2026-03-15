<?php
use Core\Product;
use Core\Stock;
use Core\Helpers;
use Core\Auth;

if (!Auth::hasRole(ROLE_ADMIN) && !Auth::hasRole(ROLE_SELLER)) {
    Helpers::redirect('/login');
}

$productModel = new Product();
$stockModel = new Stock();
$error = '';
$success = '';

// Get Product ID from query param
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$product = $productModel->getProduct($product_id);

if (!$product) {
    die("Product not found.");
}

// Handle Stock Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_stock'])) {
    $stock_content = $_POST['stock_content'];
    $stock_lines = explode("\n", str_replace("\r", "", $stock_content));
    
    $added_count = $stockModel->addStock($product_id, $stock_lines);
    if ($added_count > 0) {
        $success = "$added_count stock items added successfully!";
    } else {
        $error = "Failed to add stock.";
    }
}

$stocks = $stockModel->getStockList($product_id);
$available_count = $stockModel->getAvailableStock($product_id);

$page_title = 'Manage Stock - ' . $product['title'];
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="row">
        <div class="col-md-12 mb-3">
            <a href="<?php echo BASE_URL; ?>/admin/products" class="btn btn-sm btn-outline-dark"><i class="fas fa-arrow-left"></i> Back to Products</a>
        </div>
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h5>Add Stock for <?php echo $product['title']; ?></h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">Type: <strong><?php echo strtoupper($product['stock_type']); ?></strong></p>
                    <p class="text-muted">Available Stock: <strong><?php echo $available_count; ?></strong></p>
                    
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="add_stock" value="1">
                        <div class="mb-3">
                            <label class="form-label">Stock Content (One per line)</label>
                            <textarea name="stock_content" class="form-control" rows="10" placeholder="e.g. LICENSE-KEY-123 or username:password" required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark">Add Stock</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5>Recent Stock History</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Content</th>
                                    <th>Status</th>
                                    <th>Order ID</th>
                                    <th>Created At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($stocks as $s): ?>
                                    <tr>
                                        <td><?php echo $s['id']; ?></td>
                                        <td><code><?php echo htmlspecialchars($s['content']); ?></code></td>
                                        <td>
                                            <span class="badge bg-<?php echo $s['status'] == 'available' ? 'success' : ($s['status'] == 'sold' ? 'secondary' : 'danger'); ?>">
                                                <?php echo ucfirst($s['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo $s['order_id'] ? '#' . $s['order_id'] : '-'; ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($s['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($stocks)): ?>
                                    <tr><td colspan="5" class="text-center">No stock history found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/admin_footer.php'; ?>
