<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_login();

$page_title = 'File Manager';
include_once INCLUDES_PATH . '/header.php';

// Placeholder for messages
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="bi bi-folder-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                        <!-- Button to trigger upload modal/section might go here later -->
                    </div>
                    <hr class="my-2">
                </div>
                <div class="card-body pt-0">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <!-- File Manager UI will be built here -->
                    <!-- Tabs for Upload and Gallery -->
                    <ul class="nav nav-tabs mt-3" id="fileManagerMainTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="gallery-main-tab" data-bs-toggle="tab" data-bs-target="#gallery-main-pane" type="button" role="tab" aria-controls="gallery-main-pane" aria-selected="true"><i class="bi bi-images"></i> Media Library</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="upload-main-tab" data-bs-toggle="tab" data-bs-target="#upload-main-pane" type="button" role="tab" aria-controls="upload-main-pane" aria-selected="false"><i class="bi bi-upload"></i> Upload New File</button>
                        </li>
                    </ul>
                    <div class="tab-content" id="fileManagerMainTabsContent">
                        <!-- Gallery Pane -->
                        <div class="tab-pane fade show active p-3" id="gallery-main-pane" role="tabpanel" aria-labelledby="gallery-main-tab">
                                <div class="row mb-3">
                                    <div class="col-md-6 offset-md-3"> <!-- Or adjust width/offset as preferred -->
                                        <input type="search" id="fileSearchInput" class="form-control" placeholder="Search files by name...">
                                    </div>
                                </div>
                            <div class="row" id="fileManagerMainGallery">
                                    <p class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading files...</p>
                                    <!-- Initial message updated from "Media library will be loaded here..." for clarity -->
                            </div>
                            <div class="text-center mt-3">
                                <button type="button" class="btn btn-secondary" id="loadMoreMainFiles" style="display:none;">Load More</button>
                            </div>
                        </div>
                        <!-- Upload Pane -->
                        <div class="tab-pane fade p-3" id="upload-main-pane" role="tabpanel" aria-labelledby="upload-main-tab">
                            <form id="mainFileUploadForm" enctype="multipart/form-data">
                                <div class="mb-3 col-md-6">
                                    <label for="main_file_to_upload" class="form-label">Select file(s) (Max 5MB each: JPG, JPEG, PNG, GIF, PDF)</label>
                                    <input class="form-control" type="file" id="main_file_to_upload" name="file_to_upload" required multiple>
                                </div>
                                <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-arrow-up-fill"></i> Start Upload</button>
                                <!-- Note: The main progress bar #mainUploadProgressBar might be repurposed or removed if individual ones are sufficient -->
                                <!-- For now, let's keep it as it might show overall batch progress or be hidden -->
                                <div class="progress mt-3" style="height: 25px; display:none;">
                                    <div id="mainUploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                                </div>
                            </form>
                            <div id="mainUploadStatusMessage" class="mt-3"></div> <!-- For overall messages if any -->
                            <div id="multiUploadProgressArea" class="mt-3"></div> <!-- For individual file statuses -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// The existing modal might be reused or parts of it. For now, include it to see if it conflicts or can be adapted.
// include_once FILE_MANAGER_PATH . '/file_manager_modal.php';
    ?>

    <!-- Add this modal HTML to file_manager_page.php -->
    <div class="modal fade" id="filePreviewModal" tabindex="-1" aria-labelledby="filePreviewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="filePreviewModalLabel">File Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-3" id="filePreviewImageContainer">
                        <img src="" id="filePreviewImage" class="img-fluid" alt="File Preview" style="max-height: 70vh; display: none;">
                    </div>
                    <div class="text-center mb-3" id="filePreviewIconContainer" style="display: none; font-size: 5rem;">
                        <!-- Icon will be inserted here by JS -->
                    </div>
                    <p><strong>Filename:</strong> <span id="filePreviewName"></span></p>
                    <div class="mb-3">
                        <label for="filePreviewUrl" class="form-label">File URL:</label>
                        <div class="input-group">
                            <input type="text" id="filePreviewUrl" class="form-control" readonly>
                            <button class="btn btn-outline-secondary" type="button" id="copyFilePreviewUrl"><i class="bi bi-clipboard-check"></i> Copy</button>
                        </div>
                    </div>
                     <p class="small text-muted"><strong>Size:</strong> <span id="filePreviewSize"></span> | <strong>Modified:</strong> <span id="filePreviewModifiedDate"></span></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php
include_once INCLUDES_PATH . '/footer.php';
?>
