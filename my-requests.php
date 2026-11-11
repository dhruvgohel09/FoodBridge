<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$db = getDB();

$flash = getFlash();

$stmt = $db->prepare("SELECT r.*, o.name as org_name
                      FROM food_requests r
                      LEFT JOIN organizations o ON r.assigned_org_id = o.id
                      WHERE r.user_id = ?
                      ORDER BY r.id DESC");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Track your submitted food requests with Food Bridge.">
  <title>My Food Requests | Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/app-header.css">
  <link rel="stylesheet" href="css/my-requests.css">
</head>
<body class="my-requests-page">
  <?php $activeHeaderPage = 'requests'; include __DIR__ . '/includes/app-header.php'; ?>

  <main class="requests-page-shell">
    <div class="requests-page-inner">
      <header class="requests-header">
        <div>
          <h1>My Food Requests</h1>
          <p>View your submitted food requests and track allocation from community hubs.</p>
        </div>
        <a href="need-food.php" class="request-cta"><i class="fa-solid fa-plus"></i> Request Food</a>
      </header>

      <?php if ($flash): ?>
        <div style="background: #def7ec; color: #03543f; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; border: 1px solid #bcf0da;">
          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <section class="request-list" aria-label="Food requests list">
        <?php if (empty($requests)): ?>
          <div style="text-align: center; padding: 48px; background: white; border-radius: 16px; border: 1px solid #e1e7e4; color: #677774;">
            <i class="fa-solid fa-bowl-food" style="font-size: 2.5rem; color: #9bb0a8; margin-bottom: 12px; display: block;"></i>
            <h3>No Food Requests Yet</h3>
            <p>If you or your family require food support, we are here to help.</p>
            <a href="need-food.php" style="display: inline-block; margin-top: 14px; background: #1f8c5a; color: white; padding: 10px 20px; border-radius: 8px; font-weight: 700; text-decoration:none;">Submit a Request</a>
          </div>
        <?php else: ?>
          <?php foreach ($requests as $req):
            $statusLower = strtolower($req['status']);
            $icon = 'fa-utensils';
            if (stripos($req['food_type'], 'dry') !== false) $icon = 'fa-box-open';
            if (stripos($req['food_type'], 'produce') !== false) $icon = 'fa-apple-whole';
          ?>
            <article class="request-card">
              <div class="request-item-icon">
                <i class="fa-solid <?php echo $icon; ?>"></i>
              </div>
              <div class="request-item-main">
                <div class="request-id-row">
                  <strong><?php echo htmlspecialchars($req['request_code']); ?></strong>
                  <span class="request-status status-<?php echo $statusLower; ?>"><i class="fa-solid fa-circle"></i> <?php echo htmlspecialchars($req['status']); ?></span>
                </div>
                <div class="request-meta">
                  <span><i class="fa-solid fa-bowl-food"></i> <?php echo htmlspecialchars($req['food_type']); ?> (<?php echo htmlspecialchars($req['dietary_category']); ?>)</span>
                  <span><i class="fa-solid fa-people-group"></i> <?php echo htmlspecialchars($req['people_count']); ?> People</span>
                  <span><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($req['city']); ?></span>
                  <span><i class="fa-regular fa-calendar"></i> <?php echo date('d F Y', strtotime($req['required_date'])); ?></span>
                </div>
              </div>
              <a href="my-request-details.php?id=<?php echo $req['id']; ?>" class="view-details">View Details <i class="fa-solid fa-chevron-right"></i></a>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </section>
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
