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

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$dbError = false;
$dbErrorMessage = '';
try {
    require_once __DIR__ . '/../config/config.php';
} catch (PDOException $e) {
    $dbError = true;
    $dbErrorMessage = $e->getMessage();
}

if ($dbError) {
    echo '<p>Database unavailable. Please try again later.</p>';
    exit;
}

$userId = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'registered';
$dashboardError = '';
$dashboardSuccess = '';

try {
    $stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE id = :id');
    $stmt->execute([':id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        session_destroy();
        header('Location: index.php');
        exit;
    }
    $role = $user['role'];
    $_SESSION['role'] = $role;

    // Only staff and owner can access dashboard
    if (!in_array($role, ['staff', 'owner'])) {
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    echo '<p>Unable to load user data.</p>';
    exit;
}

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function userHasRole(string $requiredRole, string $currentRole): bool
{
    return $requiredRole === $currentRole;
}

// Check inventory access for staff members
function checkInventoryAccess(PDO $pdo, int $userId, int $perfumeId, string $requiredLevel = 'manage'): bool
{
    // First get the inventory_id for this perfume
    $invStmt = $pdo->prepare('SELECT id FROM inventory WHERE perfume_id = :perfume_id');
    $invStmt->execute([':perfume_id' => $perfumeId]);
    $inventory = $invStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$inventory) {
        return false; // No inventory record
    }
    
    $stmt = $pdo->prepare('SELECT access_level FROM inventory_access WHERE staff_id = :staff_id AND inventory_id = :inventory_id');
    $stmt->execute([':staff_id' => $userId, ':inventory_id' => $inventory['id']]);
    $access = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$access) {
        return false; // No access
    }
    
    $levels = ['view' => 1, 'manage' => 2, 'admin' => 3];
    $userLevel = $levels[$access['access_level']] ?? 0;
    $requiredLevelNum = $levels[$requiredLevel] ?? 0;
    
    return $userLevel >= $requiredLevelNum;
}

// Log inventory change to audit table
function logInventoryChange(PDO $pdo, int $inventoryId, int $staffId, $prevAvail, $newAvail, $prevDamaged, $newDamaged, string $reason = ''): bool
{
    try {
        $stmt = $pdo->prepare('INSERT INTO inventory_audit (inventory_id, changed_by, prev_available, new_available, prev_damaged, new_damaged, reason) VALUES (:inv_id, :changed_by, :prev_avail, :new_avail, :prev_damaged, :new_damaged, :reason)');
        return $stmt->execute([
            ':inv_id' => $inventoryId,
            ':changed_by' => $staffId,
            ':prev_avail' => $prevAvail,
            ':new_avail' => $newAvail,
            ':prev_damaged' => $prevDamaged,
            ':new_damaged' => $newDamaged,
            ':reason' => $reason,
        ]);
    } catch (PDOException $e) {
        return false;
    }
}

if (!in_array($role, ['staff', 'owner'], true)) {
    echo '<p>You do not have permission to view the management dashboard.</p>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_inventory') {
        if (!userHasRole('staff', $role)) {
            $dashboardError = 'Unauthorized action.';
        } else {
            $perfumeId = (int) ($_POST['perfume_id'] ?? 0);
            $availableQuantity = max(0, (int) ($_POST['available_quantity'] ?? 0));
            $damagedQuantity = max(0, (int) ($_POST['damaged_quantity'] ?? 0));
            $expirationDate = trim($_POST['expiration_date'] ?? '');

            // Check if staff has manage access to this perfume
            if (!checkInventoryAccess($pdo, $userId, $perfumeId, 'manage')) {
                $dashboardError = 'You do not have permission to manage this perfume inventory.';
            } else {
                try {
                    // Get current inventory values for audit log
                    $prevStmt = $pdo->prepare('SELECT id, available_quantity, damaged_quantity FROM inventory WHERE perfume_id = :perfume_id');
                    $prevStmt->execute([':perfume_id' => $perfumeId]);
                    $prevInventory = $prevStmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt = $pdo->prepare('INSERT INTO inventory (perfume_id, available_quantity, damaged_quantity, expiration_date, last_updated) VALUES (:perfume_id, :available_quantity, :damaged_quantity, :expiration_date, CURRENT_TIMESTAMP) ON CONFLICT(perfume_id) DO UPDATE SET available_quantity = excluded.available_quantity, damaged_quantity = excluded.damaged_quantity, expiration_date = excluded.expiration_date, last_updated = CURRENT_TIMESTAMP');
                    $stmt->execute([
                        ':perfume_id' => $perfumeId,
                        ':available_quantity' => $availableQuantity,
                        ':damaged_quantity' => $damagedQuantity,
                        ':expiration_date' => $expirationDate !== '' ? $expirationDate : null,
                    ]);
                    
                    // Log to audit table
                    if ($prevInventory) {
                        logInventoryChange($pdo, $prevInventory['id'], $userId, $prevInventory['available_quantity'], $availableQuantity, $prevInventory['damaged_quantity'], $damagedQuantity, 'Inventory update');
                    }
                    
                    $dashboardSuccess = 'Inventory updated successfully.';
                } catch (PDOException $e) {
                    $dashboardError = 'Failed to update inventory. Please try again.';
                }
            }
        }
    }

    if ($action === 'create_purchase_list') {
        if (!userHasRole('staff', $role)) {
            $dashboardError = 'Unauthorized action.';
        } else {
            $perfumeId = (int) ($_POST['purchase_perfume_id'] ?? 0);
            $quantity = max(1, (int) ($_POST['purchase_quantity'] ?? 1));

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('INSERT INTO purchase_lists (staff_id) VALUES (:staff_id)');
                $stmt->execute([':staff_id' => $userId]);
                $purchaseListId = (int) $pdo->lastInsertId();
                $stmt = $pdo->prepare('INSERT INTO purchase_list_items (purchase_list_id, perfume_id, quantity) VALUES (:purchase_list_id, :perfume_id, :quantity)');
                $stmt->execute([
                    ':purchase_list_id' => $purchaseListId,
                    ':perfume_id' => $perfumeId,
                    ':quantity' => $quantity,
                ]);
                $pdo->commit();
                $dashboardSuccess = 'Purchase list created successfully.';
            } catch (PDOException $e) {
                $pdo->rollBack();
                $dashboardError = 'Failed to create purchase list. Please try again.';
            }
        }
    }

    if ($action === 'approve_list' || $action === 'reject_list') {
        if (!userHasRole('owner', $role)) {
            $dashboardError = 'Unauthorized action.';
        } else {
            $listId = (int) ($_POST['purchase_list_id'] ?? 0);
            $status = $action === 'approve_list' ? 'approved' : 'rejected';
            $ownerNote = trim($_POST['owner_note'] ?? '');

            try {
                $stmt = $pdo->prepare('UPDATE purchase_lists SET status = :status, owner_note = :owner_note WHERE id = :id');
                $stmt->execute([
                    ':status' => $status,
                    ':owner_note' => $ownerNote,
                    ':id' => $listId,
                ]);
                $dashboardSuccess = $status === 'approved' ? 'Purchase list approved.' : 'Purchase list rejected.';
            } catch (PDOException $e) {
                $dashboardError = 'Failed to update purchase list status.';
            }
        }
    }
}

try {
    // ROLE-BASED DATA FETCHING
    if ($role === 'staff') {
        // Staff: Only see inventory for perfumes they have access to
        $inventoryStmt = $pdo->prepare('SELECT i.*, p.name AS perfume_name, ia.access_level FROM inventory i JOIN perfumes p ON p.id = i.perfume_id JOIN inventory_access ia ON ia.inventory_id = i.id WHERE ia.staff_id = :staff_id ORDER BY p.name');
        $inventoryStmt->execute([':staff_id' => $userId]);
        $inventoryItems = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Staff: Only see perfumes they can manage or view
        $perfumeStmt = $pdo->prepare('SELECT DISTINCT p.id, p.name FROM perfumes p JOIN inventory i ON i.perfume_id = p.id JOIN inventory_access ia ON ia.inventory_id = i.id WHERE ia.staff_id = :staff_id ORDER BY p.name');
        $perfumeStmt->execute([':staff_id' => $userId]);
        $perfumes = $perfumeStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Owner: See all inventory
        $inventoryStmt = $pdo->query('SELECT i.*, p.name AS perfume_name FROM inventory i JOIN perfumes p ON p.id = i.perfume_id ORDER BY p.name');
        $inventoryItems = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Owner: See all perfumes
        $perfumeStmt = $pdo->query('SELECT id, name FROM perfumes ORDER BY name');
        $perfumes = $perfumeStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($role === 'staff') {
        $purchaseStmt = $pdo->prepare('SELECT pl.*, u.username FROM purchase_lists pl JOIN users u ON pl.staff_id = u.id WHERE pl.staff_id = :user_id ORDER BY pl.created_at DESC');
        $purchaseStmt->execute([':user_id' => $userId]);
        $purchaseLists = $purchaseStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $purchaseStmt = $pdo->query('SELECT pl.*, u.username FROM purchase_lists pl JOIN users u ON pl.staff_id = u.id ORDER BY pl.created_at DESC');
        $purchaseLists = $purchaseStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $purchaseIds = array_column($purchaseLists, 'id');
    $purchaseItems = [];
    if (!empty($purchaseIds)) {
        $placeholders = implode(',', array_fill(0, count($purchaseIds), '?'));
        $itemsStmt = $pdo->prepare('SELECT pi.purchase_list_id, p.name AS perfume_name, pi.quantity FROM purchase_list_items pi JOIN perfumes p ON p.id = pi.perfume_id WHERE pi.purchase_list_id IN (' . $placeholders . ')');
        $itemsStmt->execute($purchaseIds);
        foreach ($itemsStmt->fetchAll(PDO::FETCH_ASSOC) as $item) {
            $purchaseItems[$item['purchase_list_id']][] = $item;
        }
    }
} catch (PDOException $e) {
    echo '<p>Unable to load dashboard data.</p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LabScentique Dashboard</title>
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <a href="index.php" class="brand" aria-label="LabScentique home">
        <img src="assets/logo.svg" alt="LabScentique logo" class="brand-logo" />
        <span>LabScentique</span>
      </a>
      <nav class="site-nav">
        <a href="index.php">Back to Site</a>
        <a href="dashboard.php">Dashboard</a>
      </nav>
      <div class="search-bar dashboard-user">
        <span>Welcome, <?php echo escape($user['username']); ?> (<?php echo escape($role); ?>)</span>
        <a href="index.php?logout=1">Logout</a>
      </div>
    </div>
  </header>

  <main class="container dashboard-page">
    <section class="dashboard-summary">
      <h1>Management Dashboard</h1>
      <p>Role: <?php echo escape(ucfirst($role)); ?></p>
      <?php if ($dashboardSuccess): ?>
        <div class="form-status success"><?php echo escape($dashboardSuccess); ?></div>
      <?php endif; ?>
      <?php if ($dashboardError): ?>
        <div class="form-status error"><?php echo escape($dashboardError); ?></div>
      <?php endif; ?>
    </section>

    <?php if ($role === 'staff'): ?>
      <section class="dashboard-panel">
        <h2>Inventory Management</h2>
        <form method="post" class="inventory-form">
          <input type="hidden" name="action" value="update_inventory" />
          <label>
            Perfume
            <select name="perfume_id" required>
              <?php foreach ($perfumes as $perfume): ?>
                <option value="<?php echo escape((string) $perfume['id']); ?>"><?php echo escape($perfume['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>
            Available quantity
            <input type="number" name="available_quantity" min="0" value="0" />
          </label>
          <label>
            Damaged quantity
            <input type="number" name="damaged_quantity" min="0" value="0" />
          </label>
          <label>
            Expiration date
            <input type="date" name="expiration_date" />
          </label>
          <button type="submit" class="button button-primary">Update Inventory</button>
        </form>
      </section>

      <section class="dashboard-panel">
        <h2>Create Purchase List</h2>
        <form method="post" class="inventory-form">
          <input type="hidden" name="action" value="create_purchase_list" />
          <label>
            Perfume
            <select name="purchase_perfume_id" required>
              <?php foreach ($perfumes as $perfume): ?>
                <option value="<?php echo escape((string) $perfume['id']); ?>"><?php echo escape($perfume['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </label>
          <label>
            Quantity needed
            <input type="number" name="purchase_quantity" min="1" value="1" required />
          </label>
          <button type="submit" class="button button-primary">Create Purchase List</button>
        </form>
      </section>
    <?php endif; ?>

    <?php if ($role === 'owner'): ?>
      <section class="dashboard-panel">
        <h2>Pending Purchase Lists</h2>
        <?php if (empty($purchaseLists)): ?>
          <p>No purchase lists yet.</p>
        <?php else: ?>
          <?php foreach ($purchaseLists as $list): ?>
            <div class="purchase-list-card">
              <h3>List #<?php echo escape((string) $list['id']); ?> - <?php echo escape($list['status']); ?></h3>
              <p>Created by <?php echo escape($list['username']); ?> on <?php echo escape($list['created_at']); ?></p>
              <ul>
                <?php foreach ($purchaseItems[$list['id']] ?? [] as $item): ?>
                  <li><?php echo escape($item['perfume_name']); ?> — <?php echo escape((string) $item['quantity']); ?></li>
                <?php endforeach; ?>
              </ul>
              <?php if ($list['status'] === 'pending'): ?>
                <form method="post" class="inventory-form">
                  <input type="hidden" name="action" value="approve_list" />
                  <input type="hidden" name="purchase_list_id" value="<?php echo escape((string) $list['id']); ?>" />
                  <label>
                    Note for staff
                    <textarea name="owner_note" rows="3"></textarea>
                  </label>
                  <button type="submit" class="button button-primary">Approve</button>
                </form>
                <form method="post" class="inventory-form">
                  <input type="hidden" name="action" value="reject_list" />
                  <input type="hidden" name="purchase_list_id" value="<?php echo escape((string) $list['id']); ?>" />
                  <button type="submit" class="button button-secondary">Reject</button>
                </form>
              <?php else: ?>
                <p>Owner note: <?php echo escape($list['owner_note'] ?? 'None'); ?></p>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>

      <section class="dashboard-panel">
        <h2>Staff Access Management</h2>
        <?php 
        try {
            $staffStmt = $pdo->query('SELECT id, username FROM users WHERE role = "staff" ORDER BY username');
            $staffMembers = $staffStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $staffMembers = [];
        }
        ?>
        <?php if (empty($staffMembers)): ?>
          <p>No staff members found.</p>
        <?php else: ?>
          <table class="access-table">
            <thead>
              <tr>
                <th>Staff Member</th>
                <th>Access Level</th>
                <th>Perfumes Accessible</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($staffMembers as $staff): ?>
                <?php
                $accessStmt = $pdo->prepare('SELECT COUNT(*) as cnt, GROUP_CONCAT(p.name) as perfumes FROM inventory_access ia JOIN inventory inv ON inv.id = ia.inventory_id JOIN perfumes p ON p.id = inv.perfume_id WHERE ia.staff_id = :staff_id');
                $accessStmt->execute([':staff_id' => $staff['id']]);
                $accessInfo = $accessStmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <tr>
                  <td><?php echo escape($staff['username']); ?></td>
                  <td>
                    <?php 
                    $levelStmt = $pdo->prepare('SELECT MAX(CASE WHEN access_level = "admin" THEN 3 WHEN access_level = "manage" THEN 2 WHEN access_level = "view" THEN 1 ELSE 0 END) as max_level FROM inventory_access WHERE staff_id = :staff_id');
                    $levelStmt->execute([':staff_id' => $staff['id']]);
                    $levelInfo = $levelStmt->fetch(PDO::FETCH_ASSOC);
                    $levelMap = [3 => 'Admin', 2 => 'Manage', 1 => 'View', 0 => 'None'];
                    echo escape($levelMap[$levelInfo['max_level'] ?? 0]);
                    ?>
                  </td>
                  <td><?php echo escape($accessInfo['perfumes'] ?? 'None'); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </section>

      <section class="dashboard-panel">
        <h2>Inventory Change Audit Log</h2>
        <?php 
        try {
            $auditStmt = $pdo->prepare('SELECT ia.*, u.username, p.name as perfume_name FROM inventory_audit ia JOIN users u ON u.id = ia.changed_by JOIN inventory inv ON inv.id = ia.inventory_id JOIN perfumes p ON p.id = inv.perfume_id ORDER BY ia.changed_at DESC LIMIT 50');
            $auditStmt->execute();
            $auditLogs = $auditStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $auditLogs = [];
        }
        ?>
        <?php if (empty($auditLogs)): ?>
          <p>No audit logs yet.</p>
        <?php else: ?>
          <table class="audit-table">
            <thead>
              <tr>
                <th>Perfume</th>
                <th>Changed By</th>
                <th>Previous Available</th>
                <th>New Available</th>
                <th>Previous Damaged</th>
                <th>New Damaged</th>
                <th>Reason</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($auditLogs as $log): ?>
                <tr>
                  <td><?php echo escape($log['perfume_name']); ?></td>
                  <td><?php echo escape($log['username']); ?></td>
                  <td><?php echo escape((string) $log['prev_available']); ?></td>
                  <td><?php echo escape((string) $log['new_available']); ?></td>
                  <td><?php echo escape((string) $log['prev_damaged']); ?></td>
                  <td><?php echo escape((string) $log['new_damaged']); ?></td>
                  <td><?php echo escape($log['change_reason'] ?? '—'); ?></td>
                  <td><?php echo escape($log['changed_at']); ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      </section>
    <?php endif; ?>

    <section class="dashboard-panel">
      <h2>Inventory Overview</h2>
      <?php if (empty($inventoryItems)): ?>
        <p>No inventory records available yet.</p>
      <?php else: ?>
        <table class="inventory-table">
          <thead>
            <tr>
              <th>Perfume</th>
              <th>Available</th>
              <th>Damaged</th>
              <th>Expiration</th>
              <th>Last Updated</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($inventoryItems as $item): ?>
              <tr>
                <td><?php echo escape($item['perfume_name']); ?></td>
                <td><?php echo escape((string) $item['available_quantity']); ?></td>
                <td><?php echo escape((string) $item['damaged_quantity']); ?></td>
                <td><?php echo escape($item['expiration_date'] ?? '—'); ?></td>
                <td><?php echo escape($item['last_updated']); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
