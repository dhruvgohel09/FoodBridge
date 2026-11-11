<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$db = getDB();

$flash = getFlash();

// Search and filter parameters
$search = sanitize($_GET['search'] ?? '');
$statusFilter = sanitize($_GET['status'] ?? '');
$cityFilter = sanitize($_GET['city'] ?? '');

$query = "SELECT d.*, c.name as city_name, o.name as org_name, o.short_code as org_code
          FROM donations d
          LEFT JOIN cities c ON d.city_id = c.id
          LEFT JOIN organizations o ON d.organization_id = o.id
          WHERE d.user_id = ?";
$params = [$user['id']];

if (!empty($search)) {
    $query .= " AND (d.food_name LIKE ? OR d.donation_code LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if (!empty($statusFilter) && in_array($statusFilter, ['Pending', 'Accepted', 'Completed', 'Cancelled'])) {
    $query .= " AND d.status = ?";
    $params[] = $statusFilter;
}

if (!empty($cityFilter) && $cityFilter !== 'All Cities') {
    $query .= " AND c.name = ?";
    $params[] = $cityFilter;
}

$query .= " ORDER BY d.id DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$donations = $stmt->fetchAll();

// Calculate total metrics for user
$statStmt = $db->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_cnt,
    SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) as accepted_cnt,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_cnt,
    SUM(serves) as total_servings
    FROM donations WHERE user_id = ?");
$statStmt->execute([$user['id']]);
$stats = $statStmt->fetch() ?: ['total' => 0, 'pending_cnt' => 0, 'accepted_cnt' => 0, 'completed_cnt' => 0, 'total_servings' => 0];

// Fetch distinct cities for dropdown
$citiesListStmt = $db->query("SELECT name FROM cities ORDER BY name ASC");
$citiesList = $citiesListStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Review and track your Food Bridge donations.">
  <title>My Donations | Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/app-header.css">
  <link rel="stylesheet" href="css/my-donations.css">
</head>
<body class="donations-page">
  <?php $activeHeaderPage = 'donations'; include __DIR__ . '/includes/app-header.php'; ?>
  <main class="donations-main">
    <section class="donations-heading">
      <div>
        <span class="donations-eyebrow">CONTRIBUTOR DASHBOARD</span>
        <h1>My Donations</h1>
        <p>View, audit, and trace surplus food contributions registered under your verified account.<br class="desktop-break"> Real-time logging maintains community accountability.</p>
      </div>
      <a class="donate-cta" href="donate-food.php"><i class="fa-solid fa-circle-plus" aria-hidden="true"></i> Donate Food</a>
    </section>

    <?php if ($flash): ?>
      <div style="background: #def7ec; color: #03543f; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; border: 1px solid #bcf0da;">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash['message']); ?>
      </div>
    <?php endif; ?>

    <section class="donation-stats" aria-label="Donation summary">
      <article class="donation-stat">
        <div class="stat-main">
          <span>Total Donations</span>
          <strong><?php echo intval($stats['total']); ?></strong>
          <small>entries logged</small>
          <i class="fa-solid fa-utensils stat-icon" aria-hidden="true"></i>
        </div>
        <div class="stat-foot"><span>All submitted donations</span><strong>100% Verified</strong></div>
      </article>
      <article class="donation-stat pending">
        <div class="stat-main">
          <span>Pending Intake</span>
          <strong><?php echo intval($stats['pending_cnt']); ?></strong>
          <small>requires action</small>
          <i class="fa-regular fa-clock stat-icon" aria-hidden="true"></i>
        </div>
        <div class="stat-foot"><span>Awaiting local partner pickup</span></div>
      </article>
      <article class="donation-stat completed">
        <div class="stat-main">
          <span>Completed &amp; Distributed</span>
          <strong><?php echo intval($stats['completed_cnt']); ?></strong>
          <small>rescued batches</small>
          <i class="fa-regular fa-circle-check stat-icon" aria-hidden="true"></i>
        </div>
        <div class="stat-foot"><span><?php echo intval($stats['accepted_cnt']); ?> Accepted · <?php echo intval($stats['completed_cnt']); ?> Closed</span><strong>+<?php echo intval($stats['total_servings'] ?? 0); ?> Meals Fed</strong></div>
      </article>
    </section>

    <section class="donation-records" aria-label="Donation records">
      <form method="GET" action="my-donations.php" class="donation-toolbar">
        <label class="donation-search">
          <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
          <input type="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search donations by ID or Food Name...">
        </label>
        <div class="donation-filters" aria-label="Filter donations">
          <a href="my-donations.php" class="<?php echo empty($statusFilter) ? 'selected' : ''; ?>" style="display:inline-block; padding: 8px 14px; text-decoration:none;">All (<?php echo intval($stats['total']); ?>)</a>
          <a href="my-donations.php?status=Pending" class="<?php echo $statusFilter === 'Pending' ? 'selected' : ''; ?>" style="display:inline-block; padding: 8px 14px; text-decoration:none;">Pending (<?php echo intval($stats['pending_cnt']); ?>)</a>
          <a href="my-donations.php?status=Accepted" class="<?php echo $statusFilter === 'Accepted' ? 'selected' : ''; ?>" style="display:inline-block; padding: 8px 14px; text-decoration:none;">Accepted (<?php echo intval($stats['accepted_cnt']); ?>)</a>
          <a href="my-donations.php?status=Completed" class="<?php echo $statusFilter === 'Completed' ? 'selected' : ''; ?>" style="display:inline-block; padding: 8px 14px; text-decoration:none;">Completed (<?php echo intval($stats['completed_cnt']); ?>)</a>
        </div>
        <label class="city-filter">
          <span class="visually-hidden">Filter by city</span>
          <select name="city" onchange="this.form.submit()" aria-label="Filter by city">
            <option value="">All Cities</option>
            <?php foreach ($citiesList as $c): ?>
              <option value="<?php echo $c['name']; ?>" <?php echo $cityFilter === $c['name'] ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
            <?php endforeach; ?>
          </select>
        </label>
      </form>

      <div class="donation-table-wrap">
        <table class="donation-table">
          <thead>
            <tr>
              <th scope="col">Donation<br>ID</th>
              <th scope="col">Food Details</th>
              <th scope="col">Quantity</th>
              <th scope="col">Location &amp; Hub</th>
              <th scope="col">Assigned<br>Organization</th>
              <th scope="col">Available<br>Window</th>
              <th scope="col">Status</th>
              <th scope="col">Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($donations)): ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 36px; color: #677774;">
                  <i class="fa-solid fa-basket-shopping" style="font-size: 2rem; margin-bottom: 8px; display: block; color: #9bb0a8;"></i>
                  No donations found. <a href="donate-food.php" style="color: #1f8c5a; font-weight: 700;">Donate surplus food now</a>.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($donations as $row):
                $statusClass = strtolower($row['status']) . '-status';
                $codeFormatted = htmlspecialchars($row['donation_code']);
              ?>
                <tr>
                  <td><strong class="donation-id"><?php echo $codeFormatted; ?></strong></td>
                  <td><strong><?php echo htmlspecialchars($row['food_name']); ?></strong><span class="food-tags"><em><?php echo htmlspecialchars($row['food_type']); ?></em> · Serves ~<?php echo htmlspecialchars($row['serves']); ?> people</span></td>
                  <td><strong><?php echo htmlspecialchars($row['quantity'] . ' ' . $row['unit']); ?></strong><span><?php echo htmlspecialchars($row['serves']); ?> Servings</span></td>
                  <td><strong class="location"><i class="fa-solid fa-location-dot" aria-hidden="true"></i> <?php echo htmlspecialchars($row['city_name'] ?? 'Rajkot'); ?></strong><span><?php echo htmlspecialchars($row['pickup_address'] ?? 'Municipal Hub'); ?></span></td>
                  <td><span class="organization"><b><?php echo htmlspecialchars($row['org_code'] ?? 'HF'); ?></b> <?php echo htmlspecialchars($row['org_name'] ?? 'Community Hub'); ?></span></td>
                  <td><strong><?php echo htmlspecialchars($row['available_date']); ?></strong><span><?php echo htmlspecialchars($row['available_time']); ?></span></td>
                  <td><span class="status <?php echo $statusClass; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                  <td><a class="details-link" href="donation-details.php?id=<?php echo $row['id']; ?>">View Details <i class="fa-solid fa-chevron-right" aria-hidden="true"></i></a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="donation-pagination">
        <span>Showing <?php echo count($donations); ?> donation(s)</span>
      </div>
    </section>

    <aside class="impact-card">
      <span class="impact-icon"><i class="fa-regular fa-compass" aria-hidden="true"></i></span>
      <div><h2>Your Food Rescue Score</h2><p>By contributing to Food Bridge, you help provide nutritious meals to families in need and keep surplus food out of landfills.</p></div>
    </aside>
  </main>
  <footer class="donations-footer">
    <div class="donations-footer-inner">
      <div class="donations-footer-brand">
        <a class="footer-brand" href="dashboard.php"><span class="app-header-mark"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span><strong>Food Bridge</strong></a>
        <p>Connecting surplus food with community hunger through<br> transparent, reliable local logistics.</p>
      </div>
      <nav aria-label="Footer navigation">
        <a href="about.php">About</a><a href="about.php#how-it-works">How It Works</a><a href="dashboard.php">Dashboard</a>
      </nav>
    </div>
    <div class="donations-footer-bottom">
      <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. | Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
      <p>Community Food Sharing across Gujarat</p>
    </div>
  </footer>
  <script src="js/auth.js" defer></script>
</body>
</html>
