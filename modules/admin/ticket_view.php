<?php
use Core\Support;
use Core\Helpers;
use Core\Auth;

if (!Auth::hasRole(ROLE_ADMIN) && !Auth::hasRole(ROLE_MODERATOR)) {
    Helpers::redirect('/login');
}

$supportModel = new Support();
$error = '';
$success = '';

Helpers::generateCsrfToken();

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ticket = $supportModel->getTicketDetails($ticket_id);

if (!$ticket) {
    die("Ticket not found.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_ticket'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } elseif ($ticket['status'] === 'closed') {
        $error = "Ticket is already closed.";
    } else {
        if ($supportModel->closeTicket($ticket_id)) {
            $success = "Ticket closed successfully.";
            $ticket = $supportModel->getTicketDetails($ticket_id);
        } else {
            $error = "Failed to close ticket.";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = "CSRF token validation failed.";
    } elseif ($ticket['status'] === 'closed') {
        $error = "This ticket is closed.";
    } else {
        $message = Helpers::sanitize($_POST['message'] ?? '');
        if ($message === '') {
            $error = "Message is required.";
        } else {
            if ($supportModel->replyToTicket($ticket_id, $_SESSION['user_id'], $message)) {
                $success = "Reply sent successfully.";
                $ticket = $supportModel->getTicketDetails($ticket_id);
            } else {
                $error = "Failed to send reply.";
            }
        }
    }
}

$page_title = 'Ticket #' . $ticket['id'];
include 'includes/admin_header.php';
include 'includes/admin_sidebar.php';
?>

<div class="admin-content flex-grow-1 p-4">
    <div class="container-fluid">
        <?php
            $status_class = 'secondary';
            if ($ticket['status'] === 'open') $status_class = 'danger';
            if ($ticket['status'] === 'pending_customer') $status_class = 'warning';
        ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">Ticket #<?php echo $ticket['id']; ?></h4>
                <div class="text-muted"><?php echo htmlspecialchars($ticket['subject']); ?></div>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-<?php echo $status_class; ?>"><?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?></span>
                <?php if ($ticket['status'] !== 'closed'): ?>
                    <form action="" method="POST" class="d-inline">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="close_ticket" value="1">
                        <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Close this ticket?')">Close Ticket</button>
                    </form>
                <?php endif; ?>
                <a href="<?php echo BASE_URL; ?>/admin/tickets" class="btn btn-outline-secondary btn-sm">Back</a>
            </div>
        </div>

        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="p-2 rounded mb-4" style="max-height: 420px; overflow-y: auto; background: #f8f9fa;">
                            <?php foreach ($ticket['messages'] as $msg): ?>
                                <?php $is_me = ($msg['user_id'] == $_SESSION['user_id']); ?>
                                <div class="d-flex mb-3 <?php echo $is_me ? 'justify-content-end' : ''; ?>">
                                    <div class="p-3 rounded-3 <?php echo $is_me ? 'bg-white border' : 'bg-primary text-white'; ?>" style="max-width: 75%;">
                                        <div class="small mb-2 <?php echo $is_me ? 'text-muted' : 'text-white-50'; ?>">
                                            <?php echo htmlspecialchars($msg['author_name']); ?> (<?php echo htmlspecialchars($msg['role_name']); ?>) • <?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?>
                                        </div>
                                        <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <h5 class="mb-3">Reply</h5>
                        <?php if ($ticket['status'] === 'closed'): ?>
                            <div class="alert alert-secondary mb-0">This ticket is closed.</div>
                        <?php else: ?>
                            <form action="" method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                <input type="hidden" name="reply_ticket" value="1">
                                <div class="mb-3">
                                    <textarea name="message" class="form-control" rows="5" placeholder="Write your reply..." required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Send Reply</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-user-circle me-2"></i>Customer Profile</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3 text-center">
                            <div class="display-6 text-primary mb-2">
                                <i class="fas fa-user"></i>
                            </div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($ticket['customer_name']); ?></h5>
                            <span class="badge bg-info"><?php echo htmlspecialchars($ticket['customer_role']); ?></span>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="small text-muted d-block">Email Address</label>
                            <strong><?php echo htmlspecialchars($ticket['customer_email']); ?></strong>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted d-block">Wallet Balance</label>
                            <strong class="text-success"><?php echo number_format($ticket['wallet_balance'], 2); ?> BDT</strong>
                        </div>
                        <div class="mb-3">
                            <label class="small text-muted d-block">Account Status</label>
                            <span class="badge bg-<?php echo $ticket['customer_status'] === 'active' ? 'success' : 'danger'; ?>">
                                <?php echo ucfirst($ticket['customer_status']); ?>
                            </span>
                        </div>
                        <div class="d-grid">
                            <a href="<?php echo BASE_URL; ?>/admin/users" class="btn btn-outline-primary btn-sm">View All Users</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/admin_footer.php'; ?>

