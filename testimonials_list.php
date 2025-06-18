<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();
$page_title = 'Testimonials';

include_once INCLUDES_PATH . '/header.php';

$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;
unset($_SESSION['success_message'], $_SESSION['error_message']);

$testimonials_entries = [];
$result = $conn->query("SELECT * FROM testimonials ORDER BY sort_order ASC, id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $testimonials_entries[] = $row;
    }
} else {
    $error_message = "Error fetching testimonials: " . $conn->error;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-lg-12">
            <div class="card shadow">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="card-title mb-0"><i class="bi bi-chat-square-quote-fill me-2"></i> <?php echo htmlspecialchars($page_title); ?></h4>
                        <a href="<?php echo SITE_URL; ?>/testimonials_form.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill me-1"></i> Add Testimonial
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

                    <?php if (empty($testimonials_entries) && !$error_message): ?>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle-fill"></i> No testimonials found. Click "Add Testimonial" to get started.
                        </div>
                    <?php elseif (!empty($testimonials_entries)): ?>
                        <div class="table-responsive mt-3">
                            <table class="table table-hover align-middle" id="testimonialsTable"> <!-- Added ID for sortable JS -->
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 5%;">Sort</th>
                                        <th scope="col" style="width: 10%;">Photo</th>
                                        <th scope="col" style="width: 20%;">Name</th>
                                        <th scope="col" style="width: 20%;">Designation/Org</th>
                                        <th scope="col">Content</th>
                                        <th scope="col" style="width: 8%;">Status</th>
                                        <th scope="col" style="width: 12%;" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="sortableTestimonials"> <!-- Added ID for sortable JS -->
                                    <?php foreach ($testimonials_entries as $entry): ?>
                                        <tr data-id="<?php echo $entry['id']; ?>">
                                            <td class="sort-handle" style="cursor: move;"><i class="bi bi-grip-vertical"></i> <?php echo htmlspecialchars($entry['sort_order']); ?></td>
                                            <td>
                                                <?php if (!empty($entry['photo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($entry['photo']); ?>" alt="<?php echo htmlspecialchars($entry['name']); ?>" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover;">
                                                <?php else: ?>
                                                    <i class="bi bi-person-circle fs-3 text-muted" style="font-size: 60px;"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($entry['name']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($entry['designation']); ?>
                                                <?php if(!empty($entry['designation']) && !empty($entry['organization'])) echo "<br>"; ?>
                                                <small class="text-muted"><?php echo htmlspecialchars($entry['organization']); ?></small>
                                            </td>
                                            <td><?php echo nl2br(htmlspecialchars(substr($entry['content'] ?? '', 0, 150))) . (strlen($entry['content'] ?? '') > 150 ? '...' : ''); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $entry['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($entry['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <a href="<?php echo SITE_URL; ?>/testimonials_form.php?id=<?php echo $entry['id']; ?>" class="btn btn-sm btn-info me-1" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                                <a href="<?php echo SITE_URL; ?>/testimonials_actions.php?action=toggle_status&id=<?php echo $entry['id']; ?>"
                                                   class="btn btn-sm <?php echo $entry['status'] === 'active' ? 'btn-warning' : 'btn-success'; ?> me-1" title="<?php echo $entry['status'] === 'active' ? 'Set Inactive' : 'Set Active'; ?>"
                                                   onclick="return confirm('Toggle status?');"><i class="bi <?php echo $entry['status'] === 'active' ? 'bi-eye-slash-fill' : 'bi-eye-fill'; ?>"></i></a>
                                                <a href="<?php echo SITE_URL; ?>/testimonials_actions.php?action=delete&id=<?php echo $entry['id']; ?>"
                                                   class="btn btn-sm btn-danger" title="Delete"
                                                   onclick="return confirm('Delete this testimonial?');"><i class="bi bi-trash3-fill"></i></a>
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
<?php
// Add jQuery UI if not already included globally, for sortable
// For now, assuming it might be added to footer if needed.
// $page_scripts[] = "https://code.jquery.com/ui/1.13.2/jquery-ui.js"; // Example
include_once INCLUDES_PATH . '/footer.php';
?>
<script>
// Basic Sortable JS (requires jQuery UI)
// $(function() {
//     if ($("#sortableTestimonials").length) {
//         $("#sortableTestimonials").sortable({
//             handle: ".sort-handle",
//             update: function(event, ui) {
//                 var sortedIDs = $(this).sortable("toArray", { attribute: "data-id" });
//                 // AJAX call to update sort order
//                 $.post("testimonials_actions.php", { action: "update_sort_order", sorted_ids: sortedIDs })
//                     .done(function(response) {
//                         // console.log(response);
//                         // Potentially show a success message or reload part of the page
//                         // For simplicity, a page reload or a small notification would work.
//                         // For now, we rely on session message if testimonials_actions.php sets one (but it's set for AJAX)
//                         // A better way for AJAX is to parse JSON response and show a small toast/alert.
//                         try {
//                             var res = JSON.parse(response);
//                             if(res.status === 'success') {
//                                 // Maybe a small temporary success message
//                                 // For now, let's just refresh the sort numbers manually or reload page
//                                 // location.reload(); // Simplest for now if backend sets session message
//                                 alert(res.message); // Or a less intrusive notification
//                             } else {
//                                 alert("Error reordering: " + (res.message || "Unknown error"));
//                             }
//                         } catch (e) {
//                             alert("Error processing server response.");
//                         }
//                     })
//                     .fail(function() {
//                         alert("Failed to send reorder request.");
//                     });
//             }
//         });
//     }
// });
</script>
