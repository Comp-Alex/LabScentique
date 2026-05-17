<?php
/**
 * User Favorites & Purchases API Endpoint
 * Handles: adding/removing favorites, fetching favorites, tracking purchases
 */

declare(strict_types=1);

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../config/config.php';

function normalizeText(string $text): string {
    if (function_exists('mb_strtolower')) {
        return mb_strtolower($text, 'UTF-8');
    }
    return strtolower($text);
}

function sanitizePerfumeName(string $name): string {
    $normalized = normalizeText($name);
    $normalized = preg_replace('/[^\p{L}\p{N}\s\/&_\-]+/u', '', $normalized);
    $normalized = preg_replace('/[\/&_]+/u', '-', $normalized);
    $normalized = preg_replace('/[\s_]+/u', '-', $normalized);
    $normalized = preg_replace('/-+/', '-', $normalized);
    return trim($normalized, '-');
}

function getPerfumeNameCandidates(string $perfumeName): array {
    $perfumeName = trim(str_replace([' – ', '—'], ' - ', $perfumeName));
    $candidates = [];
    $fullName = sanitizePerfumeName($perfumeName);
    if ($fullName !== '') {
        $candidates[] = $fullName;
    }

    $parts = explode(' - ', $perfumeName, 2);
    if (count($parts) === 2) {
        [$productPart, $brandPart] = $parts;
        $productOnly = sanitizePerfumeName(preg_replace('/\s*\(.*?\)/', '', $productPart));
        $brandOnly = sanitizePerfumeName($brandPart);

        if ($productOnly !== '') {
            $candidates[] = $productOnly;
            if ($brandOnly !== '') {
                $candidates[] = $productOnly . '-' . $brandOnly;
                $candidates[] = $brandOnly . '-' . $productOnly;
            }
        }
    }

    return array_values(array_unique($candidates));
}

function getLocalPerfumeImageUrl(int $perfumeId, string $perfumeName = ''): string {
    $publicRoot = __DIR__ . '/../public';
    if ($perfumeName !== '') {
        $basePath = '/assets/perfume_images/';
        foreach (getPerfumeNameCandidates($perfumeName) as $candidateName) {
            foreach (['jpeg', 'jpg', 'png', 'webp'] as $ext) {
                $relativePath = $basePath . $candidateName . '.' . $ext;
                if (file_exists($publicRoot . $relativePath)) {
                    return $relativePath;
                }
            }
        }
    }
    return '/assets/perfumes/' . $perfumeId . '.jpg';
}

function isRemoteImageUrl(string $imageUrl): bool {
    return (bool)preg_match('/\.(?:jpe?g|png|gif|webp|svg)(?:[?#].*)?$/i', $imageUrl);
}

function normalizePerfumeImageUrl(?string $imageUrl, int $perfumeId, string $perfumeName = ''): string {
    $imageUrl = trim((string)$imageUrl);

    // Prefer any available local candidate from perfume_images
    $localCandidate = getLocalPerfumeImageUrl($perfumeId, $perfumeName);
    $fallbackLocal = '/assets/perfumes/' . $perfumeId . '.jpg';
    if ($localCandidate !== $fallbackLocal) {
        return $localCandidate;
    }

    if ($imageUrl === '') {
        return $fallbackLocal;
    }

    if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $imageUrl) || str_starts_with($imageUrl, '//') || str_starts_with($imageUrl, '/')) {
        if (preg_match('/^https?:\/\//i', $imageUrl) && !isRemoteImageUrl($imageUrl)) {
            return $fallbackLocal;
        }
        return $imageUrl;
    }

    return '/' . ltrim($imageUrl, './');
}

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = intval($_SESSION['user_id']);
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get_favorites':
            getFavorites($pdo, $userId);
            break;
        case 'add_favorite':
            addFavorite($pdo, $userId);
            break;
        case 'remove_favorite':
            removeFavorite($pdo, $userId);
            break;
        case 'get_purchases':
            getPurchases($pdo, $userId);
            break;
        case 'add_purchase':
            addPurchase($pdo, $userId);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

/**
 * Get all favorites for the user with perfume details
 */
function getFavorites($pdo, $userId): void {
    $stmt = $pdo->prepare('
        SELECT 
            uf.id,
            p.id as perfume_id,
            p.name,
            p.description,
            p.image_url,
            p.rating,
            p.top_notes,
            p.heart_notes,
            p.base_notes,
            uf.added_at
        FROM user_favorites uf
        JOIN perfumes p ON uf.perfume_id = p.id
        WHERE uf.user_id = ?
        ORDER BY uf.added_at DESC
    ');
    $stmt->execute([$userId]);
    $favorites = $stmt->fetchAll();

    foreach ($favorites as &$favorite) {
        $favorite['image_url'] = normalizePerfumeImageUrl($favorite['image_url'] ?? '', (int)$favorite['perfume_id'], $favorite['name'] ?? '');
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $favorites]);
}

/**
 * Add a perfume to user's favorites
 */
function addFavorite($pdo, $userId): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $perfumeId = intval($data['perfume_id'] ?? 0);

    if ($perfumeId === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'perfume_id is required']);
        return;
    }

    // Verify perfume exists
    $checkStmt = $pdo->prepare('SELECT id FROM perfumes WHERE id = ?');
    $checkStmt->execute([$perfumeId]);
    if (!$checkStmt->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Perfume not found']);
        return;
    }

    // Check if already favorited
    $checkFavStmt = $pdo->prepare('SELECT id FROM user_favorites WHERE user_id = ? AND perfume_id = ?');
    $checkFavStmt->execute([$userId, $perfumeId]);
    if ($checkFavStmt->fetch()) {
        http_response_code(409);
        echo json_encode(['error' => 'Already in favorites']);
        return;
    }

    // Add to favorites
    $stmt = $pdo->prepare('INSERT INTO user_favorites (user_id, perfume_id) VALUES (?, ?)');
    $stmt->execute([$userId, $perfumeId]);

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Added to favorites']);
}

/**
 * Remove a perfume from user's favorites
 */
function removeFavorite($pdo, $userId): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $perfumeId = intval($data['perfume_id'] ?? 0);

    if ($perfumeId === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'perfume_id is required']);
        return;
    }

    $stmt = $pdo->prepare('DELETE FROM user_favorites WHERE user_id = ? AND perfume_id = ?');
    $stmt->execute([$userId, $perfumeId]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Removed from favorites']);
}

/**
 * Get all purchases for the user with perfume details
 */
function getPurchases($pdo, $userId): void {
    $stmt = $pdo->prepare('
        SELECT 
            cp.id,
            p.id as perfume_id,
            p.name,
            p.description,
            p.image_url,
            p.rating,
            cp.quantity,
            cp.purchase_date
        FROM customer_purchases cp
        JOIN perfumes p ON cp.perfume_id = p.id
        WHERE cp.customer_id = ?
        ORDER BY cp.purchase_date DESC
    ');
    $stmt->execute([$userId]);
    $purchases = $stmt->fetchAll();

    foreach ($purchases as &$purchase) {
        $purchase['image_url'] = normalizePerfumeImageUrl($purchase['image_url'] ?? '', (int)$purchase['perfume_id'], $purchase['name'] ?? '');
    }
    
    http_response_code(200);
    echo json_encode(['success' => true, 'data' => $purchases]);
}

/**
 * Add a purchase record for the user
 */
function addPurchase($pdo, $userId): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $perfumeId = intval($data['perfume_id'] ?? 0);
    $quantity = intval($data['quantity'] ?? 1);

    if ($perfumeId === 0) {
        http_response_code(400);
        echo json_encode(['error' => 'perfume_id is required']);
        return;
    }

    if ($quantity < 1) {
        http_response_code(400);
        echo json_encode(['error' => 'quantity must be at least 1']);
        return;
    }

    // Verify perfume exists and check availability
    $checkStmt = $pdo->prepare('
        SELECT p.id, i.available_quantity 
        FROM perfumes p
        LEFT JOIN inventory i ON p.id = i.perfume_id
        WHERE p.id = ?
    ');
    $checkStmt->execute([$perfumeId]);
    $perfume = $checkStmt->fetch();

    if (!$perfume) {
        http_response_code(404);
        echo json_encode(['error' => 'Perfume not found']);
        return;
    }

    if ($perfume['available_quantity'] < $quantity) {
        http_response_code(400);
        echo json_encode(['error' => 'Not enough stock available']);
        return;
    }

    // Record the purchase
    $stmt = $pdo->prepare('
        INSERT INTO customer_purchases (customer_id, perfume_id, quantity) 
        VALUES (?, ?, ?)
    ');
    $stmt->execute([$userId, $perfumeId, $quantity]);

    // Update inventory
    $updateStmt = $pdo->prepare('
        UPDATE inventory 
        SET available_quantity = available_quantity - ? 
        WHERE perfume_id = ?
    ');
    $updateStmt->execute([$quantity, $perfumeId]);

    http_response_code(201);
    echo json_encode(['success' => true, 'message' => 'Purchase recorded successfully']);
}
?>
