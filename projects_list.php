<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';
require_login();
$page_title = 'Projects';
include_once INCLUDES_PATH . '/header.php';
$success_message = $_SESSION['success_message'] ?? null; $error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$projects = [];
// Join with industry_categories to display category name
$sql = "SELECT p.*, ic.category_name
        FROM projects p
        LEFT JOIN industry_categories ic ON p.industry_category_id = ic.id
        ORDER BY p.sort_order ASC, p.id DESC";
$result = $conn->query($sql);
if ($result) { while ($row = $result->fetch_assoc()) { $projects[] = $row; }
} else { $error_message = "Error fetching projects: " . $conn->error; }
?>
<div class="container-fluid py-4">
    <div class="row mb-4"><div class="col-lg-12"><div class="card shadow">
        <div class="card-header pb-0">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bi bi-kanban-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                <a href="<?php echo SITE_URL; ?>/projects_form.php" class="btn btn-primary"><i class="bi bi-plus-circle-fill me-1"></i> Add Project</a>
            </div> <hr class="my-2">
        </div>
        <div class="card-body pt-0">
            <?php if ($success_message): ?> <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> <?php endif; ?>
            <?php if ($error_message): ?> <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div> <?php endif; ?>

            <?php if (empty($projects) && !$error_message): ?>
                <div class="alert alert-info mt-3"><i class="bi bi-info-circle-fill"></i> No projects found.</div>
            <?php elseif (!empty($projects)): ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>Image</th><th>Title</th><th>Category</th><th>Location</th><th>Sort</th><th>Status</th><th class="text-center">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($projects as $proj): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($proj['image'] ?? ''); ?>" alt="" style="width: 80px; height: auto; border-radius: 5px;"></td>
                                <td><?php echo htmlspecialchars($proj['title']); ?></td>
                                <td><?php echo htmlspecialchars($proj['category_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($proj['location']); ?></td>
                                <td><?php echo htmlspecialchars($proj['sort_order']); ?></td>
                                <td><span class="badge bg-<?php echo $proj['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($proj['status']); ?></span></td>
                                <td class="text-center">
                                    <a href="<?php echo SITE_URL; ?>/projects_form.php?id=<?php echo $proj['id']; ?>" class="btn btn-sm btn-info me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="<?php echo SITE_URL; ?>/projects_actions.php?action=toggle_status&id=<?php echo $proj['id']; ?>" class="btn btn-sm <?php echo $proj['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> me-1" title="Toggle" onclick="return confirm('Toggle status?');"><i class="bi <?php echo $proj['status'] === 'active' ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i></a>
                                    <a href="<?php echo SITE_URL; ?>/projects_actions.php?action=delete&id=<?php echo $proj['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete project?');"><i class="bi bi-trash3-fill"></i></a>
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
