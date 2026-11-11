<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$db = getDB();

$id = intval($_GET['id'] ?? 0);
$code = sanitize($_GET['code'] ?? '');

if ($id > 0) {
    $stmt = $db->prepare("SELECT d.*, c.name as city_name, o.name as org_name, o.short_code as org_code, o.address as org_address, o.phone as org_phone, o.description as org_desc, o.accepted_items as org_items, u.name as donor_name, u.phone as donor_phone
                          FROM donations d
                          LEFT JOIN cities c ON d.city_id = c.id
                          LEFT JOIN organizations o ON d.organization_id = o.id
                          LEFT JOIN users u ON d.user_id = u.id
                          WHERE d.id = ? AND d.user_id = ?");
    $stmt->execute([$id, $user['id']]);
} elseif (!empty($code)) {
    $stmt = $db->prepare("SELECT d.*, c.name as city_name, o.name as org_name, o.short_code as org_code, o.address as org_address, o.phone as org_phone, o.description as org_desc, o.accepted_items as org_items, u.name as donor_name, u.phone as donor_phone
                          FROM donations d
                          LEFT JOIN cities c ON d.city_id = c.id
                          LEFT JOIN organizations o ON d.organization_id = o.id
                          LEFT JOIN users u ON d.user_id = u.id
                          WHERE d.donation_code = ? AND d.user_id = ?");
    $stmt->execute([$code, $user['id']]);
} else {
    // Default to the user's latest donation
    $stmt = $db->prepare("SELECT d.*, c.name as city_name, o.name as org_name, o.short_code as org_code, o.address as org_address, o.phone as org_phone, o.description as org_desc, o.accepted_items as org_items, u.name as donor_name, u.phone as donor_phone
                          FROM donations d
                          LEFT JOIN cities c ON d.city_id = c.id
                          LEFT JOIN organizations o ON d.organization_id = o.id
                          LEFT JOIN users u ON d.user_id = u.id
                          WHERE d.user_id = ? ORDER BY d.id DESC LIMIT 1");
    $stmt->execute([$user['id']]);
}

$donation = $stmt->fetch();

if (!$donation) {
    // Fallback if not found for this specific user
    $fallbackStmt = $db->query("SELECT d.*, c.name as city_name, o.name as org_name, o.short_code as org_code, o.address as org_address, o.phone as org_phone, o.description as org_desc, o.accepted_items as org_items, u.name as donor_name, u.phone as donor_phone
                               FROM donations d
                               LEFT JOIN cities c ON d.city_id = c.id
                               LEFT JOIN organizations o ON d.organization_id = o.id
                               LEFT JOIN users u ON d.user_id = u.id
                               ORDER BY d.id DESC LIMIT 1");
    $donation = $fallbackStmt->fetch();
}

// Handle Cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    if ($donation && $donation['status'] === 'Pending') {
        $cancelStmt = $db->prepare("UPDATE donations SET status = 'Cancelled' WHERE id = ?");
        $cancelStmt->execute([$donation['id']]);
        setFlash('info', 'Donation ' . htmlspecialchars($donation['donation_code']) . ' has been cancelled.');
        header('Location: donation-details.php?id=' . $donation['id']);
        exit;
    }
}

$status = $donation['status'] ?? 'Pending';
$statusClass = 'warning';
if ($status === 'Accepted') $statusClass = 'info';
if ($status === 'Completed') $statusClass = 'success';
if ($status === 'Cancelled') $statusClass = 'danger';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Donation Details - Food Bridge</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/app-header.css">
  <link rel="stylesheet" href="css/donation-details.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="donation-details-page">
  <?php $activeHeaderPage = 'donations'; include __DIR__ . '/includes/app-header.php'; ?>

  <div class="container details-page-shell">
    <div class="details-topbar">
      <a href="my-donations.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to My Donations</a>
      <span class="donor-meta">Ref: <?php echo htmlspecialchars($user['name']); ?> (Donor ID: USR-<?php echo 100 + $user['id']; ?>)</span>
    </div>

    <header class="details-header">
      <h1>Donation Details</h1>
      <p>View your food donation information, verification audit, and current status.</p>
    </header>

    <section class="donation-header-card">
      <div class="donation-header-main">
        <div class="donation-id-line">
          <span class="detail-label">Donation ID:</span>
          <strong><?php echo htmlspecialchars($donation['donation_code']); ?></strong>
          <span class="copy-badge" onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($donation['donation_code']); ?>'); alert('Donation code copied!');">Copy</span>
        </div>
        <div class="meta-row">
          <span><i class="fa-solid fa-circle-check"></i> Submitted on <?php echo date('d M Y, h:i A', strtotime($donation['created_at'])); ?> via Food Bridge Portal</span>
        </div>
      </div>
      <div class="status-chip <?php echo $statusClass; ?>">
        <span class="status-dot"></span>
        <span><?php echo htmlspecialchars($donation['status']); ?></span>
      </div>
    </section>

    <div class="details-layout">
      <main class="details-main">
        <section class="content-panel">
          <div class="panel-header">
            <div class="panel-title"><span class="mini-icon"><i class="fa-solid fa-bowl-food"></i></span> Food Specifications</div>
            <span class="panel-note">Itemized parameters logged by donor</span>
          </div>

          <div class="spec-grid">
            <article class="spec-box">
              <span class="spec-label">Food Name</span>
              <strong><?php echo htmlspecialchars($donation['food_name']); ?></strong>
              <span class="spec-sub">Surplus Batch</span>
              <span class="tag success"><?php echo htmlspecialchars($donation['food_type']); ?></span>
            </article>
            <article class="spec-box">
              <span class="spec-label">Total Quantity</span>
              <strong><?php echo htmlspecialchars($donation['quantity'] . ' ' . $donation['unit']); ?></strong>
              <span class="spec-sub">Clean packaging</span>
            </article>
            <article class="spec-box">
              <span class="spec-label">Serves Count</span>
              <strong>~<?php echo htmlspecialchars($donation['serves']); ?> People</strong>
              <span class="spec-sub">Complete meal portions</span>
            </article>
            <article class="spec-box">
              <span class="spec-label">Available Date</span>
              <strong><?php echo htmlspecialchars($donation['available_date']); ?></strong>
            </article>
            <article class="spec-box">
              <span class="spec-label">Available Hand-off Window</span>
              <strong><?php echo htmlspecialchars($donation['available_time']); ?></strong>
              <span class="spec-sub">Pickup Window</span>
            </article>
          </div>

          <?php if (!empty($donation['food_photo'])): ?>
            <div class="photo-card">
              <div class="photo-header">
                <span>Uploaded Packing Verification</span>
                <span class="photo-badge"><i class="fa-solid fa-camera"></i> Pre-distribution Photo</span>
              </div>
              <div class="photo-wrap">
                <img src="<?php echo htmlspecialchars($donation['food_photo']); ?>" alt="<?php echo htmlspecialchars($donation['food_name']); ?>">
              </div>
              <div class="photo-footer">
                <span><i class="fa-solid fa-circle-check"></i> Verified hygiene &amp; packaging photo</span>
              </div>
            </div>
          <?php endif; ?>
        </section>

        <section class="notes-panel">
          <div class="panel-header compact">
            <div class="panel-title"><span class="mini-icon"><i class="fa-solid fa-book-open-reader"></i></span> Preparation &amp; Handling Notes</div>
          </div>
          <p><?php echo htmlspecialchars($donation['notes'] ?: 'Freshly prepared food. Suitable for children and adults. Packed in food-grade containers, kept hygienic.'); ?></p>
        </section>

        <section class="notes-panel" style="margin-top: 18px;">
          <div class="panel-header compact">
            <div class="panel-title"><span class="mini-icon"><i class="fa-solid fa-map-location-dot"></i></span> Pickup Address &amp; Contact</div>
          </div>
          <p><strong>Pickup Address:</strong> <?php echo htmlspecialchars($donation['pickup_address'] ?? 'Not specified'); ?></p>
          <p><strong>Contact Phone:</strong> <?php echo htmlspecialchars($donation['contact_phone'] ?? $user['phone']); ?></p>
        </section>

        <div class="bottom-action-row">
          <a href="my-donations.php" class="ghost-link"><i class="fa-solid fa-arrow-left"></i> Back to My Donations</a>
          <div class="action-right">
            <?php if ($donation['status'] === 'Pending'): ?>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this donation?');">
                <input type="hidden" name="action" value="cancel">
                <button type="submit" class="download-btn" style="background: #e02424; color: white; border-color: #e02424; cursor:pointer;"><i class="fa-solid fa-ban"></i> Cancel Donation</button>
              </form>
            <?php endif; ?>
            <button type="button" class="download-btn" onclick="window.print();"><i class="fa-solid fa-print"></i> Print Receipt</button>
          </div>
        </div>
      </main>

      <aside class="details-sidebar">
        <div class="info-card side-panel">
          <div class="side-card-head">
            <span class="mini-icon soft"><i class="fa-solid fa-location-dot"></i></span>
            <span>Donation City &amp; Hub</span>
            <span class="chip green">Fixed Registry</span>
          </div>
          <div class="city-line">
            <div class="city-mark"><i class="fa-solid fa-location-dot"></i></div>
            <div>
              <strong><?php echo htmlspecialchars($donation['city_name'] ?? 'Rajkot'); ?>, Gujarat</strong>
              <small>Municipal Center Hub</small>
            </div>
          </div>
          <p>Direct local municipal handoff. No transit cold storage delays; scheduled for hyper-local community dining center.</p>
        </div>

        <div class="info-card side-panel">
          <div class="side-card-head">
            <span class="mini-icon soft"><i class="fa-solid fa-building-columns"></i></span>
            <span>Assigned Organization</span>
            <span class="chip success"><i class="fa-solid fa-shield-check"></i> Vetted Partner</span>
          </div>
          <div class="org-row">
            <div class="org-mark"><?php echo htmlspecialchars($donation['org_code'] ?? 'HF'); ?></div>
            <div>
              <strong><?php echo htmlspecialchars($donation['org_name'] ?? 'Food Hope Foundation'); ?></strong>
              <small><?php echo htmlspecialchars($donation['org_address'] ?? 'Central Ward'); ?></small>
            </div>
          </div>
          <p><?php echo htmlspecialchars($donation['org_desc'] ?? 'A community organization helping connect surplus food with people in need.'); ?></p>
          <div class="accepted-tags">
            <?php
            $items = explode(',', $donation['org_items'] ?? 'Cooked Food, Packaged Food');
            foreach ($items as $it): ?>
              <span><?php echo htmlspecialchars(trim($it)); ?></span>
            <?php endforeach; ?>
          </div>
          <div class="org-note"><i class="fa-solid fa-phone"></i> Direct NGO Hotline: <strong><?php echo htmlspecialchars($donation['org_phone'] ?? '+91 828145-FOOD'); ?></strong></div>
        </div>
      </aside>
    </div>
  </div>

  <footer class="donation-footer">
    <div class="container donation-footer-inner">
      <div class="footer-branding">
        <a href="dashboard.php" class="footer-logo"><span class="app-header-mark"><i class="fa-solid fa-seedling"></i></span><span>Food Bridge</span></a>
        <p>Connecting surplus food with community hunger through transparent, reliable local logistics.</p>
      </div>
      <nav aria-label="Footer navigation">
        <a href="about.php">About</a>
        <a href="about.php#how-it-works">How It Works</a>
        <a href="dashboard.php">Dashboard</a>
      </nav>
    </div>
    <div class="container donation-footer-bottom">
      <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. | Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
      <p>Community Food Sharing across Gujarat</p>
    </div>
  </footer>
  <script src="js/auth.js" defer></script>
</body>
</html>
