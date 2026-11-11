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

$db = getDB();
$cityId = intval($draft['city_id'] ?? 1);
$orgId = intval($draft['organization_id'] ?? 1);

// Fetch city
$cityStmt = $db->prepare("SELECT * FROM cities WHERE id = ? LIMIT 1");
$cityStmt->execute([$cityId]);
$city = $cityStmt->fetch() ?: ['id' => 1, 'name' => 'Rajkot'];

// Fetch organization
$orgStmt = $db->prepare("SELECT * FROM organizations WHERE id = ? LIMIT 1");
$orgStmt->execute([$orgId]);
$organization = $orgStmt->fetch() ?: [
    'id' => 1,
    'name' => 'Food Hope Foundation',
    'short_code' => 'HF',
    'category' => 'Verified NGO distribution',
    'rating_info' => 'Admin Vetted Partner',
    'address' => 'Rajkot Central Ward • 1.8 km from Pickup Hub',
    'phone' => '+91 828145-FOOD',
    'accepted_items' => 'Cooked Food, Packaged Food, Fresh Fruits',
    'description' => 'Community organization working to connect surplus food with people in need across Rajkot neighborhoods.'
];

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup_address = sanitize($_POST['pickup_address'] ?? '');
    $contact_phone = sanitize($_POST['contact_phone'] ?? ($user['phone'] ?? ''));

    if (empty($pickup_address)) {
        $error = 'Please enter your pickup address.';
    } else {
        // Generate donation code
        $countStmt = $db->query("SELECT COUNT(*) FROM donations");
        $nextNum = intval($countStmt->fetchColumn()) + 1;
        $donationCode = 'FB-' . date('Y') . '-' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);

        $stmt = $db->prepare("INSERT INTO donations (donation_code, user_id, food_name, food_type, quantity, unit, serves, available_date, available_time, food_photo, notes, city_id, organization_id, pickup_address, contact_phone, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending')");

        try {
            $stmt->execute([
                $donationCode,
                $user['id'],
                $draft['food_name'],
                $draft['food_type'],
                $draft['quantity'],
                $draft['unit'],
                $draft['serves'],
                $draft['available_date'],
                $draft['available_time'],
                $draft['food_photo'] ?? null,
                $draft['notes'] ?? '',
                $city['id'],
                $organization['id'],
                $pickup_address,
                $contact_phone
            ]);

            unset($_SESSION['donation_draft']);
            setFlash('success', "Your donation ({$donationCode}) has been successfully logged! {$organization['name']} has been alerted.");
            header('Location: donation-details.php?code=' . urlencode($donationCode));
            exit;
        } catch (PDOException $e) {
            $error = 'Error saving donation: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Confirm Your Donation - Food Bridge</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/confirmation.css">
  <link rel="stylesheet" href="css/app-header.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="confirmation-page">
  <?php $activeHeaderPage = 'donate'; include __DIR__ . '/includes/app-header.php'; ?>

  <div class="breadcrumb-bar">
    <div class="container">
      <span class="crumb">Donate Food</span>
      <i class="fa-solid fa-chevron-right"></i>
      <span class="crumb active">Step 4: Donation Confirmation</span>
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
      <a href="select-organization.php" class="step-box done" style="text-decoration:none; color:inherit;">
        <div class="step-icon"><i class="fa-solid fa-check"></i></div>
        <div class="step-info">
          <span class="step-num">STEP 3</span>
          <span class="step-title">Select Organization</span>
        </div>
      </a>
      <div class="step-box active">
        <div class="step-icon">4</div>
        <div class="step-info">
          <span class="step-num">STEP 4</span>
          <span class="step-title">Confirmation</span>
        </div>
      </div>
    </div>
  </div>

  <form method="POST" action="confirmation.php">
    <div class="container confirmation-layout">
      <main class="confirmation-main">
        <div class="confirm-header">
          <h1>Confirm Your Donation</h1>
          <p>Please review your donation details before submitting to ensure seamless local dispatch to <?php echo htmlspecialchars($organization['name']); ?>.</p>
        </div>

        <?php if (!empty($error)): ?>
          <div style="background: #fde8e8; color: #9b1c1c; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; border: 1px solid #f8b4b4;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
          </div>
        <?php endif; ?>

        <section class="food-details-card">
          <div class="food-card-header">
            <div class="food-card-title">
              <span class="food-card-icon"><i class="fa-solid fa-bowl-food"></i></span>
              <span>Food Details</span>
            </div>
            <a href="donate-food.php" class="edit-btn" style="text-decoration:none;"><i class="fa-solid fa-pen"></i> Edit</a>
          </div>

          <div class="detail-grid">
            <div class="detail-item">
              <div class="label">Food Name</div>
              <div class="value-row">
                <strong><?php echo htmlspecialchars($draft['food_name']); ?></strong>
              </div>
              <span class="tag success"><?php echo htmlspecialchars($draft['food_type']); ?></span>
            </div>

            <div class="detail-item">
              <div class="label">Quantity &amp; Coverage</div>
              <div class="value-row">
                <strong><?php echo htmlspecialchars($draft['quantity'] . ' ' . $draft['unit']); ?></strong>
              </div>
              <small>Serves ~<?php echo htmlspecialchars($draft['serves']); ?> People</small>
            </div>

            <div class="detail-item">
              <div class="label">Scheduled Pickup Date</div>
              <div class="value-row"><strong><?php echo htmlspecialchars($draft['available_date']); ?></strong></div>
            </div>

            <div class="detail-item">
              <div class="label">Available Window</div>
              <div class="value-row"><strong><?php echo htmlspecialchars($draft['available_time']); ?></strong></div>
            </div>
          </div>

          <?php if (!empty($draft['food_photo'])): ?>
            <div class="photo-block">
              <div class="photo-wrap">
                <img src="<?php echo htmlspecialchars($draft['food_photo']); ?>" alt="Packed food ready for donation" style="max-height: 200px; object-fit: cover;">
                <span class="stamp">Visual Quality Stamp</span>
              </div>
              <div class="photo-meta">
                <span class="meta-check"><i class="fa-solid fa-circle-check"></i> Verified clean meal packaging</span>
                <span class="meta-check"><i class="fa-solid fa-circle-check"></i> Inspected</span>
              </div>
            </div>
          <?php endif; ?>

          <div class="notes-block">
            <div class="section-label">Preparation &amp; Handling Notes</div>
            <p><?php echo htmlspecialchars($draft['notes'] ?: 'Freshly prepared food. Suitable for children and adults. Packed in food-grade containers.'); ?></p>
          </div>

          <div class="hub-row">
            <div class="hub-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div class="hub-copy">
              <div class="section-label">Donation City &amp; Municipal Hub</div>
              <div class="hub-details">
                <span class="hub-name"><?php echo htmlspecialchars($city['name']); ?>, Gujarat</span>
                <span class="hub-chip">Municipal City Vetted</span>
              </div>
            </div>
            <a href="select-city.php" class="edit-btn small" style="text-decoration:none;"><i class="fa-solid fa-pen"></i> Edit</a>
          </div>

          <!-- Pickup Address Input -->
          <div style="background: #fdfdfd; border: 1px solid #e2e8e5; border-radius: 12px; padding: 18px; margin: 18px 0;">
            <div class="section-label" style="font-weight: 700; color: #1e2624; margin-bottom: 8px;">
              <i class="fa-solid fa-map-location-dot" style="color: #1f8c5a;"></i> Pickup Address <b>*</b>
            </div>
            <input type="text" name="pickup_address" value="12 Patel Colony, Kalawad Road, <?php echo htmlspecialchars($city['name']); ?>" placeholder="Enter exact street / building / landmark address for volunteer pickup" style="width: 100%; padding: 12px 14px; border: 1px solid #c7d6cf; border-radius: 8px; font-size: 0.95rem;" required>

            <div style="margin-top: 12px;">
              <div class="section-label" style="font-weight: 700; color: #1e2624; margin-bottom: 6px;">
                <i class="fa-solid fa-phone" style="color: #1f8c5a;"></i> Contact Phone Number
              </div>
              <input type="text" name="contact_phone" value="<?php echo htmlspecialchars($user['phone'] ?? '+91 98765 43210'); ?>" placeholder="Mobile number for pickup coordination" style="width: 100%; padding: 12px 14px; border: 1px solid #c7d6cf; border-radius: 8px; font-size: 0.95rem;" required>
            </div>
          </div>

          <label class="consent-box">
            <input type="checkbox" required checked>
            <span>
              <strong>Please make sure the food details are correct before submitting your donation.</strong>
              <small>By submitting the donation, you confirm that the information provided is accurate and that the food is freshly prepared, hygienic, and safe to eat.</small>
              <small>Direct handshakes: Transmitted immediately to <?php echo htmlspecialchars($organization['name']); ?>’s intake queue under central administrative audit.</small>
            </span>
          </label>
        </section>
      </main>

      <aside class="confirmation-sidebar">
        <div class="summary-card">
          <div class="summary-head">
            <h3>Donation Summary</h3>
            <span class="status-pill"><i class="fa-solid fa-circle-dot"></i> Status: Pending</span>
          </div>

          <div class="summary-list">
            <div class="summary-row">
              <span>Total Servings</span>
              <strong>~<?php echo htmlspecialchars($draft['serves']); ?> Meals</strong>
            </div>
            <div class="summary-row">
              <span>Food Category</span>
              <strong><?php echo htmlspecialchars($draft['food_type']); ?></strong>
            </div>
            <div class="summary-row">
              <span>Assigned Center</span>
              <strong><?php echo htmlspecialchars($organization['name']); ?></strong>
            </div>
            <div class="summary-row">
              <span>Target Pickup Window</span>
              <strong><?php echo htmlspecialchars($draft['available_date']); ?> • <?php echo htmlspecialchars($draft['available_time']); ?></strong>
            </div>
            <div class="summary-row">
              <span>Municipal Jurisdiction</span>
              <strong><?php echo htmlspecialchars($city['name']); ?> Hub</strong>
            </div>
          </div>

          <div class="support-banner">
            <img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=900&q=80" alt="Volunteers supporting food distribution">
            <div class="support-caption">
              <strong>Your surplus food donation</strong>
              <p>helps provide nutritious meals to families across <?php echo htmlspecialchars($city['name']); ?> with dignity and care.</p>
            </div>
          </div>

          <button type="submit" class="submit-btn" style="cursor:pointer; width:100%; border:none;">Submit Donation <i class="fa-solid fa-arrow-right"></i></button>
          <a href="select-organization.php" class="back-link"><i class="fa-solid fa-arrow-left"></i> Back to Select Organization</a>

          <div class="contact-box">
            <span class="hotline"><i class="fa-solid fa-phone-volume"></i> Direct NGO Volunteer Hotline</span>
            <strong><?php echo htmlspecialchars($organization['phone'] ?? '+91 828145-FOOD'); ?></strong>
            <p>Logged under Food Bridge direct logistics protocol • No third-party dispatch</p>
          </div>
        </div>

        <div class="selected-org-card">
          <div class="selected-head">
            <div class="org-mark"><?php echo htmlspecialchars($organization['short_code'] ?? 'HF'); ?></div>
            <div class="selected-ok"><i class="fa-solid fa-circle-check"></i> Verified NGO distribution</div>
            <a href="select-organization.php" class="change-link" style="text-decoration:none;">Change</a>
          </div>

          <div class="org-name"><?php echo htmlspecialchars($organization['name']); ?></div>
          <div class="org-subtext"><?php echo htmlspecialchars($organization['rating_info'] ?? 'Admin Vetted Partner'); ?></div>
          <div class="org-address"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($organization['address']); ?></div>
          <p><?php echo htmlspecialchars($organization['description']); ?></p>
        </div>
      </aside>
    </div>
  </form>

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
  <script src="js/auth.js" defer></script>
</body>
</html>
