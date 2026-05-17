<?php
declare(strict_types=1);
$sessionSavePath = $_ENV['SESSION_SAVE_PATH'] ?? $_SERVER['SESSION_SAVE_PATH'] ?? null;
if (is_string($sessionSavePath) && $sessionSavePath !== '') {
    if (!is_dir($sessionSavePath)) {
        @mkdir($sessionSavePath, 0755, true);
    }
    session_save_path($sessionSavePath);
}
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$loginError = '';
$registerError = '';
$registerSuccess = '';
$dbError = false;
$dbErrorMessage = '';
$pdo = null;

try {
    require_once __DIR__ . '/../config/config.php';
} catch (PDOException $e) {
    $dbError = true;
    $dbErrorMessage = $e->getMessage();
}

$introLogoPath = 'assets/intro-logo.png';
if (!file_exists(__DIR__ . '/assets/intro-logo.png')) {
    $introLogoPath = 'assets/logo.svg';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $username = trim($_POST['login_username'] ?? '');
        $password = $_POST['login_password'] ?? '';

        if ($username && $password && isset($pdo)) {
            try {
                $stmt = $pdo->prepare('SELECT id, password_hash, role FROM users WHERE LOWER(username) = LOWER(:username) OR LOWER(email) = LOWER(:email)');
                $stmt->execute([':username' => $username, ':email' => $username]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = $user['role'] ?? 'registered';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit;
                }

                $loginError = 'Invalid username or password.';
            } catch (PDOException $e) {
                $loginError = 'Database error. Please try again.';
            }
        } else {
            $loginError = 'Please fill in all fields.';
        }
    } elseif ($action === 'register') {
        $username = trim($_POST['reg_username'] ?? '');
        $email = trim($_POST['reg_email'] ?? '');
        $password = $_POST['reg_password'] ?? '';

        if ($username && $email && $password) {
            if (strlen($password) < 6) {
                $registerError = 'Password must be at least 6 characters.';
            } elseif (isset($pdo)) {
                try {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)');
                    $stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password_hash' => $passwordHash,
                    ]);

                    $registerSuccess = 'Registration successful! You can now login.';
                } catch (PDOException $e) {
                    if ($e->getCode() == 23000) {
                        $registerError = 'Username or email already exists.';
                    } else {
                        $registerError = 'Registration failed. Please try again.';
                    }
                }
            }
        } else {
            $registerError = 'Please fill in all fields.';
        }
    }
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

define('LABSCENTIQUE_APP', true);
include __DIR__ . '/index_view.php';
