<?php
/**
 * User Login
 * - Email/password authentication with bcrypt verification
 * - Google reCAPTCHA v2 verification
 * - CSRF token protection
 * - Rate limiting via session-based attempt tracking
 */

require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors = [];
$old    = ['email' => ''];

// ── Rate Limiting ───────────────────────────────────────────────────
$max_attempts   = 5;
$lockout_time   = 300; // 5 minutes in seconds

if (!isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = 0;
    $_SESSION['last_attempt']   = 0;
}

$is_locked = ($_SESSION['login_attempts'] >= $max_attempts) 
             && (time() - $_SESSION['last_attempt'] < $lockout_time);

if ($is_locked) {
    $remaining = $lockout_time - (time() - $_SESSION['last_attempt']);
    $errors[] = "Too many failed attempts. Please try again in " . ceil($remaining / 60) . " minute(s).";
}

// ── Handle Form Submission ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$is_locked) {

    // CSRF check
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid form submission. Please try again.";
    }

    $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $captcha  = $_POST['g-recaptcha-response'] ?? '';

    // Preserve old input
    $old['email'] = $email;

    // ── Validation ──────────────────────────────────────────────────
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // reCAPTCHA
    if (empty($captcha)) {
        $errors[] = "Please complete the CAPTCHA verification.";
    } elseif (!verifyCaptcha($captcha)) {
        $errors[] = "CAPTCHA verification failed. Please try again.";
    }

    // ── Authenticate ────────────────────────────────────────────────
    if (empty($errors)) {
        $stmt = $pdo->prepare("SELECT id, full_name, email, password, md5_hash FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // ✅ Successful login — reset attempts
            $_SESSION['login_attempts'] = 0;

            // Regenerate session ID to prevent session fixation
            session_regenerate_id(true);

            // Set session data
            $_SESSION['user_id']    = $user['id'];
            $_SESSION['user_name']  = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['logged_in']  = true;

            // Check if password hash needs rehashing (cost upgrade)
            if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => 12])) {
                $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHash, $user['id']]);
            }

            redirect('dashboard.php');
        } else {
            // ❌ Failed login — increment attempts
            $_SESSION['login_attempts']++;
            $_SESSION['last_attempt'] = time();

            $remaining = $max_attempts - $_SESSION['login_attempts'];
            if ($remaining > 0) {
                $errors[] = "Invalid email or password. $remaining attempt(s) remaining.";
            } else {
                $errors[] = "Too many failed attempts. Your account is locked for 5 minutes.";
            }
        }
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Blackwell Co.</title>
    <meta name="description" content="Sign in to your Blackwell Co. account for a secure and premium shopping experience.">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>

<!-- NAVIGATION -->
<nav>
    <div class="logo"><strong><a href="index.html">BLACKWELL CO.</a></strong></div>
    <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="shop.html">Shop</a></li>
        <li><a href="register.php">Register</a></li>
    </ul>
</nav>

<!-- LOGIN FORM -->
<main class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                </svg>
            </div>
            <h1 class="auth-title">Welcome Back</h1>
            <p class="auth-subtitle">Sign in to your Blackwell Co. account</p>
        </div>

        <!-- Error Messages -->
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error" id="error-alert">
                <div class="alert-icon">⚠</div>
                <div class="alert-content">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="login-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </span>
                    <input type="email" id="email" name="email" placeholder="Enter your email" 
                           value="<?php echo htmlspecialchars($old['email']); ?>" required autocomplete="email"
                           <?php echo $is_locked ? 'disabled' : ''; ?>>
                </div>
                <span class="field-error" id="email-error"></span>
            </div>

            <!-- Password -->
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password"
                           <?php echo $is_locked ? 'disabled' : ''; ?>>
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <span class="field-error" id="password-error"></span>
            </div>

            <!-- reCAPTCHA -->
            <div class="captcha-wrapper">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>" data-theme="light"></div>
                <span class="field-error" id="captcha-error"></span>
            </div>

            <!-- Submit -->
            <button type="submit" class="auth-btn" id="login-btn" <?php echo $is_locked ? 'disabled' : ''; ?>>
                <span class="btn-text"><?php echo $is_locked ? 'Account Locked' : 'Sign In'; ?></span>
                <span class="btn-loader" style="display:none;">
                    <svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="30 70"/></svg>
                </span>
            </button>
        </form>

        <div class="auth-footer">
            <p>Don't have an account? <a href="register.php" class="auth-link">Create Account</a></p>
        </div>

        <!-- Security Features Panel -->
        <div class="hash-info">
            <div class="hash-info-title">🛡️ Security Features</div>
            <ul class="security-list">
                <li>✅ Passwords hashed with <strong>bcrypt</strong></li>
                <li>✅ Google reCAPTCHA v2 protection</li>
                <li>✅ CSRF token validation</li>
                <li>✅ Rate limiting (<?php echo $max_attempts; ?> attempts max)</li>
                <li>✅ Session fixation prevention</li>
            </ul>
        </div>
    </div>
</main>

<!-- FOOTER -->
<footer>
    <div class="footer-links">
        <a href="#">Privacy Policy</a>
        <a href="#">Terms</a>
        <a href="#">Contact</a>
    </div>
    &copy; 2026 Blackwell Co.
</footer>

<script>
function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const isPassword = field.type === 'password';
    field.type = isPassword ? 'text' : 'password';
    btn.classList.toggle('active', isPassword);
}

document.getElementById('login-form')?.addEventListener('submit', function(e) {
    let valid = true;
    document.querySelectorAll('.field-error').forEach(el => el.textContent = '');

    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById('email-error').textContent = 'Please enter a valid email address.';
        valid = false;
    }

    if (!password) {
        document.getElementById('password-error').textContent = 'Password is required.';
        valid = false;
    }

    const captchaResponse = document.querySelector('[name="g-recaptcha-response"]');
    if (!captchaResponse || !captchaResponse.value) {
        document.getElementById('captcha-error').textContent = 'Please complete the CAPTCHA.';
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
    } else {
        document.getElementById('login-btn').querySelector('.btn-text').style.display = 'none';
        document.getElementById('login-btn').querySelector('.btn-loader').style.display = 'inline-flex';
    }
});
</script>

</body>
</html>
