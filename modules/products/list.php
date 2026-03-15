<?php
use Core\Product;
use Core\Helpers;

$productModel = new Product();
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : null;
$categories = $productModel->getCategories();
$selected_category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$service_type = isset($_GET['type']) ? Helpers::sanitize($_GET['type']) : null;

if ($service_type === 'auto') {
    $products = array_filter($productModel->getProducts($role_id, $selected_category_id > 0 ? $selected_category_id : null), function($p) {
        return $p['stock_type'] === 'api';
    });
} elseif ($service_type === 'manual') {
    $products = array_filter($productModel->getProducts($role_id, $selected_category_id > 0 ? $selected_category_id : null), function($p) {
        return $p['stock_type'] !== 'api';
    });
} else {
    $products = $productModel->getProducts($role_id, $selected_category_id > 0 ? $selected_category_id : null);
}

$page_title = 'Browse Products';
include 'includes/app_header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0">Products</h2>
    </div>

    <div class="mb-4">
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-sm <?php echo ($selected_category_id === 0) ? 'btn-primary' : 'btn-outline-primary'; ?>" href="<?php echo BASE_URL; ?>/products">All</a>
            <?php foreach ($categories as $cat): ?>
                <a class="btn btn-sm <?php echo ($selected_category_id === (int)$cat['id']) ? 'btn-primary' : 'btn-outline-primary'; ?>"
                   href="<?php echo BASE_URL; ?>/products?category=<?php echo $cat['id']; ?>">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-4">
        <?php foreach ($products as $p): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $p['title']; ?></h5>
                        <p class="card-text text-muted"><?php echo $p['short_desc']; ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="h5 text-primary mb-0"><?php echo Helpers::formatCurrency($p['current_price']); ?></span>
                            <span class="badge bg-light text-dark border"><?php echo ((defined('CUSTOM_PRODUCTS_ONLY') && CUSTOM_PRODUCTS_ONLY) || ($p['delivery_type'] === 'manual')) ? 'CUSTOM' : strtoupper($p['stock_type']); ?></span>
                        </div>
                        <?php if (!empty($p['category_name'])): ?>
                            <div class="mt-2">
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($p['category_name']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-top-0 d-grid">
                        <a href="<?php echo BASE_URL; ?>/checkout?id=<?php echo $p['id']; ?>" class="btn btn-primary">Buy Now</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($products)): ?>
            <div class="col-12 text-center py-5">
                <h4>No products found. Check back later!</h4>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/app_footer.php'; ?>
