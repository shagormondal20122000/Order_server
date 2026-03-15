<?php
use Core\Auth;
use Core\Helpers;

$is_logged_in = Auth::check();
if ($is_logged_in) {
    if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_MODERATOR)) {
        Helpers::redirect('/admin/dashboard');
    }
    Helpers::redirect('/dashboard');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Helpers::validateCsrfToken($_POST['csrf_token'])) {
        $error = "CSRF token validation failed.";
    } else {
        $email = Helpers::sanitize($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = "Email and Password are required.";
        } else {
            $auth = new Auth();
        if ($auth->login($email, $password)) {
            if (Auth::hasRole(ROLE_ADMIN) || Auth::hasRole(ROLE_MODERATOR)) {
                Helpers::redirect('/admin/dashboard');
            } else {
                Helpers::redirect('/dashboard');
            }
        } else {
            $error = "Invalid email or password.";
        }
        }
    }
}

$page_title = 'Login';
include 'includes/header.php';
?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow">
                <div class="card-header bg-primary text-white text-center">
                    <h4>Login</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    <form action="" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control" required>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Login</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center">
                    <p>Don't have an account? <a href="<?php echo BASE_URL; ?>/register">Register</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
