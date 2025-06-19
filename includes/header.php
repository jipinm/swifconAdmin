<?php
// This file assumes session.php and config.php have been included by the calling script.
// Or, ensure they are included here if this header is used standalone.
if (session_status() == PHP_SESSION_NONE) {
    // If config.php (which starts session) isn't included before this,
    // you might need to include it or start session manually.
    // For simplicity, assuming calling script handles config.php inclusion.
    // session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);
$admin_username = get_admin_username(); // from session.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Swifcon CMS Admin'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        body {
            display: flex;
            min-height: 100vh;
            flex-direction: column; /* Ensures footer sticks to bottom */
        }
        .admin-layout {
            display: flex;
            flex: 1; /* Allows main content to fill space */
        }
        #sidebarMenu {
            width: 250px; /* Fixed width for sidebar */
            min-height: 100vh; /* Full height */
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
        }
        .main-content {
            flex-grow: 1; /* Takes remaining width */
            padding: 20px;
            overflow-x: auto; /* In case content is too wide */
        }
        .navbar-brand img {
             max-height: 35px;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #333;
        }
        .sidebar .nav-link .bi {
            margin-right: 8px;
        }
        .sidebar .nav-link.active {
            color: #0d6efd;
        }
        .sidebar .nav-link:hover {
            color: #0a58ca;
        }
        .sidebar-header {
            padding: 10px 15px;
            font-size: 1.2rem;
            font-weight: bold;
        }
    </style>
</head>
<body>
<?php if (is_logged_in()): ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top p-0 shadow">
        <div class="container-fluid">
            <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="<?php echo SITE_URL; ?>/dashboard.php">
                <!-- <img src="<?php echo SITE_URL; ?>/assets/images/logo-light.png" alt="Swifcon CMS"> -->
                Swifcon CMS
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto px-3">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdownMenuLink" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> <?php echo htmlspecialchars($admin_username); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownMenuLink">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/change_username.php"><i class="bi bi-person-badge me-2"></i> Change Username</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/change_password.php"><i class="bi bi-key-fill me-2"></i> Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <?php include_once INCLUDES_PATH . '/sidebar.php'; ?>
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content main-content-wrapper">
<?php else: // Not logged in (e.g. login page) ?>
    <div class="main-content-login"> <!-- Simple wrapper for login page -->
<?php endif; ?>
