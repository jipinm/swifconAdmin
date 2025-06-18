<?php
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . '/db_connect.php';
require_once INCLUDES_PATH . '/session.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error_message = 'Please enter both username and password.';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, username, password_hash FROM admins WHERE username = ?");
        if ($stmt) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $admin = $result->fetch_assoc();
                if (password_verify($password, $admin['password_hash'])) {
                    // Password is correct, start session
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $admin['username'];
                    header('Location: dashboard.php');
                    exit;
                } else {
                    $error_message = 'Invalid username or password.';
                }
            } else {
                $error_message = 'Invalid username or password.';
            }
            $stmt->close();
        } else {
            $error_message = 'Database query failed. Please try again later.';
            // Log the error: error_log($conn->error);
        }
    }
}

// If there's an error or it's a GET request, redirect back to the login form
// The actual login form will be in login_form.php
$_SESSION['login_error'] = $error_message;
header('Location: login_form.php');
exit;
?>
