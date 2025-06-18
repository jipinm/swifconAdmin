<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = SITE_URL . '/projects_list.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_edit') {
    $id = $_POST['id'] ? (int)$_POST['id'] : null;
    $industry_category_id = (int)$_POST['industry_category_id'];
    $location = trim($_POST['location']);
    $title = trim($_POST['title']);
    $image_url = trim($_POST['image_url']); // Main project image
    $project_overview = trim($_POST['project_overview']);
    $video_url = trim($_POST['video_url']) ? filter_var(trim($_POST['video_url']), FILTER_VALIDATE_URL, FILTER_NULL_ON_FAILURE) : '';
    $sort_order = (int)$_POST['sort_order'];
    $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';

    // Gallery Images
    $gallery_image_paths = $_POST['gallery_images'] ?? []; // Array of image paths
    $gallery_image_ids = $_POST['gallery_image_ids'] ?? []; // Array of existing gallery image IDs
    $gallery_image_sort_orders = $_POST['gallery_image_sort_orders'] ?? [];

    if (empty($title) || empty($industry_category_id) || empty($image_url)) {
        $_SESSION['error_message'] = 'Title, Industry Category, and Main Image are required.';
        header('Location: ' . SITE_URL . ($id ? "/projects_form.php?id=$id" : "/projects_form.php"));
        exit;
    }
    if (trim($_POST['video_url']) && $video_url === null) { // if video_url was provided but invalid
         $_SESSION['error_message'] = 'Invalid Video URL format.';
         header('Location: ' . SITE_URL . ($id ? "/projects_form.php?id=$id" : "/projects_form.php"));
         exit;
    }


    $conn->begin_transaction();
    try {
        if ($id) { // Update Project
            $stmt = $conn->prepare("UPDATE projects SET industry_category_id = ?, location = ?, title = ?, image = ?, project_overview = ?, video_url = ?, sort_order = ?, status = ? WHERE id = ?");
            if (!$stmt) throw new Exception("Prepare failed (project update): " . $conn->error);
            $stmt->bind_param("isssssisi", $industry_category_id, $location, $title, $image_url, $project_overview, $video_url, $sort_order, $status, $id);
            if (!$stmt->execute()) throw new Exception("Execute failed (project update): " . $stmt->error);
            $stmt->close();
            $project_id = $id;
            $_SESSION['success_message'] = "Project updated successfully.";
        } else { // Add New Project
            $stmt = $conn->prepare("INSERT INTO projects (industry_category_id, location, title, image, project_overview, video_url, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) throw new Exception("Prepare failed (project insert): " . $conn->error);
            $stmt->bind_param("isssssis", $industry_category_id, $location, $title, $image_url, $project_overview, $video_url, $sort_order, $status);
            if (!$stmt->execute()) throw new Exception("Execute failed (project insert): " . $stmt->error);
            $project_id = $conn->insert_id;
            $stmt->close();
            $_SESSION['success_message'] = "Project added successfully.";
        }

        // Manage Project Gallery Images
        $existing_gallery_ids = [];
        if ($id) {
            $res_gallery = $conn->query("SELECT id FROM project_gallery WHERE project_id = $project_id");
            while($row = $res_gallery->fetch_assoc()) { $existing_gallery_ids[] = $row['id']; }
        }

        $submitted_gallery_ids_for_update = [];

        foreach ($gallery_image_paths as $index => $image_path_item) {
            $image_path_item = trim($image_path_item);
            if (empty($image_path_item)) continue;

            $gallery_item_id = isset($gallery_image_ids[$index]) && !empty($gallery_image_ids[$index]) ? (int)$gallery_image_ids[$index] : null;
            $gallery_item_sort_order = isset($gallery_image_sort_orders[$index]) ? (int)$gallery_image_sort_orders[$index] : ($index + 1) * 10;

            if ($gallery_item_id && in_array($gallery_item_id, $existing_gallery_ids)) { // Update
                $stmt_pg = $conn->prepare("UPDATE project_gallery SET image_path = ?, sort_order = ? WHERE id = ? AND project_id = ?");
                if (!$stmt_pg) throw new Exception("Prepare failed (PG update): " . $conn->error);
                $stmt_pg->bind_param("siii", $image_path_item, $gallery_item_sort_order, $gallery_item_id, $project_id);
                if (!$stmt_pg->execute()) throw new Exception("Execute failed (PG update): " . $stmt_pg->error);
                $stmt_pg->close();
                $submitted_gallery_ids_for_update[] = $gallery_item_id;
            } else { // Insert
                $stmt_pg = $conn->prepare("INSERT INTO project_gallery (project_id, image_path, sort_order) VALUES (?, ?, ?)");
                if (!$stmt_pg) throw new Exception("Prepare failed (PG insert): " . $conn->error);
                $stmt_pg->bind_param("isi", $project_id, $image_path_item, $gallery_item_sort_order);
                if (!$stmt_pg->execute()) throw new Exception("Execute failed (PG insert): " . $stmt_pg->error);
                $stmt_pg->close();
            }
        }

        if ($id) {
            $gallery_items_to_delete = array_diff($existing_gallery_ids, $submitted_gallery_ids_for_update);
            if (!empty($gallery_items_to_delete)) {
                $delete_ids_str = implode(',', array_map('intval', $gallery_items_to_delete));
                // Consider deleting files from server as well
                if (!$conn->query("DELETE FROM project_gallery WHERE project_id = $project_id AND id IN ($delete_ids_str)")) {
                    throw new Exception("Error deleting gallery items: " . $conn->error);
                }
            }
        }
        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Operation failed: " . $e->getMessage();
    }

} elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $conn->begin_transaction();
    try {
        // Consider deleting files from server (main image and gallery images)
        $conn->query("DELETE FROM project_gallery WHERE project_id = $id"); // ON DELETE CASCADE might handle this
        $conn->query("DELETE FROM projects WHERE id = $id");
        $conn->commit();
        $_SESSION['success_message'] = "Project deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting project: " . $e->getMessage();
    }
} elseif ($action === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // ... (toggle status logic) ...
    $current_status_res = $conn->query("SELECT status FROM projects WHERE id = $id");
    if ($current_status_res && $current_status_row = $current_status_res->fetch_assoc()) {
        $new_status = $current_status_row['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
        if($stmt){ $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) { $_SESSION['success_message'] = "Project status updated.";}
            else { $_SESSION['error_message'] = "Error updating status: " . $stmt->error;}
            $stmt->close();
        } else { $_SESSION['error_message'] = "DB error (toggle status): " . $conn->error;}
    } else { $_SESSION['error_message'] = "Project not found.";}
} else {
    $_SESSION['error_message'] = 'Invalid action for Projects.';
}
header('Location: ' . $redirect_url);
exit;
?>
