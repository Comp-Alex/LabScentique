<?php
// Comprehensive debug script
echo "=== LABSCENTIQUE DEBUG INFO ===\n\n";

// 1. Check database connection
echo "1. DATABASE CONNECTION:\n";
try {
    require_once __DIR__ . '/../config/config.php';
    $result = $pdo->query("SELECT COUNT(*) as count FROM perfumes");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Database connected\n";
    echo "   ✓ Perfumes count: " . $row['count'] . "\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

// 2. Check session
echo "\n2. SESSION STATUS:\n";
session_start();
echo "   Session ID: " . session_id() . "\n";
echo "   User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "   Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";

// 3. Check files
echo "\n3. FILE CHECKS:\n";
$files = ['app.js', 'script.js', 'validate.js', 'styles.css', 'api-data.php', 'api-dashboard.php'];
foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        $size = filesize($path);
        echo "   ✓ $file (" . $size . " bytes)\n";
    } else {
        echo "   ✗ $file (NOT FOUND)\n";
    }
}

// 4. Test API endpoints
echo "\n4. API ENDPOINTS:\n";
$endpoints = [
    'perfumes' => 'api-data.php?action=perfumes',
    'about' => 'api-data.php?action=about',
    'perfume_inventory' => 'api-data.php?action=perfume_inventory&perfume_id=1'
];

foreach ($endpoints as $name => $endpoint) {
    $url = 'http://localhost:8000/' . $endpoint;
    $response = @file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "   ✓ $name: Valid JSON\n";
        } else {
            echo "   ✗ $name: Invalid JSON\n";
        }
    } else {
        echo "   ✗ $name: Connection failed\n";
    }
}

// 5. Check for common issues
echo "\n5. COMMON ISSUES:\n";

// Check if perfumes have all required fields
try {
    $stmt = $pdo->query("SELECT id, name, description, rating FROM perfumes LIMIT 3");
    $perfumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "   ✓ Sample perfumes retrieved:\n";
    foreach ($perfumes as $p) {
        $missing = [];
        if (empty($p['id'])) $missing[] = 'id';
        if (empty($p['name'])) $missing[] = 'name';
        if (empty($p['description'])) $missing[] = 'description';
        
        if ($missing) {
            echo "     ✗ Perfume {$p['id']}: Missing " . implode(', ', $missing) . "\n";
        } else {
            echo "     ✓ Perfume: {$p['name']}\n";
        }
    }
} catch (Exception $e) {
    echo "   ✗ Error checking perfumes: " . $e->getMessage() . "\n";
}

echo "\n=== END DEBUG ===\n";
?>