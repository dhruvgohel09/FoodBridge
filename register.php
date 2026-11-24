<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/mailer.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $rawEmail = sanitize($_POST['email'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $city = sanitize($_POST['city'] ?? '');

    if (empty($name)) {
        $errors['name'] = 'Full name is required.';
    }

    if (empty($rawEmail)) {
        $errors['email'] = 'Email address is required.';
    } else {
        $emailCheck = validateEmailAddress($rawEmail);
        if (!$emailCheck['valid']) {
            $errors['email'] = 'The email address you entered is not valid. Please check your spelling and try again.';
        }
    }

    $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
    if (empty($phone)) {
        $errors['phone'] = 'Mobile number is required.';
    } elseif (strlen($cleanPhone) < 10) {
        $errors['phone'] = 'Please enter a valid 10-digit mobile number.';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters long.';
    }

    if (empty($confirm_password)) {
        $errors['confirm_password'] = 'Please confirm your password.';
    } elseif ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match. Please re-enter.';
    }

    if (empty($city)) {
        $errors['city'] = 'Please select your city in Gujarat.';
    }

    if (empty($errors)) {
        $email = $emailCheck['email'];
        $db = getDB();
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $errors['email'] = 'An account with this email address already exists. Please login instead.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, city, role, status) VALUES (?, ?, ?, ?, ?, 'user', 'Active')");
            try {
                $stmt->execute([$name, $email, $phone, $hashedPassword, $city]);
                $newUserId = $db->lastInsertId();

                // Send Welcome Email
                sendWelcomeEmail($email, $name);

                // Do not auto-login; redirect directly to login page as requested
                $_SESSION['registered_email'] = $email;
                setFlash('success', 'Account created successfully! A welcome email has been sent to ' . htmlspecialchars($email) . '. Please login with your credentials.');
                header('Location: login.php');
                exit;
            } catch (PDOException $e) {
                $errors['general'] = 'Registration failed: ' . $e->getMessage();
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
    <meta name="description" content="Create a Food Bridge community account to donate surplus food or request support." />
    <title>Create an Account | Food Bridge</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="stylesheet" href="css/style.css?v=<?php echo filemtime(__DIR__ . '/css/style.css'); ?>" />
    <link rel="stylesheet" href="css/auth.css?v=<?php echo filemtime(__DIR__ . '/css/auth.css'); ?>" />
  </head>
  <body class="auth-page">
    <header class="header">
      <div class="container header-inner">
        <a class="logo" href="index.php#home" aria-label="Food Bridge home">
          <span class="logo-mark" aria-hidden="true"><i class="fa-solid fa-seedling"></i></span>
          <span class="logo-text"><strong>Food Bridge</strong><small>Community Food Sharing</small></span>
        </a>
        <button class="menu-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="main-nav">
          <span></span><span></span><span></span>
        </button>
        <nav class="nav" id="main-nav" aria-label="Main navigation">
          <a href="index.php#home">Home</a>
          <a href="index.php#about">About</a>
          <a href="index.php#how-it-works">How It Works</a>
        </nav>
        <div class="header-actions"><a class="login-link" href="login.php">Login</a></div>
      </div>
    </header>
    <main class="auth-main register-main">
      <div class="container">
        <section class="auth-card register-card" aria-labelledby="register-heading">
          <aside class="auth-story register-story">
            <div class="register-story-content">
              <span class="story-leaf"><i class="fa-solid fa-seedling" aria-hidden="true"></i></span>
              <h1>Together, We Can Make Every Meal Count</h1>
              <p>Join Food Bridge and help connect fresh surplus food directly with families and communities in need.</p>
              <ul class="story-benefits">
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Single account for both donating and requesting</li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> No commercial fees or middlemen</li>
                <li><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Coordinated with local communities</li>
              </ul>
              <div class="autonomy-card">
                <i class="fa-solid fa-shield-heart" aria-hidden="true"></i>
                <span><strong>One account. Two ways to help.</strong><small>Give when you can, ask when you need support. Your account is ready for both.</small></span>
              </div>
            </div>
          </aside>
          <div class="auth-form-panel register-form-panel">
            <div class="auth-form-wrap register-form-wrap">
              <span class="eyebrow register-eyebrow"><i class="fa-solid fa-users" aria-hidden="true"></i> Unified community account</span>
              <h2 id="register-heading">Create Your Account</h2>
              <p class="auth-subtitle">Join Food Bridge to donate surplus food or request meals when you need them.</p>

              <form class="auth-form register-form" method="POST" action="register.php" data-auth-form="register" novalidate>
                <label class="field-label" for="register-name">Full Name <span>*</span></label>
                <div class="input-wrap <?php echo isset($errors['name']) ? 'has-error' : ''; ?>">
                  <i class="fa-regular fa-user" aria-hidden="true"></i>
                  <input id="register-name" name="name" type="text" placeholder="Enter your full name" autocomplete="name" minlength="2" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required />
                </div>
                <?php if (!empty($errors['name'])): ?>
                  <span class="field-error-text"><?php echo htmlspecialchars($errors['name']); ?></span>
                <?php endif; ?>

                <div class="form-row">
                  <div class="form-field">
                    <label class="field-label" for="register-email">Email Address <span>*</span></label>
                    <div class="input-wrap <?php echo isset($errors['email']) ? 'has-error' : ''; ?>">
                      <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                      <input id="register-email" name="email" type="email" placeholder="Enter your email" autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
                    </div>
                    <?php if (!empty($errors['email'])): ?>
                      <span class="field-error-text"><?php echo htmlspecialchars($errors['email']); ?></span>
                    <?php endif; ?>
                  </div>
                  <div class="form-field">
                    <label class="field-label" for="register-phone">Mobile Number <span>*</span></label>
                    <div class="input-wrap <?php echo isset($errors['phone']) ? 'has-error' : ''; ?>">
                      <i class="fa-solid fa-phone" aria-hidden="true"></i>
                      <input id="register-phone" name="phone" type="tel" placeholder="e.g. 9876543210" autocomplete="tel" inputmode="tel" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required />
                    </div>
                    <?php if (!empty($errors['phone'])): ?>
                      <span class="field-error-text"><?php echo htmlspecialchars($errors['phone']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-field">
                    <label class="field-label" for="register-password">Password <span>*</span></label>
                    <div class="input-wrap <?php echo isset($errors['password']) ? 'has-error' : ''; ?>">
                      <i class="fa-solid fa-lock" aria-hidden="true"></i>
                      <input id="register-password" name="password" type="password" placeholder="Create a password" autocomplete="new-password" minlength="6" required />
                      <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                    </div>
                    <?php if (!empty($errors['password'])): ?>
                      <span class="field-error-text"><?php echo htmlspecialchars($errors['password']); ?></span>
                    <?php else: ?>
                      <small class="field-hint">Must be at least 6 characters</small>
                    <?php endif; ?>
                  </div>
                  <div class="form-field">
                    <label class="field-label" for="register-confirm-password">Confirm Password <span>*</span></label>
                    <div class="input-wrap <?php echo isset($errors['confirm_password']) ? 'has-error' : ''; ?>">
                      <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                      <input id="register-confirm-password" name="confirm_password" type="password" placeholder="Confirm your password" autocomplete="new-password" minlength="6" required />
                      <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                    </div>
                    <?php if (!empty($errors['confirm_password'])): ?>
                      <span class="field-error-text"><?php echo htmlspecialchars($errors['confirm_password']); ?></span>
                    <?php endif; ?>
                  </div>
                </div>
                <label class="field-label" for="register-city">City / Location in Gujarat <span>*</span></label>
                <div class="input-wrap select-wrap <?php echo isset($errors['city']) ? 'has-error' : ''; ?>">
                  <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                  <select id="register-city" name="city" required>
                    <option value="" disabled <?php echo empty($_POST['city']) ? 'selected' : ''; ?>>Select your city in Gujarat</option>
                    <?php
                    $cities = ['Rajkot', 'Ahmedabad', 'Surat', 'Vadodara', 'Bhavnagar', 'Junagadh', 'Gandhinagar', 'Jamnagar', 'Anand', 'Morbi', 'Mehsana', 'Bharuch', 'Navsari', 'Valsad', 'Porbandar', 'Surendranagar'];
                    $selectedCity = $_POST['city'] ?? 'Rajkot';
                    foreach ($cities as $c): ?>
                      <option value="<?php echo $c; ?>" <?php echo $selectedCity === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                    <?php endforeach; ?>
                  </select>
                  <i class="fa-solid fa-chevron-down select-chevron" aria-hidden="true"></i>
                </div>
                <?php if (!empty($errors['city'])): ?>
                  <span class="field-error-text"><?php echo htmlspecialchars($errors['city']); ?></span>
                <?php endif; ?>
                <label class="checkbox-label terms-label">
                  <input name="terms" type="checkbox" required checked />
                  <span>I agree to the <a href="about.php#terms">Terms &amp; Conditions</a> and <a href="about.php#privacy">Privacy Policy</a> governing community meal donations and requests.</span>
                </label>
                <button class="button button-green auth-submit" type="submit">Create Account <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>

                <div class="auth-divider"><span>OR</span></div>

                <a href="google-auth.php" class="google-auth-btn">
                  <svg class="google-icon" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                  </svg>
                  <span>Sign up with Google</span>
                </a>
                <p class="form-message" role="status" aria-live="polite"></p>
              </form>
              <p class="auth-switch">Already have an account? <a href="login.php">Login here <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
            </div>
          </div>
        </section>
      </div>
    </main>
    <footer class="footer">
      <div class="container footer-inner">
        <div class="footer-left">
          <a class="logo" href="index.php#home" aria-label="Food Bridge home">
            <span class="logo-mark" aria-hidden="true"><i class="fa-solid fa-seedling"></i></span>
            <span class="logo-text"><strong>Food Bridge</strong><small>Community Food Sharing</small></span>
          </a>
          <p class="footer-desc">Food Bridge connects surplus food with people who need it, helping reduce food waste and spread kindness in our communities.</p>
        </div>
        <div class="footer-right"><h4>PLATFORM NAVIGATION</h4><ul>
          <li><a href="index.php#home">Home</a></li><li><a href="index.php#about">About</a></li>
          <li><a href="index.php#how-it-works">How It Works</a></li><li><a href="login.php">Login</a></li>
          <li><a href="register.php">Register</a></li>
        </ul></div>
      </div>
      <div class="container footer-bottom">
        <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. | Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
        <p>Community Food Sharing across Gujarat</p>
      </div>
    </footer>
    <script src="js/auth.js?v=<?php echo filemtime(__DIR__ . '/js/auth.js'); ?>" defer></script>
  </body>
</html>
