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
}

// Wait for the DOM to be fully loaded
$(document).ready(function() {

    // Store the input field that triggered the file manager
    let targetInputField = null;
    let targetPreviewElement = null;

    // Event listener for buttons that trigger the file manager
    $('body').on('click', '.open-file-manager', function() {
        const targetInput = $(this).data('input-field');
        const targetPreview = $(this).data('preview-element');

        let fmUrl = 'file_manager_full.php?selection_mode=true';
        if (targetInput) {
            fmUrl += '&target_input=' + encodeURIComponent(targetInput);
        }
        if (targetPreview) {
            fmUrl += '&target_preview=' + encodeURIComponent(targetPreview);
        }

        const popupWidth = 1200;
        const popupHeight = 750;
        const left = (screen.width / 2) - (popupWidth / 2);
        const top = (screen.height / 2) - (popupHeight / 2);
        const popupFeatures = 'width=' + popupWidth + ',height=' + popupHeight + ',top=' + top + ',left=' + left + ',resizable=yes,scrollbars=yes';

        const fmWindow = window.open(fmUrl, 'FileManagerPopupV2', popupFeatures);

        if (window.focus && fmWindow) {
            fmWindow.focus();
        }
    });


    // JS for OLD #fileManagerModal (if it's still used anywhere, otherwise can be removed)
    $('#fileUploadForm').on('submit', function(e) { /* ... existing old modal upload ... */ });
    $('#selectFileButton').on('click', function() { /* ... existing old modal select ... */ });
    $('#fileManagerModal').on('hidden.bs.modal', function () { /* ... existing old modal reset ... */ });

}); // End $(document).ready


// --- File Manager Full Page Specific JS (V2) ---
$(document).ready(function() {
    if ($('#fmFullGallery').length) {
        console.log("File Manager Full Page JS Initialized.");

        let fmFull_allFiles = [];
        const fmFull_gallery = $('#fmFullGallery');
        const fmFull_loadingMsg = $('#fmFullGalleryLoadingMsg');
        const fmFull_searchInput = $('#fmFullSearchInput');

        const fmFull_urlParams = new URLSearchParams(window.location.search);
        const fmFull_isSelectionMode = fmFull_urlParams.get('selection_mode') === 'true';
        const fmFull_targetInput = fmFull_urlParams.get('target_input');
        const fmFull_targetPreview = fmFull_urlParams.get('target_preview');

        if (fmFull_isSelectionMode) {
            $('#fmFull-upload-tab, #fmFull-upload-pane').hide();
            $('body').addClass('fm-selection-mode'); // For CSS to hide delete buttons etc.
            console.log("File Manager: Popup Selection Mode ACTIVE", {targetInput: fmFull_targetInput, targetPreview: fmFull_targetPreview});
        }

        function fmFull_escapeHtml(unsafe) {
            return unsafe
                 .replace(/&/g, "&amp;")
                 .replace(/</g, "&lt;")
                 .replace(/>/g, "&gt;")
                 .replace(/"/g, "&quot;")
                 .replace(/'/g, "&#039;");
        }

        function fmFull_renderGallery(filesToRender) {
            fmFull_gallery.empty();
            if (filesToRender.length > 0) {
                fmFull_loadingMsg.hide();
                filesToRender.forEach(function(file) {
                    let itemHtml = `
                        <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-4">
                            <div class="card h-100 fm-full-file-card ${fmFull_isSelectionMode ? 'file-card-popup' : ''}"
                                 data-name="${fmFull_escapeHtml(file.name)}"
                                 data-url="${file.url}"
                                 data-type="${file.type.toLowerCase()}"
                                 data-size="${file.size}"
                                 data-modified="${file.modified}"
                                 style="cursor:pointer;"> `;

                    if (['jpg', 'jpeg', 'png', 'gif'].includes(file.type.toLowerCase())) {
                        itemHtml += `<img src="${file.url}" class="card-img-top" alt="${fmFull_escapeHtml(file.name)}" style="height: 120px; object-fit: cover;">`;
                    } else {
                        let iconClass = 'bi-file-earmark-text-fill text-secondary';
                        if (file.type.toLowerCase() === 'pdf') iconClass = 'bi-file-earmark-pdf-fill text-danger';
                        itemHtml += `<div class="text-center p-3" style="height: 120px; display: flex; align-items: center; justify-content: center; font-size: 3rem;">
                                        <i class="bi ${iconClass}"></i>
                                     </div>`;
                    }
                    itemHtml += `
                                <div class="card-body p-2">
                                    <p class="card-text small text-truncate" title="${fmFull_escapeHtml(file.name)}">${fmFull_escapeHtml(file.name)}</p>
                                </div>
                                <div class="card-footer p-1 text-center">`;
                    if (!fmFull_isSelectionMode) { // Only show delete button in full page mode
                        itemHtml += `<button class="btn btn-sm btn-danger fm-full-delete-btn" data-filename="${file.name}" title="Delete ${fmFull_escapeHtml(file.name)}"><i class="bi bi-trash3-fill"></i></button>`;
                    }
                    // Explicit Select button is removed; card click handles selection in popup mode.
                    // If a visual cue is needed for selection mode in footer:
                    // else { itemHtml += `<span class="text-muted small">Click card to select</span>`; }
                    itemHtml += `</div>
                            </div>
                        </div>`;
                    fmFull_gallery.append(itemHtml);
                });
            } else {
                fmFull_gallery.html('<p class="text-center text-muted col-12">No files found matching your criteria.</p>');
                fmFull_loadingMsg.hide();
            }
        }

        function fmFull_loadFiles() {
            fmFull_loadingMsg.show().removeClass('text-danger').text('Loading files...');
            fmFull_searchInput.val('');
            $.ajax({
                url: 'file_manager_actions_v2.php?action=list_files', type: 'GET', dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') { fmFull_allFiles = response.files; fmFull_renderGallery(fmFull_allFiles); }
                    else { fmFull_allFiles = []; fmFull_renderGallery([]); fmFull_loadingMsg.show().addClass('text-danger').text('Error: ' + fmFull_escapeHtml(response.message)); }
                },
                error: function(xhr, st, err) {
                    fmFull_allFiles = []; fmFull_renderGallery([]); console.error("FM Full AJAX Error (list):", {xhr,st,err});
                    fmFull_loadingMsg.show().addClass('text-danger').text('Server error. Check console.');
                }
            });
        }
        fmFull_loadFiles();

        $('#fmFull-gallery-tab').on('shown.bs.tab', function() { fmFull_loadFiles(); });

        fmFull_searchInput.on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            const filtered = fmFull_allFiles.filter(f => f.name.toLowerCase().includes(searchTerm));
            fmFull_renderGallery(filtered);
        });

        $('#fmFullUploadForm').on('submit', function(e) { /* ... (multi-upload logic from subtask 21, using fmFull_ prefixed IDs and fmFull_escapeHtml) ... */
            e.preventDefault();
            const files = $('#fmFullUploadInput')[0].files;
            const $overallStatus = $('#fmFullOverallStatusMessage');
            const $progressArea = $('#fmFullUploadProgressArea');
            const $uploadBtn = $(this).find('button[type="submit"]');

            if (files.length === 0) { $overallStatus.html('<div class="alert alert-warning">Please select files.</div>'); return; }
            $overallStatus.html(''); $progressArea.html('');
            $uploadBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Uploading...');
            let processedCount = 0, successCount = 0;

            Array.from(files).forEach((file, index) => {
                const fileId = 'fmFull-upload-' + index + Date.now();
                $progressArea.append(`<div id="${fileId}" class="mb-2 p-2 border rounded"><div class="d-flex justify-content-between align-items-center"><small class="text-truncate" style="max-width:70%;">${fmFull_escapeHtml(file.name)}</small><small class="status-text text-muted">Waiting...</small></div><div class="progress mt-1" style="height:10px;"><div class="progress-bar" style="width:0%;">0%</div></div></div>`);
                const $itemProg = $('#'+fileId), $itemBar = $itemProg.find('.progress-bar'), $itemStatus = $itemProg.find('.status-text');
                const formData = new FormData(); formData.append('file_to_upload', file);

                $.ajax({
                    url: 'file_manager/upload.php', type: 'POST', data: formData, contentType: false, processData: false,
                    xhr: function() { const x = new window.XMLHttpRequest(); x.upload.addEventListener('progress', evt => { if(evt.lengthComputable){const p=Math.round((evt.loaded/evt.total)*100);$itemBar.width(p+'%').text(p+'%');$itemStatus.text(p+'% Uploaded');}},false); return x;},
                    success: function(r) { if(r.status==='success'){$itemBar.addClass('bg-success');$itemStatus.text('Success!').addClass('text-success');successCount++;}else{$itemBar.addClass('bg-danger');$itemStatus.text('Error: '+fmFull_escapeHtml(r.message)).addClass('text-danger');}},
                    error: function(x) {$itemBar.addClass('bg-danger');$itemStatus.text('Failed: Network/Server Error').addClass('text-danger'); console.error("Upload Error:",x);},
                    complete: function() { processedCount++; if(processedCount===files.length){ $uploadBtn.prop('disabled',false).html('<i class="bi bi-cloud-arrow-up-fill"></i> Start Uploads'); if(successCount>0)fmFull_loadFiles(); $overallStatus.html(`<div class="alert alert-${successCount===files.length?'success':(successCount>0?'warning':'danger')}">${successCount} of ${files.length} files uploaded.</div>`); $('#fmFullUploadInput').val(''); setTimeout(()=>{$progressArea.html('');$overallStatus.html('');},7000);}}
                });
            });
        });

        let fmFull_fileToDelete = '';
        const fmFull_deleteModalEl = document.getElementById('fmFullDeleteConfirmModal');
        const fmFull_deleteModal = fmFull_deleteModalEl ? new bootstrap.Modal(fmFull_deleteModalEl, {
            backdrop: false,
            keyboard: true
        }) : null;

        fmFull_gallery.on('click', '.fm-full-delete-btn', function(e) {
            e.stopPropagation();
            if(fmFull_isSelectionMode) return; // Should not happen if button is not rendered
            fmFull_fileToDelete = $(this).data('filename');
            $('#fmFullFileNameToDeletePlaceholder').text(fmFull_fileToDelete);
            if(fmFull_deleteModal) fmFull_deleteModal.show();
        });

        if(fmFull_deleteModalEl) { // Only bind if modal exists (not in popup mode)
            $('#fmFullConfirmDeleteFileBtn').on('click', function() {
                if (!fmFull_fileToDelete) return;
                const $btn = $(this); $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Deleting...');
                $.ajax({
                    url: 'file_manager_actions_v2.php', type: 'POST', data: {action:'delete_file', filename:fmFull_fileToDelete}, dataType: 'json',
                    success: function(r) { if(r.status==='success'){alert(r.message||'File deleted.');fmFull_loadFiles();}else{alert('Error: '+(r.message||'Could not delete.'));}},
                    error: function(x,s,e){ alert('Server error. Check console.'); console.error("Delete Error:", {xhr:x,status:s,error:e}); },
                    complete: function() { $btn.prop('disabled',false).html('Yes, Delete'); if(fmFull_deleteModal) fmFull_deleteModal.hide(); fmFull_fileToDelete='';}
                });
            });
        }

        const fmFull_previewModalEl = document.getElementById('fmFullFilePreviewModal');
        const fmFull_previewModal = fmFull_previewModalEl ? new bootstrap.Modal(fmFull_previewModalEl, {
            backdrop: false,
            keyboard: true
        }) : null;

        fmFull_gallery.on('click', '.fm-full-file-card', function(e) {
            if ($(e.target).closest('button').length > 0) return; // Click was on a button within card
            e.stopPropagation();
            const cardData = $(this).data();

            if (fmFull_isSelectionMode) {
                console.log("Popup Mode: Card clicked for selection.", cardData);
                if (window.opener && !window.opener.closed && fmFull_targetInput) {
                    if (typeof window.opener.handleFileSelectionFromManager === 'function') {
                        try { window.opener.handleFileSelectionFromManager(fmFull_targetInput, cardData.url, fmFull_targetPreview, cardData.name); window.close(); }
                        catch (err) { console.error("Error calling opener fn:", err); alert("Error selecting file."); }
                    } else { console.error("Opener callback not found."); alert("Error: Parent window function missing."); }
                } else { console.error("Opener invalid or target input missing."); alert("Error: Cannot select file for parent window."); }
            } else { // Full Page Mode: Show preview modal
                console.log("Full Page Mode: Card clicked for preview.", cardData);
                if(!fmFull_previewModal) { console.error("Preview modal not found"); return; }

                $('#fmFullFilePreviewModalLabel').text('Preview: ' + fmFull_escapeHtml(cardData.name));
                $('#fmFullFilePreviewName').text(cardData.name);
                $('#fmFullFilePreviewUrl').val(cardData.url);
                let fS='N/A'; if(cardData.size){if(cardData.size<1024)fS=cardData.size+' B';else if(cardData.size<(1024*1024))fS=(cardData.size/1024).toFixed(2)+' KB';else fS=(cardData.size/(1024*1024)).toFixed(2)+' MB';}$('#fmFullFilePreviewSize').text(fS);
                let fD='N/A';if(cardData.modified){const d=new Date(cardData.modified*1000);fD=d.toLocaleDateString()+' '+d.toLocaleTimeString();}$('#fmFullFilePreviewModifiedDate').text(fD);
                if(['jpg','jpeg','png','gif'].includes(cardData.type)){ $('#fmFullFilePreviewImage').attr('src',cardData.url).show();$('#fmFullFilePreviewImageContainer').show();$('#fmFullFilePreviewIconContainer').hide().html('');}
                else{ $('#fmFullFilePreviewImage').hide().attr('src','');$('#fmFullFilePreviewImageContainer').hide();let iC=cardData.type==='pdf'?'bi-file-earmark-pdf-fill text-danger':'bi-file-earmark-text-fill text-secondary';$('#fmFullFilePreviewIconContainer').html(`<i class="bi ${iC}"></i>`).show();}
                fmFull_previewModal.show();
            }
        });

        if(fmFull_previewModalEl){ // Only bind if modal exists
            $('#fmFullCopyFilePreviewUrl').off('click').on('click', function() {
                const textToCopy = $('#fmFullFilePreviewUrl').val(); const btn = this;
                if(navigator.clipboard && navigator.clipboard.writeText){ navigator.clipboard.writeText(textToCopy).then(() => { $(btn).html('<i class="bi bi-clipboard-check-fill"></i> Copied!').addClass('btn-success'); setTimeout(()=>$(btn).html('<i class="bi bi-clipboard-check"></i> Copy').removeClass('btn-success'),2000);}, () => { fmFull_tryCopyExec(textToCopy, btn); });}
                else { fmFull_tryCopyExec(textToCopy, btn); }
            });
        }
        function fmFull_tryCopyExec(text, btnElement) {
            var ta=document.createElement("textarea");ta.style.cssText='position:fixed;top:0;left:0;opacity:0;';ta.value=text;document.body.appendChild(ta);ta.focus();ta.select();
            try{document.execCommand('copy');$(btnElement).html('<i class="bi bi-clipboard-check-fill"></i> Copied! (FB)').addClass('btn-success');setTimeout(()=>$(btnElement).html('<i class="bi bi-clipboard-check"></i> Copy').removeClass('btn-success'),2000);}catch(e){alert('Copy failed.');}document.body.removeChild(ta);
        }
    }
});
