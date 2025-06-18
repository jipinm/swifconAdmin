<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$slider_data = [
    'id' => null,
    'title' => '',
    'subtitle' => '',
    'image' => '', // This will be 'image_url' in the form for clarity with File Manager
    'sort_order' => 0,
    'status' => 'active'
];
$page_action_label = 'Add New';

if ($id) {
    $page_title = 'Edit Hero Slider';
    $page_action_label = 'Edit';
    $stmt = $conn->prepare("SELECT * FROM hero_sliders WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $slider_data = $result->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Slider not found with ID: $id.";
            header('Location: ' . SITE_URL . '/hero_sliders.php');
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error preparing to fetch slider: " . $conn->error;
        header('Location: ' . SITE_URL . '/hero_sliders.php');
        exit;
    }
} else {
    $page_title = 'Add New Hero Slider';
}

include_once INCLUDES_PATH . '/header.php';

// Error message from session (e.g., validation errors from hero_slider_actions.php)
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-10 col-xl-8 mx-auto">
            <div class="card shadow">
                 <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="bi <?php echo $id ? 'bi-pencil-square' : 'bi-plus-square-fill'; ?> me-2"></i>
                            <?php echo htmlspecialchars($page_title); ?>
                        </h4>
                        <a href="<?php echo SITE_URL; ?>/hero_sliders.php" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-left-circle-fill me-1"></i> Back to List
                        </a>
                    </div>
                    <hr class="my-2">
                </div>
                <div class="card-body pt-0">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form action="<?php echo SITE_URL; ?>/hero_slider_actions.php" method="POST" class="mt-3">
                        <input type="hidden" name="action" value="add_edit">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($slider_data['id'] ?? ''); ?>">

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($slider_data['title'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="subtitle" class="form-label">Subtitle</label>
                            <textarea class="form-control" id="subtitle" name="subtitle" rows="3"><?php echo htmlspecialchars($slider_data['subtitle'] ?? ''); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="image_url" class="form-label">Image URL <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="image_url" name="image_url" value="<?php echo htmlspecialchars($slider_data['image'] ?? ''); ?>" placeholder="Click 'Select Image' button" required>
                                <button class="btn btn-outline-secondary open-file-manager" type="button" data-input-field="#image_url" data-preview-element="#image_preview_container">
                                    <i class="bi bi-folder-fill"></i> Select Image
                                </button>
                            </div>
                            <div id="image_preview_container" class="mt-2" style="<?php echo empty($slider_data['image']) ? 'display:none;' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($slider_data['image'] ?? ''); ?>" alt="Image Preview" style="max-height: 150px; border: 1px solid #ddd; padding: 5px;">
                            </div>
                            <small class="form-text text-muted">Recommended dimensions: 1920x800 pixels.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($slider_data['sort_order'] ?? 0); ?>">
                                    <small class="form-text text-muted">Lower numbers appear first.</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo ($slider_data['status'] ?? 'active') === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($slider_data['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save-fill me-1"></i> <?php echo $id ? 'Update' : 'Add'; ?> Slider
                            </button>
                            <a href="<?php echo SITE_URL; ?>/hero_sliders.php" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include File Manager Modal for image selection
include_once FILE_MANAGER_PATH . '/file_manager_modal.php';
include_once INCLUDES_PATH . '/footer.php';
?>
