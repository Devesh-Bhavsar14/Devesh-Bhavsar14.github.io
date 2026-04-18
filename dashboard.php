<?php
/**
 * User Dashboard (Protected Page)
 * Only accessible after successful login.
 * Displays user info and hash comparison.
 */

require_once 'config.php';

// Protect this page — redirect to login if not authenticated
if (!isLoggedIn()) {
    redirect('login.php');
}

// Fetch user details
$stmt = $pdo->prepare("SELECT id, full_name, email, password, md5_hash, created_at FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    redirect('logout.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Blackwell Co.</title>
    <meta name="description" content="Your Blackwell Co. account dashboard.">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/auth.css">
</head>
<body>

<!-- NAVIGATION -->
<nav>
    <div class="logo"><strong><a href="index.html">BLACKWELL CO.</a></strong></div>
    <ul>
        <li><a href="index.html">Home</a></li>
        <li><a href="shop.html">Shop</a></li>
        <li><a href="logout.php" class="logout-link">Logout</a></li>
    </ul>
</nav>

<!-- DASHBOARD -->
<main class="auth-wrapper">
    <div class="dashboard-card">
        <!-- Welcome Header -->
        <div class="dashboard-header">
            <div class="avatar-circle">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <h1 class="auth-title">Welcome, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p class="auth-subtitle">You are securely logged in</p>
        </div>

        <!-- User Information -->
        <div class="dashboard-section">
            <h2 class="section-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                Account Information
            </h2>
            <div class="info-grid">
                <div class="info-item">
                    <span class="info-label">Full Name</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['full_name']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Email</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Member Since</span>
                    <span class="info-value"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">User ID</span>
                    <span class="info-value">#<?php echo $user['id']; ?></span>
                </div>
            </div>
        </div>

        <!-- Hash Comparison (Educational) -->
        <div class="dashboard-section">
            <h2 class="section-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Password Hash Comparison
            </h2>
            <p class="hash-description">Below is a comparison of how your password is stored using different hashing algorithms:</p>

            <div class="hash-comparison">
                <div class="hash-block hash-secure">
                    <div class="hash-badge badge-secure">✅ SECURE — Used for Authentication</div>
                    <h3>bcrypt Hash</h3>
                    <code class="hash-value"><?php echo htmlspecialchars($user['password']); ?></code>
                    <ul class="hash-features">
                        <li>🔐 Adaptive cost factor (slow by design)</li>
                        <li>🧂 Built-in random salt</li>
                        <li>🔄 Resistant to rainbow table attacks</li>
                        <li>🛡️ Industry standard for password storage</li>
                    </ul>
                </div>

                <div class="hash-block hash-insecure">
                    <div class="hash-badge badge-insecure">⚠️ INSECURE — For Demonstration Only</div>
                    <h3>MD5 Hash</h3>
                    <code class="hash-value"><?php echo htmlspecialchars($user['md5_hash']); ?></code>
                    <ul class="hash-features">
                        <li>⚡ Too fast — easily brute-forced</li>
                        <li>❌ No built-in salt</li>
                        <li>❌ Vulnerable to rainbow tables</li>
                        <li>❌ Known collision vulnerabilities</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Security Features -->
        <div class="dashboard-section">
            <h2 class="section-heading">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                Security Features Implemented
            </h2>
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">🔒</div>
                    <h4>bcrypt Hashing</h4>
                    <p>Passwords stored with bcrypt (cost 12) — adaptive and salted</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🤖</div>
                    <h4>reCAPTCHA v2</h4>
                    <p>Google reCAPTCHA prevents automated bot attacks</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🛡️</div>
                    <h4>CSRF Protection</h4>
                    <p>Unique tokens per session prevent cross-site request forgery</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🚫</div>
                    <h4>Rate Limiting</h4>
                    <p>5 login attempts max, then 5-minute cooldown</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🧹</div>
                    <h4>Input Validation</h4>
                    <p>Server-side + client-side validation with sanitization</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">🔑</div>
                    <h4>Session Security</h4>
                    <p>Session fixation prevention, HTTPOnly cookies, regeneration</p>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="dashboard-actions">
            <a href="shop.html" class="auth-btn" style="text-decoration:none; text-align:center;">Continue Shopping</a>
            <a href="logout.php" class="auth-btn btn-outline" style="text-decoration:none; text-align:center;">Sign Out</a>
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

</body>
</html>
