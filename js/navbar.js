/* ============================================================
   MCCAT - Navbar Script (navbar.js)
   Handles: mobile menu toggle, scroll behavior, active links
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

  const navbar       = document.getElementById('navbar');
  const hamburger    = document.getElementById('hamburger');
  const navbarMenu   = document.getElementById('navbarMenu');
  const mobileOverlay = document.getElementById('mobileOverlay');

  /* ============================================================
     MOBILE MENU TOGGLE
     ============================================================ */

  function openMenu() {
    hamburger.classList.add('open');
    navbarMenu.classList.add('mobile-open');
    mobileOverlay.classList.add('active');
    hamburger.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden'; // prevent scroll when menu open
  }

  function closeMenu() {
    hamburger.classList.remove('open');
    navbarMenu.classList.remove('mobile-open');
    mobileOverlay.classList.remove('active');
    hamburger.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  function toggleMenu() {
    if (navbarMenu.classList.contains('mobile-open')) {
      closeMenu();
    } else {
      openMenu();
    }
  }

  if (hamburger) hamburger.addEventListener('click', toggleMenu);
  if (mobileOverlay) mobileOverlay.addEventListener('click', closeMenu);

  // Close menu when a nav link is clicked
  const navLinks = navbarMenu ? navbarMenu.querySelectorAll('.nav-link') : [];
  navLinks.forEach(link => {
    link.addEventListener('click', closeMenu);
  });

  // Close on Escape key
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeMenu();
  });

  /* ============================================================
     SCROLL BEHAVIOR — shrink navbar on scroll
     ============================================================ */

  function handleScroll() {
    if (window.scrollY > 60) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  }

  window.addEventListener('scroll', handleScroll, { passive: true });
  handleScroll(); // run on load

  /* ============================================================
     ACTIVE LINK DETECTION
     Sets .active class based on current page filename
     ============================================================ */

  function setActiveNavLink() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    const pageBase    = currentPage.replace('.html', '') || 'index';

    navLinks.forEach(link => {
      link.classList.remove('active');
      const linkPage = link.getAttribute('data-page') || '';
      if (linkPage === pageBase) {
        link.classList.add('active');
      }
    });
  }

  setActiveNavLink();

  /* ============================================================
     BACK TO TOP BUTTON
     ============================================================ */

  const backToTop = document.getElementById('backToTop');

  if (backToTop) {
    window.addEventListener('scroll', function () {
      if (window.scrollY > 400) {
        backToTop.classList.add('visible');
      } else {
        backToTop.classList.remove('visible');
      }
    }, { passive: true });

    backToTop.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
  }

  /* ============================================================
     TOAST NOTIFICATION SYSTEM
     ============================================================ */

  /**
   * Show a toast notification
   * @param {string} message
   * @param {string} type - 'success' | 'error' | 'warning' | 'info'
   * @param {number} duration - ms before auto-dismiss
   */
  window.showToast = function (message, type = 'success', duration = 3500) {
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
    const colors = { success: 'var(--success)', error: 'var(--danger)', warning: 'var(--warning)', info: '#3b82f6' };

    const toast = document.createElement('div');
    toast.className = 'toast';
    toast.style.borderLeftColor = colors[type] || colors.info;
    toast.innerHTML = `
      <span class="toast-icon">${icons[type] || icons.info}</span>
      <span class="toast-message">${message}</span>
      <span class="toast-close" role="button" aria-label="Close">✕</span>
    `;

    container.appendChild(toast);

    // Trigger animation
    requestAnimationFrame(() => {
      requestAnimationFrame(() => toast.classList.add('show'));
    });

    // Close button
    toast.querySelector('.toast-close').addEventListener('click', () => dismissToast(toast));

    // Auto dismiss
    const timer = setTimeout(() => dismissToast(toast), duration);
    toast._timer = timer;
  };

  function dismissToast(toast) {
    clearTimeout(toast._timer);
    toast.classList.remove('show');
    toast.style.transform = 'translateX(120%)';
    setTimeout(() => toast.remove(), 400);
  }

});
