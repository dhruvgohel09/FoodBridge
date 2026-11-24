<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();

$user = currentUser();
$db = getDB();

$flash = getFlash();
$errors = [];
$isEditing = isset($_GET['edit']) && $_GET['edit'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $city = sanitize($_POST['city'] ?? '');
    $new_password = $_POST['new_password'] ?? '';

    if (empty($name)) {
        $errors['name'] = 'Full name is required.';
        $isEditing = true;
    }

    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (empty($phone)) {
        $errors['phone'] = 'Mobile number is required.';
        $isEditing = true;
    } elseif (strlen($cleanPhone) < 10) {
        $errors['phone'] = 'Please enter a valid 10-digit mobile number.';
        $isEditing = true;
    }

    if (empty($city)) {
        $errors['city'] = 'Please select your city.';
        $isEditing = true;
    }

    if (!empty($new_password) && strlen($new_password) < 6) {
        $errors['new_password'] = 'New password must be at least 6 characters.';
        $isEditing = true;
    }

    if (empty($errors)) {
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, city = ?, password = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $city, $hashed, $user['id']]);
        } else {
            $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, city = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $city, $user['id']]);
        }
        setFlash('success', 'Profile updated successfully!');
        header('Location: profile.php');
        exit;
    }
}

// User avatar initials
$nameParts = preg_split('/\s+/', trim($user['name']));
$initials = 'DG';
if (!empty($nameParts)) {
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $initials .= mb_strtoupper(mb_substr(end($nameParts), 0, 1));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage your Food Bridge profile and personal information.">
  <title>My Profile | Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>">
  <link rel="stylesheet" href="css/app-header.css?v=<?php echo filemtime(__DIR__ . '/css/app-header.css'); ?>">
  <link rel="stylesheet" href="css/profile.css?v=<?php echo filemtime(__DIR__ . '/css/profile.css'); ?>">
</head>
<body class="profile-page">
  <?php $activeHeaderPage = 'profile'; include __DIR__ . '/includes/app-header.php'; ?>

  <main class="profile-shell">
    <div class="profile-container">
      <div class="breadcrumb-row">
        <a href="dashboard.php" style="color: inherit;">Home</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>User Profile</span>
      </div>

      <header class="profile-header">
        <div class="profile-header-copy">
          <h1>My Profile</h1>
          <p>Manage your personal information, contact credentials, and account settings.</p>
        </div>
        <span class="verified-pill"><i class="fa-solid fa-check"></i> Verified Community Member</span>
      </header>

      <?php if ($flash): ?>
        <div style="background: #def7ec; color: #03543f; padding: 14px 18px; border-radius: 12px; margin-bottom: 20px; font-size: 0.95rem; border: 1px solid #bcf0da;">
          <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash['message']); ?>
        </div>
      <?php endif; ?>

        <?php if ($isEditing): ?>
          <form method="POST" action="profile.php" style="margin-top: 20px;" novalidate>
            <div class="profile-fields">
              <div class="profile-field">
                <span class="field-label">Full Name <b>*</b></span>
                <input class="<?php echo isset($errors['name']) ? 'has-error' : ''; ?>" type="text" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? $user['name']); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #c8d6d0; border-radius: 8px; font-size: 0.95rem;" required>
                <?php if (!empty($errors['name'])): ?>
                  <span class="field-error-text"><?php echo htmlspecialchars($errors['name']); ?></span>
                <?php endif; ?>
              </div>
              <div class="profile-field">
                <span class="field-label">Mobile Number <b>*</b></span>
                <input class="<?php echo isset($errors['phone']) ? 'has-error' : ''; ?>" type="text" name="phone" value="<?php echo htmlspecialchars($_POST['phone'] ?? $user['phone']); ?>" style="width: 100%; padding: 10px 14px; border: 1px solid #c8d6d0; border-radius: 8px; font-size: 0.95rem;" required>
                <?php if (!empty($errors['phone'])): ?>
                  <span class="field-error-text"><?php echo htmlspecialchars($errors['phone']); ?></span>
                <?php endif; ?>
              </div>
              <div class="profile-field full">
                <span class="field-label">Email Address (Read-only)</span>
                <div class="field-content" style="background: #f2f5f3;"><?php echo htmlspecialchars($user['email']); ?></div>
              </div>
              <div class="profile-field full">
                <span class="field-label">Primary Distribution Region (City) <b>*</b></span>
                <select class="<?php echo isset($errors['city']) ? 'has-error' : ''; ?>" name="city" style="width: 100%; padding: 10px 14px; border: 1px solid #c8d6d0; border-radius: 8px; font-size: 0.95rem;" required>
                  <?php
                  $cities = ['Rajkot', 'Ahmedabad', 'Surat', 'Vadodara', 'Junagadh', 'Bhavnagar', 'Gandhinagar', 'Jamnagar', 'Anand', 'Morbi'];
                  $curCity = $_POST['city'] ?? $user['city'];
                  foreach ($cities as $c): ?>
                    <option value="<?php echo $c; ?>" <?php echo $curCity === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                  <?php endforeach; ?>
                </select>
                <?php if (!empty($errors['city'])): ?>
                  <span class="field-error-text"><?php echo htmlspecialchars($errors['city']); ?></span>
                <?php endif; ?>
              </div>
              <div class="profile-field full">
                <span class="field-label">Change Password <small>(Leave blank to keep existing password)</small></span>
                <input class="<?php echo isset($errors['new_password']) ? 'has-error' : ''; ?>" type="password" name="new_password" placeholder="Enter new password (optional)" minlength="6" style="width: 100%; padding: 10px 14px; border: 1px solid #c8d6d0; border-radius: 8px; font-size: 0.95rem;">
                <?php if (!empty($errors['new_password'])): ?>
                  <span class="field-error-text"><?php echo htmlspecialchars($errors['new_password']); ?></span>
                <?php endif; ?>
              </div>
            </div>

            <div class="profile-actions" style="margin-top: 24px;">
              <a href="profile.php" class="btn-outline" style="text-decoration:none;">Cancel</a>
              <button type="submit" class="btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Changes</button>
            </div>
          </form>
        <?php else: ?>
          <div class="profile-fields">
            <div class="profile-field">
              <span class="field-label">Full Name</span>
              <div class="field-content"><?php echo htmlspecialchars($user['name']); ?></div>
            </div>
            <div class="profile-field">
              <span class="field-label">Mobile Number</span>
              <div class="field-content phone"><?php echo htmlspecialchars($user['phone']); ?></div>
            </div>
            <div class="profile-field full">
              <span class="field-label">Email Address</span>
              <div class="field-content"><?php echo htmlspecialchars($user['email']); ?></div>
            </div>
            <div class="profile-field full">
              <span class="field-label">Primary Distribution Region</span>
              <div class="field-content"><?php echo htmlspecialchars($user['city']); ?>, Gujarat</div>
            </div>
          </div>

          <div class="profile-actions">
            <a href="logout.php" class="btn-outline" style="text-decoration:none;"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
            <a href="profile.php?edit=1" class="btn-primary" style="text-decoration:none;"><i class="fa-solid fa-pen"></i> Edit Profile</a>
          </div>
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

  <script src="js/auth.js?v=<?php echo filemtime(__DIR__ . '/js/auth.js'); ?>" defer></script>
</body>
</html>
