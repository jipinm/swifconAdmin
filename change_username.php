<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login(); // Ensures user is logged in

$page_title = 'Change Username';
$success_message = '';
$error_message = '';

$current_admin_id = $_SESSION['admin_id'];
$current_admin_username = $_SESSION['admin_username']; // For display or pre-fill if needed

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_username_submit'])) {
    $new_username = trim($_POST['new_username']);

    // Validation
    if (empty($new_username)) {
        $error_message = "New username cannot be empty.";
    } elseif (strlen($new_username) < 3) {
        $error_message = "New username must be at least 3 characters long.";
    } elseif (strlen($new_username) > 50) {
        $error_message = "New username cannot exceed 50 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
        $error_message = "Username can only contain letters, numbers, and underscores.";
    } elseif ($new_username === $current_admin_username) {
        $error_message = "The new username is the same as your current username.";
    } else {
        // Check if new username is already taken by another admin
        $stmt_check = $conn->prepare("SELECT id FROM admins WHERE username = ? AND id != ?");
        if ($stmt_check) {
            $stmt_check->bind_param("si", $new_username, $current_admin_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $error_message = "This username is already taken. Please choose another.";
            } else {
                // Proceed with update
                $stmt_update = $conn->prepare("UPDATE admins SET username = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                if ($stmt_update) {
                    $stmt_update->bind_param("si", $new_username, $current_admin_id);
                    if ($stmt_update->execute()) {
                        $_SESSION['admin_username'] = $new_username; // Update session
                        $current_admin_username = $new_username; // Update for current page display
                        $success_message = "Username changed successfully!";
                        // Consider redirecting to dashboard or showing message on same page
                    } else {
                        $error_message = "Failed to update username: " . $stmt_update->error;
                    }
                    $stmt_update->close();
                } else {
                    $error_message = "Database statement preparation error (UPDATE): " . $conn->error;
                }
            }
            $stmt_check->close();
        } else {
            $error_message = "Database statement preparation error (CHECK): " . $conn->error;
        }
    }
}

include_once INCLUDES_PATH . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center mb-4">
        <div class="col-lg-6 col-md-8">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="bi bi-person-badge me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                        <!-- Optional: Back button if needed, e.g., to dashboard -->
                        <!-- <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-circle-fill me-1"></i> Dashboard</a> -->
                    </div>
                    <hr class="my-2">
                </div>
                <div class="card-body pt-0">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show mt-3" role="alert">
                            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="change_username.php" class="mt-3">
                        <div class="mb-3">
                            <label for="current_username" class="form-label">Current Username</label>
                            <input type="text" class="form-control" id="current_username" value="<?php echo htmlspecialchars($current_admin_username); ?>" readonly disabled>
                        </div>

                        <div class="mb-3">
                            <label for="new_username" class="form-label">New Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="new_username" name="new_username" required autofocus maxlength="50">
                            <small class="form-text text-muted">Min 3 characters. Letters, numbers, and underscores only.</small>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" name="change_username_submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save-fill me-1"></i> Save New Username
                            </button>
                            <a href="<?php echo SITE_URL; ?>/dashboard.php" class="btn btn-secondary btn-lg ms-2">Cancel</a>
                        </div>
                    </form>
                </div> <!-- End card-body -->
            </div> <!-- End card -->
        </div> <!-- End col -->
    </div> <!-- End row -->
</div> <!-- End container-fluid -->

<?php
include_once INCLUDES_PATH . '/footer.php';
?>
