<?php
/**
 * User Registration
 * - Server-side validation (name, email, password strength)
 * - Password hashing with bcrypt (primary) + MD5 (demonstration)
 * - Google reCAPTCHA v2 verification
 * - CSRF token protection
 */

require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$errors   = [];
$success  = '';
$old      = ['full_name' => '', 'email' => ''];

// ── Handle Form Submission ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CSRF check
    if (!isset($_POST['csrf_token']) || !validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = "Invalid form submission. Please try again.";
    }

    $full_name = sanitize($_POST['full_name'] ?? '');
    $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $password  = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';
    $captcha   = $_POST['g-recaptcha-response'] ?? '';

    // Preserve old input
    $old['full_name'] = $full_name;
    $old['email']     = $email;

    // ── Validation ──────────────────────────────────────────────────

    // Full Name
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    } elseif (strlen($full_name) < 2 || strlen($full_name) > 100) {
        $errors[] = "Full name must be between 2 and 100 characters.";
    } elseif (!preg_match('/^[a-zA-Z\s\'-]+$/', $full_name)) {
        $errors[] = "Full name can only contain letters, spaces, hyphens, and apostrophes.";
    }

    // Email
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "An account with this email already exists.";
        }
    }

    // Password strength
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number.";
    } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
        $errors[] = "Password must contain at least one special character.";
    }

    // Confirm Password
    if ($password !== $confirm) {
        $errors[] = "Passwords do not match.";
    }

    // reCAPTCHA
    if (empty($captcha)) {
        $errors[] = "Please complete the CAPTCHA verification.";
    } elseif (!verifyCaptcha($captcha)) {
        $errors[] = "CAPTCHA verification failed. Please try again.";
    }

    // ── Register User ───────────────────────────────────────────────
    if (empty($errors)) {
        // Hash password with bcrypt (cost factor 12 for strong security)
        $bcrypt_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        // MD5 hash (stored for demonstration/comparison purposes only — NOT used for auth)
        $md5_hash = md5($password);

        $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, md5_hash) VALUES (?, ?, ?, ?)");
        $stmt->execute([$full_name, $email, $bcrypt_hash, $md5_hash]);

        $success = "Account created successfully! You can now log in.";
        $old = ['full_name' => '', 'email' => ''];

        // Regenerate CSRF token after successful submission
        unset($_SESSION['csrf_token']);
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | Blackwell Co.</title>
    <meta name="description" content="Create your Blackwell Co. account for a secure and premium shopping experience.">
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
        <li><a href="login.php">Login</a></li>
    </ul>
</nav>

<!-- REGISTRATION FORM -->
<main class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <line x1="19" y1="8" x2="19" y2="14"/>
                    <line x1="22" y1="11" x2="16" y2="11"/>
                </svg>
            </div>
            <h1 class="auth-title">Create Account</h1>
            <p class="auth-subtitle">Join Blackwell Co. for a premium experience</p>
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

        <!-- Success Message -->
        <?php if (!empty($success)): ?>
            <div class="alert alert-success" id="success-alert">
                <div class="alert-icon">✓</div>
                <div class="alert-content">
                    <p><?php echo $success; ?></p>
                    <a href="login.php" class="alert-link">Go to Login →</a>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" id="register-form" novalidate>
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <!-- Full Name -->
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    </span>
                    <input type="text" id="full_name" name="full_name" placeholder="Enter your full name" 
                           value="<?php echo htmlspecialchars($old['full_name']); ?>" required autocomplete="name">
                </div>
                <span class="field-error" id="name-error"></span>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </span>
                    <input type="email" id="email" name="email" placeholder="Enter your email" 
                           value="<?php echo htmlspecialchars($old['email']); ?>" required autocomplete="email">
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
                    <input type="password" id="password" name="password" placeholder="Create a strong password" required autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePassword('password', this)" aria-label="Toggle password visibility">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <div class="password-strength" id="password-strength">
                    <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                    <span class="strength-text" id="strength-text"></span>
                </div>
                <span class="field-error" id="password-error"></span>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-wrapper">
                    <span class="input-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </span>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required autocomplete="new-password">
                    <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)" aria-label="Toggle password visibility">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="eye-icon"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                    </button>
                </div>
                <span class="field-error" id="confirm-error"></span>
            </div>

            <!-- reCAPTCHA -->
            <div class="captcha-wrapper">
                <div class="g-recaptcha" data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>" data-theme="light"></div>
                <span class="field-error" id="captcha-error"></span>
            </div>

            <!-- Submit -->
            <button type="submit" class="auth-btn" id="register-btn">
                <span class="btn-text">Create Account</span>
                <span class="btn-loader" style="display:none;">
                    <svg class="spinner" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="3" stroke-dasharray="30 70"/></svg>
                </span>
            </button>
        </form>

        <div class="auth-footer">
            <p>Already have an account? <a href="login.php" class="auth-link">Sign In</a></p>
        </div>

        <!-- Hash Info Panel -->
        <div class="hash-info">
            <div class="hash-info-title">🔒 Security Info</div>
            <p>Passwords are hashed using <strong>bcrypt</strong> (cost factor 12) — the industry standard for secure password storage. An MD5 hash is also stored for educational comparison.</p>
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
// ── Client-side validation ──────────────────────────────────────────

function togglePassword(fieldId, btn) {
    const field = document.getElementById(fieldId);
    const isPassword = field.type === 'password';
    field.type = isPassword ? 'text' : 'password';
    btn.classList.toggle('active', isPassword);
}

// Password strength meter
document.getElementById('password')?.addEventListener('input', function() {
    const password = this.value;
    const fill = document.getElementById('strength-fill');
    const text = document.getElementById('strength-text');
    let strength = 0;

    if (password.length >= 8) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/[a-z]/.test(password)) strength++;
    if (/[0-9]/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;

    const levels = [
        { width: '0%',   color: '#ddd',    label: '' },
        { width: '20%',  color: '#ff4757', label: 'Very Weak' },
        { width: '40%',  color: '#ff6348', label: 'Weak' },
        { width: '60%',  color: '#ffa502', label: 'Fair' },
        { width: '80%',  color: '#2ed573', label: 'Strong' },
        { width: '100%', color: '#05c46b', label: 'Very Strong' },
    ];

    const level = levels[strength];
    fill.style.width = level.width;
    fill.style.background = level.color;
    text.textContent = level.label;
    text.style.color = level.color;
});

// Real-time validation
document.getElementById('register-form')?.addEventListener('submit', function(e) {
    let valid = true;

    // Clear previous errors
    document.querySelectorAll('.field-error').forEach(el => el.textContent = '');

    const name     = document.getElementById('full_name').value.trim();
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const confirm  = document.getElementById('confirm_password').value;

    if (!name || name.length < 2) {
        document.getElementById('name-error').textContent = 'Please enter your full name (min 2 characters).';
        valid = false;
    }

    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        document.getElementById('email-error').textContent = 'Please enter a valid email address.';
        valid = false;
    }

    if (password.length < 8) {
        document.getElementById('password-error').textContent = 'Password must be at least 8 characters.';
        valid = false;
    }

    if (password !== confirm) {
        document.getElementById('confirm-error').textContent = 'Passwords do not match.';
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
        // Show loading state
        document.getElementById('register-btn').querySelector('.btn-text').style.display = 'none';
        document.getElementById('register-btn').querySelector('.btn-loader').style.display = 'inline-flex';
    }
});
</script>

</body>
</html>
