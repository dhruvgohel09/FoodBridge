<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$draft = $_SESSION['donation_draft'] ?? [];

// If no food details yet, redirect back to step 1
if (empty($draft['food_name'])) {
    header('Location: donate-food.php');
    exit;
}

$db = getDB();
$citiesStmt = $db->query("SELECT * FROM cities WHERE is_active = 1 ORDER BY id ASC");
$cities = $citiesStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cityId = intval($_POST['city_id'] ?? 1);
    $_SESSION['donation_draft']['city_id'] = $cityId;
    header('Location: select-organization.php');
    exit;
}

// Current selected city
$selectedCityId = intval($_SESSION['donation_draft']['city_id'] ?? 1);
$selectedCity = null;
foreach ($cities as $c) {
    if (intval($c['id']) === $selectedCityId) {
        $selectedCity = $c;
        break;
    }
}
if (!$selectedCity && !empty($cities)) {
    $selectedCity = $cities[0];
    $selectedCityId = intval($selectedCity['id']);
    $_SESSION['donation_draft']['city_id'] = $selectedCityId;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Select City - Food Bridge</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/select-city.css">
  <link rel="stylesheet" href="css/app-header.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    .city-card { cursor: pointer; transition: transform 0.2s, border-color 0.2s; }
    .city-card:hover { transform: translateY(-3px); }
  </style>
</head>
<body class="select-city-page">

  <?php $activeHeaderPage = 'donate'; include __DIR__ . '/includes/app-header.php'; ?>

  <!-- ============ BREADCRUMB ============ -->
  <div class="breadcrumb-bar">
    <div class="container">
      <span class="crumb">Donate Food</span>
      <i class="fa-solid fa-chevron-right"></i>
      <span class="crumb active">Step 2: Select City</span>
    </div>
  </div>

  <!-- ============ PROGRESS STEPS ============ -->
  <div class="container">
    <div class="steps-bar">
      <a href="donate-food.php" class="step-box done" style="text-decoration:none; color:inherit;">
        <div class="step-icon"><i class="fa-solid fa-check"></i></div>
        <div class="step-info">
          <span class="step-num">STEP 1</span>
          <span class="step-title">Food Details</span>
        </div>
      </a>
      <div class="step-box active">
        <div class="step-icon">2</div>
        <div class="step-info">
          <span class="step-num">STEP 2</span>
          <span class="step-title">Select City</span>
        </div>
      </div>
      <div class="step-box">
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

  <!-- ============ MAIN CONTENT ============ -->
  <div class="container main-layout">

    <!-- LEFT SIDEBAR -->
    <aside class="sidebar">
      <div class="logistics-card">
        <div class="logistics-header">
          <img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80" alt="Fresh community meals ready for distribution">
          <span class="logistics-tag"><i class="fa-solid fa-truck-fast"></i> Community Logistics</span>
        </div>
        <p>Every city has registered non-profit hubs ready to rescue surplus dining portions and transport them to nearby shelters.</p>
      </div>

      <div class="summary-card">
        <div class="summary-head">
          <h3><i class="fa-solid fa-basket-shopping"></i> Donation Summary</h3>
          <span class="summary-step">Step 2 of 4</span>
        </div>

        <div class="summary-item">
          <span class="summary-label"><i class="fa-solid fa-utensils"></i> FOOD ITEMS</span>
          <p><?php echo htmlspecialchars($draft['food_name']); ?> (<?php echo htmlspecialchars($draft['quantity'] . ' ' . $draft['unit']); ?>)</p>
        </div>

        <div class="summary-item">
          <span class="summary-label"><i class="fa-solid fa-clock"></i> AVAILABLE PICK-UP WINDOW</span>
          <p><?php echo htmlspecialchars($draft['available_date']); ?>, <?php echo htmlspecialchars($draft['available_time']); ?></p>
        </div>

        <div class="summary-item">
          <span class="summary-label"><i class="fa-solid fa-arrows-rotate"></i> WORKFLOW PHASE</span>
          <p class="text-green">Ready for Local City Routing</p>
        </div>

        <a href="donate-food.php" class="edit-link"><i class="fa-solid fa-pen"></i> Edit food details</a>
      </div>

      <div class="why-card">
        <h4><i class="fa-solid fa-circle-info"></i> Why City Selection?</h4>
        <p>Food Bridge connects your donation directly to vetted non-profit community distribution centers operating in your specific municipal area to ensure prompt and fresh redistribution within safe consumption windows.</p>
        <ul>
          <li><i class="fa-solid fa-check"></i> 100% Direct Local Handoff</li>
          <li><i class="fa-solid fa-check"></i> Zero Intermediary Cold Storage Delay</li>
        </ul>
      </div>
    </aside>

    <!-- RIGHT MAIN -->
    <main class="main-content">
      <div class="main-head">
        <div>
          <h1>Choose Your City</h1>
          <p>Select a city to see available verified food rescue organizations.</p>
        </div>
        <span class="verified-badge"><span class="dot"></span> Showing <?php echo count($cities); ?> verified cities in Gujarat</span>
      </div>

      <form id="city-form" action="select-city.php" method="POST">
        <input type="hidden" name="city_id" id="selected-city-input" value="<?php echo $selectedCityId; ?>" />

        <div class="city-grid">
          <?php foreach ($cities as $city):
            $isSelected = ($city['id'] == $selectedCityId);
          ?>
            <div class="city-card <?php echo $isSelected ? 'selected' : ''; ?>" onclick="selectCity(<?php echo $city['id']; ?>, '<?php echo addslashes($city['name']); ?>')">
              <div class="city-top">
                <div class="city-icon"><i class="fa-solid fa-building"></i></div>
                <?php if ($isSelected): ?>
                  <span class="selected-tag"><i class="fa-solid fa-check"></i> Selected</span>
                <?php endif; ?>
              </div>
              <h3><?php echo htmlspecialchars($city['name']); ?></h3>
              <p class="city-meta"><strong><?php echo htmlspecialchars($city['status_label']); ?></strong> • <?php echo htmlspecialchars($city['centers_count']); ?> Verified Centers</p>
              <p class="city-desc"><?php echo htmlspecialchars($city['description']); ?></p>
              <div class="city-bottom">
                <span class="eta">Est. pickup: <strong><?php echo htmlspecialchars($city['eta_info']); ?></strong></span>
                <div class="radio <?php echo $isSelected ? 'filled' : ''; ?>"></div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Selected Info Bar -->
        <div class="selected-bar">
          <div class="selected-bar-icon"><i class="fa-solid fa-check"></i></div>
          <div class="selected-bar-text">
            <p>Selected City: <strong id="selected-city-name"><?php echo htmlspecialchars($selectedCity['name'] ?? 'Rajkot'); ?></strong> <span class="ready-tag">Ready to route</span></p>
            <p class="sub">Next step will display verified community relief centers and kitchens in <span id="selected-city-sub"><?php echo htmlspecialchars($selectedCity['name'] ?? 'Rajkot'); ?></span>.</p>
          </div>
        </div>

        <!-- Bottom Actions -->
        <div class="bottom-actions">
          <a href="donate-food.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Food Details</a>
          <button type="submit" class="btn btn-green">Continue to Select Organization <i class="fa-solid fa-arrow-right"></i></button>
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
    function selectCity(id, name) {
      document.getElementById('selected-city-input').value = id;
      document.getElementById('selected-city-name').textContent = name;
      document.getElementById('selected-city-sub').textContent = name;

      document.querySelectorAll('.city-card').forEach(function(card) {
        card.classList.remove('selected');
        const tag = card.querySelector('.selected-tag');
        if (tag) tag.remove();
        const radio = card.querySelector('.radio');
        if (radio) radio.classList.remove('filled');
      });

      const clicked = event.currentTarget;
      clicked.classList.add('selected');
      const radio = clicked.querySelector('.radio');
      if (radio) radio.classList.add('filled');
    }
  </script>
  <script src="js/auth.js" defer></script>
</body>
</html>
