<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = filter_var(sanitize($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = sanitize($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $city = sanitize($_POST['city'] ?? '');

    if (empty($name) || !$email || empty($phone) || empty($password) || empty($city)) {
        $error = 'Please fill out all required fields with valid details.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $db = getDB();
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            $error = 'An account with this email address already exists. Please login instead.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO users (name, email, phone, password, city, role, status) VALUES (?, ?, ?, ?, ?, 'user', 'Active')");
            try {
                $stmt->execute([$name, $email, $phone, $hashedPassword, $city]);
                $newUserId = $db->lastInsertId();
                $_SESSION['user_id'] = $newUserId;
                setFlash('success', 'Welcome to Food Bridge, ' . htmlspecialchars($name) . '! Your account has been created.');
                header('Location: dashboard.php');
                exit;
            } catch (PDOException $e) {
                $error = 'Registration failed: ' . $e->getMessage();
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
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/auth.css" />
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

              <?php if (!empty($error)): ?>
                <div style="background: #fde8e8; color: #9b1c1c; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #f8b4b4;">
                  <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
              <?php endif; ?>

              <form class="auth-form register-form" method="POST" action="register.php" data-auth-form="register" novalidate>
                <label class="field-label" for="register-name">Full Name <span>*</span></label>
                <div class="input-wrap">
                  <i class="fa-regular fa-user" aria-hidden="true"></i>
                  <input id="register-name" name="name" type="text" placeholder="Enter your full name" autocomplete="name" minlength="2" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required />
                </div>
                <div class="form-row">
                  <div class="form-field">
                    <label class="field-label" for="register-email">Email Address <span>*</span></label>
                    <div class="input-wrap">
                      <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                      <input id="register-email" name="email" type="email" placeholder="Enter your email" autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
                    </div>
                  </div>
                  <div class="form-field">
                    <label class="field-label" for="register-phone">Mobile Number <span>*</span></label>
                    <div class="input-wrap">
                      <i class="fa-solid fa-phone" aria-hidden="true"></i>
                      <input id="register-phone" name="phone" type="tel" placeholder="e.g. 9876543210" autocomplete="tel" inputmode="tel" value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required />
                    </div>
                  </div>
                </div>
                <div class="form-row">
                  <div class="form-field">
                    <label class="field-label" for="register-password">Password <span>*</span></label>
                    <div class="input-wrap">
                      <i class="fa-solid fa-lock" aria-hidden="true"></i>
                      <input id="register-password" name="password" type="password" placeholder="Create a password" autocomplete="new-password" minlength="6" required />
                      <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                    </div>
                    <small class="field-hint">Must be at least 6 characters</small>
                  </div>
                  <div class="form-field">
                    <label class="field-label" for="register-confirm-password">Confirm Password <span>*</span></label>
                    <div class="input-wrap">
                      <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                      <input id="register-confirm-password" name="confirm_password" type="password" placeholder="Confirm your password" autocomplete="new-password" minlength="6" required />
                      <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                    </div>
                  </div>
                </div>
                <label class="field-label" for="register-city">City / Location in Gujarat <span>*</span></label>
                <div class="input-wrap select-wrap">
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
                <label class="checkbox-label terms-label">
                  <input name="terms" type="checkbox" required checked />
                  <span>I agree to the <a href="about.php#terms">Terms &amp; Conditions</a> and <a href="about.php#privacy">Privacy Policy</a> governing community meal donations and requests.</span>
                </label>
                <button class="button button-green auth-submit" type="submit">Create Account <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
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
    <script src="js/auth.js" defer></script>
  </body>
</html>
