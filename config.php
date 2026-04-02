<?php
/**
 * config.php — Centralized configuration for Student Organizer
 * 
 * SECURITY: On production, replace these with environment variables:
 *   $DB_HOST = getenv('DB_HOST') ?: 'localhost';
 */

// ─── Database ────────────────────────────────────────────────────
define('DB_HOST', 'ep-flat-mud-aguu2wr8-pooler.c-2.eu-central-1.aws.neon.tech');
define('DB_NAME', 'neondb');
define('DB_USER', 'neondb_owner');
define('DB_PASS', 'npg_OD5LVCQb0pXu');

// ─── App Settings ────────────────────────────────────────────────
define('APP_NAME',    'Student Organizer');
define('APP_LOCALE',  'fr');
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR',  __DIR__ . '/uploads');
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10 MB

// ─── PDO helper ──────────────────────────────────────────────────
function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'pgsql:host=' . DB_HOST . ';port=5432;dbname=' . DB_NAME . ';sslmode=require';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}

// ─── Auth helper ─────────────────────────────────────────────────
function requireLogin(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }
}

// ─── Ensure uploads directory exists ─────────────────────────────
if (!is_dir(UPLOAD_DIR)) {
    @mkdir(UPLOAD_DIR, 0775, true);
}
