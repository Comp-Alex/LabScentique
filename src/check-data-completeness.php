<?php
require_once __DIR__ . '/../config/config.php';

echo "=== CHECKING PERFUME DATA COMPLETENESS ===\n\n";

$stmt = $pdo->query("SELECT id, name, description, image_url, top_notes, heart_notes, base_notes, accords, rating FROM perfumes ORDER BY id");
$perfumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$issues_count = 0;

foreach ($perfumes as $p) {
    $missing = [];
    if (empty($p['name'])) $missing[] = 'name';
    if (empty($p['description'])) $missing[] = 'description';
    if (empty($p['top_notes'])) $missing[] = 'top_notes';
    if (empty($p['heart_notes'])) $missing[] = 'heart_notes';
    if (empty($p['base_notes'])) $missing[] = 'base_notes';
    if (empty($p['accords'])) $missing[] = 'accords';
    if (empty($p['rating'])) $missing[] = 'rating';
    if (empty($p['image_url'])) $missing[] = 'image_url';
    
    if ($missing) {
        echo "Perfume ID {$p['id']} ({$p['name']}): Missing " . implode(', ', $missing) . "\n";
        $issues_count++;
    }
}

if ($issues_count === 0) {
    echo "✓ All perfumes have complete data!\n";
}

// Check inventory
echo "\n=== CHECKING INVENTORY DATA ===\n\n";
$stmt = $pdo->query("SELECT i.id, p.name, i.available_quantity, i.damaged_quantity FROM inventory i JOIN perfumes p ON i.perfume_id = p.id");
$inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($inventory as $inv) {
    $missing_issues = [];
    if ($inv['available_quantity'] === null) $missing_issues[] = 'available_quantity';
    if ($inv['damaged_quantity'] === null) $missing_issues[] = 'damaged_quantity';
    
    if ($missing_issues) {
        echo "Inventory for {$inv['name']}: Missing " . implode(', ', $missing_issues) . "\n";
    }
}

echo "✓ Inventory checked: " . count($inventory) . " items\n";

// Check users
echo "\n=== CHECKING USER DATA ===\n\n";
$stmt = $pdo->query("SELECT id, username, email, role, password_hash FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $missing = [];
    if (empty($user['username'])) $missing[] = 'username';
    if (empty($user['email'])) $missing[] = 'email';
    if (empty($user['role'])) $missing[] = 'role';
    if (empty($user['password_hash'])) $missing[] = 'password_hash';
    
    if ($missing) {
        echo "User ID {$user['id']}: Missing " . implode(', ', $missing) . "\n";
    }
}

echo "✓ Users checked: " . count($users) . " users\n";

echo "\n=== ALL DATA CHECKS COMPLETE ===\n";
?>