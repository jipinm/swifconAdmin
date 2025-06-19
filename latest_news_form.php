<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$entry_data = ['id'=>null, 'title'=>'', 'subtitle'=>'', 'is_visible'=>1, 'sort_order'=>0];
if ($id) {
    $page_title = 'Edit Latest News';
    $stmt = $conn->prepare("SELECT * FROM latest_news WHERE id = ?");
    if($stmt){ $stmt->bind_param("i", $id); $stmt->execute(); $result = $stmt->get_result();
        if ($result->num_rows > 0) { $entry_data = $result->fetch_assoc(); }
        else { $_SESSION['error_message'] = "News entry not found."; header('Location: '.SITE_URL.'/latest_news_list.php'); exit;}
        $stmt->close();
    } else { $_SESSION['error_message'] = "DB error."; header('Location: '.SITE_URL.'/latest_news_list.php'); exit;}
} else { $page_title = 'Add New Latest News'; }

include_once INCLUDES_PATH . '/header.php';
$error_message = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);
?>
<div class="container-fluid py-4">
    <div class="row mb-4"><div class="col-lg-8 col-xl-6 mx-auto"><div class="card shadow">
        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0"><i class="bi <?php echo $id ? 'bi-pencil-square' : 'bi-plus-square-fill'; ?> me-2"></i><?php echo htmlspecialchars($page_title); ?></h4>
            <a href="<?php echo SITE_URL; ?>/latest_news_list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-circle-fill me-1"></i>Back to List</a>
        </div><hr class="my-2">
        <div class="card-body pt-0">
            <?php if ($error_message): ?><div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <form action="<?php echo SITE_URL; ?>/latest_news_actions.php" method="POST" class="mt-3">
                <input type="hidden" name="action" value="add_edit"><input type="hidden" name="id" value="<?php echo htmlspecialchars($entry_data['id'] ?? ''); ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($entry_data['title']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="subtitle" class="form-label">Subtitle</label>
                    <input type="text" class="form-control" id="subtitle" name="subtitle" value="<?php echo htmlspecialchars($entry_data['subtitle']); ?>">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="sort_order" class="form-label">Sort Order</label>
                        <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($entry_data['sort_order']); ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="is_visible" class="form-label">Visibility</label>
                        <select class="form-select" id="is_visible" name="is_visible">
                            <option value="1" <?php echo ($entry_data['is_visible'] == 1) ? 'selected' : ''; ?>>Visible</option>
                            <option value="0" <?php echo ($entry_data['is_visible'] == 0 && isset($entry_data['id'])) ? 'selected' : ''; ?>>Hidden</option>
                        </select>
                    </div>
                </div>
                <div class="mt-4 pt-3 border-top"><button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save-fill me-1"></i><?php echo $id ? 'Update' : 'Add'; ?> News</button><a href="<?php echo SITE_URL; ?>/latest_news_list.php" class="btn btn-secondary btn-lg ms-2">Cancel</a></div>
            </form>
        </div>
    </div></div></div>
</div>
<?php include_once INCLUDES_PATH . '/footer.php'; ?>
