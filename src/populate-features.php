<?php
require_once __DIR__ . '/../config/config.php';

echo "Updating About Info with features...\n";

// Get current about info
$stmt = $pdo->query("SELECT id, features FROM about_info LIMIT 1");
$about = $stmt->fetch(PDO::FETCH_ASSOC);

if ($about) {
    $features = [
        'Personalized Recommendations – Discover scents tailored to your style, occasion, and even the weather.',
        'Community & Learning – Share reviews, explore fragrance notes, and connect with fellow enthusiasts.',
        'Smart Inventory Management – For retailers, track stocks, log expirations, and streamline restocking with a powerful dashboard.',
    ];
    
    $featuresJson = json_encode($features);
    
    $updateStmt = $pdo->prepare("UPDATE about_info SET features = :features WHERE id = :id");
    $updateStmt->execute([
        ':features' => $featuresJson,
        ':id' => $about['id']
    ]);
    
    echo "✓ Features updated successfully\n";
    echo "Features stored: " . count($features) . " items\n";
} else {
    echo "✗ No about_info found\n";
}

// Verify the update
$stmt = $pdo->query("SELECT features FROM about_info LIMIT 1");
$updated = $stmt->fetch(PDO::FETCH_ASSOC);
$features = json_decode($updated['features'], true);
echo "✓ Verification: Features array has " . count($features) . " items\n";
?>