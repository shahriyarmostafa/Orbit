<?php
/**
 * Authentication and Session Helpers
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireAuth(): void {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireGuest(): void {
    if (isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function getCurrentUsername(): string {
    return $_SESSION['username'] ?? 'User';
}

function isDarkMode(): bool {
    return !empty($_SESSION['dark_mode']);
}

