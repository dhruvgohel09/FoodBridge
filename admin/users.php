<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$admin = currentAdmin();
$db = getDB();

$flash = getFlash();

// Handle status change / deactivation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $targetUserId = intval($_POST['user_id']);
    $newStatus = sanitize($_POST['status'] ?? 'Active');
    if (in_array($newStatus, ['Active', 'Inactive', 'Blocked'])) {
        $upd = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
        $upd->execute([$newStatus, $targetUserId]);
        setFlash('success', "User status updated to {$newStatus}.");
        header('Location: users.php?selected=' . $targetUserId);
        exit;
    }
}

// Fetch all users
$usersStmt = $db->query("SELECT * FROM users ORDER BY id ASC");
$users = $usersStmt->fetchAll();
$totalUsers = count($users);

// Selected user
$selectedId = intval($_GET['selected'] ?? ($users[0]['id'] ?? 1));
$selectedUser = null;
foreach ($users as $u) {
    if ($u['id'] == $selectedId) {
        $selectedUser = $u;
        break;
    }
}
if (!$selectedUser && !empty($users)) {
    $selectedUser = $users[0];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage registered Food Bridge users from the admin portal.">
  <title>Manage Users | Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="css/admin-dashboard.css">
  <link rel="stylesheet" href="css/admin-users.css">
  <style>
    .users-table tbody tr { cursor: pointer; }
    .users-table tbody tr:hover { background: #f0fdf4; }
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
        <a href="users.php" class="nav-item active"><i class="fa-solid fa-user-group"></i> Users</a>
        <a href="donations.php" class="nav-item"><i class="fa-solid fa-box-open"></i> Donations</a>
        <a href="requests.php" class="nav-item"><i class="fa-solid fa-bowl-food"></i> Food Requests</a>
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

    <main class="admin-main-panel users-panel">
      <header class="topbar users-topbar">
        <div class="topbar-title-wrap">
          <span class="page-kicker">Registry</span>
          <h1>Manage Users</h1>
        </div>
        <div class="users-summary-card">
          <span class="summary-icon"><i class="fa-solid fa-users"></i></span>
          <div class="summary-copy">
            <small>Total Registered</small>
            <strong><?php echo $totalUsers; ?> Users</strong>
          </div>
        </div>
      </header>

      <?php if ($flash): ?>
        <div style="background: #def7ec; color: #03543f; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #bcf0da;">
          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <section class="users-content">
        <div class="users-table-block">
          <div class="section-header">
            <h2><i class="fa-solid fa-table-list"></i> All User Records</h2>
            <span>Showing <?php echo $totalUsers; ?> community users</span>
          </div>

          <div class="users-table-wrap">
            <table class="users-table">
              <thead>
                <tr>
                  <th>User ID</th>
                  <th>Full Name</th>
                  <th>Email</th>
                  <th>Mobile</th>
                  <th>City</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($users as $u):
                  $isSel = ($selectedUser && $selectedUser['id'] == $u['id']);
                  $userUid = 'USR-' . (100 + $u['id']);
                ?>
                  <tr class="<?php echo $isSel ? 'selected' : ''; ?>" onclick="window.location.href='users.php?selected=<?php echo $u['id']; ?>'">
                    <td><?php echo $userUid; ?></td>
                    <td><span class="name-cell"><?php echo htmlspecialchars($u['name']); ?></span></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td><?php echo htmlspecialchars($u['phone']); ?></td>
                    <td><?php echo htmlspecialchars($u['city']); ?></td>
                    <td><span class="status-pill <?php echo $u['status'] === 'Active' ? 'green' : 'orange'; ?>"><?php echo htmlspecialchars($u['status']); ?></span></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <?php if ($selectedUser):
          $selUid = 'USR-' . (100 + $selectedUser['id']);
        ?>
          <aside class="user-details-card">
            <div class="details-header">
              <div>
                <small>User Details</small>
                <h3><?php echo $selUid; ?></h3>
              </div>
              <span class="status-dot"></span>
            </div>

            <div class="detail-fields">
              <div class="detail-row">
                <label>User ID</label>
                <span><?php echo $selUid; ?></span>
              </div>
              <div class="detail-row">
                <label>City</label>
                <span><?php echo htmlspecialchars($selectedUser['city']); ?></span>
              </div>
              <div class="detail-row">
                <label>Full Name</label>
                <span><?php echo htmlspecialchars($selectedUser['name']); ?></span>
              </div>
              <div class="detail-row">
                <label>Email</label>
                <span><?php echo htmlspecialchars($selectedUser['email']); ?></span>
              </div>
              <div class="detail-row">
                <label>Mobile</label>
                <span><?php echo htmlspecialchars($selectedUser['phone']); ?></span>
              </div>
              <div class="detail-row account-row">
                <label>Account Status</label>
                <span class="account-tag"><?php echo htmlspecialchars($selectedUser['status']); ?></span>
              </div>
              <div class="detail-row">
                <label>Joined On</label>
                <span><?php echo date('d M Y', strtotime($selectedUser['created_at'])); ?></span>
              </div>
            </div>

            <div class="detail-actions">
              <form method="POST" action="users.php?selected=<?php echo $selectedUser['id']; ?>" style="display:contents;">
                <input type="hidden" name="user_id" value="<?php echo $selectedUser['id']; ?>" />
                <?php if ($selectedUser['status'] === 'Active'): ?>
                  <input type="hidden" name="status" value="Inactive" />
                  <button type="submit" class="secondary-action"><i class="fa-solid fa-user-slash"></i> Deactivate Account</button>
                <?php else: ?>
                  <input type="hidden" name="status" value="Active" />
                  <button type="submit" class="primary-action"><i class="fa-solid fa-user-check"></i> Activate Account</button>
                <?php endif; ?>
              </form>
            </div>
          </aside>
        <?php endif; ?>
      </section>

      <footer style="margin-top: 35px; padding-top: 18px; border-top: 1px solid rgba(19, 36, 29, 0.08); display: flex; justify-content: space-between; align-items: center; color: #64736f; font-size: 12px;">
        <span>© <?php echo date('Y'); ?> Food Bridge Admin Portal. All rights reserved. • Built by <strong>Team EtherX</strong></span>
        <span>Secure Operations Console • Gujarat</span>
      </footer>
    </main>
  </div>
</body>
</html>
