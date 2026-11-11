<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

requireAdminLogin();

$admin = currentAdmin();
$db = getDB();

// Statistics
$totalUsers = intval($db->query("SELECT COUNT(*) FROM users")->fetchColumn());
$totalDonations = intval($db->query("SELECT COUNT(*) FROM donations")->fetchColumn());
$pendingDonations = intval($db->query("SELECT COUNT(*) FROM donations WHERE status = 'Pending'")->fetchColumn());
$totalRequests = intval($db->query("SELECT COUNT(*) FROM food_requests")->fetchColumn());

// Recent Donations
$recentDonationsStmt = $db->query("SELECT d.id, d.donation_code, d.food_name, d.status, d.created_at, u.name as user_name, c.name as city_name
                                   FROM donations d
                                   LEFT JOIN users u ON d.user_id = u.id
                                   LEFT JOIN cities c ON d.city_id = c.id
                                   ORDER BY d.id DESC LIMIT 5");
$recentDonations = $recentDonationsStmt->fetchAll();

// Recent Requests
$recentRequestsStmt = $db->query("SELECT r.id, r.request_code, r.food_type, r.status, r.created_at, r.city, u.name as user_name
                                  FROM food_requests r
                                  LEFT JOIN users u ON r.user_id = u.id
                                  ORDER BY r.id DESC LIMIT 5");
$recentRequests = $recentRequestsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Food Bridge admin dashboard overview and activity tracking.">
  <title>Admin Dashboard | Food Bridge</title>
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
        <a href="dashboard.php" class="nav-item active"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        <a href="users.php" class="nav-item"><i class="fa-solid fa-user-group"></i> Users</a>
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

    <main class="admin-main-panel">
      <header class="topbar">
        <div class="topbar-title-wrap">
          <span class="page-kicker">Portal Overview</span>
          <h1>Admin Dashboard</h1>
        </div>
        <div class="topbar-actions">
          <button type="button" class="date-pill"><i class="fa-regular fa-calendar"></i> <?php echo date('M d, Y'); ?></button>
          <span class="status-pill"><i class="fa-solid fa-circle"></i> MySQL Database Connected</span>
        </div>
      </header>

      <section class="stats-grid" aria-label="Dashboard summary">
        <article class="stat-card">
          <div class="stat-topline">
            <span>Total Users</span>
            <span class="stat-icon green"><i class="fa-solid fa-user-group"></i></span>
          </div>
          <div class="stat-number"><?php echo $totalUsers; ?></div>
          <small>Registered community users</small>
        </article>

        <article class="stat-card">
          <div class="stat-topline">
            <span>Total Donations</span>
            <span class="stat-icon emerald"><i class="fa-solid fa-box-open"></i></span>
          </div>
          <div class="stat-number"><?php echo $totalDonations; ?></div>
          <small>All-time surplus food donated</small>
        </article>

        <article class="stat-card warning">
          <div class="stat-topline">
            <span>Pending Donations</span>
            <span class="stat-icon orange"><i class="fa-solid fa-clock"></i></span>
          </div>
          <div class="stat-number"><?php echo $pendingDonations; ?></div>
          <small>Requires review or partner pickup</small>
        </article>

        <article class="stat-card info">
          <div class="stat-topline">
            <span>Food Requests</span>
            <span class="stat-icon blue"><i class="fa-solid fa-list-check"></i></span>
          </div>
          <div class="stat-number"><?php echo $totalRequests; ?></div>
          <small>Active community requests</small>
        </article>
      </section>

      <section class="quick-actions" aria-label="Quick actions">
        <h2>Quick Actions</h2>
        <div class="quick-grid">
          <a href="users.php" class="action-card green">
            <span class="action-icon"><i class="fa-solid fa-user-group"></i></span>
            <strong>Manage Users</strong>
            <small>View &amp; update registered users</small>
          </a>

          <a href="donations.php" class="action-card emerald">
            <span class="action-icon"><i class="fa-solid fa-box-open"></i></span>
            <strong>Manage Donations</strong>
            <small>Review &amp; update statuses</small>
          </a>

          <a href="requests.php" class="action-card blue">
            <span class="action-icon"><i class="fa-solid fa-bowl-food"></i></span>
            <strong>Manage Food Requests</strong>
            <small>Review community requests</small>
          </a>

          <a href="organizations.php" class="action-card orange">
            <span class="action-icon"><i class="fa-solid fa-building-columns"></i></span>
            <strong>Organizations</strong>
            <small>NGOs &amp; relief centers</small>
          </a>
        </div>
      </section>

      <section class="activity-panel" aria-label="Recent activity">
        <div class="activity-header">
          <div>
            <h2>Recent Activity</h2>
            <p>Latest donation listings and community food requests from MySQL</p>
          </div>
          <span class="total-listed">ACTIVE MONITORING</span>
        </div>

        <div class="activity-table-wrap">
          <table class="activity-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Type</th>
                <th>User</th>
                <th>Details</th>
                <th>City</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentDonations as $don):
                $statusClass = strtolower($don['status']);
              ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($don['donation_code']); ?></strong></td>
                  <td><span class="table-tag donation">Donation</span></td>
                  <td><?php echo htmlspecialchars($don['user_name'] ?? 'Community Member'); ?></td>
                  <td><?php echo htmlspecialchars($don['food_name']); ?></td>
                  <td><?php echo htmlspecialchars($don['city_name'] ?? 'Rajkot'); ?></td>
                  <td><span class="table-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($don['status']); ?></span></td>
                </tr>
              <?php endforeach; ?>

              <?php foreach ($recentRequests as $req):
                $statusClass = strtolower($req['status']);
              ?>
                <tr>
                  <td><strong><?php echo htmlspecialchars($req['request_code']); ?></strong></td>
                  <td><span class="table-tag request">Food Request</span></td>
                  <td><?php echo htmlspecialchars($req['user_name'] ?? 'Community Member'); ?></td>
                  <td><?php echo htmlspecialchars($req['food_type']); ?></td>
                  <td><?php echo htmlspecialchars($req['city']); ?></td>
                  <td><span class="table-status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($req['status']); ?></span></td>
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
