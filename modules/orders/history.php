<?php
use Core\Order;
use Core\Helpers;
use Core\Auth;

if (!Auth::check()) {
    Helpers::redirect('/login');
}

$orderModel = new Order();
$orders = $orderModel->getUserOrders($_SESSION['user_id']);

$page_title = 'Order History';
include 'includes/app_header.php';
?>

<script>
function copyPaymentLink(id) {
    const copyText = document.getElementById(id);
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);
    alert("Payment link copied to clipboard!");
}

function copyDeliveredText(id) {
    const copyText = document.getElementById(id);
    copyText.select();
    copyText.setSelectionRange(0, 999999);
    navigator.clipboard.writeText(copyText.value);
    alert("Text copied to clipboard!");
}
</script>

<div class="container mt-4">
    <h2 class="mb-4">My Purchase History</h2>
    <div class="row">
        <?php foreach ($orders as $order): ?>
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <span><strong>Order #<?php echo $order['id']; ?></strong> - <?php echo date('M d, Y H:i', strtotime($order['created_at'])); ?></span>
                        <span class="badge bg-<?php echo $order['status'] == ORDER_COMPLETED ? 'success' : ($order['status'] == ORDER_PENDING ? 'warning' : 'danger'); ?>">
                            <?php echo strtoupper($order['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Qty</th>
                                        <th>Subtotal</th>
                                        <th>Delivered Content</th>
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
                                            <td><?php echo Helpers::formatCurrency($item['price'] * $item['qty']); ?></td>
                                            <td>
                                                <?php if ($order['status'] == ORDER_COMPLETED): ?>
                                                    <?php $delivered = $item['delivery_content'] ?? ''; ?>
                                                    <?php if (strpos($delivered, '{') === 0): ?>
                                                        <?php $payload = json_decode($delivered, true); ?>
                                                        <?php if (is_array($payload) && ($payload['type'] ?? '') === 'payment_link'): ?>
                                                            <div class="card bg-light border-0 small p-2 mb-2">
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span>Citizen ID:</span>
                                                                    <strong><?php echo $payload['holding_data']['citizen_id'] ?? 'N/A'; ?></strong>
                                                                </div>
                                                                <div class="d-flex justify-content-between mb-1">
                                                                    <span>Holding ID:</span>
                                                                    <strong><?php echo $payload['holding_data']['holding_id'] ?? 'N/A'; ?></strong>
                                                                </div>
                                                                <div class="d-flex justify-content-between">
                                                                    <span>Total Demand:</span>
                                                                    <strong class="text-danger"><?php echo $payload['holding_data']['total_demand'] ?? '0'; ?> BDT</strong>
                                                                </div>
                                                            </div>
                                                            <div class="input-group input-group-sm">
                                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($payload['url']); ?>" id="pay_link_<?php echo $item['id']; ?>" readonly>
                                                                <button class="btn btn-primary" type="button" onclick="copyPaymentLink('pay_link_<?php echo $item['id']; ?>')">
                                                                    <i class="fas fa-copy"></i> Copy
                                                                </button>
                                                                <a href="<?php echo htmlspecialchars($payload['url']); ?>" target="_blank" class="btn btn-success">
                                                                    <i class="fas fa-external-link-alt"></i> Pay
                                                                </a>
                                                            </div>
                                                        <?php elseif (is_array($payload) && ($payload['type'] ?? '') === 'birth_data'): ?>
                                                            <div class="birth-results mt-2">
                                                                <h6 class="small fw-bold">Search Results (<?php echo $payload['count']; ?>)</h6>
                                                                <?php foreach ($payload['results'] as $index => $res): ?>
                                                                    <div class="card bg-light border-0 small p-2 mb-2">
                                                                        <div class="d-flex justify-content-end mb-1">
                                                                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="copyDeliveredText('birth_<?php echo $item['id']; ?>_<?php echo $index; ?>')">
                                                                                <i class="fas fa-copy me-1"></i> Copy
                                                                            </button>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mb-1">
                                                                            <span>Name (EN):</span>
                                                                            <strong><?php echo $res['personNameEn']; ?></strong>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mb-1">
                                                                            <span>Name (BN):</span>
                                                                            <strong><?php echo $res['personNameBn']; ?></strong>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mb-1">
                                                                            <span>Gender:</span>
                                                                            <strong><?php echo htmlspecialchars($res['gender'] ?? 'N/A'); ?></strong>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between mb-1">
                                                                            <span>Date of Birth:</span>
                                                                            <strong class="text-primary"><?php echo $res['personBirthDate']; ?></strong>
                                                                        </div>
                                                                        <div class="d-flex justify-content-between">
                                                                            <span>UBRN:</span>
                                                                            <strong class="text-muted"><?php echo $res['ubrn']; ?></strong>
                                                                        </div>
                                                                        <textarea id="birth_<?php echo $item['id']; ?>_<?php echo $index; ?>" class="visually-hidden" readonly><?php echo htmlspecialchars(
                                                                            'Name (EN): ' . ($res['personNameEn'] ?? '') . "\n" .
                                                                            'Name (BN): ' . ($res['personNameBn'] ?? '') . "\n" .
                                                                            'Gender: ' . ($res['gender'] ?? '') . "\n" .
                                                                            'Date of Birth: ' . ($res['personBirthDate'] ?? '') . "\n" .
                                                                            'UBRN: ' . ($res['ubrn'] ?? ''),
                                                                            ENT_QUOTES,
                                                                            'UTF-8'
                                                                        ); ?></textarea>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php elseif (is_array($payload) && ($payload['type'] ?? '') === 'file' && !empty($payload['path'])): ?>
                                                            <a class="btn btn-sm btn-outline-primary" href="<?php echo BASE_URL; ?>/<?php echo htmlspecialchars($payload['path']); ?>" target="_blank">
                                                                Download File<?php echo !empty($payload['name']) ? ' (' . htmlspecialchars($payload['name']) . ')' : ''; ?>
                                                            </a>
                                                        <?php else: ?>
                                                            <div class="d-flex justify-content-end mb-1">
                                                                <button class="btn btn-sm btn-outline-secondary" type="button" onclick="copyDeliveredText('delivered_<?php echo $item['id']; ?>')">
                                                                    <i class="fas fa-copy me-1"></i> Copy
                                                                </button>
                                                            </div>
                                                            <pre class="bg-light p-2 mb-0" style="font-size: 0.85rem;"><?php echo htmlspecialchars($delivered); ?></pre>
                                                            <textarea id="delivered_<?php echo $item['id']; ?>" class="visually-hidden" readonly><?php echo htmlspecialchars($delivered); ?></textarea>
                                                        <?php endif; ?>
                                                    <?php elseif (strpos($delivered, 'http') === 0): ?>
                                                        <?php $links = explode("\n", $delivered); ?>
                                                        <div class="d-flex flex-column gap-2">
                                                            <?php foreach ($links as $index => $link): ?>
                                                                <a class="btn btn-sm btn-success" href="<?php echo htmlspecialchars(trim($link)); ?>" target="_blank">
                                                                    <i class="fas fa-download me-1"></i> Download Dakhila <?php echo count($links) > 1 ? ($index + 1) : ''; ?>
                                                                </a>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="d-flex justify-content-end mb-1">
                                                            <button class="btn btn-sm btn-outline-secondary" type="button" onclick="copyDeliveredText('delivered_<?php echo $item['id']; ?>')">
                                                                <i class="fas fa-copy me-1"></i> Copy
                                                            </button>
                                                        </div>
                                                        <pre class="bg-light p-2 mb-0" style="font-size: 0.85rem;"><?php echo htmlspecialchars($delivered); ?></pre>
                                                        <textarea id="delivered_<?php echo $item['id']; ?>" class="visually-hidden" readonly><?php echo htmlspecialchars($delivered); ?></textarea>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted small italic">Delivery pending...</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total Amount:</th>
                                        <th colspan="2" class="text-start h5"><?php echo Helpers::formatCurrency($order['total_amount']); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-shopping-bag fa-4x text-muted mb-3"></i>
                <h4>You haven't made any purchases yet.</h4>
                <a href="<?php echo BASE_URL; ?>/products" class="btn btn-primary mt-3">Browse Products</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/app_footer.php'; ?>
