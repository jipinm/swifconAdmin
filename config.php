<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USERNAME', 'root'); // Replace with your DB username
define('DB_PASSWORD', '');     // Replace with your DB password
define('DB_NAME', 'swifcon_cms'); // Replace with your DB name

// Site Configuration
define('SITE_URL', 'http://localhost/swifcon_cms'); // Replace with your site URL

// Paths
define('BASE_PATH', __DIR__);
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('FILE_MANAGER_PATH', BASE_PATH . '/file_manager'); // This was already correct

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
