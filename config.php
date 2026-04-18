<?php
/**
 * Configuration File
 * Database connection & session setup for secure login system
 * 
 * IMPORTANT: Update these values with your actual database credentials
 * and reCAPTCHA keys before deploying.
 */

// Start session with secure settings
session_start([
    'cookie_httponly' => true,     // Prevent JavaScript access to session cookie
    'cookie_secure'   => false,    // Set to true in production with HTTPS
    'cookie_samesite' => 'Strict', // Prevent CSRF via cookie
    'use_strict_mode' => true,     // Reject uninitialized session IDs
]);

// ── Database Configuration ──────────────────────────────────────────
define('DB_HOST', 'localhost');
define('DB_NAME', 'blackwell_auth');
define('DB_USER', 'root');
define('DB_PASS', '');

// ── Google reCAPTCHA v2 Keys ────────────────────────────────────────
// Register at: https://www.google.com/recaptcha/admin
// Use reCAPTCHA v2 "I'm not a robot" checkbox
define('RECAPTCHA_SITE_KEY',   '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI'); // Test key (replace in production)
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe'); // Test key (replace in production)

// ── Database Connection (PDO) ───────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Don't expose DB errors to users in production
    error_log("Database connection failed: " . $e->getMessage());
    die("A database error occurred. Please try again later.");
}

// ── Helper Functions ────────────────────────────────────────────────

/**
 * Verify Google reCAPTCHA response
 */
function verifyCaptcha(string $response): bool {
    $url = 'https://www.google.com/recaptcha/api/siteverify';
    $data = [
        'secret'   => RECAPTCHA_SECRET_KEY,
        'response' => $response,
        'remoteip' => $_SERVER['REMOTE_ADDR'],
    ];

    $options = [
        'http' => [
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context = stream_context_create($options);
    $result  = file_get_contents($url, false, $context);

    if ($result === false) {
        return false;
    }

    $json = json_decode($result, true);
    return isset($json['success']) && $json['success'] === true;
}

/**
 * Sanitize user input
 */
function sanitize(string $input): string {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect helper
 */
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
