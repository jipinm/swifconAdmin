<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$page_title = 'Admin Login'; // For the <title> tag

$error_message = '';
if (isset($_SESSION['login_error']) && !empty($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}

// For login page, we don't include the full admin header (no sidebar, no main nav bar)
// We will use the conditional logic within header.php and footer.php
include_once INCLUDES_PATH . '/header.php';
?>

<div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-lg">
                <div class="card-body p-4 p-md-5">
                    <div class="text-center mb-4">
                        <!-- <img src="<?php echo SITE_URL; ?>/assets/images/logo.png" alt="Swifcon CMS Logo" style="max-height: 60px;"> -->
                        <h3 class="mt-2">Swifcon CMS</h3>
                    </div>
                    <h4 class="card-title text-center mb-4">Admin Login</h4>

                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="login.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label"><i class="bi bi-person-fill"></i> Username</label>
                            <input type="text" class="form-control form-control-lg" id="username" name="username" required autofocus>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label"><i class="bi bi-lock-fill"></i> Password</label>
                            <input type="password" class="form-control form-control-lg" id="password" name="password" required>
                        </div>
                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center py-3">
                    <small class="text-muted">Powered by Swifcon</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// No page specific scripts for login typically
include_once INCLUDES_PATH . '/footer.php';
?>
