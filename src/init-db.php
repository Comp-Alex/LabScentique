<?php
declare(strict_types=1);

echo "Initializing LabScentique SQLite database...\n";

$dbFile = __DIR__ . '/../labscentique.db';

if (file_exists($dbFile)) {
    echo "Removing existing DB file...\n";
    if (!unlink($dbFile)) {
        die("Error: Could not remove existing DB file. Close any process using it and try again.\n");
    }
}

require_once __DIR__ . '/../config/config.php';
$pdo->exec('PRAGMA foreign_keys = ON;');

// Read the SQL schema from the file
$sqlFile = __DIR__ . '/../database/init.sql';
if (!file_exists($sqlFile)) {
    die("Error: SQL schema file not found at $sqlFile\n");
}

$sqlContent = file_get_contents($sqlFile);
if ($sqlContent === false) {
    die("Error: Could not read SQL schema file\n");
}

// Execute the entire SQL content as one statement
try {
    $pdo->exec($sqlContent);
    echo "OK: Database schema and data loaded successfully\n";
} catch (PDOException $e) {
    echo "Error executing SQL: " . $e->getMessage() . "\n";
}

echo "✅ Database 'labscentique.db' initialized successfully!\n";
echo "You can now run: php -S localhost:8000\n";
?>
