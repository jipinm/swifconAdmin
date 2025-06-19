<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';
require_login();
$page_title = 'Latest Updates';
include_once INCLUDES_PATH . '/header.php';
$success_message = $_SESSION['success_message'] ?? null; $error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$updates = [];
$result = $conn->query("SELECT * FROM latest_updates ORDER BY sort_order ASC, id DESC");
if ($result) { while ($row = $result->fetch_assoc()) { $updates[] = $row; }
} else { $error_message = "Error fetching updates: " . $conn->error; }
?>
<div class="container-fluid py-4">
    <div class="row mb-4"><div class="col-lg-12"><div class="card shadow">
        <div class="card-header pb-0"><div class="d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0"><i class="bi bi-newspaper me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
            <a href="<?php echo SITE_URL; ?>/latest_updates_form.php" class="btn btn-primary"><i class="bi bi-plus-circle-fill me-1"></i> Add New Update</a>
        </div><hr class="my-2"></div>
        <div class="card-body pt-0">
            <?php if ($success_message): ?><div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($error_message): ?><div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <?php if (empty($updates) && !$error_message): ?>
                <div class="alert alert-info mt-3"><i class="bi bi-info-circle-fill"></i> No updates found.</div>
            <?php elseif (!empty($updates)): ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle" id="latestUpdatesTable">
                        <thead class="table-light"><tr><th style="width:5%;">Sort</th><th style="width:10%;">Image</th><th>Title</th><th>Subtitle</th><th style="width:8%;">Visible</th><th class="text-center" style="width:12%;">Actions</th></tr></thead>
                        <tbody id="sortableUpdates">
                        <?php foreach ($updates as $item): ?>
                            <tr data-id="<?php echo $item['id']; ?>">
                                <td class="sort-handle" style="cursor:move;"><i class="bi bi-grip-vertical"></i> <?php echo htmlspecialchars($item['sort_order']); ?></td>
                                <td><img src="<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>" alt="" style="width:80px; height:auto; border-radius:5px;"></td>
                                <td><?php echo htmlspecialchars($item['title']); ?></td>
                                <td><?php echo htmlspecialchars(substr($item['subtitle'] ?? '', 0, 70) . (strlen($item['subtitle'] ?? '') > 70 ? '...' : '')); ?></td>
                                <td><span class="badge bg-<?php echo $item['is_visible'] ? 'success' : 'secondary'; ?>"><?php echo $item['is_visible'] ? 'Yes' : 'No'; ?></span></td>
                                <td class="text-center">
                                    <a href="<?php echo SITE_URL; ?>/latest_updates_form.php?id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="<?php echo SITE_URL; ?>/latest_updates_actions.php?action=toggle_visibility&id=<?php echo $item['id']; ?>" class="btn btn-sm <?php echo $item['is_visible'] ? 'btn-warning' : 'btn-success'; ?> me-1" title="Toggle Visibility" onclick="return confirm('Toggle visibility?');"><i class="bi <?php echo $item['is_visible'] ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i></a>
                                    <a href="<?php echo SITE_URL; ?>/latest_updates_actions.php?action=delete&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete this update?');"><i class="bi bi-trash3-fill"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div></div></div>
</div>
<?php include_once INCLUDES_PATH . '/footer.php'; ?>
<script>
// Placeholder for Sortable JS (requires jQuery UI, similar to testimonials)
// $(function() { if ($("#sortableUpdates").length) { $("#sortableUpdates").sortable({ handle: ".sort-handle", update: function(event, ui) { /* AJAX call to update_sort_order */ } }); } });
</script>
