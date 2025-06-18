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
        targetInputField = $(this).data('input-field');
        targetPreviewElement = $(this).data('preview-element');
        $('#fileManagerModal').modal('show');
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
