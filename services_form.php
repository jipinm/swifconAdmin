<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';
require_login();

$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$entry_data = ['id'=>null, 'service_name'=>'', 'image'=>'', 'description'=>'', 'our_expertise'=>'', 'sort_order'=>0, 'status'=>'active'];
$service_offerings = []; $service_benefits = [];

if ($id) {
    $page_title = 'Edit Service';
    $stmt = $conn->prepare("SELECT * FROM services WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id); $stmt->execute(); $result = $stmt->get_result();
        if ($result->num_rows > 0) { $entry_data = $result->fetch_assoc(); }
        else { $_SESSION['error_message'] = "Service not found."; header('Location: '.SITE_URL.'/services_list.php'); exit; }
        $stmt->close();

        // Fetch offerings
        $so_stmt = $conn->prepare("SELECT * FROM service_offerings WHERE service_id = ? ORDER BY sort_order ASC");
        if($so_stmt){ $so_stmt->bind_param("i", $id); $so_stmt->execute(); $so_result = $so_stmt->get_result();
            while($so_row = $so_result->fetch_assoc()){ $service_offerings[] = $so_row; } $so_stmt->close(); }
        // Fetch benefits
        $sb_stmt = $conn->prepare("SELECT * FROM service_benefits WHERE service_id = ? ORDER BY sort_order ASC");
        if($sb_stmt){ $sb_stmt->bind_param("i", $id); $sb_stmt->execute(); $sb_result = $sb_stmt->get_result();
            while($sb_row = $sb_result->fetch_assoc()){ $service_benefits[] = $sb_row; } $sb_stmt->close(); }
    } else { $_SESSION['error_message'] = "DB error."; header('Location: '.SITE_URL.'/services_list.php'); exit;}
} else { $page_title = 'Add New Service'; }

include_once INCLUDES_PATH . '/header.php';
$error_message = $_SESSION['error_message'] ?? null; unset($_SESSION['error_message']);
?>
<div class="container-fluid py-4">
    <div class="row mb-4"><div class="col-lg-12"><div class="card shadow">
        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
            <h4 class="card-title mb-0"><i class="bi <?php echo $id ? 'bi-pencil-square' : 'bi-plus-square-fill'; ?> me-2"></i><?php echo htmlspecialchars($page_title); ?></h4>
            <a href="<?php echo SITE_URL; ?>/services_list.php" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left-circle-fill me-1"></i>Back to List</a>
        </div> <hr class="my-2">
        <div class="card-body pt-0">
            <?php if ($error_message): ?><div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
            <form action="<?php echo SITE_URL; ?>/services_actions.php" method="POST" class="mt-3">
                <input type="hidden" name="action" value="add_edit"><input type="hidden" name="id" value="<?php echo htmlspecialchars($entry_data['id'] ?? ''); ?>">

                <div class="row">
                    <div class="col-md-8"><div class="mb-3">
                        <label for="service_name" class="form-label">Service Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="service_name" name="service_name" value="<?php echo htmlspecialchars($entry_data['service_name']); ?>" required>
                    </div></div>
                    <div class="col-md-4"><div class="mb-3">
                        <label for="image_url" class="form-label">Image URL</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="image_url" name="image_url" value="<?php echo htmlspecialchars($entry_data['image']); ?>">
                            <button class="btn btn-outline-secondary open-file-manager" type="button" data-input-field="#image_url" data-preview-element="#image_preview_container"><i class="bi bi-folder-fill"></i></button>
                        </div>
                        <div id="image_preview_container" class="mt-2" style="<?php echo empty($entry_data['image']) ? 'display:none;' : ''; ?>"><img src="<?php echo htmlspecialchars($entry_data['image'] ?? ''); ?>" style="max-height:100px; border:1px solid #ddd; padding:5px;"></div>
                    </div></div>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($entry_data['description']); ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="our_expertise" class="form-label">Our Expertise</label>
                    <textarea class="form-control" id="our_expertise" name="our_expertise" rows="4"><?php echo htmlspecialchars($entry_data['our_expertise']); ?></textarea>
                </div>

                <!-- Service Offerings -->
                <hr><h5 class="mb-3 mt-4">Service Offerings</h5>
                <div id="service-offerings-container">
                    <?php if (empty($service_offerings)): ?>
                        <div class="row service-offering-item mb-2 align-items-center">
                            <div class="col-md-8"><input type="text" name="offering_texts[]" class="form-control" placeholder="Offering text"></div>
                            <div class="col-md-3"><input type="number" name="offering_sort_orders[]" class="form-control" placeholder="Sort" value="10"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-service-item"><i class="bi bi-trash"></i></button></div>
                            <input type="hidden" name="offering_ids[]" value="">
                        </div>
                    <?php else: foreach ($service_offerings as $offering): ?>
                        <div class="row service-offering-item mb-2 align-items-center">
                            <div class="col-md-8"><input type="text" name="offering_texts[]" class="form-control" value="<?php echo htmlspecialchars($offering['offering_text']); ?>"></div>
                            <div class="col-md-3"><input type="number" name="offering_sort_orders[]" class="form-control" value="<?php echo htmlspecialchars($offering['sort_order']); ?>"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-service-item"><i class="bi bi-trash"></i></button></div>
                            <input type="hidden" name="offering_ids[]" value="<?php echo htmlspecialchars($offering['id']); ?>">
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <button type="button" id="add-service-offering" class="btn btn-sm btn-success mb-3"><i class="bi bi-plus-circle"></i> Add Offering</button>

                <!-- Service Benefits -->
                <hr><h5 class="mb-3 mt-4">Service Benefits</h5>
                <div id="service-benefits-container">
                     <?php if (empty($service_benefits)): ?>
                        <div class="row service-benefit-item mb-2 align-items-center">
                            <div class="col-md-8"><input type="text" name="benefit_texts[]" class="form-control" placeholder="Benefit text"></div>
                            <div class="col-md-3"><input type="number" name="benefit_sort_orders[]" class="form-control" placeholder="Sort" value="10"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-service-item"><i class="bi bi-trash"></i></button></div>
                            <input type="hidden" name="benefit_ids[]" value="">
                        </div>
                    <?php else: foreach ($service_benefits as $benefit): ?>
                        <div class="row service-benefit-item mb-2 align-items-center">
                            <div class="col-md-8"><input type="text" name="benefit_texts[]" class="form-control" value="<?php echo htmlspecialchars($benefit['benefit_text']); ?>"></div>
                            <div class="col-md-3"><input type="number" name="benefit_sort_orders[]" class="form-control" value="<?php echo htmlspecialchars($benefit['sort_order']); ?>"></div>
                            <div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-service-item"><i class="bi bi-trash"></i></button></div>
                            <input type="hidden" name="benefit_ids[]" value="<?php echo htmlspecialchars($benefit['id']); ?>">
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                <button type="button" id="add-service-benefit" class="btn btn-sm btn-success mb-3"><i class="bi bi-plus-circle"></i> Add Benefit</button>

                <div class="row mt-4">
                    <div class="col-md-6 mb-3"><label for="sort_order" class="form-label">Service Sort Order</label><input type="number" class="form-control" id="sort_order" name="sort_order" value="<?php echo htmlspecialchars($entry_data['sort_order']); ?>"></div>
                    <div class="col-md-6 mb-3"><label for="status" class="form-label">Status</label><select class="form-select" id="status" name="status"><option value="active" <?php echo ($entry_data['status'] === 'active') ? 'selected' : ''; ?>>Active</option><option value="inactive" <?php echo ($entry_data['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option></select></div>
                </div>
                <div class="mt-4 pt-3 border-top"><button type="submit" class="btn btn-primary btn-lg"><i class="bi bi-save-fill me-1"></i> <?php echo $id ? 'Update' : 'Add'; ?> Service</button><a href="<?php echo SITE_URL; ?>/services_list.php" class="btn btn-secondary btn-lg ms-2">Cancel</a></div>
            </form>
        </div>
    </div></div></div>
</div>
<?php include_once INCLUDES_PATH . '/footer.php'; ?>
<script>
$(document).ready(function() {
    var offeringSortCounter = <?php echo count($service_offerings) > 0 ? (max(array_column($service_offerings, 'sort_order')) + 10) : 10; ?>;
    $("#add-service-offering").click(function() {
        var html = '<div class="row service-offering-item mb-2 align-items-center">' +
            '<div class="col-md-8"><input type="text" name="offering_texts[]" class="form-control" placeholder="Offering text"></div>' +
            '<div class="col-md-3"><input type="number" name="offering_sort_orders[]" class="form-control" placeholder="Sort" value="' + offeringSortCounter + '"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-service-item"><i class="bi bi-trash"></i></button></div>' +
            '<input type="hidden" name="offering_ids[]" value=""></div>';
        $("#service-offerings-container").append(html);
        offeringSortCounter += 10;
    });

    var benefitSortCounter = <?php echo count($service_benefits) > 0 ? (max(array_column($service_benefits, 'sort_order')) + 10) : 10; ?>;
    $("#add-service-benefit").click(function() {
        var html = '<div class="row service-benefit-item mb-2 align-items-center">' +
            '<div class="col-md-8"><input type="text" name="benefit_texts[]" class="form-control" placeholder="Benefit text"></div>' +
            '<div class="col-md-3"><input type="number" name="benefit_sort_orders[]" class="form-control" placeholder="Sort" value="' + benefitSortCounter + '"></div>' +
            '<div class="col-md-1"><button type="button" class="btn btn-sm btn-danger remove-service-item"><i class="bi bi-trash"></i></button></div>' +
            '<input type="hidden" name="benefit_ids[]" value=""></div>';
        $("#service-benefits-container").append(html);
        benefitSortCounter += 10;
    });

    // Generic remove for offerings and benefits
    $("body").on("click", ".remove-service-item", function() {
        $(this).closest(".row").remove();
    });
});
</script>
