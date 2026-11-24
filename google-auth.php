<?php
/**
 * FoodBridge - Google Authentication Handler & Account Chooser
 */
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

$db = getDB();

// If user already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Handle Google Account Selection / Direct Sign In
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['account'])) {
    $selectedEmail = sanitize($_POST['email'] ?? $_GET['account'] ?? '');
    $selectedName = sanitize($_POST['name'] ?? $_GET['name'] ?? '');

    // Default to Dhruv Gohel demo Google account if empty
    if (empty($selectedEmail)) {
        $selectedEmail = 'dhruv.gohel@example.com';
        $selectedName = 'Dhruv Gohel';
    }

    // Check if user exists with this email
    $stmt = $db->prepare("SELECT id, name, email, status FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$selectedEmail]);
    $user = $stmt->fetch();

    if ($user) {
        if ($user['status'] === 'Blocked') {
            setFlash('error', 'Your Google linked account is currently suspended. Please contact support.');
            header('Location: login.php');
            exit;
        }
        $userId = $user['id'];
        $userName = $user['name'];
    } else {
        // Create new user via Google Sign-In
        $userName = !empty($selectedName) ? $selectedName : explode('@', $selectedEmail)[0];
        $defaultPassword = password_hash(bin2hex(random_bytes(8)), PASSWORD_BCRYPT);
        $insert = $db->prepare("INSERT INTO users (name, email, phone, password, city, role, status) VALUES (?, ?, '9876543210', ?, 'Ahmedabad', 'user', 'Active')");
        $insert->execute([$userName, $selectedEmail, $defaultPassword]);
        $userId = $db->lastInsertId();
    }

    // Set logged-in session
    $_SESSION['user_id'] = $userId;
    setFlash('success', 'Successfully signed in with Google as ' . htmlspecialchars($userName) . '!');
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign in with Google • Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
      background: #f8faf9;
      color: #1f2937;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }
    .google-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      width: 100%;
      max-width: 440px;
      padding: 36px 32px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.01);
    }
    .google-logo-wrap {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 12px;
      margin-bottom: 24px;
    }
    .google-card h1 {
      font-size: 22px;
      font-weight: 700;
      color: #111827;
      text-align: center;
      margin-bottom: 6px;
    }
    .google-card p.subtitle {
      font-size: 13.5px;
      color: #6b7280;
      text-align: center;
      margin-bottom: 28px;
    }
    .account-item {
      display: flex;
      align-items: center;
      gap: 14px;
      padding: 14px 16px;
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      text-decoration: none;
      color: inherit;
      margin-bottom: 12px;
      transition: all 0.18s ease;
      background: #fff;
      cursor: pointer;
    }
    .account-item:hover {
      background: #f9fafb;
      border-color: #2563eb;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.08);
    }
    .avatar {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background: #e0f2fe;
      color: #0369a1;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 16px;
      flex-shrink: 0;
    }
    .avatar.alt {
      background: #dcfce7;
      color: #15803d;
    }
    .account-info {
      flex: 1;
      min-width: 0;
    }
    .account-name {
      font-size: 14px;
      font-weight: 600;
      color: #111827;
      margin-bottom: 2px;
    }
    .account-email {
      font-size: 12px;
      color: #6b7280;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .account-action {
      color: #9ca3af;
      font-size: 14px;
    }
    .account-item:hover .account-action {
      color: #2563eb;
    }
    .google-custom-form {
      margin-top: 18px;
      border-top: 1px solid #f3f4f6;
      padding-top: 18px;
    }
    .custom-input-wrap {
      display: flex;
      gap: 8px;
    }
    .custom-input-wrap input {
      flex: 1;
      padding: 10px 14px;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      font-size: 13px;
      outline: none;
    }
    .custom-input-wrap input:focus {
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }
    .custom-input-wrap button {
      padding: 10px 16px;
      background: #1f8c5a;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
    }
    .footer-links {
      margin-top: 24px;
      text-align: center;
    }
    .footer-links a {
      color: #6b7280;
      text-decoration: none;
      font-size: 12.5px;
      display: inline-flex;
      align-items: center;
      gap: 6px;
    }
    .footer-links a:hover {
      color: #111827;
    }
  </style>
</head>
<body>

  <div class="google-card">
    <div class="google-logo-wrap">
      <svg width="32" height="32" viewBox="0 0 24 24">
        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/>
        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/>
      </svg>
    </div>

    <h1>Choose an account</h1>
    <p class="subtitle">to continue to <strong>Food Bridge Community</strong></p>

    <!-- Account 1: Dhruv Gohel (Demo Registered User) -->
    <a href="google-auth.php?account=dhruv.gohel@example.com&name=Dhruv+Gohel" class="account-item">
      <div class="avatar">DG</div>
      <div class="account-info">
        <div class="account-name">Dhruv Gohel</div>
        <div class="account-email">dhruv.gohel@example.com</div>
      </div>
      <i class="fa-solid fa-chevron-right account-action"></i>
    </a>

    <!-- Account 2: Rahul Sharma (Active User) -->
    <a href="google-auth.php?account=rahul.sharma@example.com&name=Rahul+Sharma" class="account-item">
      <div class="avatar alt">RS</div>
      <div class="account-info">
        <div class="account-name">Rahul Sharma</div>
        <div class="account-email">rahul.sharma@example.com</div>
      </div>
      <i class="fa-solid fa-chevron-right account-action"></i>
    </a>

    <!-- Custom Account Input -->
    <form class="google-custom-form" method="POST" action="google-auth.php">
      <div class="custom-input-wrap">
        <input type="email" name="email" placeholder="Use another Google email..." required />
        <button type="submit">Sign In <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </form>

    <div class="footer-links">
      <a href="login.php"><i class="fa-solid fa-arrow-left"></i> Return to standard email login</a>
    </div>
  </div>

</body>
</html>
