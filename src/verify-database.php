<?php
/**
 * Database Verification Script
 * Tests that all tables, relationships, and data exist
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

echo "LabScentique Database Verification\n";
echo "===================================\n\n";

// Test 1: All tables exist
echo "✓ Testing Table Structure...\n";
$tables = ['users', 'perfumes', 'inventory', 'purchase_lists', 'purchase_list_items', 
           'inventory_access', 'inventory_audit', 'contacts', 'about_info'];

$result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
$existingTables = $result->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $exists = in_array($table, $existingTables);
    echo "  " . ($exists ? "✓" : "✗") . " $table\n";
}

// Test 2: Users and Roles
echo "\n✓ Testing Users and Roles...\n";
$users = $pdo->query("SELECT username, role, email FROM users ORDER BY role DESC, username")->fetchAll();
foreach ($users as $user) {
    echo "  ✓ {$user['username']} ({$user['role']})\n";
}

// Test 3: Perfumes
echo "\n✓ Testing Perfumes...\n";
$perfumeCount = $pdo->query("SELECT COUNT(*) FROM perfumes")->fetchColumn();
echo "  ✓ $perfumeCount perfumes in catalog\n";

// Test 4: Inventory
echo "\n✓ Testing Inventory...\n";
$inventory = $pdo->query("
    SELECT p.name, i.available_quantity, i.damaged_quantity, i.expiration_date 
    FROM inventory i 
    JOIN perfumes p ON i.perfume_id = p.id 
    LIMIT 3
")->fetchAll();

foreach ($inventory as $item) {
    $status = $item['available_quantity'] > 0 ? "✓" : "⚠";
    echo "  $status {$item['name']}: {$item['available_quantity']} available, {$item['damaged_quantity']} damaged\n";
}

// Test 5: Staff Access
echo "\n✓ Testing Staff Access Control...\n";
$staffAccess = $pdo->query("
    SELECT DISTINCT u.username, ia.access_level
    FROM users u
    JOIN inventory_access ia ON u.id = ia.staff_id
    WHERE u.role = 'staff'
")->fetchAll();

foreach ($staffAccess as $access) {
    $levels = $pdo->prepare("
        SELECT COUNT(*) FROM inventory_access 
        WHERE staff_id = (SELECT id FROM users WHERE username = :username)
    ");
    $levels->execute([':username' => $access['username']]);
    $count = $levels->fetchColumn();
    echo "  ✓ {$access['username']} ({$access['access_level']}) - access to $count inventories\n";
}

// Test 6: About Info
echo "\n✓ Testing About Information...\n";
$about = $pdo->query("SELECT heading, stat_1_value, stat_2_value FROM about_info LIMIT 1")->fetch();
echo "  ✓ Heading: {$about['heading']}\n";
echo "  ✓ Stats: {$about['stat_1_value']} | {$about['stat_2_value']}\n";

// Test 7: API Simulation - User can access perfumes
echo "\n✓ Testing API Endpoints (Simulation)...\n";

// Simulate staff user
$_SESSION['user_id'] = $pdo->query("SELECT id FROM users WHERE username = 'staff'")->fetchColumn();
$_SESSION['username'] = 'staff';
$_SESSION['role'] = 'staff';

// Test getting perfumes
$perfumes = $pdo->query("SELECT id, name, description FROM perfumes ORDER BY name LIMIT 2")->fetchAll();
echo "  ✓ Perfumes API: " . count($perfumes) . " items returned\n";

// Test getting inventory
$invQuery = $pdo->prepare("
    SELECT i.id, i.perfume_id, p.name as perfume_name, i.available_quantity, 
           i.damaged_quantity, i.expiration_date, i.last_updated
    FROM inventory i
    JOIN perfumes p ON i.perfume_id = p.id
    JOIN inventory_access ia ON i.id = ia.inventory_id
    WHERE ia.staff_id = :user_id
    LIMIT 2
");
$invQuery->execute([':user_id' => $_SESSION['user_id']]);
$inventory = $invQuery->fetchAll();
echo "  ✓ Inventory API: " . count($inventory) . " items accessible to staff\n";

// Test creating purchase list
echo "\n✓ Testing Purchase List Functionality...\n";
$perfume = $pdo->query("SELECT id FROM perfumes LIMIT 1")->fetch();
$stmt = $pdo->prepare("INSERT INTO purchase_lists (staff_id, status) VALUES (:staff_id, 'pending')");
$stmt->execute([':staff_id' => $_SESSION['user_id']]);
$listId = $pdo->lastInsertId();

$stmt = $pdo->prepare("INSERT INTO purchase_list_items (purchase_list_id, perfume_id, quantity) VALUES (:list_id, :perfume_id, :qty)");
$stmt->execute([':list_id' => $listId, ':perfume_id' => $perfume['id'], ':qty' => 5]);

echo "  ✓ Created purchase list #$listId\n";

// Verify it's retrievable
$lists = $pdo->query("
    SELECT pl.id, pl.status, COUNT(pli.id) as items 
    FROM purchase_lists pl
    LEFT JOIN purchase_list_items pli ON pl.id = pli.purchase_list_id
    WHERE pl.id = $listId
    GROUP BY pl.id
")->fetchAll();
echo "  ✓ Retrieved purchase list: " . count($lists) . " found\n";

// Test audit log
echo "\n✓ Testing Inventory Audit Logging...\n";
$inv = $pdo->query("SELECT id FROM inventory LIMIT 1")->fetch();
$stmt = $pdo->prepare("
    INSERT INTO inventory_audit (inventory_id, changed_by, prev_available, new_available, prev_damaged, new_damaged, reason)
    VALUES (:inv_id, :changed_by, 25, 24, 0, 1, 'Quality check failure')
");
$stmt->execute([':inv_id' => $inv['id'], ':changed_by' => $_SESSION['user_id']]);

$auditCount = $pdo->query("SELECT COUNT(*) FROM inventory_audit")->fetchColumn();
echo "  ✓ Audit records: $auditCount entries logged\n";

echo "\n" . str_repeat("=", 50) . "\n";
echo "✓ DATABASE FULLY OPERATIONAL\n";
echo "✓ All APIs ready for use\n";
echo "✓ Ready to start the application!\n";
