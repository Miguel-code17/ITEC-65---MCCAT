/* ============================================================
   MCCAT - Animations Script (animations.js)
   Handles: scroll reveal, counters, ripple, page transitions
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

  /* ============================================================
     SCROLL REVEAL — Intersection Observer
     Elements with .reveal, .reveal-left, .reveal-right, .reveal-scale
     become visible when they enter the viewport
     ============================================================ */

  const revealClasses = ['.reveal', '.reveal-left', '.reveal-right', '.reveal-scale'];
  const revealSelector = revealClasses.join(', ');
  const revealElements = document.querySelectorAll(revealSelector);

  if (revealElements.length > 0 && 'IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            // Optional: stop observing after reveal
            // revealObserver.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.12,
        rootMargin: '0px 0px -40px 0px'
      }
    );

    revealElements.forEach(el => revealObserver.observe(el));
  } else {
    // Fallback: show all immediately
    revealElements.forEach(el => el.classList.add('visible'));
  }

  /* ============================================================
     ANIMATED COUNTER
     Elements: <span class="counter" data-target="1200" data-suffix="+">0</span>
     ============================================================ */

  function animateCounter(el) {
    const target   = parseInt(el.getAttribute('data-target') || el.textContent, 10);
    const suffix   = el.getAttribute('data-suffix') || '';
    const prefix   = el.getAttribute('data-prefix') || '';
    const duration = parseInt(el.getAttribute('data-duration') || '2000', 10);
    const start    = 0;
    const startTime = performance.now();

    function easeOutCubic(t) {
      return 1 - Math.pow(1 - t, 3);
    }

    function step(currentTime) {
      const elapsed  = currentTime - startTime;
      const progress = Math.min(elapsed / duration, 1);
      const eased    = easeOutCubic(progress);
      const current  = Math.floor(eased * (target - start) + start);

      el.textContent = prefix + current.toLocaleString() + suffix;

      if (progress < 1) {
        requestAnimationFrame(step);
      } else {
        el.textContent = prefix + target.toLocaleString() + suffix;
      }
    }

    requestAnimationFrame(step);
  }

  const counterElements = document.querySelectorAll('.counter');

  if (counterElements.length > 0 && 'IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            animateCounter(entry.target);
            counterObserver.unobserve(entry.target); // only animate once
          }
        });
      },
      { threshold: 0.5 }
    );

    counterElements.forEach(el => counterObserver.observe(el));
  }

  /* ============================================================
     RIPPLE EFFECT ON BUTTONS
     Adds .btn-ripple behavior to all .btn elements
     ============================================================ */

  const rippleButtons = document.querySelectorAll('.btn');

  rippleButtons.forEach(btn => {
    btn.classList.add('btn-ripple');
    btn.addEventListener('click', function (e) {
      const rect   = btn.getBoundingClientRect();
      const x      = e.clientX - rect.left;
      const y      = e.clientY - rect.top;
      const size   = Math.max(rect.width, rect.height) * 2;

      const ripple = document.createElement('span');
      ripple.className = 'ripple-effect';
      ripple.style.cssText = `
        width: ${size}px;
        height: ${size}px;
        left: ${x - size / 2}px;
        top: ${y - size / 2}px;
      `;

      btn.appendChild(ripple);
      ripple.addEventListener('animationend', () => ripple.remove());
    });
  });

  /* ============================================================
     PARALLAX EFFECT for Hero Section
     ============================================================ */

  const parallaxHero = document.querySelector('.hero-parallax');

  if (parallaxHero) {
    window.addEventListener('scroll', function () {
      const scrolled = window.pageYOffset;
      parallaxHero.style.transform = `translateY(${scrolled * 0.3}px)`;
    }, { passive: true });
  }

  /* ============================================================
     FLOATING FOOD ELEMENTS CREATION
     Adds decorative food emojis to hero section
     ============================================================ */

  const heroSection = document.querySelector('.hero-floats');

  if (heroSection) {
    const foods = ['🍔', '🍟', '🍗', '🥤', '🍦', '🌮', '🍕', '🥪'];
    const count = 5;

    for (let i = 0; i < count; i++) {
      const el = document.createElement('span');
      el.className = 'floating-food';
      el.textContent = foods[i % foods.length];
      heroSection.appendChild(el);
    }
  }

  /* ============================================================
     SMOOTH PAGE ENTRANCE
     ============================================================ */

  document.body.classList.add('page-transition');

  /* ============================================================
     TYPED TEXT EFFECT (hero heading)
     Target: element with id="typedText" and data-texts attribute
     Example: data-texts='["Fresh & Delicious","Fast Delivery","Best Burgers"]'
     ============================================================ */

  const typedEl = document.getElementById('typedText');

  if (typedEl) {
    let texts = [];
    try {
      texts = JSON.parse(typedEl.getAttribute('data-texts') || '[]');
    } catch (e) {
      texts = [];
    }

    if (texts.length > 0) {
      let textIndex  = 0;
      let charIndex  = 0;
      let isDeleting = false;
      const typingSpeed  = 80;
      const deletingSpeed = 45;
      const pauseTime = 1800;

      function typeChar() {
        const current = texts[textIndex];
        if (isDeleting) {
          typedEl.textContent = current.substring(0, charIndex - 1);
          charIndex--;
        } else {
          typedEl.textContent = current.substring(0, charIndex + 1);
          charIndex++;
        }

        let delay = isDeleting ? deletingSpeed : typingSpeed;

        if (!isDeleting && charIndex === current.length) {
          delay = pauseTime;
          isDeleting = true;
        } else if (isDeleting && charIndex === 0) {
          isDeleting  = false;
          textIndex   = (textIndex + 1) % texts.length;
          delay       = 300;
        }

        setTimeout(typeChar, delay);
      }

      typeChar();
    }
  }

  /* ============================================================
     IMAGE LAZY LOADING
     ============================================================ */

  const lazyImages = document.querySelectorAll('img[data-src]');

  if (lazyImages.length > 0 && 'IntersectionObserver' in window) {
    const imageObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const img = entry.target;
            img.src = img.getAttribute('data-src');
            img.removeAttribute('data-src');
            imageObserver.unobserve(img);
          }
        });
      },
      { rootMargin: '200px 0px' }
    );

    lazyImages.forEach(img => imageObserver.observe(img));
  }

  /* ============================================================
     FOOD CARD HOVER SOUND (optional, muted by default)
     ============================================================ */

  // Intentionally left minimal — add Web Audio API sounds here if desired

});
