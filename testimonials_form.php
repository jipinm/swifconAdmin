<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$entry_data = [
    'id' => null, 'name' => '', 'designation' => '', 'organization' => '',
    'content' => '', 'photo' => '', 'sort_order' => 0, 'status' => 'active'
];
$form_action_label = 'Add New';

if ($id) {
    $page_title = 'Edit Testimonial';
    $form_action_label = 'Edit';
    $stmt = $conn->prepare("SELECT * FROM testimonials WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $entry_data = $result->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Testimonial not found with ID: $id.";
            header('Location: ' . SITE_URL . '/testimonials_list.php');
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error preparing to fetch testimonial: " . $conn->error;
        header('Location: ' . SITE_URL . '/testimonials_list.php');
        exit;
    }
} else {
    $page_title = 'Add New Testimonial';
}

include_once INCLUDES_PATH . '/header.php';
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
                        <a href="<?php echo SITE_URL; ?>/testimonials_list.php" class="btn btn-sm btn-outline-secondary">
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

                    <form action="<?php echo SITE_URL; ?>/testimonials_actions.php" method="POST" class="mt-3">
                        <input type="hidden" name="action" value="add_edit">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($entry_data['id'] ?? ''); ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($entry_data['name']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="photo_url" class="form-label">Photo URL</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="photo_url" name="photo_url" value="<?php echo htmlspecialchars($entry_data['photo'] ?? ''); ?>" placeholder="Click 'Select Image'">
                                        <button class="btn btn-outline-secondary open-file-manager" type="button" data-input-field="#photo_url" data-preview-element="#photo_preview_container">
                                            <i class="bi bi-folder-fill"></i> Select Image
                                        </button>
                                    </div>
                                    <div id="photo_preview_container" class="mt-2" style="<?php echo empty($entry_data['photo']) ? 'display:none;' : ''; ?>">
                                        <img src="<?php echo htmlspecialchars($entry_data['photo'] ?? ''); ?>" alt="Photo Preview" style="max-height: 100px; border: 1px solid #ddd; padding: 5px; border-radius: 50%;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="designation" class="form-label">Designation</label>
                                    <input type="text" class="form-control" id="designation" name="designation" value="<?php echo htmlspecialchars($entry_data['designation']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="organization" class="form-label">Organization</label>
                                    <input type="text" class="form-control" id="organization" name="organization" value="<?php echo htmlspecialchars($entry_data['organization']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Content / Testimonial <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="content" name="content" rows="6" required><?php echo htmlspecialchars($entry_data['content']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($entry_data['sort_order']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" <?php echo ($entry_data['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo ($entry_data['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save-fill me-1"></i> <?php echo $id ? 'Update' : 'Add'; ?> Testimonial
                            </button>
                            <a href="<?php echo SITE_URL; ?>/testimonials_list.php" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
// Include File Manager modal for photo selection
// This is already handled by the new popup mechanism via .open-file-manager buttons
include_once INCLUDES_PATH . '/footer.php';
?>
