<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$admin = currentAdmin();
$db = getDB();
$flash = getFlash();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'])) {
    $reqId = intval($_POST['request_id']);
    $newStatus = sanitize($_POST['status'] ?? '');
    if (in_array($newStatus, ['Pending', 'Accepted', 'Completed', 'Cancelled'])) {
        $upd = $db->prepare("UPDATE food_requests SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $reqId]);
        setFlash('success', "Food request status updated to {$newStatus}.");
        header('Location: requests.php');
        exit;
    }
}

$statusFilter = sanitize($_GET['status'] ?? '');
$query = "SELECT r.*, u.name as user_name, u.phone as user_phone, o.name as org_name
          FROM food_requests r
          LEFT JOIN users u ON r.user_id = u.id
          LEFT JOIN organizations o ON r.assigned_org_id = o.id";
$params = [];
if (!empty($statusFilter)) {
    $query .= " WHERE r.status = ?";
    $params[] = $statusFilter;
}
$query .= " ORDER BY r.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Food Requests | Food Bridge Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="css/admin-dashboard.css">
  <style>
    .filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; }
    .filter-tab { padding: 8px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; color: #4b5563; background: #e5e7eb; font-size: 0.85rem; }
    .filter-tab.active { background: #1f8c5a; color: white; }
    .status-select { padding: 6px 10px; border-radius: 6px; border: 1px solid #d1d5db; font-size: 0.82rem; font-weight: 600; }
  </style>
</head>
<body class="admin-dashboard-page">
  <div class="admin-app-shell">
    <aside class="admin-sidebar">
      <div class="brand-row">
        <a class="admin-brand" href="../index.php" aria-label="Food Bridge home">
          <span class="admin-brand-mark"><i class="fa-solid fa-seedling"></i></span>
          <span class="admin-brand-copy">
            <strong>Food Bridge</strong>
          </span>
        </a>
        <span class="admin-sidebar-tag">ADMIN</span>
      </div>

      <nav class="admin-nav" aria-label="Admin navigation">
        <a href="dashboard.php" class="nav-item"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="users.php" class="nav-item"><i class="fa-solid fa-user-group"></i> Users</a>
        <a href="donations.php" class="nav-item"><i class="fa-solid fa-box-open"></i> Donations</a>
        <a href="requests.php" class="nav-item active"><i class="fa-solid fa-bowl-food"></i> Food Requests</a>
        <a href="organizations.php" class="nav-item"><i class="fa-solid fa-building-columns"></i> Organizations</a>
        <a href="cities.php" class="nav-item"><i class="fa-solid fa-city"></i> Cities</a>
      </nav>

      <div class="admin-user-box">
        <div class="admin-user-mini">
          <span class="mini-avatar"><i class="fa-solid fa-user-shield"></i></span>
          <div>
            <strong><?php echo htmlspecialchars($admin['name'] ?? 'System Admin'); ?></strong>
            <small><?php echo htmlspecialchars($admin['email'] ?? 'admin@foodbridge.org'); ?></small>
          </div>
        </div>
        <a href="logout.php" class="logout-link"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
      </div>
    </aside>

    <main class="admin-main-panel">
      <header class="topbar">
        <div class="topbar-title-wrap">
          <span class="page-kicker">Relief Coordination</span>
          <h1>Manage Food Requests</h1>
        </div>
        <div class="topbar-actions">
          <span class="status-pill"><i class="fa-solid fa-bowl-food"></i> Total: <?php echo count($requests); ?></span>
        </div>
      </header>

      <?php if ($flash): ?>
        <div style="background: #def7ec; color: #03543f; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #bcf0da;">
          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <div class="filter-tabs">
        <a href="requests.php" class="filter-tab <?php echo empty($statusFilter) ? 'active' : ''; ?>">All</a>
        <a href="requests.php?status=Pending" class="filter-tab <?php echo $statusFilter === 'Pending' ? 'active' : ''; ?>">Pending</a>
        <a href="requests.php?status=Accepted" class="filter-tab <?php echo $statusFilter === 'Accepted' ? 'active' : ''; ?>">Accepted</a>
        <a href="requests.php?status=Completed" class="filter-tab <?php echo $statusFilter === 'Completed' ? 'active' : ''; ?>">Completed</a>
        <a href="requests.php?status=Cancelled" class="filter-tab <?php echo $statusFilter === 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
      </div>

      <section class="activity-panel">
        <div class="activity-table-wrap">
          <table class="activity-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Requester</th>
                <th>Food Type</th>
                <th>People</th>
                <th>City</th>
                <th>Required Date</th>
                <th>Status</th>
                <th>Update Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($requests)): ?>
                <tr><td colspan="8" style="text-align:center; padding: 24px;">No food requests found for this filter.</td></tr>
              <?php endif; ?>
              <?php foreach ($requests as $req): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($req['request_code']); ?></strong></td>
                  <td><?php echo htmlspecialchars($req['user_name'] ?? 'Beneficiary'); ?><br><small style="color:#6b7280;"><?php echo htmlspecialchars($req['user_phone'] ?? ''); ?></small></td>
                  <td><strong><?php echo htmlspecialchars($req['food_type']); ?></strong><br><small style="color:#6b7280;"><?php echo htmlspecialchars($req['dietary_category'] ?? 'Vegetarian'); ?></small></td>
                  <td><strong style="color: #10b981;"><?php echo htmlspecialchars($req['people_count']); ?> People</strong></td>
                  <td><?php echo htmlspecialchars($req['city']); ?></td>
                  <td><?php echo date('d M Y', strtotime($req['required_date'])); ?></td>
                  <td><span class="table-status <?php echo strtolower($req['status']); ?>"><?php echo htmlspecialchars($req['status']); ?></span></td>
                  <td>
                    <form method="POST" action="requests.php" style="display:flex; gap: 6px; align-items:center;">
                      <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>" />
                      <select name="status" class="status-select">
                        <option value="Pending" <?php echo $req['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Accepted" <?php echo $req['status'] === 'Accepted' ? 'selected' : ''; ?>>Accepted</option>
                        <option value="Completed" <?php echo $req['status'] === 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Cancelled" <?php echo $req['status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                      </select>
                      <button type="submit" style="padding: 6px 10px; background: #1f8c5a; color: white; border: none; border-radius: 6px; cursor: pointer;"><i class="fa-solid fa-check"></i></button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </section>

      <footer style="margin-top: 35px; padding-top: 18px; border-top: 1px solid rgba(19, 36, 29, 0.08); display: flex; justify-content: space-between; align-items: center; color: #64736f; font-size: 12px;">
        <span>© <?php echo date('Y'); ?> Food Bridge Admin Portal. All rights reserved. • Built by <strong>Team EtherX</strong></span>
        <span>Secure Operations Console • Gujarat</span>
      </footer>
    </main>
  </div>
</body>
</html>

