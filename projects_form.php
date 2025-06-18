<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$entry_data = ['id'=>null, 'industry_category_id'=>'', 'location'=>'', 'title'=>'', 'image'=>'', 'project_overview'=>'', 'video_url'=>'', 'sort_order'=>0, 'status'=>'active'];
$project_gallery_images = [];
$industry_categories = [];

// Fetch industry categories for dropdown
$cat_result = $conn->query("SELECT id, category_name FROM industry_categories WHERE status = 'active' ORDER BY category_name ASC");
if ($cat_result) { while($cat_row = $cat_result->fetch_assoc()){ $industry_categories[] = $cat_row; } }

if ($id) {
    $page_title = 'Edit Project';
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id); $stmt->execute(); $result = $stmt->get_result();
        if ($result->num_rows > 0) { $entry_data = $result->fetch_assoc(); }
        else { $_SESSION['error_message'] = "Project not found."; header('Location: '.SITE_URL.'/projects_list.php'); exit; }
        $stmt->close();

        $pg_stmt = $conn->prepare("SELECT * FROM project_gallery WHERE project_id = ? ORDER BY sort_order ASC");
        if($pg_stmt){
            $pg_stmt->bind_param("i", $id); $pg_stmt->execute(); $pg_result = $pg_stmt->get_result();
            while($pg_row = $pg_result->fetch_assoc()){ $project_gallery_images[] = $pg_row; }
            $pg_stmt->close();
        }
    } else { $_SESSION['error_message'] = "DB error."; header('Location: '.SITE_URL.'/projects_list.php'); exit;}
} else { $page_title = 'Add New Project'; }

include_once INCLUDES_PATH . '/header.php';
$error_message = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);
?>
<div class="container-fluid py-4">
    <div class="row mb-4"><div class="col-lg-12"><div class="card shadow">
        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0"><i class="bi <?php echo $id ? 'bi-pencil-square' : 'bi-plus-square-fill'; ?> me-2"></i><?php echo htmlspecialchars($page_title); ?></h4>
            <a href="<?php echo SITE_URL; ?>/projects_list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-circle-fill me-1"></i>Back to List</a>
        </div> <hr class="my-2">
        <div class="card-body pt-0">
            <?php if ($error_message): ?><div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <form action="<?php echo SITE_URL; ?>/projects_actions.php" method="POST" class="mt-3">
                <input type="hidden" name="action" value="add_edit"><input type="hidden" name="id" value="<?php echo htmlspecialchars($entry_data['id'] ?? ''); ?>">

                <div class="row">
                    <div class="col-md-8"><div class="mb-3">
                        <label for="title" class="form-label">Project Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($entry_data['title']); ?>" required>
                    </div></div>
                    <div class="col-md-4"><div class="mb-3">
                        <label for="industry_category_id" class="form-label">Industry Category <span class="text-danger">*</span></label>
                        <select class="form-select" id="industry_category_id" name="industry_category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach($industry_categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($entry_data['industry_category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['category_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div></div>
                </div>
                <div class="row">
                    <div class="col-md-6"><div class="mb-3">
                        <label for="image_url" class="form-label">Main Image URL <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="image_url" name="image_url" value="<?php echo htmlspecialchars($entry_data['image']); ?>" required>
                            <button class="btn btn-outline-secondary open-file-manager" type="button" data-input-field="#image_url" data-preview-element="#image_preview_container"><i class="bi bi-folder-fill"></i></button>
                        </div>
                        <div id="image_preview_container" class="mt-2" style="<?php echo empty($entry_data['image']) ? 'display:none;' : ''; ?>"><img src="<?php echo htmlspecialchars($entry_data['image'] ?? ''); ?>" style="max-height:100px; border:1px solid #ddd; padding:5px;"></div>
                    </div></div>
                    <div class="col-md-6"><div class="mb-3">
                        <label for="location" class="form-label">Location</label>
                        <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($entry_data['location']); ?>">
                    </div></div>
                </div>
                <div class="mb-3">
                    <label for="project_overview" class="form-label">Project Overview</label>
                    <textarea class="form-control" id="project_overview" name="project_overview" rows="5"><?php echo htmlspecialchars($entry_data['project_overview']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="video_url" class="form-label">Video URL (Optional, e.g., YouTube, Vimeo)</label>
                    <input type="url" class="form-control" id="video_url" name="video_url" value="<?php echo htmlspecialchars($entry_data['video_url']); ?>" placeholder="https://www.youtube.com/watch?v=...">
                </div>

                <hr><h5 class="mb-3">Project Gallery Images</h5>
                <div id="project-gallery-container">
                    <?php if (!empty($project_gallery_images)): ?>
                        <?php foreach ($project_gallery_images as $img_idx => $img): ?>
                        <div class="row project-gallery-item mb-3 align-items-center">
                            <div class="col-md-2"><img src="<?php echo htmlspecialchars($img['image_path']); ?>" style="width:100%; height:auto; max-height:70px; object-fit:cover; border-radius:4px;" class="gallery-item-preview"></div>
                            <div class="col-md-6"><input type="text" name="gallery_images[]" id="gallery_image_<?php echo $img_idx;?>_path" class="form-control gallery-image-path-input" value="<?php echo htmlspecialchars($img['image_path']); ?>" readonly placeholder="Select image"></div>
                            <div class="col-md-1"><button class="btn btn-sm btn-outline-primary open-file-manager" type="button" data-input-field="#gallery_image_<?php echo $img_idx;?>_path" data-preview-element="#gallery_image_<?php echo $img_idx;?>_preview_img"><i class="bi bi-folder-fill"></i></button></div>
                            <input type="hidden" id="gallery_image_<?php echo $img_idx;?>_preview_img"> <!-- Dummy target for preview, actual preview is on the <img> -->
                            <div class="col-md-2"><input type="number" name="gallery_image_sort_orders[]" class="form-control" placeholder="Sort" value="<?php echo htmlspecialchars($img['sort_order']); ?>"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-gallery-item"><i class="bi bi-trash"></i></button></div>
                            <input type="hidden" name="gallery_image_ids[]" value="<?php echo htmlspecialchars($img['id']); ?>">
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" id="add-gallery-item" class="btn btn-sm btn-success mb-3"><i class="bi bi-plus-circle"></i> Add Gallery Image</button>

                <div class="row mt-3">
                    <div class="col-md-6 mb-3"><label for="sort_order" class="form-label">Project Sort Order</label><input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($entry_data['sort_order']); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="status" class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active" <?php echo ($entry_data['status'] === 'active') ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo ($entry_data['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option></select></div>
                </div>
                <div class="mt-4 pt-3 border-top"><button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save-fill me-1"></i> <?php echo $id ? 'Update' : 'Add'; ?> Project</button><a href="<?php echo SITE_URL; ?>/projects_list.php" class="btn btn-secondary btn-lg ms-2">Cancel</a></div>
            </form>
        </div>
    </div></div></div>
</div>
<?php include_once INCLUDES_PATH . '/footer.php'; ?>
<script>
$(document).ready(function() {
    var gallerySortCounter = <?php echo count($project_gallery_images) > 0 ? (max(array_column($project_gallery_images, 'sort_order')) + 10) : 10; ?>;
    var galleryItemIndex = <?php echo count($project_gallery_images); ?>;

    $("#add-gallery-item").click(function() {
        galleryItemIndex++;
        var newItemHtml =
            '<div class="row project-gallery-item mb-3 align-items-center">' +
            '  <div class="col-md-2"><img src="" style="width:100%; height:auto; max-height:70px; object-fit:cover; border-radius:4px; display:none;" class="gallery-item-preview"></div>' +
            '  <div class="col-md-6"><input type="text" name="gallery_images[]" id="new_gallery_image_' + galleryItemIndex + '_path" class="form-control gallery-image-path-input" readonly placeholder="Click button to select"></div>' +
            '  <div class="col-md-1"><button class="btn btn-sm btn-outline-primary open-file-manager" type="button" data-input-field="#new_gallery_image_' + galleryItemIndex + '_path" data-preview-element="#new_gallery_image_' + galleryItemIndex + '_preview_img"><i class="bi bi-folder-fill"></i></button></div>' +
            // The preview element for new items will be the img tag itself
            '  <input type="hidden" id="new_gallery_image_' + galleryItemIndex + '_preview_img">' + // Dummy target for preview, actual preview is on the <img>
            '  <div class="col-md-2"><input type="number" name="gallery_image_sort_orders[]" class="form-control" placeholder="Sort" value="' + gallerySortCounter + '"></div>' +
            '  <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-gallery-item"><i class="bi bi-trash"></i></button></div>' +
            '  <input type="hidden" name="gallery_image_ids[]" value="">' +
            '</div>';
        $("#project-gallery-container").append(newItemHtml);
        gallerySortCounter += 10;
    });

    $("#project-gallery-container").on("click", ".remove-gallery-item", function() {
        $(this).closest(".project-gallery-item").remove();
    });

    // Update preview for gallery items when file manager selects a file
    // The global handleFileSelectionFromManager needs to be smart or we need specific listeners
    // For new items, the data-preview-element points to a hidden input.
    // We need to update the actual <img> tag's src in the same .project-gallery-item row.
    // For now, the global function will set the input field's value.
    // We can add a change listener to these inputs to update their sibling image.

    $("#project-gallery-container").on("change", ".gallery-image-path-input", function() {
        var newSrc = $(this).val();
        var $previewImg = $(this).closest(".project-gallery-item").find(".gallery-item-preview");
        if (newSrc) {
            $previewImg.attr("src", newSrc).show();
        } else {
            $previewImg.hide();
        }
    });

});
// Modify global handleFileSelectionFromManager if needed to directly update previews for these dynamic items better
// Or rely on the .gallery-image-path-input change listener above.
// Current handleFileSelectionFromManager:
// function handleFileSelectionFromManager(targetInputId, fileUrl, targetPreviewId, fileName) {
//     $(targetInputId).val(fileUrl).trigger('change'); // Trigger change for our listener above
//     if (targetPreviewId && $(targetPreviewId).is('img')) { $(targetPreviewId).attr('src', fileUrl).show(); }
//     else if (targetPreviewId) { $(targetPreviewId).html('<img src="' + fileUrl + '" class="img-fluid" style="max-height: 150px;">').show(); }
//     if ($('#fileManagerModal').hasClass('show')) { $('#fileManagerModal').modal('hide'); }
// }
// The .trigger('change') on val() should make the above listener work for new gallery items.

</script>
