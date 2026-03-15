<?php
use Core\Product;
use Core\Helpers;
use Core\Auth;

if (!Auth::hasRole(ROLE_ADMIN) && !Auth::hasRole(ROLE_SELLER)) {
    Helpers::redirect('/login');
}

$productModel = new Product();
$error = '';
$success = '';

// Handle Product Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $title = Helpers::sanitize($_POST['title']);
    $slug = Helpers::generateSlug($title);
    $short_desc = Helpers::sanitize($_POST['short_desc']);
    $full_desc = $_POST['full_desc']; // Allow HTML if needed, but should sanitize for production
    $category_id = $_POST['category_id'];
    $stock_type = $_POST['stock_type'] ?? 'manual';
    $delivery_type = $_POST['delivery_type'] ?? 'manual';
    $base_price = $_POST['base_price'];
    $order_format_template = Helpers::sanitize($_POST['order_format_template']);

    $data = [
        'title' => $title,
        'slug' => $slug,
        'short_desc' => $short_desc,
        'full_desc' => $full_desc,
        'category_id' => $category_id,
        'thumbnail' => '', // Handle file upload later
        'stock_type' => $stock_type,
        'delivery_type' => $delivery_type,
        'status' => 'active',
        'order_format_template' => $order_format_template
    ];

    $product_id = $productModel->createProduct($data);
    if ($product_id) {
        $productModel->setPrice($product_id, null, $base_price); // NULL for general base price
        $success = "Product created successfully!";
    } else {
        $error = "Failed to create product.";
    }
}

$categories = $productModel->getCategories();
$products = $productModel->getProducts($_SESSION['role_id']);

$page_title = 'Manage Products';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h5>Add New Product</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="add_product" value="1">
                        <div class="mb-3">
                            <label class="form-label">Product Title</label>
                            <input type="text" name="title" class="form-control" placeholder="e.g. LD Tax Dakhila" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service Type</label>
                            <select name="stock_type" class="form-select" required>
                                <option value="manual">Manual Service</option>
                                <option value="api">Auto API Service (LD Tax)</option>
                            </select>
                            <small class="text-muted">Use 'Auto API Service' for Dakhila or Payment Link. Ensure title contains 'Dakhila' or 'Payment Link' for automation.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Delivery Type</label>
                            <select name="delivery_type" class="form-select" required>
                                <option value="manual">Manual Delivery</option>
                                <option value="instant">Instant Delivery</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Short Description</label>
                            <textarea name="short_desc" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Full Description</label>
                            <textarea name="full_desc" class="form-control" rows="4"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Order Format Template (Optional)</label>
                            <textarea name="order_format_template" class="form-control" rows="3" placeholder="e.g. Paste Dakhila Link Here:"></textarea>
                            <small class="text-muted">For Auto Service, this will guide the customer on what to provide.</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Price (৳)</label>
                            <input type="number" step="0.01" name="base_price" class="form-control" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark">Create Product</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5>Product List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td><?php echo $p['id']; ?></td>
                                        <td><?php echo $p['title']; ?></td>
                                        <td><?php echo $p['category_name']; ?></td>
                                        <td><?php echo Helpers::formatCurrency($p['current_price']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $p['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($p['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/admin/edit-product?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($products)): ?>
                                    <tr><td colspan="6" class="text-center">No products found.</td></tr>
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
