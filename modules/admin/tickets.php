<?php
use Core\Support;
use Core\Helpers;
use Core\Auth;

if (!Auth::hasRole(ROLE_ADMIN) && !Auth::hasRole(ROLE_MODERATOR)) {
    Helpers::redirect('/login');
}

$supportModel = new Support();

// Filter logic
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

// Fetch tickets using the new method
$tickets = $supportModel->getAllTickets($filter_status);

$page_title = 'Manage Support Tickets';
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>
<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <div class="card shadow">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">All Support Tickets</h5>
            <div class="btn-group">
                <a href="?status=all" class="btn btn-sm btn-outline-light <?php echo $filter_status === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?status=open" class="btn btn-sm btn-outline-danger <?php echo $filter_status === 'open' ? 'active' : ''; ?>">Open</a>
                <a href="?status=pending_customer" class="btn btn-sm btn-outline-warning <?php echo $filter_status === 'pending_customer' ? 'active' : ''; ?>">Pending Customer</a>
                <a href="?status=closed" class="btn btn-sm btn-outline-secondary <?php echo $filter_status === 'closed' ? 'active' : ''; ?>">Closed</a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Subject</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['id']; ?></td>
                                <td><?php echo $ticket['subject']; ?></td>
                                <td><?php echo $ticket['customer_name']; ?></td>
                                <td>
                                    <?php
                                        $status_class = 'secondary';
                                        if ($ticket['status'] == 'open') $status_class = 'danger';
                                        if ($ticket['status'] == 'pending_customer') $status_class = 'warning';
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/admin/ticket?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-primary">View & Reply</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (empty($tickets)): ?>
                            <tr><td colspan="6" class="text-center py-4">No support tickets found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>
<?php include 'includes/admin_footer.php'; ?>
