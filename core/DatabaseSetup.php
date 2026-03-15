<?php
namespace Core;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

class DatabaseSetup {
    public static function createTables() {
        $pdo = Database::getInstance();

        $queries = [
            // Roles table
            "CREATE TABLE IF NOT EXISTS roles (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                description TEXT
            )",

            // Permissions table
            "CREATE TABLE IF NOT EXISTS permissions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL UNIQUE,
                slug TEXT NOT NULL UNIQUE
            )",

            // Role-Permissions table
            "CREATE TABLE IF NOT EXISTS role_permissions (
                role_id INTEGER,
                permission_id INTEGER,
                PRIMARY KEY (role_id, permission_id),
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
            )",

            // Users table
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                role_id INTEGER,
                wallet_balance REAL DEFAULT 0,
                status TEXT DEFAULT 'active', -- active, suspended, pending
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (role_id) REFERENCES roles(id)
            )",

            // Categories table
            "CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                parent_id INTEGER DEFAULT NULL,
                status TEXT DEFAULT 'active',
                FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
            )",

            // Products table
            "CREATE TABLE IF NOT EXISTS products (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                short_desc TEXT,
                full_desc TEXT,
                category_id INTEGER,
                thumbnail TEXT,
                stock_type TEXT NOT NULL, -- file, text, key, account, manual, api
                delivery_type TEXT NOT NULL, -- instant, manual
                status TEXT DEFAULT 'active',
                order_format_template TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
            )",

            // Product Pricing table (dynamic pricing)
            "CREATE TABLE IF NOT EXISTS product_prices (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER,
                role_id INTEGER, -- NULL for general base price
                price REAL NOT NULL,
                min_qty INTEGER DEFAULT 1,
                is_active BOOLEAN DEFAULT 1,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
            )",

            // Product Stocks table
            "CREATE TABLE IF NOT EXISTS product_stocks (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                product_id INTEGER,
                content TEXT NOT NULL, -- The actual data (key, account details, text)
                status TEXT DEFAULT 'available', -- available, sold, reserved, invalid
                order_id INTEGER DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
            )",

            // Orders table
            "CREATE TABLE IF NOT EXISTS orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                total_amount REAL NOT NULL,
                status TEXT DEFAULT 'pending', -- pending, completed, cancelled, refunded
                payment_status TEXT DEFAULT 'pending', -- pending, paid, failed
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Order Items table
            "CREATE TABLE IF NOT EXISTS order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER,
                product_id INTEGER,
                price REAL NOT NULL,
                qty INTEGER NOT NULL,
                customer_data TEXT,
                delivery_content TEXT, -- Store delivered content for history
                FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                FOREIGN KEY (product_id) REFERENCES products(id)
            )",

            // Wallet Transactions table
            "CREATE TABLE IF NOT EXISTS wallet_transactions (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                amount REAL NOT NULL,
                type TEXT NOT NULL, -- credit, debit
                description TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Payment Methods table
            "CREATE TABLE IF NOT EXISTS payment_methods (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                details TEXT, -- JSON or text details for payment info
                status TEXT DEFAULT 'active'
            )",

            // Deposits table (Manual payment requests)
            "CREATE TABLE IF NOT EXISTS deposits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                amount REAL NOT NULL,
                payment_method_id INTEGER,
                proof TEXT, -- Path to image proof
                status TEXT DEFAULT 'pending', -- pending, approved, rejected
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id),
                FOREIGN KEY (payment_method_id) REFERENCES payment_methods(id)
            )",

            // Logs table
            "CREATE TABLE IF NOT EXISTS logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                action TEXT NOT NULL,
                details TEXT,
                ip_address TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Support Tickets table
            "CREATE TABLE IF NOT EXISTS tickets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                subject TEXT NOT NULL,
                status TEXT DEFAULT 'open', -- open, closed, pending_customer
                priority TEXT DEFAULT 'normal',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Ticket Messages table
            "CREATE TABLE IF NOT EXISTS ticket_messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                ticket_id INTEGER,
                user_id INTEGER,
                message TEXT NOT NULL,
                attachment TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES users(id)
            )",

            // Settings table (Key-Value)
            "CREATE TABLE IF NOT EXISTS settings (
                key TEXT PRIMARY KEY,
                value TEXT
            )"
        ];

        foreach ($queries as $sql) {
            $pdo->exec($sql);
        }

        // Insert default roles if not exist
        $roles = [
            ['id' => 1, 'name' => 'Admin', 'description' => 'Full control'],
            ['id' => 2, 'name' => 'Seller', 'description' => 'Can manage own products and stocks'],
            ['id' => 3, 'name' => 'Reseller', 'description' => 'Can buy products at wholesale rates'],
            ['id' => 4, 'name' => 'Moderator', 'description' => 'Can manage orders and tickets'],
            ['id' => 5, 'name' => 'Customer', 'description' => 'Can buy products']
        ];

        $stmt = $pdo->prepare("INSERT OR IGNORE INTO roles (id, name, description) VALUES (?, ?, ?)");
        foreach ($roles as $role) {
            $stmt->execute([$role['id'], $role['name'], $role['description']]);
        }

        return "Database tables created successfully.";
    }
}

// Run setup if called directly
if (php_sapi_name() === 'cli' || isset($_GET['setup'])) {
    echo DatabaseSetup::createTables();
}
