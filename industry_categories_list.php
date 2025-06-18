<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();
$page_title = 'Industry Categories';
include_once INCLUDES_PATH . '/header.php';

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$categories = [];
$result = $conn->query("SELECT * FROM industry_categories ORDER BY sort_order ASC, id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Fetch key features count for display
        $kf_res = $conn->query("SELECT COUNT(*) as count FROM category_key_features WHERE category_id = " . $row['id']);
        $row['key_features_count'] = $kf_res->fetch_assoc()['count'] ?? 0;
        $categories[] = $row;
    }
} else {
    $error_message = "Error fetching categories: " . $conn->error;
}
?>
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="bi bi-tags-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                        <a href="<?php echo SITE_URL; ?>/industry_categories_form.php" class="btn btn-primary"><i class="bi bi-plus-circle-fill me-1"></i> Add Category</a>
                    </div> <hr class="my-2">
                </div>
                <div class="card-body pt-0">
                    <?php if ($success_message): ?> <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> <?php endif; ?>
                    <?php if ($error_message): ?> <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> <?php endif; ?>

                    <?php if (empty($categories) && !$error_message): ?>
                        <div class="alert alert-info mt-3"><i class="bi bi-info-circle-fill"></i> No categories found.</div>
                    <?php elseif (!empty($categories)): ?>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover align-middle">
                                <thead class="table-light"><tr><th>Image</th><th>Name</th><th>Key Features</th><th>Sort Order</th><th>Status</th><th class="text-center">Actions</th></tr></thead>
                                <tbody>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><img src="<?php echo htmlspecialchars($cat['image'] ?? ''); ?>" alt="" style="width: 70px; height: auto; border-radius: 5px;"></td>
                                        <td><?php echo htmlspecialchars($cat['category_name']); ?></td>
                                        <td><?php echo $cat['key_features_count']; ?></td>
                                        <td><?php echo htmlspecialchars($cat['sort_order']); ?></td>
                                        <td><span class="badge bg-<?php echo $cat['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($cat['status']); ?></span></td>
                                        <td class="text-center">
                                            <a href="<?php echo SITE_URL; ?>/industry_categories_form.php?id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-info me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                            <a href="<?php echo SITE_URL; ?>/industry_categories_actions.php?action=toggle_status&id=<?php echo $cat['id']; ?>" class="btn btn-sm <?php echo $cat['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> me-1" title="Toggle Status" onclick="return confirm('Toggle status?');"><i class="bi <?php echo $cat['status'] === 'active' ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i></a>
                                            <a href="<?php echo SITE_URL; ?>/industry_categories_actions.php?action=delete&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this category and its features?');"><i class="bi bi-trash3-fill"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include_once INCLUDES_PATH . '/footer.php'; ?>
