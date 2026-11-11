<?php
if (!function_exists('currentUser')) {
    require_once __DIR__ . '/../config/auth.php';
}

$activeHeaderPage = $activeHeaderPage ?? '';
$headerNavigation = [
    ['page' => 'home', 'label' => 'Home', 'href' => 'dashboard.php'],
    ['page' => 'about', 'label' => 'About', 'href' => 'about.php'],
    ['page' => 'donate', 'label' => 'Donate Food', 'href' => 'donate-food.php'],
    ['page' => 'need', 'label' => 'Need Food', 'href' => 'need-food.php'],
    ['page' => 'donations', 'label' => 'My Donations', 'href' => 'my-donations.php'],
    ['page' => 'requests', 'label' => 'My Requests', 'href' => 'my-requests.php'],
];

$user = currentUser();
$userName = $user ? $user['name'] : 'Dhruv Gohel';
$nameParts = preg_split('/\s+/', trim($userName));
$initials = '';
if (!empty($nameParts)) {
    $initials = mb_strtoupper(mb_substr($nameParts[0], 0, 1));
    if (count($nameParts) > 1) {
        $initials .= mb_strtoupper(mb_substr(end($nameParts), 0, 1));
    }
} else {
    $initials = 'DG';
}
?>
<header class="app-header">
  <div class="app-header-inner">
    <a class="app-header-brand" href="dashboard.php" aria-label="Food Bridge home">
      <span class="app-header-mark" aria-hidden="true"><i class="fa-solid fa-seedling"></i></span>
      <span class="app-header-brand-copy">
        <span class="app-header-brand-name">Food Bridge</span>
        <span class="app-header-brand-subtitle">Community Food Sharing</span>
      </span>
    </a>
    <button class="menu-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="app-main-nav">
      <span></span><span></span><span></span>
    </button>
    <nav class="app-header-nav" id="app-main-nav" aria-label="Main navigation">
      <?php foreach ($headerNavigation as $item): ?>
        <a href="<?php echo htmlspecialchars($item['href'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo $activeHeaderPage === $item['page'] ? ' class="is-active" aria-current="page"' : ''; ?>>
          <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="app-header-actions">
      <a href="need-food.php" class="app-header-notifications" aria-label="Need food notifications" title="Food Requests">
        <i class="fa-regular fa-bell" aria-hidden="true"></i>
      </a>
      <details class="app-header-user-menu">
        <summary>
          <span class="app-header-avatar"><?php echo htmlspecialchars($initials); ?></span>
          <span class="app-header-name"><?php echo htmlspecialchars($userName); ?></span>
          <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </summary>
        <div class="app-header-user-dropdown">
          <a href="profile.php"><i class="fa-solid fa-user"></i> My Profile</a>
          <a href="my-donations.php"><i class="fa-solid fa-box-open"></i> My Donations</a>
          <a href="my-requests.php"><i class="fa-solid fa-bowl-food"></i> My Requests</a>
          <a href="logout.php"><i class="fa-solid fa-arrow-right-from-bracket"></i> Logout</a>
        </div>
      </details>
    </div>
  </div>
</header>
