<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$admin = currentAdmin();
$db = getDB();
$flash = getFlash();
$error = '';

// Add Organization
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = sanitize($_POST['name'] ?? '');
    $city_id = intval($_POST['city_id'] ?? 1);
    $short_code = sanitize($_POST['short_code'] ?? 'ORG');
    $address = sanitize($_POST['address'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $accepted_items = sanitize($_POST['accepted_items'] ?? 'Cooked Food, Packaged Food');
    $description = sanitize($_POST['description'] ?? '');

    if (empty($name) || empty($address)) {
        $error = 'Organization name and address are required.';
    } else {
        $stmt = $db->prepare("INSERT INTO organizations (city_id, name, short_code, address, phone, accepted_items, description, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$city_id, $name, $short_code, $address, $phone, $accepted_items, $description]);
        setFlash('success', "Organization {$name} added successfully.");
        header('Location: organizations.php');
        exit;
    }
}

// Toggle status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle') {
    $orgId = intval($_POST['org_id']);
    $newActive = intval($_POST['is_active']) ? 0 : 1;
    $upd = $db->prepare("UPDATE organizations SET is_active = ? WHERE id = ?");
    $upd->execute([$newActive, $orgId]);
    setFlash('info', 'Organization status updated.');
    header('Location: organizations.php');
    exit;
}

$orgsStmt = $db->query("SELECT o.*, c.name as city_name FROM organizations o LEFT JOIN cities c ON o.city_id = c.id ORDER BY o.id ASC");
$organizations = $orgsStmt->fetchAll();

$citiesStmt = $db->query("SELECT id, name FROM cities WHERE is_active = 1 ORDER BY name ASC");
$cities = $citiesStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Organizations | Food Bridge Admin</title>
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
        <a href="organizations.php" class="nav-item active"><i class="fa-solid fa-building-columns"></i> Organizations</a>
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
          <span class="page-kicker">Partner Network</span>
          <h1>Manage Relief Organizations</h1>
        </div>
        <div class="topbar-actions">
          <span class="status-pill"><i class="fa-solid fa-building-columns"></i> Total: <?php echo count($organizations); ?></span>
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

      <!-- Add Organization Form -->
      <section style="background: white; padding: 20px; border-radius: 14px; margin-bottom: 24px; border: 1px solid rgba(20,40,34,0.08);">
        <h3 style="margin-top:0; margin-bottom: 14px; font-size: 1.1rem; color: #172a25;"><i class="fa-solid fa-plus-circle" style="color: #1f8c5a;"></i> Add New Partner Organization</h3>
        <form method="POST" action="organizations.php" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px;">
          <input type="hidden" name="action" value="add">
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;">Organization Name *</label>
            <input type="text" name="name" placeholder="e.g. Surat Roti Bank" required style="width:100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;">City *</label>
            <select name="city_id" required style="width:100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
              <?php foreach ($cities as $c): ?>
                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;">Short Code</label>
            <input type="text" name="short_code" placeholder="e.g. SRB" maxlength="6" style="width:100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;">Address *</label>
            <input type="text" name="address" placeholder="e.g. Ring Road, Surat" required style="width:100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;">Hotline Phone</label>
            <input type="text" name="phone" placeholder="+91 98250-XXXXX" style="width:100%; padding: 8px 12px; border: 1px solid #d1d5db; border-radius: 6px;">
          </div>
          <div style="display:flex; align-items:flex-end;">
            <button type="submit" style="width:100%; padding: 10px; background: #1f8c5a; color: white; border: none; border-radius: 6px; font-weight: 700; cursor: pointer;"><i class="fa-solid fa-plus"></i> Save Organization</button>
          </div>
        </form>
      </section>

      <section class="activity-panel">
        <div class="activity-table-wrap">
          <table class="activity-table">
            <thead>
              <tr>
                <th>Code</th>
                <th>Name</th>
                <th>City</th>
                <th>Address</th>
                <th>Hotline Phone</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($organizations as $org): ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($org['short_code']); ?></strong></td>
                  <td><strong><?php echo htmlspecialchars($org['name']); ?></strong></td>
                  <td><?php echo htmlspecialchars($org['city_name'] ?? 'Gujarat'); ?></td>
                  <td><?php echo htmlspecialchars($org['address']); ?></td>
                  <td><?php echo htmlspecialchars($org['phone']); ?></td>
                  <td><span class="table-status <?php echo $org['is_active'] ? 'completed' : 'pending'; ?>"><?php echo $org['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
                  <td>
                    <form method="POST" action="organizations.php" style="display:inline;">
                      <input type="hidden" name="action" value="toggle">
                      <input type="hidden" name="org_id" value="<?php echo $org['id']; ?>">
                      <input type="hidden" name="is_active" value="<?php echo $org['is_active']; ?>">
                      <button type="submit" style="padding: 5px 10px; font-size: 0.78rem; border-radius: 4px; border: 1px solid #d1d5db; cursor: pointer; background: white;">
                        <?php echo $org['is_active'] ? 'Deactivate' : 'Activate'; ?>
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

