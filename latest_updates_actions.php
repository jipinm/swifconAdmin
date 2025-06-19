<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = SITE_URL . '/latest_updates_list.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_edit') {
    $id = $_POST['id'] ? (int)$_POST['id'] : null;
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $image_url = trim($_POST['image_url']);
    $is_visible = isset($_POST['is_visible']) ? 1 : 0;
    $sort_order = (int)$_POST['sort_order'];

    if (empty($title) || empty($image_url)) {
        $_SESSION['error_message'] = 'Title and Image URL are required.';
        header('Location: ' . SITE_URL . ($id ? "/latest_updates_form.php?id=$id" : "/latest_updates_form.php"));
        exit;
    }

    if ($id) { // Update
        $stmt = $conn->prepare("UPDATE latest_updates SET title = ?, subtitle = ?, image_url = ?, is_visible = ?, sort_order = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssiii", $title, $subtitle, $image_url, $is_visible, $sort_order, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Latest update entry updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating entry: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare update): " . $conn->error;
        }
    } else { // Add new
        $stmt = $conn->prepare("INSERT INTO latest_updates (title, subtitle, image_url, is_visible, sort_order) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssii", $title, $subtitle, $image_url, $is_visible, $sort_order);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Latest update entry added successfully.";
            } else {
                $_SESSION['error_message'] = "Error adding entry: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare insert): " . $conn->error;
        }
    }
} elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Consider deleting image from server if stored locally and not used elsewhere
    $stmt = $conn->prepare("DELETE FROM latest_updates WHERE id = ?");
    if($stmt){ $stmt->bind_param("i", $id);
        if ($stmt->execute()) { $_SESSION['success_message'] = "Entry deleted successfully."; }
        else { $_SESSION['error_message'] = "Error deleting entry: " . $stmt->error; }
        $stmt->close();
    } else { $_SESSION['error_message'] = "Database error (delete): " . $conn->error; }
} elseif ($action === 'toggle_visibility' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Fetch current visibility
    $current_res = $conn->query("SELECT is_visible FROM latest_updates WHERE id = $id");
    if ($current_res && $current_row = $current_res->fetch_assoc()) {
        $new_visibility = $current_row['is_visible'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE latest_updates SET is_visible = ? WHERE id = ?");
        if($stmt){ $stmt->bind_param("ii", $new_visibility, $id);
            if ($stmt->execute()) { $_SESSION['success_message'] = "Visibility updated."; }
            else { $_SESSION['error_message'] = "Error updating visibility: " . $stmt->error; }
            $stmt->close();
        } else { $_SESSION['error_message'] = "Database error (toggle visibility): " . $conn->error; }
    } else { $_SESSION['error_message'] = "Entry not found for visibility toggle."; }
} elseif ($action === 'update_sort_order' && isset($_POST['sorted_ids'])) {
    // AJAX action for drag-and-drop reordering
    $sorted_ids = $_POST['sorted_ids'];
    if (is_array($sorted_ids)) {
        $conn->begin_transaction();
        try {
            foreach ($sorted_ids as $index => $id) {
                $sort_order_val = $index + 1;
                $stmt = $conn->prepare("UPDATE latest_updates SET sort_order = ? WHERE id = ?");
                if(!$stmt) throw new Exception("Prepare failed: ".$conn->error);
                $stmt->bind_param("ii", $sort_order_val, $id);
                if(!$stmt->execute()) throw new Exception("Execute failed: ".$stmt->error);
                $stmt->close();
            }
            $conn->commit();
            echo json_encode(['status' => 'success', 'message' => 'Sort order updated.']);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['status' => 'error', 'message' => "Error reordering: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => "Invalid data for reordering."]);
    }
    exit; // AJAX handlers should exit.
} else {
    $_SESSION['error_message'] = 'Invalid action for Latest Updates.';
}
header('Location: ' . $redirect_url);
exit;
?>
