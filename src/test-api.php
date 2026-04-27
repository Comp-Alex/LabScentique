<?php
// Test API endpoints
echo "Testing API endpoints...\n\n";

// Test perfumes
$url = 'http://localhost:8000/api-data.php?action=perfumes';
echo "Testing URL: $url\n";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);
$response = file_get_contents($url, false, $context);
$perfumes = json_decode($response, true);
echo "Response length: " . strlen($response) . "\n";
echo "Found " . count($perfumes['data'] ?? []) . " perfumes\n\n";

// Test perfume inventory
$url = 'http://localhost:8000/api-data.php?action=perfume_inventory&perfume_id=1';
echo "Testing URL: $url\n";
$response = file_get_contents($url, false, $context);
echo "Response: $response\n\n";
?>