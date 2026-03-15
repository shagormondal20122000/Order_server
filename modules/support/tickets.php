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

Helpers::generateCsrfToken();

// Handle New Ticket Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_ticket'])) {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        $subject = Helpers::sanitize($_POST['subject']);
        $message = Helpers::sanitize($_POST['message']);

        if (empty($subject) || empty($message)) {
            $error = "Subject and message are required.";
        } else {
            $ticket_id = $supportModel->createTicket($_SESSION['user_id'], $subject, $message);
            if ($ticket_id) {
                $success = "Support ticket created successfully! Our team will get back to you soon.";
            } else {
                $error = "Failed to create ticket.";
            }
        }
    }
}

$tickets = $supportModel->getUserTickets($_SESSION['user_id']);

$page_title = 'My Support Tickets';
include 'includes/app_header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h5>Create New Ticket</h5>
                </div>
                <div class="card-body">
                    <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                    
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="create_ticket" value="1">
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <input type="text" name="subject" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" required></textarea>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Submit Ticket</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-dark text-white">
                    <h5>My Tickets</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Subject</th>
                                    <th>Status</th>
                                    <th>Last Updated</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tickets as $ticket): ?>
                                    <tr>
                                        <td>#<?php echo $ticket['id']; ?></td>
                                        <td><?php echo $ticket['subject']; ?></td>
                                        <td>
                                            <?php
                                                $status_class = 'secondary';
                                                if ($ticket['status'] == 'open') $status_class = 'primary';
                                                if ($ticket['status'] == 'pending_customer') $status_class = 'warning';
                                            ?>
                                            <span class="badge bg-<?php echo $status_class; ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $ticket['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($ticket['created_at'])); ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/support/ticket?id=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info text-white">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($tickets)): ?>
                                    <tr><td colspan="5" class="text-center py-4">You have no support tickets.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/app_footer.php'; ?>
