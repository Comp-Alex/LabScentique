<?php
require_once '../config/config.php';

try {
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM perfumes');
    $result = $stmt->fetch();
    echo 'Perfumes count: ' . $result['count'] . PHP_EOL;

    $stmt = $pdo->query('SELECT COUNT(*) as count FROM inventory');
    $result = $stmt->fetch();
    echo 'Inventory count: ' . $result['count'] . PHP_EOL;

    // Test inventory query
    $stmt = $pdo->prepare('SELECT available_quantity FROM inventory WHERE perfume_id = ?');
    $stmt->execute([1]);
    $inventory = $stmt->fetch();
    echo 'Inventory for perfume 1: ' . ($inventory ? $inventory['available_quantity'] : 'not found') . PHP_EOL;

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>