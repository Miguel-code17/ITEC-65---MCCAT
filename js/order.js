/* ============================================================
   MCCAT — order.js
   Handles: load menu, add to cart, cart table, checkout

   HOW THE CART WORKS:
   - cartItems is a plain JavaScript array stored in memory.
   - Each item is an object: { food_id, food_name, unit_price, quantity, line_total }
   - Every time an item is added or removed the table and totals re-render.
   - On checkout the array is sent as JSON via fetch() to the backend.

   PHP BACKEND INTEGRATION:
   When you are ready to connect PHP, find the comment:
     // ── BACKEND INTEGRATION POINT ──
   and replace the simulateCheckout() call with a real fetch() POST.
   ============================================================ */

'use strict';

/* ============================================================
   CONSTANTS
   ============================================================ */

const DELIVERY_FEE            = 49;      // pesos
const FREE_DELIVERY_THRESHOLD = 500;     // pesos — order above this = free delivery

/* ============================================================
   STATE
   cartItems holds everything in the cart for this session.
   Structure of each item:
   {
     food_id:    number,   -- matches id in foods.json / foods DB table
     food_name:  string,
     unit_price: number,
     quantity:   number,
     line_total: number    -- unit_price * quantity, calculated here
   }
   ============================================================ */

let cartItems = [];   // The cart array. Reset on page reload.
let allFoods  = [];   // Full menu loaded from foods.json (or PHP later).

const CART_STORAGE_KEY = 'mccatCart';

function getCartFromStorage() {
  const raw = localStorage.getItem(CART_STORAGE_KEY);
  if (!raw) return [];
  try {
    const parsed = JSON.parse(raw);
    return Array.isArray(parsed) ? parsed : [];
  } catch (e) {
    console.warn('[MCCAT Order] Corrupt cart in storage, clearing.');
    localStorage.removeItem(CART_STORAGE_KEY);
    return [];
  }
}

function saveCartToStorage(cart) {
  localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(cart));
}

/* ============================================================
   DOM REFERENCES
   Grab every element once at the top — easier to find and change.
   ============================================================ */

const foodSelect        = document.getElementById('foodSelect');
const itemQtyInput      = document.getElementById('itemQty');
const unitPriceDisplay  = document.getElementById('unitPriceDisplay');
const addToCartBtn      = document.getElementById('addToCartBtn');

const cartEmpty         = document.getElementById('cartEmpty');
const cartTableWrapper  = document.getElementById('cartTableWrapper');
const cartTableBody     = document.getElementById('cartTableBody');

const totalSubtotalEl   = document.getElementById('totalSubtotal');
const totalDeliveryEl   = document.getElementById('totalDelivery');
const grandTotalEl      = document.getElementById('grandTotal');
const deliveryNoteEl    = document.getElementById('deliveryNote');
const amountToFreeEl    = document.getElementById('amountToFreeDelivery');

const checkoutBtn       = document.getElementById('checkoutBtn');
const orderAlert        = document.getElementById('orderAlert');
const orderFormWrapper  = document.getElementById('orderFormWrapper');
const orderSuccess      = document.getElementById('orderSuccess');
const successOrderId    = document.getElementById('successOrderId');

/* ============================================================
   STEP 1 — LOAD MENU FROM foods.json
   This fetch() mirrors exactly what you will do against PHP later.
   PHP replacement:
     const response = await fetch('api/foods.php');
   Your PHP file should return the same JSON shape:
     { "foods": [ { "id":1, "name":"...", "price":189, ... }, ... ] }
   ============================================================ */

async function loadMenu() {
  try {
    const response = await fetch('api/foods.php');

    if (!response.ok) {
      throw new Error('Network response was not OK: ' + response.status);
    }

    const data = await response.json();
    allFoods = Array.isArray(data.foods) ? data.foods : [];

    if (allFoods.length === 0) {
      throw new Error('Menu API returned no items. Falling back to static menu.');
    }

    populateFoodSelect(allFoods);
    clearAlert();

  } catch (error) {
    console.error('[MCCAT] Failed to load menu from API:', error);

    // Fallback to the static JSON menu if the backend API is not available
    // or if the API returns an empty menu list.
    try {
      const fallback = await fetch('data/foods.json');
      if (!fallback.ok) {
        throw new Error('Fallback response was not OK: ' + fallback.status);
      }
      const data = await fallback.json();
      allFoods = Array.isArray(data.foods) ? data.foods : [];
      populateFoodSelect(allFoods);
      clearAlert();
      return;
    } catch (fallbackError) {
      console.error('[MCCAT] Fallback menu load failed:', fallbackError);
      showAlert('error', 'Could not load the menu. Please refresh the page and try again.');

      if (foodSelect) {
        foodSelect.innerHTML = '<option value="">Menu unavailable — please refresh</option>';
      }
    }
  }
}

/* ============================================================
   STEP 2 — POPULATE THE FOOD <SELECT> DROPDOWN
   Groups items by category using <optgroup> so the list is easier
   to scan. Each <option> value is the food's numeric id.
   ============================================================ */

function populateFoodSelect(foods) {
  if (!foodSelect) return;

  foodSelect.innerHTML = '<option value="">-- Select a food item --</option>';

  // Group foods by category
  const groups = {};
  foods.forEach(function(food) {
    if (!groups[food.category]) {
      groups[food.category] = [];
    }
    groups[food.category].push(food);
  });

  // Build one <optgroup> per category
  Object.keys(groups).forEach(function(category) {
    const optgroup = document.createElement('optgroup');
    // Capitalise the category label
    optgroup.label = category.charAt(0).toUpperCase() + category.slice(1);

    groups[category].forEach(function(food) {
      const option       = document.createElement('option');
      option.value       = food.id;
      option.textContent = food.name + ' — ₱' + food.price.toFixed(2);
      optgroup.appendChild(option);
    });

    foodSelect.appendChild(optgroup);
  });

  // If a food was linked from menu.html (?food=3), pre-select it
  const urlParams = new URLSearchParams(window.location.search);
  const preselect = urlParams.get('food');
  if (preselect) {
    foodSelect.value = preselect;
    onFoodChange();   // trigger price display update
  }
}

/* ============================================================
   STEP 3 — FOOD SELECT CHANGE HANDLER
   When the user picks a food, show its unit price in the
   read-only price field so they know what they are adding.
   ============================================================ */

function onFoodChange() {
  const selectedId = parseInt(foodSelect.value, 10);
  const food       = allFoods.find(function(f) { return f.id === selectedId; });

  if (food) {
    unitPriceDisplay.value = '₱' + food.price.toFixed(2);
  } else {
    unitPriceDisplay.value = '₱0.00';
  }
}

/* ============================================================
   STEP 4 — ADD TO CART
   Reads the selected food + quantity, validates both,
   then either adds a new row or increases the quantity of an
   existing row if the same food is already in the cart.
   ============================================================ */

function addToCart() {
  // --- Validate food selection ---
  const selectedId = parseInt(foodSelect.value, 10);
  if (!selectedId) {
    showAlert('error', 'Please select a food item before adding to cart.');
    foodSelect.focus();
    return;
  }

  // --- Validate quantity ---
  const qty = parseInt(itemQtyInput.value, 10);
  const qtyResult = Validation.validateQuantity(qty);
  if (!qtyResult.valid) {
    showAlert('error', qtyResult.message);
    itemQtyInput.focus();
    return;
  }

  // --- Find the food object ---
  const food = allFoods.find(function(f) { return f.id === selectedId; });
  if (!food) {
    showAlert('error', 'Selected item not found. Please refresh the page.');
    return;
  }

  // --- Check if this food is already in the cart ---
  // If it is, just increase the quantity instead of adding a duplicate row.
  const existing = cartItems.find(function(item) { return item.food_id === food.id; });

  if (existing) {
    const newQty = existing.quantity + qty;

    // Cap at 50 per item
    if (newQty > 50) {
      showAlert('error', 'You already have ' + existing.quantity + ' of this item. Maximum is 50 per item.');
      return;
    }

    existing.quantity   = newQty;
    existing.line_total = parseFloat((existing.unit_price * newQty).toFixed(2));

  } else {
    // Build the cart item object
    // These property names match the database columns in the `order_items` table.
    const newItem = {
      food_id:    food.id,
      food_name:  food.name,
      unit_price: food.price,
      quantity:   qty,
      line_total: parseFloat((food.price * qty).toFixed(2))
    };
    cartItems.push(newItem);
  }

  // Reset the add-item controls for the next pick
  foodSelect.value       = '';
  itemQtyInput.value     = 1;
  unitPriceDisplay.value = '₱0.00';

  clearAlert();
  saveCartToStorage(cartItems);
  renderCart();
}

/* ============================================================
   STEP 5 — REMOVE ITEM FROM CART
   Called by the Remove button in each table row.
   Filters out the item at the given index and re-renders.
   ============================================================ */

function removeFromCart(index) {
  cartItems.splice(index, 1);
  saveCartToStorage(cartItems);
  renderCart();
}

/* ============================================================
   STEP 6 — RENDER CART TABLE
   Rebuilds the entire table body and totals from the cartItems
   array every time anything changes. This is intentionally
   simple — no virtual DOM, no diffing. Just clear and rebuild.
   ============================================================ */

function renderCart() {
  if (cartItems.length === 0) {
    // Show empty state, hide the table
    cartEmpty.style.display       = 'block';
    cartTableWrapper.style.display = 'none';
    if (checkoutBtn) checkoutBtn.disabled = true;
    return;
  }

  // Hide empty state, show the table
  cartEmpty.style.display       = 'none';
  cartTableWrapper.style.display = 'block';
  if (checkoutBtn) checkoutBtn.disabled = false;

  // --- Build table rows ---
  cartTableBody.innerHTML = '';

  cartItems.forEach(function(item, index) {
    const tr = document.createElement('tr');
    tr.innerHTML =
      '<td>' + escapeHTML(item.food_name) + '</td>' +
      '<td style="text-align:center;">' +
        '<div class="qty-controls" style="display:inline-flex;align-items:center;gap:0.4rem;">' +
          '<button class="btn-qty" onclick="decreaseQty(' + index + ')" aria-label="Decrease">−</button>' +
          '<span>' + item.quantity + '</span>' +
          '<button class="btn-qty" onclick="increaseQty(' + index + ')" aria-label="Increase">+</button>' +
        '</div>' +
      '</td>' +
      '<td class="price-col">₱' + item.unit_price.toFixed(2) + '</td>' +
      '<td class="price-col">₱' + item.line_total.toFixed(2) + '</td>' +
      '<td class="action-col">' +
        '<button class="btn-remove" onclick="removeFromCart(' + index + ')" ' +
        'aria-label="Remove ' + escapeHTML(item.food_name) + ' from cart">✕</button>' +
      '</td>';
    cartTableBody.appendChild(tr);
  });

  // --- Calculate totals ---
  const subtotal    = cartItems.reduce(function(sum, item) { return sum + item.line_total; }, 0);
  const deliveryFee = subtotal >= FREE_DELIVERY_THRESHOLD ? 0 : DELIVERY_FEE;
  const grandTotal  = subtotal + deliveryFee;

  // --- Update totals display ---
  totalSubtotalEl.textContent = '₱' + subtotal.toFixed(2);
  grandTotalEl.textContent    = '₱' + grandTotal.toFixed(2);

  if (deliveryFee === 0) {
    totalDeliveryEl.textContent = 'FREE 🎉';
    totalDeliveryEl.style.color = '#16a34a';
    deliveryNoteEl.textContent  = 'You qualify for FREE delivery!';
    deliveryNoteEl.style.color  = '#16a34a';
  } else {
    totalDeliveryEl.textContent = '₱' + deliveryFee.toFixed(2);
    totalDeliveryEl.style.color = '';
    const needed = FREE_DELIVERY_THRESHOLD - subtotal;
    amountToFreeEl.textContent  = needed.toFixed(2);
    deliveryNoteEl.style.color  = '#6b7280';
  }
}

/* ============================================================
   STEP 7 — VALIDATE CUSTOMER FORM
   Runs validation on the three required customer fields before
   the checkout fetch fires. Uses shared Validation helpers from
   validation.js so rules stay consistent across the whole site.
   ============================================================ */

function validateCustomerForm() {
  let isValid = true;

  const phoneInput   = document.getElementById('orderPhone');
  const addressInput = document.getElementById('orderAddress');

  // Clear any previous error state first
  [phoneInput, addressInput].forEach(function(el) {
    if (el) Validation.clearFieldState(el);
  });

  // Name comes from the authenticated session, no validation needed

  const phoneResult = Validation.validatePhone(phoneInput ? phoneInput.value : '');
  if (!phoneResult.valid) {
    Validation.showFieldError(phoneInput, phoneResult.message);
    isValid = false;
  }

  const addressResult = Validation.validateAddress(addressInput ? addressInput.value : '');
  if (!addressResult.valid) {
    Validation.showFieldError(addressInput, addressResult.message);
    isValid = false;
  }

  return isValid;
}

/* ============================================================
   STEP 8 — CHECKOUT (SUBMIT ORDER)
   Collects customer info + cart array into one JSON payload,
   then calls submitOrder() which sends it to the backend.

   PAYLOAD SHAPE sent to api/place-order.php:
   {
     "customer": {
       "name":    "Juan dela Cruz",
       "phone":   "09123456789",
       "address": "123 Street, Imus, Cavite",
       "notes":   "Extra sauce please"
     },
     "cart": [
       { "food_id": 1, "food_name": "Classic McCAT Burger",
         "unit_price": 189.00, "quantity": 2, "line_total": 378.00 },
       ...
     ],
     "subtotal":     378.00,
     "delivery_fee": 49.00,
     "grand_total":  427.00
   }
   ============================================================ */

function checkout() {
  // 1. Make sure the cart is not empty
  if (cartItems.length === 0) {
    showAlert('error', 'Your cart is empty. Please add at least one item before checking out.');
    return;
  }

  // 2. Validate the customer form fields
  if (!validateCustomerForm()) {
    showAlert('error', 'Please fill in all required customer fields correctly.');
    // Scroll up so the user sees the errors
    document.getElementById('orderPhone').scrollIntoView({ behavior: 'smooth', block: 'center' });
    return;
  }

  // 3. Build the payload
  const subtotal    = cartItems.reduce(function(sum, item) { return sum + item.line_total; }, 0);
  const deliveryFee = subtotal >= FREE_DELIVERY_THRESHOLD ? 0 : DELIVERY_FEE;
  const grandTotal  = subtotal + deliveryFee;

  const payload = {
    customer: {
      name:    document.getElementById('authenticatedUserName').value.trim(),
      phone:   document.getElementById('orderPhone').value.trim(),
      address: document.getElementById('orderAddress').value.trim(),
      notes:   document.getElementById('orderNotes').value.trim()
    },
    cart:         cartItems,
    subtotal:     parseFloat(subtotal.toFixed(2)),
    delivery_fee: deliveryFee,
    grand_total:  parseFloat(grandTotal.toFixed(2))
  };

  // 4. Disable the button to prevent double-submit
  checkoutBtn.disabled      = true;
  checkoutBtn.textContent   = 'Placing order...';

  // Send the order payload to the PHP backend
  submitOrder(payload);
}

/* ============================================================
   submitOrder() — REAL FETCH TO PHP BACKEND
   This function sends the order payload to api/place-order.php.

   What your PHP file should do:
     1. Receive raw JSON:  $data = json_decode(file_get_contents('php://input'), true);
     2. Validate server-side (never trust the client alone).
     3. Begin a MySQL transaction.
     4. INSERT INTO orders (customer_name, phone, address, notes, subtotal, delivery_fee, grand_total, status)
     5. Loop over $data['cart'] and INSERT INTO order_items (order_id, food_id, food_name, unit_price, quantity, line_total)
     6. COMMIT.
     7. Return:  echo json_encode(['success' => true, 'order_id' => $newOrderId]);
   ============================================================ */

async function submitOrder(payload) {
  try {
    const apiUrl = new URL('api/place-order.php', window.location.href).href;
    console.log('[MCCAT] Sending order payload to', apiUrl);

    const response = await fetch(apiUrl, {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify(payload)
    });

    if (!response.ok) {
      const text = await response.text();
      let errorMessage = text;
      try {
        const json = JSON.parse(text);
        if (json && json.message) errorMessage = json.message;
      } catch (_e) {
        // ignore invalid JSON
      }
      throw new Error('Server returned status ' + response.status + ' - ' + errorMessage);
    }

    const result = await response.json();

    if (result.success) {
      onOrderSuccess(result.order_id);
    } else {
      showAlert('error', result.message || 'Order failed. Please try again.');
      checkoutBtn.disabled    = false;
      checkoutBtn.textContent = '🚀 Place Order';
    }

  } catch (error) {
    console.error('[MCCAT] Order submission failed:', error);
    showAlert('error', error.message || 'Could not reach the server. Please check your connection and try again.');
    checkoutBtn.disabled    = false;
    checkoutBtn.textContent = '🚀 Place Order';
  }
}

/* ============================================================
   simulateCheckout() — FRONTEND DEMO ONLY
   Mimics a network delay so you can see the full flow without
   a backend. DELETE this function when you connect PHP.
   ============================================================ */

function simulateCheckout(payload) {
  // Log the exact payload that will be sent to PHP — useful for
  // building the backend and checking the data shape.
  console.log('=== MCCAT ORDER PAYLOAD (would be sent to api/place-order.php) ===');
  console.log(JSON.stringify(payload, null, 2));
  console.log('===================================================================');

  // Simulate a 1.5-second network round-trip
  setTimeout(function() {
    // Generate a fake order ID — PHP will return a real DB auto-increment ID
    const fakeOrderId = 'MC' + Date.now().toString().slice(-6);
    onOrderSuccess(fakeOrderId);
  }, 1500);
}

/* ============================================================
   onOrderSuccess() — SHOW THE SUCCESS STATE
   Called by both simulateCheckout() and submitOrder() so the
   UI update logic lives in one place.
   ============================================================ */

function onOrderSuccess(orderId) {
  // Show the success banner
  successOrderId.textContent = '#' + orderId;
  orderSuccess.style.display = 'block';

  // Hide the order form
  orderFormWrapper.style.display = 'none';

  // Clear the cart from memory
  cartItems = [];
  // Remove persisted cart
  try { localStorage.removeItem(CART_STORAGE_KEY); } catch (e) {}

  // Scroll to the top of the page so the user sees the message
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ============================================================
   ALERT HELPERS
   Simple inline alert above the form. No toast library needed.
   ============================================================ */

function showAlert(type, message) {
  if (!orderAlert) return;
  const icons = { error: '❌', success: '✅', warning: '⚠️' };
  orderAlert.innerHTML =
    '<div class="alert alert-' + type + '">' +
      '<span class="alert-icon">' + (icons[type] || 'ℹ️') + '</span>' +
      '<span>' + message + '</span>' +
    '</div>';
  orderAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearAlert() {
  if (orderAlert) orderAlert.innerHTML = '';
}

/* ============================================================
   ESCAPE HTML
   Prevents XSS when inserting user-influenced data (food names)
   into the table via innerHTML.
   NOTE: Always sanitise again on the PHP side — this is only
   a client-side precaution.
   ============================================================ */

function escapeHTML(str) {
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

/* ============================================================
   EVENT LISTENERS — wired up once the DOM is ready
   ============================================================ */

document.addEventListener('DOMContentLoaded', function() {

  // Load the menu dropdown
  loadMenu();

  // Update price preview when the user picks a food
  if (foodSelect) {
    foodSelect.addEventListener('change', onFoodChange);
  }

  // Add to cart button
  if (addToCartBtn) {
    addToCartBtn.addEventListener('click', addToCart);
  }

  // Allow pressing Enter in the qty field to also add to cart
  if (itemQtyInput) {
    itemQtyInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        addToCart();
      }
    });
  }

  // Checkout button
  if (checkoutBtn) {
    checkoutBtn.addEventListener('click', checkout);
  }

  // Initial render — shows the empty cart state
  // Load saved cart from localStorage (if any)
  cartItems = getCartFromStorage();
  renderCart();
});

// Quantity helpers exposed globally for inline onclick handlers
function increaseQty(index) {
  if (!cartItems[index]) return;
  if (cartItems[index].quantity >= 50) return;
  cartItems[index].quantity += 1;
  cartItems[index].line_total = parseFloat((cartItems[index].unit_price * cartItems[index].quantity).toFixed(2));
  saveCartToStorage(cartItems);
  renderCart();
}

function decreaseQty(index) {
  if (!cartItems[index]) return;
  cartItems[index].quantity -= 1;
  if (cartItems[index].quantity <= 0) {
    cartItems.splice(index, 1);
  } else {
    cartItems[index].line_total = parseFloat((cartItems[index].unit_price * cartItems[index].quantity).toFixed(2));
  }
  saveCartToStorage(cartItems);
  renderCart();
}