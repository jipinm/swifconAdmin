<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();
$page_title = 'Our Values';

include_once INCLUDES_PATH . '/header.php';

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$values_entries = []; // Renamed variable to avoid conflict if included elsewhere
$result = $conn->query("SELECT * FROM our_values ORDER BY sort_order ASC, id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $values_entries[] = $row;
    }
} else {
    $error_message = "Error fetching value entries: " . $conn->error;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="bi bi-gem me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                        <a href="<?php echo SITE_URL; ?>/our_values_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill me-1"></i> Add Value Entry
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

                    <?php if (empty($values_entries) && !$error_message): ?>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle-fill"></i> No value entries found. Click "Add Value Entry" to get started.
                        </div>
                    <?php elseif (!empty($values_entries)): ?>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Title</th>
                                        <th scope="col">Subtitle</th>
                                        <th scope="col">Sort Order</th>
                                        <th scope="col">Status</th>
                                        <th scope="col" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($values_entries as $entry): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($entry['title']); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars(substr($entry['subtitle'] ?? '', 0, 100))) . (strlen($entry['subtitle'] ?? '') > 100 ? '...' : ''); ?></td>
                                            <td><?php echo htmlspecialchars($entry['sort_order']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $entry['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($entry['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?php echo SITE_URL; ?>/our_values_form.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-info me-1" title="Edit">
                                                    <i class="bi bi-pencil-square"></i>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>/our_values_actions.php?action=toggle_status&id=<?php echo $entry['id']; ?>"
                                                   class="btn btn-sm <?php echo $entry['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> me-1"
                                                   title="<?php echo $entry['status'] === 'active' ? 'Set Inactive' : 'Set Active'; ?>"
                                                   onclick="return confirm('Are you sure you want to toggle status for this entry?');">
                                                    <i class="bi <?php echo $entry['status'] === 'active' ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i>
                                                </a>
                                                <a href="<?php echo SITE_URL; ?>/our_values_actions.php?action=delete&id=<?php echo $entry['id']; ?>"
                                                   class="btn btn-sm btn-danger" title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this entry? This action cannot be undone.');">
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
<?php include_once INCLUDES_PATH . '/footer.php'; ?>
