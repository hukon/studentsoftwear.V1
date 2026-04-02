<?php
/**
 * db.php — Database connection (uses centralized config)
 */
require_once __DIR__ . '/config.php';

$pdo = getDB();
