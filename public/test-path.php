<?php
$apiFile = __DIR__ . '/../api/data.php';
echo 'API file path: ' . $apiFile . PHP_EOL;
echo 'File exists: ' . (file_exists($apiFile) ? 'YES' : 'NO') . PHP_EOL;
echo 'Current dir: ' . __DIR__ . PHP_EOL;
echo 'Request URI: ' . ($_SERVER['REQUEST_URI'] ?? 'N/A') . PHP_EOL;
?>