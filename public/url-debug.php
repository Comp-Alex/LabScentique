<?php
// Check what URL would be constructed
echo "=== URL CONSTRUCTION DEBUG ===\n\n";

// Test what the URL constructor would do
$base_url = 'api-data.php';
$origin = 'http://localhost:8000';

// The JavaScript does: new URL(baseUrl, window.location.origin)
// This is equivalent to: new URL('api-data.php', 'http://localhost:8000')

echo "Base URL: $base_url\n";
echo "Origin: $origin\n";
echo "Expected full URL: $origin/$base_url\n\n";

// Test API endpoint directly
echo "Testing actual API endpoint:\n";
$urls_to_test = [
    'http://localhost:8000/api-data.php?action=perfumes',
    'http://localhost:8000/api-data.php?action=about',
];

foreach ($urls_to_test as $url) {
    $response = @file_get_contents($url);
    if ($response) {
        $data = json_decode($response, true);
        echo "✓ $url\n";
        echo "  Response: " . (isset($data['success']) ? 'Success' : (isset($data['data']) ? 'Has data' : 'Unknown')) . "\n";
    } else {
        echo "✗ $url - Failed to fetch\n";
    }
}

// Check if the API files exist
echo "\n=== FILE EXISTENCE ===\n";
$files = [
    'public/api-data.php',
    'public/api-dashboard.php',
];

foreach ($files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file NOT FOUND\n";
    }
}
?>