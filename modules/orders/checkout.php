<?php
use Core\Product;
use Core\Order;
use Core\Stock;
use Core\Wallet;
use Core\Helpers;
use Core\Auth;
use Core\Database;

if (!Auth::check()) {
    Helpers::redirect('/login');
}

$productModel = new Product();
$orderModel = new Order();
$stockModel = new Stock();
$walletModel = new Wallet();
$error = null;
$success = null;
$search_results = null;

// Get Product ID from query param
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$product = $productModel->getProduct($product_id, $_SESSION['role_id']);

if (!$product) {
    die("Product not found.");
}

$available_stock = $stockModel->getAvailableStock($product_id);
$user_balance = $walletModel->getBalance($_SESSION['user_id']);
$is_manual = (defined('CUSTOM_PRODUCTS_ONLY') && CUSTOM_PRODUCTS_ONLY) || ($product['delivery_type'] === 'manual');

// Identify Service Types
$is_dakhila = strpos(strtolower($product['title']), 'dakhila') !== false;
$is_payment_link = strpos(strtolower($product['title']), 'payment link') !== false;
$is_birth_search = strpos(strtolower($product['title']), 'birth') !== false;

// Handle Search (Before Purchase)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['search_birth'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        $db = Database::getInstance();
        $stmtUrl = $db->query("SELECT value FROM settings WHERE key = 'birth_search_api_url'");
        $apiUrlBase = $stmtUrl->fetchColumn() ?: "https://my.birthhelp.top/api/bdris/data/search?";
        
        $params = [
            'ubrn' => Helpers::sanitize($_POST['ubrn'] ?? ''),
            'personNameEn' => Helpers::sanitize($_POST['personNameEn'] ?? ''),
            'personNameBn' => Helpers::sanitize($_POST['personNameBn'] ?? ''),
            'gender' => Helpers::sanitize($_POST['gender'] ?? ''),
            'birthYearFrom' => Helpers::sanitize($_POST['birthYearFrom'] ?? ''),
            'birthYearTo' => Helpers::sanitize($_POST['birthYearTo'] ?? ''),
            'match' => 'exact',
            'limit' => (int)($_POST['limit'] ?? 2),
            'skip' => 0
        ];
        $apiUrl = $apiUrlBase . http_build_query($params);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            $response = curl_exec($ch);
            curl_close($ch);

            if ($response) {
                $data = json_decode($response, true);
                if ($data && isset($data['results']) && !empty($data['results'])) {
                    // Generate Temp Tokens and Cache results in Session
                    $_SESSION['birth_search_cache'] = [];
                    foreach ($data['results'] as $res) {
                        $temp_token = bin2hex(random_bytes(16));
                        $_SESSION['birth_search_cache'][$temp_token] = $res;
                    }
                    $search_results = $data;
                } else {
                    $error = "No results found for your search criteria.";
                }
            } else {
                $error = "Failed to connect to search API.";
            }
        } catch (\Exception $e) {
            $error = "Search error occurred.";
        }
    }
}

// Handle Order Placement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        $qty = 1;
        $total_cost = $product['current_price'] * $qty;

        if ($is_birth_search) {
            // Secure Purchase using Temp Token from Session Cache
            $temp_token = Helpers::sanitize($_POST['temp_token'] ?? '');
            if (isset($_SESSION['birth_search_cache'][$temp_token])) {
                $selected_record = $_SESSION['birth_search_cache'][$temp_token];
                $customer_data = json_encode([
                    'type' => 'birth_data',
                    'results' => [$selected_record],
                    'count' => 1
                ]);
                $order_processed = true;
                // Clear cache after selection to prevent replay
                unset($_SESSION['birth_search_cache']);
            } else {
                $error = "Invalid selection or session expired. Please search again.";
            }
        } else {
            $customer_data = isset($_POST['customer_data']) ? Helpers::sanitize($_POST['customer_data']) : null;
        }

        if ($user_balance < $total_cost) {
            $error = "Insufficient wallet balance. Please <a href='" . BASE_URL . "/deposit'>add money</a> first.";
        } else {
            $db = Database::getInstance();
            $order_processed = false;
            $delivery_content = '';

            if ($product['stock_type'] === 'api') {
                if ($is_birth_search) {
                    $delivery_content = $customer_data;
                    $order_processed = !empty($delivery_content);
                } else {
                    $apiUrl = null;
                    if ($is_dakhila) {
                        $stmtUrl = $db->query("SELECT value FROM settings WHERE key = 'ldtax_api_url'");
                        $apiUrlBase = $stmtUrl->fetchColumn() ?: "https://api.udcsheva.com/find_dakhila.php?input=";
                        $apiUrl = $apiUrlBase . urlencode($customer_data);
                    } elseif ($is_payment_link) {
                        $stmtUrl = $db->query("SELECT value FROM settings WHERE key = 'payment_link_api_url'");
                        $apiUrlBase = $stmtUrl->fetchColumn() ?: "https://api.udcsheva.com/payment_link_gen.php?url=";
                        $apiUrl = $apiUrlBase . urlencode($customer_data) . "&advance_year=0";
                    }

                    if ($apiUrl) {
                        try {
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL, $apiUrl);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
                            $response = curl_exec($ch);
                            curl_close($ch);

                            if ($response) {
                                $data = json_decode($response, true);
                                if ($is_dakhila && isset($data['pdf_urls']) && !empty($data['pdf_urls'])) {
                                    $delivery_content = implode("\n", $data['pdf_urls']);
                                    $order_processed = true;
                                } elseif ($is_payment_link && isset($data['url']) && !empty($data['url'])) {
                                    $delivery_content = json_encode(['type' => 'payment_link', 'url' => $data['url'], 'holding_data' => $data['holding_data'] ?? null]);
                                    $order_processed = true;
                                } else {
                                    $error = "Service Error: " . ($data['error'] ?? "Data not found.");
                                }
                            } else {
                                $error = "Failed to connect to API.";
                            }
                        } catch (\Exception $e) {
                            $error = "API connection error.";
                        }
                    }
                }

                if ($order_processed) {
                    if ($walletModel->deductBalance($_SESSION['user_id'], $total_cost, "Auto Service: " . $product['title'])) {
                        $items = [['product_id' => $product_id, 'price' => $product['current_price'], 'qty' => $qty, 'customer_data' => $is_birth_search ? 'Birth Search' : $customer_data]];
                        $order_id = $orderModel->createOrder($_SESSION['user_id'], $items);
                        if ($order_id) {
                            $db->prepare("UPDATE order_items SET delivery_content = ? WHERE order_id = ?")->execute([$delivery_content, $order_id]);
                            $db->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?")->execute([ORDER_COMPLETED, PAYMENT_PAID, $order_id]);
                            $success = "Order successful! View <a href='" . BASE_URL . "/order-history'>Order History</a>.";
                            $user_balance -= $total_cost;
                        }
                    }
                }
            } else {
                if ($walletModel->deductBalance($_SESSION['user_id'], $total_cost, "Purchase: " . $product['title'])) {
                    $items = [['product_id' => $product_id, 'price' => $product['current_price'], 'qty' => $qty, 'customer_data' => $customer_data]];
                    $order_id = $orderModel->createOrder($_SESSION['user_id'], $items);
                    if ($order_id) {
                        if ($is_manual) {
                            $success = "Order placed! Admin will deliver soon.";
                        } else {
                            $orderModel->processOrder($order_id);
                            $success = "Order delivered!";
                        }
                        $user_balance -= $total_cost;
                    }
                }
            }
        }
    }
}

$page_title = 'Checkout - ' . $product['title'];
include 'includes/app_header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-dark text-white text-center">
                    <h4>Checkout</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                    
                    <div class="mb-4">
                        <h5><?php echo $product['title']; ?></h5>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span>Price per Unit:</span>
                            <strong><?php echo Helpers::formatCurrency($product['current_price']); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Your Wallet Balance:</span>
                            <strong class="text-primary"><?php echo Helpers::formatCurrency($user_balance); ?></strong>
                        </div>
                    </div>

                    <?php if ($is_birth_search): ?>
                        <?php if ($search_results): ?>
                            <div class="birth-search-results border rounded p-3 bg-white mb-3">
                                <h6 class="fw-bold mb-3 text-success">Search Results Found (<?php echo count($search_results['results']); ?>)</h6>
                                <div class="list-group">
                                    <?php foreach ($_SESSION['birth_search_cache'] as $token => $res): ?>
                                        <div class="list-group-item list-group-item-action">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1 text-primary"><?php echo $res['personNameEn']; ?></h6>
                                                <small class="badge bg-secondary"><?php echo htmlspecialchars($res['gender'] ?? 'N/A'); ?></small>
                                            </div>
                                            <p class="mb-1 small">
                                                <strong>BN:</strong> <?php echo $res['personNameBn']; ?><br>
                                                <strong>Father:</strong> <?php echo $res['fatherNameEn']; ?><br>
                                                <strong>Mother:</strong> <?php echo $res['motherNameEn']; ?>
                                            </p>
                                            <form action="" method="POST" class="mt-2">
                                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                <input type="hidden" name="place_order" value="1">
                                                <input type="hidden" name="temp_token" value="<?php echo $token; ?>">
                                                <button type="submit" class="btn btn-sm btn-success w-100">
                                                    <i class="fas fa-shopping-cart me-1"></i> Purchase This Record
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <a href="" class="btn btn-link btn-sm w-100 mt-2">Search Again</a>
                            </div>
                        <?php else: ?>
                            <form action="" method="POST" class="border rounded p-3 bg-light mb-3">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="search_birth" value="1">
                                <h6 class="fw-bold mb-3">Search Birth Data</h6>
                                <div class="row g-2">
                                    <div class="col-md-6 mb-2">
                                        <label class="small fw-bold">Full Name (English)</label>
                                        <input type="text" name="personNameEn" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="small fw-bold">Full Name (Bangla)</label>
                                        <input type="text" name="personNameBn" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="small fw-bold">Gender</label>
                                        <select name="gender" class="form-select form-select-sm" required>
                                            <option value="">Select</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-2">
                                        <label class="small fw-bold">Limit</label>
                                        <input type="number" name="limit" class="form-control form-control-sm" value="2" min="1">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Birth Year From</label>
                                        <input type="number" name="birthYearFrom" class="form-control form-control-sm">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="small fw-bold">Birth Year To</label>
                                        <input type="number" name="birthYearTo" class="form-control form-control-sm">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-dark btn-sm w-100 mt-3">Search Before Purchase</button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <form action="" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                            <input type="hidden" name="place_order" value="1">
                            <?php if ($is_manual && !empty($product['order_format_template'])): ?>
                                <div class="mb-3">
                                    <label class="form-label">Order Info</label>
                                    <textarea name="customer_data" class="form-control" rows="3" placeholder="<?php echo htmlspecialchars($product['order_format_template']); ?>" required></textarea>
                                </div>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary w-100">Purchase Now</button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center bg-light">
                    <small class="text-muted">
                        <?php echo ($product['stock_type'] === 'api') ? 'Instant delivery: Your order will be processed immediately.' : ($is_manual ? 'Manual delivery: Admin will deliver after review.' : 'Instant delivery.'); ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/app_footer.php'; ?>
