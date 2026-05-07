<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

try {
    require_once __DIR__ . '/../config/config.php';
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

/**
 * Get user profile information
 */
function getUserProfile($pdo, $userId) {
    try {
        $stmt = $pdo->prepare('
            SELECT id, username, email, full_name, bio, profile_picture_url, role, created_at
            FROM users
            WHERE id = :id
        ');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'User not found']);
            return;
        }

        echo json_encode([
            'success' => true,
            'data' => $user
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch profile']);
    }
}

/**
 * Update user profile
 */
function updateUserProfile($pdo, $userId) {
    try {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            return;
        }

        $fullName = trim($data['full_name'] ?? '');
        $bio = trim($data['bio'] ?? '');
        $profilePictureUrl = trim($data['profile_picture_url'] ?? '');

        // Validate input
        if (strlen($fullName) > 255) {
            http_response_code(400);
            echo json_encode(['error' => 'Full name too long']);
            return;
        }

        if (strlen($bio) > 1000) {
            http_response_code(400);
            echo json_encode(['error' => 'Bio too long']);
            return;
        }

        // Update profile
        $stmt = $pdo->prepare('
            UPDATE users
            SET full_name = :full_name, bio = :bio, profile_picture_url = :profile_picture_url, updated_at = datetime(\'now\')
            WHERE id = :id
        ');
        $stmt->execute([
            ':full_name' => $fullName ?: null,
            ':bio' => $bio ?: null,
            ':profile_picture_url' => $profilePictureUrl ?: null,
            ':id' => $userId
        ]);

        // Fetch and return updated profile
        $stmt = $pdo->prepare('
            SELECT id, username, email, full_name, bio, profile_picture_url, role, created_at, updated_at
            FROM users
            WHERE id = :id
        ');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update profile']);
    }
}

/**
 * Update password
 */
function updatePassword($pdo, $userId) {
    try {
        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!is_array($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON payload']);
            return;
        }

        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        if (!$currentPassword || !$newPassword) {
            http_response_code(400);
            echo json_encode(['error' => 'Current and new password are required']);
            return;
        }

        if (strlen($newPassword) < 6) {
            http_response_code(400);
            echo json_encode(['error' => 'New password must be at least 6 characters']);
            return;
        }

        // Verify current password
        $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Current password is incorrect']);
            return;
        }

        // Update password
        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('
            UPDATE users
            SET password_hash = :password_hash, updated_at = datetime(\'now\')
            WHERE id = :id
        ');
        $stmt->execute([
            ':password_hash' => $newPasswordHash,
            ':id' => $userId
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Password updated successfully'
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update password']);
    }
}

try {
    switch ($action) {
        case 'get':
            getUserProfile($pdo, $userId);
            break;
        case 'update':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            } else {
                updateUserProfile($pdo, $userId);
            }
            break;
        case 'update_password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
            } else {
                updatePassword($pdo, $userId);
            }
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log('User Profile API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error']);
}
?>
