<?php
// No direct config.php include needed unless SITE_URL is used for assets,
// but it's better to keep error pages self-contained or use relative paths for minimal assets.
// For consistency with other pages, we can include it if header/footer are desired.
require_once __DIR__ . '/config.php'; // To get SITE_URL for assets if needed, and for session start
$page_title = "404 Not Found";
// We could use a simplified header/footer or the main ones. Let's try with main for now.
// If using main header, session.php is usually included there.
if (file_exists(INCLUDES_PATH . '/header.php')) {
    include_once INCLUDES_PATH . '/header.php';
} else { // Minimal fallback if header is missing or paths are broken during error
    echo "<!DOCTYPE html><html lang='en'><head><meta charset='UTF-8'><title>$page_title</title>";
    echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">';
    echo "</head><body><div class='container text-center mt-5'>";
}
?>
<div class="container text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card shadow-lg border-danger">
                <div class="card-body p-5">
                    <h1 class="display-1 text-danger fw-bold">404</h1>
                    <h2 class="mb-4">Page Not Found</h2>
                    <p class="lead text-muted mb-4">
                        Oops! The page you are looking for does not exist, might have been removed, or is temporarily unavailable.
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
