<?php
/**
 * config.php — Centralized configuration for Student Organizer
 * 
 * SECURITY: On production, replace these with environment variables:
 *   $DB_HOST = getenv('DB_HOST') ?: 'localhost';
 */

// ─── Database ────────────────────────────────────────────────────
define('DB_HOST', 'sql308.infinityfree.com');
define('DB_NAME', 'if0_39971413_student');
define('DB_USER', 'if0_39971413');
define('DB_PASS', '2thxXBOi75vZOFT');

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
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
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
