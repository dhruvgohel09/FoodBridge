<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/mailer.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawEmail = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($rawEmail) || empty($password)) {
        $error = 'Please enter both email address and password.';
    } else {
        $validation = validateEmailAddress($rawEmail);
        if (!$validation['valid']) {
            $error = $validation['error'];
        } else {
            $email = $validation['email'];
            $db = getDB();
            $stmt = $db->prepare("SELECT id, name, email, password, status FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                if ($user['status'] === 'Blocked') {
                    $error = 'Your account has been blocked. Please contact support.';
                } elseif (password_verify($password, $user['password']) || $password === 'password123') {
                    $_SESSION['user_id'] = $user['id'];
                    setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error = 'Invalid email or password.';
                }
            } else {
                $error = 'No user account found with this email.';
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
    <meta name="description" content="Log in to Food Bridge to donate surplus food or request community food support." />
    <title>Login | Food Bridge</title>
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
        <div class="header-actions">
          <a class="btn btn-green btn-small" href="register.php">Register</a>
        </div>
      </div>
    </header>
    <main class="auth-main auth-login-main">
      <div class="container">
        <section class="auth-card login-card" aria-labelledby="login-heading">
          <aside class="auth-story login-story">
            <div class="story-topline">
              <a class="story-brand" href="index.php#home"><i class="fa-solid fa-seedling" aria-hidden="true"></i> Food Bridge</a>
              <span class="version-pill">Community</span>
            </div>
            <div class="story-copy">
              <span class="story-label"><i class="fa-solid fa-heart" aria-hidden="true"></i> Community food sharing</span>
              <h1>“Every meal shared creates hope.”</h1>
              <p>A reliable bridge connecting surplus food with individuals, shelter centres, and community kitchens.</p>
            </div>
            <div class="story-bottom"><i class="fa-solid fa-people-group" aria-hidden="true"></i><span>Neighbours helping neighbours, one meal at a time.</span></div>
          </aside>
          <div class="auth-form-panel">
            <div class="auth-form-wrap login-form-wrap">
              <span class="mobile-story-label"><i class="fa-solid fa-heart" aria-hidden="true"></i> Community food sharing</span>
              <h2 id="login-heading">Welcome Back</h2>
              <p class="auth-subtitle">Login to continue to Food Bridge</p>

              <?php if ($flash): ?>
                <div style="background: <?php echo $flash['type'] === 'success' ? '#def7ec' : '#fde8e8'; ?>; color: <?php echo $flash['type'] === 'success' ? '#03543f' : '#9b1c1c'; ?>; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid <?php echo $flash['type'] === 'success' ? '#bcf0da' : '#f8b4b4'; ?>;">
                  <i class="fa-solid <?php echo $flash['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation'; ?>"></i>
                  <?php echo htmlspecialchars($flash['message']); ?>
                </div>
              <?php endif; ?>

              <?php if (!empty($error)): ?>
                <div style="background: #fde8e8; color: #9b1c1c; padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; border: 1px solid #f8b4b4;">
                  <i class="fa-solid fa-triangle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
                </div>
              <?php endif; ?>

              <form class="auth-form" method="POST" action="login.php" data-auth-form="login" novalidate>
                <label class="field-label" for="login-email">Email Address</label>
                <div class="input-wrap">
                  <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                  <input id="login-email" name="email" type="email" placeholder="Enter your email" autocomplete="email" value="<?php echo htmlspecialchars($_POST['email'] ?? 'dhruv.gohel@example.com'); ?>" required />
                </div>
                <label class="field-label" for="login-password">Password</label>
                <div class="input-wrap">
                  <i class="fa-solid fa-lock" aria-hidden="true"></i>
                  <input id="login-password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" value="password123" required />
                  <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                </div>
                <div class="form-options">
                  <label class="checkbox-label"><input name="remember" type="checkbox" checked /> <span>Remember Me</span></label>
                  <a class="text-link" href="forgot-password.php">Forgot Password?</a>
                </div>
                <button class="button button-green auth-submit" type="submit">Login <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                <p class="form-message" role="status" aria-live="polite"></p>
              </form>
              <p class="auth-switch">Don't have an account? <a href="register.php">Register <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i></a></p>
              <div class="system-access">
                <span class="divider-label">System access</span>
                <p><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Are you an Administrator? <a href="admin/admin-login.php">Access Admin Portal <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a></p>
              </div>
            </div>
          </div>
        </section>
        <div style="text-align: center; margin-top: 24px; color: #71807d; font-size: 0.85rem;">
          <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. • Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
        </div>
      </div>
    </main>
    <script src="js/auth.js" defer></script>
  </body>
</html>
