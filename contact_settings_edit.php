<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$page_title = 'Contact Settings';

$success_message = '';
$error_message = '';

// Fetch existing contact settings. The schema inserts one record by default.
// We'll assume we're always updating that first record.
$contact_settings_data = null;
$record_id = 1; // Default ID from schema.sql for the initial record

$query = "SELECT * FROM contact_settings ORDER BY id LIMIT 1"; // Fetch the first available record
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $contact_settings_data = $result->fetch_assoc();
    $record_id = $contact_settings_data['id']; // Use the actual ID of the fetched record
} else {
    // If no record exists, we'll prepare to insert one with a default ID or let auto-increment handle it.
    // The schema includes an auto-insert, so this case is unlikely unless that row was deleted.
    // For simplicity, if no record, form will allow creating one (which will be an INSERT).
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_contact_settings'])) {
    $office_hours = trim($_POST['office_hours']);
    $google_map_embed = trim($_POST['google_map_embed']);

    // No specific validation other than trimming, as fields are text.

    // Check if a record was fetched initially to determine INSERT vs UPDATE
    $current_check_query = "SELECT id FROM contact_settings WHERE id = ?";
    $stmt_check = $conn->prepare($current_check_query);
    $stmt_check->bind_param("i", $record_id); // Use $record_id which was fetched or defaulted
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();
    $record_exists_for_update = ($check_result->num_rows > 0);
    $stmt_check->close();


    if ($record_exists_for_update) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE contact_settings SET
            office_hours = ?, google_map_embed = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("ssi", $office_hours, $google_map_embed, $record_id);
            if ($stmt->execute()) {
                $success_message = "Contact settings updated successfully!";
            } else {
                $error_message = "Failed to update contact settings: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Database statement preparation error (UPDATE): " . $conn->error;
        }
    } else {
        // Insert new record (if somehow the initial record was deleted or never existed)
        // The schema auto-inserts ID 1, so this path is more of a fallback.
        $stmt_insert = $conn->prepare("INSERT INTO contact_settings (office_hours, google_map_embed) VALUES (?, ?)");
        if ($stmt_insert) {
            $stmt_insert->bind_param("ss", $office_hours, $google_map_embed);
            if ($stmt_insert->execute()) {
                $success_message = "Contact settings saved successfully!";
                $record_id = $conn->insert_id; // Update record_id to the newly inserted one
            } else {
                $error_message = "Failed to save contact settings: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        } else {
             $error_message = "Database statement preparation error (INSERT): " . $conn->error;
        }
    }

    // Re-fetch data after successful update/insert
    if ($success_message) {
        $query_refetch = "SELECT * FROM contact_settings WHERE id = ?";
        $stmt_refetch = $conn->prepare($query_refetch);
        $stmt_refetch->bind_param("i", $record_id);
        $stmt_refetch->execute();
        $result_refetch = $stmt_refetch->get_result();
        if ($result_refetch && $result_refetch->num_rows > 0) {
            $contact_settings_data = $result_refetch->fetch_assoc();
        }
        $stmt_refetch->close();
    }
}

// Initialize with empty strings if still null (e.g. first load and DB is empty and no form submitted)
if (!$contact_settings_data) {
    $contact_settings_data = [
        'office_hours' => '', 'google_map_embed' => ''
    ];
}

include_once INCLUDES_PATH . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-10 col-xl-8 mx-auto">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="bi bi-telephone-inbound-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
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

                    <form method="POST" action="contact_settings_edit.php" class="mt-3">
                        <div class="mb-3">
                            <label for="office_hours" class="form-label">Office Hours</label>
                            <textarea class="form-control" id="office_hours" name="office_hours" rows="4" placeholder="e.g., Monday - Friday: 9:00 AM - 5:00 PM&#10;Saturday: 10:00 AM - 2:00 PM&#10;Sunday: Closed"><?php echo htmlspecialchars($contact_settings_data['office_hours']); ?></textarea>
                            <small class="form-text text-muted">Enter each day/time on a new line if needed.</small>
                        </div>

                        <div class="mb-3">
                            <label for="google_map_embed" class="form-label">Google Map Embed Code</label>
                            <textarea class="form-control" id="google_map_embed" name="google_map_embed" rows="6" placeholder="Paste your Google Maps embed code here (e.g., <iframe src='...'></iframe>)"><?php echo htmlspecialchars($contact_settings_data['google_map_embed']); ?></textarea>
                            <small class="form-text text-muted">Get this from Google Maps > Share > Embed a map.</small>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" name="update_contact_settings" class="btn btn-primary btn-lg">
                                <i class="bi bi-save-fill me-1"></i> Save Contact Settings
                            </button>
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
