<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login(); // Ensures user is logged in

$page_title = 'Change Password';
$success_message = '';
$error_message = '';

$current_admin_id = $_SESSION['admin_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password_submit'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Basic Validations
    if (empty($current_password) || empty($new_password) || empty($confirm_new_password)) {
        $error_message = "All password fields are required.";
    } elseif ($new_password !== $confirm_new_password) {
        $error_message = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 6) { // Example: Minimum length validation
        $error_message = "New password must be at least 6 characters long.";
    } else {
        // Fetch current user's hashed password
        $stmt_fetch = $conn->prepare("SELECT password_hash FROM admins WHERE id = ?");
        if ($stmt_fetch) {
            $stmt_fetch->bind_param("i", $current_admin_id);
            $stmt_fetch->execute();
            $result_fetch = $stmt_fetch->get_result();

            if ($admin_data = $result_fetch->fetch_assoc()) {
                // Verify current password
                if (password_verify($current_password, $admin_data['password_hash'])) {
                    // Current password is correct, proceed to update
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                    $stmt_update = $conn->prepare("UPDATE admins SET password_hash = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    if ($stmt_update) {
                        $stmt_update->bind_param("si", $new_password_hash, $current_admin_id);
                        if ($stmt_update->execute()) {
                            $success_message = "Password changed successfully! Please use your new password for the next login.";
                            // Optional: Force re-login by destroying current session
                            // session_destroy();
                            // header('Location: login_form.php?message=password_changed');
                            // exit;
                        } else {
                            $error_message = "Failed to update password: " . $stmt_update->error;
                        }
                        $stmt_update->close();
                    } else {
                        $error_message = "Database statement preparation error (UPDATE): " . $conn->error;
                    }
                } else {
                    $error_message = "Incorrect current password.";
                }
            } else {
                $error_message = "Could not retrieve current user data."; // Should not happen if logged in
            }
            $stmt_fetch->close();
        } else {
            $error_message = "Database statement preparation error (FETCH): " . $conn->error;
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
                        <h4 class="card-title mb-0"><i class="bi bi-key-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
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

                    <form method="POST" action="change_password.php" class="mt-3" id="changePasswordForm">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>

                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                            <small class="form-text text-muted">Minimum 6 characters.</small>
                        </div>

                        <div class="mb-3">
                            <label for="confirm_new_password" class="form-label">Confirm New Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_new_password" name="confirm_new_password" required minlength="6">
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" name="change_password_submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-save-fill me-1"></i> Change Password
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
// Optional: Add client-side JS for password match check
$page_scripts[] = '<script>
$(document).ready(function() {
    $("#changePasswordForm").submit(function(event) {
        var newPassword = $("#new_password").val();
        var confirmPassword = $("#confirm_new_password").val();
        if (newPassword !== confirmPassword) {
            // Display error message next to confirm password field or use a general alert
            // For simplicity, using existing error message display area if it were dynamic
            // Or add a specific one:
            if(!$("#passwordMatchError").length) {
                 $("#confirm_new_password").parent().append("<div id=\'passwordMatchError\' class=\'text-danger small mt-1\'>New password and confirm password do not match.</div>");
            } else {
                 $("#passwordMatchError").text("New password and confirm password do not match.");
            }
            event.preventDefault(); // Prevent form submission
            return false;
        }
        // Clear error if they match now
        $("#passwordMatchError").remove();
        return true; // Allow submission
    });
    // Clear error on input change
    $("#new_password, #confirm_new_password").on("input", function() {
        $("#passwordMatchError").remove();
    });
});
</script>';

include_once INCLUDES_PATH . '/footer.php';
?>
