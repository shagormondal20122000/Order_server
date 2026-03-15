<?php
namespace Core;

use PDO;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Get all users with their roles
    public function getAllUsers() {
        $stmt = $this->db->query("
            SELECT u.*, r.name as role_name 
            FROM users u 
            JOIN roles r ON u.role_id = r.id 
            ORDER BY u.id DESC
        ");
        return $stmt->fetchAll();
    }

    // Update user balance
    public function updateBalance($user_id, $amount, $type = 'add', $description = 'Admin Update') {
        try {
            $this->db->beginTransaction();

            $operator = ($type === 'add') ? '+' : '-';
            $stmt = $this->db->prepare("UPDATE users SET wallet_balance = wallet_balance $operator ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            // Log transaction
            $trans_type = ($type === 'add') ? 'credit' : 'debit';
            $stmtLog = $this->db->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (?, ?, ?, ?)");
            $stmtLog->execute([$user_id, $amount, $trans_type, $description]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Get roles
    public function getRoles() {
        return $this->db->query("SELECT * FROM roles")->fetchAll();
    }

    // Update user role
    public function updateRole($user_id, $role_id) {
        $stmt = $this->db->prepare("UPDATE users SET role_id = ? WHERE id = ?");
        return $stmt->execute([$role_id, $user_id]);
    }
}
