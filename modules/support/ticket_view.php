<?php
use Core\Support;
use Core\Helpers;
use Core\Auth;

if (!Auth::check()) {
    Helpers::redirect('/login');
}

$supportModel = new Support();
$error = '';
$success = '';
$is_staff = Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_MODERATOR);

Helpers::generateCsrfToken();

$ticket_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$ticket = $supportModel->getTicketDetails($ticket_id);

// Ensure user can only see their own ticket unless they are admin/mod
if (!$ticket || ($ticket['user_id'] != $_SESSION['user_id'] && !$is_staff)) {
    die("Ticket not found or access denied.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['close_ticket'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'])) {
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

// Handle Reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_ticket'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } elseif ($ticket['status'] === 'closed') {
        $error = "This ticket is closed.";
    } else {
        $message = Helpers::sanitize($_POST['message']);
        if (!empty($message)) {
            if ($supportModel->replyToTicket($ticket_id, $_SESSION['user_id'], $message)) {
                $success = "Reply sent successfully.";
                $ticket = $supportModel->getTicketDetails($ticket_id);
            } else {
                $error = "Failed to send reply.";
            }
        }
    }
}

$page_title = 'Ticket #' . $ticket['id'] . ' - ' . $ticket['subject'];
include 'includes/app_header.php';
?>

<div class="container mt-4">
    <?php
        $status_class = 'secondary';
        if ($ticket['status'] === 'open') $status_class = 'primary';
        if ($ticket['status'] === 'pending_customer') $status_class = 'warning';
    ?>
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <h4 class="mb-1">Ticket #<?php echo $ticket['id']; ?></h4>
            <div class="text-muted"><?php echo htmlspecialchars($ticket['subject']); ?></div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-<?php echo $status_class; ?>">
                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
            </span>
            <?php if ($ticket['status'] !== 'closed'): ?>
                <form action="" method="POST" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="close_ticket" value="1">
                    <button type="submit" class="btn btn-outline-danger btn-sm" onclick="return confirm('Close this ticket?')">Close Ticket</button>
                </form>
            <?php endif; ?>
            <a href="<?php echo BASE_URL; ?>/support/tickets" class="btn btn-outline-secondary btn-sm">Back</a>
        </div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="messages-container mb-4 p-2 rounded" style="max-height: 420px; overflow-y: auto; background: #f8f9fa;">
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

            <hr>
            <h5 class="mb-3">Reply</h5>

            <?php if ($ticket['status'] === 'closed'): ?>
                <div class="alert alert-secondary mb-0">This ticket is closed.</div>
            <?php else: ?>
                <form action="" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="reply_ticket" value="1">
                    <div class="mb-3">
                        <textarea name="message" class="form-control" rows="5" placeholder="Write your message..." required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/app_footer.php'; ?>
