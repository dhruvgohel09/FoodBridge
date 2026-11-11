<?php
/**
 * FoodBridge - Authentication Helpers
 */

require_once __DIR__ . '/db.php';

/**
 * Check if a regular user is logged in
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Require login for protected user pages
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlash('error', 'Please login to continue.');
        header('Location: login.php');
        exit;
    }
}

/**
 * Get current logged-in user record
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    static $user = null;
    if ($user === null) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, phone, city, role, status, created_at FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch() ?: null;
    }
    return $user;
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn(): bool {
    return isset($_SESSION['admin_id']) && !empty($_SESSION['admin_id']);
}

/**
 * Require admin authentication
 */
function requireAdminLogin(): void {
    if (!isAdminLoggedIn()) {
        setFlash('error', 'Administrator login required.');
        header('Location: admin-login.php');
        exit;
    }
}

/**
 * Get current logged-in admin record
 */
function currentAdmin(): ?array {
    if (!isAdminLoggedIn()) {
        return null;
    }
    static $admin = null;
    if ($admin === null) {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, name, email, role, status, created_at FROM admins WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch() ?: null;
    }
    return $admin;
}

