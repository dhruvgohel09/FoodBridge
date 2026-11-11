<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$identifier = $_SESSION['reset_identifier'] ?? '';
$error = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = 'Please enter and confirm your new password.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        if (!empty($identifier)) {
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE email = ? OR phone = ? OR phone LIKE ?");
            $cleanPhone = preg_replace('/[^0-9]/', '', $identifier);
            $phonePattern = '%' . (strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone) . '%';
            $stmt->execute([$hashed, $identifier, $identifier, $phonePattern]);
        } else {
            // Fallback for demo user if reached directly
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = 1");
            $stmt->execute([$hashed]);
        }

        unset($_SESSION['reset_identifier']);
        unset($_SESSION['demo_otp']);
        unset($_SESSION['otp_verified']);

        setFlash('success', 'Your password has been successfully reset! You can now log in with your new password.');
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Set a new password for your Food Bridge account." />
    <title>Reset Password | Food Bridge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/auth.css" />
  </head>
  <body class="auth-page recovery-page">
    <header class="header">
      <div class="container header-inner">
        <a class="logo" href="index.php#home" aria-label="Food Bridge home">
          <span class="logo-mark" aria-hidden="true"><i class="fa-solid fa-seedling"></i></span>
          <span class="logo-text"><strong>Food Bridge</strong><small>Community Food Sharing</small></span>
        </a>
        <button class="menu-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="main-nav"><span></span><span></span><span></span></button>
        <nav class="nav" id="main-nav" aria-label="Main navigation">
          <a href="index.php#home">Home</a><a href="index.php#about">About</a><a href="index.php#how-it-works">How It Works</a>
        </nav>
        <div class="header-actions"><a class="login-link" href="login.php">Login</a><a class="btn btn-green btn-small" href="register.php">Register</a></div>
      </div>
    </header>
    <main class="recovery-main reset-main">
      <div class="container">
        <section class="recovery-card reset-card" aria-labelledby="reset-heading">
          <span class="verified-pill"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Identity Verified</span>
          <div class="recovery-icon reset-icon"><i class="fa-solid fa-lock" aria-hidden="true"></i><span><i class="fa-solid fa-check" aria-hidden="true"></i></span></div>
          <h1 id="reset-heading">Reset Your Password</h1>
          <p class="recovery-description">Choose a strong, new password for your Food Bridge account.</p>

          <?php if ($flash): ?>
            <div style="background: #def7ec; color: #03543f; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #bcf0da;">
              <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($flash['message']); ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($error)): ?>
            <div style="background: #fde8e8; color: #9b1c1c; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #f8b4b4;">
              <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
          <?php endif; ?>

          <form class="auth-form recovery-form reset-form" method="POST" action="reset-password.php" data-auth-form="reset" novalidate>
            <label class="field-label" for="new-password">New Password</label>
            <div class="input-wrap">
              <i class="fa-solid fa-lock" aria-hidden="true"></i>
              <input id="new-password" name="password" type="password" placeholder="Enter at least 6 characters" autocomplete="new-password" minlength="6" required />
              <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
            </div>
            <label class="field-label" for="confirm-new-password">Confirm New Password</label>
            <div class="input-wrap">
              <i class="fa-solid fa-lock" aria-hidden="true"></i>
              <input id="confirm-new-password" name="confirm_password" type="password" placeholder="Re-enter your new password" autocomplete="new-password" minlength="6" required />
              <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
            </div>
            <button class="button button-green auth-submit" type="submit">Reset Password &amp; Log In <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            <p class="form-message" role="status" aria-live="polite"></p>
          </form>
          <a class="cancel-link" href="login.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Cancel and return to Login</a>
        </section>
        <div style="text-align: center; margin-top: 24px; color: #71807d; font-size: 0.85rem;">
          <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. • Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
        </div>
      </div>
    </main>
    <script src="js/auth.js" defer></script>
  </body>
</html>
