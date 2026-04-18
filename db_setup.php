<?php
/**
 * Database Setup Script
 * Run this once to create the required 'users' table.
 * 
 * Usage: php db_setup.php  (from command line)
 * Or navigate to it in browser: http://localhost/db_setup.php
 * 
 * NOTE: Make sure you have created the 'blackwell_auth' database first:
 *       CREATE DATABASE blackwell_auth;
 */

require_once 'config.php';

try {
    // Create users table
    $sql = "
        CREATE TABLE IF NOT EXISTS users (
            id          INT AUTO_INCREMENT PRIMARY KEY,
            full_name   VARCHAR(100)  NOT NULL,
            email       VARCHAR(255)  NOT NULL UNIQUE,
            password    VARCHAR(255)  NOT NULL COMMENT 'bcrypt hashed password',
            md5_hash    VARCHAR(32)   DEFAULT NULL COMMENT 'MD5 hash stored for demonstration purposes',
            created_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
            updated_at  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";

    $pdo->exec($sql);

    echo "✅ Database setup complete! The 'users' table has been created successfully.<br>";
    echo "<a href='register.php'>Go to Registration</a>";

} catch (PDOException $e) {
    echo "❌ Setup failed: " . $e->getMessage();
}
