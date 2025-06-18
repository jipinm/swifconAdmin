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

    function loadFileManagerFiles() {
        const gallery = $('#fileManagerMainGallery');
        gallery.html('<p class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading files...</p>');

        $.ajax({
            url: 'file_manager_actions.php?action=list_files', // Assumes file_manager_actions.php is in root
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                gallery.empty();
                if (response.status === 'success' && response.files.length > 0) {
                    response.files.forEach(function(file) {
                        let itemHtml = '<div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3 file-item" data-url="' + file.url + '" data-name="' + file.name + '">';
                        itemHtml += '<div class="card h-100">';
                        if (['jpg', 'jpeg', 'png', 'gif'].includes(file.type)) {
                            itemHtml += '<img src="' + file.url + '" class="card-img-top img-fluid" alt="' + file.name + '" style="height: 120px; object-fit: cover;">';
                        } else {
                            itemHtml += '<div class="text-center p-3" style="height: 120px; display: flex; align-items: center; justify-content: center;">';
                            itemHtml += '<i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>'; // Example for PDF
                            itemHtml += '</div>';
                        }
                        itemHtml += '<div class="card-body p-2">';
                        itemHtml += '<p class="card-text small text-truncate" title="' + file.name + '">' + file.name + '</p>';
                        itemHtml += '</div>';
                        itemHtml += '<div class="card-footer p-1 text-center">';
                        itemHtml += '<button class="btn btn-sm btn-danger delete-file-btn" data-filename="' + file.name + '" title="Delete"><i class="bi bi-trash3-fill"></i></button>';
                        itemHtml += '<button class="btn btn-sm btn-primary select-file-main-btn ms-1" title="Select"><i class="bi bi-check-circle-fill"></i></button>';
                        itemHtml += '</div>';
                        itemHtml += '</div></div>';
                        gallery.append(itemHtml);
                    });
                } else if (response.status === 'success') {
                    gallery.html('<p class="text-center text-muted">No files found in the library.</p>');
                } else {
                    gallery.html('<p class="text-center text-danger">Error loading files: ' + response.message + '</p>');
                }
            },
            error: function(xhr, status, error) {
                gallery.html('<p class="text-center text-danger">Could not connect to server to load files.</p>');
            }
        });
    }

    // Load files on page load or tab shown
    $('#gallery-main-tab').on('shown.bs.tab', loadFileManagerFiles);
    if ($('#gallery-main-tab').hasClass('active')) {
         loadFileManagerFiles(); // Initial load if tab is active by default
    }


    // Handle Main File Upload Form Submission (Adapted from modal version)
    $('#mainFileUploadForm').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const $progressBar = $('#mainUploadProgressBar');
        const $statusMessage = $('#mainUploadStatusMessage');
        const $uploadButton = $(this).find('button[type="submit"]');

        $statusMessage.html('').removeClass('alert alert-success alert-danger');
        $progressBar.parent().show();
        $progressBar.width('0%').attr('aria-valuenow', 0).text('0%');
        $uploadButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Uploading...');

        $.ajax({
            url: 'file_manager/upload.php', // This is the existing upload script
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            xhr: function() { /* ... (same as modal progress bar) ... */
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
                    $statusMessage.html('<div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> ' + response.message + '</div>');
                    loadFileManagerFiles(); // Refresh gallery
                    $('#upload-main-tab').removeClass('active'); // Switch back to gallery view
                    $('#gallery-main-tab').tab('show');
                } else {
                    $statusMessage.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> ' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) { /* ... (same as modal error handling) ... */
                let errorMsg = 'Error uploading file: ' + error;
                 if(xhr.responseText){ try { const errResponse = JSON.parse(xhr.responseText); if(errResponse.message) errorMsg = errResponse.message; } catch (e) {} }
                $statusMessage.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> ' + errorMsg + '</div>');
            },
            complete: function() { /* ... (same as modal complete handling) ... */
                $uploadButton.prop('disabled', false).html('<i class="bi bi-cloud-arrow-up-fill"></i> Start Upload');
                $progressBar.parent().delay(3000).fadeOut(function(){ $progressBar.width('0%').attr('aria-valuenow', 0).text('0%'); });
                $('#mainFileUploadForm')[0].reset();
            }
        });
    });

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
