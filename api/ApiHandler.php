<?php
/**
 * API Handler Base Class
 * Consolidates common API functionality across all endpoints
 * Eliminates boilerplate code duplication
 */

declare(strict_types=1);

class ApiHandler {
    /**
     * Initialize API response headers
     */
    public static function init(): void {
        header('Content-Type: application/json; charset=UTF-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }

    /**
     * Require authentication
     */
    public static function requireAuth(): int {
        session_start();
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            self::sendError('Unauthorized', 401);
            exit;
        }
        
        return (int) $_SESSION['user_id'];
    }

    /**
     * Require specific role
     */
    public static function requireRole(string|array $roles): int {
        $userId = self::requireAuth();
        $userRole = $_SESSION['user_role'] ?? 'registered';
        $requiredRoles = is_string($roles) ? [$roles] : $roles;
        
        if (!in_array($userRole, $requiredRoles)) {
            http_response_code(403);
            self::sendError('Access denied', 403);
            exit;
        }
        
        return $userId;
    }

    /**
     * Get JSON input
     */
    public static function getInput(): array {
        return json_decode(file_get_contents('php://input'), true) ?? [];
    }

    /**
     * Send success response
     */
    public static function sendSuccess(
        ?string $message = null,
        ?array $data = null,
        int $statusCode = 200
    ): void {
        http_response_code($statusCode);
        
        $response = ['success' => true];
        
        if ($message !== null) {
            $response['message'] = $message;
        }
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }

    /**
     * Send error response
     */
    public static function sendError(
        string $error,
        int $statusCode = 400,
        ?array $details = null
    ): void {
        http_response_code($statusCode);
        
        $response = [
            'success' => false,
            'error' => $error,
        ];
        
        if ($details !== null) {
            $response['details'] = $details;
        }
        
        echo json_encode($response);
        exit;
    }

    /**
     * Validate method
     */
    public static function requireMethod(string|array $methods): void {
        $allowedMethods = is_string($methods) ? [$methods] : $methods;
        
        if (!in_array($_SERVER['REQUEST_METHOD'], $allowedMethods)) {
            http_response_code(405);
            self::sendError('Method not allowed', 405);
        }
    }

    /**
     * Get query parameter
     */
    public static function getQueryParam(string $name, ?string $default = null): ?string {
        return $_GET[$name] ?? $default;
    }

    /**
     * Get query parameter as integer
     */
    public static function getQueryParamInt(string $name, int $default = 0): int {
        return intval($_GET[$name] ?? $default);
    }

    /**
     * Try-catch wrapper for database operations
     */
    public static function executeDbOperation(callable $callback): void {
        try {
            $callback();
        } catch (PDOException $e) {
            error_log('Database Error: ' . $e->getMessage());
            http_response_code(500);
            self::sendError('Database error occurred', 500);
        } catch (Exception $e) {
            error_log('API Error: ' . $e->getMessage());
            http_response_code(500);
            self::sendError('An error occurred', 500);
        }
    }

    /**
     * Handle CRUD operations
     */
    public static function handleAction(
        string $action,
        array $handlers
    ): void {
        if (!isset($handlers[$action])) {
            http_response_code(400);
            self::sendError('Invalid action', 400);
            return;
        }
        
        self::executeDbOperation($handlers[$action]);
    }
}
?>
