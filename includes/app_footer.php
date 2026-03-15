<?php
if (\Core\Auth::check()) {
    if (\Core\Auth::hasRole(ROLE_ADMIN) || \Core\Auth::hasRole(ROLE_MODERATOR)) {
        \Core\Helpers::redirect('/admin/dashboard');
    }
    include 'includes/user_footer.php';
} else {
    include 'includes/footer.php';
}

