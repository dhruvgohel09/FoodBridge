<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $food_type = sanitize($_POST['food_type'] ?? '');
    $dietary_category = sanitize($_POST['dietary_category'] ?? 'Vegetarian');
    $people_count = intval($_POST['people_count'] ?? 1);
    $city = sanitize($_POST['city'] ?? 'Rajkot');
    $required_date = sanitize($_POST['required_date'] ?? date('Y-m-d'));
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($food_type) || $people_count < 1 || empty($city) || empty($required_date)) {
        $error = 'Please fill out all required fields marked with *.';
    } else {
        $db = getDB();
        // Generate unique request code
        $countStmt = $db->query("SELECT COUNT(*) FROM food_requests");
        $nextNum = intval($countStmt->fetchColumn()) + 1;
        $requestCode = 'FB-REQ-' . date('Y') . '-' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);

        // Pick an organization in that city if available
        $orgStmt = $db->prepare("SELECT id FROM organizations WHERE city_id = (SELECT id FROM cities WHERE name = ? LIMIT 1) LIMIT 1");
        $orgStmt->execute([$city]);
        $assignedOrgId = $orgStmt->fetchColumn() ?: 1;

        $stmt = $db->prepare("INSERT INTO food_requests (request_code, user_id, food_type, dietary_category, people_count, city, required_date, notes, assigned_org_id, contact_window, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Today, 1:30 PM', 'Pending')");
        try {
            $stmt->execute([$requestCode, $user['id'], $food_type, $dietary_category, $people_count, $city, $required_date, $notes, $assignedOrgId]);
            setFlash('success', "Your food request ({$requestCode}) has been successfully submitted! Community hubs will be alerted.");
            header('Location: my-requests.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Request food support through Food Bridge in a respectful, community-first process.">
  <title>Need Food | Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/app-header.css">
  <link rel="stylesheet" href="css/need-food.css">
</head>
<body class="need-food-page">
  <?php $activeHeaderPage = 'need'; include __DIR__ . '/includes/app-header.php'; ?>

  <main class="need-food-shell">
    <div class="need-food-container">
      <div class="breadcrumb-row">
        <a href="dashboard.php" style="color: inherit;">Home</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Need Food</span>
        <i class="fa-solid fa-chevron-right"></i>
        <strong>Request Form</strong>
      </div>

      <header class="need-food-header">
        <div class="need-food-title-wrap">
          <h1>Need Food?</h1>
          <p>Submit a simple request if you or your family need food support. Verified local communities and organizations will review and allocate available surplus meals.</p>
        </div>
        <span class="stigma-pill"><i class="fa-solid fa-shield-heart"></i> Zero Stigma • 100% Free Service</span>
      </header>

      <?php if (!empty($error)): ?>
        <div style="background: #fde8e8; color: #9b1c1c; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; border: 1px solid #f8b4b4;">
          <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
        </div>
      <?php endif; ?>

      <div class="need-food-layout">
        <aside class="need-food-support">
          <div class="support-photo">
            <img src="https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=1200&q=80" alt="Community meal support">
            <div class="support-photo-overlay">
              <span class="mini-badge">Food Bridge</span>
              <strong>Community Kitchen Relief</strong>
              <small><?php echo htmlspecialchars($user['city'] ?? 'Rajkot'); ?> Community Center</small>
            </div>
          </div>

          <div class="request-process">
            <h2><i class="fa-solid fa-hand-holding-heart"></i> How Food Request Works</h2>
            <ol>
              <li>
                <span class="step-number">1</span>
                <div>
                  <strong>Submit Request</strong>
                  <p>Fill in your food type, number of people, and your city.</p>
                </div>
              </li>
              <li>
                <span class="step-number">2</span>
                <div>
                  <strong>Admin &amp; Partner Audit</strong>
                  <p>Community volunteers or local kitchens check available surplus meals.</p>
                </div>
              </li>
              <li>
                <span class="step-number">3</span>
                <div>
                  <strong>Hand off &amp; Collect</strong>
                  <p>Receive support from the nearest verified community hub or meal center.</p>
                </div>
              </li>
            </ol>
          </div>
        </aside>

        <section class="request-form-panel" aria-label="Food request form">
          <div class="form-header">
            <h2>Food Request Details</h2>
            <button type="button" class="icon-button" aria-label="Edit form"><i class="fa-solid fa-pen"></i></button>
          </div>
          <p class="form-subtitle">Please enter your requirements below to help us coordinate appropriate meals.</p>

          <form class="food-request-form" action="need-food.php" method="POST">
            <div class="fields-row two-columns">
              <label class="form-field">
                <span>Food Type <b>*</b></span>
                <select name="food_type" required>
                  <option value="">Select food type...</option>
                  <option value="Cooked Food" selected>Cooked Food (Rice, Roti, Dal, Sabzi)</option>
                  <option value="Dry Ration">Dry Ration (Wheat, Rice, Pulses)</option>
                  <option value="Fresh Produce">Fresh Produce (Vegetables, Fruits)</option>
                  <option value="Ready-to-Eat Meals">Ready-to-Eat Packed Meals</option>
                </select>
              </label>

              <label class="form-field">
                <span>Dietary Category <b>*</b></span>
                <select name="dietary_category">
                  <option value="Vegetarian" selected>Vegetarian</option>
                </select>
                <small style="color: #15803d; font-weight: 500;"><i class="fa-solid fa-leaf"></i> Only Pure Vegetarian food is provided</small>
              </label>
            </div>

            <div class="fields-row two-columns">
              <label class="form-field">
                <span>Number of People <b>*</b></span>
                <input type="number" name="people_count" min="1" max="500" value="4" placeholder="4" required>
                <small>How many individuals will this request feed?</small>
              </label>

              <label class="form-field">
                <span>City <b>*</b></span>
                <select name="city" required>
                  <?php
                  $cities = ['Rajkot', 'Ahmedabad', 'Surat', 'Vadodara', 'Junagadh', 'Bhavnagar', 'Gandhinagar'];
                  $userCity = $user['city'] ?? 'Rajkot';
                  foreach ($cities as $c): ?>
                    <option value="<?php echo $c; ?>" <?php echo $c === $userCity ? 'selected' : ''; ?>><?php echo $c; ?></option>
                  <?php endforeach; ?>
                </select>
                <small>Where would food be received or coordinated?</small>
              </label>
            </div>

            <label class="form-field">
              <span>Required Date <b>*</b></span>
              <div class="input-icon-wrap">
                <i class="fa-regular fa-calendar"></i>
                <input type="date" name="required_date" value="<?php echo date('Y-m-d'); ?>" required>
              </div>
              <small>When do you need the food support?</small>
            </label>

            <label class="form-field">
              <span>Additional Notes <small>(Optional)</small></span>
              <textarea name="notes" placeholder="e.g., Warm dinner meals for a family of 4 (Rice, Roti, Dal, Sabzi)..." rows="4"></textarea>
            </label>

            <div class="privacy-banner">
              <i class="fa-solid fa-shield-heart"></i>
              <div>
                <strong>Privacy &amp; Community Care</strong>
                <p>Your request is handled with full respect and confidentiality. Only authorized municipal or community coordinators review your location details.</p>
              </div>
            </div>

            <div class="form-actions">
              <a href="dashboard.php" class="btn-secondary" style="display:inline-block; text-align:center; padding: 12px 24px; text-decoration:none;">Cancel</a>
              <button type="submit" class="btn-primary">Submit Request <i class="fa-solid fa-arrow-right"></i></button>
            </div>
          </form>
        </section>
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
        <a href="need-food.php">Need Food</a>
        <a href="donate-food.php">Donate Food</a>
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
