<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../config/config.php';
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

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

function normalizeLocalPerfumeImageUrl(string $imageUrl): string {
    $imageUrl = trim($imageUrl);
    if ($imageUrl === '') {
        return '';
    }
    if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:\/\//', $imageUrl)) {
        return $imageUrl;
    }

    $publicRoot = __DIR__ . '/../public';
    $imageUrl = preg_replace('#/+#', '/', $imageUrl);
    $lastAssets = strrpos($imageUrl, '/assets/');
    if ($lastAssets !== false) {
        return substr($imageUrl, $lastAssets);
    }
    return $imageUrl;
}

function getLocalPerfumeImageUrl(int $perfumeId, string $perfumeName = ''): string {
    $publicRoot = __DIR__ . '/../public';
    $basePaths = [
        '/assets/perfume_images/',
    ];

    if ($perfumeName !== '') {
        $candidates = getPerfumeNameCandidates($perfumeName);
        $candidateVariants = [];
        foreach ($candidates as $candidateName) {
            $candidateVariants[] = $candidateName;
            $candidateVariants[] = str_replace('-', '_', $candidateName);
        }
        $candidateVariants = array_values(array_unique(array_filter($candidateVariants)));

        foreach ($basePaths as $basePath) {
            foreach ($candidateVariants as $candidateName) {
                foreach (['jpeg', 'jpg', 'png', 'webp'] as $ext) {
                    $relativePath = $basePath . $candidateName . '.' . $ext;
                    if (file_exists($publicRoot . $relativePath)) {
                        return $relativePath;
                    }
                }
            }
        }

        $assetFiles = [];
        foreach ($basePaths as $basePath) {
            $dir = $publicRoot . $basePath;
            if (!is_dir($dir)) {
                continue;
            }
            foreach (scandir($dir) as $file) {
                if (!is_file($dir . DIRECTORY_SEPARATOR . $file)) {
                    continue;
                }
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (!in_array($ext, ['jpeg', 'jpg', 'png', 'webp'], true)) {
                    continue;
                }
                $assetFiles[] = [
                    'relative' => $basePath . $file,
                    'basename' => pathinfo($file, PATHINFO_FILENAME),
                ];
            }
        }

        $bestScore = 0.0;
        $bestPath = '';
        foreach ($assetFiles as $assetFile) {
            foreach ($candidateVariants as $candidateName) {
                similar_text($candidateName, $assetFile['basename'], $percent);
                if ($percent > $bestScore) {
                    $bestScore = $percent;
                    $bestPath = $assetFile['relative'];
                }
            }
        }

        if ($bestScore >= 60.0 && $bestPath !== '') {
            return $bestPath;
        }
    }

    return '/assets/placeholder-perfume.svg';
}

function isRemoteImageUrl(string $imageUrl): bool {
    return (bool)preg_match('/\.(?:jpe?g|png|gif|webp|svg)(?:[?#].*)?$/i', $imageUrl);
}

function normalizePerfumeImageUrl(?string $imageUrl, int $perfumeId, string $perfumeName = ''): string {
    $imageUrl = trim((string)$imageUrl);
    if ($imageUrl !== '') {
        $imageUrl = normalizeLocalPerfumeImageUrl($imageUrl);
    }

    // Prefer any available local candidate from perfume_images.
    $localCandidate = getLocalPerfumeImageUrl($perfumeId, $perfumeName);
    if ($localCandidate !== '/assets/placeholder-perfume.svg') {
        return $localCandidate;
    }

    if ($imageUrl === '') {
        return '/assets/placeholder-perfume.svg';
    }

    if (preg_match('/^[a-zA-Z][a-zA-Z0-9+.-]*:/', $imageUrl) || str_starts_with($imageUrl, '//') || str_starts_with($imageUrl, '/')) {
        if (preg_match('/^https?:\/\//i', $imageUrl) && !isRemoteImageUrl($imageUrl)) {
            return '/assets/placeholder-perfume.svg';
        }
        if (str_starts_with($imageUrl, '/perfume_images/')) {
            return '/assets' . $imageUrl;
        }
        return $imageUrl;
    }

    $normalized = '/' . ltrim($imageUrl, './');
    if (str_starts_with($normalized, '/perfume_images/')) {
        return '/assets' . $normalized;
    }
    return $normalized;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Get all perfumes with optional search
if ($action === 'perfumes') {
    try {
        $search = trim($_GET['search'] ?? '');
        
        if ($search) {
            $stmt = $pdo->prepare('
                SELECT id, name, description, image_url, top_notes, heart_notes, base_notes, accords, rating 
                FROM perfumes 
                WHERE name LIKE :search 
                   OR description LIKE :search 
                   OR top_notes LIKE :search 
                   OR heart_notes LIKE :search 
                   OR base_notes LIKE :search 
                ORDER BY rating DESC
            ');
            $stmt->execute([':search' => '%' . $search . '%']);
        } else {
            $stmt = $pdo->query('
                SELECT id, name, description, image_url, top_notes, heart_notes, base_notes, accords, rating 
                FROM perfumes 
                ORDER BY rating DESC
            ');
        }

        $perfumes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Convert ratings to float and normalize image URLs from the database
        foreach ($perfumes as &$perfume) {
            $perfume['rating'] = (float)($perfume['rating'] ?? 0);
            $perfume['image_url'] = normalizePerfumeImageUrl($perfume['image_url'] ?? '', (int)$perfume['id'], $perfume['name'] ?? '');
        }

        echo json_encode(['success' => true, 'data' => $perfumes]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch perfumes']);
    }
    exit;
}

// Get perfume inventory (for stock display)
if ($action === 'inventory') {
    try {
        $perfumeId = (int)($_GET['perfume_id'] ?? 0);
        
        if (!$perfumeId) {
            http_response_code(400);
            echo json_encode(['error' => 'Perfume ID required']);
            exit;
        }

        $stmt = $pdo->prepare('SELECT available_quantity FROM inventory WHERE perfume_id = :perfume_id');
        $stmt->execute([':perfume_id' => $perfumeId]);
        $inventory = $stmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'stock' => $inventory ? (int)$inventory['available_quantity'] : 0
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch inventory']);
    }
    exit;
}

// Purchase perfume (for registered users)
if ($action === 'purchase_perfume') {
    try {
        // Check if user is logged in and not a staff account
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') === 'staff') {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        $customerId = $_SESSION['user_id'];
        $perfumeId = (int)($_POST['perfume_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if (!$perfumeId) {
            http_response_code(400);
            echo json_encode(['error' => 'Perfume ID required']);
            exit;
        }

        // Check if perfume exists and get inventory
        $invStmt = $pdo->prepare('SELECT i.id, i.available_quantity, p.name FROM inventory i JOIN perfumes p ON i.perfume_id = p.id WHERE i.perfume_id = :perfume_id');
        $invStmt->execute([':perfume_id' => $perfumeId]);
        $inventory = $invStmt->fetch(PDO::FETCH_ASSOC);

        if (!$inventory) {
            http_response_code(404);
            echo json_encode(['error' => 'Perfume not found']);
            exit;
        }

        if ($inventory['available_quantity'] < $quantity) {
            http_response_code(400);
            echo json_encode(['error' => 'Insufficient stock']);
            exit;
        }

        // Begin transaction
        $pdo->beginTransaction();

        // Record the purchase
        $purchaseStmt = $pdo->prepare('INSERT INTO customer_purchases (customer_id, perfume_id, quantity) VALUES (:customer_id, :perfume_id, :quantity)');
        $purchaseStmt->execute([
            ':customer_id' => $customerId,
            ':perfume_id' => $perfumeId,
            ':quantity' => $quantity
        ]);

        // Update inventory
        $newQuantity = $inventory['available_quantity'] - $quantity;
        $updateStmt = $pdo->prepare('UPDATE inventory SET available_quantity = :quantity, last_updated = CURRENT_TIMESTAMP WHERE id = :id');
        $updateStmt->execute([
            ':quantity' => $newQuantity,
            ':id' => $inventory['id']
        ]);

        // Log to audit
        $auditStmt = $pdo->prepare('INSERT INTO inventory_audit (inventory_id, changed_by, prev_available, new_available, prev_damaged, new_damaged, reason) VALUES (:inv_id, :changed_by, :prev_avail, :new_avail, :prev_damaged, :new_damaged, :reason)');
        $auditStmt->execute([
            ':inv_id' => $inventory['id'],
            ':changed_by' => $customerId,
            ':prev_avail' => $inventory['available_quantity'],
            ':new_avail' => $newQuantity,
            ':prev_damaged' => 0, // We don't change damaged in purchases
            ':new_damaged' => 0,
            ':reason' => 'Customer purchase'
        ]);

        $pdo->commit();

        $receipt = [
            'customer_id' => $customerId,
            'purchased_at' => date('c'),
            'items' => [
                [
                    'perfume_id' => $perfumeId,
                    'name' => $inventory['name'],
                    'quantity' => $quantity,
                    'remaining_stock' => $newQuantity
                ]
            ],
            'item_count' => $quantity
        ];

        $_SESSION['last_receipt'] = $receipt;

        echo json_encode([
            'success' => true,
            'message' => "Successfully purchased {$quantity} x {$inventory['name']}",
            'remaining_stock' => $newQuantity,
            'receipt' => $receipt
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Purchase failed']);
    }
    exit;
}

// Add item to cart
if ($action === 'cart_add') {
    try {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') === 'staff') {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        $perfumeId = (int)($_POST['perfume_id'] ?? 0);
        $quantity = max(1, (int)($_POST['quantity'] ?? 1));

        if (!$perfumeId) {
            http_response_code(400);
            echo json_encode(['error' => 'Perfume ID required']);
            exit;
        }

        $checkStmt = $pdo->prepare('SELECT id, name FROM perfumes WHERE id = :perfume_id');
        $checkStmt->execute([':perfume_id' => $perfumeId]);
        $perfume = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if (!$perfume) {
            http_response_code(404);
            echo json_encode(['error' => 'Perfume not found']);
            exit;
        }

        if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $_SESSION['cart'][$perfumeId] = ($_SESSION['cart'][$perfumeId] ?? 0) + $quantity;

        echo json_encode([
            'success' => true,
            'message' => "Added {$quantity} x {$perfume['name']} to cart",
            'cart_count' => array_sum($_SESSION['cart'])
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to add to cart']);
    }
    exit;
}

// Get cart contents
if ($action === 'cart_items') {
    try {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') === 'staff') {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        $cart = $_SESSION['cart'] ?? [];
        if (empty($cart)) {
            echo json_encode(['success' => true, 'data' => [], 'cart_count' => 0]);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($cart), '?'));
        $stmt = $pdo->prepare("SELECT id, name, description, image_url, rating FROM perfumes WHERE id IN ($placeholders)");
        $stmt->execute(array_keys($cart));
        $perfumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = [];
        foreach ($perfumes as $perfume) {
            $id = (int)$perfume['id'];
            $quantity = $cart[$id] ?? 0;
            if ($quantity > 0) {
                $items[] = [
                    'perfume_id' => $id,
                    'name' => $perfume['name'],
                    'description' => $perfume['description'],
                    'image_url' => normalizePerfumeImageUrl($perfume['image_url'] ?? '', $id, $perfume['name'] ?? ''),
                    'rating' => (float)($perfume['rating'] ?? 0),
                    'quantity' => $quantity
                ];
            }
        }

        echo json_encode(['success' => true, 'data' => $items, 'cart_count' => array_sum($cart)]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch cart']);
    }
    exit;
}

// Checkout cart and create receipt
if ($action === 'cart_checkout') {
    try {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') === 'staff') {
            http_response_code(401);
            echo json_encode(['error' => 'Authentication required']);
            exit;
        }

        $customerId = $_SESSION['user_id'];
        $cart = $_SESSION['cart'] ?? [];

        if (empty($cart)) {
            http_response_code(400);
            echo json_encode(['error' => 'Your cart is empty']);
            exit;
        }

        $placeholders = implode(',', array_fill(0, count($cart), '?'));
        $stmt = $pdo->prepare("SELECT i.id, i.available_quantity, p.id AS perfume_id, p.name FROM inventory i JOIN perfumes p ON i.perfume_id = p.id WHERE p.id IN ($placeholders)");
        $stmt->execute(array_keys($cart));
        $inventory = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $inventory[(int)$row['perfume_id']] = $row;
        }

        foreach ($cart as $perfumeId => $quantity) {
            if (!isset($inventory[$perfumeId])) {
                http_response_code(404);
                echo json_encode(['error' => 'Perfume not available: ' . $perfumeId]);
                exit;
            }
            if ($inventory[$perfumeId]['available_quantity'] < $quantity) {
                http_response_code(400);
                echo json_encode(['error' => 'Insufficient stock for ' . $inventory[$perfumeId]['name']]);
                exit;
            }
        }

        $pdo->beginTransaction();
        $receiptItems = [];
        foreach ($cart as $perfumeId => $quantity) {
            $item = $inventory[$perfumeId];
            $newAvailable = $item['available_quantity'] - $quantity;

            $purchaseStmt = $pdo->prepare('INSERT INTO customer_purchases (customer_id, perfume_id, quantity) VALUES (:customer_id, :perfume_id, :quantity)');
            $purchaseStmt->execute([
                ':customer_id' => $customerId,
                ':perfume_id' => $perfumeId,
                ':quantity' => $quantity
            ]);

            $updateStmt = $pdo->prepare('UPDATE inventory SET available_quantity = :quantity, last_updated = CURRENT_TIMESTAMP WHERE id = :id');
            $updateStmt->execute([
                ':quantity' => $newAvailable,
                ':id' => $item['id']
            ]);

            $auditStmt = $pdo->prepare('INSERT INTO inventory_audit (inventory_id, changed_by, prev_available, new_available, prev_damaged, new_damaged, reason) VALUES (:inv_id, :changed_by, :prev_avail, :new_avail, :prev_damaged, :new_damaged, :reason)');
            $auditStmt->execute([
                ':inv_id' => $item['id'],
                ':changed_by' => $customerId,
                ':prev_avail' => $item['available_quantity'],
                ':new_avail' => $newAvailable,
                ':prev_damaged' => 0,
                ':new_damaged' => 0,
                ':reason' => 'Customer cart checkout'
            ]);

            $receiptItems[] = [
                'perfume_id' => $perfumeId,
                'name' => $item['name'],
                'quantity' => $quantity,
                'remaining_stock' => $newAvailable
            ];
        }

        $pdo->commit();

        $receipt = [
            'customer_id' => $customerId,
            'purchased_at' => date('c'),
            'items' => $receiptItems,
            'item_count' => array_sum($cart)
        ];

        unset($_SESSION['cart']);
        $_SESSION['last_receipt'] = $receipt;

        echo json_encode([
            'success' => true,
            'message' => 'Purchase completed successfully',
            'receipt' => $receipt
        ]);
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['error' => 'Checkout failed']);
    }
    exit;
}

// Get about info
if ($action === 'about') {
    try {
        $stmt = $pdo->query('
            SELECT heading, intro, details, features, audience, benefits, 
                   stat_1_value, stat_1_label, stat_2_value, stat_2_label, stat_3_value, stat_3_label 
            FROM about_info 
            ORDER BY id DESC 
            LIMIT 1
        ');
        $about = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($about && isset($about['features'])) {
            $about['features'] = json_decode($about['features'], true) ?? [];
        }

        echo json_encode(['success' => true, 'data' => $about ?: []]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch about info']);
    }
    exit;
}

// Submit contact form
if ($action === 'contact' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = file_get_contents('php://input');
    $data = json_decode($body, true);

    if (!is_array($data)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid JSON']);
        exit;
    }

    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $message = trim($data['message'] ?? '');

    // Server-side validation (never trust client)
    if (!$name || !$email) {
        http_response_code(400);
        echo json_encode(['error' => 'Name and email are required']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address']);
        exit;
    }

    try {
        $stmt = $pdo->prepare('INSERT INTO contacts (name, email, message) VALUES (:name, :email, :message)');
        $stmt->execute([
            ':name' => $name,
            ':email' => $email,
            ':message' => $message,
        ]);

        echo json_encode(['success' => true, 'message' => 'Thank you! Your message was received.']);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to save your message']);
    }
    exit;
}

// Not found
http_response_code(404);
echo json_encode(['error' => 'Action not found']);
