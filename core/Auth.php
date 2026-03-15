<?php
namespace Core;

use PDO;

class Auth {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // User registration
    public function register($name, $email, $password, $role_id = ROLE_CUSTOMER) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, role_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$name, $email, $hashed_password, $role_id]);
    }

    // User login
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role_id'] = $user['role_id'];
            $_SESSION['email'] = $user['email'];
            return true;
        }
        return false;
    }

    // Logout
    public static function logout() {
        session_unset();
        session_destroy();
    }

    // Check if user is logged in
    public static function check() {
        return isset($_SESSION['user_id']);
    }

    // Check if user has a specific role
    public static function hasRole($role_id) {
        return isset($_SESSION['role_id']) && $_SESSION['role_id'] == $role_id;
    }

    // Check if user has permission
    public static function hasPermission($permission_slug) {
        if (!isset($_SESSION['user_id'])) return false;
        
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT p.slug 
            FROM permissions p
            JOIN role_permissions rp ON p.id = rp.permission_id
            WHERE rp.role_id = ? AND p.slug = ?
        ");
        $stmt->execute([$_SESSION['role_id'], $permission_slug]);
        return (bool)$stmt->fetch();
    }

    // Get current user info
    public function getCurrentUser() {
        if (!self::check()) return null;
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
}
