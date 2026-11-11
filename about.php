<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

requireLogin();
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Learn how Food Bridge connects surplus food with local communities.">
  <title>About Food Bridge</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/app-header.css">
  <style>
    .about-page{background:#fbf9ff;color:#20212b}
    .about-main{padding:20px 0 0}
    .about-intro{padding:0 0 24px}
    .about-breadcrumb{font-size:11px;color:#68706f;margin-bottom:12px}
    .about-intro-top{display:flex;justify-content:space-between;gap:16px;align-items:center}
    .about-tags{display:flex;gap:7px;flex-wrap:wrap}
    .about-tag{padding:5px 9px;border-radius:99px;background:#e9e6f3;font-size:9px;font-weight:700;color:#4b5b55}
    .about-tag:first-child{background:#e6f7ec;color:#08733d}
    .about-intro h1{max-width:760px;margin:10px 0;font-size:clamp(2rem,4vw,3.3rem);line-height:1.08;letter-spacing:-.06em}
    .about-intro h1 span{color:#08733d}
    .about-intro p{max-width:680px;margin:0;color:#68706f;font-size:13px;line-height:1.7}
    .about-pills{display:flex;flex-wrap:wrap;gap:8px;margin-top:12px}
    .about-pills span{padding:5px 9px;border-radius:99px;background:#eeebf7;font-size:9px;font-weight:600}
    .about-photo{height:clamp(250px,40vw,440px);position:relative;overflow:hidden;border-radius:14px;box-shadow:0 14px 30px rgba(29,35,44,.14)}
    .about-photo img{width:100%;height:100%;object-fit:cover}
    .about-photo:after{content:"";position:absolute;inset:35% 0 0;background:linear-gradient(transparent,rgba(15,20,18,.7))}
    .rescued-pill,.about-quote{position:absolute;z-index:1;background:rgba(255,255,255,.94);border-radius:10px;box-shadow:0 8px 22px rgba(0,0,0,.13)}
    .rescued-pill{top:16px;right:16px;padding:9px 12px;font-size:10px;font-weight:700}
    .rescued-pill i{color:#08733d;margin-right:6px}
    .about-quote{left:24px;bottom:22px;width:min(440px,calc(100% - 48px));padding:14px 17px}
    .about-quote strong{display:block;font-size:14px;line-height:1.45}
    .about-quote small{color:#68706f;font-size:9px}
    .directives{padding:30px 0;background:#f3f0fb}
    .directive-heading{display:flex;justify-content:space-between;align-items:end;gap:18px;margin-bottom:14px}
    .directive-heading h2,.about-how h2{margin:5px 0;font-size:clamp(1.45rem,2.5vw,2rem);letter-spacing:-.05em;line-height:1.2}
    .directive-heading p{max-width:410px;margin:0;color:#68706f;font-size:10px}
    .directive-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .directive-card{background:#fff;border:1px solid #eeebf4;border-radius:12px;overflow:hidden}
    .directive-card article{padding:14px;min-height:150px}
    .directive-card i{display:grid;place-items:center;width:31px;height:31px;border-radius:8px;background:#e6f2ea;color:#08733d}
    .directive-card:nth-child(2) i{background:#e6f3fb;color:#1682c4}
    .directive-card:nth-child(3) i{background:#fff0e9;color:#d85b19}
    .directive-card small{display:inline-block;margin:10px 0 4px;padding:3px 7px;border-radius:99px;background:#e6f7ec;color:#08733d;font-size:8px}
    .directive-card h3{margin:0 0 5px;font-size:12px}
    .directive-card p{margin:0;color:#68706f;font-size:9px;line-height:1.55}
    .directive-foot{padding:8px 12px;border-top:1px solid #eeebf4;color:#08733d;font-size:8px}
    .about-how{padding:34px 0}
    .about-how .section-header{margin-bottom:18px}
    .about-how .section-header h2{margin:5px 0}
    .about-how .section-header p{font-size:10px}
    .about-steps{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
    .about-step{padding:16px;background:white;border:1px solid #efedf4;border-radius:13px}
    .step-label{display:flex;justify-content:space-between;align-items:center;margin-bottom:13px}
    .step-number{width:29px;height:29px;display:grid;place-items:center;border-radius:8px;background:#08733d;color:white;font-size:10px;font-weight:700}
    .about-step:nth-child(2) .step-number{background:#1682c4}
    .about-step:nth-child(3) .step-number{background:#ff651f}
    .step-kind{padding:4px 8px;border-radius:99px;background:#eeebf7;color:#5b6061;font-size:8px}
    .about-step h3{margin:0 0 7px;font-size:13px}
    .about-step p{min-height:56px;margin:0;color:#68706f;font-size:9px;line-height:1.55}
    .step-note{display:block;margin-top:11px;color:#08733d;font-size:8px}
    .impact-strip{padding:20px 0;background:#f0eef8}
    .impact-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
    .impact-card{padding:14px 10px;text-align:center;background:white;border-radius:10px}
    .impact-card strong{display:block;color:#08733d;font-size:clamp(1.5rem,3vw,2rem);line-height:1.2}
    .impact-card:nth-child(2) strong{color:#1682c4}
    .impact-card:nth-child(3) strong{color:#b84905}
    .impact-card span,.impact-card small{display:block}
    .impact-card span{font-size:9px;font-weight:700}
    .impact-card small{color:#68706f;font-size:8px}
    .about-cta{padding:24px 0;background:#fbf9ff}
    .about-cta-box{display:flex;align-items:center;justify-content:space-between;gap:20px;padding:24px;border-radius:13px;color:white;background:linear-gradient(110deg,#056d36,#13824b);box-shadow:0 12px 24px rgba(0,80,40,.16)}
    .about-cta-box h2{max-width:480px;margin:8px 0;font-size:clamp(1.35rem,2.5vw,1.8rem);line-height:1.2;letter-spacing:-.04em}
    .about-cta-box p{max-width:520px;margin:0;color:rgba(255,255,255,.82);font-size:10px}
    .about-cta-actions{display:flex;gap:8px;flex-wrap:wrap}
    .about-cta-actions .btn{min-height:39px;padding:0 13px;font-size:10px;white-space:nowrap}
    .about-page .footer{padding-top:25px;background:#f3f0fb}
    @media(max-width:850px){.directive-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
    @media(max-width:700px){.about-intro-top,.directive-heading,.about-cta-box{align-items:flex-start;flex-direction:column}.about-intro-top .about-tags{order:2}.about-steps{grid-template-columns:1fr}.about-step p{min-height:0}.impact-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.about-cta-actions{width:100%}}
    @media(max-width:440px){.about-page .logo{min-width:0}.about-tags{gap:4px}.about-tag{font-size:8px}.directive-grid{grid-template-columns:1fr}.directive-card article{min-height:0}.about-photo{height:280px}.about-quote{left:12px;bottom:12px;width:calc(100% - 24px)}.rescued-pill{top:10px;right:10px;font-size:8px}}
  </style>
</head>
<body class="about-page">
  <?php $activeHeaderPage = 'about'; include __DIR__ . '/includes/app-header.php'; ?>
  <main class="about-main">
    <section class="container about-intro" id="top">
      <div class="about-intro-top"><div class="about-breadcrumb"><a href="dashboard.php"><i class="fa-solid fa-house"></i> Home</a> &gt; About Food Bridge</div><div class="about-tags"><span class="about-tag"><i class="fa-solid fa-circle"></i> COMMUNITY FOOD INITIATIVE</span><span class="about-tag"><i class="fa-solid fa-diagram-project"></i> DIRECT LOGISTICS NETWORK</span></div></div>
      <h1>Bridging surplus harvest with <span>local community need.</span></h1>
      <p>Food Bridge is a simple, dedicated civic platform connecting edible food surplus from restaurants, banquets, student cafeterias, and households directly to verified local shelters, orphanages, and community kitchens across our city.</p>
      <div class="about-pills"><span><i class="fa-solid fa-leaf"></i> Zero Food Waste Goal</span><span><i class="fa-solid fa-earth-asia"></i> 100% Non-Profit Model</span><span><i class="fa-solid fa-hand-holding-heart"></i> Direct &amp; Dignified Support</span></div>
    </section>
    <section class="container about-photo" aria-label="Community meal sharing">
      <img src="https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&w=1600&q=85" alt="Volunteers and community members sharing freshly prepared food">
      <span class="rescued-pill"><i class="fa-solid fa-circle"></i> 148,200+ kg Rescued <small>This Fiscal Year</small></span>
      <blockquote class="about-quote"><strong><i class="fa-solid fa-quote-left" style="color:#08733d"></i> “No edible food discarded while a single neighbor goes hungry.”</strong><small>Food Bridge Community Guiding Pledge • Serving 42 Local Wards</small></blockquote>
    </section>
    <section class="directives">
      <div class="container">
        <div class="directive-heading"><div><span class="section-label">CORE DIRECTIVES</span><h2>How We Transform Surplus Into Community Relief</h2></div><p>A dependable, zero-red-tape distribution fabric keeping quality food fresh, compliant, and rapidly shared with those who need it most.</p></div>
        <div class="directive-grid">
          <div class="directive-card"><article><i class="fa-solid fa-utensils"></i><small>Our Mission</small><h3>Direct Surplus Diversion</h3><p>Divert excess prepared food, bakery goods, and pantry supplies away from landfills directly to people who need nourishment today.</p></article><div class="directive-foot">100% Free Civic Service <i class="fa-solid fa-circle-check"></i></div></div>
          <div class="directive-card"><article><i class="fa-solid fa-hand-holding-heart"></i><small>Human Dignity</small><h3>Safe &amp; Dignified Support</h3><p>Direct coordination with verified local community centers, senior homes, and student programs without bureaucratic friction.</p></article><div class="directive-foot">Verified Center Network</div></div>
          <div class="directive-card"><article><i class="fa-solid fa-chart-line"></i><small>Accountability</small><h3>Real-Time Transparency</h3><p>Transparent request status and verifiable donation logs keeping food donors and recipient shelters synchronized with accountability.</p></article><div class="directive-foot">Real-Time Dispatching</div></div>
          <div class="directive-card"><article><i class="fa-solid fa-leaf"></i><small>Circularity</small><h3>Environmental Care</h3><p>Reducing municipal greenhouse gas emissions and harmful methane by preventing perfectly edible meals from entering landfill waste streams.</p></article><div class="directive-foot">312 Metric Tons CO₂ Saved</div></div>
        </div>
      </div>
    </section>
    <section class="container about-how" id="about-how">
      <div class="section-header"><span class="section-label">OPERATIONAL SIMPLICITY</span><h2>How Food Bridge Works</h2><p>We designed a three-step logistical handshake to route surplus food safely and swiftly within hours of preparation.</p></div>
      <div class="about-steps">
        <article class="about-step"><div class="step-label"><span class="step-number">1</span><span class="step-kind">PREPARATION</span></div><h3>Donor Lists Food</h3><p>Cooked meals, dry rations, or fresh produce are posted with portion estimates, dietary tags, storage guidelines, and a flexible pickup window.</p><small class="step-note"><i class="fa-regular fa-clock"></i> Takes under 90 seconds</small></article>
        <article class="about-step"><div class="step-label"><span class="step-number">2</span><span class="step-kind">MATCHMAKING</span></div><h3>System &amp; Center Connect</h3><p>Matched instantly with the nearest verified distribution shelter, senior day-care, or hostel pantry equipped for prompt, safe collection.</p><small class="step-note" style="color:#1682c4"><i class="fa-solid fa-location-arrow"></i> Smart vicinity routing</small></article>
        <article class="about-step"><div class="step-label"><span class="step-number">3</span><span class="step-kind">NOURISHMENT</span></div><h3>Food Feeds People</h3><p>Wholesome nourishment is served directly with dignity, zero waste, and transparent real-time confirmation logged for both parties.</p><small class="step-note" style="color:#d85b19"><i class="fa-regular fa-heart"></i> Immediate community impact</small></article>
      </div>
    </section>
    <section class="impact-strip"><div class="container impact-grid">
      <article class="impact-card"><strong>195k+</strong><span>Meals Delivered</span><small>Across city centers</small></article>
      <article class="impact-card"><strong>340+</strong><span>Active Donors</span><small>Hotels, mess &amp; households</small></article>
      <article class="impact-card"><strong>56</strong><span>Verified Centers</span><small>Shelters, care homes &amp; hubs</small></article>
      <article class="impact-card"><strong>38 min</strong><span>Avg. Claim Time</span><small>From post to confirmation</small></article>
    </div></section>
    <section class="about-cta"><div class="container about-cta-box"><div><span class="cta-tag"><i class="fa-solid fa-handshake-angle"></i> JOIN THE MOVEMENT</span><h2>Ready to contribute or coordinate with your local shelter?</h2><p>Whether you run a mess, restaurant, student hostel, or require food assistance for your center, Food Bridge is open for every neighbor.</p></div><div class="about-cta-actions"><a class="btn btn-outline-white" href="donate-food.php"><i class="fa-solid fa-hand-holding-heart"></i> Donate Food Now</a>    <a class="btn btn-green" href="need-food.php"><i class="fa-solid fa-box-heart"></i> Request Food Support</a></div></div></section>  </main>
  <footer class="footer">
    <div class="container footer-inner">
      <div class="footer-left">
        <a class="logo" href="dashboard.php">
          <span class="logo-mark"><i class="fa-solid fa-seedling"></i></span>
          <span class="logo-text"><strong>Food Bridge</strong><small>Community Food Sharing</small></span>
        </a>
        <p class="footer-desc">Connecting community food surpluses directly with local shelter kitchens, student initiatives, and families in need through an open logistical network.</p>
      </div>
      <nav class="footer-right" aria-label="Footer navigation">
        <ul>
          <li><a href="about.php">About</a></li>
          <li><a href="#about-how">How It Works</a></li>
          <li><a href="login.php">Contact</a></li>
          <li><a href="login.php">Privacy Policy</a></li>
          <li><a href="login.php">Terms &amp; Conditions</a></li>
        </ul>
      </nav>
    </div>
    <div class="container footer-bottom">
      <p>© <?php echo date('Y'); ?> Food Bridge. All rights reserved. | Developed with <i class="fa-solid fa-heart" style="color: #e25555;"></i> by <strong>Team EtherX</strong></p>
      <p>Community Food Sharing across Gujarat</p>
    </div>
  </footer>
  <script src="js/auth.js" defer></script>
</body>
</html>
