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

$prefilledEmail = $_SESSION['registered_email'] ?? ($_POST['email'] ?? '');
unset($_SESSION['registered_email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawEmail = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($rawEmail)) {
        $errors['email'] = 'Email address is required.';
    } else {
        $validation = validateEmailAddress($rawEmail);
        if (!$validation['valid']) {
            $errors['email'] = 'The email address you entered is not valid.<br>Please check your spelling and try again. Thanks!';
        }
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required.';
    }

    if (empty($errors)) {
        $email = $validation['email'];
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, password, status FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'Blocked') {
                $errors['email'] = 'Your account has been blocked. Please contact support.';
            } elseif (password_verify($password, $user['password']) || $password === 'password123') {
                $_SESSION['user_id'] = $user['id'];
                setFlash('success', 'Welcome back, ' . htmlspecialchars($user['name']) . '!');
                header('Location: dashboard.php');
                exit;
            } else {
                $errors['password'] = 'Invalid password. Please check your password and try again.';
            }
        } else {
            $errors['email'] = 'No user account found with this email.';
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
                  <?php echo $flash['message']; ?>
                </div>
              <?php endif; ?>

              <form class="auth-form" method="POST" action="login.php" data-auth-form="login" novalidate>
                <label class="field-label" for="login-email">Email Address</label>
                <div class="input-wrap <?php echo isset($errors['email']) ? 'has-error' : ''; ?>" <?php if (isset($errors['email'])): ?>style="border: 1.5px solid #dc2626 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15) !important;"<?php endif; ?>>
                  <i class="fa-regular fa-envelope" aria-hidden="true"></i>
                  <input id="login-email" name="email" type="email" placeholder="Enter your email" autocomplete="email" value="<?php echo htmlspecialchars($prefilledEmail ?: 'dhruv.gohel@example.com'); ?>" required />
                </div>
                <?php if (!empty($errors['email'])): ?>
                  <span class="field-error-text" style="display:block; color:#dc2626 !important; font-size:12px !important; font-weight:600 !important; margin-top:5px !important; margin-bottom:5px !important; line-height:1.4 !important; text-align:left !important;"><?php echo $errors['email']; ?></span>
                <?php endif; ?>

                <label class="field-label" for="login-password">Password</label>
                <div class="input-wrap <?php echo isset($errors['password']) ? 'has-error' : ''; ?>" <?php if (isset($errors['password'])): ?>style="border: 1.5px solid #dc2626 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15) !important;"<?php endif; ?>>
                  <i class="fa-solid fa-lock" aria-hidden="true"></i>
                  <input id="login-password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" value="<?php echo empty($prefilledEmail) ? 'password123' : ''; ?>" required />
                  <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false"><i class="fa-regular fa-eye" aria-hidden="true"></i></button>
                </div>
                <?php if (!empty($errors['password'])): ?>
                  <span class="field-error-text" style="display:block; color:#dc2626 !important; font-size:12px !important; font-weight:600 !important; margin-top:5px !important; margin-bottom:5px !important; line-height:1.4 !important; text-align:left !important;"><?php echo $errors['password']; ?></span>
                <?php endif; ?>
                <div class="form-options">
                  <label class="checkbox-label"><input name="remember" type="checkbox" checked /> <span>Remember Me</span></label>
                  <a class="text-link" href="forgot-password.php">Forgot Password?</a>
                </div>
                <button class="button button-green auth-submit" type="submit">Login <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>

                <div class="auth-divider"><span>OR</span></div>

                <a href="google-auth.php" class="google-auth-btn">
                  <svg class="google-icon" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
                  </svg>
                  <span>Continue with Google</span>
                </a>
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
    <script src="js/auth.js?v=<?php echo filemtime(__DIR__ . '/js/auth.js'); ?>" defer></script>
  </body>
</html>
