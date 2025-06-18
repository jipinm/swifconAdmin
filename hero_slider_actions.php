<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = SITE_URL . '/hero_sliders.php'; // Default redirect

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_edit') {
    $id = $_POST['id'] ? (int)$_POST['id'] : null;
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $image_url = trim($_POST['image_url']); // From File Manager
    $sort_order = (int)$_POST['sort_order'];
    $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($title) || empty($image_url)) {
        $_SESSION['error_message'] = 'Title and Image URL are required.';
        if ($id) {
            header('Location: ' . SITE_URL . '/hero_slider_form.php?id=' . $id);
        } else {
            header('Location: ' . SITE_URL . '/hero_slider_form.php');
        }
        exit;
    }

    if ($id) { // Update existing slider
        $stmt = $conn->prepare("UPDATE hero_sliders SET title = ?, subtitle = ?, image = ?, sort_order = ?, status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssiis", $title, $subtitle, $image_url, $sort_order, $status, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Hero slider updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating slider: " . $stmt->error;
            }
            $stmt->close();
        } else {
             $_SESSION['error_message'] = "Database error (prepare): " . $conn->error;
        }
    } else { // Add new slider
        $stmt = $conn->prepare("INSERT INTO hero_sliders (title, subtitle, image, sort_order, status) VALUES (?, ?, ?, ?, ?)");
         if ($stmt) {
            $stmt->bind_param("sssis", $title, $subtitle, $image_url, $sort_order, $status);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Hero slider added successfully.";
            } else {
                $_SESSION['error_message'] = "Error adding slider: " . $stmt->error;
            }
            $stmt->close();
        }  else {
             $_SESSION['error_message'] = "Database error (prepare): " . $conn->error;
        }
    }
} elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Optional: Add CSRF token check here for GET based deletion
    $stmt = $conn->prepare("DELETE FROM hero_sliders WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Hero slider deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting slider: " . $stmt->error;
        }
        $stmt->close();
    } else {
         $_SESSION['error_message'] = "Database error (prepare): " . $conn->error;
    }
} elseif ($action === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Fetch current status
    $current_status_res = $conn->query("SELECT status FROM hero_sliders WHERE id = $id");
    if ($current_status_res && $current_status_row = $current_status_res->fetch_assoc()) {
        $new_status = $current_status_row['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE hero_sliders SET status = ? WHERE id = ?");
        if($stmt){
            $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Slider status updated.";
            } else {
                $_SESSION['error_message'] = "Error updating status: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare): " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Slider not found for status toggle.";
    }
} else {
    $_SESSION['error_message'] = 'Invalid action specified for hero sliders.';
}

header('Location: ' . $redirect_url);
exit;
?>
