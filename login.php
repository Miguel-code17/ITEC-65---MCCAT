<?php
$loginError = '';
$loginSuccess = '';

$remember = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remember_me']);
if ($remember) {
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 30,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
}
session_start();
include 'connection.php';

if (isset($_SESSION['user_id'])) {
    header('Location: order.php');
    exit();
}

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $loginSuccess = '✅ Your account has been created. Please sign in.';
}

if (isset($_GET['logout']) && $_GET['logout'] === '1') {
    $loginSuccess = '✅ You have been logged out successfully.';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $loginError = 'Please enter a valid email address and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, fullname, password FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];

                header('Location: order.php');
                exit();
            }
        }

        $loginError = '❌ Incorrect email or password.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Login — MCCAT</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/navbar.css" />
  <link rel="stylesheet" href="css/forms.css" />
  <link rel="stylesheet" href="css/animations.css" />
</head>
<body>

  <div id="navbarPlaceholder"></div>
  <div class="navbar-spacer"></div>

  <!-- ╔══════════════════════════════════════════════════════╗
       ║  LOGIN PAGE                                          ║
       ╚══════════════════════════════════════════════════════╝ -->
  <main class="form-page">
    <div class="form-card">

      <!-- Header -->
      <div class="form-header">
        <div class="form-logo">🐱</div>
        <h2>Welcome Back!</h2>
        <p>Sign in to your MCCAT account</p>
      </div>

      <!-- Alert container -->
      <div id="loginAlert">
        <?php if (!empty($loginSuccess)) echo '<div class="alert alert-success">' . htmlspecialchars($loginSuccess) . '</div>'; ?>
        <?php if (!empty($loginError)) echo '<div class="alert alert-error">' . htmlspecialchars($loginError) . '</div>'; ?>
      </div>

      <!-- Login Form
           PHP BACKEND NOTE:
           action="api/login.php" method="POST"
           Add CSRF token: <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf'] ?>">
      -->
      <form id="loginForm" method="POST" action="login.php" autocomplete="on">

        <!-- Email -->
        <div class="form-group">
          <label for="loginEmail" class="form-label">
            Email Address <span class="required">*</span>
          </label>
          <div class="input-group">
            <input
              type="email"
              id="loginEmail"
              name="email"
              class="form-control"
              placeholder="you@example.com"
              autocomplete="email"
              required
            />
            <span class="input-icon">📧</span>
          </div>
        </div>

        <!-- Password -->
        <div class="form-group">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.45rem;">
            <label for="loginPassword" class="form-label" style="margin:0;">
              Password <span class="required">*</span>
            </label>
            <a href="#" id="forgotPasswordLink" style="font-size:0.82rem;color:var(--primary);font-weight:600;">
              Forgot password?
            </a>
          </div>
          <div class="input-group">
            <input
              type="password"
              id="loginPassword"
              name="password"
              class="form-control has-right-icon"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            />
            <span class="input-icon">🔒</span>
            <button type="button" class="toggle-password" id="togglePassword" aria-label="Show password">
              👁️
            </button>
          </div>
        </div>

        <!-- Remember Me -->
        <div class="form-group" style="margin-bottom:1.5rem;">
          <label class="form-check">
            <input type="checkbox" id="rememberMe" name="remember_me" class="form-check-input" />
            <span class="form-check-label">Keep me signed in for 30 days</span>
          </label>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          id="loginSubmitBtn"
          class="btn btn-primary btn-full btn-lg btn-submit"
        >
          <span class="btn-text">Sign In →</span>
          <span class="btn-loader" style="display:none;align-items:center;gap:0.5rem;">
            <span class="spinner spinner-sm" style="border-color:rgba(255,255,255,0.3);border-top-color:#fff;"></span>
            Signing in...
          </span>
        </button>

      </form>

      <!-- Divider -->
      <div class="form-divider">or continue as</div>

      <!-- Guest option -->
      <a href="menu.php" class="btn btn-secondary btn-full" style="margin-bottom:1.25rem;">
        👀 Browse Menu as Guest
      </a>

      <!-- Footer -->
      <div class="form-footer">
        Don't have an account?
        <a href="signup.php">Create one free →</a>
      </div>

    </div>
  </main>

  <!-- Toast -->
  <div class="toast-container"></div>

  <script src="js/validation.js"></script>
  <script src="js/navbar.js"></script>
  <script src="js/animations.js"></script>
  <script src="js/login.js"></script>
  <script>
    async function loadComponent(id, file) {
      try { document.getElementById(id).innerHTML = await (await fetch(file)).text(); } catch(e) {}
    }
    loadComponent('navbarPlaceholder', 'components/navbar.php');
  </script>
</body>
</html>
