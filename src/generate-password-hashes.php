<?php
declare(strict_types=1);

/**
 * Generate bcrypt password hashes for testing accounts
 * Run this once to see all password hashes needed for init.sql
 */

$testAccounts = [
    'owner' => 'owner123',
    'staff' => 'staff123',
    'manager' => 'owner123',
    'staff_warehouse' => 'staff123',
    'staff_quality' => 'staff123',
    'guest_user' => 'guest123',
];

echo "========================================\n";
echo "LabScentique Test Account Passwords\n";
echo "========================================\n\n";

foreach ($testAccounts as $username => $plainPassword) {
    $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
    echo "Username: $username\n";
    echo "Password: $plainPassword\n";
    echo "Hash:     $hash\n";
    echo "\n";
}

echo "========================================\n";
echo "SQL INSERT STATEMENT:\n";
echo "========================================\n\n";

echo "INSERT OR IGNORE INTO users (username, email, password_hash, role) VALUES\n";

$users = [
    ['owner', 'owner@labscentique.local', 'owner', 'owner123'],
    ['staff', 'staff@labscentique.local', 'staff', 'staff123'],
    ['manager', 'manager@labscentique.local', 'owner', 'owner123'],
    ['staff_warehouse', 'warehouse@labscentique.local', 'staff', 'staff123'],
    ['staff_quality', 'quality@labscentique.local', 'staff', 'staff123'],
    ['guest_user', 'guest@labscentique.local', 'registered', 'guest123'],
];

foreach ($users as $index => $user) {
    [$username, $email, $role, $password] = $user;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $comma = $index < count($users) - 1 ? ',' : ';';
    echo "('$username', '$email', '$hash', '$role')$comma\n";
}
