<?php
require_once __DIR__ . '/config.php';
$page_title = "500 Internal Server Error";
if (file_exists(INCLUDES_PATH . '/header.php')) {
    include_once INCLUDES_PATH . '/header.php';
} else {
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>$page_title</title>";
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo "</head><body><div class='container text-center mt-5'>";
}
?>
<div class="container text-center py-5">
     <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-warning">
                <div class="card-body p-5">
                    <h1 class="display-1 text-warning fw-bold">500</h1>
                    <h2 class="mb-4">Internal Server Error</h2>
                    <p class="lead text-muted mb-4">
                        We are sorry, something went wrong on our end. Please try again later or contact support if the issue persists.
                    </p>
                     <a href="<?php echo defined('SITE_URL') ? SITE_URL : '/'; ?>/dashboard.php" class="btn btn-primary btn-lg">
                        <i class="bi bi-house-door-fill"></i> Go to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
if (file_exists(INCLUDES_PATH . '/footer.php')) {
    include_once INCLUDES_PATH . '/footer.php';
} else {
    echo "</div></body></html>";
}
?>
