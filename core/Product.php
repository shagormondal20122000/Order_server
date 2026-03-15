<?php
namespace Core;

use PDO;

class Product {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    // Create Category
    public function createCategory($name, $slug, $parent_id = null) {
        $stmt = $this->db->prepare("INSERT INTO categories (name, slug, parent_id) VALUES (?, ?, ?)");
        return $stmt->execute([$name, $slug, $parent_id]);
    }

    // Get Categories
    public function getCategories() {
        $stmt = $this->db->query("SELECT * FROM categories ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    // Create Product
    public function createProduct($data) {
        $stmt = $this->db->prepare("
            INSERT INTO products (title, slug, short_desc, full_desc, category_id, thumbnail, stock_type, delivery_type, status, order_format_template)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['short_desc'],
            $data['full_desc'],
            $data['category_id'],
            $data['thumbnail'],
            $data['stock_type'],
            $data['delivery_type'],
            $data['status'],
            $data['order_format_template']
        ]);
        return $this->db->lastInsertId();
    }

    // Add Product Price
    public function setPrice($product_id, $role_id, $price, $min_qty = 1) {
        // Check if price already exists for this role and product
        $stmt = $this->db->prepare("SELECT id FROM product_prices WHERE product_id = ? AND role_id IS ?");
        $stmt->execute([$product_id, $role_id]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $this->db->prepare("UPDATE product_prices SET price = ?, min_qty = ? WHERE id = ?");
            return $stmt->execute([$price, $min_qty, $existing['id']]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO product_prices (product_id, role_id, price, min_qty) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$product_id, $role_id, $price, $min_qty]);
        }
    }

    public function updateProduct($product_id, $data) {
        $stmt = $this->db->prepare("
            UPDATE products
            SET title = ?, slug = ?, short_desc = ?, full_desc = ?, category_id = ?, stock_type = ?, delivery_type = ?, status = ?, order_format_template = ?
            WHERE id = ?
        ");
        return $stmt->execute([
            $data['title'],
            $data['slug'],
            $data['short_desc'],
            $data['full_desc'],
            $data['category_id'],
            $data['stock_type'],
            $data['delivery_type'],
            $data['status'],
            $data['order_format_template'],
            $product_id
        ]);
    }

    public function getProducts($role_id = null, $category_id = null) {
        $sql = "
            SELECT p.*, c.name as category_name, 
            (SELECT price FROM product_prices WHERE product_id = p.id AND (role_id = ? OR role_id IS NULL) ORDER BY role_id DESC LIMIT 1) as current_price
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            " . ($category_id ? "WHERE p.category_id = ?" : "") . "
            ORDER BY p.id DESC
        ";
        $stmt = $this->db->prepare($sql);
        $params = [$role_id];
        if ($category_id) {
            $params[] = $category_id;
        }
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    // Get Single Product
    public function getProduct($id, $role_id = null) {
        $sql = "
            SELECT p.*, c.name as category_name,
            (SELECT price FROM product_prices WHERE product_id = p.id AND (role_id = ? OR role_id IS NULL) ORDER BY role_id DESC LIMIT 1) as current_price
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.id = ?
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$role_id, $id]);
        return $stmt->fetch();
    }
}
