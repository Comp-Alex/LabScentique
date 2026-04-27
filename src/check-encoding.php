<?php
// Check for character encoding and potential display issues
echo "=== CHARACTER & ENCODING CHECK ===\n\n";

require_once __DIR__ . '/../config/config.php';

// Check for special characters in data
$stmt = $pdo->query("SELECT heading, intro, details FROM about_info LIMIT 1");
$data = $stmt->fetch(PDO::FETCH_ASSOC);

echo "1. SPECIAL CHARACTERS IN ABOUT DATA:\n";
foreach ($data as $field => $value) {
    echo "\n$field:\n";
    echo "  Length: " . strlen($value) . " chars\n";
    echo "  Content: " . substr($value, 0, 100) . "...\n";
    
    // Check for problematic characters
    $hasSpecial = preg_match('/[^\x{0020}-\x{007E}]/u', $value);
    echo "  Has special chars: " . ($hasSpecial ? "YES" : "NO") . "\n";
    
    // Check for em-dashes or other Unicode
    if (strpos($value, '—') !== false) echo "  Contains em-dash (—): YES\n";
    if (strpos($value, '–') !== false) echo "  Contains en-dash (–): YES\n";
}

echo "\n2. PERFUME DESCRIPTIONS:\n";
$stmt = $pdo->query("SELECT name, description FROM perfumes LIMIT 3");
$perfumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($perfumes as $p) {
    echo "  {$p['name']}: " . substr($p['description'], 0, 60) . "...\n";
    $hasSpecial = preg_match('/[^\x{0020}-\x{007E}]/u', $p['description']);
    if ($hasSpecial) echo "    - Contains special characters: YES\n";
}

echo "\n3. DATABASE CHARSET:\n";
$stmt = $pdo->query("PRAGMA encoding");
$result = $stmt->fetch(PDO::FETCH_ASSOC);
echo "  Encoding: " . print_r($result, true);

?>