<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$entry_data = [
    'id' => null, 'title' => '', 'subtitle' => '',
    'sort_order' => 0, 'status' => 'active'
];
$form_action_label = 'Add New';

if ($id) {
    $page_title = 'Edit Value Entry';
    $form_action_label = 'Edit';
    $stmt = $conn->prepare("SELECT * FROM our_values WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $entry_data = $result->fetch_assoc();
        } else {
            $_SESSION['error_message'] = "Value entry not found with ID: $id.";
            header('Location: ' . SITE_URL . '/our_values_list.php');
            exit;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error preparing to fetch value entry: " . $conn->error;
        header('Location: ' . SITE_URL . '/our_values_list.php');
        exit;
    }
} else {
    $page_title = 'Add New Value Entry';
}

include_once INCLUDES_PATH . '/header.php';
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['error_message']);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-8 col-xl-6 mx-auto">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0">
                            <i class="bi <?php echo $id ? 'bi-pencil-square' : 'bi-plus-square-fill'; ?> me-2"></i>
                            <?php echo htmlspecialchars($page_title); ?>
                        </h4>
                        <a href="<?php echo SITE_URL; ?>/our_values_list.php" class="btn btn-sm btn-outline-secondary">
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

                    <form action="<?php echo SITE_URL; ?>/our_values_actions.php" method="POST" class="mt-3">
                        <input type="hidden" name="action" value="add_edit">
                        <input type="hidden" name="id" value="<?php echo htmlspecialchars($entry_data['id'] ?? ''); ?>">

                        <div class="mb-3">
                            <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($entry_data['title']); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="subtitle" class="form-label">Subtitle / Description</label>
                            <textarea class="form-control" id="subtitle" name="subtitle" rows="4"><?php echo htmlspecialchars($entry_data['subtitle']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="sort_order" class="form-label">Sort Order</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($entry_data['sort_order']); ?>">
                                    <small class="form-text text-muted">Ascending order. Lower numbers appear first.</small>
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
                                <i class="bi bi-save-fill me-1"></i> <?php echo $id ? 'Update' : 'Add'; ?> Entry
                            </button>
                            <a href="<?php echo SITE_URL; ?>/our_values_list.php" class="btn btn-secondary btn-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once INCLUDES_PATH . '/footer.php'; ?>
