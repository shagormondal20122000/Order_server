<?php
use Core\Database;
use Core\Helpers;
use Core\Auth;

if (!Auth::check()) {
    Helpers::redirect('/login');
}

$db = Database::getInstance();
$error = '';
$success = '';

// Fetch Payment Methods
$stmt = $db->prepare("SELECT * FROM payment_methods WHERE status = 'active' AND (LOWER(name) LIKE '%bkash%')");
$stmt->execute();
$methods = $stmt->fetchAll();

// Handle Deposit Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deposit_request'])) {
    $amount = (float)$_POST['amount'];
    $method_id = (int)$_POST['method_id'];
    $proof = Helpers::sanitize($_POST['proof']); // TrxID or proof

    // Check if bKash Auto Recharge is selected (Assuming method name is bKash)
    $stmtMethod = $db->prepare("SELECT name FROM payment_methods WHERE id = ?");
    $stmtMethod->execute([$method_id]);
    $methodName = strtolower($stmtMethod->fetchColumn());

    if ($methodName === 'bkash' || $methodName === 'bkash auto') {
        // bKash Auto Recharge Logic
        $trxid = $proof;
        
        // Fetch API URL from settings
        $stmtUrl = $db->query("SELECT value FROM settings WHERE key = 'bkash_api_url'");
        $apiUrlBase = $stmtUrl->fetchColumn() ?: "https://api.bdx.kg/bkash/submit.php?trxid=";
        $apiUrl = $apiUrlBase . urlencode($trxid);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);

            if ($data && isset($data['transactionStatus']) && $data['transactionStatus'] === 'Completed') {
                $receivedAmount = (float)$data['amount'];
                
                // Verify amount if provided in form (optional but recommended)
                // if ($receivedAmount != $amount) { ... }

                $db->beginTransaction();
                try {
                    // 1. Insert into deposits as approved
                    $stmt = $db->prepare("INSERT INTO deposits (user_id, amount, payment_method_id, proof, status) VALUES (?, ?, ?, ?, 'approved')");
                    $stmt->execute([$_SESSION['user_id'], $receivedAmount, $method_id, $trxid]);

                    // 2. Update user wallet
                    $stmtUpdate = $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
                    $stmtUpdate->execute([$receivedAmount, $_SESSION['user_id']]);

                    // 3. Log transaction
                    $stmtLog = $db->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
                    $stmtLog->execute([$_SESSION['user_id'], $receivedAmount, "bKash Auto Recharge - TrxID: $trxid"]);

                    $db->commit();
                    $success = "Recharge Successful! " . Helpers::formatCurrency($receivedAmount) . " has been added to your wallet.";
                } catch (\Exception $e) {
                    $db->rollBack();
                    $error = "Transaction failed internally. Please contact admin.";
                }
            } else {
                // If API fails or transaction not found/completed
                $apiMsg = isset($data['statusMessage']) ? $data['statusMessage'] : (isset($data['error']) ? $data['error'] : "Invalid Transaction ID or API error.");
                $error = "bKash API Error: " . $apiMsg;
                if (!$data) {
                    $error = "Could not connect to bKash API. Please try again later.";
                }
            }
        } catch (\Exception $e) {
            $error = "API connection error.";
        }
    } else {
        // Manual Deposit Request
        if ($amount < 10) {
            $error = "Minimum deposit amount is 10 ৳.";
        } elseif ($method_id < 1) {
            $error = "Please select a payment method.";
        } else {
            $stmt = $db->prepare("INSERT INTO deposits (user_id, amount, payment_method_id, proof, status) VALUES (?, ?, ?, ?, 'pending')");
            if ($stmt->execute([$_SESSION['user_id'], $amount, $method_id, $proof])) {
                $success = "Deposit request submitted successfully! Admin will review it soon.";
            } else {
                $error = "Failed to submit deposit request.";
            }
        }
    }
}

// Fetch User's Recent Deposits
$stmtRecent = $db->prepare("
    SELECT d.*, pm.name as method_name 
    FROM deposits d 
    JOIN payment_methods pm ON d.payment_method_id = pm.id 
    WHERE d.user_id = ? 
    ORDER BY d.id DESC LIMIT 10
");
$stmtRecent->execute([$_SESSION['user_id']]);
$deposits = $stmtRecent->fetchAll();

// Fetch Payment Numbers from settings
$stmtSettings = $db->query("SELECT * FROM settings WHERE key = 'bkash_merchant_number'");
$payment_settings = $stmtSettings->fetchAll(PDO::FETCH_KEY_PAIR);

$page_title = 'Deposit Money';
include 'includes/app_header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h5>Deposit Money</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                    
                    <!-- Payment Numbers Display -->
                    <div class="alert alert-info py-3 mb-4 text-center border-0 shadow-sm" style="background-color: #e3f2fd; border-radius: 10px;">
                        <div class="small text-muted mb-1" style="font-weight: 500;">bKash Merchant (Payment)</div>
                        <div class="d-flex align-items-center justify-content-center gap-2">
                            <h4 class="mb-0 text-primary" style="font-weight: 700; letter-spacing: 1px;">
                                <span id="merchant_number"><?php echo htmlspecialchars($payment_settings['bkash_merchant_number'] ?? 'Not set'); ?></span>
                            </h4>
                            <button type="button" class="btn btn-sm btn-outline-primary border-0 p-1" onclick="copyToClipboard()" title="Copy Number">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div id="copy_msg" class="small text-success mt-1" style="display: none; font-size: 0.75rem;">Copied!</div>
                    </div>

                    <form action="" method="POST">
                        <input type="hidden" name="deposit_request" value="1">
                        <div class="mb-3">
                            <label class="form-label">Payment Method</label>
                            <select name="method_id" class="form-select" id="payment_method" required>
                                <option value="">Select Method</option>
                                <?php foreach ($methods as $m): ?>
                                    <?php $display_name = (strtolower($m['name']) === 'bkash personal') ? 'bKash Payment' : $m['name']; ?>
                                    <option value="<?php echo $m['id']; ?>" data-name="<?php echo strtolower($m['name']); ?>"><?php echo $display_name; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3" id="amount_group">
                            <label class="form-label">Amount (৳)</label>
                            <input type="number" name="amount" class="form-control" placeholder="Min 10 ৳">
                        </div>
                        <div class="mb-3">
                            <label class="form-label" id="proof_label">Transaction Reference / Proof</label>
                            <textarea name="proof" class="form-control" placeholder="Type TrxID or reference here" required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-dark">Submit Request</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer bg-light">
                    <p class="small text-muted mb-0"><strong>Note:</strong> উপরে দেওয়া মার্চেন্ট নাম্বারে <strong>Payment</strong> করুন এবং পেমেন্ট শেষে TrxID টি এখানে দিয়ে সাবমিট করুন। আপনার ব্যালেন্স অটোমেটিক অ্যাড হয়ে যাবে।</p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5>Recent Deposits</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deposits as $d): ?>
                                    <tr>
                                        <td>#<?php echo $d['id']; ?></td>
                                        <td><?php echo Helpers::formatCurrency($d['amount']); ?></td>
                                        <td><?php echo $d['method_name']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $d['status'] == 'approved' ? 'success' : ($d['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                                <?php echo strtoupper($d['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($d['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($deposits)): ?>
                                    <tr><td colspan="5" class="text-center py-4">No deposits found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyToClipboard() {
    const numberText = document.getElementById('merchant_number').innerText;
    navigator.clipboard.writeText(numberText).then(() => {
        const copyMsg = document.getElementById('copy_msg');
        copyMsg.style.display = 'block';
        setTimeout(() => {
            copyMsg.style.display = 'none';
        }, 2000);
    });
}

document.getElementById('payment_method').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const methodName = selectedOption.getAttribute('data-name') || '';
    const amountGroup = document.getElementById('amount_group');
    const amountInput = amountGroup.querySelector('input');
    const proofLabel = document.getElementById('proof_label');
    const proofTextarea = document.querySelector('textarea[name="proof"]');

    if (methodName.includes('bkash')) {
        amountGroup.style.display = 'none';
        amountInput.removeAttribute('required');
        proofLabel.innerText = 'bKash Transaction ID (TrxID)';
        proofTextarea.placeholder = 'Example: CEE34XDZUN';
    } else {
        amountGroup.style.display = 'block';
        amountInput.setAttribute('required', 'required');
        proofLabel.innerText = 'Transaction Reference / Proof';
        proofTextarea.placeholder = 'Type TrxID or reference here';
    }
});

// Trigger once on load
document.getElementById('payment_method').dispatchEvent(new Event('change'));
</script>

<?php include 'includes/app_footer.php'; ?>
