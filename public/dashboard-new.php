<?php
declare(strict_types=1);
session_start();

// Only check auth - everything else is in dashboard.html and dashboard.js
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

try {
    require_once __DIR__ . '/../config/config.php';
    
    // Verify user still exists
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = :id');
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    
    // Verify user has staff or owner role
    if (!in_array($user['role'] ?? '', ['staff', 'owner'], true)) {
        echo '<p>You do not have permission to access the dashboard.</p>';
        exit;
    }
    
} catch (PDOException $e) {
    echo '<p>Database unavailable. Please try again later.</p>';
    exit;
}

// All the HTML and JavaScript logic is in dashboard.html and dashboard.js
// This PHP file only does authentication
readfile(__DIR__ . '/dashboard.html');
