<?php
declare(strict_types=1);

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Headers for JSON and CORS
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Session management
session_start();

// Require database connection
require_once '../config/config.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$username = $_SESSION['username'] ?? null;
$role = $_SESSION['role'] ?? null;

// Verify user exists in database
if (!$userId || !$username) {
    http_response_code(401);
    echo json_encode(['error' => 'Session invalid']);
    exit;
}

// Get action from request
$action = $_GET['action'] ?? ($_POST['action'] ?? null);

try {
    switch ($action) {
        // GET USER INFO
        case 'user_info':
            handleUserInfo($pdo, $userId, $username, $role);
            break;

        // GET PERFUMES
        case 'perfumes':
            handleGetPerfumes($pdo, $role, $userId);
            break;

        // GET INVENTORY
        case 'inventory':
            handleGetInventory($pdo, $role, $userId);
            break;

        // UPDATE INVENTORY
        case 'update_inventory':
            handleUpdateInventory($pdo, $role, $userId, $username);
            break;

        // CREATE PURCHASE LIST
        case 'create_purchase_list':
            handleCreatePurchaseList($pdo, $userId, $username);
            break;

        // GET PURCHASE LISTS
        case 'purchase_lists':
            handleGetPurchaseLists($pdo, $role, $userId);
            break;

        // APPROVE PURCHASE LIST
        case 'approve_list':
            handleApprovePurchaseList($pdo, $userId, $username);
            break;

        // REJECT PURCHASE LIST
        case 'reject_list':
            handleRejectPurchaseList($pdo, $userId, $username);
            break;

        // GET STAFF ACCESS
        case 'staff_access':
            handleGetStaffAccess($pdo, $role);
            break;

        // GET AUDIT LOGS
        case 'audit_logs':
            handleGetAuditLogs($pdo, $role);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    error_log('Dashboard API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}

/**
 * Get current user information
 */
function handleUserInfo($pdo, $userId, $username, $role) {
    $accessLevel = null;
    if ($role === 'staff') {
        $query = 'SELECT COALESCE(MAX(access_level), "manage") as max_access FROM inventory_access WHERE staff_id = :user_id';
        $stmt = $pdo->prepare($query);
        $stmt->execute([':user_id' => $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $accessLevel = $result['max_access'] ?? 'manage';
    }

    echo json_encode([
        'user_id' => $userId,
        'username' => $username,
        'role' => $role,
        'access_level' => $accessLevel,
    ]);
}

/**
 * Get perfumes list
 */
function handleGetPerfumes($pdo, $role, $userId) {
    try {
        // Only staff/owner can access
        if (!in_array($role, ['staff', 'owner'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $query = 'SELECT id, name, description FROM perfumes ORDER BY name';
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $perfumes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $perfumes,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch perfumes']);
    }
}

/**
 * Get inventory records
 */
function handleGetInventory($pdo, $role, $userId) {
    try {
        if ($role === 'staff') {
            // Staff sees only their assigned inventory
            $query = 'SELECT i.id, i.perfume_id, p.name as perfume_name, i.available_quantity, 
                      i.damaged_quantity, i.expiration_date, i.last_updated
                      FROM inventory i
                      JOIN perfumes p ON i.perfume_id = p.id
                      JOIN inventory_access ia ON i.id = ia.inventory_id
                      WHERE ia.staff_id = :user_id
                      ORDER BY p.name';
        } else {
            // Owner/admin sees all inventory
            $query = 'SELECT i.id, i.perfume_id, p.name as perfume_name, i.available_quantity, 
                      i.damaged_quantity, i.expiration_date, i.last_updated
                      FROM inventory i
                      JOIN perfumes p ON i.perfume_id = p.id
                      ORDER BY p.name';
        }

        $stmt = $pdo->prepare($query);
        if ($role === 'staff') {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $inventory = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $inventory,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch inventory']);
    }
}

/**
 * Update inventory record
 */
function handleUpdateInventory($pdo, $role, $userId, $username) {
    try {
        // Only staff can update
        if ($role !== 'staff') {
            http_response_code(403);
            echo json_encode(['error' => 'Only staff can update inventory']);
            return;
        }

        // Check if user has manage access to this inventory
        $accessQuery = 'SELECT access_level FROM inventory_access 
                       WHERE staff_id = :user_id AND inventory_id = (
                           SELECT id FROM inventory WHERE perfume_id = :perfume_id
                       )';
        $accessStmt = $pdo->prepare($accessQuery);
        $accessStmt->execute([':user_id' => $userId, ':perfume_id' => $perfumeId]);
        $access = $accessStmt->fetch(PDO::FETCH_ASSOC);

        if (!$access || $access['access_level'] !== 'manage') {
            http_response_code(403);
            echo json_encode(['error' => 'Insufficient permissions to update this inventory']);
            return;
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $perfumeId = $data['perfume_id'] ?? null;
        $availableQty = $data['available_quantity'] ?? 0;
        $damagedQty = $data['damaged_quantity'] ?? 0;
        $expirationDate = $data['expiration_date'] ?? null;

        if (!$perfumeId) {
            http_response_code(400);
            echo json_encode(['error' => 'Perfume ID required']);
            return;
        }

        // Check if inventory exists
        $checkQuery = 'SELECT id, available_quantity as prev_available, damaged_quantity as prev_damaged 
                       FROM inventory WHERE perfume_id = :perfume_id LIMIT 1';
        $checkStmt = $pdo->prepare($checkQuery);
        $checkStmt->execute([':perfume_id' => $perfumeId]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update existing
            $updateQuery = 'UPDATE inventory 
                           SET available_quantity = :available_qty, damaged_quantity = :damaged_qty,
                               expiration_date = :exp_date, last_updated = NOW()
                           WHERE perfume_id = :perfume_id';
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([
                ':available_qty' => $availableQty,
                ':damaged_qty' => $damagedQty,
                ':exp_date' => $expirationDate ?: null,
                ':perfume_id' => $perfumeId,
            ]);

            // Log to audit
            $auditQuery = 'INSERT INTO inventory_audit 
                          (inventory_id, changed_by, prev_available, new_available, 
                           prev_damaged, new_damaged, reason, changed_at)
                          VALUES (:inv_id, :changed_by, :prev_avail, :new_avail, 
                                  :prev_damaged, :new_damaged, :reason, NOW())';
            $auditStmt = $pdo->prepare($auditQuery);
            $auditStmt->execute([
                ':inv_id' => $existing['id'],
                ':changed_by' => $userId,
                ':prev_avail' => $existing['prev_available'],
                ':new_avail' => $availableQty,
                ':prev_damaged' => $existing['prev_damaged'],
                ':new_damaged' => $damagedQty,
                ':reason' => 'Staff update',
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Inventory updated successfully',
            ]);
        } else {
            // Insert new
            $insertQuery = 'INSERT INTO inventory 
                           (perfume_id, available_quantity, damaged_quantity, 
                            expiration_date, last_updated)
                           VALUES (:perfume_id, :available_qty, :damaged_qty, :exp_date, NOW())';
            $insertStmt = $pdo->prepare($insertQuery);
            $insertStmt->execute([
                ':perfume_id' => $perfumeId,
                ':available_qty' => $availableQty,
                ':damaged_qty' => $damagedQty,
                ':exp_date' => $expirationDate ?: null,
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Inventory created successfully',
            ]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update inventory: ' . $e->getMessage()]);
    }
}

/**
 * Create a new purchase list
 */
function handleCreatePurchaseList($pdo, $userId, $username) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $perfumeId = $data['perfume_id'] ?? null;
        $quantity = $data['quantity'] ?? 0;

        if (!$perfumeId || $quantity <= 0) {
            http_response_code(400);
            echo json_encode(['error' => 'Valid perfume and quantity required']);
            return;
        }

        // Create purchase list
        $insertQuery = 'INSERT INTO purchase_lists 
                       (staff_id, status, created_at)
                       VALUES (:staff_id, :status, NOW())';
        $insertStmt = $pdo->prepare($insertQuery);
        $insertStmt->execute([
            ':staff_id' => $userId,
            ':status' => 'pending',
        ]);

        $listId = $pdo->lastInsertId();

        // Add item to list (using perfumes table or a pivot table)
        $itemQuery = 'INSERT INTO purchase_list_items 
                     (purchase_list_id, perfume_id, quantity)
                     VALUES (:list_id, :perfume_id, :quantity)';
        $itemStmt = $pdo->prepare($itemQuery);
        $itemStmt->execute([
            ':list_id' => $listId,
            ':perfume_id' => $perfumeId,
            ':quantity' => $quantity,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Purchase list created',
            'list_id' => $listId,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create purchase list: ' . $e->getMessage()]);
    }
}

/**
 * Get purchase lists (filtered by role)
 */
function handleGetPurchaseLists($pdo, $role, $userId) {
    try {
        if ($role === 'staff') {
            // Staff sees only their own lists
            $query = 'SELECT pl.id, pl.staff_id, u.username, pl.status, 
                      pl.owner_note, pl.created_at, COUNT(pli.id) as item_count
                      FROM purchase_lists pl
                      JOIN users u ON pl.staff_id = u.id
                      LEFT JOIN purchase_list_items pli ON pl.id = pli.purchase_list_id
                      WHERE pl.staff_id = :user_id
                      GROUP BY pl.id
                      ORDER BY pl.created_at DESC';
        } else {
            // Owner sees all lists
            $query = 'SELECT pl.id, pl.staff_id, u.username, pl.status, 
                      pl.owner_note, pl.created_at, COUNT(pli.id) as item_count
                      FROM purchase_lists pl
                      JOIN users u ON pl.staff_id = u.id
                      LEFT JOIN purchase_list_items pli ON pl.id = pli.purchase_list_id
                      GROUP BY pl.id
                      ORDER BY pl.created_at DESC';
        }

        $stmt = $pdo->prepare($query);
        if ($role === 'staff') {
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        }
        $stmt->execute();
        $lists = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $lists,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch purchase lists']);
    }
}

/**
 * Approve a purchase list
 */
function handleApprovePurchaseList($pdo, $userId, $username) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $listId = $data['list_id'] ?? null;
        $note = $data['note'] ?? '';

        if (!$listId) {
            http_response_code(400);
            echo json_encode(['error' => 'List ID required']);
            return;
        }

        $query = 'UPDATE purchase_lists 
                 SET status = :status, owner_note = :note, approved_at = NOW()
                 WHERE id = :list_id';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':status' => 'approved',
            ':note' => $note,
            ':list_id' => $listId,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Purchase list approved',
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to approve list']);
    }
}

/**
 * Reject a purchase list
 */
function handleRejectPurchaseList($pdo, $userId, $username) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        $listId = $data['list_id'] ?? null;
        $note = $data['note'] ?? '';

        if (!$listId) {
            http_response_code(400);
            echo json_encode(['error' => 'List ID required']);
            return;
        }

        $query = 'UPDATE purchase_lists 
                 SET status = :status, owner_note = :note, approved_at = NOW()
                 WHERE id = :list_id';
        $stmt = $pdo->prepare($query);
        $stmt->execute([
            ':status' => 'rejected',
            ':note' => $note,
            ':list_id' => $listId,
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Purchase list rejected',
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to reject list']);
    }
}

/**
 * Get staff access information (owner only)
 */
function handleGetStaffAccess($pdo, $role) {
    try {
        if ($role !== 'owner') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $query = 'SELECT u.id, u.username, 
                 COALESCE(MAX(ia.access_level), \"manage\") as max_access_level,
                 COUNT(DISTINCT ia.inventory_id) as perfume_count
                 FROM users u
                 LEFT JOIN inventory_access ia ON u.id = ia.staff_id
                 WHERE u.role = \"staff\"
                 GROUP BY u.id
                 ORDER BY u.username';
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $staff,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch staff access']);
    }
}

/**
 * Get inventory audit logs (owner only)
 */
function handleGetAuditLogs($pdo, $role) {
    try {
        if ($role !== 'owner') {
            http_response_code(403);
            echo json_encode(['error' => 'Forbidden']);
            return;
        }

        $query = 'SELECT ia.id, p.name as perfume_name, u.username, 
                 ia.prev_available, ia.new_available, ia.prev_damaged, ia.new_damaged,
                 ia.reason, ia.changed_at
                 FROM inventory_audit ia
                 JOIN inventory i ON ia.inventory_id = i.id
                 JOIN perfumes p ON i.perfume_id = p.id
                 JOIN users u ON ia.changed_by = u.id
                 ORDER BY ia.changed_at DESC
                 LIMIT 100';
        $stmt = $pdo->prepare($query);
        $stmt->execute();
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'success' => true,
            'data' => $logs,
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch audit logs']);
    }
}
