<?php
// Get the actual HTML being served
echo "=== CHECKING RENDERED HTML ===\n\n";

// Fetch the index page
$html = file_get_contents('http://localhost:8000');

if (!$html) {
    echo "ERROR: Could not fetch the page\n";
    exit;
}

echo "HTML Length: " . strlen($html) . " bytes\n";

// Check for common content
$checks = [
    'DOCTYPE' => '<!DOCTYPE html>',
    'Title' => '<title>LabScentique</title>',
    'Header' => '<header class="site-header">',
    'Feature Grid' => '<div class="container feature-grid">',
    'About Section' => '<section class="about" id="about">',
    'Footer' => '<footer class="site-footer">',
    'app.js loaded' => '<script src="app.js"></script>',
    'styles.css' => '<link rel="stylesheet" href="styles.css" />',
];

echo "\nContent Checks:\n";
foreach ($checks as $label => $content) {
    if (strpos($html, $content) !== false) {
        echo "  ✓ $label: FOUND\n";
    } else {
        echo "  ✗ $label: NOT FOUND\n";
    }
}

// Check for errors
echo "\nError Checks:\n";
if (strpos($html, 'PHP Error') !== false || strpos($html, 'Fatal error') !== false) {
    echo "  ✗ PHP Errors detected in output\n";
    // Extract error message
    preg_match('/(Fatal error.*?)<|$/', $html, $matches);
    if (!empty($matches[1])) {
        echo "    " . substr($matches[1], 0, 100) . "\n";
    }
} else {
    echo "  ✓ No PHP errors detected\n";
}

// Check for JavaScript/CSS loading
echo "\nAsset Checks:\n";
$scripts = [];
preg_match_all('/<script[^>]*src="([^"]+)"[^>]*><\/script>/i', $html, $matches);
echo "  Scripts found: " . count($matches[1]) . "\n";
foreach ($matches[1] as $script) {
    echo "    - $script\n";
}

$styles = [];
preg_match_all('/<link[^>]*rel="stylesheet"[^>]*href="([^"]+)"[^>]*>/i', $html, $matches);
echo "  Stylesheets found: " . count($matches[1]) . "\n";
foreach ($matches[1] as $style) {
    echo "    - $style\n";
}

echo "\n=== END HTML CHECK ===\n";
?>