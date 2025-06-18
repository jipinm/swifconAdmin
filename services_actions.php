<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = SITE_URL . '/services_list.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_edit') {
    $id = $_POST['id'] ? (int)$_POST['id'] : null;
    $service_name = trim($_POST['service_name']);
    $image_url = trim($_POST['image_url']);
    $description = trim($_POST['description']);
    $our_expertise = trim($_POST['our_expertise']);
    $sort_order = (int)$_POST['sort_order'];
    $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';

    // Offerings
    $offering_texts = $_POST['offering_texts'] ?? [];
    $offering_ids = $_POST['offering_ids'] ?? [];
    $offering_sort_orders = $_POST['offering_sort_orders'] ?? [];

    // Benefits
    $benefit_texts = $_POST['benefit_texts'] ?? [];
    $benefit_ids = $_POST['benefit_ids'] ?? [];
    $benefit_sort_orders = $_POST['benefit_sort_orders'] ?? [];

    if (empty($service_name)) {
        $_SESSION['error_message'] = 'Service Name is required.';
        header('Location: ' . SITE_URL . ($id ? "/services_form.php?id=$id" : "/services_form.php"));
        exit;
    }

    $conn->begin_transaction();
    try {
        if ($id) { // Update Service
            $stmt = $conn->prepare("UPDATE services SET service_name = ?, image = ?, description = ?, our_expertise = ?, sort_order = ?, status = ? WHERE id = ?");
            if (!$stmt) throw new Exception("Prepare failed (service update): " . $conn->error);
            $stmt->bind_param("ssssisi", $service_name, $image_url, $description, $our_expertise, $sort_order, $status, $id);
            if (!$stmt->execute()) throw new Exception("Execute failed (service update): " . $stmt->error);
            $stmt->close();
            $service_id = $id;
            $_SESSION['success_message'] = "Service updated successfully.";
        } else { // Add New Service
            $stmt = $conn->prepare("INSERT INTO services (service_name, image, description, our_expertise, sort_order, status) VALUES (?, ?, ?, ?, ?, ?)");
            if (!$stmt) throw new Exception("Prepare failed (service insert): " . $conn->error);
            $stmt->bind_param("ssssis", $service_name, $image_url, $description, $our_expertise, $sort_order, $status);
            if (!$stmt->execute()) throw new Exception("Execute failed (service insert): " . $stmt->error);
            $service_id = $conn->insert_id;
            $stmt->close();
            $_SESSION['success_message'] = "Service added successfully.";
        }

        // Manage Service Offerings
        $existing_offering_ids = [];
        if ($id) {
            $res = $conn->query("SELECT id FROM service_offerings WHERE service_id = $service_id");
            while($row = $res->fetch_assoc()){ $existing_offering_ids[] = $row['id']; }
        }
        $submitted_offering_ids_for_update = [];
        foreach ($offering_texts as $index => $text) {
            $text = trim($text); if (empty($text)) continue;
            $item_id = isset($offering_ids[$index]) && !empty($offering_ids[$index]) ? (int)$offering_ids[$index] : null;
            $item_sort_order = isset($offering_sort_orders[$index]) ? (int)$offering_sort_orders[$index] : ($index+1)*10;
            if ($item_id && in_array($item_id, $existing_offering_ids)) {
                $stmt_item = $conn->prepare("UPDATE service_offerings SET offering_text = ?, sort_order = ? WHERE id = ? AND service_id = ?");
                $stmt_item->bind_param("siii", $text, $item_sort_order, $item_id, $service_id); $stmt_item->execute(); $stmt_item->close();
                $submitted_offering_ids_for_update[] = $item_id;
            } else {
                $stmt_item = $conn->prepare("INSERT INTO service_offerings (service_id, offering_text, sort_order) VALUES (?, ?, ?)");
                $stmt_item->bind_param("isi", $service_id, $text, $item_sort_order); $stmt_item->execute(); $stmt_item->close();
            }
        }
        if ($id) {
            $items_to_delete = array_diff($existing_offering_ids, $submitted_offering_ids_for_update);
            if(!empty($items_to_delete)){ $conn->query("DELETE FROM service_offerings WHERE service_id = $service_id AND id IN (".implode(',', $items_to_delete).")");}
        }

        // Manage Service Benefits (similar logic to offerings)
        $existing_benefit_ids = [];
        if ($id) {
            $res = $conn->query("SELECT id FROM service_benefits WHERE service_id = $service_id");
            while($row = $res->fetch_assoc()){ $existing_benefit_ids[] = $row['id']; }
        }
        $submitted_benefit_ids_for_update = [];
        foreach ($benefit_texts as $index => $text) {
            $text = trim($text); if (empty($text)) continue;
            $item_id = isset($benefit_ids[$index]) && !empty($benefit_ids[$index]) ? (int)$benefit_ids[$index] : null;
            $item_sort_order = isset($benefit_sort_orders[$index]) ? (int)$benefit_sort_orders[$index] : ($index+1)*10;
            if ($item_id && in_array($item_id, $existing_benefit_ids)) {
                $stmt_item = $conn->prepare("UPDATE service_benefits SET benefit_text = ?, sort_order = ? WHERE id = ? AND service_id = ?");
                $stmt_item->bind_param("siii", $text, $item_sort_order, $item_id, $service_id); $stmt_item->execute(); $stmt_item->close();
                $submitted_benefit_ids_for_update[] = $item_id;
            } else {
                $stmt_item = $conn->prepare("INSERT INTO service_benefits (service_id, benefit_text, sort_order) VALUES (?, ?, ?)");
                $stmt_item->bind_param("isi", $service_id, $text, $item_sort_order); $stmt_item->execute(); $stmt_item->close();
            }
        }
        if ($id) {
            $items_to_delete = array_diff($existing_benefit_ids, $submitted_benefit_ids_for_update);
             if(!empty($items_to_delete)){ $conn->query("DELETE FROM service_benefits WHERE service_id = $service_id AND id IN (".implode(',', $items_to_delete).")");}
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
        // ON DELETE CASCADE in schema handles offerings and benefits
        $conn->query("DELETE FROM services WHERE id = $id");
        $conn->commit();
        $_SESSION['success_message'] = "Service deleted successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Error deleting service: " . $e->getMessage();
    }
} elseif ($action === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // ... (toggle status logic) ...
    $current_status_res = $conn->query("SELECT status FROM services WHERE id = $id");
    if ($current_status_res && $current_status_row = $current_status_res->fetch_assoc()) {
        $new_status = $current_status_row['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE services SET status = ? WHERE id = ?");
        if($stmt){ $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) { $_SESSION['success_message'] = "Service status updated.";}
            else { $_SESSION['error_message'] = "Error updating status: " . $stmt->error;}
            $stmt->close();
        } else { $_SESSION['error_message'] = "DB error (toggle status): " . $conn->error;}
    } else { $_SESSION['error_message'] = "Service not found.";}
} else {
    $_SESSION['error_message'] = 'Invalid action for Services.';
}
header('Location: ' . $redirect_url);
exit;
?>
