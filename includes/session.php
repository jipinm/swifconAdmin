<?php
// Ensure config.php (which calls session_start()) is included before this file.
// If not already started by config.php, start it.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in() {
    return isset($_SESSION['admin_id']);
}

function require_login() {
    if (!is_logged_in()) {
        // Store the intended location and redirect to login
        // $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI']; // Optional: redirect back after login
        header('Location: ' . ADMIN_URL . '/login_form.php');
        exit;
    }
}

function get_admin_username() {
    return $_SESSION['admin_username'] ?? null;
}

function get_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}
?>
