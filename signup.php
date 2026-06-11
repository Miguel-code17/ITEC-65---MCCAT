<?php
session_start();
include 'connection.php';

$signupError = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($first_name === '' || mb_strlen($first_name) < 2 || mb_strlen($first_name) > 50) {
        $signupError = 'Please enter your first name (2 to 50 characters).';
    } elseif ($last_name === '' || mb_strlen($last_name) < 2 || mb_strlen($last_name) > 50) {
        $signupError = 'Please enter your last name (2 to 50 characters).';
    } elseif ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $signupError = 'Please enter a valid email address.';
    } elseif ($phone === '' || !preg_match('/^(\+63|0)9[0-9]{9}$/', preg_replace('/[\s\-()]/', '', $phone))) {
        $signupError = 'Please enter a valid Philippine phone number.';
    } elseif ($password === '' || strlen($password) < 8) {
        $signupError = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $signupError = 'Passwords do not match.';
    } else {
        $check = $conn->prepare('SELECT id FROM users WHERE email = ?');
        $check->bind_param('s', $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $signupError = 'Email already exists. Please use a different email.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare('INSERT INTO users (first_name, last_name, email, phone, password) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sssss', $first_name, $last_name, $email, $phone, $hashedPassword);

            if ($stmt->execute()) {
                header('Location: login.php?registered=1');
                exit();
            } else {
                $signupError = 'Registration failed. Please try again later.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Create Account — MCCAT</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/navbar.css" />
  <link rel="stylesheet" href="css/forms.css" />
  <link rel="stylesheet" href="css/animations.css" />
</head>
<body>

  <div id="navbarPlaceholder"></div>
  <div class="navbar-spacer"></div>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  SIGNUP PAGE                                         ║
       ╚══════════════════════════════════════════════════════╝ -->
  <main class="form-page" style="padding:2rem 1rem;">
    <div class="form-card form-card-wide">

      <!-- Header -->
      <div class="form-header">
        <div class="form-logo">🎉</div>
        <h2>Join MCCAT!</h2>
        <p>Create your free account and start ordering</p>
      </div>

      <!-- Alert -->
      <div id="signupAlert">
        <?php if (!empty($signupError)) echo '<div class="alert alert-error">' . htmlspecialchars($signupError) . '</div>'; ?>
      </div>

      <!-- Signup Form
           PHP BACKEND NOTE:
           action="api/register.php" method="POST"
           Sanitize all inputs server-side with htmlspecialchars() / prepared statements
      -->
      <form id="signupForm" method="POST" autocomplete="on">

        <!-- First Name -->
        <div class="form-group">
          <label for="signupFirstName" class="form-label">
            First Name <span class="required">*</span>
          </label>
          <div class="input-group">
            <input
              type="text"
              id="signupFirstName"
              name="first_name"
              class="form-control"
              placeholder="e.g. Juan"
              autocomplete="name"
              maxlength="50"
              required
            />
            <span class="input-icon">👤</span>
          </div>
        </div>

          <!-- Last Name -->
          <div class="form-group">
            <label for="signupLastName" class="form-label">
              Last Name <span class="required">*</span>
            </label>
            <div class="input-group">
              <input
                type="text"
                id="signupLastName"
                name="last_name"
                class="form-control"
                placeholder="e.g. dela Cruz"
                autocomplete="family-name"
                maxlength="50"
                required
              />
              <span class="input-icon">👤</span>
            </div>
          </div>

        <!-- Email -->
        <div class="form-group">
          <label for="signupEmail" class="form-label">
            Email Address <span class="required">*</span>
          </label>
          <div class="input-group">
            <input
              type="email"
              id="signupEmail"
              name="email"
              class="form-control"
              placeholder="you@example.com"
              autocomplete="email"
              required
            />
            <span class="input-icon">📧</span>
          </div>
          <span class="form-hint">We'll send your order confirmations here.</span>
        </div>

        <!-- Phone -->
        <div class="form-group">
          <label for="signupPhone" class="form-label">
            Contact Number <span class="required">*</span>
          </label>
          <div class="input-group">
            <input
              type="tel"
              id="signupPhone"
              name="phone"
              class="form-control"
              placeholder="09123456789"
              autocomplete="tel"
              maxlength="15"
              required
            />
            <span class="input-icon">📞</span>
          </div>
          <span class="form-hint">Philippine number format (e.g. 09123456789)</span>
        </div>

        <!-- Password -->
        <div class="form-group">
          <label for="signupPassword" class="form-label">
            Password <span class="required">*</span>
          </label>
          <div class="input-group">
            <input
              type="password"
              id="signupPassword"
              name="password"
              class="form-control has-right-icon"
              placeholder="Minimum 8 characters"
              autocomplete="new-password"
              required
            />
            <span class="input-icon">🔒</span>
            <button type="button" class="toggle-password" id="togglePassword1" aria-label="Show password">👁️</button>
          </div>

          <!-- Password Strength Meter -->
          <div class="password-strength" id="passwordStrength" style="display:none;margin-top:0.6rem;">
            <div class="strength-bars">
              <div class="strength-bar"></div>
              <div class="strength-bar"></div>
              <div class="strength-bar"></div>
              <div class="strength-bar"></div>
            </div>
            <span class="strength-label"></span>
          </div>
        </div>

        <!-- Confirm Password -->
        <div class="form-group">
          <label for="signupConfirmPassword" class="form-label">
            Confirm Password <span class="required">*</span>
          </label>
          <div class="input-group">
            <input
              type="password"
              id="signupConfirmPassword"
              name="confirm_password"
              class="form-control has-right-icon"
              placeholder="Re-enter your password"
              autocomplete="new-password"
              required
            />
            <span class="input-icon">🔐</span>
            <button type="button" class="toggle-password" id="togglePassword2" aria-label="Show confirm password">👁️</button>
          </div>
        </div>

        <!-- Terms & Conditions -->
        <div class="form-group" style="margin-bottom:1.5rem;">
          <label class="form-check" style="align-items:flex-start;">
            <input type="checkbox" id="agreeTerms" name="agree_terms" class="form-check-input" style="margin-top:2px;" required />
            <span class="form-check-label">
              I agree to the <a href="#" style="color:var(--primary);font-weight:600;">Terms & Conditions</a>
              and <a href="#" style="color:var(--primary);font-weight:600;">Privacy Policy</a>
            </span>
          </label>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          id="signupSubmitBtn"
          class="btn btn-primary btn-full btn-lg btn-submit"
        >
          <span class="btn-text">🎉 Create My Account</span>
          <span class="btn-loader" style="display:none;align-items:center;gap:0.5rem;">
            <span class="spinner spinner-sm" style="border-color:rgba(255,255,255,0.3);border-top-color:#fff;"></span>
            Creating account...
          </span>
        </button>

      </form>

      <!-- Footer -->
      <div class="form-footer" style="margin-top:1.25rem;">
        Already have an account?
        <a href="login.php">Sign in →</a>
      </div>

    </div>
  </main>

  <div class="toast-container"></div>

  <script src="js/validation.js"></script>
  <script src="js/navbar.js"></script>
  <script src="js/animations.js"></script>
  <script src="js/signup.js"></script>
  <script>
    async function loadComponent(id, file) {
      try { document.getElementById(id).innerHTML = await (await fetch(file)).text(); } catch(e) {}
    }
    loadComponent('navbarPlaceholder', 'components/navbar.php');
  </script>
</body>
</html>
