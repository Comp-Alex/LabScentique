<?php
// Debug API endpoints and display issues
echo "=== DEBUGGING API RESPONSES ===\n\n";

// Test perfumes endpoint
$url = 'http://localhost:8000/api-data.php?action=perfumes';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "PERFUMES API:\n";
echo "Total perfumes: " . count($data['data'] ?? []) . "\n";
foreach ($data['data'] ?? [] as $perfume) {
    echo "  - " . $perfume['name'] . " (ID: " . $perfume['id'] . ")\n";
    echo "    Description: " . substr($perfume['description'] ?? '', 0, 50) . "...\n";
    echo "    Rating: " . $perfume['rating'] . "\n";
}

echo "\n=== CHECKING ABOUT INFO ===\n";
$url = 'http://localhost:8000/api-data.php?action=about';
$response = file_get_contents($url);
$data = json_decode($response, true);
if (isset($data['data'])) {
    echo "Heading: " . substr($data['data']['heading'] ?? '', 0, 100) . "...\n";
    echo "Stats: " . $data['data']['stat_1_value'] . " " . $data['data']['stat_1_label'] . "\n";
}
?>