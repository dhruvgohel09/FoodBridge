<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

$isUserLoggedIn = false;
$user = null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Food Bridge connects surplus food with people who need it, helping reduce food waste and spread kindness in the community.">
  <title>Food Bridge - Community Food Sharing</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <header class="header">
    <div class="container header-inner">
      <a class="logo" href="index.php" aria-label="Food Bridge home">
        <span class="logo-mark" aria-hidden="true"><i class="fa-solid fa-seedling"></i></span>
        <span class="logo-text"><strong>Food Bridge</strong><small>Community Food Sharing</small></span>
      </a>
      <button class="menu-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="main-nav">
        <span></span><span></span><span></span>
      </button>
      <nav class="nav" id="main-nav" aria-label="Main navigation">
        <a href="#home" class="active">Home</a>
        <a href="#about">About</a>
        <a href="#how-it-works">How It Works</a>
        <a href="about.php">Mission</a>
      </nav>
      <div class="header-actions">
        <a class="login-link" href="login.php">Login</a>
        <a class="btn btn-green btn-small" href="register.php">Register</a>
      </div>
    </div>
  </header>

  <main>
    <!-- HERO SECTION -->
    <section class="hero" id="home">
      <div class="container hero-inner">
        <div class="hero-content">
          <span class="section-label"><i class="fa-solid fa-circle"></i> COMMUNITY FOOD SHARING</span>
          <h1>Share Food,<br><span class="text-green">Spread Hope</span></h1>
          <p class="hero-desc">Food Bridge connects surplus food from homes, caterers, and food businesses directly with local community shelters and families in need. Zero waste, zero fees.</p>
          <div class="hero-buttons">
            <a href="<?php echo $isUserLoggedIn ? 'donate-food.php' : 'login.php'; ?>" class="btn btn-green"><i class="fa-solid fa-hand-holding-heart"></i> Donate Food</a>
            <a href="need-food.php" class="btn btn-outline-orange"><i class="fa-solid fa-bowl-food"></i> Need Food</a>
          </div>
          <div class="hero-features">
            <span><i class="fa-solid fa-circle-check"></i> Two simple actions: donate or request</span>
            <span><i class="fa-solid fa-circle-check"></i> 100% Free &amp; community powered</span>
          </div>
        </div>
        <div class="hero-image">
          <img src="images/food-rescue.jpg" alt="Volunteers packing fresh food donations for the community">
          <div class="hero-badge">
            <div class="badge-icon"><i class="fa-solid fa-bowl-food"></i></div>
            <div class="badge-text"><h4>Community Powered</h4><p>Fresh &amp; Dignified Meals</p></div>
          </div>
        </div>
      </div>
    </section>

    <!-- IMPACT STATS STRIP -->
    <section class="stats section-space" style="padding: 40px 0; background: #fff; border-top: 1px solid #eef2ef; border-bottom: 1px solid #eef2ef;">
      <div class="container">
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; text-align: center;">
          <div class="stat-item">
            <div style="font-size: 2.2rem; font-weight: 800; color: #1f8c5a; letter-spacing: -0.04em;">15,000+</div>
            <div style="font-size: 0.9rem; font-weight: 700; color: #1e2624; margin-top: 4px;">Meals Rescued</div>
            <small style="font-size: 0.78rem; color: #677774;">Directly to community tables</small>
          </div>
          <div class="stat-item">
            <div style="font-size: 2.2rem; font-weight: 800; color: #ef8b41; letter-spacing: -0.04em;">7 Cities</div>
            <div style="font-size: 0.9rem; font-weight: 700; color: #1e2624; margin-top: 4px;">Active in Gujarat</div>
            <small style="font-size: 0.78rem; color: #677774;">Rajkot, Ahmedabad, Surat &amp; more</small>
          </div>
          <div class="stat-item">
            <div style="font-size: 2.2rem; font-weight: 800; color: #2563eb; letter-spacing: -0.04em;">25+ NGOs</div>
            <div style="font-size: 0.9rem; font-weight: 700; color: #1e2624; margin-top: 4px;">Verified Relief Hubs</div>
            <small style="font-size: 0.78rem; color: #677774;">Vetted distribution partners</small>
          </div>
          <div class="stat-item">
            <div style="font-size: 2.2rem; font-weight: 800; color: #0d6d43; letter-spacing: -0.04em;">100% Free</div>
            <div style="font-size: 0.9rem; font-weight: 700; color: #1e2624; margin-top: 4px;">Zero Middlemen</div>
            <small style="font-size: 0.78rem; color: #677774;">Hyper-local neighbor relief</small>
          </div>
        </div>
      </div>
    </section>

    <!-- ABOUT SECTION -->
    <section class="about section-space" id="about">
      <div class="container about-inner">
        <div class="about-image">
          <img src="images/wholesome-meals.jpg" alt="Fresh wholesome food prepared for community distribution" loading="lazy">
        </div>
        <div class="about-content">
          <span class="section-label"><i class="fa-solid fa-circle"></i> PLATFORM OVERVIEW</span>
          <h2>What is Food Bridge?</h2>
          <p class="section-desc">A simple platform designed to help surplus food reach people who need it. Community members can donate available food or request support with dignity.</p>
          <div class="about-list">
            <div class="about-item">
              <div class="about-item-icon icon-green"><i class="fa-solid fa-leaf"></i></div>
              <div><h4>Reduce Food Waste</h4><p>Share wholesome surplus meals and groceries with the local community.</p></div>
            </div>
            <div class="about-item">
              <div class="about-item-icon icon-orange"><i class="fa-solid fa-heart"></i></div>
              <div><h4>Dignified Community Support</h4><p>Make it easier to ask for and offer support respectfully.</p></div>
            </div>
            <div class="about-item">
              <div class="about-item-icon icon-blue"><i class="fa-solid fa-people-roof"></i></div>
              <div><h4>Connect Locally</h4><p>Help neighbors and local organizations coordinate food sharing.</p></div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- HOW IT WORKS -->
    <section class="how section-space" id="how-it-works">
      <div class="container">
        <div class="section-header">
          <span class="section-label"><i class="fa-solid fa-circle"></i> SIMPLE LOGISTICS</span>
          <h2>How Food Bridge Works</h2>
          <p>A simple, transparent process connecting surplus meals with community needs.</p>
        </div>
        <div class="how-grid">
          <article class="how-card">
            <div class="how-icon icon-green"><i class="fa-solid fa-utensils"></i></div>
            <h3>1. List Surplus Food</h3>
            <p>Donors post extra cooked meals, dry rations, or produce with portion counts and pickup windows.</p>
          </article>
          <article class="how-card">
            <div class="how-icon icon-blue"><i class="fa-solid fa-arrows-rotate"></i></div>
            <h3>2. Local Matching</h3>
            <p>Our network automatically routes requests to verified nearby distribution kitchens and relief hubs.</p>
          </article>
          <article class="how-card">
            <div class="how-icon icon-orange"><i class="fa-solid fa-hand-holding-heart"></i></div>
            <h3>3. Nourish People</h3>
            <p>Meals are distributed to families and individuals with dignity, hygiene, and zero waste.</p>
          </article>
        </div>
      </div>
    </section>

    <!-- CTA BANNER SECTION -->
    <section class="cta section-space">
      <div class="container">
        <div class="cta-box">
          <div class="cta-content">
            <span class="cta-tag"><i class="fa-solid fa-heart"></i> JOIN THE MOVEMENT</span>
            <h2>One Meal Can Make a Difference</h2>
            <p>Share what you can. Ask when you need help. Together, we can rescue surplus meals and build a more caring, connected community.</p>
            <div class="cta-buttons">
              <?php if ($isUserLoggedIn): ?>
                <a href="donate-food.php" class="btn btn-green"><i class="fa-solid fa-hand-holding-heart"></i> Donate Food Now</a>
                <a href="need-food.php" class="btn btn-outline-white"><i class="fa-solid fa-bowl-food"></i> Request Food</a>
                <a href="dashboard.php" class="btn btn-outline-white"><i class="fa-solid fa-gauge-high"></i> My Dashboard</a>
              <?php else: ?>
                <a href="register.php" class="btn btn-green"><i class="fa-solid fa-user-plus"></i> Create an Account</a>
                <a href="login.php" class="btn btn-outline-white"><i class="fa-solid fa-arrow-right-to-bracket"></i> Login</a>
                <a href="need-food.php" class="btn btn-outline-orange"><i class="fa-solid fa-bowl-food"></i> Need Food?</a>
              <?php endif; ?>
            </div>
            <div class="cta-footer">
              <i class="fa-solid fa-shield-heart"></i> 100% Free Civic Service • Transparent Local Relief • No Middlemen
            </div>
          </div>
        </div>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="container footer-inner">
      <div class="footer-left">
        <a class="logo" href="index.php" aria-label="Food Bridge home">
          <span class="logo-mark" aria-hidden="true"><i class="fa-solid fa-seedling"></i></span>
          <span class="logo-text"><strong>Food Bridge</strong><small>Community Food Sharing</small></span>
        </a>
        <p class="footer-desc">Connecting surplus food with people who need it, helping reduce waste and build stronger communities across Gujarat.</p>
      </div>
      <div class="footer-right">
        <h4>PLATFORM NAVIGATION</h4>
        <ul>
          <li><a href="#home">Home</a></li>
          <li><a href="#about">About</a></li>
          <li><a href="#how-it-works">How It Works</a></li>
          <li><a href="donate-food.php">Donate Food</a></li>
          <li><a href="need-food.php">Need Food</a></li>
          <li><a href="login.php">Login</a></li>
          <li><a href="register.php">Register</a></li>
        </ul>
      </div>
    </div>
    <div class="container footer-bottom">
      <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. | Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
      <p>Community Food Sharing across Gujarat</p>
    </div>
  </footer>
  <script src="js/auth.js" defer></script>
</body>
</html>
