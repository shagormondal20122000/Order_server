<?php
if (\Core\Auth::check()) {
    if (\Core\Auth::hasRole(ROLE_ADMIN) || \Core\Auth::hasRole(ROLE_MODERATOR)) {
        \Core\Helpers::redirect('/admin/dashboard');
    }
    include 'includes/user_header.php';
} else {
    include 'includes/header.php';
}

