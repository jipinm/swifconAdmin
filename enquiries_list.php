<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();
$page_title = 'Form Enquiries';

// Handle status update
if (isset($_GET['action']) && $_GET['action'] === 'update_status' && isset($_GET['id']) && isset($_GET['new_status'])) {
    $enquiry_id = (int)$_GET['id'];
    $new_status = $_GET['new_status'];
    $allowed_statuses = ['new', 'read', 'responded'];

    if (in_array($new_status, $allowed_statuses)) {
        // Optional: Add CSRF token check here for GET based status updates
        $stmt = $conn->prepare("UPDATE form_enquiries SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $new_status, $enquiry_id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Enquiry status updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating status: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error: " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Invalid status value provided.";
    }
    // Redirect to clean URL
    header('Location: ' . SITE_URL . '/enquiries_list.php' . (isset($_GET['filter_status']) ? '?filter_status=' . urlencode($_GET['filter_status']) : ''));
    exit;
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete_enquiry' && isset($_GET['id'])) {
    $enquiry_id_to_delete = (int)$_GET['id'];
    // Optional: CSRF check
    $stmt_delete = $conn->prepare("DELETE FROM form_enquiries WHERE id = ?");
    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $enquiry_id_to_delete);
        if ($stmt_delete->execute()) {
            $_SESSION['success_message'] = "Enquiry deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting enquiry: " . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $_SESSION['error_message'] = "Database error (delete): " . $conn->error;
    }
    header('Location: ' . SITE_URL . '/enquiries_list.php' . (isset($_GET['filter_status']) ? '?filter_status=' . urlencode($_GET['filter_status']) : ''));
    exit;
}


include_once INCLUDES_PATH . '/header.php';

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Filtering
$filter_status = $_GET['filter_status'] ?? '';
$where_clause = "";
if (!empty($filter_status) && in_array($filter_status, ['new', 'read', 'responded'])) {
    $where_clause = "WHERE status = '" . $conn->real_escape_string($filter_status) . "'";
}

$enquiries = [];
$result = $conn->query("SELECT * FROM form_enquiries $where_clause ORDER BY created_at DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $enquiries[] = $row;
    }
} else {
    $error_message = "Error fetching enquiries: " . $conn->error;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <h4 class="card-title mb-0 me-3"><i class="bi bi-envelope-paper-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                        <form method="GET" action="enquiries_list.php" class="row row-cols-lg-auto g-2 align-items-center ms-lg-auto">
                            <div class="col-12">
                                <label for="filter_status" class="visually-hidden">Filter by Status</label>
                                <select class="form-select form-select-sm" id="filter_status" name="filter_status" onchange="this.form.submit()">
                                    <option value="">All Statuses</option>
                                    <option value="new" <?php echo ($filter_status === 'new') ? 'selected' : ''; ?>>New</option>
                                    <option value="read" <?php echo ($filter_status === 'read') ? 'selected' : ''; ?>>Read</option>
                                    <option value="responded" <?php echo ($filter_status === 'responded') ? 'selected' : ''; ?>>Responded</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <hr class="my-2">
                </div>
                <div class="card-body pt-0">
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                    <?php endif; ?>

                    <?php if (empty($enquiries) && !$error_message): ?>
                        <div class="alert alert-info mt-3"><i class="bi bi-info-circle-fill"></i> No enquiries found<?php echo !empty($filter_status) ? ' for the selected status' : ''; ?>.</div>
                    <?php elseif (!empty($enquiries)): ?>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Subject</th>
                                        <th>Date Received</th>
                                        <th>Status</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enquiries as $index => $enquiry): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td><?php echo htmlspecialchars($enquiry['name']); ?></td>
                                            <td><a href="mailto:<?php echo htmlspecialchars($enquiry['email']); ?>"><?php echo htmlspecialchars($enquiry['email']); ?></a></td>
                                            <td><?php echo htmlspecialchars($enquiry['phone'] ?? 'N/A'); ?></td>
                                            <td><?php echo htmlspecialchars($enquiry['subject']); ?></td>
                                            <td><?php echo date("M d, Y h:i A", strtotime($enquiry['created_at'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php
                                                    switch($enquiry['status']) {
                                                        case 'new': echo 'primary'; break;
                                                        case 'read': echo 'info'; break;
                                                        case 'responded': echo 'success'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>"><?php echo ucfirst($enquiry['status']); ?></span>
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-secondary me-1" data-bs-toggle="modal" data-bs-target="#viewEnquiryModal_<?php echo $enquiry['id']; ?>" title="View Enquiry">
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-warning dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status">
                                                        <i class="bi bi-pencil-fill"></i> Status
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="?action=update_status&id=<?php echo $enquiry['id']; ?>&new_status=new<?php echo !empty($filter_status) ? '&filter_status='.$filter_status : ''; ?>">Mark as New</a></li>
                                                        <li><a class="dropdown-item" href="?action=update_status&id=<?php echo $enquiry['id']; ?>&new_status=read<?php echo !empty($filter_status) ? '&filter_status='.$filter_status : ''; ?>">Mark as Read</a></li>
                                                        <li><a class="dropdown-item" href="?action=update_status&id=<?php echo $enquiry['id']; ?>&new_status=responded<?php echo !empty($filter_status) ? '&filter_status='.$filter_status : ''; ?>">Mark as Responded</a></li>
                                                    </ul>
                                                </div>
                                                 <a href="?action=delete_enquiry&id=<?php echo $enquiry['id']; ?><?php echo !empty($filter_status) ? '&filter_status='.$filter_status : ''; ?>"
                                                   class="btn btn-sm btn-danger ms-1" title="Delete Enquiry"
                                                   onclick="return confirm('Are you sure you want to delete this enquiry? This action cannot be undone.');">
                                                    <i class="bi bi-trash3-fill"></i>
                                                </a>
                                            </td>
                                        </tr>

                                        <!-- View Enquiry Modal -->
                                        <div class="modal fade" id="viewEnquiryModal_<?php echo $enquiry['id']; ?>" tabindex="-1" aria-labelledby="viewEnquiryModalLabel_<?php echo $enquiry['id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="viewEnquiryModalLabel_<?php echo $enquiry['id']; ?>">Enquiry: <?php echo htmlspecialchars($enquiry['subject']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($enquiry['name']); ?></p>
                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($enquiry['email']); ?></p>
                                                        <p><strong>Phone:</strong> <?php echo htmlspecialchars($enquiry['phone'] ?? 'N/A'); ?></p>
                                                        <p><strong>Date:</strong> <?php echo date("M d, Y h:i A", strtotime($enquiry['created_at'])); ?></p>
                                                        <p><strong>Status:</strong> <?php echo ucfirst($enquiry['status']); ?></p>
                                                        <hr>
                                                        <p><strong>Message:</strong></p>
                                                        <div style="white-space: pre-wrap; background-color: #f8f9fa; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;"><?php echo nl2br(htmlspecialchars($enquiry['content'])); ?></div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <!-- Optional: Add quick status change from modal footer -->
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
