<?php
namespace Core;

use PDO;

class Stock {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Add Stock (Single or Bulk)
    public function addStock($product_id, $content_list) {
        $stmt = $this->db->prepare("INSERT INTO product_stocks (product_id, content) VALUES (?, ?)");
        $count = 0;
        foreach ($content_list as $content) {
            $content = trim($content);
            if (!empty($content)) {
                $stmt->execute([$product_id, $content]);
                $count++;
            }
        }
        return $count;
    }

    // Get Available Stock Count
    public function getAvailableStock($product_id) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM product_stocks WHERE product_id = ? AND status = 'available'");
        $stmt->execute([$product_id]);
        return $stmt->fetchColumn();
    }

    // Get Stock List for Admin
    public function getStockList($product_id) {
        $stmt = $this->db->prepare("SELECT * FROM product_stocks WHERE product_id = ? ORDER BY id DESC");
        $stmt->execute([$product_id]);
        return $stmt->fetchAll();
    }

    // Assign Stock to Order
    public function assignStockToOrder($product_id, $order_id, $qty) {
        $stmt = $this->db->prepare("
            UPDATE product_stocks 
            SET status = 'sold', order_id = ? 
            WHERE id IN (
                SELECT id FROM product_stocks 
                WHERE product_id = ? AND status = 'available' 
                LIMIT ?
            )
        ");
        return $stmt->execute([$order_id, $product_id, $qty]);
    }

    // Get Delivered Content for Order
    public function getDeliveredContent($order_id, $product_id) {
        $stmt = $this->db->prepare("SELECT content FROM product_stocks WHERE order_id = ? AND product_id = ?");
        $stmt->execute([$order_id, $product_id]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
