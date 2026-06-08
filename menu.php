<?php

include 'connection.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Our Menu — MCCAT</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/navbar.css" />
  <link rel="stylesheet" href="css/animations.css" />

  <style>
    /* Menu page */
    .menu-controls {
      background: var(--white);
      padding: 1.5rem 0;
      position: sticky;
      top: 70px;
      z-index: 100;
      border-bottom: 1px solid var(--gray-100);
      box-shadow: 0 2px 10px rgba(0,0,0,0.04);
    }
    .menu-controls-inner {
      display: flex;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
    }
    .search-box {
      position: relative;
      flex: 1;
      min-width: 220px;
    }
    .search-box input {
      width: 100%;
      padding: 0.65rem 1rem 0.65rem 2.75rem;
      border: 2px solid var(--gray-200);
      border-radius: var(--radius-full);
      font-size: 0.9rem;
      font-family: var(--font-main);
      outline: none;
      transition: var(--transition);
      background: var(--gray-50,#f9fafb);
    }
    .search-box input:focus {
      border-color: var(--primary);
      background: var(--white);
      box-shadow: 0 0 0 3px rgba(26,122,60,0.1);
    }
    .search-box .search-icon {
      position: absolute;
      left: 0.9rem;
      top: 50%;
      transform: translateY(-50%);
      font-size: 0.95rem;
      pointer-events: none;
    }
    .sort-select {
      padding: 0.65rem 2.25rem 0.65rem 0.9rem;
      border: 2px solid var(--gray-200);
      border-radius: var(--radius-full);
      font-size: 0.88rem;
      font-family: var(--font-main);
      outline: none;
      cursor: pointer;
      background: var(--gray-50,#f9fafb);
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath fill='%236b7280' d='M0 0l5 6 5-6z'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 0.75rem center;
      transition: var(--transition);
      white-space: nowrap;
    }
    .sort-select:focus {
      border-color: var(--primary);
    }

    /* Category tabs */
    .category-tabs-wrapper {
      background: var(--white);
      padding: 1rem 0;
      border-bottom: 1px solid var(--gray-100);
    }
    #categoryTabs {
      display: flex;
      gap: 0.6rem;
      overflow-x: auto;
      padding-bottom: 2px;
      scrollbar-width: none;
    }
    #categoryTabs::-webkit-scrollbar { display: none; }
    .category-tab {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.55rem 1.1rem;
      border-radius: var(--radius-full);
      border: 2px solid var(--gray-200);
      background: var(--white);
      font-size: 0.88rem;
      font-weight: 600;
      font-family: var(--font-main);
      color: var(--gray-600);
      cursor: pointer;
      white-space: nowrap;
      transition: var(--transition);
    }
    .category-tab:hover {
      border-color: var(--primary);
      color: var(--primary);
      background: var(--primary-pale);
    }
    .category-tab.active {
      background: var(--primary);
      border-color: var(--primary);
      color: var(--white);
      box-shadow: var(--shadow-green);
    }

    /* Results bar */
    .results-bar {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 1.5rem;
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    .results-bar p { margin: 0; font-size: 0.9rem; color: var(--gray-500); }
    .results-bar strong { color: var(--gray-800); }

    /* Food grid */
    .food-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
      gap: 1.5rem;
    }

    @media (max-width: 640px) {
      .menu-controls-inner { flex-direction: column; align-items: stretch; }
      .sort-select { width: 100%; }
    }
  </style>
</head>
<body>

  <div id="navbarPlaceholder"></div>

  <!-- Page Hero -->
  <div class="page-hero">
    <div class="container">
      <h1 class="animate-fadeInDown">🍔 Our Full Menu</h1>
      <p class="animate-fadeInUp delay-100">Fresh, delicious food made to order. Browse and pick your favorites!</p>
      <div class="breadcrumb">
        <a href="index.php">Home</a>
        <span>›</span>
        <span>Menu</span>
      </div>
    </div>
  </div>

  <!-- Controls: Search + Sort -->
  <div class="menu-controls">
    <div class="container">
      <div class="menu-controls-inner">
        <div class="search-box">
          <span class="search-icon">🔍</span>
          <input
            type="search"
            id="menuSearch"
            placeholder="Search burgers, chicken, drinks..."
            autocomplete="off"
          />
        </div>
        <select id="sortSelect" class="sort-select" aria-label="Sort items">
          <option value="default">Sort: Default</option>
          <option value="popular">🔥 Most Popular</option>
          <option value="price-asc">💰 Price: Low to High</option>
          <option value="price-desc">💰 Price: High to Low</option>
          <option value="rating">⭐ Highest Rated</option>
        </select>
      </div>
    </div>
  </div>

  <!-- Category Tabs -->
  <div class="category-tabs-wrapper">
    <div class="container">
      <div id="categoryTabs">
        <!-- Built dynamically by menu.js -->
      </div>
    </div>
  </div>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  MAIN MENU SECTION                                   ║
       ╚══════════════════════════════════════════════════════╝ -->
  <section class="section">
    <div class="container">

      <!-- Results Count -->
      <div class="results-bar">
        <p>Showing <strong><span id="resultsCount">—</span></strong> items</p>
        <a href="order.php" class="btn btn-primary btn-sm">🛒 Go to Order Form</a>
      </div>

      <!-- Food Grid (rendered by menu.js) -->
      <div id="foodGrid" class="food-grid">
        <!-- Skeleton placeholders -->
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-text short"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-text short"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-text short"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-text short"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-text short"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-text short"></div><div class="skeleton skeleton-btn"></div></div>
      </div>

    </div>
  </section>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  FOOTER                                              ║
       ╚══════════════════════════════════════════════════════╝ -->
  <div id="footerPlaceholder"></div>

  <button id="backToTop" aria-label="Back to top">↑</button>
  <div class="toast-container"></div>

  <script src="js/validation.js"></script>
  <script src="js/navbar.js"></script>
  <script src="js/animations.js"></script>
  <script src="js/menu.js"></script>
  <script>
    async function loadComponent(id, file) {
      try { document.getElementById(id).innerHTML = await (await fetch(file)).text(); } catch(e) {}
    }
    loadComponent('navbarPlaceholder', 'components/navbar.php');
    loadComponent('footerPlaceholder', 'components/footer.html');
  </script>
</body>
</html>
