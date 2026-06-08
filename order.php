<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Place an Order — MCCAT</title>

  <!-- Project stylesheets (no animations.css needed here) -->
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/navbar.css" />
  <link rel="stylesheet" href="css/forms.css" />

  <style>
    /* Order page styles */

    /* Page wrapper */
    .order-page {
      padding: 2.5rem 0 4rem;
    }

    /* Two-column layout */
    .order-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
      align-items: start;
    }

    /* Section boxes */
    .order-box {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 10px;
      padding: 1.75rem;
      margin-bottom: 1.5rem;
    }

    .order-box h3 {
      font-size: 1rem;
      font-weight: 700;
      color: #111827;
      margin-bottom: 1.25rem;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid #f3f4f6;
    }

    /* Add-to-cart row */
    .add-item-row {
      display: grid;
      grid-template-columns: 1fr 90px auto;
      gap: 0.75rem;
      align-items: end;
    }

    /* Cart table */
    .cart-table {
      width: 100%;
      border-collapse: collapse;
      font-size: 0.9rem;
    }

    .cart-table thead tr {
      background: #f3f4f6;
    }

    .cart-table th,
    .cart-table td {
      padding: 0.75rem 0.9rem;
      text-align: left;
      border-bottom: 1px solid #e5e7eb;
    }

    .cart-table th {
      font-size: 0.78rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #6b7280;
    }

    .cart-table td.price-col,
    .cart-table th.price-col {
      text-align: right;
    }

    .cart-table td.action-col {
      text-align: center;
      width: 60px;
    }

    .cart-table tbody tr:last-child td {
      border-bottom: none;
    }

    /* Subtotal row */
    .cart-table tfoot tr {
      background: #f9fafb;
    }

    .cart-table tfoot td {
      font-weight: 700;
      border-top: 2px solid #e5e7eb;
      border-bottom: none;
      font-size: 0.95rem;
    }

    /* Remove button */
    .btn-remove {
      background: #fee2e2;
      color: #dc2626;
      border: none;
      border-radius: 6px;
      padding: 0.3rem 0.6rem;
      font-size: 0.82rem;
      font-weight: 600;
      cursor: pointer;
    }

    .btn-remove:hover {
      background: #fecaca;
    }

    /* Empty cart */
    .cart-empty {
      text-align: center;
      padding: 2rem 1rem;
      color: #9ca3af;
      font-size: 0.9rem;
    }

    .cart-empty .cart-empty-icon {
      font-size: 2.5rem;
      display: block;
      margin-bottom: 0.5rem;
    }

    /* Totals */
    .cart-totals {
      margin-top: 1rem;
      border-top: 1px solid #e5e7eb;
      padding-top: 1rem;
    }

    .totals-row {
      display: flex;
      justify-content: space-between;
      font-size: 0.9rem;
      padding: 0.3rem 0;
      color: #4b5563;
    }

    .totals-row.grand-total {
      font-size: 1.1rem;
      font-weight: 800;
      color: #111827;
      border-top: 2px solid #e5e7eb;
      margin-top: 0.4rem;
      padding-top: 0.75rem;
    }

    .totals-row .amount-green {
      color: #1a7a3c;
    }

    /* Delivery note */
    .delivery-note {
      font-size: 0.8rem;
      color: #6b7280;
      margin-top: 0.5rem;
      text-align: right;
    }

    /* Checkout button */
    .btn-checkout {
      width: 100%;
      margin-top: 1.25rem;
      padding: 0.85rem;
      background: #1a7a3c;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      font-family: inherit;
    }

    .btn-checkout:hover {
      background: #145e2e;
    }

    .btn-checkout:disabled {
      background: #9ca3af;
      cursor: not-allowed;
    }

    /* Add to cart button */
    .btn-add-to-cart {
      padding: 0.72rem 1.1rem;
      background: #1a7a3c;
      color: #fff;
      border: none;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      white-space: nowrap;
      font-family: inherit;
    }

    .btn-add-to-cart:hover {
      background: #145e2e;
    }

    /* Success message */
    .order-success {
      display: none;
      background: #d1fae5;
      border: 1px solid #6ee7b7;
      border-radius: 10px;
      padding: 2rem;
      text-align: center;
      margin-bottom: 1.5rem;
    }

    .order-success .success-emoji {
      font-size: 3rem;
      display: block;
      margin-bottom: 0.75rem;
    }

    .order-success h3 {
      color: #065f46;
      margin-bottom: 0.4rem;
    }

    .order-success p {
      color: #047857;
      font-size: 0.9rem;
      margin-bottom: 0;
    }

    .order-success .order-id {
      display: inline-block;
      background: #fff;
      border: 1px dashed #059669;
      color: #065f46;
      font-weight: 800;
      padding: 0.25rem 0.9rem;
      border-radius: 999px;
      font-size: 0.95rem;
      margin: 0.75rem 0;
    }

    /* Delivery info */
    .delivery-info-list {
      list-style: none;
      padding: 0;
      margin: 0;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
    }

    .delivery-info-list li {
      display: flex;
      gap: 0.6rem;
      font-size: 0.88rem;
      color: #4b5563;
      align-items: flex-start;
    }

    .delivery-info-list li .di-icon {
      flex-shrink: 0;
      margin-top: 1px;
    }

    /* Page hero */
    .page-hero-simple {
      background: linear-gradient(135deg, #145e2e, #1a7a3c);
      padding: 3rem 0 2.25rem;
      text-align: center;
    }

    .page-hero-simple h1 {
      color: #fff;
      font-size: 1.9rem;
      margin-bottom: 0.4rem;
    }

    .page-hero-simple p {
      color: rgba(255,255,255,0.8);
      font-size: 0.95rem;
    }

    .page-hero-simple .breadcrumb {
      justify-content: center;
      display: flex;
      gap: 0.5rem;
      margin-top: 0.75rem;
      font-size: 0.85rem;
      color: rgba(255,255,255,0.65);
    }

    .page-hero-simple .breadcrumb a {
      color: rgba(255,255,255,0.85);
      text-decoration: none;
    }

    /* Responsive */
    @media (max-width: 800px) {
      .order-grid {
        grid-template-columns: 1fr;
      }

      .add-item-row {
        grid-template-columns: 1fr 80px;
      }

      /* Move button to own row on small screens */
      .add-item-row .btn-add-to-cart {
        grid-column: 1 / -1;
      }
    }

    @media (max-width: 480px) {
      .order-box {
        padding: 1.25rem;
      }
    }
  </style>
</head>
<body>

  <!-- Navbar loaded from component -->
  <div id="navbarPlaceholder"></div>

  <!-- ── Page Hero ─────────────────────────────────────────── -->
  <div class="page-hero-simple">
    <div class="container">
      <h1>🛒 Place Your Order</h1>
      <p>Choose your items, review your cart, then checkout.</p>
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="index.php">Home</a>
        <span>›</span>
        <a href="menu.php">Menu</a>
        <span>›</span>
        <span>Order</span>
      </nav>
    </div>
  </div>

  <!-- ── Main Content ──────────────────────────────────────── -->
  <main class="order-page">
    <div class="container">

      <!-- Global alert (validation errors, server errors) -->
      <div id="orderAlert"></div>

      <!-- Success message — hidden until checkout completes -->
      <div class="order-success" id="orderSuccess" role="alert">
        <span class="success-emoji">🎉</span>
        <h3>Order Placed Successfully!</h3>
        <div class="order-id" id="successOrderId">#MC000000</div>
        <p>Our team is preparing your food now. Estimated delivery: <strong>30–45 minutes</strong>.</p>
        <p style="margin-top:0.5rem;">We'll contact you at the number you provided.</p>
        <div style="margin-top:1.25rem;display:flex;gap:0.75rem;justify-content:center;flex-wrap:wrap;">
          <a href="menu.php" class="btn btn-secondary btn-sm">← Back to Menu</a>
          <a href="index.php" class="btn btn-primary btn-sm">🏠 Go Home</a>
        </div>
      </div>

      <!-- Order form — hidden after successful checkout -->
      <div id="orderFormWrapper">
        <div class="order-grid">

          <!-- ════════════════════════════════════════════════
               LEFT COLUMN — Customer info + Add item
               ════════════════════════════════════════════════ -->
          <div>

            <!-- Section 1: Customer Information -->
            <!--
              PHP BACKEND NOTE:
              These field names (customer_name, phone, address, notes)
              map directly to columns in the `orders` database table.
              Keep them consistent when you wire up api/place-order.php.
            -->
            <div class="order-box">
              <h3>👤 Customer Information</h3>

              <div class="form-group">
                <label for="orderName" class="form-label">
                  Full Name <span class="required">*</span>
                </label>
                <input
                  type="text"
                  id="orderName"
                  name="customer_name"
                  class="form-control"
                  placeholder="Juan dela Cruz"
                  autocomplete="name"
                  maxlength="100"
                  required
                />
              </div>

              <div class="form-group">
                <label for="orderPhone" class="form-label">
                  Contact Number <span class="required">*</span>
                </label>
                <input
                  type="tel"
                  id="orderPhone"
                  name="phone"
                  class="form-control"
                  placeholder="09123456789"
                  autocomplete="tel"
                  maxlength="15"
                  required
                />
                <span class="form-hint">We'll call or text this number for updates.</span>
              </div>

              <div class="form-group">
                <label for="orderAddress" class="form-label">
                  Delivery Address <span class="required">*</span>
                </label>
                <textarea
                  id="orderAddress"
                  name="address"
                  class="form-control"
                  placeholder="House/Unit No., Street, Barangay, City, Province"
                  rows="3"
                  required
                ></textarea>
                <span class="form-hint">Provide a complete address for accurate delivery.</span>
              </div>

              <div class="form-group" style="margin-bottom:0;">
                <label for="orderNotes" class="form-label">
                  Special Instructions
                  <span style="font-weight:400;color:#9ca3af;">(optional)</span>
                </label>
                <textarea
                  id="orderNotes"
                  name="notes"
                  class="form-control"
                  placeholder="e.g. No onions, extra sauce, call when nearby..."
                  rows="2"
                ></textarea>
              </div>
            </div>

            <!-- Section 2: Add Item to Cart -->
            <div class="order-box">
              <h3>🍔 Add Item to Cart</h3>

              <div class="form-group">
                <label for="foodSelect" class="form-label">Food Item</label>
                <select id="foodSelect" name="food_id" class="form-control">
                  <option value="">Loading menu...</option>
                </select>
              </div>

              <!-- Quantity + Add button on one row -->
              <div class="add-item-row">
                <div class="form-group" style="margin-bottom:0;">
                  <label for="itemQty" class="form-label">Quantity</label>
                  <input
                    type="number"
                    id="itemQty"
                    class="form-control"
                    value="1"
                    min="1"
                    max="50"
                  />
                </div>

                <div class="form-group" style="margin-bottom:0;">
                  <label class="form-label">Unit Price</label>
                  <input
                    type="text"
                    id="unitPriceDisplay"
                    class="form-control"
                    value="₱0.00"
                    readonly
                    style="background:#f9fafb;color:#1a7a3c;font-weight:700;"
                  />
                </div>

                <div style="padding-bottom:0.05rem;">
                  <button type="button" id="addToCartBtn" class="btn-add-to-cart">
                    + Add to Cart
                  </button>
                </div>
              </div>

            </div><!-- /add item box -->

          </div><!-- /left column -->

          <!-- ════════════════════════════════════════════════
               RIGHT COLUMN — Cart + Checkout
               ════════════════════════════════════════════════ -->
          <div>

            <!-- Section 3: Cart Summary Table -->
            <div class="order-box">
              <h3>🧾 Your Cart</h3>

              <!-- Empty state (shown when cart has no items) -->
              <div class="cart-empty" id="cartEmpty">
                <span class="cart-empty-icon">🛒</span>
                Your cart is empty. Add items from the left to get started.
              </div>

              <!-- Cart table (hidden when cart is empty) -->
              <!--
                PHP BACKEND NOTE:
                When you POST to api/place-order.php, send the
                cartItems array as JSON in the request body.
                Each item: { food_id, food_name, unit_price, quantity, line_total }
              -->
              <div id="cartTableWrapper" style="display:none;">
                <table class="cart-table" id="cartTable" aria-label="Cart items">
                  <thead>
                    <tr>
                      <th>Item</th>
                      <th style="text-align:center;width:60px;">Qty</th>
                      <th class="price-col">Price</th>
                      <th class="price-col">Subtotal</th>
                      <th class="action-col">Remove</th>
                    </tr>
                  </thead>
                  <tbody id="cartTableBody">
                    <!-- Rows inserted by order.js -->
                  </tbody>
                </table>

                <!-- Totals -->
                <div class="cart-totals">
                  <div class="totals-row">
                    <span>Items subtotal</span>
                    <span id="totalSubtotal">₱0.00</span>
                  </div>
                  <div class="totals-row">
                    <span>Delivery fee</span>
                    <span id="totalDelivery">₱49.00</span>
                  </div>
                  <div class="totals-row grand-total">
                    <span>Total</span>
                    <span class="amount-green" id="grandTotal">₱0.00</span>
                  </div>
                  <p class="delivery-note" id="deliveryNote">
                    Add ₱<span id="amountToFreeDelivery">500.00</span> more for FREE delivery.
                  </p>
                </div>

                <!-- Checkout button -->
                <!--
                  PHP BACKEND NOTE:
                  This button triggers submitOrder() in order.js,
                  which uses fetch() to POST to api/place-order.php.
                  Replace the simulateCheckout() call with the real fetch.
                -->
                <button type="button" id="checkoutBtn" class="btn-checkout">
                  🚀 Place Order
                </button>

              </div><!-- /cartTableWrapper -->
            </div><!-- /cart box -->

            <!-- Section 4: Delivery Info (static, no JS needed) -->
            <div class="order-box">
              <h3>ℹ️ Delivery Information</h3>
              <ul class="delivery-info-list">
                <li>
                  <span class="di-icon">⏱️</span>
                  <div>
                    <strong>Delivery Time:</strong> 30–45 minutes on average.
                  </div>
                </li>
                <li>
                  <span class="di-icon">🚚</span>
                  <div>
                    <strong>Delivery Fee:</strong> ₱49 flat rate.
                    FREE for orders over ₱500.
                  </div>
                </li>
                <li>
                  <span class="di-icon">💵</span>
                  <div>
                    <strong>Payment:</strong> Cash on Delivery (COD) only.
                  </div>
                </li>
                <li>
                  <span class="di-icon">📞</span>
                  <div>
                    <strong>Support:</strong> (046) 123-4567 or
                    +63 912 345 6789
                  </div>
                </li>
              </ul>
            </div>

          </div><!-- /right column -->
        </div><!-- /order-grid -->
      </div><!-- /orderFormWrapper -->

    </div><!-- /container -->
  </main>

  <!-- Footer loaded from component -->
  <div id="footerPlaceholder"></div>

  <!-- Back to top -->
  <button id="backToTop" aria-label="Back to top">↑</button>

  <!-- ── Scripts ───────────────────────────────────────────── -->
  <!--
    Load order: validation first (order.js calls Validation.*),
    then navbar, then the page-specific script.
    animations.js is intentionally omitted on this page.
  -->
  <script src="js/validation.js"></script>
  <script src="js/navbar.js"></script>
  <script src="js/order.js"></script>

  <!-- Load navbar and footer components -->
  <script>
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
  </script>

</body>
</html>