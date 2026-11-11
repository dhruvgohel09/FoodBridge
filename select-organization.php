<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$draft = $_SESSION['donation_draft'] ?? [];

if (empty($draft['food_name'])) {
    header('Location: donate-food.php');
    exit;
}

$cityId = intval($draft['city_id'] ?? 1);
$db = getDB();

// Fetch city
$cityStmt = $db->prepare("SELECT * FROM cities WHERE id = ? LIMIT 1");
$cityStmt->execute([$cityId]);
$city = $cityStmt->fetch();
if (!$city) {
    $city = ['id' => 1, 'name' => 'Rajkot'];
}

// Fetch organizations for this city
$orgStmt = $db->prepare("SELECT * FROM organizations WHERE city_id = ? AND is_active = 1 ORDER BY id ASC");
$orgStmt->execute([$city['id']]);
$organizations = $orgStmt->fetchAll();

// If none found for specific city, fetch all active
if (empty($organizations)) {
    $allOrgStmt = $db->query("SELECT * FROM organizations WHERE is_active = 1 ORDER BY id ASC");
    $organizations = $allOrgStmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orgId = intval($_POST['organization_id'] ?? ($organizations[0]['id'] ?? 1));
    $_SESSION['donation_draft']['organization_id'] = $orgId;
    header('Location: confirmation.php');
    exit;
}

$selectedOrgId = intval($draft['organization_id'] ?? ($organizations[0]['id'] ?? 1));
$selectedOrg = null;
foreach ($organizations as $org) {
    if (intval($org['id']) === $selectedOrgId) {
        $selectedOrg = $org;
        break;
    }
}
if (!$selectedOrg && !empty($organizations)) {
    $selectedOrg = $organizations[0];
    $selectedOrgId = intval($selectedOrg['id']);
    $_SESSION['donation_draft']['organization_id'] = $selectedOrgId;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Select Organization - Food Bridge</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/select-organization.css">
  <link rel="stylesheet" href="css/app-header.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .organization-card { cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; }
    .organization-card:hover { transform: translateY(-2px); }
  </style>
</head>
<body class="select-organization-page">

  <?php $activeHeaderPage = 'donate'; include __DIR__ . '/includes/app-header.php'; ?>

  <div class="breadcrumb-bar">
    <div class="container">
      <span class="crumb">Donate Food</span>
      <i class="fa-solid fa-chevron-right"></i>
      <span class="crumb active">Step 3: Select Organization</span>
    </div>
  </div>

  <div class="container">
    <div class="steps-bar">
      <a href="donate-food.php" class="step-box done" style="text-decoration:none; color:inherit;">
        <div class="step-icon"><i class="fa-solid fa-check"></i></div>
        <div class="step-info">
          <span class="step-num">STEP 1</span>
          <span class="step-title">Food Details</span>
        </div>
      </a>
      <a href="select-city.php" class="step-box done" style="text-decoration:none; color:inherit;">
        <div class="step-icon"><i class="fa-solid fa-check"></i></div>
        <div class="step-info">
          <span class="step-num">STEP 2</span>
          <span class="step-title">Select City</span>
        </div>
      </a>
      <div class="step-box active">
        <div class="step-icon">3</div>
        <div class="step-info">
          <span class="step-num">STEP 3</span>
          <span class="step-title">Select Organization</span>
        </div>
      </div>
      <div class="step-box">
        <div class="step-icon">4</div>
        <div class="step-info">
          <span class="step-num">STEP 4</span>
          <span class="step-title">Confirmation</span>
        </div>
      </div>
    </div>
  </div>

  <div class="container main-layout">
    <aside class="sidebar">
      <div class="story-card">
        <div class="story-photo">
          <img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80" alt="Community volunteers preparing food for distribution">
          <span class="story-tag"><i class="fa-solid fa-truck-fast"></i> Community Logistics</span>
        </div>
        <p>Food Bridge relays your donation to community kitchens and relief partners who operate across <?php echo htmlspecialchars($city['name']); ?> to distribute fresh meals quickly.</p>
      </div>

      <div class="summary-card">
        <div class="summary-head">
          <h3><i class="fa-solid fa-basket-shopping"></i> Donation Summary</h3>
          <span class="summary-step">Step 3 of 4</span>
        </div>

        <div class="summary-item">
          <span class="summary-label"><i class="fa-solid fa-utensils"></i> FOOD DETAILS</span>
          <p><?php echo htmlspecialchars($draft['food_name']); ?> (<?php echo htmlspecialchars($draft['quantity'] . ' ' . $draft['unit']); ?>)</p>
        </div>

        <div class="summary-item">
          <span class="summary-label"><i class="fa-solid fa-location-dot"></i> CITY</span>
          <p><?php echo htmlspecialchars($city['name']); ?>, Gujarat</p>
        </div>

        <div class="summary-item">
          <span class="summary-label"><i class="fa-solid fa-clock"></i> PICK-UP WINDOW</span>
          <p><?php echo htmlspecialchars($draft['available_date']); ?>, <?php echo htmlspecialchars($draft['available_time']); ?></p>
        </div>

        <div class="summary-item">
          <span class="summary-label"><i class="fa-regular fa-circle-check"></i> CURRENT ACTION</span>
          <p class="text-green" id="summary-org-name"><?php echo htmlspecialchars($selectedOrg['name'] ?? 'Food Hope Foundation'); ?> (Selected Partner)</p>
        </div>

        <a href="donate-food.php" class="edit-link"><i class="fa-solid fa-pen"></i> Edit food details</a>
      </div>
    </aside>

    <main class="main-content">
      <div class="main-head">
        <h1>Select Organization</h1>
        <p>Choose a verified community relief partner to safely receive and distribute your food surplus.</p>
      </div>

      <div class="city-banner">
        <div class="city-banner-left">
          <div class="city-pin"><i class="fa-solid fa-location-dot"></i></div>
          <div class="city-info">
            <div class="city-name"><?php echo htmlspecialchars($city['name']); ?>, Gujarat</div>
            <span class="trust-pill"><i class="fa-solid fa-circle-check"></i> Verified Regional Hub</span>
          </div>
        </div>
        <a href="select-city.php" class="change-city-btn" style="text-decoration:none;"><i class="fa-solid fa-arrows-rotate"></i> Change City</a>
      </div>

      <form id="org-form" action="select-organization.php" method="POST">
        <input type="hidden" name="organization_id" id="selected-org-input" value="<?php echo $selectedOrgId; ?>" />

        <div class="organization-list">
          <?php
          $orgImages = [
              1 => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80', // Hot cooked meals
              2 => 'https://images.unsplash.com/photo-1555244162-803834f70033?auto=format&fit=crop&w=900&q=85', // Packed thalis
              3 => 'https://images.unsplash.com/photo-1509315811345-672d83ef2fbc?auto=format&fit=crop&w=900&q=80', // Fresh bread and grains
              4 => 'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=900&q=80', // Food rescue boxes
              5 => 'https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=900&q=80', // Community dining
              6 => 'https://images.unsplash.com/photo-1610348725531-843dff563e2c?auto=format&fit=crop&w=900&q=80', // Fresh produce & fruits
          ];
          foreach ($organizations as $org):
            $isSelected = ($org['id'] == $selectedOrgId);
            $itemsArray = explode(',', $org['accepted_items'] ?? 'Cooked Food, Packaged Food');
            $imgUrl = $orgImages[$org['id']] ?? 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80';
          ?>
            <article class="organization-card <?php echo $isSelected ? 'selected' : ''; ?>" onclick="selectOrg(<?php echo $org['id']; ?>, '<?php echo addslashes($org['name']); ?>')">
              <div class="org-image">
                <img src="<?php echo $imgUrl; ?>" alt="<?php echo htmlspecialchars($org['name']); ?>">
              </div>
              <div class="org-body">
                <div class="org-header-row">
                  <div class="org-icon"><i class="fa-solid fa-utensils"></i></div>
                  <div class="org-title-wrap">
                    <h2><?php echo htmlspecialchars($org['name']); ?></h2>
                    <div class="org-subtitle"><?php echo htmlspecialchars($org['address']); ?> • <?php echo htmlspecialchars($org['distance_info']); ?></div>
                  </div>
                  <?php if ($isSelected): ?>
                    <span class="selected-badge"><i class="fa-solid fa-check"></i> Selected</span>
                  <?php endif; ?>
                </div>

                <p class="org-description"><?php echo htmlspecialchars($org['description'] ?? 'Connecting surplus meals with local relief operations and community shelters.'); ?></p>

                <div class="tag-row">
                  <?php foreach ($itemsArray as $item): ?>
                    <span class="tag"><?php echo htmlspecialchars(trim($item)); ?></span>
                  <?php endforeach; ?>
                </div>

                <div class="org-footer-row">
                  <div class="capacity"><i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($org['phone'] ?? '+91 828145-FOOD'); ?></div>
                  <button type="button" class="selection-btn <?php echo $isSelected ? 'selected' : ''; ?>">
                    <?php echo $isSelected ? 'Selected' : 'Select Partner'; ?>
                  </button>
                </div>
              </div>
            </article>
          <?php endforeach; ?>
        </div>

        <div class="bottom-actions" style="margin-top: 24px; display: flex; justify-content: space-between; align-items: center;">
          <a href="select-city.php" class="btn btn-outline" style="text-decoration:none;"><i class="fa-solid fa-arrow-left"></i> Back to City Selection</a>
          <button type="submit" class="btn btn-green">Continue to Confirmation <i class="fa-solid fa-arrow-right"></i></button>
        </div>
      </form>
    </main>

  </div>

  <footer class="footer-dark">
    <div class="container footer-dark-inner">
      <div class="footer-dark-left">
        <div class="logo">
          <span class="logo-mark" aria-hidden="true"><i class="fa-solid fa-seedling"></i></span>
          <div class="logo-text">
            <h3>Food Bridge</h3>
          </div>
        </div>
        <p>Connecting surplus food with community hunger through transparent, reliable local logistics.</p>
      </div>
      <ul class="footer-dark-links">
        <li><a href="about.php">About</a></li>
        <li><a href="about.php#how-it-works">How It Works</a></li>
        <li><a href="dashboard.php">Dashboard</a></li>
      </ul>
    </div>
    <div class="container footer-dark-bottom">
      <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. | Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
      <p>Community Food Sharing across Gujarat</p>
    </div>
  </footer>

  <script>
    function selectOrg(id, name) {
      document.getElementById('selected-org-input').value = id;
      document.getElementById('summary-org-name').textContent = name + ' (Selected Partner)';

      document.querySelectorAll('.organization-card').forEach(function(card) {
        card.classList.remove('selected');
        const badge = card.querySelector('.selected-badge');
        if (badge) badge.remove();
        const btn = card.querySelector('.selection-btn');
        if (btn) {
          btn.classList.remove('selected');
          btn.textContent = 'Select Partner';
        }
      });

      const clicked = event.currentTarget;
      clicked.classList.add('selected');
      const btn = clicked.querySelector('.selection-btn');
      if (btn) {
        btn.classList.add('selected');
        btn.textContent = 'Selected';
      }
    }
  </script>
  <script src="js/auth.js" defer></script>
</body>
</html>
