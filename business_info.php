<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php'; // For $conn

require_login();

$page_title = 'Business Information';

$success_message = '';
$error_message = '';

// Fetch existing business data - there should only be one row
$query = "SELECT * FROM business_data ORDER BY id LIMIT 1";
$result = $conn->query($query);
$business_info = null;

if ($result && $result->num_rows > 0) {
    $business_info = $result->fetch_assoc();
} else {
    $error_message = "Business data not found. Please check database setup. An initial record is expected in the 'business_data' table.";
}


// Handle form submission for updating business info
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_business_info'])) {
    // Sanitize and validate input
    $business_name = trim($_POST['business_name']);
    $address = trim($_POST['address']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone']);
    $header_logo = trim($_POST['header_logo']); // URL from File Manager
    $footer_logo = trim($_POST['footer_logo']); // URL from File Manager
    $instagram_link = trim($_POST['instagram_link']) ? filter_var(trim($_POST['instagram_link']), FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE) : '';
    $facebook_link = trim($_POST['facebook_link']) ? filter_var(trim($_POST['facebook_link']), FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE) : '';
    $twitter_link = trim($_POST['twitter_link']) ? filter_var(trim($_POST['twitter_link']), FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE) : '';
    $web_credits = trim($_POST['web_credits']);
    $copyright_content = trim($_POST['copyright_content']);

    // Basic validation
    if (empty($business_name) || empty($phone)) { // Email is validated by filter_var
        $error_message = "Business Name and Phone are required fields.";
    } elseif (empty($email)) { // Check if email was provided before validation
         $error_message = "Email is a required field.";
    } elseif (!$email && !empty(trim($_POST['email']))) { // Email provided but invalid
        $error_message = "Invalid email format.";
    } elseif (trim($_POST['instagram_link']) && $instagram_link === null) {
        $error_message = "Invalid Instagram URL format.";
    } elseif (trim($_POST['facebook_link']) && $facebook_link === null) {
        $error_message = "Invalid Facebook URL format.";
    } elseif (trim($_POST['twitter_link']) && $twitter_link === null) {
        $error_message = "Invalid Twitter URL format.";
    }
     else {
        // Prepare update statement
        $record_id = $business_info ? $business_info['id'] : 1;

        $stmt = $conn->prepare("UPDATE business_data SET
            header_logo = ?, footer_logo = ?, business_name = ?, address = ?, email = ?, phone = ?,
            instagram_link = ?, facebook_link = ?, twitter_link = ?, web_credits = ?, copyright_content = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");

        if ($stmt) {
            $stmt->bind_param("sssssssssssi",
                $header_logo, $footer_logo, $business_name, $address, $email, $phone,
                $instagram_link, $facebook_link, $twitter_link, $web_credits, $copyright_content,
                $record_id
            );

            if ($stmt->execute()) {
                $success_message = "Business information updated successfully!";
                // Re-fetch data to display updated values
                $result_refetch = $conn->query("SELECT * FROM business_data WHERE id = " . $record_id);
                if ($result_refetch && $result_refetch->num_rows > 0) {
                    $business_info = $result_refetch->fetch_assoc();
                }
            } else {
                $error_message = "Failed to update business information: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Database statement preparation error: " . $conn->error;
        }
    }
}

include_once INCLUDES_PATH . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="bi bi-info-circle-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                    </div>
                     <hr class="my-2">
                </div>
                <div class="card-body pt-0">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($business_info): ?>
                        <form method="POST" action="business_info.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="business_name" class="form-label">Business Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="business_name" name="business_name" value="<?php echo htmlspecialchars($business_info['business_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($business_info['phone'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($business_info['email'] ?? ''); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($business_info['address'] ?? ''); ?></textarea>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3">Logos</h5>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="header_logo" class="form-label">Header Logo URL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="header_logo" name="header_logo" value="<?php echo htmlspecialchars($business_info['header_logo'] ?? ''); ?>" placeholder="Click button to select logo">
                                            <button class="btn btn-outline-secondary open-file-manager" type="button" data-input-field="#header_logo" data-preview-element="#header_logo_preview_container"><i class="bi bi-folder-fill"></i> Select</button>
                                        </div>
                                        <div id="header_logo_preview_container" class="mt-2" style="<?php echo empty($business_info['header_logo']) ? 'display:none;' : ''; ?>">
                                            <img src="<?php echo htmlspecialchars($business_info['header_logo'] ?? ''); ?>" alt="Header Logo Preview" style="max-height: 80px; border: 1px solid #ddd; padding: 5px;">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="footer_logo" class="form-label">Footer Logo URL</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="footer_logo" name="footer_logo" value="<?php echo htmlspecialchars($business_info['footer_logo'] ?? ''); ?>" placeholder="Click button to select logo">
                                            <button class="btn btn-outline-secondary open-file-manager" type="button" data-input-field="#footer_logo" data-preview-element="#footer_logo_preview_container"><i class="bi bi-folder-fill"></i> Select</button>
                                        </div>
                                        <div id="footer_logo_preview_container" class="mt-2" style="<?php echo empty($business_info['footer_logo']) ? 'display:none;' : ''; ?>">
                                            <img src="<?php echo htmlspecialchars($business_info['footer_logo'] ?? ''); ?>" alt="Footer Logo Preview" style="max-height: 80px; border: 1px solid #ddd; padding: 5px;">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3">Social Media Links</h5>
                             <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="instagram_link" class="form-label"><i class="bi bi-instagram"></i> Instagram URL</label>
                                        <input type="url" class="form-control" id="instagram_link" name="instagram_link" value="<?php echo htmlspecialchars($business_info['instagram_link'] ?? ''); ?>" placeholder="https://instagram.com/yourprofile">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="facebook_link" class="form-label"><i class="bi bi-facebook"></i> Facebook URL</label>
                                        <input type="url" class="form-control" id="facebook_link" name="facebook_link" value="<?php echo htmlspecialchars($business_info['facebook_link'] ?? ''); ?>" placeholder="https://facebook.com/yourpage">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="twitter_link" class="form-label"><i class="bi bi-twitter"></i> Twitter URL</label>
                                        <input type="url" class="form-control" id="twitter_link" name="twitter_link" value="<?php echo htmlspecialchars($business_info['twitter_link'] ?? ''); ?>" placeholder="https://twitter.com/yourhandle">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-4">
                            <h5 class="mb-3">Other Information</h5>

                            <div class="mb-3">
                                <label for="web_credits" class="form-label">Website Credits</label>
                                <textarea class="form-control" id="web_credits" name="web_credits" rows="2"><?php echo htmlspecialchars($business_info['web_credits'] ?? ''); ?></textarea>
                                <small class="form-text text-muted">e.g., "Designed by Swifcon | Developed by YourTeam"</small>
                            </div>

                            <div class="mb-3">
                                <label for="copyright_content" class="form-label">Copyright Content</label>
                                <input type="text" class="form-control" id="copyright_content" name="copyright_content" value="<?php echo htmlspecialchars($business_info['copyright_content'] ?? ''); ?>" placeholder="© <?php echo date('Y'); ?> Your Business Name. All rights reserved.">
                            </div>

                            <div class="mt-4 pt-2 border-top">
                                <button type="submit" name="update_business_info" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save-fill"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-circle-fill"></i> Could not load business information. Please ensure the `business_data` table has an initial record as per the `schema.sql` setup. If this is a new setup, one record should have been inserted automatically.
                        </div>
                    <?php endif; ?>
                </div> <!-- End card-body -->
            </div> <!-- End card -->
        </div> <!-- End col -->
    </div> <!-- End row -->
</div> <!-- End container-fluid -->

<?php
// Include the File Manager Modal on this page
include_once FILE_MANAGER_PATH . '/file_manager_modal.php';
include_once INCLUDES_PATH . '/footer.php';
?>
