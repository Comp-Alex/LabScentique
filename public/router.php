<?php
// Simple API router for PHP development server
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Debug: log the request
error_log("Router: Request URI: $requestUri, Path: $path");

// Route API requests to the api directory
if (strpos($path, '/api/') === 0) {
    $apiFile = __DIR__ . '/../api' . substr($path, 4); // Remove /api prefix

    error_log("Router: API file path: $apiFile");

    if (file_exists($apiFile)) {
        error_log("Router: Including API file: $apiFile");
        require $apiFile;
        exit;
    } else {
        error_log("Router: API file not found: $apiFile");
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
        exit;
    }
}

// For all other requests, serve the main index.php
error_log("Router: Serving index.php");
require __DIR__ . '/index.php';
?>