<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$page_title = 'About Us Content';

$success_message = '';
$error_message = '';

// Fetch existing about content - there should ideally be one row
// The schema doesn't auto-insert one, so we might need to handle that or assume admin creates it if not found.
// For simplicity, we'll try to fetch, and if not found, the form will be for inserting.
// Or, more robustly, check if exists, if not, use INSERT, else use UPDATE.
// Let's assume an ID of 1 for the single record, similar to business_data, if it exists.
// If it doesn't exist, we'll perform an INSERT.

$about_content_data = null;
$record_exists = false;
$record_id = 1; // Assuming a single, fixed-ID record for simplicity or the first record.

// Try to fetch the first record, whatever its ID.
$query = "SELECT * FROM about_content ORDER BY id LIMIT 1";
$result = $conn->query($query);
if ($result && $result->num_rows > 0) {
    $about_content_data = $result->fetch_assoc();
    $record_id = $about_content_data['id']; // Get the actual ID
    $record_exists = true;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_about_content'])) {
    $about_content = trim($_POST['about_content']);
    $mission = trim($_POST['mission']);
    $vision = trim($_POST['vision']);
    $years_experience = filter_var($_POST['years_experience'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'default' => 0]]);
    $projects_completed = filter_var($_POST['projects_completed'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'default' => 0]]);
    $happy_clients = filter_var($_POST['happy_clients'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'default' => 0]]);
    $team_members = filter_var($_POST['team_members'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'default' => 0]]);

    // Basic validation
    if (empty($about_content)) {
        $error_message = "About Content field is required.";
    } else {
        if ($record_exists) {
            // Update existing record
            $stmt = $conn->prepare("UPDATE about_content SET
                about_content = ?, mission = ?, vision = ?, years_experience = ?,
                projects_completed = ?, happy_clients = ?, team_members = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("sssiissi",
                    $about_content, $mission, $vision, $years_experience,
                    $projects_completed, $happy_clients, $team_members,
                    $record_id
                );
                if ($stmt->execute()) {
                    $success_message = "About content updated successfully!";
                } else {
                    $error_message = "Failed to update about content: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Database statement preparation error (UPDATE): " . $conn->error;
            }
        } else {
            // Insert new record
            $stmt_insert = $conn->prepare("INSERT INTO about_content
                (about_content, mission, vision, years_experience, projects_completed, happy_clients, team_members)
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_insert) {
                $stmt_insert->bind_param("sssiiss",
                    $about_content, $mission, $vision, $years_experience,
                    $projects_completed, $happy_clients, $team_members
                );
                if ($stmt_insert->execute()) {
                    $success_message = "About content saved successfully!";
                    $record_id = $conn->insert_id; // Get the new ID
                    $record_exists = true; // Now it exists
                } else {
                    $error_message = "Failed to save about content: " . $stmt_insert->error;
                }
                $stmt_insert->close();
            } else {
                 $error_message = "Database statement preparation error (INSERT): " . $conn->error;
            }
        }

        // Re-fetch data after successful update/insert to display current values
        if ($success_message) {
            $query_refetch = "SELECT * FROM about_content WHERE id = ?";
            $stmt_refetch = $conn->prepare($query_refetch);
            $stmt_refetch->bind_param("i", $record_id);
            $stmt_refetch->execute();
            $result_refetch = $stmt_refetch->get_result();
            if ($result_refetch && $result_refetch->num_rows > 0) {
                $about_content_data = $result_refetch->fetch_assoc();
            }
            $stmt_refetch->close();
        }
    }
}

// If still no data after potential insert attempt and it failed, $about_content_data remains null.
// If form not submitted yet, and no record exists, $about_content_data is null.
// Initialize with empty strings if null, for form pre-filling.
if (!$about_content_data) {
    $about_content_data = [
        'about_content' => '', 'mission' => '', 'vision' => '',
        'years_experience' => 0, 'projects_completed' => 0,
        'happy_clients' => 0, 'team_members' => 0
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
                        <h4 class="card-title mb-0"><i class="bi bi-file-person-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                        <!-- No 'Back to List' for single record pages usually -->
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

                    <form method="POST" action="about_content_edit.php" class="mt-3">
                        <div class="mb-3">
                            <label for="about_content" class="form-label">Main About Content <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="about_content" name="about_content" rows="8" required><?php echo htmlspecialchars($about_content_data['about_content']); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="mission" class="form-label">Our Mission</label>
                                    <textarea class="form-control" id="mission" name="mission" rows="4"><?php echo htmlspecialchars($about_content_data['mission']); ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="vision" class="form-label">Our Vision</label>
                                    <textarea class="form-control" id="vision" name="vision" rows="4"><?php echo htmlspecialchars($about_content_data['vision']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">
                        <h5 class="mb-3">Company Statistics</h5>

                        <div class="row">
                            <div class="col-md-3 col-6">
                                <div class="mb-3">
                                    <label for="years_experience" class="form-label">Years of Experience</label>
                                    <input type="number" class="form-control" id="years_experience" name="years_experience" value="<?php echo htmlspecialchars($about_content_data['years_experience']); ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="mb-3">
                                    <label for="projects_completed" class="form-label">Projects Completed</label>
                                    <input type="number" class="form-control" id="projects_completed" name="projects_completed" value="<?php echo htmlspecialchars($about_content_data['projects_completed']); ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="mb-3">
                                    <label for="happy_clients" class="form-label">Happy Clients</label>
                                    <input type="number" class="form-control" id="happy_clients" name="happy_clients" value="<?php echo htmlspecialchars($about_content_data['happy_clients']); ?>" min="0">
                                </div>
                            </div>
                            <div class="col-md-3 col-6">
                                <div class="mb-3">
                                    <label for="team_members" class="form-label">Team Members</label>
                                    <input type="number" class="form-control" id="team_members" name="team_members" value="<?php echo htmlspecialchars($about_content_data['team_members']); ?>" min="0">
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 pt-3 border-top">
                            <button type="submit" name="update_about_content" class="btn btn-primary btn-lg">
                                <i class="bi bi-save-fill me-1"></i> Save About Content
                            </button>
                        </div>
                    </form>
                </div> <!-- End card-body -->
            </div> <!-- End card -->
        </div> <!-- End col -->
    </div> <!-- End row -->
</div> <!-- End container-fluid -->

<?php
// No File Manager needed for this module based on schema.
include_once INCLUDES_PATH . '/footer.php';
?>
