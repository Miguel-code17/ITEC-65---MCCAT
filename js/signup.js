/* ============================================================
   MCCAT - Signup Script (signup.js)
   Handles: registration form validation, password strength, simulate signup
   
   PHP BACKEND INTEGRATION NOTE:
   Replace simulateSignup() with fetch() POST to api/register.php.
   The PHP script should:
     1. Sanitize & validate all inputs server-side
     2. Check if email already exists: SELECT id FROM users WHERE email = ?
     3. Hash password: $hash = password_hash($password, PASSWORD_BCRYPT)
     4. Insert user: INSERT INTO users (full_name, email, phone, password_hash) VALUES (...)
     5. Auto-login: session_start(); $_SESSION['user_id'] = $newUserId
     6. Return JSON: { success: true, redirect: 'menu.html' }
   ============================================================ */

'use strict';

document.addEventListener('DOMContentLoaded', function () {

  const signupForm     = document.getElementById('signupForm');
  if (!signupForm) return; // Guard — only run on signup page

  const firstNameInput = document.getElementById('signupFirstName');
  const lastNameInput  = document.getElementById('signupLastName');
  const emailInput     = document.getElementById('signupEmail');
  const phoneInput     = document.getElementById('signupPhone');
  const passwordInput  = document.getElementById('signupPassword');
  const confirmInput   = document.getElementById('signupConfirmPassword');
  const termsCheck     = document.getElementById('agreeTerms');
  const submitBtn      = document.getElementById('signupSubmitBtn');

  const togglePassBtn1 = document.getElementById('togglePassword1');
  const togglePassBtn2 = document.getElementById('togglePassword2');

  /* ============================================================
     SHOW / HIDE PASSWORD TOGGLES
     ============================================================ */

  function setupPasswordToggle(button, input) {
    if (!button || !input) return;
    button.addEventListener('click', function () {
      const hidden = input.type === 'password';
      input.type = hidden ? 'text' : 'password';
      button.textContent = hidden ? '🙈' : '👁️';
    });
  }

  setupPasswordToggle(togglePassBtn1, passwordInput);
  setupPasswordToggle(togglePassBtn2, confirmInput);

  /* ============================================================
     PASSWORD STRENGTH METER
     ============================================================ */

  if (passwordInput) {
    passwordInput.addEventListener('input', function () {
      updatePasswordStrength(passwordInput.value);

      // Live check confirm password match
      if (confirmInput && confirmInput.value.length > 0) {
        const matchResult = Validation.validatePasswordMatch(passwordInput.value, confirmInput.value);
        if (!matchResult.valid) {
          Validation.showFieldError(confirmInput, matchResult.message);
        } else {
          Validation.showFieldSuccess(confirmInput, matchResult.message);
        }
      }
    });

    passwordInput.addEventListener('blur', function () {
      const result = Validation.validatePassword(passwordInput.value);
      if (!result.valid && passwordInput.value.length > 0) {
        Validation.showFieldError(passwordInput, result.message);
      }
    });
  }

  function updatePasswordStrength(password) {
    const strengthContainer = document.getElementById('passwordStrength');
    if (!strengthContainer) return;

    if (!password || password.length === 0) {
      strengthContainer.style.display = 'none';
      return;
    }

    strengthContainer.style.display = 'block';
    const result = Validation.validatePassword(password);
    const bars   = strengthContainer.querySelectorAll('.strength-bar');
    const label  = strengthContainer.querySelector('.strength-label');

    const strengthMap = { weak: 1, fair: 2, good: 3, strong: 4 };
    const activeCount = strengthMap[result.strength] || 0;

    bars.forEach((bar, i) => {
      bar.className = 'strength-bar';
      if (i < activeCount) bar.classList.add(result.strength);
    });

    if (label) {
      label.textContent = result.message;
      label.className   = `strength-label ${result.strength}`;
    }
  }

  /* ============================================================
     REAL-TIME VALIDATION
     ============================================================ */

  if (firstNameInput) Validation.attachRealtimeValidation(firstNameInput, (v) => Validation.validateTextField(v, 'First name', 2, 50));
  if (lastNameInput)  Validation.attachRealtimeValidation(lastNameInput,  (v) => Validation.validateTextField(v, 'Last name', 2, 50));
  if (emailInput)    Validation.attachRealtimeValidation(emailInput,    Validation.validateEmail);
  if (phoneInput)    Validation.attachRealtimeValidation(phoneInput,    Validation.validatePhone);

  if (confirmInput) {
    confirmInput.addEventListener('input', function () {
      if (confirmInput.classList.contains('is-invalid')) {
        Validation.clearFieldState(confirmInput);
      }
    });
    confirmInput.addEventListener('blur', function () {
      const result = Validation.validatePasswordMatch(passwordInput.value, confirmInput.value);
      if (!result.valid) {
        Validation.showFieldError(confirmInput, result.message);
      } else {
        Validation.showFieldSuccess(confirmInput, result.message);
      }
    });
  }

  /* ============================================================
     FORM SUBMISSION
     ============================================================ */

  signupForm.addEventListener('submit', function (e) {
    Validation.clearFormAlert('signupAlert');

    // Gather values
    const formData = {
      first_name:       firstNameInput ? firstNameInput.value.trim() : '',
      last_name:        lastNameInput  ? lastNameInput.value.trim()  : '',
      fullname:         (firstNameInput ? firstNameInput.value.trim() : '') + ' ' + (lastNameInput ? lastNameInput.value.trim() : ''),
      email:            emailInput     ? emailInput.value.trim()     : '',
      phone:            phoneInput     ? phoneInput.value.trim()     : '',
      password:         passwordInput  ? passwordInput.value         : '',
      confirm_password: confirmInput   ? confirmInput.value          : '',
      agree_terms:      termsCheck     ? termsCheck.checked          : false
    };

    // Validate all fields
    let isValid = true;

    const nameResult = Validation.validateFullName(formData.fullname);
    if (!nameResult.valid) {
      if (firstNameInput) Validation.showFieldError(firstNameInput, nameResult.message);
      if (lastNameInput)  Validation.showFieldError(lastNameInput,  nameResult.message);
      isValid = false;
    } else {
      if (firstNameInput) Validation.showFieldSuccess(firstNameInput);
      if (lastNameInput)  Validation.showFieldSuccess(lastNameInput);
    }

    const emailResult = Validation.validateEmail(formData.email);
    if (!emailResult.valid) { Validation.showFieldError(emailInput, emailResult.message); isValid = false; }
    else Validation.showFieldSuccess(emailInput);

    const phoneResult = Validation.validatePhone(formData.phone);
    if (!phoneResult.valid) { Validation.showFieldError(phoneInput, phoneResult.message); isValid = false; }
    else Validation.showFieldSuccess(phoneInput);

    const passResult = Validation.validatePassword(formData.password);
    if (!passResult.valid) { Validation.showFieldError(passwordInput, passResult.message); isValid = false; }

    const matchResult = Validation.validatePasswordMatch(formData.password, formData.confirm_password);
    if (!matchResult.valid) { Validation.showFieldError(confirmInput, matchResult.message); isValid = false; }
    else Validation.showFieldSuccess(confirmInput);

    if (!formData.agree_terms) {
      Validation.showFormAlert('signupAlert', 'error', 'You must agree to the Terms & Conditions to continue.');
      isValid = false;
    }

    if (!isValid) {
      e.preventDefault();
      return;
    }

    setLoadingState(true);
    // Allow native form submission to the server.
  });

  /* ============================================================
     SIMULATE SIGNUP FUNCTION
     ============================================================ */

  function simulateSignup(formData) {
    console.log('[MCCAT Signup] New user registration:');
    console.log('  Name:  ', formData.fullname);
    console.log('  Email: ', formData.email);
    console.log('  Phone: ', formData.phone);
    console.log('  Pass:  ', '***hidden***');
    console.log('---');
    console.log('// PHP BACKEND INTEGRATION POINT:');
    console.log('// POST to: api/register.php');
    console.log('// Database: INSERT INTO users (fullname, email, phone, password_hash, created_at)');

    setTimeout(() => {
      setLoadingState(false);
      Validation.showFormAlert('signupAlert', 'success',
        `Welcome to MCCAT, ${formData.fullname}! Your account has been created. Redirecting to menu...`
      );
      if (window.showToast) showToast('Account created successfully! 🎉', 'success');

      setTimeout(() => { window.location.href = 'menu.php'; }, 2000);
    }, 1800);
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

});
