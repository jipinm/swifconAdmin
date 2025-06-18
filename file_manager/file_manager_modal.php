<?php
// This file is intended to be included where the modal is needed.
// Ensure CSRF token generation if you plan to implement CSRF protection for AJAX.
?>
<div class="modal fade" id="fileManagerModal" tabindex="-1" aria-labelledby="fileManagerModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="fileManagerModalLabel"><i class="bi bi-folder-fill"></i> File Manager</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Tabs for Upload and Gallery -->
                <ul class="nav nav-tabs" id="fileManagerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload-pane" type="button" role="tab" aria-controls="upload-pane" aria-selected="true"><i class="bi bi-upload"></i> Upload File</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="gallery-tab" data-bs-toggle="tab" data-bs-target="#gallery-pane" type="button" role="tab" aria-controls="gallery-pane" aria-selected="false"><i class="bi bi-images"></i> Media Library</button>
                    </li>
                </ul>
                <div class="tab-content" id="fileManagerTabsContent">
                    <!-- Upload Pane -->
                    <div class="tab-pane fade show active p-3" id="upload-pane" role="tabpanel" aria-labelledby="upload-tab">
                        <form id="fileUploadForm" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="file_to_upload" class="form-label">Select file (Max 5MB: JPG, JPEG, PNG, GIF, PDF)</label>
                                <input class="form-control" type="file" id="file_to_upload" name="file_to_upload" required>
                            </div>
                            <button type="submit" class="btn btn-primary"><i class="bi bi-cloud-arrow-up-fill"></i> Start Upload</button>
                            <div class="progress mt-3" style="height: 25px; display:none;">
                                <div id="uploadProgressBar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                            </div>
                        </form>
                        <div id="uploadStatusMessage" class="mt-3"></div>
                    </div>
                    <!-- Gallery Pane (Placeholder for now) -->
                    <div class="tab-pane fade p-3" id="gallery-pane" role="tabpanel" aria-labelledby="gallery-tab">
                        <div class="row" id="fileManagerGallery">
                            <p>Media library will be displayed here. Functionality to list, preview, and select files will be added later.</p>
                            <!-- Files will be loaded here by JS -->
                        </div>
                         <div class="text-center mt-3">
                            <button type="button" class="btn btn-secondary" id="loadMoreFiles" style="display:none;">Load More</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="selectFileButton" disabled><i class="bi bi-check-circle-fill"></i> Select File</button>
            </div>
        </div>
    </div>
</div>
