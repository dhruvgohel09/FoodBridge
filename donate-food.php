<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$draft = $_SESSION['donation_draft'] ?? [];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $food_name = sanitize($_POST['food_name'] ?? '');
    $food_type = sanitize($_POST['food_type'] ?? '');
    $quantity = floatval($_POST['quantity'] ?? 0);
    $unit = sanitize($_POST['unit'] ?? 'Kg');
    $serves = intval($_POST['serves'] ?? 1);
    $available_date = sanitize($_POST['available_date'] ?? date('Y-m-d'));
    $available_time = sanitize($_POST['available_time'] ?? '11:30');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($food_name) || empty($food_type) || $quantity <= 0 || $serves < 1) {
        $error = 'Please fill out all required fields marked with * correctly.';
    } else {
        $photoPath = $draft['food_photo'] ?? null;
        if (isset($_FILES['food_photo']) && $_FILES['food_photo']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['food_photo']['tmp_name'];
            $ext = strtolower(pathinfo($_FILES['food_photo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $filename = 'food_' . time() . '_' . mt_rand(100, 999) . '.' . $ext;
                $target = __DIR__ . '/uploads/donations/' . $filename;
                if (move_uploaded_file($tmpName, $target)) {
                    $photoPath = 'uploads/donations/' . $filename;
                }
            }
        }

        $_SESSION['donation_draft'] = array_merge($draft, [
            'food_name' => $food_name,
            'food_type' => $food_type,
            'quantity' => $quantity,
            'unit' => $unit,
            'serves' => $serves,
            'available_date' => $available_date,
            'available_time' => $available_time,
            'food_photo' => $photoPath,
            'notes' => $notes,
        ]);

        header('Location: select-city.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Share surplus food with your local community through Food Bridge.">
  <title>Donate Food | Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/app-header.css">
  <link rel="stylesheet" href="css/donate-food.css">
</head>
<body class="donate-food-page">
  <?php $activeHeaderPage = 'donate'; include __DIR__ . '/includes/app-header.php'; ?>
  <main class="donate-main">
    <div class="donate-breadcrumb"><span>DONATE FOOD</span><i class="fa-solid fa-chevron-right" aria-hidden="true"></i><strong>STEP 1: FOOD DETAILS</strong></div>
    <section class="donate-intro">
      <h1>Donate Food</h1>
      <p>Share your extra food and help someone enjoy a good meal. Direct, transparent, and community-powered.</p>
    </section>

    <section class="donate-steps" aria-label="Donation progress">
      <div class="donate-step current"><span class="step-icon"><i class="fa-solid fa-check" aria-hidden="true"></i></span><span><small>STEP 1</small><strong>Food Details</strong></span></div>
      <div class="donate-step"><span class="step-icon">2</span><span><small>STEP 2</small><strong>Select City</strong></span></div>
      <div class="donate-step"><span class="step-icon">3</span><span><small>STEP 3</small><strong>Select Organization</strong></span></div>
      <div class="donate-step"><span class="step-icon">4</span><span><small>STEP 4</small><strong>Confirmation</strong></span></div>
    </section>

    <?php if (!empty($error)): ?>
      <div style="background: #fde8e8; color: #9b1c1c; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; border: 1px solid #f8b4b4;">
        <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <div class="donate-layout">
      <aside class="donate-sidebar">
        <article class="pledge-card">
          <div class="pledge-photo">
            <img src="https://images.unsplash.com/photo-1555244162-803834f70033?auto=format&fit=crop&w=900&q=85" alt="Fresh meals prepared for community sharing">
            <span><i class="fa-solid fa-truck-fast" aria-hidden="true"></i> Community Nourishment</span>
          </div>
          <div class="pledge-copy">
            <h2>Pledge Clean Surplus</h2>
            <p>Every box of fresh, untouched food logged here gets routed directly to vetted local relief centers.</p>
            <span class="pledge-proof"><i class="fa-regular fa-circle-check" aria-hidden="true"></i> 100% Fresh &amp; Zero Middlemen</span>
          </div>
        </article>
        <article class="safety-checklist">
          <h2><i class="fa-solid fa-shield-heart" aria-hidden="true"></i> Safety Checklist</h2>
          <ul>
            <li><i class="fa-regular fa-circle-check" aria-hidden="true"></i> Prepared within safe consumption window (4–6 hours)</li>
            <li><i class="fa-regular fa-circle-check" aria-hidden="true"></i> Clean, food-grade packaging for all containers</li>
            <li><i class="fa-regular fa-circle-check" aria-hidden="true"></i> No expired packaged or partially consumed food</li>
          </ul>
        </article>
      </aside>

      <form class="food-details-form" action="donate-food.php" method="POST" enctype="multipart/form-data">
        <div class="food-name-row">
          <label class="form-field food-name-field">
            <span>Food Name <b>*</b></span>
            <input name="food_name" type="text" placeholder="e.g. Rice, Roti, Dal Tadka &amp; Vegetable Curry" value="<?php echo htmlspecialchars($draft['food_name'] ?? 'Rice, Roti, Dal & Vegetable Curry'); ?>" required>
          </label>
          <span class="form-hint">e.g., Prepared meal or ration kit</span>
        </div>
        <div class="suggestions">
          <span>Suggestions:</span>
          <button type="button">+ Packed Thalis</button>
          <button type="button">+ Khichdi &amp; Sabzi</button>
          <button type="button">+ Dry Ration Kit</button>
          <button type="button">+ Fresh Fruits</button>
        </div>
        <label class="form-field food-type-field">
          <span>Food Type <b>*</b></span>
          <select name="food_type" required>
            <option value="">Select category of food...</option>
            <?php
            $types = ['Prepared Meals', 'Cooked Food', 'Dry Ration', 'Fresh Produce', 'Packaged Food'];
            $selectedType = $draft['food_type'] ?? 'Prepared Meals';
            foreach ($types as $t): ?>
              <option value="<?php echo $t; ?>" <?php echo $selectedType === $t ? 'selected' : ''; ?>><?php echo $t; ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div class="form-grid three-columns">
          <label class="form-field">
            <span>Quantity &amp; Unit <b>*</b></span>
            <span class="quantity-control">
              <input name="quantity" type="number" min="1" step="0.5" value="<?php echo htmlspecialchars($draft['quantity'] ?? '25'); ?>" placeholder="25" required>
              <select name="unit" aria-label="Quantity unit">
                <?php
                $units = ['Meals', 'Kg', 'Boxes'];
                $selectedUnit = $draft['unit'] ?? 'Meals';
                foreach ($units as $u): ?>
                  <option value="<?php echo $u; ?>" <?php echo $selectedUnit === $u ? 'selected' : ''; ?>><?php echo $u; ?></option>
                <?php endforeach; ?>
              </select>
            </span>
          </label>
          <label class="form-field">
            <span>Serves (Approx. People) <b>*</b></span>
            <input name="serves" type="number" min="1" value="<?php echo htmlspecialchars($draft['serves'] ?? '25'); ?>" placeholder="e.g. 25 people" required>
          </label>
          <label class="form-field">
            <span>Available Date <b>*</b></span>
            <input name="available_date" type="date" value="<?php echo htmlspecialchars($draft['available_date'] ?? date('Y-m-d')); ?>" required>
          </label>
          <label class="form-field">
            <span>Available Time (Pickup window) <b>*</b></span>
            <input name="available_time" type="time" value="<?php echo htmlspecialchars($draft['available_time'] ?? '12:30'); ?>" required>
          </label>
        </div>
        <div class="photo-field">
          <div class="field-label-row"><label for="food-photo">Food Photo <small>(Optional)</small></label><span>Helps distribution centers verify hygiene</span></div>
          <label class="upload-box" for="food-photo">
            <span class="upload-icon"><i class="fa-solid fa-camera" aria-hidden="true"></i></span>
            <strong>Upload a clear photo of the food</strong>
            <small><?php echo !empty($draft['food_photo']) ? 'Selected: ' . htmlspecialchars(basename($draft['food_photo'])) : 'Drag & drop file here, or click to browse (PNG, JPG up to 5MB)'; ?></small>
            <span class="choose-file"><i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i> Choose Image</span>
          </label>
          <input class="file-input" id="food-photo" name="food_photo" type="file" accept="image/png,image/jpeg,image/webp">
        </div>
        <label class="form-field notes-field">
          <span>Additional Notes <small>(Optional)</small></span>
          <textarea name="notes" placeholder="Add any additional details (e.g., Prepared at 11:30 AM, vegetarian, stored warm in hygienic catering containers)..."><?php echo htmlspecialchars($draft['notes'] ?? 'Freshly prepared food. Suitable for children and adults. Packed in food-grade foil containers.'); ?></textarea>
          <small>e.g., Freshly prepared food. Suitable for both children and adults.</small>
        </label>
        <aside class="food-guidelines">
          <span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span>
          <div><strong>Food Safety &amp; Quality Guidelines</strong><p>Please donate only fresh and safe-to-eat food. Avoid donating spoiled, sour, or stale food. Ensure meals are packed cleanly in leak-proof, hygienic containers to protect recipient health.</p></div>
        </aside>
        <div class="form-actions">
          <a class="cancel-action" href="dashboard.php">Cancel</a>
          <button class="continue-action" type="submit">Continue to Select City <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
        </div>
      </form>
    </div>
  </main>
  <footer class="donate-footer">
    <div class="donate-footer-inner">
      <div><a class="donate-footer-brand" href="dashboard.php"><span class="app-header-mark"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span> Food Bridge</a><p>Connecting surplus food with community hunger through<br> transparent, reliable local logistics.</p></div>
      <nav aria-label="Footer navigation"><a href="about.php">About</a><a href="about.php#how-it-works">How It Works</a><a href="dashboard.php">Dashboard</a></nav>
    </div>
    <div class="donate-footer-bottom">
      <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. | Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
      <p>Community Food Sharing across Gujarat</p>
    </div>
  </footer>
  <script src="js/auth.js" defer></script>
  <script src="js/donate-food.js" defer></script>
</body>
</html>
