<?php

include 'connection.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="MCCAT - Fresh, fast, and absolutely delicious food delivered to your door." />
  <title>MCCAT - Fresh Food, Fast Delivery</title>

  <!-- CSS -->
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/navbar.css" />
  <link rel="stylesheet" href="css/animations.css" />

  <style>
    /* Hero section */
    .hero {
      min-height: 100vh;
      background: linear-gradient(135deg, #0d4f25 0%, #1a7a3c 45%, #27ae60 100%);
      display: flex;
      align-items: center;
      position: relative;
      overflow: hidden;
      padding-top: 70px;
    }
    .hero::before {
      content: '';
      position: absolute;
      top: -20%;
      right: -10%;
      width: 700px;
      height: 700px;
      background: radial-gradient(circle, rgba(255,255,255,0.06) 0%, transparent 65%);
      pointer-events: none;
    }
    .hero::after {
      content: '';
      position: absolute;
      bottom: -20%;
      left: -10%;
      width: 500px;
      height: 500px;
      background: radial-gradient(circle, rgba(243,156,18,0.08) 0%, transparent 65%);
      pointer-events: none;
    }
    .hero-inner {
      display: grid;
      grid-template-columns: 1fr 1fr;
      align-items: center;
      gap: 4rem;
      position: relative;
      z-index: 1;
      padding: 4rem 0;
    }
    .hero-tag {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: rgba(255,255,255,0.12);
      border: 1px solid rgba(255,255,255,0.2);
      backdrop-filter: blur(8px);
      color: #fff;
      font-size: 0.82rem;
      font-weight: 600;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      padding: 0.4rem 1rem;
      border-radius: 999px;
      margin-bottom: 1.5rem;
    }
    .hero-title {
      font-family: var(--font-display);
      font-size: clamp(2.8rem, 5.5vw, 4.2rem);
      font-weight: 800;
      color: #fff;
      line-height: 1.08;
      margin-bottom: 1.5rem;
    }
    .hero-title .accent { color: #f39c12; }
    .hero-title .typed-cursor {
      display: inline-block;
      width: 3px;
      height: 1em;
      background: #f39c12;
      vertical-align: text-bottom;
      animation: blink 0.8s step-end infinite;
      margin-left: 3px;
    }
    @keyframes blink { 0%,100%{opacity:1} 50%{opacity:0} }
    .hero-desc {
      color: rgba(255,255,255,0.82);
      font-size: 1.1rem;
      line-height: 1.8;
      margin-bottom: 2.25rem;
      max-width: 480px;
    }
    .hero-actions {
      display: flex;
      gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 2.5rem;
    }
    .hero-stats {
      display: flex;
      gap: 2.5rem;
      padding-top: 2rem;
      border-top: 1px solid rgba(255,255,255,0.12);
    }
    .hero-stat-value {
      font-size: 1.6rem;
      font-weight: 800;
      color: #fff;
      display: block;
    }
    .hero-stat-label {
      font-size: 0.8rem;
      color: rgba(255,255,255,0.6);
      text-transform: uppercase;
      letter-spacing: 0.06em;
    }

    /* Hero visual */
    .hero-visual {
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
    }
    .hero-plate {
      width: 420px;
      height: 420px;
      border-radius: 50%;
      background: rgba(255,255,255,0.07);
      border: 2px solid rgba(255,255,255,0.12);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11rem;
      position: relative;
      animation: float 4s ease-in-out infinite;
    }
    .hero-badge {
      position: absolute;
      background: #f39c12;
      color: #fff;
      font-weight: 800;
      font-size: 0.85rem;
      padding: 0.6rem 1rem;
      border-radius: 999px;
      box-shadow: 0 8px 24px rgba(243,156,18,0.4);
      white-space: nowrap;
    }
    .hero-badge.top    { top: 10%;  right: -5%; }
    .hero-badge.bottom { bottom: 18%; left: -8%; background: #fff; color: var(--primary); }

    /* Categories strip */
    .categories-strip {
      background: var(--white);
      padding: 3rem 0;
      border-bottom: 1px solid var(--gray-100);
    }
    .cat-grid {
      display: flex;
      gap: 1rem;
      justify-content: center;
      flex-wrap: wrap;
    }
    .cat-card {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.6rem;
      padding: 1.25rem 1.75rem;
      border-radius: var(--radius-lg);
      background: var(--gray-50, #f9fafb);
      border: 2px solid var(--gray-100);
      cursor: pointer;
      transition: var(--transition);
      text-decoration: none;
      color: var(--gray-700);
      min-width: 110px;
    }
    .cat-card:hover, .cat-card.active {
      background: var(--primary-pale);
      border-color: var(--primary);
      color: var(--primary);
      transform: translateY(-4px);
      box-shadow: var(--shadow-green);
    }
    .cat-card .emoji { font-size: 2rem; }
    .cat-card span   { font-size: 0.85rem; font-weight: 600; }

    /* ── POPULAR FOODS ────────────────────────────────────── */
    .popular-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 1.5rem;
    }

    /* How it works */
    .how-section { background: var(--gray-50, #f9fafb); }
    .steps-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 2rem;
      position: relative;
    }
    .steps-grid::before {
      content: '';
      position: absolute;
      top: 40px;
      left: 12%;
      right: 12%;
      height: 2px;
      background: linear-gradient(90deg, var(--primary-pale), var(--primary-light), var(--primary-pale));
    }
    .step-card {
      text-align: center;
      padding: 2rem 1rem;
      position: relative;
    }
    .step-number {
      width: 72px;
      height: 72px;
      border-radius: 50%;
      background: var(--white);
      border: 3px solid var(--primary);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.8rem;
      margin: 0 auto 1.25rem;
      box-shadow: var(--shadow-green);
      position: relative;
      z-index: 1;
      transition: var(--transition);
    }
    .step-card:hover .step-number {
      background: var(--primary);
      transform: scale(1.1);
    }
    .step-card h4 { margin-bottom: 0.5rem; font-size: 1rem; }
    .step-card p  { font-size: 0.88rem; color: var(--gray-500); }

    /* Reviews section */
    .reviews-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 1.5rem;
    }
    .review-card {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 1.75rem;
      box-shadow: var(--shadow-md);
      border: 1px solid var(--gray-100);
      transition: var(--transition);
      position: relative;
    }
    .review-card::before {
      content: '"';
      position: absolute;
      top: 1.25rem;
      right: 1.5rem;
      font-family: var(--font-display);
      font-size: 4rem;
      color: var(--primary-pale);
      line-height: 1;
    }
    .review-card:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); }
    .review-avatar {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      background: var(--primary-pale);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.5rem;
      margin-bottom: 1rem;
    }
    .review-name   { font-weight: 700; font-size: 0.95rem; margin-bottom: 0.2rem; }
    .review-role   { font-size: 0.78rem; color: var(--gray-400); margin-bottom: 0.75rem; }
    .review-stars  { color: #f59e0b; font-size: 0.9rem; margin-bottom: 0.75rem; }
    .review-text   { font-size: 0.9rem; color: var(--gray-600); line-height: 1.7; }

    /* Promo banner */
    .promo-banner {
      background: linear-gradient(135deg, #0d4f25, var(--primary), #27ae60);
      border-radius: var(--radius-xl);
      padding: 3.5rem;
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 2rem;
      align-items: center;
      position: relative;
      overflow: hidden;
    }
    .promo-banner::before {
      content: '🍔';
      position: absolute;
      right: 180px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 6rem;
      opacity: 0.12;
    }
    .promo-banner h2 { color: #fff; font-size: clamp(1.5rem,3vw,2.2rem); margin-bottom: 0.5rem; }
    .promo-banner p  { color: rgba(255,255,255,0.8); margin-bottom: 0; }
    .promo-code {
      display: inline-block;
      background: rgba(255,255,255,0.15);
      border: 2px dashed rgba(255,255,255,0.4);
      color: #fff;
      font-weight: 800;
      font-size: 1.1rem;
      padding: 0.4rem 1rem;
      border-radius: var(--radius-md);
      letter-spacing: 0.1em;
      margin-top: 0.75rem;
    }

    /* Responsive */
    @media (max-width: 900px) {
      .hero-inner { grid-template-columns: 1fr; text-align: center; }
      .hero-visual { display: none; }
      .hero-actions { justify-content: center; }
      .hero-stats   { justify-content: center; }
      .steps-grid   { grid-template-columns: repeat(2,1fr); }
      .steps-grid::before { display: none; }
      .reviews-grid { grid-template-columns: 1fr; }
      .promo-banner { grid-template-columns: 1fr; text-align: center; }
      .promo-banner .btn { width: 100%; justify-content: center; }
    }
    @media (max-width: 600px) {
      .steps-grid { grid-template-columns: 1fr; }
      .hero-plate { width: 280px; height: 280px; font-size: 7rem; }
    }
  </style>
</head>
<body>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  NAVBAR  (component loaded via JS)                  ║
       ╚══════════════════════════════════════════════════════╝ -->
  <div id="navbarPlaceholder"></div>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  HERO SECTION                                        ║
       ╚══════════════════════════════════════════════════════╝ -->
  <section class="hero hero-floats">
    <div class="container">
      <div class="hero-inner">

        <!-- Left: Text -->
        <div class="hero-content">
          <div class="hero-tag animate-fadeInDown">🔥 #1 Online Food Ordering in Cavite</div>

          <h1 class="hero-title animate-fadeInUp delay-100">
            Crave It.<br>
            Order It.<br>
            <span class="accent" id="typedText" data-texts='["Eat Happy! 🍔","Stay Delicious! 🍗","Love Every Bite! 🍟"]'>Eat Happy!</span><span class="typed-cursor"></span>
          </h1>

          <p class="hero-desc animate-fadeInUp delay-200">
            Hot, fresh, and made with love — MCCAT delivers your favorite meals right to your doorstep. 
            Fast. Affordable. Always delicious.
          </p>

          <div class="hero-actions animate-fadeInUp delay-300">
            <a href="order.php" class="btn btn-accent btn-lg hover-shine">
              🛒 Order Now
            </a>
            <a href="menu.php" class="btn btn-outline btn-lg">
              🍔 View Menu
            </a>
          </div>

          <div class="hero-stats animate-fadeInUp delay-400">
            <div>
              <span class="hero-stat-value counter" data-target="5000" data-suffix="+">0</span>
              <span class="hero-stat-label">Happy Customers</span>
            </div>
            <div>
              <span class="hero-stat-value counter" data-target="50" data-suffix="+">0</span>
              <span class="hero-stat-label">Menu Items</span>
            </div>
            <div>
              <span class="hero-stat-value counter" data-target="30" data-suffix=" min">0</span>
              <span class="hero-stat-label">Avg. Delivery</span>
            </div>
          </div>
        </div>

        <!-- Right: Visual -->
        <div class="hero-visual animate-scaleIn delay-300">
          <div class="hero-plate">
            🍔
            <div class="hero-badge top">⚡ Fast Delivery!</div>
            <div class="hero-badge bottom">⭐ 4.9 Rated</div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  CATEGORIES STRIP                                    ║
       ╚══════════════════════════════════════════════════════╝ -->
  <section class="categories-strip">
    <div class="container">
      <div class="cat-grid">
        <a href="menu.php" class="cat-card reveal">
          <span class="emoji">🍽️</span>
          <span>All Items</span>
        </a>
        <a href="menu.php?cat=burgers" class="cat-card reveal delay-100">
          <span class="emoji">🍔</span>
          <span>Burgers</span>
        </a>
        <a href="menu.php?cat=chicken" class="cat-card reveal delay-200">
          <span class="emoji">🍗</span>
          <span>Chicken</span>
        </a>
        <a href="menu.php?cat=sides" class="cat-card reveal delay-300">
          <span class="emoji">🍟</span>
          <span>Sides</span>
        </a>
        <a href="menu.php?cat=drinks" class="cat-card reveal delay-400">
          <span class="emoji">🥤</span>
          <span>Drinks</span>
        </a>
        <a href="menu.php?cat=desserts" class="cat-card reveal delay-500">
          <span class="emoji">🍦</span>
          <span>Desserts</span>
        </a>
      </div>
    </div>
  </section>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  POPULAR FOODS                                       ║
       ╚══════════════════════════════════════════════════════╝ -->
  <section class="section">
    <div class="container">
      <div class="section-header reveal">
        <span class="tag">🔥 Best Sellers</span>
        <h2>Most Popular Items</h2>
        <div class="divider"></div>
        <p>Our customers can't get enough of these crowd favorites. Fresh, flavorful, and always satisfying.</p>
      </div>

      <div class="popular-grid" id="popularFoodsGrid">
        <!-- Loaded dynamically by JS -->
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-btn"></div></div>
      </div>

      <div class="text-center mt-4" style="margin-top:2.5rem;">
        <a href="menu.php" class="btn btn-secondary btn-lg reveal">View Full Menu →</a>
      </div>
    </div>
  </section>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  HOW IT WORKS                                        ║
       ╚══════════════════════════════════════════════════════╝ -->
  <section class="section how-section">
    <div class="container">
      <div class="section-header reveal">
        <span class="tag">Simple Process</span>
        <h2>How It Works</h2>
        <div class="divider"></div>
        <p>Ordering your favorite food is as easy as 1-2-3-4. Hot food at your door in minutes!</p>
      </div>

      <div class="steps-grid stagger">
        <div class="step-card reveal">
          <div class="step-number">🍔</div>
          <h4>Browse Menu</h4>
          <p>Explore our wide selection of burgers, chicken, sides, drinks & desserts.</p>
        </div>
        <div class="step-card reveal">
          <div class="step-number">🛒</div>
          <h4>Choose Your Food</h4>
          <p>Pick your favorite item, select the quantity, and customize your order.</p>
        </div>
        <div class="step-card reveal">
          <div class="step-number">📋</div>
          <h4>Fill Order Form</h4>
          <p>Enter your name, contact number, and complete delivery address.</p>
        </div>
        <div class="step-card reveal">
          <div class="step-number">🚀</div>
          <h4>Fast Delivery</h4>
          <p>We prepare and deliver your fresh order hot and fast — usually under 45 min!</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  FEATURED ITEMS                                      ║
       ╚══════════════════════════════════════════════════════╝ -->
  <section class="section">
    <div class="container">
      <div class="section-header reveal">
        <span class="tag">⭐ Chef's Pick</span>
        <h2>Featured Specials</h2>
        <div class="divider"></div>
        <p>Hand-picked by our chefs — don't miss out on these amazing specials!</p>
      </div>

      <div class="popular-grid" id="featuredFoodsGrid">
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-btn"></div></div>
        <div class="skeleton-card"><div class="skeleton skeleton-img"></div><div class="skeleton skeleton-title"></div><div class="skeleton skeleton-text medium"></div><div class="skeleton skeleton-btn"></div></div>
      </div>
    </div>
  </section>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  REVIEWS                                             ║
       ╚══════════════════════════════════════════════════════╝ -->
  <section class="section" style="background:var(--gray-50,#f9fafb);">
    <div class="container">
      <div class="section-header reveal">
        <span class="tag">💬 Testimonials</span>
        <h2>What Our Customers Say</h2>
        <div class="divider"></div>
        <p>Real reviews from real hungry people. See why thousands choose MCCAT every day.</p>
      </div>

      <div class="reviews-grid stagger">
        <div class="review-card reveal">
          <div class="review-avatar">😊</div>
          <div class="review-name">Maria Santos</div>
          <div class="review-role">Regular Customer • Imus, Cavite</div>
          <div class="review-stars">★★★★★</div>
          <p class="review-text">MCCAT has the best burgers I've ever tasted! The delivery was super fast and the food arrived still hot. Will definitely order again every week!</p>
        </div>
        <div class="review-card reveal delay-100">
          <div class="review-avatar">🤩</div>
          <div class="review-name">Juan dela Cruz</div>
          <div class="review-role">Food Enthusiast • Bacoor, Cavite</div>
          <div class="review-stars">★★★★★</div>
          <p class="review-text">The spicy chicken wings are absolutely insane! Perfect crunch, amazing sauce. The ordering website is so easy to use too. 10/10 would recommend!</p>
        </div>
        <div class="review-card reveal delay-200">
          <div class="review-avatar">😍</div>
          <div class="review-name">Ana Reyes</div>
          <div class="review-role">College Student • Dasmariñas, Cavite</div>
          <div class="review-stars">★★★★☆</div>
          <p class="review-text">Affordable prices, big portions, and super yummy! The McCAT Sundae is my favorite dessert ever. My go-to late night snack order for sure.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  PROMO BANNER                                        ║
       ╚══════════════════════════════════════════════════════╝ -->
  <section class="section-sm">
    <div class="container">
      <div class="promo-banner reveal">
        <div>
          <h2>🎉 First Order Special!</h2>
          <p>Get FREE delivery on your first order. Use the promo code below at checkout.</p>
          <div class="promo-code">MCCAT-FIRST</div>
        </div>
        <a href="order.php" class="btn btn-accent btn-lg hover-shine">Order Now →</a>
      </div>
    </div>
  </section>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  FOOTER                                              ║
       ╚══════════════════════════════════════════════════════╝ -->
  <div id="footerPlaceholder"></div>

  <!-- Back to Top -->
  <button id="backToTop" aria-label="Back to top">↑</button>

  <!-- Toast Container -->
  <div class="toast-container" id="toastContainer"></div>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  SCRIPTS                                             ║
       ╚══════════════════════════════════════════════════════╝ -->
  <script src="js/validation.js"></script>
  <script src="js/navbar.js"></script>
  <script src="js/animations.js"></script>

  <script>
    // Load navbar and footer
    async function loadComponent(id, file) {
      try {
        const res  = await fetch(file);
        const html = await res.text();
        document.getElementById(id).innerHTML = html;
      } catch (e) {
        console.warn('Could not load component:', file);
      }
    }

    loadComponent('navbarPlaceholder', 'components/navbar.php');
    loadComponent('footerPlaceholder', 'components/footer.html');

    // Load popular and featured foods
    async function loadHomeFoods() {
      try {
        // PHP BACKEND: Replace with fetch('api/foods.php?featured=1')
        const res  = await fetch('data/foods.json');
        const data = await res.json();
        const all  = data.foods || [];

        renderHomeGrid('popularFoodsGrid',  all.filter(f => f.popular),  4);
        renderHomeGrid('featuredFoodsGrid', all.filter(f => f.featured), 3);
      } catch (e) {
        console.error('Failed to load home foods:', e);
      }
    }

    function renderHomeGrid(containerId, foods, limit) {
      const container = document.getElementById(containerId);
      if (!container) return;
      const items = foods.slice(0, limit);
      if (!items.length) { container.style.display = 'none'; return; }

      const emojiMap = { burgers:'🍔', chicken:'🍗', sides:'🍟', drinks:'🥤', desserts:'🍦' };
      const bgMap    = { burgers:'#fff3e0', chicken:'#fce4ec', sides:'#fffde7', drinks:'#e3f2fd', desserts:'#fce4ec' };

      container.innerHTML = items.map((f, i) => `
        <div class="food-card hover-lift reveal" style="animation-delay:${i*0.08}s">
          <div style="height:200px;background:${bgMap[f.category]||'#e8f5ee'};display:flex;align-items:center;justify-content:center;font-size:5rem;position:relative;">
            ${f.popular  ? '<span class="badge badge-green"  style="position:absolute;top:10px;left:10px;">🔥 Popular</span>'  : ''}
            ${f.featured ? '<span class="badge badge-orange" style="position:absolute;top:10px;left:10px;">⭐ Featured</span>' : ''}
            ${emojiMap[f.category]||'🍽️'}
          </div>
          <div class="food-card-body">
            <h3 class="food-card-name">${f.name}</h3>
            <p class="food-card-desc">${f.description}</p>
            <div class="food-card-rating mb-2">
              <span style="color:#f59e0b">★</span> ${f.rating} · ${f.reviews.toLocaleString()} reviews
            </div>
            <div class="food-card-footer">
              <span class="food-card-price">₱${f.price.toFixed(2)}</span>
              <a href="order.php?food=${f.id}" class="btn btn-primary btn-sm hover-shine">Order 🛒</a>
            </div>
          </div>
        </div>
      `).join('');

      // Trigger reveal
      setTimeout(() => container.querySelectorAll('.reveal').forEach(el => el.classList.add('visible')), 100);
    }

    loadHomeFoods();
  </script>
</body>
</html>
