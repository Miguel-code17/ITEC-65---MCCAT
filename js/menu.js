/* ============================================================
   MCCAT - Menu Script (menu.js)
   Loads food items from data/foods.json using fetch()
   
   PHP BACKEND INTEGRATION NOTE:
   Replace the fetch('data/foods.json') call with:
   fetch('api/foods.php?category=' + activeCategory)
   
   Your PHP endpoint (api/foods.php) should:
     1. Connect to MySQL database
     2. Run: SELECT * FROM foods WHERE category = ? (or all if empty)
     3. Return: header('Content-Type: application/json'); echo json_encode($foods);
   
   The JS rendering code STAYS THE SAME — only the data source changes.
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

  /* ============================================================
     STATE
     ============================================================ */

  let allFoods       = [];
  let allCategories  = [];
  let activeCategory = 'all';
  let searchQuery    = '';

  /* ============================================================
     DOM REFERENCES
     ============================================================ */

  const foodGrid         = document.getElementById('foodGrid');
  const categoryTabs     = document.getElementById('categoryTabs');
  const searchInput      = document.getElementById('menuSearch');
  const resultsCount     = document.getElementById('resultsCount');
  const sortSelect       = document.getElementById('sortSelect');

  const CART_STORAGE_KEY = 'mccatCart';
  const toastContainer = document.querySelector('.toast-container');

  function getCartFromStorage() {
    const raw = localStorage.getItem(CART_STORAGE_KEY);
    if (!raw) return [];
    try {
      const parsed = JSON.parse(raw);
      return Array.isArray(parsed) ? parsed : [];
    } catch (e) {
      console.warn('[MCCAT Menu] Invalid cart data in storage, resetting.', e);
      localStorage.removeItem(CART_STORAGE_KEY);
      return [];
    }
  }

  function saveCartToStorage(cart) {
    localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
  }

  function showNotification(message) {
    if (!toastContainer) {
      alert(message);
      return;
    }
    const toast = document.createElement('div');
    toast.className = 'menu-toast';
    toast.textContent = message;
    toast.style.cssText = 'position:fixed;bottom:1.5rem;right:1.5rem;z-index:1000;padding:0.9rem 1.2rem;background:#111;color:#fff;border-radius:0.75rem;box-shadow:0 10px 30px rgba(0,0,0,0.18);font-size:0.95rem;';
    toastContainer.appendChild(toast);
    setTimeout(() => toast.remove(), 2600);
  }

  function addToCartFromMenu(foodId) {
    const food = allFoods.find(item => item.id === foodId);
    if (!food) {
      showNotification('Could not add selected item to cart. Please refresh and try again.');
      return;
    }

    const cart = getCartFromStorage();
    const existing = cart.find(item => item.food_id === food.id);

    if (existing) {
      existing.quantity = Math.min(existing.quantity + 1, 50);
      existing.line_total = parseFloat((existing.unit_price * existing.quantity).toFixed(2));
    } else {
      cart.push({
        food_id: food.id,
        food_name: food.name,
        unit_price: parseFloat(food.price.toFixed(2)),
        quantity: 1,
        line_total: parseFloat(food.price.toFixed(2))
      });
    }

    saveCartToStorage(cart);
    showNotification(`Added ${food.name} to cart`);
  }

  // Handle clicks on Add to Cart buttons rendered inside foodGrid
  foodGrid.addEventListener('click', function (event) {
    const btn = event.target.closest('.add-to-cart-menu');
    if (!btn) return;
    const id = parseInt(btn.dataset.foodId, 10);
    if (Number.isInteger(id)) addToCartFromMenu(id);
  });

  if (!foodGrid) return; // Guard — only run on menu page

  /* ============================================================
     LOAD FOODS FROM JSON (SIMULATES API CALL)
     ============================================================ */

  async function loadFoods() {
    showLoadingSkeleton();

    try {
      // -----------------------------------------------------------
      // FRONTEND: Load from local JSON file
      // -----------------------------------------------------------
      const response = await fetch('data/foods.json');

      // -----------------------------------------------------------
      // PHP BACKEND REPLACEMENT:
      // const response = await fetch(`api/foods.php?category=${activeCategory}&search=${encodeURIComponent(searchQuery)}`);
      // -----------------------------------------------------------

      if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);

      const data = await response.json();

      // Store data
      allFoods      = data.foods      || [];
      allCategories = data.categories || [];

      // Build category tabs
      renderCategoryTabs();

      // Check URL for category param (e.g., menu.html?cat=burgers)
      const urlParams = new URLSearchParams(window.location.search);
      const catParam  = urlParams.get('cat');
      if (catParam) {
        activeCategory = catParam;
        updateActiveCategoryTab();
      }

      // Render
      renderFoods();

    } catch (error) {
      console.error('[MCCAT Menu] Failed to load foods:', error);
      showErrorState();
    }
  }

  /* ============================================================
     RENDER CATEGORY TABS
     ============================================================ */

  function renderCategoryTabs() {
    if (!categoryTabs) return;

    const allTab = document.createElement('button');
    allTab.className  = 'category-tab active';
    allTab.dataset.cat = 'all';
    allTab.innerHTML  = '<span>🍽️</span> All Items';
    categoryTabs.appendChild(allTab);

    allCategories.forEach(cat => {
      const tab = document.createElement('button');
      tab.className  = 'category-tab';
      tab.dataset.cat = cat.id;
      tab.innerHTML  = `<span>${cat.icon}</span> ${cat.name}`;
      categoryTabs.appendChild(tab);
    });

    // Event delegation for tabs
    categoryTabs.addEventListener('click', function (e) {
      const tab = e.target.closest('.category-tab');
      if (!tab) return;

      activeCategory = tab.dataset.cat;
      updateActiveCategoryTab();
      renderFoods();

      // Scroll to grid on mobile
      if (window.innerWidth < 768) {
        foodGrid.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }

  function updateActiveCategoryTab() {
    if (!categoryTabs) return;
    categoryTabs.querySelectorAll('.category-tab').forEach(tab => {
      tab.classList.toggle('active', tab.dataset.cat === activeCategory);
    });
  }

  /* ============================================================
     SEARCH
     ============================================================ */

  if (searchInput) {
    let searchTimeout;
    searchInput.addEventListener('input', function () {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        searchQuery = searchInput.value.trim().toLowerCase();
        renderFoods();
      }, 300);
    });
  }

  /* ============================================================
     SORT
     ============================================================ */

  if (sortSelect) {
    sortSelect.addEventListener('change', renderFoods);
  }

  /* ============================================================
     FILTER & SORT LOGIC
     ============================================================ */

  function getFilteredFoods() {
    let foods = [...allFoods];

    // Category filter
    if (activeCategory !== 'all') {
      foods = foods.filter(f => f.category === activeCategory);
    }

    // Search filter
    if (searchQuery) {
      foods = foods.filter(f =>
        f.name.toLowerCase().includes(searchQuery) ||
        f.description.toLowerCase().includes(searchQuery)
      );
    }

    // Sort
    const sortVal = sortSelect ? sortSelect.value : 'default';
    switch (sortVal) {
      case 'price-asc':
        foods.sort((a, b) => a.price - b.price);
        break;
      case 'price-desc':
        foods.sort((a, b) => b.price - a.price);
        break;
      case 'rating':
        foods.sort((a, b) => b.rating - a.rating);
        break;
      case 'popular':
        foods.sort((a, b) => (b.popular ? 1 : 0) - (a.popular ? 1 : 0));
        break;
      default:
        break;
    }

    return foods;
  }

  /* ============================================================
     RENDER FOODS GRID
     ============================================================ */

  function renderFoods() {
    const foods = getFilteredFoods();

    // Update results count
    if (resultsCount) {
      resultsCount.textContent = `${foods.length} item${foods.length !== 1 ? 's' : ''}`;
    }

    if (foods.length === 0) {
      showEmptyState();
      return;
    }

    foodGrid.innerHTML = '';
    foodGrid.className = 'food-grid grid-auto stagger';

    foods.forEach((food, index) => {
      const card = createFoodCard(food);
      card.classList.add('reveal');
      card.style.animationDelay = `${index * 0.05}s`;
      foodGrid.appendChild(card);
    });

    // Trigger scroll reveal
    setTimeout(() => {
      foodGrid.querySelectorAll('.reveal').forEach(el => el.classList.add('visible'));
    }, 50);
  }

  /* ============================================================
     CREATE FOOD CARD ELEMENT
     ============================================================ */

  function createFoodCard(food) {
    const card = document.createElement('div');
    card.className = 'food-card hover-lift';

    const stars = generateStars(food.rating);
    const isFeatured = food.featured ? '<span class="badge badge-orange" style="position:absolute;top:12px;left:12px;z-index:1;">⭐ Featured</span>' : '';
    const isPopular  = food.popular  ? '<span class="badge badge-green" style="position:absolute;top:12px;right:12px;z-index:1;">🔥 Popular</span>'  : '';

    card.innerHTML = `
      <div class="food-card-image" style="position:relative;">
        ${isFeatured}
        ${isPopular}
        ${generateFoodSVG(food)}
      </div>
      <div class="food-card-body">
        <h3 class="food-card-name">${escapeHTML(food.name)}</h3>
        <p class="food-card-desc">${escapeHTML(food.description)}</p>
        <div class="food-card-rating mb-2">
          <span class="star">${stars}</span>
          <span>${food.rating}</span>
          <span style="color:var(--gray-300)">•</span>
          <span>${food.reviews.toLocaleString()} reviews</span>
        </div>
        <div class="food-card-footer">
          <div>
            <div class="food-card-price">₱${food.price.toFixed(2)}</div>
          </div>
          <button type="button" class="btn btn-primary btn-sm add-to-cart-menu hover-shine" data-food-id="${food.id}">
            Add to Cart
          </button>
        </div>
      </div>
    `;

    return card;
  }

  /* ============================================================
     GENERATE FOOD SVG PLACEHOLDER
     (Replace <img> tags when you have real food images)
     ============================================================ */

  function generateFoodSVG(food) {
    const emojiMap = {
      burgers:  '🍔',
      chicken:  '🍗',
      sides:    '🍟',
      drinks:   '🥤',
      desserts: '🍦'
    };
    const emoji = emojiMap[food.category] || '🍽️';
    const colors = {
      burgers:  '#fff3e0',
      chicken:  '#fce4ec',
      sides:    '#fffde7',
      drinks:   '#e3f2fd',
      desserts: '#fce4ec'
    };
    const bg = colors[food.category] || '#e8f5ee';

    return `
      <div style="
        width:100%;height:200px;
        background:${bg};
        display:flex;align-items:center;justify-content:center;
        font-size:80px;
        transition:transform 0.3s ease;
      " class="food-emoji-display">
        ${emoji}
      </div>
    `;
  }

  /* ============================================================
     STAR RATING GENERATOR
     ============================================================ */

  function generateStars(rating) {
    const full  = Math.floor(rating);
    const half  = rating % 1 >= 0.5 ? 1 : 0;
    const empty = 5 - full - half;
    return '★'.repeat(full) + (half ? '½' : '') + '☆'.repeat(empty);
  }

  /* ============================================================
     LOADING SKELETON
     ============================================================ */

  function showLoadingSkeleton() {
    foodGrid.innerHTML = '';
    foodGrid.className = 'food-grid grid-auto';
    for (let i = 0; i < 8; i++) {
      foodGrid.innerHTML += `
        <div class="skeleton-card">
          <div class="skeleton skeleton-img"></div>
          <div class="skeleton skeleton-title"></div>
          <div class="skeleton skeleton-text medium"></div>
          <div class="skeleton skeleton-text short"></div>
          <div class="skeleton skeleton-btn"></div>
        </div>
      `;
    }
  }

  /* ============================================================
     EMPTY / ERROR STATES
     ============================================================ */

  function showEmptyState() {
    foodGrid.innerHTML = `
      <div class="empty-state" style="grid-column:1/-1;text-align:center;padding:4rem;">
        <div style="font-size:4rem;margin-bottom:1rem;">🔍</div>
        <h3>No items found</h3>
        <p>Try a different category or search term.</p>
        <button onclick="clearSearch()" class="btn btn-primary mt-3" style="margin-top:1.5rem;">
          Clear Search
        </button>
      </div>
    `;
  }

  function showErrorState() {
    foodGrid.innerHTML = `
      <div class="empty-state" style="grid-column:1/-1;">
        <div style="font-size:4rem;margin-bottom:1rem;">⚠️</div>
        <h3>Failed to Load Menu</h3>
        <p>We couldn't load the menu. Please try again.</p>
        <button onclick="location.reload()" class="btn btn-primary mt-3" style="margin-top:1.5rem;">
          Retry
        </button>
      </div>
    `;
  }

  /* ============================================================
     UTILITIES
     ============================================================ */

  window.clearSearch = function () {
    searchQuery        = '';
    activeCategory     = 'all';
    if (searchInput) searchInput.value = '';
    updateActiveCategoryTab();
    renderFoods();
  };

  function escapeHTML(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ============================================================
     INITIALIZE
     ============================================================ */

  loadFoods();

});
