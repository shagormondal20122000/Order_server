<?php
namespace Core;

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/Database.php';

$db = Database::getInstance();

$methods = [
    ['name' => 'bKash Personal', 'details' => 'Send money to 01700000000', 'status' => 'active'],
    ['name' => 'Nagad Personal', 'details' => 'Send money to 01800000000', 'status' => 'active'],
    ['name' => 'Rocket Personal', 'details' => 'Send money to 01900000000', 'status' => 'active'],
    ['name' => 'Bank Transfer', 'details' => 'Account Name: Digital Platform, Bank: ABC Bank, AC: 123456789', 'status' => 'active']
];

$stmt = $db->prepare("INSERT OR IGNORE INTO payment_methods (name, details, status) VALUES (?, ?, ?)");
foreach ($methods as $m) {
    $stmt->execute([$m['name'], $m['details'], $m['status']]);
}

echo "Initial payment methods seeded successfully.\n";
