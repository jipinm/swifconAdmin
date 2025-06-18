<?php
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . '/session.php';
require_login();

$page_title = 'Admin Dashboard'; // For the <title> tag in header.php

include_once INCLUDES_PATH . '/header.php';
// Sidebar is included within header.php if logged in.
?>

<!-- Main content area for dashboard -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($page_title); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <!-- Quick actions can go here -->
        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
    </div>
</div>

<h2>Welcome, <?php echo htmlspecialchars(get_admin_username()); ?>!</h2>
<p>This is the main dashboard of Swifcon CMS. You can manage your website content from here.</p>
<p>Select an option from the sidebar to get started.</p>

<!-- Placeholder for content statistics and quick actions -->
<div class="row mt-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Projects</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">15</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-kanban-fill fs-2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Testimonials</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">25</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-chat-square-quote-fill fs-2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">New Enquiries</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">5</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-envelope-paper-fill fs-2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
     <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Active Services</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">10</div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-gear-wide-connected fs-2 text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once INCLUDES_PATH . '/footer.php';
?>
