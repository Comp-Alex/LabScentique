<?php
require_once __DIR__ . '/../config/config.php';
$stmt = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :u');
$stmt->execute([':u' => 'owner']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "NO_USER\n";
    exit(1);
}
$hash = $user['password_hash'];
$test = password_verify('owner123', $hash) ? 'VERIFIES' : 'NO';
echo "id={$user['id']} username={$user['username']}\n";
echo "hash={$hash}\n";
echo "verify={$test}\n";
