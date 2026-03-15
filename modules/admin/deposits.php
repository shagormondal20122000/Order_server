<?php
use Core\Database;
use Core\Helpers;
use Core\Auth;
use Core\Wallet;

if (!Auth::hasRole(ROLE_ADMIN) && !Auth::hasRole(ROLE_MODERATOR)) {
    Helpers::redirect('/login');
}

$db = Database::getInstance();
$wallet = new Wallet();

// Handle Deposit Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_deposit'])) {
    $deposit_id = (int)$_POST['deposit_id'];
    $new_status = $_POST['status'];
    
    // Fetch deposit info
    $stmtD = $db->prepare("SELECT * FROM deposits WHERE id = ? AND status = 'pending'");
    $stmtD->execute([$deposit_id]);
    $deposit = $stmtD->fetch();

    if ($deposit) {
        if ($new_status === 'approved') {
            // Add balance to user wallet
            if ($wallet->addBalance($deposit['user_id'], $deposit['amount'], "Deposit #$deposit_id approved")) {
                $stmt = $db->prepare("UPDATE deposits SET status = 'approved' WHERE id = ?");
                $stmt->execute([$deposit_id]);
                $success = "Deposit #$deposit_id approved and balance added!";
            } else {
                $error = "Failed to update user wallet.";
            }
        } elseif ($new_status === 'rejected') {
            $stmt = $db->prepare("UPDATE deposits SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$deposit_id]);
            $success = "Deposit #$deposit_id rejected.";
        }
    } else {
        $error = "Deposit not found or already processed.";
    }
}

// Fetch all pending deposits
$stmtPending = $db->query("
    SELECT d.*, u.name as customer_name, u.email as customer_email, pm.name as method_name 
    FROM deposits d 
    JOIN users u ON d.user_id = u.id 
    JOIN payment_methods pm ON d.payment_method_id = pm.id 
    ORDER BY d.id DESC
");
$deposits = $stmtPending->fetchAll();

$page_title = 'Manage Deposits';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="card shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Deposits</h5>
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
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Proof/Reference</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deposits as $d): ?>
                            <tr>
                                <td>#<?php echo $d['id']; ?></td>
                                <td>
                                    <strong><?php echo $d['customer_name']; ?></strong><br>
                                    <small class="text-muted"><?php echo $d['customer_email']; ?></small>
                                </td>
                                <td><?php echo Helpers::formatCurrency($d['amount']); ?></td>
                                <td><?php echo $d['method_name']; ?></td>
                                <td><code class="small"><?php echo htmlspecialchars($d['proof']); ?></code></td>
                                <td>
                                    <span class="badge bg-<?php echo $d['status'] == 'approved' ? 'success' : ($d['status'] == 'pending' ? 'warning' : 'danger'); ?>">
                                        <?php echo strtoupper($d['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($d['created_at'])); ?></td>
                                <td>
                                    <?php if ($d['status'] == 'pending'): ?>
                                        <form action="" method="POST" class="d-inline">
                                            <input type="hidden" name="deposit_id" value="<?php echo $d['id']; ?>">
                                            <input type="hidden" name="update_deposit" value="1">
                                            <button type="submit" name="status" value="approved" class="btn btn-sm btn-success" onclick="return confirm('Approve this deposit?')">Approve</button>
                                            <button type="submit" name="status" value="rejected" class="btn btn-sm btn-danger" onclick="return confirm('Reject this deposit?')">Reject</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Processed</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($deposits)): ?>
                            <tr><td colspan="8" class="text-center py-4">No deposits found.</td></tr>
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
