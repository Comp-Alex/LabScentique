<?php
// Comprehensive error and rendering check
echo "=== COMPREHENSIVE LABSCENTIQUE ERROR CHECK ===\n\n";

// 1. Check PHP error logs
echo "1. PHP INFORMATION:\n";
echo "   Version: " . phpversion() . "\n";
echo "   Error reporting: " . (ini_get('error_reporting') ? "ON" : "OFF") . "\n";
echo "   Display errors: " . (ini_get('display_errors') ? "ON" : "OFF") . "\n";

// 2. Test all API endpoints
echo "\n2. API ENDPOINT TESTS:\n";
require_once __DIR__ . '/../config/config.php';

$endpoints = [
    'perfumes' => ['action' => 'perfumes'],
    'perfume_inventory' => ['action' => 'perfume_inventory', 'perfume_id' => 1],
    'about' => ['action' => 'about'],
    'contact' => ['action' => 'contact'],
];

foreach ($endpoints as $name => $params) {
    $query = http_build_query($params);
    $url = "http://localhost:8000/api-data.php?$query";
    
    echo "\n   Testing: $name\n";
    echo "   URL: " . substr($url, -60) . "\n";
    
    $response = @file_get_contents($url);
    if ($response !== false) {
        $data = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo "   ✓ Valid JSON\n";
            if (isset($data['error'])) {
                echo "   ✗ API Error: " . $data['error'] . "\n";
            } elseif (isset($data['success'])) {
                echo "   ✓ API Success\n";
            }
        } else {
            echo "   ✗ Invalid JSON: " . json_last_error_msg() . "\n";
            echo "   Response (first 100 chars): " . substr($response, 0, 100) . "\n";
        }
    } else {
        echo "   ✗ Connection failed\n";
    }
}

// 3. Check database tables
echo "\n3. DATABASE TABLES:\n";
$tables = ['perfumes', 'users', 'inventory', 'about_info', 'contacts'];
foreach ($tables as $table) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   $table: " . $result['count'] . " rows\n";
}

// 4. Check HTML structure issues
echo "\n4. FILE INTEGRITY:\n";
$files_to_check = [
    'public/index.php',
    'public/app.js',
    'public/styles.css',
];
foreach ($files_to_check as $file) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $lines = count(explode("\n", $content));
        echo "   $file: OK ($lines lines, " . strlen($content) . " bytes)\n";
        
        // Check for unclosed tags in PHP files
        if (strpos($file, '.php') !== false) {
            $open_tags = substr_count($content, '<?php') + substr_count($content, '<?');
            $close_tags = substr_count($content, '?>');
            if ($open_tags !== $close_tags) {
                echo "     ✗ WARNING: Possible unclosed PHP tags (open: $open_tags, close: $close_tags)\n";
            }
        }
    } else {
        echo "   $file: NOT FOUND\n";
    }
}

echo "\n5. DATABASE CONNECTIVITY:\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM perfumes WHERE rating IS NOT NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ✓ Database operational (Perfumes with ratings: " . $result['count'] . ")\n";
} catch (Exception $e) {
    echo "   ✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n=== END CHECK ===\n";
?>