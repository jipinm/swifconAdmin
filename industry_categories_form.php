<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$entry_data = ['id'=>null, 'category_name'=>'', 'content'=>'', 'image'=>'', 'sort_order'=>0, 'status'=>'active'];
$key_features = []; // To hold associated key features

if ($id) {
    $page_title = 'Edit Industry Category';
    $stmt = $conn->prepare("SELECT * FROM industry_categories WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id); $stmt->execute(); $result = $stmt->get_result();
        if ($result->num_rows > 0) { $entry_data = $result->fetch_assoc(); }
        else { $_SESSION['error_message'] = "Category not found."; header('Location: '.SITE_URL.'/industry_categories_list.php'); exit; }
        $stmt->close();

        // Fetch key features
        $kf_stmt = $conn->prepare("SELECT * FROM category_key_features WHERE category_id = ? ORDER BY sort_order ASC");
        if($kf_stmt){
            $kf_stmt->bind_param("i", $id); $kf_stmt->execute(); $kf_result = $kf_stmt->get_result();
            while($kf_row = $kf_result->fetch_assoc()){ $key_features[] = $kf_row; }
            $kf_stmt->close();
        }
    } else { $_SESSION['error_message'] = "DB error fetching category."; header('Location: '.SITE_URL.'/industry_categories_list.php'); exit;}
} else { $page_title = 'Add New Industry Category'; }

include_once INCLUDES_PATH . '/header.php';
$error_message = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-10 col-xl-8 mx-auto">
            <div class="card shadow">
                <div class="card-header pb-0 d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0"><i class="bi <?php echo $id ? 'bi-pencil-square' : 'bi-plus-square-fill'; ?> me-2"></i><?php echo htmlspecialchars($page_title); ?></h4>
                    <a href="<?php echo SITE_URL; ?>/industry_categories_list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-circle-fill me-1"></i>Back to List</a>
                </div> <hr class="my-2">
                <div class="card-body pt-0">
                    <?php if ($error_message): ?> <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> <?php endif; ?>
                    <form action="<?php echo SITE_URL; ?>/industry_categories_actions.php" method="POST" class="mt-3">
                        <input type="hidden" name="action" value="add_edit"><input type="hidden" name="id" value="<?php echo htmlspecialchars($entry_data['id'] ?? ''); ?>">
                        <div class="mb-3">
                            <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="category_name" name="category_name" value="<?php echo htmlspecialchars($entry_data['category_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="image_url" class="form-label">Image URL</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="image_url" name="image_url" value="<?php echo htmlspecialchars($entry_data['image'] ?? ''); ?>" placeholder="Select Image">
                                <button class="btn btn-outline-secondary open-file-manager" type="button" data-input-field="#image_url" data-preview-element="#image_preview_container"><i class="bi bi-folder-fill"></i> Select Image</button>
                            </div>
                            <div id="image_preview_container" class="mt-2" style="<?php echo empty($entry_data['image']) ? 'display:none;' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($entry_data['image'] ?? ''); ?>" alt="Preview" style="max-height: 100px; border:1px solid #ddd; padding:5px;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="content" class="form-label">Content / Description</label>
                            <textarea class="form-control" id="content" name="content" rows="5"><?php echo htmlspecialchars($entry_data['content']); ?></textarea>
                        </div>

                        <hr><h5 class="mb-3">Key Features</h5>
                        <div id="key-features-container">
                            <?php if (empty($key_features)): ?>
                                <div class="row key-feature-item mb-2 align-items-center">
                                    <div class="col-md-8"><input type="text" name="key_features[]" class="form-control" placeholder="Feature text"></div>
                                    <div class="col-md-3"><input type="number" name="key_feature_sort_orders[]" class="form-control" placeholder="Sort" value="10"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-key-feature"><i class="bi bi-trash"></i></button></div>
                                    <input type="hidden" name="key_feature_ids[]" value="">
                                </div>
                            <?php else: ?>
                                <?php foreach ($key_features as $kf_idx => $kf): ?>
                                <div class="row key-feature-item mb-2 align-items-center">
                                    <div class="col-md-8"><input type="text" name="key_features[]" class="form-control" placeholder="Feature text" value="<?php echo htmlspecialchars($kf['feature_text']); ?>"></div>
                                    <div class="col-md-3"><input type="number" name="key_feature_sort_orders[]" class="form-control" placeholder="Sort" value="<?php echo htmlspecialchars($kf['sort_order']); ?>"></div>
                                    <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-key-feature"><i class="bi bi-trash"></i></button></div>
                                    <input type="hidden" name="key_feature_ids[]" value="<?php echo htmlspecialchars($kf['id']); ?>">
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-key-feature" class="btn btn-sm btn-success mb-3"><i class="bi bi-plus-circle"></i> Add Key Feature</button>

                        <div class="row mt-3">
                            <div class="col-md-6 mb-3"><label for="sort_order" class="form-label">Category Sort Order</label><input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($entry_data['sort_order']); ?>"></div>
                            <div class="col-md-6 mb-3"><label for="status" class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active" <?php echo ($entry_data['status'] === 'active') ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo ($entry_data['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option></select></div>
                        </div>
                        <div class="mt-4 pt-3 border-top"><button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save-fill me-1"></i> <?php echo $id ? 'Update' : 'Add'; ?> Category</button><a href="<?php echo SITE_URL; ?>/industry_categories_list.php" class="btn btn-secondary btn-lg ms-2">Cancel</a></div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once INCLUDES_PATH . '/footer.php'; ?>
<script>
$(document).ready(function() {
    var featureSortCounter = <?php echo count($key_features) > 0 ? (max(array_column($key_features, 'sort_order')) + 10) : 10; ?>;

    $("#add-key-feature").click(function() {
        var newFeatureHtml = '<div class="row key-feature-item mb-2 align-items-center">' +
            '<div class="col-md-8"><input type="text" name="key_features[]" class="form-control" placeholder="Feature text"></div>' +
            '<div class="col-md-3"><input type="number" name="key_feature_sort_orders[]" class="form-control" placeholder="Sort" value="' + featureSortCounter + '"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-key-feature"><i class="bi bi-trash"></i></button></div>' +
            '<input type="hidden" name="key_feature_ids[]" value="">' + // Important: empty ID for new features
            '</div>';
        $("#key-features-container").append(newFeatureHtml);
        featureSortCounter += 10;
    });

    $("#key-features-container").on("click", ".remove-key-feature", function() {
        // Prevent removing the last item if you want at least one feature always present (optional)
        // if ($(".key-feature-item").length > 1) {
            $(this).closest(".key-feature-item").remove();
        // } else {
        //     alert("At least one key feature must be present.");
        // }
    });
});
</script>
