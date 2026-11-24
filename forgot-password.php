<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/mailer.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawIdentifier = sanitize($_POST['identifier'] ?? '');

    if (empty($rawIdentifier)) {
        $errors['identifier'] = 'Please enter your registered email address or mobile number.';
    } else {
        $isEmail = str_contains($rawIdentifier, '@');
        $identifier = $rawIdentifier;

        // Strict Email validation if input looks like an email
        if ($isEmail) {
            $validation = validateEmailAddress($rawIdentifier);
            if (!$validation['valid']) {
                $errors['identifier'] = 'The email address you entered is not valid. Please check your spelling and try again.';
            } else {
                $identifier = $validation['email'];
            }
        }

        if (empty($errors)) {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, name, email, phone FROM users WHERE email = ? OR phone = ? OR phone LIKE ? LIMIT 1");
            $cleanPhone = preg_replace('/[^0-9]/', '', $identifier);
            $phonePattern = '%' . (strlen($cleanPhone) >= 10 ? substr($cleanPhone, -10) : $cleanPhone) . '%';
            $stmt->execute([$identifier, $identifier, $phonePattern]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate 6-digit secure numeric OTP
                $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
                // Invalidate any existing unused OTPs for this email
                $db->prepare("UPDATE password_resets SET is_verified = 2 WHERE identifier = ? AND is_verified = 0")->execute([$user['email']]);

                // Store new OTP with 15-minute validity
                $ins = $db->prepare("INSERT INTO password_resets (identifier, otp, is_verified, expires_at) VALUES (?, ?, 0, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
                $ins->execute([$user['email'], $otp]);

                // Dispatch OTP email to user's registered email
                $mailSent = sendOtpEmail($user['email'], $user['name'], $otp, 'Password Recovery');

                $_SESSION['reset_identifier'] = $user['email'];
                $_SESSION['reset_name'] = $user['name'];
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_otp_time'] = time();
                $_SESSION['demo_otp'] = $otp;

                $masked = maskEmail($user['email']);
                if ($mailSent) {
                    setFlash('info', "A 6-digit verification code has been dispatched to <strong>{$masked}</strong>. Please check your inbox and spam folder.");
                } else {
                    setFlash('info', "A 6-digit verification code has been generated for <strong>{$masked}</strong>. (Verification code: <strong>{$otp}</strong>).");
                }
                header('Location: verify-otp.php');
                exit;
            } else {
                $errors['identifier'] = 'No registered Food Bridge account found with that email or mobile number.';
            }
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
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>" />
    <link rel="stylesheet" href="css/auth.css?v=<?php echo filemtime(__DIR__ . '/css/auth.css'); ?>" />
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
          <div class="recovery-icon"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i><span><i class="fa-solid fa-envelope" aria-hidden="true"></i></span></div>
          <h1 id="forgot-heading">Forgot Password?</h1>
          <p class="recovery-description">Enter your registered email address or mobile number to receive a 6-digit verification code directly to your email.</p>

          <?php if ($flash): ?>
            <div style="background: #def7ec; color: #03543f; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #bcf0da;">
              <i class="fa-solid fa-circle-info"></i> <?php echo $flash['message']; ?>
            </div>
          <?php endif; ?>

          <form class="auth-form recovery-form" method="POST" action="forgot-password.php" data-auth-form="forgot" novalidate>
            <label class="field-label" for="recovery-identifier">Registered Email or Mobile Number <span>*</span></label>
            <div class="input-wrap <?php echo isset($errors['identifier']) ? 'has-error' : ''; ?>" <?php if (isset($errors['identifier'])): ?>style="border: 1.5px solid #dc2626 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15) !important;"<?php endif; ?>>
              <i class="fa-solid fa-at" aria-hidden="true"></i>
              <input id="recovery-identifier" name="identifier" type="text" placeholder="e.g. name@gmail.com" value="<?php echo htmlspecialchars($_POST['identifier'] ?? ''); ?>" required />
            </div>
            <?php if (!empty($errors['identifier'])): ?>
              <span class="field-error-text" style="display:block; color:#dc2626 !important; font-size:12px !important; font-weight:600 !important; margin-top:5px !important; margin-bottom:5px !important; line-height:1.4 !important; text-align:left !important;"><?php echo $errors['identifier']; ?></span>
            <?php endif; ?>
            <button class="button button-green auth-submit" type="submit">Send Verification Code <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            <p class="form-message" role="status" aria-live="polite"></p>
          </form>
          <p class="recovery-switch">Remember your password? <a href="login.php">Login <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
          <div class="recovery-security"><i class="fa-solid fa-shield-halved" aria-hidden="true"></i><span>Account recovery is protected with single-use, 15-minute OTP tokens delivered to verified email addresses.</span></div>
        </section>
        <div style="text-align: center; margin-top: 24px; color: #71807d; font-size: 0.85rem;">
          <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. • Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
        </div>
      </div>
    </main>
    <script src="js/auth.js?v=<?php echo filemtime(__DIR__ . '/js/auth.js'); ?>" defer></script>
  </body>
</html>
