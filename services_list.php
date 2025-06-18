<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';
require_login();
$page_title = 'Services';
include_once INCLUDES_PATH . '/header.php';
$success_message = $_SESSION['success_message'] ?? null; $error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$services = [];
$sql = "SELECT s.*,
        (SELECT COUNT(*) FROM service_offerings so WHERE so.service_id = s.id) as offerings_count,
        (SELECT COUNT(*) FROM service_benefits sb WHERE sb.service_id = s.id) as benefits_count
        FROM services s
        ORDER BY s.sort_order ASC, s.id DESC";
$result = $conn->query($sql);
if ($result) { while ($row = $result->fetch_assoc()) { $services[] = $row; }
} else { $error_message = "Error fetching services: " . $conn->error; }
?>
<div class="container-fluid py-4">
    <div class="row mb-4"><div class="col-lg-12"><div class="card shadow">
        <div class="card-header pb-0">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0"><i class="bi bi-gear-wide-connected me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                <a href="<?php echo SITE_URL; ?>/services_form.php" class="btn btn-primary"><i class="bi bi-plus-circle-fill me-1"></i> Add Service</a>
            </div> <hr class="my-2">
        </div>
        <div class="card-body pt-0">
            <?php if ($success_message): ?><div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <?php if ($error_message): ?><div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>

            <?php if (empty($services) && !$error_message): ?>
                <div class="alert alert-info mt-3"><i class="bi bi-info-circle-fill"></i> No services found.</div>
            <?php elseif (!empty($services)): ?>
                <div class="table-responsive mt-3">
                    <table class="table table-hover align-middle">
                        <thead class="table-light"><tr><th>Image</th><th>Name</th><th>Offerings</th><th>Benefits</th><th>Sort</th><th>Status</th><th class="text-center">Actions</th></tr></thead>
                        <tbody>
                        <?php foreach ($services as $service): ?>
                            <tr>
                                <td><img src="<?php echo htmlspecialchars($service['image'] ?? ''); ?>" alt="" style="width: 70px; height: auto; border-radius:5px;"></td>
                                <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                <td><?php echo $service['offerings_count']; ?></td>
                                <td><?php echo $service['benefits_count']; ?></td>
                                <td><?php echo htmlspecialchars($service['sort_order']); ?></td>
                                <td><span class="badge bg-<?php echo $service['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($service['status']); ?></span></td>
                                <td class="text-center">
                                    <a href="<?php echo SITE_URL; ?>/services_form.php?id=<?php echo $service['id']; ?>" class="btn btn-sm btn-info me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="<?php echo SITE_URL; ?>/services_actions.php?action=toggle_status&id=<?php echo $service['id']; ?>" class="btn btn-sm <?php echo $service['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> me-1" title="Toggle" onclick="return confirm('Toggle status?');"><i class="bi <?php echo $service['status'] === 'active' ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i></a>
                                    <a href="<?php echo SITE_URL; ?>/services_actions.php?action=delete&id=<?php echo $service['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Delete service?');"><i class="bi bi-trash3-fill"></i></a>
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
