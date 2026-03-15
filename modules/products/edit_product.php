<?php
use Core\Product;
use Core\Helpers;
use Core\Auth;
use Core\Wallet;

if (!Auth::hasRole(ROLE_ADMIN) && !Auth::hasRole(ROLE_SELLER)) {
    Helpers::redirect('/login');
}

$productModel = new Product();
$error = '';
$success = '';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = $productModel->getProduct($product_id, $_SESSION['role_id']);
if (!$product) {
    die("Product not found.");
}

$categories = $productModel->getCategories();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_product'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        $title = Helpers::sanitize($_POST['title']);
        $slug = Helpers::generateSlug($title);
        $short_desc = Helpers::sanitize($_POST['short_desc']);
        $full_desc = $_POST['full_desc'];
        $category_id = (int)$_POST['category_id'];
        $status = Helpers::sanitize($_POST['status']);
        $base_price = (float)$_POST['base_price'];
        $order_format_template = Helpers::sanitize($_POST['order_format_template']);

        if ($title === '' || $category_id < 1) {
            $error = "Title and category are required.";
        } else {
            $ok = $productModel->updateProduct($product_id, [
                'title' => $title,
                'slug' => $slug,
                'short_desc' => $short_desc,
                'full_desc' => $full_desc,
                'category_id' => $category_id,
                'stock_type' => 'manual',
                'delivery_type' => 'manual',
                'status' => ($status === 'inactive') ? 'inactive' : 'active',
                'order_format_template' => $order_format_template
            ]);

            if ($ok) {
                $productModel->setPrice($product_id, null, $base_price);
                $success = "Product updated successfully!";
                $product = $productModel->getProduct($product_id, $_SESSION['role_id']);
            } else {
                $error = "Failed to update product.";
            }
        }
    }
}

$page_title = 'Edit Product';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>

<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Edit Product</h4>
            <a href="<?php echo BASE_URL; ?>/admin/products" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="update_product" value="1">

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Product Title</label>
                            <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($product['title']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?php echo ($product['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($product['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-select" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['id']; ?>" <?php echo ((int)$product['category_id'] === (int)$cat['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Base Price (৳)</label>
                            <input type="number" step="0.01" name="base_price" class="form-control" value="<?php echo htmlspecialchars((string)$product['current_price']); ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Short Description</label>
                            <textarea name="short_desc" class="form-control" rows="2"><?php echo htmlspecialchars($product['short_desc']); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Full Description</label>
                            <textarea name="full_desc" class="form-control" rows="6"><?php echo htmlspecialchars($product['full_desc']); ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Order Format Template (Optional)</label>
                            <textarea name="order_format_template" class="form-control" rows="4"><?php echo htmlspecialchars($product['order_format_template'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

