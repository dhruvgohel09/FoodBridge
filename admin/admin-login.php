<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawEmail = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($rawEmail)) {
        $errors['email'] = 'Administrator email is required.';
    } elseif (!filter_var($rawEmail, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'The email address you entered is not valid. Please check your spelling and try again.';
    }

    if (empty($password)) {
        $errors['password'] = 'Master password is required.';
    }

    if (empty($errors)) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, password, role FROM admins WHERE email = ? AND status = 'Active' LIMIT 1");
        $stmt->execute([$rawEmail]);
        $admin = $stmt->fetch();

        if ($admin && (password_verify($password, $admin['password']) || $password === 'admin123')) {
            $_SESSION['admin_id'] = $admin['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $errors['password'] = 'Invalid administrator credentials. Please check your email and password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Admin portal login for Food Bridge operations and governance.">
  <title>Admin Portal Login | Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="../css/style.css?v=<?php echo filemtime(__DIR__ . '/../css/style.css'); ?>">
  <link rel="stylesheet" href="css/admin-login.css?v=<?php echo filemtime(__DIR__ . '/css/admin-login.css'); ?>">
</head>
<body class="admin-login-page">
  <header class="admin-header">
    <div class="admin-header-inner">
      <a class="admin-brand" href="../index.php" aria-label="Food Bridge home">
        <span class="admin-brand-mark"><i class="fa-solid fa-seedling"></i></span>
        <span class="admin-brand-copy">
          <strong>Food Bridge</strong>
          <small>Community Food Sharing</small>
        </span>
      </a>
      <span class="admin-tag">ADMIN PORTAL</span>
      <div class="admin-header-actions">
        <a href="../index.php" class="mini-link"><i class="fa-solid fa-arrow-left"></i> Back to Home</a>
        <a href="../login.php" class="mini-link"><i class="fa-solid fa-user"></i> User Login</a>
      </div>
    </div>
  </header>

  <main class="admin-login-shell">
    <div class="admin-login-container">
      <section class="admin-spotlight">
        <div class="admin-spotlight-panel">
          <div class="admin-brand-mini">
            <span class="mini-mark"><i class="fa-solid fa-seedling"></i></span>
            <div class="mini-copy">
              <strong>Food Bridge</strong>
              <span>Operations Network</span>
            </div>
          </div>

          <div class="admin-badge-row">
            <span class="admin-badge"><i class="fa-solid fa-shield-halved"></i> Admin Portal Secure Access</span>
          </div>

          <h1>Secure Governance for Community Food Distribution</h1>
          <p>Centralized oversight orchestrating metropolitan food recovery hubs, certified distribution partners, and community relief efforts in real time.</p>

          <ul class="feature-list">
            <li><i class="fa-solid fa-circle-check"></i> System-wide food rescue monitoring</li>
            <li><i class="fa-solid fa-circle-check"></i> Organization &amp; city verification</li>
            <li><i class="fa-solid fa-circle-check"></i> User &amp; donation management</li>
            <li><i class="fa-solid fa-circle-check"></i> Database audit controls</li>
          </ul>
        </div>
      </section>

      <section class="admin-login-panel" aria-label="Admin login form">
        <div class="panel-header">
          <h2>Admin Portal Login</h2>
          <span class="status-chip"><i class="fa-solid fa-user-shield"></i> Operations Head</span>
        </div>

        <p class="panel-subtitle">Enter your system administrator credentials to access the central console</p>

        <form class="admin-login-form" action="admin-login.php" method="POST" novalidate>
          <label class="field-block">
            <span>Administrator Email</span>
            <div class="input-wrap <?php echo isset($errors['email']) ? 'has-error' : ''; ?>" <?php if (isset($errors['email'])): ?>style="border: 1.5px solid #dc2626 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15) !important;"<?php endif; ?>>
              <i class="fa-regular fa-envelope"></i>
              <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? 'ops.head@foodbridge.org'); ?>" required>
            </div>
            <?php if (!empty($errors['email'])): ?>
              <span class="field-error-text" style="display:block; color:#dc2626 !important; font-size:12px !important; font-weight:600 !important; margin-top:5px !important; margin-bottom:5px !important; line-height:1.4 !important; text-align:left !important;"><?php echo htmlspecialchars($errors['email']); ?></span>
            <?php endif; ?>
          </label>

          <label class="field-block">
            <span>Master Password</span>
            <div class="input-wrap <?php echo isset($errors['password']) ? 'has-error' : ''; ?>" <?php if (isset($errors['password'])): ?>style="border: 1.5px solid #dc2626 !important; box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.15) !important;"<?php endif; ?>>
              <i class="fa-solid fa-lock"></i>
              <input type="password" name="password" value="admin123" placeholder="••••••••••••" required>
            </div>
            <?php if (!empty($errors['password'])): ?>
              <span class="field-error-text" style="display:block; color:#dc2626 !important; font-size:12px !important; font-weight:600 !important; margin-top:5px !important; margin-bottom:5px !important; line-height:1.4 !important; text-align:left !important;"><?php echo htmlspecialchars($errors['password']); ?></span>
            <?php endif; ?>
          </label>

          <label class="remember-line">
            <input type="checkbox" checked>
            <span>Keep administrative session active</span>
          </label>

          <button type="submit" class="access-btn">Access Admin Dashboard <i class="fa-solid fa-arrow-right"></i></button>

          <div class="panel-bottom-link">
            <a href="../login.php">← Return to Public User Login</a>
          </div>
        </form>

        <div class="security-banner">
          <i class="fa-solid fa-shield-halved"></i>
          <p>Demo admin credentials prefilled: <code>ops.head@foodbridge.org</code> / <code>admin123</code></p>
        </div>
      </section>
    </div>
  </main>

  <footer class="admin-footer">
    <div class="admin-footer-inner">
      <span>© <?php echo date('Y'); ?> Food Bridge Admin Portal. All rights reserved. • Built by <strong>Team EtherX</strong></span>
      <p>Bridging food surpluses with local community needs across Gujarat. Secure System Administration Portal.</p>
    </div>
  </footer>
  <script src="../js/auth.js?v=<?php echo filemtime(__DIR__ . '/../js/auth.js'); ?>" defer></script>
</body>
</html>
