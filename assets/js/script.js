// Global function to be called by the File Manager popup window
function handleFileSelectionFromManager(targetInputId, fileUrl, targetPreviewId, fileName) {
    console.log("Global handleFileSelectionFromManager called with:", {
        targetInputId: targetInputId,
        fileUrl: fileUrl,
        targetPreviewId: targetPreviewId,
        fileName: fileName
    });

    $(targetInputId).val(fileUrl).trigger('change'); // .trigger('change') is good for reactivity

    if (targetPreviewId) {
        let $previewElement = $(targetPreviewId);
        if ($previewElement.length) {
            if ($previewElement.is('img')) {
                $previewElement.attr('src', fileUrl).show();
            } else {
                // If it's a container, replace its content with an image
                $previewElement.html('<img src="' + fileUrl + '" class="img-fluid" style="max-height: 150px; border: 1px solid #ddd; padding: 5px;">').show();
            }
        } else {
            console.warn("handleFileSelectionFromManager: Preview element " + targetPreviewId + " not found.");
        }
    }

    // This part for old modal might be removed if modal is no longer used for opening FM
    // if ($('#fileManagerModal').length && $('#fileManagerModal').hasClass('show')) {
    //     $('#fileManagerModal').modal('hide');
    // }
}

// Wait for the DOM to be fully loaded
$(document).ready(function() {

    // Store the input field that triggered the file manager
    // These are likely for the old modal, might be removable if modal is fully deprecated.
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


    // Handle File Upload Form Submission (For old modal - #fileUploadForm)
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
                    $('#gallery-tab').tab('show'); // Switch to gallery in modal
                    if (targetInputField) { // targetInputField is for modal context
                        $('#selectFileButton').data('file-url', response.url).data('file-name', response.filename).prop('disabled', false);
                    }
                    // Might need a way to refresh modal's own gallery if it had one.
                } else {
                    $statusMessage.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> ' + response.message + '</div>');
                }
            },
            error: function(xhr, status, error) {
                let errorMsg = 'Error uploading file: ' + error;
                if(xhr.responseText){ try { const err = JSON.parse(xhr.responseText); if(err.message) errorMsg = err.message; } catch (e) {} }
                $statusMessage.html('<div class="alert alert-danger"><i class="bi bi-exclamation-triangle-fill"></i> ' + errorMsg + '</div>');
            },
            complete: function() {
                $uploadButton.prop('disabled', false).html('<i class="bi bi-cloud-arrow-up-fill"></i> Start Upload');
                $progressBar.parent().delay(2000).fadeOut(function(){ $progressBar.width('0%').attr('aria-valuenow', 0).text('0%'); });
                $('#fileUploadForm')[0].reset();
            }
        });
    });

    // Handle File Selection (from old modal - #selectFileButton)
    $('#selectFileButton').on('click', function() {
        if (targetInputField) { // targetInputField is for modal context
            const fileUrl = $(this).data('file-url');
            // Call the global handler, as if it was selected from the popup
            // This ensures consistent behavior for populating input/preview
            handleFileSelectionFromManager(targetInputField, fileUrl, targetPreviewElement, $(this).data('file-name'));

            $('#fileManagerModal').modal('hide');
            // Reset modal-specific states
            $(this).removeData('file-url').removeData('file-name').prop('disabled', true);
            targetInputField = null;
            targetPreviewElement = null;
        } else {
            alert('No target input field specified for selection.');
        }
    });

    $('#fileManagerModal').on('hidden.bs.modal', function () {
        $('#uploadStatusMessage').html('');
        $('#uploadProgressBar').parent().hide();
        $('#fileUploadForm')[0].reset();
        $('#selectFileButton').removeData('file-url').removeData('file-name').prop('disabled', true);
    });

}); // End $(document).ready


// --- File Manager Page Specific JS ---
if ($('#fileManagerMainGallery').length) { // Only run if on File Manager Page

    let allFileManagerFiles = [];

    function escapeHtml(unsafe) {
        return unsafe
             .replace(/&/g, "&amp;")
             .replace(/</g, "&lt;")
             .replace(/>/g, "&gt;")
             .replace(/"/g, "&quot;")
             .replace(/'/g, "&#039;");
    }

    function renderFileManagerGallery(filesToRender) {
        const gallery = $('#fileManagerMainGallery');
        gallery.empty();

        if (filesToRender.length > 0) {
            filesToRender.forEach(function(file) {
                let itemHtml = '<div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3 file-item" data-url="' + file.url + '" data-name="' + escapeHtml(file.name) + '" data-type="' + file.type + '" data-size="' + file.size + '" data-modified="' + file.modified + '">';
                itemHtml += '<div class="card h-100 file-card-item">';
                if (['jpg', 'jpeg', 'png', 'gif'].includes(file.type.toLowerCase())) {
                    itemHtml += '<img src="' + file.url + '" class="card-img-top img-fluid file-preview-image" alt="' + escapeHtml(file.name) + '" style="height: 120px; object-fit: cover; cursor:pointer;">';
                } else {
                    itemHtml += '<div class="text-center p-3 file-preview-icon" style="height: 120px; display: flex; align-items: center; justify-content: center; cursor:pointer;">';
                    if (file.type === 'pdf') { itemHtml += '<i class="bi bi-file-earmark-pdf-fill fs-1 text-danger"></i>'; }
                    else { itemHtml += '<i class="bi bi-file-earmark-text-fill fs-1 text-secondary"></i>'; }
                    itemHtml += '</div>';
                }
                itemHtml += '<div class="card-body p-2"><p class="card-text small text-truncate" title="' + escapeHtml(file.name) + '">' + escapeHtml(file.name) + '</p></div>';
                itemHtml += '<div class="card-footer p-1 text-center">';
                itemHtml += '<button class="btn btn-sm btn-danger delete-file-btn" data-filename="' + file.name + '" title="Delete (' + escapeHtml(file.name) + ')"><i class="bi bi-trash3-fill"></i></button>';
                itemHtml += '<button class="btn btn-sm btn-primary select-file-main-btn ms-1" title="Select for Form"><i class="bi bi-check-circle-fill"></i></button>';
                itemHtml += '</div></div></div>';
                gallery.append(itemHtml);
            });
        } else {
            gallery.html('<p class="text-center text-muted">No files found matching your criteria.</p>');
        }
    }

    function loadFileManagerFiles() {
        const gallery = $('#fileManagerMainGallery');
        gallery.html('<p class="text-center"><span class="spinner-border spinner-border-sm"></span> Loading files...</p>');
        $('#fileSearchInput').val('');

        $.ajax({
            url: 'file_manager_actions.php?action=list_files', type: 'GET', dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    allFileManagerFiles = response.files;
                    renderFileManagerGallery(allFileManagerFiles);
                } else {
                    allFileManagerFiles = [];
                    renderFileManagerGallery([]);
                    gallery.html('<p class="text-center text-danger">Error loading files: ' + escapeHtml(response.message) + '</p>');
                }
            },
            error: function(xhr, status, error) {
                console.error("File Manager AJAX Error:", { readyState: xhr.readyState, status: xhr.status, statusText: xhr.statusText, responseText: xhr.responseText, errorThrown: error, ajaxStatus: status });
                gallery.html('<p class="text-center text-danger">Error loading files. Check console for details. (Is `uploads/` directory writable and readable by the web server?)</p>');
                allFileManagerFiles = [];
            }
        });
    }

    loadFileManagerFiles();

    $('#gallery-main-tab').on('shown.bs.tab', function() { loadFileManagerFiles(); });

    $('#fileSearchInput').on('input', function() {
        const searchTerm = $(this).val().toLowerCase();
        if (searchTerm === "") { renderFileManagerGallery(allFileManagerFiles); }
        else { const filteredFiles = allFileManagerFiles.filter(function(file) { return file.name.toLowerCase().includes(searchTerm); }); renderFileManagerGallery(filteredFiles); }
    });

    $('#mainFileUploadForm').on('submit', function(e) {
        e.preventDefault();
        const files = $('#main_file_to_upload')[0].files;
        const $overallStatusMessage = $('#mainUploadStatusMessage');
        const $multiProgressArea = $('#multiUploadProgressArea');
        const $uploadButton = $(this).find('button[type="submit"]');

        if (files.length === 0) { $overallStatusMessage.html('<div class="alert alert-warning">Please select one or more files to upload.</div>'); return; }

        $overallStatusMessage.html(''); $multiProgressArea.html('');
        $uploadButton.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Uploading...');

        let filesProcessed = 0, successfulUploads = 0; const totalFiles = files.length;

        Array.from(files).forEach((file, index) => {
            const fileId = 'upload-progress-' + index + '-' + Date.now();
            const progressElementHtml = '<div id="' + fileId + '" class="mb-2 p-2 border rounded"><div class="d-flex justify-content-between align-items-center"><small class="text-truncate" style="max-width: 70%;">' + escapeHtml(file.name) + '</small><small class="status-text text-muted">Waiting...</small></div><div class="progress mt-1" style="height: 10px;"><div class="progress-bar" role="progressbar" style="width: 0%;">0%</div></div></div>';
            $multiProgressArea.append(progressElementHtml);
            const $fileProgressElement = $('#' + fileId), $progressBar = $fileProgressElement.find('.progress-bar'), $statusText = $fileProgressElement.find('.status-text');
            const formData = new FormData(); formData.append('file_to_upload', file);

            $.ajax({
                url: 'file_manager/upload.php', type: 'POST', data: formData, contentType: false, processData: false,
                xhr: function() { const xhr = new window.XMLHttpRequest(); xhr.upload.addEventListener('progress', function(evt) { if (evt.lengthComputable) { const percent = Math.round((evt.loaded / evt.total) * 100); $progressBar.width(percent + '%').text(percent + '%'); $statusText.text(percent + '% Uploaded');}}, false); return xhr; },
                success: function(response) { if (response.status === 'success') { $progressBar.addClass('bg-success'); $statusText.text('Success!').removeClass('text-muted').addClass('text-success'); successfulUploads++; } else { $progressBar.addClass('bg-danger'); $statusText.text('Error: ' + response.message).removeClass('text-muted').addClass('text-danger'); }},
                error: function(xhr, st, err) { let msg = 'Network/Server Error'; if(xhr.responseText){ try { const er = JSON.parse(xhr.responseText); if(er.message) msg = er.message; } catch (e) {} } $progressBar.addClass('bg-danger'); $statusText.text('Failed: ' + msg).removeClass('text-muted').addClass('text-danger'); },
                complete: function() { filesProcessed++; if (filesProcessed === totalFiles) { $uploadButton.prop('disabled', false).html('<i class="bi bi-cloud-arrow-up-fill"></i> Start Upload'); if (successfulUploads > 0) { loadFileManagerFiles(); if (successfulUploads === totalFiles) { $overallStatusMessage.html('<div class="alert alert-success">All files uploaded successfully!</div>'); } else { $overallStatusMessage.html('<div class="alert alert-warning">' + successfulUploads + ' of ' + totalFiles + ' files uploaded. Some had errors.</div>'); }} else { $overallStatusMessage.html('<div class="alert alert-danger">No files were uploaded successfully.</div>');} $('#main_file_to_upload').val('');}}
            });
        });
    });

    $('body').on('click', '.delete-file-btn', function() {
        alert('DEBUG: Delete button (.delete-file-btn) clicked!'); // Immediate visual feedback
        console.log('DEBUG: .delete-file-btn clicked'); // Console feedback

        const filename = $(this).data('filename');
        console.log('DEBUG: Filename to delete:', filename);

        if (!filename) {
            alert('DEBUG: Error - No filename found for deletion. Check data-filename attribute.');
            console.error('DEBUG: No filename found for deletion on button:', this);
            return;
        }

        if (confirm('Are you sure you want to delete the file: ' + escapeHtml(filename) + '? This action cannot be undone.')) {
            console.log('DEBUG: Deletion confirmed for:', filename);
            $.ajax({
                url: 'file_manager_actions.php', // This URL should be correct
                type: 'POST',
                data: {
                    action: 'delete_file',
                    filename: filename
                },
                dataType: 'json',
                beforeSend: function() {
                    console.log('DEBUG: Sending delete request for:', filename);
                },
                success: function(response) {
                    console.log('DEBUG: Delete response received:', response);
                    if (response.status === 'success') {
                        alert(response.message); // Or use a less intrusive notification
                        loadFileManagerFiles(); // Refresh gallery
                    } else {
                        alert('Error deleting file: ' + (response.message || 'Unknown server error.'));
                        console.error('DEBUG: Error response from server:', response);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Could not connect to the server to delete the file. Check console for details.');
                    console.error("DEBUG: Delete AJAX Error:", {
                        readyState: xhr.readyState,
                        status: xhr.status,
                        statusText: xhr.statusText,
                        responseText: xhr.responseText,
                        errorThrown: error,
                        ajaxStatus: status
                    });
                }
            });
        } else {
            console.log('DEBUG: Deletion cancelled for:', filename);
        }
    });

    const urlParamsForSelect = new URLSearchParams(window.location.search);
    const isSelectionModeForSelect = urlParamsForSelect.get('selection_mode') === 'true';
    const targetInputIdForSelect = urlParamsForSelect.get('target_input');
    const targetPreviewIdForSelect = urlParamsForSelect.get('target_preview');

    $('body').on('click', '.select-file-main-btn', function() {
        alert('DEBUG: Select button (.select-file-main-btn) clicked!'); // Immediate visual feedback
        console.log('DEBUG: .select-file-main-btn clicked'); // Console feedback

        const fileItem = $(this).closest('.file-item');
        if (!fileItem.length) {
            console.error('DEBUG: .select-file-main-btn - Could not find parent .file-item');
            alert('DEBUG: Error finding file item. Check console.');
            return;
        }

        const fileUrl = fileItem.data('url');
        const fileName = fileItem.data('name'); // Ensure this is consistently set (escaped or raw)

        console.log("Select button clicked. File URL:", fileUrl, "Filename:", fileName);
        console.log("Selection Mode Active:", isSelectionModeForSelect);
        console.log("Target Input ID:", targetInputIdForSelect);
        console.log("Target Preview ID:", targetPreviewIdForSelect);
        console.log("window.opener:", window.opener);

        if (isSelectionModeForSelect && targetInputIdForSelect) {
            if (window.opener && !window.opener.closed) {
                console.log("Opener window found.");
                if (typeof window.opener.handleFileSelectionFromManager === 'function') {
                    console.log("Callback function 'handleFileSelectionFromManager' found on opener. Calling it.");
                    try {
                        // Pass the raw filename if handleFileSelectionFromManager handles escaping,
                        // or ensure fileName is what the parent expects.
                        window.opener.handleFileSelectionFromManager(targetInputIdForSelect, fileUrl, targetPreviewIdForSelect, fileName);
                        window.close();
                    } catch (e) {
                        console.error("Error calling handleFileSelectionFromManager on opener:", e);
                        alert("Error communicating with the parent window. See console for details.");
                    }
                } else {
                    console.error("Callback function 'handleFileSelectionFromManager' NOT FOUND on opener window.", window.opener);
                    alert('Error: Parent window callback function not found. Ensure the main page script is loaded correctly.');
                }
            } else {
                console.error("Opener window is not available or has been closed.");
                alert('Error: Could not connect to the parent window that opened the file manager. It might have been closed or navigated away.');
            }
        } else if (!isSelectionModeForSelect) {
            console.log("Not in selection mode. Attempting to copy URL to clipboard.");
            // ... (copy to clipboard logic) ...
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(fileUrl).then(function() {
                    alert('File URL copied to clipboard (API): ' + fileUrl);
                }, function(err) {
                    console.error('Clipboard API writeText error in .select-file-main-btn:', err);
                    tryCopyExecCommand(fileUrl, ".select-file-main-btn");
                });
            } else {
                console.log("Using execCommand fallback for copy in .select-file-main-btn.");
                tryCopyExecCommand(fileUrl, ".select-file-main-btn");
            }
        } else {
            console.warn("In selection mode, but targetInputId is missing. Cannot select file for form.");
            alert('Selection mode active, but no target input field specified.');
        }
    });

    $('#fileManagerMainGallery').on('click', '.file-card-item', function(e) {
        alert('DEBUG: File card (.file-card-item) clicked for preview!'); // Immediate visual feedback
        console.log('DEBUG: .file-card-item clicked for preview'); // Console feedback

        // Prevent if the click was on a button within the card footer
        if ($(e.target).closest('.card-footer').length > 0) {
            console.log('DEBUG: Click was on card footer button, ignoring for preview.');
            return;
        }
        // Also check if the click was on the select button itself, even if not in footer (though it is)
        if ($(e.target).hasClass('select-file-main-btn') || $(e.target).closest('.select-file-main-btn').length > 0) {
            console.log('DEBUG: Click was on select-file-main-btn, ignoring for preview.');
            return;
        }


        const fileItem = $(this).closest('.file-item'); // .file-item is the parent div with all data attributes
        if (!fileItem.length) {
            console.error('DEBUG: Preview - Could not find parent .file-item for card:', this);
            alert('DEBUG: Error finding file item for preview. Check console.');
            return;
        }

        const fileName = fileItem.data('name');
        const fileUrl = fileItem.data('url');
        const fileType = (fileItem.data('type') || '').toString().toLowerCase(); // Ensure type is a string
        const fileSize = fileItem.data('size');
        const fileModifiedTimestamp = fileItem.data('modified');

        console.log('DEBUG: Preview Data:', { fileName, fileUrl, fileType, fileSize, fileModifiedTimestamp });

        // Target modal elements
        const $modal = $('#filePreviewModal');
        const $modalLabel = $('#filePreviewModalLabel');
        const $previewName = $('#filePreviewName');
        const $previewUrlInput = $('#filePreviewUrl');
        const $previewImage = $('#filePreviewImage');
        const $previewImageContainer = $('#filePreviewImageContainer');
        const $previewIconContainer = $('#filePreviewIconContainer');
        const $previewSize = $('#filePreviewSize');
        const $previewModifiedDate = $('#filePreviewModifiedDate');

        if(!$modal.length || !$modalLabel.length || !$previewName.length || !$previewUrlInput.length || !$previewImage.length || !$previewImageContainer.length || !$previewIconContainer.length || !$previewSize.length || !$previewModifiedDate.length) {
            console.error('DEBUG: One or more modal elements for preview are missing from the DOM.');
            alert('DEBUG: Error - preview modal elements missing. Check console.');
            return;
        }

        $modalLabel.text('Preview: ' + escapeHtml(fileName || 'File')); // Use escapeHtml
        $previewName.text(fileName || 'N/A');
        $previewUrlInput.val(fileUrl || '');

        // Format file size (same logic as before)
        let formattedSize = 'N/A';
        if (fileSize !== undefined) { /* ... (existing formatting logic) ... */
            if (fileSize < 1024) { formattedSize = fileSize + ' Bytes'; }
            else if (fileSize < (1024*1024)) { formattedSize = (fileSize/1024).toFixed(2) + ' KB'; }
            else { formattedSize = (fileSize/(1024*1024)).toFixed(2) + ' MB'; }
        }
        $previewSize.text(formattedSize);

        // Format modified date (same logic as before)
        let formattedDate = 'N/A';
        if (fileModifiedTimestamp) { /* ... (existing formatting logic) ... */
            const date = new Date(fileModifiedTimestamp * 1000);
            formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
        }
        $previewModifiedDate.text(formattedDate);

        if (['jpg', 'jpeg', 'png', 'gif'].includes(fileType)) {
            $previewImage.attr('src', fileUrl).show();
            $previewImageContainer.show();
            $previewIconContainer.hide().html('');
        } else {
            $previewImage.hide().attr('src', '');
            $previewImageContainer.hide();
            let iconClass = 'bi-file-earmark-text-fill text-secondary';
            if (fileType === 'pdf') {
                iconClass = 'bi-file-earmark-pdf-fill text-danger';
            }
            $previewIconContainer.html('<i class="bi ' + iconClass + '"></i>').show();
        }

        $modal.modal('show');
    });

    function tryCopyExecCommand(textToCopy, contextId) {
        console.log("Attempting execCommand copy for context:", contextId, "Text:", textToCopy);
        // Create a temporary textarea element
        var tempTextArea = document.createElement("textarea");
        tempTextArea.style.position = "fixed"; // Prevent scrolling to bottom of page
        tempTextArea.style.top = "0";
        tempTextArea.style.left = "0";
        tempTextArea.style.width = "2em"; // Ensure it's not too big
        tempTextArea.style.height = "2em";
        tempTextArea.style.padding = "0";
        tempTextArea.style.border = "none";
        tempTextArea.style.outline = "none";
        tempTextArea.style.boxShadow = "none";
        tempTextArea.style.background = "transparent";
        tempTextArea.value = textToCopy;
        document.body.appendChild(tempTextArea);
        tempTextArea.focus();
        tempTextArea.select();

        try {
            var successful = document.execCommand('copy');
            var msg = successful ? 'File URL copied to clipboard (Fallback).' : 'Fallback copy command failed.';
            alert(msg + '\nURL: ' + textToCopy); // Corrected newline
            if(contextId === "#copyFilePreviewUrl" && successful){ // Specific feedback for modal button
                 $('#copyFilePreviewUrl').html('<i class="bi bi-clipboard-check-fill"></i> Copied!').removeClass('btn-outline-secondary').addClass('btn-success');
                 setTimeout(() => {
                     $('#copyFilePreviewUrl').html('<i class="bi bi-clipboard-check"></i> Copy').removeClass('btn-success').addClass('btn-outline-secondary');
                 }, 2000);
            }
        } catch (err) {
            console.error('Fallback execCommand copy error for context ' + contextId + ':', err);
            alert('Failed to copy URL using fallback. Please copy manually: ' + textToCopy);
        }
        document.body.removeChild(tempTextArea);
    }

    $('#copyFilePreviewUrl').off('click').on('click', function() {
        const urlInput = document.getElementById('filePreviewUrl');
        const textToCopy = urlInput.value;
        console.log("Copy button in modal clicked for URL:", textToCopy);

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(textToCopy).then(() => {
                $(this).html('<i class="bi bi-clipboard-check-fill"></i> Copied!').removeClass('btn-outline-secondary').addClass('btn-success');
                setTimeout(() => {
                    $(this).html('<i class="bi bi-clipboard-check"></i> Copy').removeClass('btn-success').addClass('btn-outline-secondary');
                }, 2000);
            }, (err) => {
                console.error('Clipboard API writeText error for #copyFilePreviewUrl:', err);
                tryCopyExecCommand(textToCopy, "#copyFilePreviewUrl");
            });
        } else {
            console.log("Using execCommand fallback for copy in #copyFilePreviewUrl.");
            tryCopyExecCommand(textToCopy, "#copyFilePreviewUrl");
        }
    });

    $('#filePreviewModal').on('hidden.bs.modal', function () {
        $('#filePreviewImage').attr('src', '').hide();
        $('#filePreviewIconContainer').hide().html('');
        $('#filePreviewName').text('');
        $('#filePreviewUrl').val('');
        $('#filePreviewModalLabel').text('File Preview');
    });
} // End File Manager Page Specific JS

// Note: escapeHtml was defined inside the FM specific block, it should be fine there or global.
// For this refactor, I'll ensure it's accessible if needed by handleFileSelectionFromManager, or define it globally if it's not.
// The subtask prompt does not ask to move escapeHtml, so it will remain within the FM specific block.
// The handleFileSelectionFromManager does not use escapeHtml.
