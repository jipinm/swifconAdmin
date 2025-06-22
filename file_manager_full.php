<?php
// file_manager_full.php (V2)

// This MUST be before any HTML output or session-dependent includes
$is_selection_mode = (isset($_GET['selection_mode']) && $_GET['selection_mode'] === 'true');

// Config is needed for both modes for SITE_URL, INCLUDES_PATH etc.
if (!file_exists(__DIR__ . '/config.php')) {
    // For popup mode, a plain die() is better than full HTML error page
    if ($is_selection_mode) { die('Critical error: Configuration missing.'); }
    // For full page mode, you might redirect or show a more styled error
    die('Critical error: Configuration missing. Please check server setup.');
}
require_once __DIR__ . '/config.php';

// Session handling & login check
// For popup, we still need to ensure the user is logged in,
// as this page provides access to potentially sensitive file operations/listings.
if (!defined('INCLUDES_PATH') || !file_exists(INCLUDES_PATH . '/session.php')) {
    if ($is_selection_mode) { die('Critical error: Session library missing.'); }
    die('Critical error: Session library missing. Please check server setup.');
}
require_once INCLUDES_PATH . '/session.php';

// All access to this file requires login, regardless of mode.
// require_login() will redirect to login_form.php if not logged in,
// which is fine for full page mode. For popup, this might break the popup flow.
// A better check for popup might be:
if (!is_logged_in()) {
    if ($is_selection_mode) {
        // Output a simple message and tell JS to close, or just die.
        // This prevents redirecting the small popup window to a full login page.
        echo "<script>alert('Session expired or not logged in. Please log in to the main application and try again.'); window.close();</script>";
        exit;
    } else {
        // For full page, the existing require_login() handles redirection well.
        require_login(); // This calls header() so must be before HTML
    }
}


if (!$is_selection_mode) { // ======= FULL PAGE MODE =======
    $page_title = 'File Manager';
    include_once INCLUDES_PATH . '/header.php'; // Standard header, HTML head, opening body, sidebar
?>
    <div class="container-fluid py-4"> <!-- Main content wrapper for full page -->
        <div class="card shadow">
            <div class="card-header pb-0">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <h4 class="card-title mb-0 me-3"><i class="bi bi-folder-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                </div>
                <hr class="my-2">
            </div>
            <div class="card-body pt-0">
                <ul class="nav nav-tabs mt-2" id="fmFullTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="fmFull-gallery-tab" data-bs-toggle="tab" data-bs-target="#fmFull-gallery-pane" type="button" role="tab" aria-controls="fmFull-gallery-pane" aria-selected="true"><i class="bi bi-images"></i> Media Library</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="fmFull-upload-tab" data-bs-toggle="tab" data-bs-target="#fmFull-upload-pane" type="button" role="tab" aria-controls="fmFull-upload-pane" aria-selected="false"><i class="bi bi-upload"></i> Upload New Files</button>
                    </li>
                </ul>
                <div class="tab-content" id="fmFullTabsContent">
                    <div class="tab-pane fade show active p-3" id="fmFull-gallery-pane" role="tabpanel" aria-labelledby="fmFull-gallery-tab">
                        <div class="row mb-3">
                            <div class="col-md-8 col-lg-6 mx-auto">
                                <input type="search" id="fmFullSearchInput" class="form-control form-control-sm" placeholder="Search files by name...">
                            </div>
                        </div>
                        <div class="row" id="fmFullGallery"><p class="text-center text-muted" id="fmFullGalleryLoadingMsg">Loading files...</p></div>
                    </div>
                    <div class="tab-pane fade p-3" id="fmFull-upload-pane" role="tabpanel" aria-labelledby="fmFull-upload-tab">
                        <form id="fmFullUploadForm" enctype="multipart/form-data">
                            <div class="mb-3 col-md-8 col-lg-6">
                                <label for="fmFullUploadInput" class="form-label">Select file(s) (Max 5MB each: JPG, JPEG, PNG, GIF, PDF)</label>
                                <input class="form-control" type="file" id="fmFullUploadInput" name="file_to_upload" required multiple>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-arrow-up-fill"></i> Start Uploads</button>
                        </form>
                        <div id="fmFullOverallStatusMessage" class="mt-3"></div>
                        <div id="fmFullUploadProgressArea" class="mt-3"></div>
                    </div>
                </div>
            </div> <!-- End card-body -->
        </div> <!-- End card -->
    </div> <!-- End container-fluid for full page -->

    <!-- Modals for Full Page View -->
    <div class="modal fade" id="fmFullDeleteConfirmModal" tabindex="-1" aria-labelledby="fmFullDeleteConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="fmFullDeleteConfirmModalLabel">Confirm Deletion</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body">Are you sure you want to delete the file: <strong id="fmFullFileNameToDeletePlaceholder"></strong>?<p class="text-danger small mt-2">This action cannot be undone.</p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button type="button" class="btn btn-danger" id="fmFullConfirmDeleteFileBtn">Yes, Delete</button></div></div></div></div>
    <div class="modal fade" id="fmFullFilePreviewModal" tabindex="-1" aria-labelledby="fmFullFilePreviewModalLabel" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content"><div class="modal-header"><h5 class="modal-title" id="fmFullFilePreviewModalLabel">File Preview</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div><div class="modal-body"><div class="text-center mb-3" id="fmFullFilePreviewImageContainer"><img src="" id="fmFullFilePreviewImage" alt="File Preview" style="display: none;"></div><div class="text-center mb-3" id="fmFullFilePreviewIconContainer" style="display: none; font-size: 6rem;"></div><p><strong>Filename:</strong> <span id="fmFullFilePreviewName"></span></p><div class="mb-3"><label for="fmFullFilePreviewUrl" class="form-label">File URL:</label><div class="input-group"><input type="text" id="fmFullFilePreviewUrl" class="form-control" readonly><button class="btn btn-outline-secondary" type="button" id="fmFullCopyFilePreviewUrl"><i class="bi bi-clipboard-check"></i> Copy</button></div></div><p class="small text-muted"><strong>Size:</strong> <span id="fmFullFilePreviewSize"></span> | <strong>Modified:</strong> <span id="fmFullFilePreviewModifiedDate"></span></p></div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button></div></div></div></div>
<?php
    include_once INCLUDES_PATH . '/footer.php'; // Standard footer for full page
} else { // ======= POPUP SELECTION MODE =======
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Link to main style.css for consistent card/button styling if desired, or keep popup minimal -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        body { padding: 10px; background-color: #fff; /* Or #f8f9fa for slight off-white */ }
        .fm-full-file-card { cursor: pointer; } /* Already defined in main CSS but good to have if main CSS not loaded */
        .fm-full-file-card:hover { box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important; border-color: #0d6efd !important;}
        /* Ensure gallery items are visible */
        #fmFullGallery .col-6 { flex-basis: 50%; max-width: 50%; } /* 2 per row on small screens */
        @media (min-width: 576px) { #fmFullGallery .col-sm-4 { flex-basis: 33.33%; max-width: 33.33%; } } /* 3 per row */
        @media (min-width: 768px) { #fmFullGallery .col-md-3 { flex-basis: 25%; max-width: 25%; } } /* 4 per row */
        @media (min-width: 992px) { #fmFullGallery .col-lg-2 { flex-basis: 20%; max-width: 20%; } } /* 5 per row for popup */

        /* Minimal scrollbar for popup */
        body::-webkit-scrollbar { width: 8px; }
        body::-webkit-scrollbar-thumb { background-color: #ccc; border-radius: 4px; }
        body::-webkit-scrollbar-track { background-color: #f1f1f1; }
    </style>
</head>
<body class="fm-selection-mode"> <!-- Add class to body for JS/CSS targeting -->
    <div class="container-fluid">
        <div class="row mb-3 sticky-top bg-white py-2 shadow-sm"> <!-- Sticky search bar -->
            <div class="col-12">
                <input type="search" id="fmFullSearchInput" class="form-control form-control-sm" placeholder="Search files by name...">
            </div>
        </div>
        <div class="row" id="fmFullGallery">
            <p class="text-center text-muted" id="fmFullGalleryLoadingMsg">Loading files...</p>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
<?php
} // End of if ($is_selection_mode)
?>
