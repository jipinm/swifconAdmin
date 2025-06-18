// Wait for the DOM to be fully loaded
$(document).ready(function() {

    // Store the input field that triggered the file manager
    let targetInputField = null;
    let targetPreviewElement = null;

    // Event listener for buttons that trigger the file manager
    // Assumes buttons have class 'open-file-manager' and data attributes:
    // data-input-field="#target_input_id"
    // data-preview-element="#target_preview_id" (optional)
    $('body').on('click', '.open-file-manager', function() {
        const targetInput = $(this).data('input-field');
        const targetPreview = $(this).data('preview-element');

        // Construct the URL for the file manager page in selection mode
        let fmUrl = 'file_manager_page.php?selection_mode=true';
        if (targetInput) {
            fmUrl += '&target_input=' + encodeURIComponent(targetInput);
        }
        if (targetPreview) {
            fmUrl += '&target_preview=' + encodeURIComponent(targetPreview);
        }

        // Define popup window features
        const popupWidth = 900;
        const popupHeight = 700;
        const left = (screen.width / 2) - (popupWidth / 2);
        const top = (screen.height / 2) - (popupHeight / 2);
        const popupFeatures = 'width=' + popupWidth + ',height=' + popupHeight + ',top=' + top + ',left=' + left + ',resizable=yes,scrollbars=yes';

        // Open the file manager in a new window
        const fmWindow = window.open(fmUrl, 'FileManagerPopup', popupFeatures);

        if (window.focus && fmWindow) {
            fmWindow.focus();
        }
    });


    // Handle File Upload Form Submission
    $('#fileUploadForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const $progressBar = $('#uploadProgressBar');
        const $statusMessage = $('#uploadStatusMessage');
        const $uploadButton = $(this).find('button[type="submit"]');

        $statusMessage.html('').removeClass('alert alert-success alert-danger');
        $progressBar.parent().show();
        $progressBar.width('0%').attr('aria-valuenow', 0).text('0%');
        $uploadButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...');

        $.ajax({
            url: 'file_manager/upload.php', // Relative path
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() {
                const xhr = new window.XMLHttpRequest();
                xhr.upload.addEventListener('progress', function(evt) {
                    if (evt.lengthComputable) {
                        const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                        $progressBar.width(percentComplete + '%').attr('aria-valuenow', percentComplete).text(percentComplete + '%');
                    }
                }, false);
                return xhr;
            },
            success: function(response) {
                if (response.status === 'success') {
                    $statusMessage.html('<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> ' + response.message + ' (URL: ' + response.url + ')</div>');
                    // Optionally, switch to gallery tab and highlight the new file
                    $('#gallery-tab').tab('show');
                    // TODO: Add uploaded file to gallery view and select it
                    // For now, just enable select button if a target input is set
                    if (targetInputField) {
                        // Store file info for selection
                        $('#selectFileButton').data('file-url', response.url).data('file-name', response.filename).prop('disabled', false);
                    }
                    // Refresh gallery (will be implemented later)
                    // loadFileManagerFiles();
                } else {
                    $statusMessage.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> ' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Error uploading file: ' + error;
                if(xhr.responseText){
                    try {
                        const errResponse = JSON.parse(xhr.responseText);
                        if(errResponse.message) errorMsg = errResponse.message;
                    } catch (e) { /* ignore parsing error, use default message */ }
                }
                $statusMessage.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> ' + errorMsg + '</div>');
            },
            complete: function() {
                $uploadButton.prop('disabled', false).html('<i class="bi bi-cloud-arrow-up-fill"></i> Start Upload');
                $progressBar.parent().delay(2000).fadeOut(function(){
                     $progressBar.width('0%').attr('aria-valuenow', 0).text('0%');
                });
                $('#fileUploadForm')[0].reset(); // Reset the form
            }
        });
    });

    // Handle File Selection
    $('#selectFileButton').on('click', function() {
        if (targetInputField) {
            const fileUrl = $(this).data('file-url');
            $(targetInputField).val(fileUrl);

            if (targetPreviewElement) {
                // Assuming the preview element is an <img> or a container for an <img>
                // You might need more sophisticated preview logic based on file type
                if ($(targetPreviewElement).is('img')) {
                    $(targetPreviewElement).attr('src', fileUrl);
                } else {
                    // For non-image previews or more complex previews
                    $(targetPreviewElement).html('<img src="' + fileUrl + '" class="img-fluid" style="max-height: 150px;">');
                }
                 $(targetPreviewElement).show();
            }
            $('#fileManagerModal').modal('hide');
            // Reset for next use
            $(this).removeData('file-url').removeData('file-name').prop('disabled', true);
            targetInputField = null;
            targetPreviewElement = null;
        } else {
            alert('No target input field specified for selection.');
        }
    });

    // When modal is hidden, reset states
    $('#fileManagerModal').on('hidden.bs.modal', function () {
        $('#uploadStatusMessage').html('');
        $('#uploadProgressBar').parent().hide();
        $('#fileUploadForm')[0].reset();
        $('#selectFileButton').removeData('file-url').removeData('file-name').prop('disabled', true);
        // Potentially reset gallery selection too
        // $('.file-manager-item.selected').removeClass('selected');
    });

    // Placeholder for loading files into the gallery (to be implemented)
    // function loadFileManagerFiles(page = 1) {
    //     console.log("loadFileManagerFiles function called - to be implemented.");
    //     // AJAX call to a PHP script that lists files from the uploads directory
    //     // e.g., file_manager/list_files.php
    //     // Display files in #fileManagerGallery
    //     // Handle selection, deletion, preview etc.
    // }

    // Trigger initial load or when gallery tab is shown
    // $('#gallery-tab').on('shown.bs.tab', function () {
    //    loadFileManagerFiles();
    // });

}); // End document ready


// --- File Manager Page Specific JS ---
if ($('#fileManagerMainGallery').length) { // Only run if on File Manager Page

    let allFileManagerFiles = []; // To store the complete list of files from server

    // New function to render the gallery
    function renderFileManagerGallery(filesToRender) {
        const gallery = $('#fileManagerMainGallery');
        gallery.empty(); // Clear previous content

        if (filesToRender.length > 0) {
            filesToRender.forEach(function(file) {
                let itemHtml = '<div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3 file-item" data-url="' + file.url + '" data-name="' + escapeHtml(file.name) + '" data-type="' + file.type + '" data-size="' + file.size + '" data-modified="' + file.modified + '">'; // Added more data attributes
                itemHtml += '<div class="card h-100 file-card-item">'; // Added file-card-item class for general click
                if (['jpg', 'jpeg', 'png', 'gif'].includes(file.type.toLowerCase())) {
                    itemHtml += '<img src="' + file.url + '" class="card-img-top img-fluid file-preview-image" alt="' + escapeHtml(file.name) + '" style="height: 120px; object-fit: cover; cursor:pointer;">';
                } else {
                    itemHtml += '<div class="text-center p-3 file-preview-icon" style="height: 120px; display: flex; align-items: center; justify-content: center; cursor:pointer;">';
                    // More specific icons can be added here based on file.type
                    if (file.type === 'pdf') {
                        itemHtml += '<i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>';
                    } else {
                        itemHtml += '<i class="bi bi-file-earmark-text-fill fs-1 text-secondary"></i>'; // Generic file icon
                    }
                    itemHtml += '</div>';
                }
                itemHtml += '<div class="card-body p-2">';
                itemHtml += '<p class="card-text small text-truncate" title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + '</p>';
                itemHtml += '</div>';
                itemHtml += '<div class="card-footer p-1 text-center">';
                itemHtml += '<button class="btn btn-sm btn-danger delete-file-btn" data-filename="' + escapeHtml(file.name) + '" title="Delete"><i class="bi bi-trash3-fill"></i></button>';
                itemHtml += '<button class="btn btn-sm btn-primary select-file-main-btn ms-1" title="Select for Form"><i class="bi bi-check-circle-fill"></i></button>';
                itemHtml += '</div>';
                itemHtml += '</div></div>';
                gallery.append(itemHtml);
            });
        } else {
            gallery.html('<p class="text-center text-muted">No files found matching your criteria.</p>');
        }
    }

    // Modify existing loadFileManagerFiles function
    function loadFileManagerFiles() {
        const gallery = $('#fileManagerMainGallery');
        // Keep the initial loading message or spinner setup
        gallery.html('<p class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading files...</p>');
        $('#fileSearchInput').val(''); // Clear search input on full reload

        $.ajax({
            url: 'file_manager_actions.php?action=list_files',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                // gallery.empty(); // Moved to renderFileManagerGallery
                if (response.status === 'success') {
                    allFileManagerFiles = response.files; // Store all files
                    renderFileManagerGallery(allFileManagerFiles); // Initial render
                } else {
                    allFileManagerFiles = []; // Clear stored files on error
                    renderFileManagerGallery([]); // Render empty state with error message from server if any
                    gallery.html('<p class="text-center text-danger">Error loading files: ' + escapeHtml(response.message) + '</p>'); // Fallback if renderFileManagerGallery shows "no files found"
                }
            },
            error: function(xhr, status, error) {
                // Existing detailed error handling
                console.error("File Manager AJAX Error:", { readyState: xhr.readyState, status: xhr.status, statusText: xhr.statusText, responseText: xhr.responseText, errorThrown: error, ajaxStatus: status });
                gallery.html('<p class="text-center text-danger">Error loading files. Check console for details. (Is `uploads/` directory writable and readable by the web server?)</p>');
                allFileManagerFiles = []; // Clear stored files on error
            }
        });
    }

    // Add event listener for the search input
    $('#fileSearchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        if (searchTerm === "") {
            renderFileManagerGallery(allFileManagerFiles); // If search is cleared, show all files
        } else {
            const filteredFiles = allFileManagerFiles.filter(function(file) {
                return file.name.toLowerCase().includes(searchTerm);
            });
            renderFileManagerGallery(filteredFiles);
        }
    });

    // Ensure escapeHtml function is available
    // function escapeHtml(unsafe) { ... } (already added in previous step)


    // Handle Main File Upload Form Submission (Adapted from modal version)
    $('#mainFileUploadForm').on('submit', function(e) {
        e.preventDefault();
        const files = $('#main_file_to_upload')[0].files;
        const $overallStatusMessage = $('#mainUploadStatusMessage'); // Overall status for the batch
        const $multiProgressArea = $('#multiUploadProgressArea');
        const $uploadButton = $(this).find('button[type="submit"]');

        if (files.length === 0) {
            $overallStatusMessage.html('<div class="alert alert-warning">Please select one or more files to upload.</div>');
            return;
        }

        $overallStatusMessage.html('');
        $multiProgressArea.html(''); // Clear previous individual progresses
        $uploadButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Uploading...');

        let filesProcessed = 0;
        let successfulUploads = 0;
        const totalFiles = files.length;

        Array.from(files).forEach((file, index) => {
            const fileId = 'upload-progress-' + index + '-' + Date.now(); // Unique ID for the progress element
            const progressElementHtml =
                '<div id="' + fileId + '" class="mb-2 p-2 border rounded">' +
                '  <div class="d-flex justify-content-between align-items-center">' +
                '    <small class="text-truncate" style="max-width: 70%;">' + escapeHtml(file.name) + '</small>' +
                '    <small class="status-text text-muted">Waiting...</small>' +
                '  </div>' +
                '  <div class="progress mt-1" style="height: 10px;">' +
                '    <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>' +
                '  </div>' +
                '</div>';
            $multiProgressArea.append(progressElementHtml);

            const $fileProgressElement = $('#' + fileId);
            const $progressBar = $fileProgressElement.find('.progress-bar');
            const $statusText = $fileProgressElement.find('.status-text');

            const formData = new FormData();
            formData.append('file_to_upload', file);

            $.ajax({
                url: 'file_manager/upload.php', // Existing single file upload script
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = Math.round((evt.loaded / evt.total) * 100);
                            $progressBar.width(percentComplete + '%').attr('aria-valuenow', percentComplete).text(percentComplete + '%');
                            $statusText.text(percentComplete + '% Uploaded');
                        }
                    }, false);
                    return xhr;
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $progressBar.addClass('bg-success');
                        $statusText.text('Success!').removeClass('text-muted').addClass('text-success');
                        successfulUploads++;
                    } else {
                        $progressBar.addClass('bg-danger');
                        $statusText.text('Error: ' + response.message).removeClass('text-muted').addClass('text-danger');
                    }
                },
                error: function(xhr, status, error) {
                    let errorMsg = 'Network/Server Error';
                    if(xhr.responseText){ try { const errResponse = JSON.parse(xhr.responseText); if(errResponse.message) errorMsg = errResponse.message; } catch (e) {} }
                    $progressBar.addClass('bg-danger');
                    $statusText.text('Failed: ' + errorMsg).removeClass('text-muted').addClass('text-danger');
                },
                complete: function() {
                    filesProcessed++;
                    if (filesProcessed === totalFiles) {
                        $uploadButton.prop('disabled', false).html('<i class="bi bi-cloud-arrow-up-fill"></i> Start Upload');
                        if (successfulUploads > 0) {
                            loadFileManagerFiles(); // Refresh gallery if at least one success
                            if (successfulUploads === totalFiles) {
                                $overallStatusMessage.html('<div class="alert alert-success">All files uploaded successfully!</div>');
                            } else {
                                $overallStatusMessage.html('<div class="alert alert-warning">' + successfulUploads + ' of ' + totalFiles + ' files uploaded. Some had errors.</div>');
                            }
                        } else {
                             $overallStatusMessage.html('<div class="alert alert-danger">No files were uploaded successfully.</div>');
                        }
                        $('#main_file_to_upload').val(''); // Clear the file input
                        // Optionally clear individual progress after a delay
                        // setTimeout(function(){ $multiProgressArea.html(''); $overallStatusMessage.html(''); }, 5000);
                    }
                }
            });
        });
    });

    // Helper function to escape HTML (simple version)
    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }
    // Make sure escapeHtml is available or defined if not already
    // This can be placed at a more global scope in script.js if used elsewhere.


    // Handle Delete File
    $('body').on('click', '.delete-file-btn', function() {
        const filename = $(this).data('filename');
        if (confirm('Are you sure you want to delete the file: ' + filename + '? This action cannot be undone.')) {
            $.ajax({
                url: 'file_manager_actions.php', // Assumes in root
                type: 'POST',
                data: { action: 'delete_file', filename: filename },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message); // Or use a nicer Bootstrap alert
                        loadFileManagerFiles(); // Refresh gallery
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Could not connect to server to delete file.');
                }
            });
        }
    });

    // Handle Selection from Main File Manager (This is the tricky part for integration)
    // This example assumes the page was opened in a "selection mode" via URL parameter
    // And will call a function in the opener window.
    const urlParams = new URLSearchParams(window.location.search);
    const isSelectionMode = urlParams.get('selection_mode') === 'true';
    const targetInputId = urlParams.get('target_input');
    const targetPreviewId = urlParams.get('target_preview');

    if (isSelectionMode) {
        // Modify UI slightly for selection mode, e.g., hide delete, show prominent select instructions
        $('#fileManagerMainGallery').addClass('selection-mode');
        // Add a specific class to body or a container for selection mode styling
        $('body').addClass('fm-selection-active');
    }

    $('body').on('click', '.select-file-main-btn', function() {
        const fileUrl = $(this).closest('.file-item').data('url');
        const fileName = $(this).closest('.file-item').data('name');

        if (isSelectionMode && window.opener && !window.opener.closed && targetInputId) {
            // Call a function in the opener window to set the value
            // The opener window must have a function like `handleFileSelectionFromManager`
            if (typeof window.opener.handleFileSelectionFromManager === 'function') {
                window.opener.handleFileSelectionFromManager(targetInputId, fileUrl, targetPreviewId, fileName);
                window.close(); // Close the file manager window
            } else {
                alert('Error: Parent window callback function not found.');
            }
        } else if (!isSelectionMode) {
            // Normal click, maybe copy URL to clipboard or show details (not implemented here)
            navigator.clipboard.writeText(fileUrl).then(function() {
                alert('File URL copied to clipboard: ' + fileUrl);
            }, function(err) {
                alert('Could not copy URL. Manual copy: ' + fileUrl);
            });
        } else {
             alert('File selected: ' + fileUrl + '\n(This window was not opened in selection mode or opener is invalid)');
        }
    });

    // Click handler for file items in the gallery (for preview)
    $('#fileManagerMainGallery').on('click', '.file-card-item', function(e) {
        // Prevent if the click was on a button within the card footer
        if ($(e.target).closest('.card-footer').length > 0) {
            return;
        }

        // Prevent if in selection mode (as defined by URL param for popup)
        const urlParamsForPreview = new URLSearchParams(window.location.search);
        if (urlParamsForPreview.get('selection_mode') === 'true') {
            // In selection mode, a click on the item might be intended for selection.
            // For now, let's assume the explicit "Select for Form" button is the only selection trigger.
            // If a general click on the card should also select, this logic would need to change.
            // For this step, we only show preview if NOT in selection mode.
            // However, user might want to preview *before* selecting via the button.
            // Let's allow preview always, but selection is only via the button.
        }

        const fileItem = $(this).closest('.file-item');
        const fileName = fileItem.data('name');
        const fileUrl = fileItem.data('url');
        const fileType = fileItem.data('type').toLowerCase();
        const fileSize = fileItem.data('size'); // In bytes
        const fileModifiedTimestamp = fileItem.data('modified'); // Timestamp

        $('#filePreviewName').text(fileName);
        $('#filePreviewUrl').val(fileUrl);

        // Format file size
        let formattedSize = 'N/A';
        if (fileSize) {
            if (fileSize < 1024) {
                formattedSize = fileSize + ' Bytes';
            } else if (fileSize < (1024*1024)) {
                formattedSize = (fileSize/1024).toFixed(2) + ' KB';
            } else {
                formattedSize = (fileSize/(1024*1024)).toFixed(2) + ' MB';
            }
        }
        $('#filePreviewSize').text(formattedSize);

        // Format modified date
        let formattedDate = 'N/A';
        if (fileModifiedTimestamp) {
            const date = new Date(fileModifiedTimestamp * 1000); // JS uses milliseconds
            formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
        $('#filePreviewModifiedDate').text(formattedDate);


        const $previewImage = $('#filePreviewImage');
        const $previewImageContainer = $('#filePreviewImageContainer');
        const $previewIconContainer = $('#filePreviewIconContainer');

        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileType)) {
            $previewImage.attr('src', fileUrl).show();
            $previewImageContainer.show();
            $previewIconContainer.hide().html('');
        } else {
            $previewImage.hide().attr('src', '');
            $previewImageContainer.hide();
            let iconClass = 'bi-file-earmark-text-fill text-secondary'; // Generic file
            if (fileType === 'pdf') {
                iconClass = 'bi-file-earmark-pdf-fill text-danger';
            } // Add more else if for other types (zip, doc, etc.)
            $previewIconContainer.html('<i class="bi ' + iconClass + '"></i>').show();
        }

        $('#filePreviewModalLabel').text('Preview: ' + escapeHtml(fileName)); // Use escapeHtml for filename in title
        $('#filePreviewModal').modal('show');
    });

    // Copy URL button in preview modal
    $('#copyFilePreviewUrl').on('click', function() {
        const urlInput = document.getElementById('filePreviewUrl');
        urlInput.select();
        urlInput.setSelectionRange(0, 99999); // For mobile devices

        try {
            document.execCommand('copy');
            // Optionally show a temporary "Copied!" message
            $(this).html('<i class="bi bi-clipboard-check-fill"></i> Copied!').removeClass('btn-outline-secondary').addClass('btn-success');
            setTimeout(() => {
                $(this).html('<i class="bi bi-clipboard-check"></i> Copy').removeClass('btn-success').addClass('btn-outline-secondary');
            }, 2000);
        } catch (err) {
            alert('Failed to copy URL. Please copy it manually.');
        }
    });

    // Reset modal content when hidden (optional, good practice)
    $('#filePreviewModal').on('hidden.bs.modal', function () {
        $('#filePreviewImage').attr('src', '').hide();
        $('#filePreviewIconContainer').hide().html('');
        $('#filePreviewName').text('');
        $('#filePreviewUrl').val('');
        $('#filePreviewModalLabel').text('File Preview');
    });
} // End File Manager Page Specific JS

// Global function to be called by the File Manager popup window
function handleFileSelectionFromManager(targetInputId, fileUrl, targetPreviewId, fileName) {
    $(targetInputId).val(fileUrl);
    if (targetPreviewId) {
        if ($(targetPreviewId).is('img')) {
            $(targetPreviewId).attr('src', fileUrl).show();
        } else {
            $(targetPreviewId).html('<img src="' + fileUrl + '" class="img-fluid" style="max-height: 150px;">').show();
        }
    }
    // If the file manager was opened via the old modal method, hide that modal
    if ($('#fileManagerModal').hasClass('show')) {
        $('#fileManagerModal').modal('hide');
    }
}
