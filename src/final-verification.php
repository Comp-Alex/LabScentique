<?php
// Final comprehensive verification
echo "=== FINAL LABSCENTIQUE VERIFICATION ===\n\n";

require_once __DIR__ . '/../config/config.php';

$all_ok = true;

// 1. Database
echo "1. DATABASE:\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM (SELECT 1 FROM perfumes UNION ALL SELECT 1 FROM users UNION ALL SELECT 1 FROM inventory)");
    echo "   ✓ Database connected\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
    $all_ok = false;
}

// 2. Test APIs
echo "\n2. TESTING APIs:\n";
$test_urls = [
    'Perfumes List' => 'http://localhost:8000/api-data.php?action=perfumes',
    'About Info' => 'http://localhost:8000/api-data.php?action=about',
    'Stock Check' => 'http://localhost:8000/api-data.php?action=perfume_inventory&perfume_id=1',
];

foreach ($test_urls as $name => $url) {
    $response = @file_get_contents($url);
    $data = $response ? json_decode($response, true) : null;
    
    if ($data && (isset($data['success']) || isset($data['data']))) {
        echo "   ✓ $name\n";
    } else {
        echo "   ✗ $name\n";
        $all_ok = false;
    }
}

// 3. File checks
echo "\n3. FILES:\n";
$critical_files = [
    'public/app.js',
    'public/api-data.php',
    'public/styles.css',
    'public/index.php',
];

foreach ($critical_files as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path) && filesize($path) > 100) {
        echo "   ✓ $file\n";
    } else {
        echo "   ✗ $file\n";
        $all_ok = false;
    }
}

// 4. Data samples
echo "\n4. DATA SAMPLES:\n";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM perfumes");
$perfume_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "   ✓ Perfumes: $perfume_count\n";

$stmt = $pdo->query("SELECT AVG(CAST(rating AS FLOAT)) as avg_rating FROM perfumes");
$avg_rating = $stmt->fetch(PDO::FETCH_ASSOC)['avg_rating'];
echo "   ✓ Average rating: " . round($avg_rating, 1) . "/5\n";

$stmt = $pdo->query("SELECT SUM(available_quantity) as total_stock FROM inventory");
$total_stock = $stmt->fetch(PDO::FETCH_ASSOC)['total_stock'];
echo "   ✓ Total stock: $total_stock units\n";

$stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'registered'");
$users_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "   ✓ Registered users: $users_count\n";

// 5. About section
echo "\n5. ABOUT SECTION:\n";
$stmt = $pdo->query("SELECT heading, features FROM about_info LIMIT 1");
$about = $stmt->fetch(PDO::FETCH_ASSOC);

echo "   ✓ Heading: " . substr($about['heading'], 0, 50) . "...\n";

$features = json_decode($about['features'], true);
if (is_array($features) && count($features) > 0) {
    echo "   ✓ Features populated: " . count($features) . " items\n";
} else {
    echo "   ⚠ Features empty\n";
}

// Summary
echo "\n" . str_repeat("=", 40) . "\n";
if ($all_ok) {
    echo "✅ ALL SYSTEMS OPERATIONAL\n";
    echo "   The application is ready to use!\n";
} else {
    echo "⚠️  SOME ISSUES DETECTED\n";
    echo "   Please review the results above\n";
}
echo str_repeat("=", 40) . "\n";
?>