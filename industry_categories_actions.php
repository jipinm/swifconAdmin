<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = SITE_URL . '/industry_categories_list.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_edit') {
    $id = $_POST['id'] ? (int)$_POST['id'] : null;
    $category_name = trim($_POST['category_name']);
    $content = trim($_POST['content']);
    $image_url = trim($_POST['image_url']); // From File Manager
    $sort_order = (int)$_POST['sort_order'];
    $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';

    // Key features - expected as an array
    $key_features_text = $_POST['key_features'] ?? []; // Array of feature texts
    $key_feature_ids = $_POST['key_feature_ids'] ?? []; // Array of existing feature IDs (for updates)
    $key_feature_sort_orders = $_POST['key_feature_sort_orders'] ?? []; // Array of sort orders for features


    if (empty($category_name)) {
        $_SESSION['error_message'] = 'Category Name is required.';
        // Redirect back to form with data if possible (more complex, for now just redirect)
        if ($id) {
            header('Location: ' . SITE_URL . '/industry_categories_form.php?id=' . $id);
        } else {
            header('Location: ' . SITE_URL . '/industry_categories_form.php');
        }
        exit;
    }

    $conn->begin_transaction();
    try {
        if ($id) { // Update Category
            $stmt = $conn->prepare("UPDATE industry_categories SET category_name = ?, content = ?, image = ?, sort_order = ?, status = ? WHERE id = ?");
            if (!$stmt) throw new Exception("Prepare failed (category update): " . $conn->error);
            $stmt->bind_param("sssisi", $category_name, $content, $image_url, $sort_order, $status, $id);
            if (!$stmt->execute()) throw new Exception("Execute failed (category update): " . $stmt->error);
            $stmt->close();
            $category_id = $id;
            $_SESSION['success_message'] = "Industry category updated successfully.";

        } else { // Add New Category
            $stmt = $conn->prepare("INSERT INTO industry_categories (category_name, content, image, sort_order, status) VALUES (?, ?, ?, ?, ?)");
            if (!$stmt) throw new Exception("Prepare failed (category insert): " . $conn->error);
            $stmt->bind_param("sssis", $category_name, $content, $image_url, $sort_order, $status);
            if (!$stmt->execute()) throw new Exception("Execute failed (category insert): " . $stmt->error);
            $category_id = $conn->insert_id;
            $stmt->close();
            $_SESSION['success_message'] = "Industry category added successfully.";
        }

        // Manage Key Features
        // 1. Get existing feature IDs for this category to find out which ones to delete
        $existing_db_feature_ids = [];
        if ($id) { // Only if updating an existing category
            $res_existing_features = $conn->query("SELECT id FROM category_key_features WHERE category_id = $category_id");
            while($row = $res_existing_features->fetch_assoc()) {
                $existing_db_feature_ids[] = $row['id'];
            }
        }

        $submitted_feature_ids_for_update = []; // Keep track of IDs submitted that are for existing features

        foreach ($key_features_text as $index => $feature_text_item) {
            $feature_text_item = trim($feature_text_item);
            if (empty($feature_text_item)) continue; // Skip empty feature texts

            $feature_id = isset($key_feature_ids[$index]) && !empty($key_feature_ids[$index]) ? (int)$key_feature_ids[$index] : null;
            $feature_sort_order = isset($key_feature_sort_orders[$index]) ? (int)$key_feature_sort_orders[$index] : ($index + 1) * 10;


            if ($feature_id && in_array($feature_id, $existing_db_feature_ids)) { // Update existing feature
                $stmt_kf = $conn->prepare("UPDATE category_key_features SET feature_text = ?, sort_order = ? WHERE id = ? AND category_id = ?");
                if (!$stmt_kf) throw new Exception("Prepare failed (KF update): " . $conn->error);
                $stmt_kf->bind_param("siii", $feature_text_item, $feature_sort_order, $feature_id, $category_id);
                if (!$stmt_kf->execute()) throw new Exception("Execute failed (KF update): " . $stmt_kf->error);
                $stmt_kf->close();
                $submitted_feature_ids_for_update[] = $feature_id;
            } else { // Insert new feature
                $stmt_kf = $conn->prepare("INSERT INTO category_key_features (category_id, feature_text, sort_order) VALUES (?, ?, ?)");
                if (!$stmt_kf) throw new Exception("Prepare failed (KF insert): " . $conn->error);
                $stmt_kf->bind_param("isi", $category_id, $feature_text_item, $feature_sort_order);
                if (!$stmt_kf->execute()) throw new Exception("Execute failed (KF insert): " . $stmt_kf->error);
                $stmt_kf->close();
            }
        }

        // Delete features that were removed from the form
        if ($id) { // Only for existing categories
            $features_to_delete = array_diff($existing_db_feature_ids, $submitted_feature_ids_for_update);
            if (!empty($features_to_delete)) {
                $delete_ids_str = implode(',', array_map('intval', $features_to_delete));
                $delete_stmt = "DELETE FROM category_key_features WHERE category_id = $category_id AND id IN ($delete_ids_str)";
                if (!$conn->query($delete_stmt)) {
                    throw new Exception("Error deleting key features: " . $conn->error);
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
        // First delete associated key features (due to FOREIGN KEY ON DELETE CASCADE, this might be optional if DB handles it)
        // For explicit control:
        $stmt_kf_del = $conn->prepare("DELETE FROM category_key_features WHERE category_id = ?");
        if (!$stmt_kf_del) throw new Exception("Prepare failed (KF delete): " . $conn->error);
        $stmt_kf_del->bind_param("i", $id);
        if (!$stmt_kf_del->execute()) throw new Exception("Execute failed (KF delete): " . $stmt_kf_del->error);
        $stmt_kf_del->close();

        // Then delete the category
        $stmt = $conn->prepare("DELETE FROM industry_categories WHERE id = ?");
        if (!$stmt) throw new Exception("Prepare failed (category delete): " . $conn->error);
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) throw new Exception("Execute failed (category delete): " . $stmt->error);
        $stmt->close();

        $conn->commit();
        $_SESSION['success_message'] = "Industry category and its key features deleted successfully.";

    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting category: " . $e->getMessage();
    }

} elseif ($action === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // ... (toggle status logic similar to other modules) ...
    $current_status_res = $conn->query("SELECT status FROM industry_categories WHERE id = $id");
    if ($current_status_res && $current_status_row = $current_status_res->fetch_assoc()) {
        $new_status = $current_status_row['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE industry_categories SET status = ? WHERE id = ?");
        if($stmt){
            $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Category status updated.";
            } else {
                $_SESSION['error_message'] = "Error updating status: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare toggle status): " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Category not found for status toggle.";
    }
} else {
    $_SESSION['error_message'] = 'Invalid action specified for Industry Categories.';
}

header('Location: ' . $redirect_url);
exit;
?>
