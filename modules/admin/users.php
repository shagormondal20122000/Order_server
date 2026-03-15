<?php
use Core\User;
use Core\Helpers;
use Core\Auth;

if (!Auth::hasRole(ROLE_ADMIN)) {
    Helpers::redirect('/login');
}

$userModel = new User();
$error = '';
$success = '';

// Handle Balance Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_balance'])) {
    $user_id = (int)$_POST['user_id'];
    $amount = (float)$_POST['amount'];
    $type = $_POST['type']; // add or subtract
    $desc = Helpers::sanitize($_POST['description'] ?? 'Admin Manual Adjustment');

    if ($amount <= 0) {
        $error = "Amount must be greater than zero.";
    } else {
        if ($userModel->updateBalance($user_id, $amount, $type, $desc)) {
            $success = "User balance updated successfully.";
        } else {
            $error = "Failed to update user balance.";
        }
    }
}

// Handle Role Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = (int)$_POST['user_id'];
    $role_id = (int)$_POST['role_id'];

    if ($userModel->updateRole($user_id, $role_id)) {
        $success = "User role updated successfully.";
    } else {
        $error = "Failed to update user role.";
    }
}

// Handle User Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = $_POST['status'];

    $db = Core\Database::getInstance();
    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
    if ($stmt->execute([$new_status, $user_id])) {
        $success = "User status updated successfully.";
    } else {
        $error = "Failed to update user status.";
    }
}

$users = $userModel->getAllUsers();
$roles = $userModel->getRoles();

$page_title = 'Manage Users';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="card shadow">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">All Users</h5>
        </div>
        <div class="card-body p-0">
            <?php if ($error): ?><div class="alert alert-danger m-3"><?php echo $error; ?></div><?php endif; ?>
            <?php if ($success): ?><div class="alert alert-success m-3"><?php echo $success; ?></div><?php endif; ?>
            
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Wallet Balance</th>
                            <th>Status</th>
                            <th>Joined At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                </td>
                                <td>
                                    <form action="" method="POST" class="d-flex gap-1">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="update_role" value="1">
                                        <select name="role_id" class="form-select form-select-sm" onchange="this.form.submit()">
                                            <?php foreach ($roles as $role): ?>
                                                <option value="<?php echo $role['id']; ?>" <?php echo $user['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $role['name']; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td>
                                    <div class="fw-bold text-primary mb-1"><?php echo Helpers::formatCurrency($user['wallet_balance']); ?></div>
                                    <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" data-bs-toggle="modal" data-bs-target="#balanceModal<?php echo $user['id']; ?>">
                                        <i class="fas fa-edit small"></i> Manage
                                    </button>

                                    <!-- Balance Modal -->
                                    <div class="modal fade" id="balanceModal<?php echo $user['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-dark">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Manage Balance: <?php echo htmlspecialchars($user['name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form action="" method="POST">
                                                    <div class="modal-body text-start">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="update_balance" value="1">
                                                        
                                                        <div class="mb-3">
                                                            <label class="form-label">Current Balance: <strong><?php echo Helpers::formatCurrency($user['wallet_balance']); ?></strong></label>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Action Type</label>
                                                            <select name="type" class="form-select" required>
                                                                <option value="add">Add Balance (+)</option>
                                                                <option value="subtract">Reduce Balance (-)</option>
                                                            </select>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Amount</label>
                                                            <input type="number" step="0.01" name="amount" class="form-control" required placeholder="0.00">
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label">Description (Optional)</label>
                                                            <input type="text" name="description" class="form-control" placeholder="Reason for adjustment">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary">Update Balance</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] == 'active' ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <?php if ($user['status'] == 'active'): ?>
                                            <button type="submit" name="status" value="suspended" class="btn btn-sm btn-warning">Suspend</button>
                                        <?php else: ?>
                                            <button type="submit" name="status" value="active" class="btn btn-sm btn-success">Activate</button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/admin_footer.php'; ?>
