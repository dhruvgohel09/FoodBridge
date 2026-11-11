<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$db = getDB();

$flash = getFlash();

// User Statistics from MySQL
$statStmt = $db->prepare("SELECT
    COUNT(*) as total_donations,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_donations,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_donations,
    SUM(serves) as total_servings
    FROM donations WHERE user_id = ?");
$statStmt->execute([$user['id']]);
$userDonationStats = $statStmt->fetch() ?: ['total_donations' => 0, 'completed_donations' => 0, 'pending_donations' => 0, 'total_servings' => 0];

$reqStatStmt = $db->prepare("SELECT
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_requests,
    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_requests
    FROM food_requests WHERE user_id = ?");
$reqStatStmt->execute([$user['id']]);
$userReqStats = $reqStatStmt->fetch() ?: ['total_requests' => 0, 'completed_requests' => 0, 'pending_requests' => 0];

// Recent Donations (last 3)
$recentDonationsStmt = $db->prepare("SELECT donation_code, food_name, city_id, status, created_at, serves FROM donations WHERE user_id = ? ORDER BY id DESC LIMIT 3");
$recentDonationsStmt->execute([$user['id']]);
$recentDonations = $recentDonationsStmt->fetchAll();

// Recent Requests (last 3)
$recentRequestsStmt = $db->prepare("SELECT request_code, food_type, city, status, created_at, people_count FROM food_requests WHERE user_id = ? ORDER BY id DESC LIMIT 3");
$recentRequestsStmt->execute([$user['id']]);
$recentRequests = $recentRequestsStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Food Bridge | Dashboard</title>
  <meta name="description" content="Food Bridge connects surplus food with people who need it, helping reduce food waste and build a stronger community." />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/app-header.css" />
  <style>
    :root {
      --bg: #eef1ee;
      --panel: #f5f6f5;
      --white: #ffffff;
      --green: #1f8c5a;
      --green-dark: #0d6d43;
      --green-soft: #eaf6ee;
      --orange: #ef8b41;
      --orange-soft: #fff3ea;
      --text: #1e2624;
      --muted: #677774;
      --line: #dfe7e4;
      --shadow: 0 18px 36px rgba(18, 32, 28, 0.08);
    }
    * { box-sizing: border-box; }
    html { scroll-behavior: smooth; }
    body {
      margin: 0;
      background: #f2f3f1;
      color: var(--text);
      font-family: 'Inter', 'Segoe UI', sans-serif;
      line-height: 1.6;
    }
    a { text-decoration: none; color: inherit; }
    img { display: block; width: 100%; }
    .page-shell {
      width: 100%;
      max-width: none;
      margin: 0 auto 30px;
      padding: 0 32px;
    }
    main.dashboard {
      display: grid;
      gap: 24px;
      margin-top: 26px;
    }
    .welcome-card {
      background: linear-gradient(135deg, #edf9f1 0%, #f5faf6 100%);
      border: 1px solid rgba(19,54,39,0.08);
      border-radius: 22px;
      box-shadow: var(--shadow);
      padding: 24px 24px 18px;
      display: grid;
      grid-template-columns: 1.1fr 0.9fr;
      gap: 20px;
      align-items: center;
    }
    .status-row {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--green-dark);
      margin-bottom: 8px;
    }
    .status-dot {
      width: 8px;
      height: 8px;
      border-radius: 50%;
      background: var(--green);
    }
    .welcome-copy h1 {
      margin: 0 0 10px;
      font-size: clamp(2rem, 3vw, 2.7rem);
      letter-spacing: -0.05em;
      line-height: 1.15;
      color: #172a25;
    }
    .welcome-copy p {
      margin: 0 0 14px;
      color: var(--muted);
      font-size: 0.98rem;
    }
    .chip-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 16px;
    }
    .chip {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,0.7);
      border: 1px solid rgba(17,82,58,0.08);
      border-radius: 999px;
      padding: 6px 12px;
      font-size: 0.76rem;
      font-weight: 600;
      color: #49635c;
    }
    .chip i { font-size: 0.4rem; color: var(--green); }
    .cta-row {
      display: flex;
      gap: 12px;
      flex-wrap: wrap;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 11px 18px;
      border-radius: 12px;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      transition: transform 0.15s ease;
    }
    .btn:hover { transform: translateY(-2px); }
    .btn-green { background: var(--green); color: white; }
    .btn-soft { background: white; border: 1px solid rgba(20,40,34,0.1); color: #2e433e; }
    .welcome-visual { position: relative; }
    .welcome-photo {
      border-radius: 18px;
      overflow: hidden;
      aspect-ratio: 16/9;
      box-shadow: 0 12px 24px rgba(0,0,0,0.08);
    }
    .welcome-photo img { object-fit: cover; height: 100%; }
    .visual-card {
      position: absolute;
      bottom: -12px;
      left: 16px;
      background: white;
      padding: 10px 14px;
      border-radius: 14px;
      box-shadow: 0 10px 20px rgba(0,0,0,0.08);
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .visual-card .mini-icon {
      width: 34px;
      height: 34px;
      background: #eaf6ee;
      color: var(--green-dark);
      border-radius: 10px;
      display: grid;
      place-items: center;
    }
    .visual-card strong { display: block; font-size: 0.88rem; color: #1e2624; }
    .visual-card span { font-size: 0.72rem; color: var(--muted); }
    .help-section { margin-top: 10px; }
    .section-head h2 {
      font-size: 1.6rem;
      letter-spacing: -0.04em;
      margin: 0 0 16px;
      color: #172a25;
    }
    .help-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 18px;
    }
    .help-card {
      background: white;
      border: 1px solid rgba(20,40,34,0.08);
      border-radius: 18px;
      padding: 22px;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .help-card:hover { transform: translateY(-3px); box-shadow: var(--shadow); }
    .help-icon {
      width: 48px;
      height: 48px;
      border-radius: 14px;
      display: grid;
      place-items: center;
      font-size: 1.4rem;
      background: rgba(31,140,90,0.12);
      color: var(--green-dark);
      margin-bottom: 14px;
    }
    .help-card.orange .help-icon {
      background: rgba(239,139,65,0.12);
      color: #b75f1d;
    }
    .help-card h3 {
      margin: 0 0 8px;
      font-size: 1.35rem;
      letter-spacing: -0.03em;
      color: #1d2d29;
    }
    .help-card p {
      margin: 0 0 16px;
      color: var(--muted);
      line-height: 1.6;
      font-size: 0.94rem;
    }
    .meta-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-bottom: 16px;
    }
    .meta-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 4px 10px;
      background: #f4f6f4;
      border-radius: 999px;
      font-size: 0.74rem;
      font-weight: 600;
      color: #536b66;
    }
    .help-link {
      width: 100%;
      display: inline-flex;
      align-items: center;
      justify-content: space-between;
      background: #f7faf8;
      border: 1px solid rgba(17,82,58,0.08);
      border-radius: 12px;
      padding: 12px 14px;
      font-weight: 700;
      color: var(--green-dark);
    }
    .summary-row {
      display: grid;
      grid-template-columns: repeat(4,minmax(0,1fr));
      gap: 16px;
    }
    .summary-card {
      background: white;
      border: 1px solid rgba(20,37,32,0.08);
      border-radius: 16px;
      padding: 18px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
    }
    .summary-left {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .summary-label {
      font-size: 0.8rem;
      font-weight: 600;
      color: var(--muted);
    }
    .summary-value {
      font-size: 1.8rem;
      font-weight: 800;
      color: #1a2a26;
      letter-spacing: -0.04em;
    }
    .summary-icon {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: grid;
      place-items: center;
      font-size: 1.2rem;
      background: rgba(31,140,90,0.1);
      color: var(--green);
    }
    .summary-card.orange .summary-icon { background: rgba(239,139,65,0.12); color: #d97706; }
    .summary-card.blue .summary-icon { background: rgba(37,99,235,0.1); color: #2563eb; }
    .summary-card.purple .summary-icon { background: rgba(147,51,234,0.1); color: #9333ea; }
    .activity {
      background: white;
      border-radius: 18px;
      padding: 24px;
      border: 1px solid rgba(20,40,34,0.08);
    }
    .activity-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 18px;
    }
    .activity-header h3 {
      margin: 0;
      font-size: 1.35rem;
      letter-spacing: -0.03em;
      color: #1b2d2a;
    }
    .view-link {
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--green-dark);
    }
    .activity-list {
      display: grid;
      gap: 10px;
    }
    .activity-item {
      background: #fafbfa;
      border: 1px solid rgba(24,41,35,0.06);
      border-radius: 12px;
      padding: 12px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
    }
    .activity-main {
      display: flex;
      align-items: center;
      gap: 12px;
      min-width: 0;
    }
    .activity-bullet {
      width: 28px;
      height: 28px;
      border-radius: 8px;
      display: grid;
      place-items: center;
      background: rgba(31,140,90,0.1);
      color: var(--green-dark);
      font-size: 0.85rem;
      flex-shrink: 0;
    }
    .activity-text strong {
      display: block;
      font-size: 0.9rem;
      color: #1e2c2a;
    }
    .activity-text span {
      display: block;
      font-size: 0.74rem;
      color: var(--muted);
      margin-top: 1px;
    }
    .status-badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 4px 10px;
      border-radius: 999px;
      background: rgba(31,140,90,0.1);
      color: var(--green-dark);
      font-size: 0.74rem;
      font-weight: 700;
    }
    .status-badge.complete {
      background: rgba(239,139,65,0.12);
      color: #b65e1b;
    }
    @media (max-width: 980px) {
      .welcome-card { grid-template-columns: 1fr; }
      .help-grid { grid-template-columns: 1fr; }
      .summary-row { grid-template-columns: repeat(2,1fr); }
    }
    @media (max-width: 600px) {
      .page-shell { padding: 0 16px; }
      .summary-row { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
  <?php $activeHeaderPage = 'home'; include __DIR__ . '/includes/app-header.php'; ?>
  <div class="page-shell">
    <main class="dashboard">

      <?php if ($flash): ?>
        <div style="background: #def7ec; color: #03543f; padding: 14px 18px; border-radius: 14px; font-size: 0.95rem; border: 1px solid #bcf0da;">
          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

      <section class="welcome-card">
        <div class="welcome-copy">
          <div class="status-row">
            <span class="status-dot"></span>
            <span>Community Contributor • <?php echo htmlspecialchars($user['city']); ?>, Gujarat</span>
          </div>
          <h1>Welcome Back, <?php echo htmlspecialchars($user['name']); ?>!</h1>
          <p>Together, we can ensure wholesome surplus food reaches people who need it. Your compassionate actions build a kinder, more connected neighborhood.</p>
          <div class="chip-grid">
            <span class="chip"><i class="fa-solid fa-circle"></i> Active City: <?php echo htmlspecialchars($user['city']); ?></span>
            <span class="chip"><i class="fa-solid fa-circle"></i> Donations: <?php echo intval($userDonationStats['total_donations']); ?> logged</span>
            <span class="chip"><i class="fa-solid fa-circle"></i> Requests: <?php echo intval($userReqStats['total_requests']); ?> submitted</span>
          </div>
          <div class="cta-row">
            <a href="donate-food.php" class="btn btn-green"><i class="fa-solid fa-hand-holding-heart"></i> Quick Donate Food</a>
            <a href="need-food.php" class="btn btn-soft"><i class="fa-solid fa-bowl-food"></i> Request Food</a>
          </div>
        </div>
        <div class="welcome-visual">
          <div class="welcome-photo">
            <img src="https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=1200&q=80" alt="Community food rescue and distribution" />
          </div>
          <div class="visual-card">
            <div class="mini-icon"><i class="fa-solid fa-people-group"></i></div>
            <div>
              <strong>+<?php echo intval($userDonationStats['total_servings'] ?? 0); ?> Meals Shared</strong>
              <span>Impact logged under your account</span>
            </div>
          </div>
        </div>
      </section>

      <section class="summary-row" aria-label="Your impact summary">
        <article class="summary-card">
          <div class="summary-left">
            <span class="summary-label">Your Donations</span>
            <span class="summary-value"><?php echo intval($userDonationStats['total_donations']); ?></span>
          </div>
          <div class="summary-icon"><i class="fa-solid fa-gift"></i></div>
        </article>
        <article class="summary-card orange">
          <div class="summary-left">
            <span class="summary-label">Your Requests</span>
            <span class="summary-value"><?php echo intval($userReqStats['total_requests']); ?></span>
          </div>
          <div class="summary-icon"><i class="fa-solid fa-clipboard-list"></i></div>
        </article>
        <article class="summary-card blue">
          <div class="summary-left">
            <span class="summary-label">Completed Donations</span>
            <span class="summary-value"><?php echo intval($userDonationStats['completed_donations']); ?></span>
          </div>
          <div class="summary-icon"><i class="fa-solid fa-circle-check"></i></div>
        </article>
        <article class="summary-card purple">
          <div class="summary-left">
            <span class="summary-label">Completed Requests</span>
            <span class="summary-value"><?php echo intval($userReqStats['completed_requests']); ?></span>
          </div>
          <div class="summary-icon"><i class="fa-solid fa-check-double"></i></div>
        </article>
      </section>

      <section class="help-section" id="actions">
        <div class="section-head">
          <h2>How Would You Like to Help?</h2>
        </div>
        <div class="help-grid">
          <article class="help-card">
            <div class="help-card-header">
              <div class="help-icon"><i class="fa-solid fa-hand-holding-heart"></i></div>
            </div>
            <h3>Donate Food</h3>
            <p>Have extra food? Share it with people who need it. Prevent waste and support local families with a simple, respectful donation process.</p>
            <div class="meta-row">
              <span class="meta-pill"><i class="fa-solid fa-check"></i> Fresh &amp; cooked</span>
              <span class="meta-pill"><i class="fa-solid fa-clock"></i> Takes 2 minutes</span>
            </div>
            <a href="donate-food.php" class="help-link">Donate Food <i class="fa-solid fa-arrow-right"></i></a>
          </article>
          <article class="help-card orange">
            <div class="help-card-header">
              <div class="help-icon"><i class="fa-solid fa-bowl-food"></i></div>
            </div>
            <h3>Need Food?</h3>
            <p>Request food support when you or someone you know needs a little help. The process is quick, private, and designed to be respectful.</p>
            <div class="meta-row">
              <span class="meta-pill"><i class="fa-solid fa-shield-heart"></i> Dignified &amp; confidential</span>
              <span class="meta-pill"><i class="fa-solid fa-sparkles"></i> 100% Free service</span>
            </div>
            <a href="need-food.php" class="help-link">Request Food <i class="fa-solid fa-arrow-right"></i></a>
          </article>
        </div>
      </section>

      <section class="activity" id="recent-activity" aria-label="Recent activity">
        <div class="activity-header">
          <h3>Recent Activity</h3>
          <a href="my-donations.php" class="view-link">View All Donations</a>
        </div>
        <div class="activity-list">
          <?php if (empty($recentDonations) && empty($recentRequests)): ?>
            <p style="color: var(--muted); padding: 12px 0;">No recent activity yet. Make your first donation or request food to get started.</p>
          <?php endif; ?>

          <?php foreach ($recentDonations as $d): ?>
            <article class="activity-item">
              <div class="activity-main">
                <div class="activity-bullet"><i class="fa-solid fa-utensils"></i></div>
                <div class="activity-text">
                  <strong>Food donation (<?php echo htmlspecialchars($d['food_name']); ?>)</strong>
                  <span><?php echo htmlspecialchars($d['donation_code']); ?> • <?php echo date('d M Y, h:i A', strtotime($d['created_at'])); ?></span>
                </div>
              </div>
              <span class="status-badge <?php echo $d['status'] === 'Completed' ? 'complete' : ''; ?>"><?php echo htmlspecialchars($d['status']); ?></span>
            </article>
          <?php endforeach; ?>

          <?php foreach ($recentRequests as $r): ?>
            <article class="activity-item">
              <div class="activity-main">
                <div class="activity-bullet" style="background: rgba(239,139,65,0.12); color: #b75f1d;"><i class="fa-solid fa-bowl-food"></i></div>
                <div class="activity-text">
                  <strong>Food request (<?php echo htmlspecialchars($r['food_type']); ?> for <?php echo htmlspecialchars($r['people_count']); ?> people)</strong>
                  <span><?php echo htmlspecialchars($r['request_code']); ?> • <?php echo date('d M Y, h:i A', strtotime($r['created_at'])); ?></span>
                </div>
              </div>
              <span class="status-badge <?php echo $r['status'] === 'Completed' ? 'complete' : ''; ?>"><?php echo htmlspecialchars($r['status']); ?></span>
            </article>
          <?php endforeach; ?>
        </div>
      </section>

      <footer class="dashboard-footer-bottom" style="margin-top: 36px; padding: 20px 0; border-top: 1px solid rgba(0,0,0,0.06); display: flex; justify-content: space-between; align-items: center; color: var(--muted); font-size: 0.85rem;">
        <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. | Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
        <p>Connecting surplus food with communities across Gujarat</p>
      </footer>
    </main>
  </div>
  <script src="js/auth.js" defer></script>
</body>
</html>
