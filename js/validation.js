/* ============================================================
   MCCAT - Validation Functions (validation.js)
   Shared reusable validation utilities across all pages.
   NOTE: These rules should be mirrored on the PHP backend
         for security. Client-side validation = UX only.
   ============================================================ */

'use strict';

/* ============================================================
   FIELD VALIDATORS
   ============================================================ */

/**
 * Validate email format
 * @param {string} email
 * @returns {{ valid: boolean, message: string }}
 */
function validateEmail(email) {
  if (!email || email.trim() === '') {
    return { valid: false, message: 'Email is required.' };
  }
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email.trim())) {
    return { valid: false, message: 'Please enter a valid email address.' };
  }
  return { valid: true, message: '' };
}

/**
 * Validate password strength
 * @param {string} password
 * @returns {{ valid: boolean, message: string, strength: string, score: number }}
 */
function validatePassword(password) {
  if (!password || password.length === 0) {
    return { valid: false, message: 'Password is required.', strength: '', score: 0 };
  }
  if (password.length < 8) {
    return { valid: false, message: 'Password must be at least 8 characters.', strength: 'weak', score: 1 };
  }

  let score = 0;
  if (password.length >= 8)  score++;
  if (password.length >= 12) score++;
  if (/[A-Z]/.test(password)) score++;
  if (/[a-z]/.test(password)) score++;
  if (/[0-9]/.test(password)) score++;
  if (/[^A-Za-z0-9]/.test(password)) score++;

  let strength = '';
  let message  = '';
  if (score <= 2)      { strength = 'weak';   message = 'Weak password. Add uppercase and numbers.'; }
  else if (score <= 3) { strength = 'fair';   message = 'Fair password. Add special characters.'; }
  else if (score <= 4) { strength = 'good';   message = 'Good password!'; }
  else                 { strength = 'strong'; message = 'Strong password! ✓'; }

  return { valid: score >= 2, message, strength, score };
}

/**
 * Validate password confirmation
 * @param {string} password
 * @param {string} confirmPassword
 * @returns {{ valid: boolean, message: string }}
 */
function validatePasswordMatch(password, confirmPassword) {
  if (!confirmPassword || confirmPassword.length === 0) {
    return { valid: false, message: 'Please confirm your password.' };
  }
  if (password !== confirmPassword) {
    return { valid: false, message: 'Passwords do not match.' };
  }
  return { valid: true, message: 'Passwords match! ✓' };
}

/**
 * Validate full name
 * @param {string} name
 * @returns {{ valid: boolean, message: string }}
 */
function validateFullName(name) {
  if (!name || name.trim() === '') {
    return { valid: false, message: 'Full name is required.' };
  }
  if (name.trim().length < 2) {
    return { valid: false, message: 'Name must be at least 2 characters.' };
  }
  if (name.trim().length > 100) {
    return { valid: false, message: 'Name must not exceed 100 characters.' };
  }
  const nameRegex = /^[a-zA-Z\s\-'.]+$/;
  if (!nameRegex.test(name.trim())) {
    return { valid: false, message: 'Name can only contain letters, spaces, and hyphens.' };
  }
  return { valid: true, message: '' };
}

/**
 * Validate Philippine phone number
 * @param {string} phone
 * @returns {{ valid: boolean, message: string }}
 */
function validatePhone(phone) {
  if (!phone || phone.trim() === '') {
    return { valid: false, message: 'Contact number is required.' };
  }
  const cleaned = phone.replace(/[\s\-\(\)]/g, '');
  const phRegex  = /^(\+63|0)9[0-9]{9}$/;
  const landline = /^(\+63|0)?[2-8][0-9]{6,8}$/;
  if (!phRegex.test(cleaned) && !landline.test(cleaned)) {
    return { valid: false, message: 'Enter a valid PH number (e.g. 09123456789).' };
  }
  return { valid: true, message: '' };
}

/**
 * Validate text field (required + length)
 * @param {string} value
 * @param {string} fieldName
 * @param {number} min
 * @param {number} max
 * @returns {{ valid: boolean, message: string }}
 */
function validateTextField(value, fieldName, min = 1, max = 500) {
  if (!value || value.trim() === '') {
    return { valid: false, message: `${fieldName} is required.` };
  }
  if (value.trim().length < min) {
    return { valid: false, message: `${fieldName} must be at least ${min} characters.` };
  }
  if (value.trim().length > max) {
    return { valid: false, message: `${fieldName} must not exceed ${max} characters.` };
  }
  return { valid: true, message: '' };
}

/**
 * Validate address
 * @param {string} address
 * @returns {{ valid: boolean, message: string }}
 */
function validateAddress(address) {
  if (!address || address.trim() === '') {
    return { valid: false, message: 'Delivery address is required.' };
  }
  if (address.trim().length < 10) {
    return { valid: false, message: 'Please enter a complete address.' };
  }
  return { valid: true, message: '' };
}

/**
 * Validate select field
 * @param {string} value
 * @param {string} fieldName
 * @returns {{ valid: boolean, message: string }}
 */
function validateSelect(value, fieldName) {
  if (!value || value === '' || value === '0') {
    return { valid: false, message: `Please select a ${fieldName}.` };
  }
  return { valid: true, message: '' };
}

/**
 * Validate quantity
 * @param {number|string} qty
 * @returns {{ valid: boolean, message: string }}
 */
function validateQuantity(qty) {
  const num = parseInt(qty, 10);
  if (isNaN(num) || num < 1) {
    return { valid: false, message: 'Quantity must be at least 1.' };
  }
  if (num > 50) {
    return { valid: false, message: 'Maximum order quantity is 50.' };
  }
  return { valid: true, message: '' };
}

/* ============================================================
   UI HELPERS
   ============================================================ */

/**
 * Show field error under an input
 * @param {HTMLElement} input
 * @param {string} message
 */
function showFieldError(input, message) {
  clearFieldState(input);
  input.classList.add('is-invalid');

  const errorEl = document.createElement('span');
  errorEl.className = 'field-error';
  errorEl.textContent = message;
  input.parentNode.insertBefore(errorEl, input.nextSibling);
}

/**
 * Show field success state
 * @param {HTMLElement} input
 * @param {string} message
 */
function showFieldSuccess(input, message = '') {
  clearFieldState(input);
  input.classList.add('is-valid');

  if (message) {
    const successEl = document.createElement('span');
    successEl.className = 'field-success';
    successEl.textContent = message;
    input.parentNode.insertBefore(successEl, input.nextSibling);
  }
}

/**
 * Clear field validation state
 * @param {HTMLElement} input
 */
function clearFieldState(input) {
  input.classList.remove('is-valid', 'is-invalid');
  const siblings = input.parentNode.querySelectorAll('.field-error, .field-success');
  siblings.forEach(el => el.remove());
}

/**
 * Show form-level alert
 * @param {string} containerId - ID of element to insert alert into
 * @param {string} type - 'success' | 'error' | 'warning' | 'info'
 * @param {string} message
 */
function showFormAlert(containerId, type, message) {
  const container = document.getElementById(containerId);
  if (!container) return;

  const icons = { success: '✅', error: '❌', warning: '⚠️', info: 'ℹ️' };
  container.innerHTML = `
    <div class="alert alert-${type}">
      <span class="alert-icon">${icons[type] || 'ℹ️'}</span>
      <span>${message}</span>
    </div>
  `;
  container.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

/**
 * Clear form-level alert
 * @param {string} containerId
 */
function clearFormAlert(containerId) {
  const container = document.getElementById(containerId);
  if (container) container.innerHTML = '';
}

/* ============================================================
   REAL-TIME VALIDATION SETUP
   ============================================================ */

/**
 * Attach blur/input listeners for real-time validation
 * @param {HTMLElement} input
 * @param {Function} validatorFn - returns { valid, message }
 */
function attachRealtimeValidation(input, validatorFn) {
  const validate = () => {
    const result = validatorFn(input.value);
    if (!result.valid) {
      showFieldError(input, result.message);
    } else {
      showFieldSuccess(input);
    }
  };

  input.addEventListener('blur', validate);
  input.addEventListener('input', () => {
    // Clear error on typing; re-validate on blur
    if (input.classList.contains('is-invalid')) {
      clearFieldState(input);
    }
  });
}

/* ============================================================
   SANITIZE HELPERS
   ============================================================ */

/**
 * Sanitize input to prevent XSS (basic)
 * NOTE: Always sanitize on the PHP backend — never trust client-side
 * @param {string} str
 * @returns {string}
 */
function sanitizeInput(str) {
  const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  return String(str).replace(/[&<>"']/g, m => map[m]);
}

/**
 * Format Philippine Peso
 * @param {number} amount
 * @returns {string}
 */
function formatPeso(amount) {
  return `₱${parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',')}`;
}

/* ============================================================
   EXPORT (for use in other scripts)
   ============================================================ */

// Make available globally (browser script tag usage)
window.Validation = {
  validateEmail,
  validatePassword,
  validatePasswordMatch,
  validateFullName,
  validatePhone,
  validateTextField,
  validateAddress,
  validateSelect,
  validateQuantity,
  showFieldError,
  showFieldSuccess,
  clearFieldState,
  showFormAlert,
  clearFormAlert,
  attachRealtimeValidation,
  sanitizeInput,
  formatPeso
};
