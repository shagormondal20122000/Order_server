<?php
use Core\Database;
use Core\Helpers;
use Core\Auth;
use Core\Order;
use Core\Wallet;

if (!Auth::hasRole(ROLE_ADMIN) && !Auth::hasRole(ROLE_MODERATOR)) {
    Helpers::redirect('/login');
}

$db = Database::getInstance();
$orderModel = new Order();
$wallet = new Wallet();

// Handle Manual Delivery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_delivery'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
    $order_id = (int)$_POST['order_id'];
    $delivery_text = Helpers::sanitize($_POST['delivery_content'] ?? '');

    $stmtO = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmtO->execute([$order_id]);
    $order = $stmtO->fetch();
    if (!$order) {
        $error = "Order not found.";
    } elseif ($order['status'] !== ORDER_PROCESSING) {
        $error = "Order must be in Processing status.";
    } else {
        $delivery_payload = '';
        $delivery_file_uploaded = isset($_FILES['delivery_file']) && is_array($_FILES['delivery_file']) && ($_FILES['delivery_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

        if ($delivery_file_uploaded) {
            if (($_FILES['delivery_file']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $error = "Failed to upload file.";
            } else {
                $original_name = (string)($_FILES['delivery_file']['name'] ?? 'delivery');
                $tmp_path = (string)($_FILES['delivery_file']['tmp_name'] ?? '');
                $size = (int)($_FILES['delivery_file']['size'] ?? 0);
                $ext = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

                $allowed_ext = ['zip', 'rar', '7z', 'txt', 'pdf', 'png', 'jpg', 'jpeg'];
                if ($size <= 0) {
                    $error = "Invalid file.";
                } elseif ($size > 10 * 1024 * 1024) {
                    $error = "File too large (max 10MB).";
                } elseif (!in_array($ext, $allowed_ext, true)) {
                    $error = "File type not allowed.";
                } else {
                    $upload_dir_fs = __DIR__ . '/../../uploads/deliveries';
                    $upload_dir_web = 'uploads/deliveries';
                    if (!is_dir($upload_dir_fs)) {
                        @mkdir($upload_dir_fs, 0755, true);
                    }

                    $safe_name = bin2hex(random_bytes(16)) . ($ext ? ('.' . $ext) : '');
                    $dest_fs = $upload_dir_fs . '/' . $safe_name;
                    $dest_web = $upload_dir_web . '/' . $safe_name;

                    if (!is_uploaded_file($tmp_path) || !move_uploaded_file($tmp_path, $dest_fs)) {
                        $error = "Failed to save uploaded file.";
                    } else {
                        $delivery_payload = json_encode([
                            'type' => 'file',
                            'path' => $dest_web,
                            'name' => $original_name
                        ], JSON_UNESCAPED_SLASHES);
                    }
                }
            }
        } else {
            if ($delivery_text === '') {
                $error = "Delivery content is required.";
            } else {
                $delivery_payload = $delivery_text;
            }
        }

        if (empty($error)) {
    
    // Update order items with delivery content
    $stmt = $db->prepare("UPDATE order_items SET delivery_content = ? WHERE order_id = ?");
    if ($stmt->execute([$delivery_payload, $order_id])) {
        // Mark order as completed
        $stmt = $db->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([ORDER_COMPLETED, PAYMENT_PAID, $order_id]);
        $success = "Order #$order_id marked as completed and delivery content sent.";
    } else {
        $error = "Failed to update delivery content.";
    }
        }
    }
    }
}

// Handle Order Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } else {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];

    $allowed_statuses = [ORDER_PENDING, ORDER_PROCESSING, ORDER_COMPLETED, ORDER_CANCELLED, ORDER_REFUNDED];
    if (!in_array($new_status, $allowed_statuses, true)) {
        $error = "Invalid status.";
    } else {
    
    // Fetch order info
    $stmtO = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmtO->execute([$order_id]);
    $order = $stmtO->fetch();

    if ($order) {
        if ($new_status === ORDER_CANCELLED || $new_status === ORDER_REFUNDED) {
            if ($order['status'] !== ORDER_CANCELLED && $order['status'] !== ORDER_REFUNDED) {
                // Refund to user wallet
                if ($wallet->addBalance($order['user_id'], $order['total_amount'], "Refund for Order #$order_id")) {
                    $stmt = $db->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
                    $stmt->execute([$new_status, 'refunded', $order_id]);
                    $success = "Order #$order_id status updated and amount refunded to wallet.";
                } else {
                    $error = "Failed to refund amount.";
                }
            } else {
                $error = "Order is already cancelled/refunded.";
            }
        } elseif ($new_status === ORDER_COMPLETED) {
            $stmt = $db->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
            if ($stmt->execute([ORDER_COMPLETED, PAYMENT_PAID, $order_id])) {
                $success = "Order #$order_id status updated to " . strtoupper($new_status);
            } else {
                $error = "Failed to update order status.";
            }
        } else {
            $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            if ($stmt->execute([$new_status, $order_id])) {
                $success = "Order #$order_id status updated to " . strtoupper($new_status);
            } else {
                $error = "Failed to update order status.";
            }
        }
    } else {
        $error = "Order not found.";
    }
    }
    }
}

// Fetch all orders with customer info
$stmt = $db->query("
    SELECT o.*, u.name as customer_name, u.email as customer_email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.id DESC
");
$orders = $stmt->fetchAll();

$page_title = 'Manage Orders';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="card shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Orders</h5>
            <div class="btn-group">
                <button class="btn btn-sm btn-outline-light">Filter</button>
                <button class="btn btn-sm btn-outline-light">Export</button>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (isset($error)): ?><div class="alert alert-danger m-3"><?php echo $error; ?></div><?php endif; ?>
            <?php if (isset($success)): ?><div class="alert alert-success m-3"><?php echo $success; ?></div><?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Customer</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td>
                                    <strong><?php echo $order['customer_name']; ?></strong><br>
                                    <small class="text-muted"><?php echo $order['customer_email']; ?></small>
                                </td>
                                <td><?php echo Helpers::formatCurrency($order['total_amount']); ?></td>
                                <td>
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="status" class="form-select form-select-sm d-inline-block w-auto">
                                            <option value="pending" <?php echo $order['status'] == ORDER_PENDING ? 'selected' : ''; ?>>Pending</option>
                                            <option value="processing" <?php echo $order['status'] == ORDER_PROCESSING ? 'selected' : ''; ?>>Processing</option>
                                            <option value="completed" <?php echo $order['status'] == ORDER_COMPLETED ? 'selected' : ''; ?>>Completed</option>
                                            <option value="cancelled" <?php echo $order['status'] == ORDER_CANCELLED ? 'selected' : ''; ?>>Cancelled</option>
                                            <option value="refunded" <?php echo $order['status'] == ORDER_REFUNDED ? 'selected' : ''; ?>>Refunded</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-outline-primary ms-1">Update</button>
                                    </form>
                                </td>
                                <td>
                                    <?php 
                                    $payment_badge = 'warning';
                                    if ($order['payment_status'] == 'paid') $payment_badge = 'success';
                                    if ($order['payment_status'] == 'refunded') $payment_badge = 'info';
                                    if ($order['payment_status'] == 'failed') $payment_badge = 'danger';
                                    ?>
                                    <span class="badge bg-<?php echo $payment_badge; ?>">
                                        <?php echo strtoupper($order['payment_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info text-white" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['id']; ?>">
                                        <i class="fas fa-eye"></i> Details
                                    </button>
                                    
                                    <!-- Order Detail Modal -->
                                    <div class="modal fade" id="orderModal<?php echo $order['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Order Details #<?php echo $order['id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="table-responsive">
                                                        <table class="table table-bordered">
                                                            <thead class="table-light">
                                                                <tr>
                                                                    <th>Product</th>
                                                                    <th>Price</th>
                                                                    <th>Qty</th>
                                                                    <th>Delivery Content</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php 
                                                                $items = $orderModel->getOrderDetails($order['id']);
                                                                foreach ($items as $item): 
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo $item['product_title']; ?></td>
                                                                        <td><?php echo Helpers::formatCurrency($item['price']); ?></td>
                                                                        <td><?php echo $item['qty']; ?></td>
                                                                        <td>
                                                                            <?php if (!empty($item['customer_data'])): ?>
                                                                                <strong>Customer Data:</strong>
                                                                                <pre class="bg-warning p-2 mb-2" style="font-size: 0.85rem;"><?php echo htmlspecialchars($item['customer_data']); ?></pre>
                                                                            <?php endif; ?>
                                                                            <strong>Delivered Content:</strong>
                                                                            <?php $delivered = $item['delivery_content'] ?? ''; ?>
                                                                            <?php if ($delivered === ''): ?>
                                                                                <div class="text-muted small">Not delivered yet.</div>
                                                                            <?php else: ?>
                                                                                <?php $payload = json_decode($delivered, true); ?>
                                                                                <?php if (is_array($payload) && ($payload['type'] ?? '') === 'file' && !empty($payload['path'])): ?>
                                                                                    <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($payload['path']); ?>" target="_blank">
                                                                                        Download File<?php echo !empty($payload['name']) ? ' (' . htmlspecialchars($payload['name']) . ')' : ''; ?>
                                                                                    </a>
                                                                                <?php else: ?>
                                                                                    <pre class="bg-light p-2 mb-0" style="font-size: 0.85rem;"><?php echo htmlspecialchars($delivered); ?></pre>
                                                                                <?php endif; ?>
                                                                            <?php endif; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    
                                                    <?php if ($order['status'] === ORDER_PENDING): ?>
                                                    <hr>
                                                    <form action="" method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <input type="hidden" name="status" value="processing">
                                                        <button type="submit" class="btn btn-warning">Receive Order (Mark as Processing)</button>
                                                    </form>
                                                    <?php endif; ?>

                                                    <?php if ($order['status'] === ORDER_PROCESSING): ?>
                                                    <hr>
                                                    <h6>Manual Delivery</h6>
                                                    <form action="" method="POST" enctype="multipart/form-data">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="manual_delivery" value="1">
                                                        <div class="mb-3">
                                                            <label class="form-label">Delivery Content (Keys, Accounts, Text)</label>
                                                            <textarea name="delivery_content" class="form-control" rows="4" placeholder="Write delivery text (optional if you upload a file)"></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Or Upload Delivery File</label>
                                                            <input type="file" name="delivery_file" class="form-control">
                                                        </div>
                                                        <button type="submit" class="btn btn-success">Send Delivery & Complete Order</button>
                                                    </form>
                                                    <?php endif; ?>

                                                    <?php if ($order['status'] === ORDER_PENDING || $order['status'] === ORDER_PROCESSING): ?>
                                                    <hr>
                                                    <form action="" method="POST" onsubmit="return confirm('Are you sure you want to cancel and refund this order?')">
                                                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                        <input type="hidden" name="update_status" value="1">
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="btn btn-danger">Cancel & Refund Order</button>
                                                    </form>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($orders)): ?>
                            <tr><td colspan="7" class="text-center py-4">No orders found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/admin_footer.php'; ?>
