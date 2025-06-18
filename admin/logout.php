<?php
require_once __DIR__ . '/../config.php'; // Ensures session_start() is called
require_once INCLUDES_PATH . '/session.php';

// Destroy all session data
$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

header('Location: login_form.php');
exit;
?>
