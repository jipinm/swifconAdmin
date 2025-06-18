<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = SITE_URL . '/testimonials_list.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_edit') {
    $id = $_POST['id'] ? (int)$_POST['id'] : null;
    $name = trim($_POST['name']);
    $designation = trim($_POST['designation']);
    $organization = trim($_POST['organization']);
    $content = trim($_POST['content']);
    $photo_url = trim($_POST['photo_url']); // From File Manager
    $sort_order = (int)$_POST['sort_order'];
    $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($name) || empty($content)) {
        $_SESSION['error_message'] = 'Name and Content are required.';
        if ($id) {
            header('Location: ' . SITE_URL . '/testimonials_form.php?id=' . $id);
        } else {
            header('Location: ' . SITE_URL . '/testimonials_form.php');
        }
        exit;
    }

    if ($id) { // Update
        $stmt = $conn->prepare("UPDATE testimonials SET name = ?, designation = ?, organization = ?, content = ?, photo = ?, sort_order = ?, status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssssisi", $name, $designation, $organization, $content, $photo_url, $sort_order, $status, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Testimonial updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating testimonial: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare update): " . $conn->error;
        }
    } else { // Add new
        $stmt = $conn->prepare("INSERT INTO testimonials (name, designation, organization, content, photo, sort_order, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssssis", $name, $designation, $organization, $content, $photo_url, $sort_order, $status);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Testimonial added successfully.";
            } else {
                $_SESSION['error_message'] = "Error adding testimonial: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare insert): " . $conn->error;
        }
    }
} elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    // Consider deleting the associated photo from uploads/ if it's not used elsewhere
    $stmt = $conn->prepare("DELETE FROM testimonials WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Testimonial deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting testimonial: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error (prepare delete): " . $conn->error;
    }
} elseif ($action === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $current_status_res = $conn->query("SELECT status FROM testimonials WHERE id = $id");
    if ($current_status_res && $current_status_row = $current_status_res->fetch_assoc()) {
        $new_status = $current_status_row['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE testimonials SET status = ? WHERE id = ?");
        if($stmt){
            $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Testimonial status updated.";
            } else {
                $_SESSION['error_message'] = "Error updating status: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare toggle status): " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Testimonial not found for status toggle.";
    }
} elseif ($action === 'update_sort_order' && isset($_POST['sorted_ids'])) {
    // This action is for drag-and-drop reordering if implemented
    $sorted_ids = $_POST['sorted_ids']; // Expecting an array of IDs in the new order
    if (is_array($sorted_ids)) {
        $conn->begin_transaction();
        try {
            foreach ($sorted_ids as $index => $id) {
                $sort_order = $index + 1; // Or any other logic for sort_order values
                $stmt = $conn->prepare("UPDATE testimonials SET sort_order = ? WHERE id = ?");
                if(!$stmt) throw new Exception("Prepare failed: ".$conn->error);
                $stmt->bind_param("ii", $sort_order, $id);
                if(!$stmt->execute()) throw new Exception("Execute failed: ".$stmt->error);
                $stmt->close();
            }
            $conn->commit();
            // For AJAX, return JSON. For now, setting session message if it were a full page reload.
            $_SESSION['success_message'] = "Testimonials reordered successfully.";
             // If called via AJAX, might want to echo json_encode(['status' => 'success', ...]) and exit.
             // For now, assuming it might be part of a non-AJAX flow or a page that reloads.
             // If this is AJAX only, the redirect below is wrong.
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error_message'] = "Error reordering testimonials: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Invalid data for reordering.";
    }
    // If called via AJAX, this redirect is not appropriate.
    // For non-AJAX sort, redirect might be okay.
    // header('Location: ' . $redirect_url);
    // exit;
    // For now, let's assume this action is called via AJAX and would return JSON
    if(isset($_SESSION['success_message'])) {
        echo json_encode(['status' => 'success', 'message' => $_SESSION['success_message']]);
        unset($_SESSION['success_message']);
    } elseif(isset($_SESSION['error_message'])) {
         echo json_encode(['status' => 'error', 'message' => $_SESSION['error_message']]);
         unset($_SESSION['error_message']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unknown sort order update issue.']);
    }
    exit; // AJAX handlers should exit.
}
 else {
    $_SESSION['error_message'] = 'Invalid action specified for Testimonials.';
}

header('Location: ' . $redirect_url);
exit;
?>
