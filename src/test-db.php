<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';

echo "=== LabScentique DB Test ===\n";

try {
    $stmt = $pdo->query('SELECT COUNT(*) FROM users');
    echo 'Users: ' . $stmt->fetchColumn() . "\n";
    
    $stmt = $pdo->query('SELECT name FROM perfumes LIMIT 3');
    echo 'Perfumes sample: ';
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
        echo $name . ', ';
    }
    echo "\n";
    
    $stmt = $pdo->query('SELECT COUNT(*) FROM contacts');
    echo 'Contacts: ' . $stmt->fetchColumn() . "\n";
    
    echo "✅ DB connected and data present!\n";
} catch (PDOException $e) {
    echo '❌ DB Error: ' . $e->getMessage() . "\n";
}
?>
