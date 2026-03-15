<?php
namespace Core;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

class Migration {
    public static function run() {
        $pdo = Database::getInstance();

        try {
            // Add order_format_template to products table
            $pdo->exec("ALTER TABLE products ADD COLUMN order_format_template TEXT");
            echo "Column 'order_format_template' added to 'products' table.\n";
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                echo "Column 'order_format_template' already exists in 'products' table.\n";
            } else {
                echo "Error adding column to products table: " . $e->getMessage() . "\n";
            }
        }

        try {
            // Add customer_data to order_items table
            $pdo->exec("ALTER TABLE order_items ADD COLUMN customer_data TEXT");
            echo "Column 'customer_data' added to 'order_items' table.\n";
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'duplicate column name') !== false) {
                echo "Column 'customer_data' already exists in 'order_items' table.\n";
            } else {
                echo "Error adding column to order_items table: " . $e->getMessage() . "\n";
            }
        }
    }
}

// Run migration
Migration::run();
