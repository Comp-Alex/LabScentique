<?php
require_once __DIR__ . '/../config/config.php';
$username = 'Owner';
$stmt = $pdo->prepare('SELECT id, username, password_hash, role FROM users WHERE LOWER(username) = LOWER(:username) OR LOWER(email) = LOWER(:email)');
$stmt->execute([':username' => $username, ':email' => $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    echo "NO USER\n";
    exit(1);
}
$verify = password_verify('owner123', $user['password_hash']) ? 'VERIFIES' : 'NO';
echo "id={$user['id']} username={$user['username']} role={$user['role']} verify={$verify}\n";