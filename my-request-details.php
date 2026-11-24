<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$db = getDB();

$id = intval($_GET['id'] ?? 0);
$code = sanitize($_GET['code'] ?? '');

if ($id > 0) {
    $stmt = $db->prepare("SELECT r.*, o.name as org_name, o.phone as org_phone, o.address as org_address
                          FROM food_requests r
                          LEFT JOIN organizations o ON r.assigned_org_id = o.id
                          WHERE r.id = ? AND r.user_id = ?");
    $stmt->execute([$id, $user['id']]);
} elseif (!empty($code)) {
    $stmt = $db->prepare("SELECT r.*, o.name as org_name, o.phone as org_phone, o.address as org_address
                          FROM food_requests r
                          LEFT JOIN organizations o ON r.assigned_org_id = o.id
                          WHERE r.request_code = ? AND r.user_id = ?");
    $stmt->execute([$code, $user['id']]);
} else {
    $stmt = $db->prepare("SELECT r.*, o.name as org_name, o.phone as org_phone, o.address as org_address
                          FROM food_requests r
                          LEFT JOIN organizations o ON r.assigned_org_id = o.id
                          WHERE r.user_id = ? ORDER BY r.id DESC LIMIT 1");
    $stmt->execute([$user['id']]);
}

$request = $stmt->fetch();

if (!$request) {
    setFlash('info', 'No food request found. You can submit a request below.');
    header('Location: my-requests.php');
    exit;
}

// Handle Cancel action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    if ($request && $request['status'] === 'Pending') {
        $cancelStmt = $db->prepare("UPDATE food_requests SET status = 'Cancelled' WHERE id = ?");
        $cancelStmt->execute([$request['id']]);
        setFlash('info', 'Request ' . htmlspecialchars($request['request_code']) . ' has been cancelled.');
        header('Location: my-request-details.php?id=' . $request['id']);
        exit;
    }
}

$statusLower = strtolower($request['status'] ?? 'pending');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="View detailed information for a Food Bridge food request.">
  <title>Food Request Details | Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/app-header.css">
  <link rel="stylesheet" href="css/my-request-details.css">
</head>
<body class="request-details-page">
  <?php $activeHeaderPage = 'requests'; include __DIR__ . '/includes/app-header.php'; ?>

  <main class="request-details-shell">
    <div class="request-details-inner">
      <div class="topbar-row">
        <a href="my-requests.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to My Requests</a>
        <span class="request-ref">Ref: <?php echo htmlspecialchars($request['request_code']); ?></span>
      </div>

      <header class="detail-header">
        <div>
          <p class="eyebrow">Food Request Details</p>
          <h1>Request Summary</h1>
        </div>
        <span class="status-pill <?php echo $statusLower; ?>"><i class="fa-solid fa-circle"></i> <?php echo htmlspecialchars($request['status']); ?></span>
      </header>

      <section class="summary-card">
        <div class="summary-left">
          <div class="summary-badge"><i class="fa-solid fa-bowl-food"></i></div>
          <div>
            <div class="label-row">
              <span class="label">Request ID</span>
              <strong><?php echo htmlspecialchars($request['request_code']); ?></strong>
            </div>
            <div class="meta-row">
              <span><i class="fa-solid fa-utensils"></i> <?php echo htmlspecialchars($request['food_type']); ?></span>
              <span><i class="fa-solid fa-people-group"></i> <?php echo htmlspecialchars($request['people_count']); ?> People</span>
              <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($request['city']); ?></span>
            </div>
          </div>
        </div>
        <div class="summary-action">
          <?php if ($request['status'] === 'Pending'): ?>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to cancel this request?');">
              <input type="hidden" name="action" value="cancel">
              <button type="submit" class="ghost-btn" style="color: #e02424; border-color: #f8b4b4; cursor:pointer;"><i class="fa-solid fa-ban"></i> Cancel Request</button>
            </form>
          <?php endif; ?>
          <button type="button" class="ghost-btn" onclick="window.print();"><i class="fa-solid fa-print"></i> Print Details</button>
        </div>
      </section>

      <div class="detail-grid">
        <section class="content-panel">
          <div class="panel-block">
            <h2>Request Information</h2>
            <div class="info-grid">
              <div class="info-box">
                <span class="mini-label">Requested Food Type</span>
                <strong><?php echo htmlspecialchars($request['food_type']); ?></strong>
              </div>
              <div class="info-box">
                <span class="mini-label">Dietary Category</span>
                <strong><?php echo htmlspecialchars($request['dietary_category'] ?? 'Vegetarian'); ?></strong>
              </div>
              <div class="info-box">
                <span class="mini-label">Required Date</span>
                <strong><?php echo date('d M Y', strtotime($request['required_date'])); ?></strong>
              </div>
              <div class="info-box">
                <span class="mini-label">Number of People</span>
                <strong><?php echo htmlspecialchars($request['people_count']); ?></strong>
              </div>
              <div class="info-box">
                <span class="mini-label">City</span>
                <strong><?php echo htmlspecialchars($request['city']); ?></strong>
              </div>
              <div class="info-box">
                <span class="mini-label">Submitted On</span>
                <strong><?php echo date('d M Y, h:i A', strtotime($request['created_at'])); ?></strong>
              </div>
            </div>
          </div>

          <div class="panel-block">
            <h2>Additional Notes</h2>
            <p><?php echo htmlspecialchars($request['notes'] ?: 'No additional notes provided with this request.'); ?></p>
          </div>

          <div class="panel-block">
            <h2>Community Support Status</h2>
            <div class="status-strip">
              <div>
                <span class="mini-label">Assigned Partner</span>
                <strong><?php echo htmlspecialchars($request['org_name'] ?? 'Food Hope Foundation'); ?></strong>
              </div>
              <div>
                <span class="mini-label">Contact Window</span>
                <strong><?php echo htmlspecialchars($request['contact_window'] ?? 'Today, 1:30 PM'); ?></strong>
              </div>
            </div>
          </div>
        </section>

        <aside class="sidebar-panel">
          <div class="side-card">
            <h3>Request Timeline</h3>
            <ul class="timeline">
              <li>
                <span class="dot done"></span>
                <div>
                  <strong>Submitted</strong>
                  <small><?php echo date('d M Y • h:i A', strtotime($request['created_at'])); ?></small>
                </div>
              </li>
              <li>
                <span class="dot <?php echo in_array($request['status'], ['Accepted', 'Completed']) ? 'done' : 'active'; ?>"></span>
                <div>
                  <strong>Audit &amp; Matching</strong>
                  <small><?php echo in_array($request['status'], ['Accepted', 'Completed']) ? 'Partner allocated' : 'Checking available surplus meals'; ?></small>
                </div>
              </li>
              <li>
                <span class="dot <?php echo $request['status'] === 'Completed' ? 'done' : ''; ?>"></span>
                <div>
                  <strong>Pickup / Distribution</strong>
                  <small><?php echo $request['status'] === 'Completed' ? 'Meals successfully distributed' : 'Scheduled coordination'; ?></small>
                </div>
              </li>
            </ul>
          </div>
        </aside>
      </div>
    </div>
  </main>

  <footer class="site-footer">
    <div class="site-footer-inner">
      <div class="footer-branding">
        <a href="dashboard.php" class="footer-logo"><span class="footer-mark"><i class="fa-solid fa-seedling"></i></span>Food Bridge</a>
        <p>Connecting surplus food with community hunger through transparent, reliable local logistics.</p>
      </div>
      <nav class="footer-nav" aria-label="Footer navigation">
        <a href="about.php">About</a>
        <a href="about.php#how-it-works">How It Works</a>
        <a href="dashboard.php">Dashboard</a>
      </nav>
    </div>
    <div class="site-footer-bottom">
      <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. | Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
      <p>Community Food Sharing across Gujarat</p>
    </div>
  </footer>

  <script src="js/auth.js" defer></script>
</body>
</html>
