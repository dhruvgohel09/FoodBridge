<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/mailer.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$identifier = $_SESSION['reset_identifier'] ?? '';
$demoOtp = $_SESSION['demo_otp'] ?? '';
$error = '';
$flash = getFlash();

// Redirect back to forgot-password if no recovery session exists
if (empty($identifier)) {
    setFlash('info', 'Please submit your registered email or mobile number to begin password recovery.');
    header('Location: forgot-password.php');
    exit;
}

// Handle Resend OTP Request
if (isset($_GET['resend']) && $_GET['resend'] === '1') {
    $lastSent = $_SESSION['reset_otp_time'] ?? 0;
    $timeSinceLast = time() - $lastSent;
    $cooldown = 30; // seconds

    if ($timeSinceLast < $cooldown) {
        $wait = $cooldown - $timeSinceLast;
        setFlash('warning', "Please wait {$wait} seconds before requesting another verification code.");
    } else {
        $db = getDB();
        $otp = str_pad((string)random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Invalidate old unverified codes
        $db->prepare("UPDATE password_resets SET is_verified = 2 WHERE identifier = ? AND is_verified = 0")->execute([$identifier]);

        // Insert new code
        $db->prepare("INSERT INTO password_resets (identifier, otp, is_verified, expires_at) VALUES (?, ?, 0, ?)")->execute([$identifier, $otp, $expiresAt]);

        $name = $_SESSION['reset_name'] ?? 'Community Member';
        $sent = sendOtpEmail($identifier, $name, $otp, 'Password Recovery');

        $_SESSION['demo_otp'] = $otp;
        $_SESSION['reset_otp_time'] = time();

        $masked = maskEmail($identifier);
        if ($sent) {
            setFlash('success', "A new 6-digit verification code has been dispatched to <strong>{$masked}</strong>. Please check your inbox and spam folder.");
        } else {
            setFlash('info', "A new verification code was generated for <strong>{$masked}</strong>: <strong>{$otp}</strong>.");
        }
    }
    header('Location: verify-otp.php');
    exit;
}

// Handle OTP Verification Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = preg_replace('/[^0-9]/', '', sanitize($_POST['otp'] ?? ''));
    if (empty($otp) && !empty($_POST['otp_digits']) && is_array($_POST['otp_digits'])) {
        $otp = preg_replace('/[^0-9]/', '', implode('', $_POST['otp_digits']));
    }

    if (empty($otp) || strlen($otp) !== 6) {
        $error = 'Please enter the complete 6-digit verification code.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, identifier FROM password_resets WHERE otp = ? AND identifier = ? AND is_verified = 0 AND expires_at > NOW() ORDER BY id DESC LIMIT 1");
        $stmt->execute([$otp, $identifier]);
        $record = $stmt->fetch();

        if ($record || ($demoOtp && $otp === $demoOtp)) {
            if ($record) {
                $upd = $db->prepare("UPDATE password_resets SET is_verified = 1 WHERE id = ?");
                $upd->execute([$record['id']]);
            }
            $_SESSION['otp_verified'] = true;
            setFlash('success', 'Identity successfully verified! Please create your new secure password.');
            header('Location: reset-password.php');
            exit;
        } else {
            $error = 'Invalid or expired verification code. Please check your email or click Resend Code below.';
        }
    }
}

$timeSinceLast = time() - ($_SESSION['reset_otp_time'] ?? 0);
$cooldownLeft = max(0, 30 - $timeSinceLast);
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="Verify your identity to continue recovering your Food Bridge account." />
    <title>Verify OTP | Food Bridge</title>
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
      <div class="container otp-container">
        <a class="back-link otp-back-link" href="forgot-password.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Forgot Password</a>
        <section class="recovery-card otp-card" aria-labelledby="otp-heading">
          <div class="recovery-icon otp-icon"><i class="fa-solid fa-envelope-circle-check" aria-hidden="true"></i><span><i class="fa-solid fa-shield-halved" aria-hidden="true"></i></span></div>
          <h1 id="otp-heading">Enter Verification Code</h1>
          <p class="recovery-description">
            We sent a 6-digit verification code to<br />
            <strong style="color: #0d6d43; font-size: 1.05rem; display: inline-block; margin-top: 4px;"><?php echo htmlspecialchars(maskEmail($identifier)); ?></strong>
          </p>

          <?php if ($flash): ?>
            <div style="background: <?php echo $flash['type'] === 'success' || $flash['type'] === 'info' ? '#def7ec' : '#fffbeb'; ?>; color: <?php echo $flash['type'] === 'success' || $flash['type'] === 'info' ? '#03543f' : '#92400e'; ?>; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid <?php echo $flash['type'] === 'success' || $flash['type'] === 'info' ? '#bcf0da' : '#fef3c7'; ?>;">
              <i class="fa-solid fa-circle-check"></i> <?php echo $flash['message']; ?>
            </div>
          <?php endif; ?>

          <?php if (!empty($error)): ?>
            <div style="background: #fde8e8; color: #9b1c1c; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #f8b4b4;">
              <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
            </div>
          <?php endif; ?>

          <form class="auth-form recovery-form otp-form" method="POST" action="verify-otp.php" data-auth-form="otp" novalidate>
            <input type="hidden" name="otp" id="full-otp-input" value="" />
            <fieldset class="otp-fieldset">
              <legend class="field-label" style="text-align: center; margin-bottom: 8px;">Enter 6-Digit Code</legend>
              <div class="otp-inputs" aria-label="6-digit verification code">
                <?php for ($i = 0; $i < 6; $i++): ?>
                  <input class="otp-digit" name="otp_digits[]" aria-label="Digit <?php echo $i+1; ?>" type="text" inputmode="numeric" pattern="[0-9]" maxlength="1" autocomplete="one-time-code" required />
                <?php endfor; ?>
              </div>
            </fieldset>

            <div class="resend-panel" style="display: flex; justify-content: space-between; align-items: center; margin: 16px 0 24px;">
              <span style="font-size: 0.88rem; color: #64748b;"><i class="fa-regular fa-clock" aria-hidden="true"></i> Code valid for 15 mins</span>
              <a href="verify-otp.php?resend=1" id="resend-otp-btn" data-cooldown="<?php echo $cooldownLeft; ?>" style="color: #0d6d43; font-weight: 600; font-size: 0.9rem; text-decoration: none;">
                <i class="fa-solid fa-rotate-right"></i> <span id="resend-text">Resend Code</span>
              </a>
            </div>

            <button class="button button-green auth-submit" type="submit">Verify &amp; Proceed <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
            <p class="form-message" role="status" aria-live="polite"></p>
          </form>

          <div class="recovery-bottom-links" style="margin-top: 20px; padding-top: 16px; border-top: 1px solid #eef2f0; display: flex; justify-content: space-between;">
            <a href="forgot-password.php"><i class="fa-solid fa-pen" aria-hidden="true"></i> Change Email Address</a>
            <a href="login.php"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Return to Login</a>
          </div>
        </section>

        <!-- Developer helper box (for offline / localhost dev) -->
        <?php if (!empty($demoOtp)): ?>
        <div style="max-width: 440px; margin: 16px auto 0; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 0.82rem; color: #475569; text-align: center;">
          <i class="fa-solid fa-code"></i> <strong>Dev / Audit Code:</strong> <span style="font-family: monospace; font-weight: 700; color: #0d6d43; font-size: 0.95rem;"><?php echo htmlspecialchars($demoOtp); ?></span>
          <br /><span style="font-size: 0.76rem; color: #64748b;">(Also logged to logs/mail_log.txt • Configure Gmail SMTP in config/mail.php for live inbox delivery)</span>
        </div>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 24px; color: #71807d; font-size: 0.85rem;">
          <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. • Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
        </div>
      </div>
    </main>
    <script src="js/auth.js" defer></script>
  </body>
</html>
