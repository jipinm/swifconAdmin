<?php
require_once __DIR__ . '/config.php'; // Required for SITE_URL, INCLUDES_PATH etc.
require_once INCLUDES_PATH . '/session.php'; // For require_login

$is_selection_mode = (isset($_GET['selection_mode']) && $_GET['selection_mode'] === 'true');

if (!$is_selection_mode) { // Full page view
    require_login(); // Full page requires login check immediately
    $page_title = 'File Manager';
    include_once INCLUDES_PATH . '/header.php'; // Standard header & sidebar
} else { // Popup view - minimal layout
    // For popup, login check might still be good.
    // If direct access to popup URL needs auth, require_login() is essential.
    // This also ensures session is started if not already by config.php or other means.
    require_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select File</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        body { padding: 15px; background-color: #f8f9fa; }
        .file-card-popup { cursor: pointer; } /* Will be applied by JS if in selection mode */
        .file-card-popup:hover { box-shadow: 0 .125rem .25rem rgba(0,0,0,.075)!important; }
        /* Hide delete buttons in selection mode via CSS for extra safety, JS also handles rendering */
        body.fm-selection-mode .fm-full-delete-btn { display: none !important; }
    </style>
</head>
<body>
<?php
} // End of else for $is_selection_mode (minimal layout starts)
?>

<!-- Common Content for both modes -->
<div class="container-fluid <?php echo $is_selection_mode ? '' : 'py-4'; ?>">
    <div class="card shadow">
        <?php if (!$is_selection_mode): ?>
        <div class="card-header pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-center">
                <h4 class="card-title mb-0 me-3"><i class="bi bi-folder-fill me-2"></i> File Manager</h4>
            </div>
            <hr class="my-2">
        </div>
        <?php endif; ?>
        <div class="card-body <?php echo $is_selection_mode ? 'p-2' : 'pt-0'; ?>">
            <?php if (!$is_selection_mode): ?>
            <ul class="nav nav-tabs mt-2" id="fmFullTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="fmFull-gallery-tab" data-bs-toggle="tab" data-bs-target="#fmFull-gallery-pane" type="button" role="tab" aria-controls="fmFull-gallery-pane" aria-selected="true"><i class="bi bi-images"></i> Media Library</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="fmFull-upload-tab" data-bs-toggle="tab" data-bs-target="#fmFull-upload-pane" type="button" role="tab" aria-controls="fmFull-upload-pane" aria-selected="false"><i class="bi bi-upload"></i> Upload New Files</button>
                </li>
            </ul>
            <?php endif; ?>

            <div class="tab-content" id="fmFullTabsContent">
                <div class="tab-pane fade show active p-3 <?php echo $is_selection_mode ? 'pt-1' : ''; ?>" id="fmFull-gallery-pane" role="tabpanel" aria-labelledby="fmFull-gallery-tab">
                    <div class="row mb-3">
                        <div class="col-md-8 col-lg-6 <?php echo $is_selection_mode ? 'col-12' : 'mx-auto'; ?>">
                            <input type="search" id="fmFullSearchInput" class="form-control" placeholder="Search files by name...">
                        </div>
                    </div>
                    <div class="row" id="fmFullGallery">
                        <p class="text-center text-muted" id="fmFullGalleryLoadingMsg">Loading files...</p>
                    </div>
                </div>

                <?php if (!$is_selection_mode): ?>
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
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!$is_selection_mode): ?>
<!-- Delete Confirmation Modal -->
<div class="modal fade" id="fmFullDeleteConfirmModal" tabindex="-1" aria-labelledby="fmFullDeleteConfirmModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fmFullDeleteConfirmModalLabel">Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete the file: <strong id="fmFullFileNameToDeletePlaceholder"></strong>?
                <p class="text-danger small mt-2">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="fmFullConfirmDeleteFileBtn">Yes, Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- File Preview Modal -->
<div class="modal fade" id="fmFullFilePreviewModal" tabindex="-1" aria-labelledby="fmFullFilePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fmFullFilePreviewModalLabel">File Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3" id="fmFullFilePreviewImageContainer">
                    <img src="" id="fmFullFilePreviewImage" alt="File Preview" style="display: none;">
                </div>
                <div class="text-center mb-3" id="fmFullFilePreviewIconContainer" style="display: none; font-size: 6rem;">
                </div>
                <p><strong>Filename:</strong> <span id="fmFullFilePreviewName"></span></p>
                <div class="mb-3">
                    <label for="fmFullFilePreviewUrl" class="form-label">File URL:</label>
                    <div class="input-group">
                        <input type="text" id="fmFullFilePreviewUrl" class="form-control" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="fmFullCopyFilePreviewUrl"><i class="bi bi-clipboard-check"></i> Copy</button>
                    </div>
                </div>
                <p class="small text-muted"><strong>Size:</strong> <span id="fmFullFilePreviewSize"></span> | <strong>Modified:</strong> <span id="fmFullFilePreviewModifiedDate"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php
if (!$is_selection_mode) {
    include_once INCLUDES_PATH . '/footer.php'; // Standard footer for full page
} else { // Minimal JS for popup (jQuery, Bootstrap, and main script.js which contains FM logic)
?>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/script.js"></script>
</body>
</html>
<?php
}
?>
