<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$admin = currentAdmin();
$db = getDB();
$flash = getFlash();
$error = '';

// Add City
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = sanitize($_POST['name'] ?? '');
    $status_label = sanitize($_POST['status_label'] ?? 'Active Hub');
    $centers_count = intval($_POST['centers_count'] ?? 3);
    $description = sanitize($_POST['description'] ?? '');
    $eta_info = sanitize($_POST['eta_info'] ?? '< 45 mins');

    if (empty($name)) {
        $error = 'City name is required.';
    } else {
        $check = $db->prepare("SELECT id FROM cities WHERE name = ? LIMIT 1");
        $check->execute([$name]);
        if ($check->fetch()) {
            $error = 'City already exists in registry.';
        } else {
            $stmt = $db->prepare("INSERT INTO cities (name, status_label, centers_count, description, eta_info, is_active) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$name, $status_label, $centers_count, $description, $eta_info]);
            setFlash('success', "City {$name} added successfully.");
            header('Location: cities.php');
            exit;
        }
    }
}

// Toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $cityId = intval($_POST['city_id']);
    $newActive = intval($_POST['is_active']) ? 0 : 1;
    $upd = $db->prepare("UPDATE cities SET is_active = ? WHERE id = ?");
    $upd->execute([$newActive, $cityId]);
    setFlash('info', 'City status updated.');
    header('Location: cities.php');
    exit;
}

$citiesStmt = $db->query("SELECT * FROM cities ORDER BY id ASC");
$cities = $citiesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Cities | Food Bridge Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="css/admin-dashboard.css">
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
        <a href="requests.php" class="nav-item"><i class="fa-solid fa-bowl-food"></i> Food Requests</a>
        <a href="organizations.php" class="nav-item"><i class="fa-solid fa-building-columns"></i> Organizations</a>
        <a href="cities.php" class="nav-item active"><i class="fa-solid fa-city"></i> Cities</a>
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
          <span class="page-kicker">Geographic Coverage</span>
          <h1>Manage Municipal Service Cities</h1>
        </div>
        <div class="topbar-actions">
          <span class="status-pill"><i class="fa-solid fa-city"></i> Total: <?php echo count($cities); ?> Cities</span>
        </div>
      </header>

      <?php if ($flash): ?>
        <div style="background: #def7ec; color: #03543f; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #bcf0da;">
          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($error)): ?>
        <div style="background: #fde8e8; color: #9b1c1c; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #f8b4b4;">
          <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <!-- Add City Form -->
      <section style="background: white; padding: 20px; border-radius: 14px; margin-bottom: 24px; border: 1px solid rgba(20,40,34,0.08);">
        <h3 style="margin-top:0; margin-bottom: 14px; font-size: 1.1rem; color: #172a25;"><i class="fa-solid fa-plus-circle" style="color: #1f8c5a;"></i> Add New Service City in Gujarat</h3>
        <form method="POST" action="cities.php" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px;">
          <input type="hidden" name="action" value="add">
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;">City Name *</label>
            <input type="text" name="name" placeholder="e.g. Jamnagar" required style="width:100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;">Status Label</label>
            <input type="text" name="status_label" value="Active Hub" style="width:100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;">Estimated Pickup Time</label>
            <input type="text" name="eta_info" value="< 45 mins" style="width:100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
          <div style="display:flex; align-items:flex-end;">
            <button type="submit" style="width:100%; padding: 10px; background: #1f8c5a; color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;"><i class="fa-solid fa-plus"></i> Add City</button>
          </div>
        </form>
      </section>

      <section class="activity-panel">
        <div class="activity-table-wrap">
          <table class="activity-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>City Name</th>
                <th>Status Label</th>
                <th>Verified Centers</th>
                <th>ETA Info</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($cities as $city): ?>
                <tr>
                  <td><?php echo $city['id']; ?></td>
                  <td><strong><?php echo htmlspecialchars($city['name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($city['status_label']); ?></td>
                  <td><?php echo htmlspecialchars($city['centers_count']); ?> Centers</td>
                  <td><?php echo htmlspecialchars($city['eta_info']); ?></td>
                  <td><span class="table-status <?php echo $city['is_active'] ? 'completed' : 'pending'; ?>"><?php echo $city['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                  <td>
                    <form method="POST" action="cities.php" style="display:inline;">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="city_id" value="<?php echo $city['id']; ?>">
                      <input type="hidden" name="is_active" value="<?php echo $city['is_active']; ?>">
                      <button type="submit" style="padding: 5px 10px; font-size: 0.78rem; border-radius: 4px; border: 1px solid #d1d5db; cursor: pointer; background: white;">
                        <?php echo $city['is_active'] ? 'Deactivate' : 'Activate'; ?>
                      </button>
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

