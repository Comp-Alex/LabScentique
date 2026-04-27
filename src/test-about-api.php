<?php
// Test the about API response
$url = 'http://localhost:8000/api-data.php?action=about';
$response = file_get_contents($url);
$data = json_decode($response, true);

echo "ABOUT API RESPONSE:\n";
echo "JSON Valid: " . (json_last_error() === JSON_ERROR_NONE ? "YES" : "NO") . "\n";
echo "\nData structure:\n";
print_r($data);

echo "\n\nAbout data fields:\n";
if (isset($data['data'])) {
    foreach ($data['data'] as $key => $value) {
        if (is_array($value)) {
            echo "  $key: Array with " . count($value) . " items\n";
            foreach ($value as $item) {
                echo "    - " . substr($item, 0, 50) . "...\n";
            }
        } else {
            echo "  $key: " . substr($value, 0, 100) . "...\n";
        }
    }
}
?>