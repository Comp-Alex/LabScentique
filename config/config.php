<?php
declare(strict_types=1);

// Load environment variables from .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (trim($line) === '' || str_starts_with(trim($line), '#')) {
            continue;
        }

        [$name, $value] = array_map('trim', explode('=', $line, 2) + ['', '']);
        if ($name === '' || $value === '') {
            continue;
        }

        putenv("$name=$value");
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
    }
}

function applySessionSavePath(): void
{
    $sessionSavePath = $_ENV['SESSION_SAVE_PATH'] ?? $_SERVER['SESSION_SAVE_PATH'] ?? null;
    if (is_string($sessionSavePath) && $sessionSavePath !== '' && session_status() === PHP_SESSION_NONE) {
        if (!is_dir($sessionSavePath)) {
            @mkdir($sessionSavePath, 0755, true);
        }
        session_save_path($sessionSavePath);
    }
}

applySessionSavePath();

// Determine database type (defaults to SQLite for backward compatibility)
$dbType = $_ENV['DB_TYPE'] ?? $_SERVER['DB_TYPE'] ?? 'sqlite';

// Configure database connection
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$dbUser = '';
$dbPass = '';

if ($dbType === 'postgres' || $dbType === 'postgresql') {
    // PostgreSQL Configuration (production)
    $databaseUrl = $_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? null;
    
    if (!$databaseUrl) {
        throw new Exception('DATABASE_URL environment variable is required for PostgreSQL');
    }
    
    // Parse PostgreSQL connection string
    // Format: postgresql://user:password@host:port/database
    $parsedUrl = parse_url($databaseUrl);
    
    $dbUser = $parsedUrl['user'] ?? '';
    $dbPass = $parsedUrl['pass'] ?? '';
    $dbHost = $parsedUrl['host'] ?? 'localhost';
    $dbPort = $parsedUrl['port'] ?? 5432;
    $dbName = ltrim($parsedUrl['path'] ?? '', '/');
    
    // Support both postgresql:// and postgres:// schemes
    $scheme = ($parsedUrl['scheme'] ?? 'postgresql') === 'postgres' ? 'pgsql' : 'pgsql';
    
    $dsn = "pgsql:host=$dbHost;port=$dbPort;dbname=$dbName;sslmode=require";
    
    // For PostgreSQL, use user and password from URL
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
    
} else {
    // SQLite Configuration (local development)
    $dbPath = $_ENV['DB_PATH'] ?? $_SERVER['DB_PATH'] ?? __DIR__ . '/../labscentique.db';
    
    // Create directory if it doesn't exist
    $dbDir = dirname($dbPath);
    if (!is_dir($dbDir) && $dbDir !== '.') {
        @mkdir($dbDir, 0755, true);
    }
    
    $dsn = 'sqlite:' . $dbPath;
    $pdo = new PDO($dsn, '', '', $options);
    
    // Enable foreign keys for SQLite
    $pdo->exec('PRAGMA foreign_keys = ON;');
}

// Verify connection
try {
    $pdo->query('SELECT 1');
} catch (PDOException $e) {
    error_log('Database Connection Error: ' . $e->getMessage());
    throw new Exception('Failed to connect to database: ' . $e->getMessage());
}

