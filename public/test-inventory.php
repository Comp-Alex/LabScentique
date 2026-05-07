<?php
require_once '../config/config.php';

try {
    $stmt = $pdo->query('SELECT i.perfume_id, p.name, i.available_quantity FROM inventory i JOIN perfumes p ON i.perfume_id = p.id ORDER BY p.rating DESC');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        echo $row['perfume_id'] . ': ' . $row['name'] . ' - ' . $row['available_quantity'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
}
?>