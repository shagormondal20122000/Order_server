<?php
namespace Core;

use PDO;

class Order {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Create Order
    public function createOrder($user_id, $items) {
        try {
            $this->db->beginTransaction();

            $total_amount = 0;
            foreach ($items as $item) {
                $total_amount += $item['price'] * $item['qty'];
            }

            // Create Order record
            $stmt = $this->db->prepare("INSERT INTO orders (user_id, total_amount, status, payment_status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $total_amount, ORDER_PENDING, PAYMENT_PENDING]);
            $order_id = $this->db->lastInsertId();

            // Create Order Items
            $stmt = $this->db->prepare("INSERT INTO order_items (order_id, product_id, price, qty, customer_data) VALUES (?, ?, ?, ?, ?)");
            foreach ($items as $item) {
                $stmt->execute([$order_id, $item['product_id'], $item['price'], $item['qty'], $item['customer_data']]);
            }

            $this->db->commit();
            return $order_id;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Process Order (Automated Delivery)
    public function processOrder($order_id) {
        try {
            $this->db->beginTransaction();

            // Get order items
            $stmt = $this->db->prepare("SELECT * FROM order_items WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $items = $stmt->fetchAll();

            $stockModel = new Stock();

            foreach ($items as $item) {
                // Assign stock to order
                $stockModel->assignStockToOrder($item['product_id'], $order_id, $item['qty']);
                
                // Get delivered content
                $delivered_content = $stockModel->getDeliveredContent($order_id, $item['product_id']);
                $content_text = implode("\n", $delivered_content);

                // Update order item with delivered content
                $stmtUpdate = $this->db->prepare("UPDATE order_items SET delivery_content = ? WHERE id = ?");
                $stmtUpdate->execute([$content_text, $item['id']]);
            }

            // Update order status
            $stmtOrder = $this->db->prepare("UPDATE orders SET status = ?, payment_status = ? WHERE id = ?");
            $stmtOrder->execute([ORDER_COMPLETED, PAYMENT_PAID, $order_id]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Get User Orders
    public function getUserOrders($user_id) {
        $stmt = $this->db->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }

    // Get Order Details
    public function getOrderDetails($order_id) {
        $stmt = $this->db->prepare("
            SELECT oi.*, p.title as product_title, p.stock_type 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        return $stmt->fetchAll();
    }
}
