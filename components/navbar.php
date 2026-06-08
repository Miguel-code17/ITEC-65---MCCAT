<?php
session_start();
$userName = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : null;
$isLoggedIn = !empty($_SESSION['user_id']);
?>
<nav id="navbar">
  <div class="navbar-container">

    <!-- Logo -->
    <a href="index.php" class="navbar-logo">
      <div class="logo-icon">🐱</div>
      <span class="logo-text">MC<span>CAT</span></span>
    </a>

    <!-- Navigation Menu -->
    <ul class="navbar-menu" id="navbarMenu">
      <li class="nav-item">
        <a href="index.php" class="nav-link" data-page="index">
          <span class="nav-icon">🏠</span> Home
        </a>
      </li>
      <li class="nav-item">
        <a href="menu.php" class="nav-link" data-page="menu">
          <span class="nav-icon">🍔</span> Menu
        </a>
      </li>
      <li class="nav-item">
        <a href="order.php" class="nav-link" data-page="order">
          <span class="nav-icon">🛒</span> Order
        </a>
      </li>
      <li class="nav-item">
        <a href="about.html" class="nav-link" data-page="about">
          <span class="nav-icon">ℹ️</span> About
        </a>
      </li>
      <li class="nav-item">
        <a href="contact.html" class="nav-link" data-page="contact">
          <span class="nav-icon">📞</span> Contact
        </a>
      </li>

      <?php if ($isLoggedIn): ?>
      <li class="nav-item nav-user-badge">
        <span class="nav-link nav-user">👋 Hello, <?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
      </li>
      <?php endif; ?>

      <!-- Mobile-only divider & actions -->
      <div class="nav-divider"></div>
      <div class="mobile-nav-actions">
        <?php if ($isLoggedIn): ?>
          <a href="logout.php" class="btn btn-secondary">Logout</a>
          <a href="order.php" class="btn btn-primary">Order Now 🛒</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-secondary">Login</a>
          <a href="order.php" class="btn btn-primary">Order Now 🛒</a>
        <?php endif; ?>
      </div>
    </ul>

    <!-- Desktop Actions -->
    <div class="navbar-actions">
      <?php if ($isLoggedIn): ?>

        <a href="logout.php" class="btn btn-secondary">Logout</a>
        <a href="order.php" class="btn btn-primary">Order Now 🛒</a>
      <?php else: ?>
        <a href="login.php" class="btn-login">Login</a>
        <a href="order.php" class="btn-order-now">Order Now 🛒</a>
      <?php endif; ?>
    </div>

    <!-- Hamburger Button -->
    <button class="hamburger" id="hamburger" aria-label="Toggle navigation" aria-expanded="false">
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
      <span class="hamburger-line"></span>
    </button>

  </div>
</nav>

<!-- Mobile Menu Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>
