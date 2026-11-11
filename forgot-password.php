<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = sanitize($_POST['identifier'] ?? '');

    if (empty($identifier)) {
        $error = 'Please enter your registered email address or mobile number.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, phone FROM users WHERE email = ? OR phone = ? OR phone LIKE ? LIMIT 1");
        $cleanPhone = preg_replace('/[^0-9]/', '', $identifier);
        $phonePattern = '%' . (strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone) . '%';
        $stmt->execute([$identifier, $identifier, $phonePattern]);
        $user = $stmt->fetch();

        if ($user) {
            $otp = str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
            $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

            $ins = $db->prepare("INSERT INTO password_resets (identifier, otp, is_verified, expires_at) VALUES (?, ?, 0, ?)");
            $ins->execute([$identifier, $otp, $expiresAt]);

            $_SESSION['reset_identifier'] = $identifier;
            $_SESSION['demo_otp'] = $otp; // Saved for friendly demo display on verify screen
            setFlash('info', "A 6-digit verification code has been generated. (Demo Code: <strong>{$otp}</strong>)");
            header('Location: verify-otp.php');
            exit;
        } else {
            $error = 'No registered Food Bridge account found with that email or phone number.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Start the Food Bridge password recovery process." />
    <title>Forgot Password | Food Bridge</title>
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
    <main class="recovery-main">
      <div class="container recovery-container">
        <section class="recovery-card forgot-card" aria-labelledby="forgot-heading">
          <a class="back-link" href="login.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Login</a>
          <div class="recovery-icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span><i class="fa-solid fa-check" aria-hidden="true"></i></span></div>
          <h1 id="forgot-heading">Forgot Password?</h1>
          <p class="recovery-description">Enter your registered email address or mobile number to receive a 6-digit verification OTP.</p>

          <?php if (!empty($error)): ?>
            <div style="background: #fde8e8; color: #9b1c1c; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #f8b4b4;">
              <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
          <?php endif; ?>

          <form class="auth-form recovery-form" method="POST" action="forgot-password.php" novalidate>
            <label class="field-label" for="recovery-identifier">Registered Email or Mobile Number <span>*</span></label>
            <div class="input-wrap">
              <i class="fa-solid fa-at" aria-hidden="true"></i>
              <input id="recovery-identifier" name="identifier" type="text" placeholder="e.g. dhruv.gohel@example.com" value="<?php echo htmlspecialchars($_POST['identifier'] ?? 'dhruv.gohel@example.com'); ?>" required />
            </div>
            <button class="button button-green auth-submit" type="submit">Send Verification OTP <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            <p class="form-message" role="status" aria-live="polite"></p>
          </form>
          <p class="recovery-switch">Remember your password? <a href="login.php">Login <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
          <div class="recovery-security"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>Account recovery is protected by timed single-use security tokens.</span></div>
        </section>
        <div style="text-align: center; margin-top: 24px; color: #71807d; font-size: 0.85rem;">
          <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. • Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
        </div>
      </div>
    </main>
    <script src="js/auth.js" defer></script>
  </body>
</html>
