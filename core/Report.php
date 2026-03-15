<?php
namespace Core;

use PDO;

class Report {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Get sales report by date range
    public function getSalesReport($start_date, $end_date) {
        $stmt = $this->db->prepare("
            SELECT 
                DATE(created_at) as sale_date,
                COUNT(id) as total_orders,
                SUM(total_amount) as total_revenue
            FROM orders 
            WHERE status = 'completed' AND DATE(created_at) BETWEEN ? AND ?
            GROUP BY sale_date
            ORDER BY sale_date DESC
        ");
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll();
    }

    // Get product-wise sales report
    public function getProductWiseSales() {
        $stmt = $this->db->query("
            SELECT 
                p.title as product_name,
                SUM(oi.qty) as total_qty_sold,
                SUM(oi.price * oi.qty) as total_revenue
            FROM order_items oi
            JOIN products p ON oi.product_id = p.id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'completed'
            GROUP BY p.id
            ORDER BY total_revenue DESC
        ");
        return $stmt->fetchAll();
    }

    // Get customer-wise sales report
    public function getCustomerWiseSales() {
        $stmt = $this->db->query("
            SELECT 
                u.name as customer_name,
                u.email as customer_email,
                COUNT(o.id) as total_orders,
                SUM(o.total_amount) as total_spent
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.status = 'completed'
            GROUP BY u.id
            ORDER BY total_spent DESC
        ");
        return $stmt->fetchAll();
    }
}
