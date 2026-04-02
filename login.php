<?php
session_start();
require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        header("Location: login.html?error=1");
        exit;
    }

    try {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: index.php");
            exit;
        } else {
            header("Location: login.html?error=1");
            exit;
        }
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        header("Location: login.html?error=1");
        exit;
    }
}

// If GET request, redirect to login page
header("Location: login.html");
exit;
