<?php
require 'config/config.php';
echo 'PDO connected: ' . (isset($pdo) ? 'yes' : 'no') . PHP_EOL;
if (isset($pdo)) {
    try {
        $stmt = $pdo->query('SELECT COUNT(*) FROM inventory_access');
        echo 'Access records: ' . $stmt->fetchColumn() . PHP_EOL;
        
        $stmt = $pdo->query('SELECT COUNT(*) FROM inventory');
        echo 'Inventory records: ' . $stmt->fetchColumn() . PHP_EOL;
        
        $stmt = $pdo->query('SELECT COUNT(*) FROM perfumes');
        echo 'Perfume records: ' . $stmt->fetchColumn() . PHP_EOL;
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage() . PHP_EOL;
    }
}
?>