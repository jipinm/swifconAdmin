<?php
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();
$page_title = 'Hero Sliders';

include_once INCLUDES_PATH . '/header.php';

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Fetch all sliders
$sliders = [];
$result = $conn->query("SELECT * FROM hero_sliders ORDER BY sort_order ASC, id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $sliders[] = $row;
    }
} else {
    // This error should ideally be logged, not just shown to user if critical
    $error_message = "Error fetching sliders: " . $conn->error;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="bi bi-images me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                        <a href="<?php echo ADMIN_URL; ?>/hero_slider_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill me-1"></i> Add New Slider
                        </a>
                    </div>
                    <hr class="my-2">
                </div>
                <div class="card-body pt-0">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($sliders) && !$error_message): ?>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle-fill"></i> No sliders found. Click "Add New Slider" to get started.
                        </div>
                    <?php elseif (!empty($sliders)): ?>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">#</th>
                                        <th scope="col">Image</th>
                                        <th scope="col">Title</th>
                                        <th scope="col">Subtitle</th>
                                        <th scope="col">Sort Order</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sliders as $index => $slider): ?>
                                        <tr>
                                            <th scope="row"><?php echo $index + 1; ?></th>
                                            <td>
                                                <img src="<?php echo htmlspecialchars($slider['image']); ?>" alt="<?php echo htmlspecialchars($slider['title']); ?>" style="width: 100px; height: auto; border-radius: 5px;">
                                            </td>
                                            <td><?php echo htmlspecialchars($slider['title']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars(substr($slider['subtitle'] ?? '', 0, 50))) . (strlen($slider['subtitle'] ?? '') > 50 ? '...' : ''); ?></td>
                                            <td><?php echo htmlspecialchars($slider['sort_order']); ?></td>
                                            <td>
                                                <?php if ($slider['status'] === 'active'): ?>
                                                    <span class="badge bg-success">Active</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactive</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?php echo ADMIN_URL; ?>/hero_slider_form.php?id=<?php echo $slider['id']; ?>" class="btn btn-sm btn-info me-1" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>/hero_slider_actions.php?action=toggle_status&id=<?php echo $slider['id']; ?>"
                                                   class="btn btn-sm <?php echo $slider['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> me-1"
                                                   title="<?php echo $slider['status'] === 'active' ? 'Set Inactive' : 'Set Active'; ?>"
                                                   onclick="return confirm('Are you sure you want to toggle status for this slider?');">
                                                    <i class="bi <?php echo $slider['status'] === 'active' ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>/hero_slider_actions.php?action=delete&id=<?php echo $slider['id']; ?>"
                                                   class="btn btn-sm btn-danger" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this slider? This action cannot be undone.');">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
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

<?php
include_once INCLUDES_PATH . '/footer.php';
?>
