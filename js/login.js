/* ============================================================
   MCCAT - Login Script (login.js)
   Handles: login form validation, show/hide password, simulate login
   
   PHP BACKEND INTEGRATION NOTE:
   Replace the simulateLogin() function with a real fetch() POST
   to your PHP endpoint (e.g., api/login.php).
   The PHP script should:
     1. Sanitize inputs
     2. Query the database (SELECT * FROM users WHERE email = ?)
     3. Verify password using password_verify()
     4. Start a session: session_start(); $_SESSION['user_id'] = ...
     5. Return JSON: { success: true, redirect: 'menu.html' }
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

  const loginForm      = document.getElementById('loginForm');
  const emailInput     = document.getElementById('loginEmail');
  const passwordInput  = document.getElementById('loginPassword');
  const rememberMe     = document.getElementById('rememberMe');
  const togglePassBtn  = document.getElementById('togglePassword');
  const submitBtn      = document.getElementById('loginSubmitBtn');
  const alertContainer = document.getElementById('loginAlert');

  if (!loginForm) return; // Guard — only run on login page

  /* ============================================================
     SHOW / HIDE PASSWORD TOGGLE
     ============================================================ */

  if (togglePassBtn && passwordInput) {
    togglePassBtn.addEventListener('click', function () {
      const isHidden = passwordInput.type === 'password';
      passwordInput.type = isHidden ? 'text' : 'password';
      togglePassBtn.textContent = isHidden ? '🙈' : '👁️';
      togglePassBtn.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
    });
  }

  /* ============================================================
     REAL-TIME VALIDATION SETUP
     ============================================================ */

  if (emailInput) {
    Validation.attachRealtimeValidation(emailInput, Validation.validateEmail);
  }

  if (passwordInput) {
    passwordInput.addEventListener('blur', function () {
      if (passwordInput.value.length > 0 && passwordInput.value.length < 8) {
        Validation.showFieldError(passwordInput, 'Password must be at least 8 characters.');
      } else if (passwordInput.value.length >= 8) {
        Validation.showFieldSuccess(passwordInput);
      }
    });
    passwordInput.addEventListener('input', function () {
      if (passwordInput.classList.contains('is-invalid')) {
        Validation.clearFieldState(passwordInput);
      }
    });
  }

  /* ============================================================
     FORM SUBMISSION
     ============================================================ */

  loginForm.addEventListener('submit', function (e) {
    e.preventDefault();
    Validation.clearFormAlert('loginAlert');

    const email    = emailInput ? emailInput.value.trim() : '';
    const password = passwordInput ? passwordInput.value : '';

    // --- Client-side Validation ---
    let isValid = true;

    const emailResult = Validation.validateEmail(email);
    if (!emailResult.valid) {
      Validation.showFieldError(emailInput, emailResult.message);
      isValid = false;
    } else {
      Validation.showFieldSuccess(emailInput);
    }

    if (!password || password.length < 8) {
      Validation.showFieldError(passwordInput, 'Password must be at least 8 characters.');
      isValid = false;
    } else {
      Validation.showFieldSuccess(passwordInput);
    }

    if (!isValid) return;

    // --- Set loading state ---
    setLoadingState(true);

    // -----------------------------------------------------------
    // SIMULATE LOGIN (Frontend only)
    // -----------------------------------------------------------
    // REPLACE THIS WITH PHP BACKEND LATER:
    //
    // fetch('api/login.php', {
    //   method: 'POST',
    //   headers: { 'Content-Type': 'application/json' },
    //   body: JSON.stringify({ email, password, remember: rememberMe.checked })
    // })
    // .then(res => res.json())
    // .then(data => {
    //   if (data.success) {
    //     window.location.href = data.redirect || 'menu.html';
    //   } else {
    //     showLoginError(data.message || 'Invalid credentials.');
    //   }
    // })
    // .catch(err => {
    //   showLoginError('Server error. Please try again.');
    //   console.error('[Login Error]', err);
    // })
    // .finally(() => setLoadingState(false));
    // -----------------------------------------------------------

    simulateLogin(email, password);
  });

  /* ============================================================
     SIMULATE LOGIN FUNCTION
     (Demo purposes only — replace with real API call)
     ============================================================ */

  function simulateLogin(email, password) {
    // Log for demo visibility
    console.log('[MCCAT Login] Attempting login for:', email);
    console.log('[MCCAT Login] Remember Me:', rememberMe ? rememberMe.checked : false);
    console.log('---');
    console.log('// PHP BACKEND INTEGRATION POINT:');
    console.log('// POST to: api/login.php');
    console.log('// Payload:', JSON.stringify({ email, password: '***hidden***', remember: rememberMe ? rememberMe.checked : false }));

    // Simulate network delay
    setTimeout(() => {
      setLoadingState(false);

      // Demo: Accept any properly formatted email + password >= 8 chars
      const isDemo = true; // Remove this in PHP version

      if (isDemo) {
        showLoginSuccess('Login successful! Redirecting...');
        setTimeout(() => {
          window.location.href = 'menu.html';
        }, 1500);
      } else {
        showLoginError('Invalid email or password. Please try again.');
      }
    }, 1500);
  }

  /* ============================================================
     UI STATE HELPERS
     ============================================================ */

  function setLoadingState(loading) {
    if (!submitBtn) return;
    submitBtn.disabled = loading;

    const btnText   = submitBtn.querySelector('.btn-text');
    const btnLoader = submitBtn.querySelector('.btn-loader');

    if (loading) {
      submitBtn.classList.add('loading');
      if (btnText)   btnText.style.opacity   = '0';
      if (btnLoader) btnLoader.style.display = 'flex';
    } else {
      submitBtn.classList.remove('loading');
      if (btnText)   btnText.style.opacity   = '1';
      if (btnLoader) btnLoader.style.display = 'none';
    }
  }

  function showLoginError(message) {
    Validation.showFormAlert('loginAlert', 'error', message);
    if (window.showToast) showToast(message, 'error');
  }

  function showLoginSuccess(message) {
    Validation.showFormAlert('loginAlert', 'success', message);
    if (window.showToast) showToast(message, 'success');
  }

  /* ============================================================
     FORGOT PASSWORD LINK
     ============================================================ */

  const forgotLink = document.getElementById('forgotPasswordLink');
  if (forgotLink) {
    forgotLink.addEventListener('click', function (e) {
      e.preventDefault();
      // TODO: Implement forgot password flow
      // In PHP: POST to api/forgot-password.php with email
      Validation.showFormAlert('loginAlert', 'info',
        'Password reset feature coming soon. Please contact support at hello@mccat.com'
      );
    });
  }

});
