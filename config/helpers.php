<?php
/**
 * Common Helper Functions
 * Consolidates reusable functions used across the application
 * Replaces duplicated code in index.php, dashboard.php, and other files
 */

declare(strict_types=1);

/**
 * Escape HTML special characters for safe output
 * Prevents XSS attacks by converting special characters to HTML entities
 */
function escape(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Build navigation menu items
 * Returns array of navigation items with href, label, and visibility conditions
 */
function getNavigationItems(bool $isAuthenticated = false, string $userRole = ''): array {
    return [
        ['href' => '#home', 'label' => 'Home', 'visible' => true],
        ['href' => '#products', 'label' => 'Perfumes', 'visible' => true],
        ['href' => '#news', 'label' => 'News', 'visible' => true],
        ['href' => '#about', 'label' => 'About', 'visible' => true],
        ['href' => 'accreditation.php', 'label' => 'Accreditation', 'visible' => true],
        ['href' => '#contact', 'label' => 'Contact', 'visible' => true],
        ['href' => 'dashboard.php', 'label' => 'Dashboard', 'visible' => $isAuthenticated && in_array($userRole, ['staff', 'owner', 'admin'])],
    ];
}

/**
 * Render navigation HTML
 * Loops through navigation items and generates proper HTML
 */
function renderNavigation(array $items, string $containerClass = ''): string {
    $html = '<nav class="' . escape($containerClass) . '">';
    
    foreach ($items as $item) {
        if (!$item['visible']) continue;
        
        $href = escape($item['href']);
        $label = escape($item['label']);
        $html .= "<a href=\"{$href}\">{$label}</a>";
    }
    
    $html .= '</nav>';
    return $html;
}

/**
 * Check if user has required role
 * Useful for permission checks
 */
function hasRole(string $role, ?string $userRole = null): bool {
    if ($userRole === null && isset($_SESSION['user_role'])) {
        $userRole = $_SESSION['user_role'];
    }
    
    if ($role === 'authenticated') {
        return isset($_SESSION['user_id']);
    }
    
    return $userRole === $role;
}

/**
 * Check if user has any of the required roles
 */
function hasAnyRole(array $roles, ?string $userRole = null): bool {
    if ($userRole === null && isset($_SESSION['user_role'])) {
        $userRole = $_SESSION['user_role'];
    }
    
    return in_array($userRole, $roles);
}

/**
 * Validate email format
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate username (alphanumeric + underscore, 3-20 chars)
 */
function isValidUsername(string $username): bool {
    return preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username) === 1;
}

/**
 * Validate password strength (min 6 chars, at least one number and letter)
 */
function isValidPassword(string $password): bool {
    return strlen($password) >= 6 && 
           preg_match('/[a-zA-Z]/', $password) && 
           preg_match('/[0-9]/', $password);
}

/**
 * Hash password using bcrypt
 */
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verify password against hash
 */
function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

/**
 * Get user session data
 */
function getUserSession(): ?array {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'role' => $_SESSION['user_role'] ?? null,
        'full_name' => $_SESSION['full_name'] ?? null,
    ];
}

/**
 * Destroy user session
 */
function destroySession(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies') && !headers_sent()) {
        setcookie(session_name(), '', time() - 42000, '/');
    }
    session_destroy();
}

/**
 * Format timestamp for display
 */
function formatDate(string $timestamp, string $format = 'Y-m-d H:i'): string {
    try {
        $date = new DateTime($timestamp);
        return $date->format($format);
    } catch (Exception $e) {
        return $timestamp;
    }
}

/**
 * Convert database row errors to user-friendly messages
 */
function getErrorMessage(string $errorCode): string {
    $errors = [
        'UNIQUE' => 'This email or username is already taken',
        'NOT_NULL' => 'Please fill in all required fields',
        'FOREIGN_KEY' => 'Invalid reference to related data',
        'validation' => 'Please check your input and try again',
    ];
    
    return $errors[$errorCode] ?? 'An error occurred. Please try again.';
}

/**
 * Sanitize user input for database storage
 */
function sanitizeInput(string $input): string {
    return trim(strip_tags($input));
}

/**
 * Build API response
 */
function apiResponse(bool $success, ?string $message = null, $data = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    
    $response = ['success' => $success];
    
    if ($message !== null) {
        $response['message'] = $message;
    }
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
}
?>
