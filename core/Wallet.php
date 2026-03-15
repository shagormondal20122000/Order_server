<?php
namespace Core;

use PDO;

class Wallet {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Get User Balance
    public function getBalance($user_id) {
        $stmt = $this->db->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return (float)$stmt->fetchColumn();
    }

    // Add Balance
    public function addBalance($user_id, $amount, $description) {
        try {
            $this->db->beginTransaction();

            // Update user balance
            $stmt = $this->db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            // Log transaction
            $stmtLog = $this->db->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (?, ?, 'credit', ?)");
            $stmtLog->execute([$user_id, $amount, $description]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Deduct Balance
    public function deductBalance($user_id, $amount, $description) {
        try {
            $current_balance = $this->getBalance($user_id);
            if ($current_balance < $amount) return false;

            $this->db->beginTransaction();

            // Update user balance
            $stmt = $this->db->prepare("UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?");
            $stmt->execute([$amount, $user_id]);

            // Log transaction
            $stmtLog = $this->db->prepare("INSERT INTO wallet_transactions (user_id, amount, type, description) VALUES (?, ?, 'debit', ?)");
            $stmtLog->execute([$user_id, $amount, $description]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Get Transaction History
    public function getTransactions($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM wallet_transactions WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
}
