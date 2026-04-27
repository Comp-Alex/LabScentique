<?php
/**
 * Database Reset Script
 * Drops all tables and reinitializes the database schema with seed data
 * 
 * Run from command line: php src/reset-database.php
 * Or in browser: http://localhost:8000/src/reset-database.php (not recommended for production)
 */

declare(strict_types=1);

$dbPath = __DIR__ . '/../labscentique.db';
$initSqlPath = __DIR__ . '/../database/init.sql';

// Read database path from config
require_once __DIR__ . '/../config/config.php';

echo "Resetting LabScentique Database...\n\n";

try {
    // Create/connect to PDO (will create database if needed)
    $pdo = new PDO('sqlite:' . $dbPath, '', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Drop existing tables (in reverse order of dependencies)
    $dropStatements = [
        'DROP TABLE IF EXISTS inventory_audit',
        'DROP TABLE IF EXISTS inventory_access',
        'DROP TABLE IF EXISTS purchase_list_items',
        'DROP TABLE IF EXISTS purchase_lists',
        'DROP TABLE IF EXISTS contacts',
        'DROP TABLE IF EXISTS inventory',
        'DROP TABLE IF EXISTS about_info',
        'DROP TABLE IF EXISTS perfumes',
        'DROP TABLE IF EXISTS users',
    ];

    foreach ($dropStatements as $statement) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            // Table might not exist, continue
        }
    }

    echo "✓ Dropped existing tables\n";

    // Read and execute init SQL
    $sql = file_get_contents($initSqlPath);
    if ($sql === false) {
        echo "✗ Could not read init.sql file\n";
        exit(1);
    }

    // Remove comment lines
    $lines = explode("\n", $sql);
    $cleanedLines = [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if (!empty($trimmed) && !str_starts_with($trimmed, '--')) {
            $cleanedLines[] = $line;
        }
    }
    $cleanedSql = implode("\n", $cleanedLines);

    // Split SQL by semicolons but be careful with quoted strings
    $statements = [];
    $currentStatement = '';
    $inQuote = false;
    $quoteChar = null;

    for ($i = 0; $i < strlen($cleanedSql); $i++) {
        $char = $cleanedSql[$i];

        // Toggle quote state
        if (($char === '"' || $char === "'") && ($i === 0 || $cleanedSql[$i - 1] !== '\\')) {
            if (!$inQuote) {
                $inQuote = true;
                $quoteChar = $char;
            } elseif ($char === $quoteChar) {
                $inQuote = false;
                $quoteChar = null;
            }
        }

        // Check for statement end
        if ($char === ';' && !$inQuote) {
            $statement = trim($currentStatement);
            if (!empty($statement)) {
                $statements[] = $statement;
            }
            $currentStatement = '';
        } else {
            $currentStatement .= $char;
        }
    }

    // Add final statement if exists
    $statement = trim($currentStatement);
    if (!empty($statement)) {
        $statements[] = $statement;
    }

    foreach ($statements as $statement) {
        try {
            $pdo->exec($statement);
        } catch (PDOException $e) {
            echo "✗ SQL Error: " . $e->getMessage() . "\n";
            echo "Statement: " . substr($statement, 0, 100) . "...\n";
            exit(1);
        }
    }

    echo "✓ Database schema created\n";

    // Verify tables
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);

    echo "\n✓ Database tables:\n";
    foreach ($tables as $table) {
        echo "  - $table\n";
    }

    // Check data
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    $perfumeCount = $pdo->query("SELECT COUNT(*) FROM perfumes")->fetchColumn();
    $inventoryCount = $pdo->query("SELECT COUNT(*) FROM inventory")->fetchColumn();
    $accessCount = $pdo->query("SELECT COUNT(*) FROM inventory_access")->fetchColumn();

    echo "\n✓ Seed data:\n";
    echo "  - Users: $userCount\n";
    echo "  - Perfumes: $perfumeCount\n";
    echo "  - Inventory records: $inventoryCount\n";
    echo "  - Access grants: $accessCount\n";

    // Display test credentials
    echo "\n✓ Test Credentials:\n";
    echo "  Owner:     owner / owner123\n";
    echo "  Staff:     staff / staff123\n";
    echo "  Warehouse: staff_warehouse / staff123\n";
    echo "  Quality:   staff_quality / staff123\n";
    echo "  Guest:     guest_user / guest123\n";

    echo "\n✓ Database reset complete!\n";

} catch (PDOException $e) {
    echo "✗ Database Error: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
