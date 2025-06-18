<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php'; // Will be created in a later step

// For now, just a placeholder
if (is_logged_in()) { // is_logged_in() will be defined in session.php
    header('Location: dashboard.php');
    exit;
} else {
    header('Location: login_form.php'); // login_form.php will be created later
    exit;
}
?>
