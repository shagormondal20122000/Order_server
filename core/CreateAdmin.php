<?php
namespace Core;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Auth.php';

$auth = new Auth();
$name = "Super Admin";
$email = "admin@example.com";
$password = "admin123";

if ($auth->register($name, $email, $password, ROLE_ADMIN)) {
    echo "Admin user created successfully.\nEmail: $email\nPassword: $password\n";
} else {
    echo "Failed to create admin user. It might already exist.\n";
}
