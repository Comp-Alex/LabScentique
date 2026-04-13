<?php
declare(strict_types=1);
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
} catch (PDOException $e) {
    echo '<p>Unable to load user data.</p>';
    exit;
}

if (!in_array($role, ['staff', 'owner'], true)) {
    echo '<p>You do not have permission to view the management dashboard.</p>';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($role === 'staff' && $action === 'update_inventory') {
        $perfumeId = (int) ($_POST['perfume_id'] ?? 0);
        $availableQuantity = max(0, (int) ($_POST['available_quantity'] ?? 0));
        $damagedQuantity = max(0, (int) ($_POST['damaged_quantity'] ?? 0));
        $expirationDate = trim($_POST['expiration_date'] ?? '');

        try {
            $stmt = $pdo->prepare('INSERT INTO inventory (perfume_id, available_quantity, damaged_quantity, expiration_date) VALUES (:perfume_id, :available_quantity, :damaged_quantity, :expiration_date) ON DUPLICATE KEY UPDATE available_quantity = VALUES(available_quantity), damaged_quantity = VALUES(damaged_quantity), expiration_date = VALUES(expiration_date), last_updated = CURRENT_TIMESTAMP');
            $stmt->execute([
                ':perfume_id' => $perfumeId,
                ':available_quantity' => $availableQuantity,
                ':damaged_quantity' => $damagedQuantity,
                ':expiration_date' => $expirationDate !== '' ? $expirationDate : null,
            ]);
            $dashboardSuccess = 'Inventory updated successfully.';
        } catch (PDOException $e) {
            $dashboardError = 'Failed to update inventory. Please try again.';
        }
    }

    if ($role === 'staff' && $action === 'create_purchase_list') {
        $perfumeId = (int) ($_POST['purchase_perfume_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['purchase_quantity'] ?? 1));

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO purchase_lists (created_by) VALUES (:created_by)');
            $stmt->execute([':created_by' => $userId]);
            $purchaseListId = (int) $pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO purchase_items (purchase_list_id, perfume_id, quantity) VALUES (:purchase_list_id, :perfume_id, :quantity)');
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

    if ($role === 'owner' && ($action === 'approve_list' || $action === 'reject_list')) {
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

function escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

try {
    $inventoryStmt = $pdo->query('SELECT i.*, p.name AS perfume_name FROM inventory i JOIN perfumes p ON p.id = i.perfume_id ORDER BY p.name');
    $inventoryItems = $inventoryStmt->fetchAll(PDO::FETCH_ASSOC);

    $perfumeStmt = $pdo->query('SELECT id, name FROM perfumes ORDER BY name');
    $perfumes = $perfumeStmt->fetchAll(PDO::FETCH_ASSOC);

    if ($role === 'staff') {
        $purchaseStmt = $pdo->prepare('SELECT pl.*, u.username FROM purchase_lists pl JOIN users u ON pl.created_by = u.id WHERE pl.created_by = :user_id ORDER BY pl.created_at DESC');
        $purchaseStmt->execute([':user_id' => $userId]);
        $purchaseLists = $purchaseStmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $purchaseStmt = $pdo->query('SELECT pl.*, u.username FROM purchase_lists pl JOIN users u ON pl.created_by = u.id ORDER BY pl.created_at DESC');
        $purchaseLists = $purchaseStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    $purchaseIds = array_column($purchaseLists, 'id');
    $purchaseItems = [];
    if (!empty($purchaseIds)) {
        $in = implode(',', array_map('intval', $purchaseIds));
        $itemsStmt = $pdo->query('SELECT pi.purchase_list_id, p.name AS perfume_name, pi.quantity FROM purchase_items pi JOIN perfumes p ON p.id = pi.perfume_id WHERE pi.purchase_list_id IN (' . $in . ')');
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
