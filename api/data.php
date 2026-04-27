<?php
declare(strict_types=1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    require_once __DIR__ . '/../config/config.php';
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
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
        
        // Convert ratings to float
        foreach ($perfumes as &$perfume) {
            $perfume['rating'] = (float)($perfume['rating'] ?? 0);
        }

        echo json_encode(['success' => true, 'data' => $perfumes]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch perfumes']);
    }

// Get perfume inventory (for stock display)
if ($action === 'perfume_inventory') {
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

// Purchase perfume (for registered users)
if ($action === 'purchase_perfume') {
    try {
        // Check if user is logged in
        session_start();
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'registered') {
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
        $updateStmt = $pdo->prepare('UPDATE inventory SET available_quantity = :quantity, last_updated = datetime("now") WHERE id = :id');
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

        echo json_encode([
            'success' => true,
            'message' => "Successfully purchased {$quantity} x {$inventory['name']}",
            'remaining_stock' => $newQuantity
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Purchase failed']);
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
