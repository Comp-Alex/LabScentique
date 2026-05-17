<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

try {
    $stmt = $pdo->query('SELECT COUNT(*) AS c FROM perfumes');
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "DB connected. Perfume rows: " . ($row['c'] ?? '0') . "\n";
} catch (PDOException $e) {
    echo "DB error: " . $e->getMessage() . "\n";
}

?>
