<?php
/**
 * Database Initialization Script
 * This script initializes the database and creates necessary tables
 * Run once: php init-db.php
 */

declare(strict_types=1);

try {
    require_once __DIR__ . '/config/config.php';
    
    // Read the init.sql file
    $sqlFile = __DIR__ . '/database/init.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    if (!$sql) {
        throw new Exception("Failed to read SQL file");
    }
    
    // Split SQL into individual statements
    $statements = array_filter(
        array_map('trim', explode(';', $sql)),
        fn($s) => !empty($s) && !str_starts_with($s, '--') && !str_starts_with($s, '/*')
    );
    
    // Execute each statement
    $count = 0;
    foreach ($statements as $statement) {
        if (trim($statement)) {
            $pdo->exec($statement . ';');
            $count++;
        }
    }
    
    echo "✓ Database initialized successfully! ($count statements executed)\n";
    echo "✓ Tables created: users, perfumes, inventory, product_reviews, review_replies, etc.\n";
    
} catch (Exception $e) {
    echo "✗ Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}
