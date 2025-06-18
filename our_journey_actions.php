<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_once INCLUDES_PATH . '/db_connect.php';

require_login();

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$redirect_url = SITE_URL . '/our_journey_list.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add_edit') {
    $id = $_POST['id'] ? (int)$_POST['id'] : null;
    $year = trim($_POST['year']); // Consider validation if it must be numeric, or specific format
    $title = trim($_POST['title']);
    $subtitle = trim($_POST['subtitle']);
    $sort_order = (int)$_POST['sort_order'];
    $status = in_array($_POST['status'], ['active', 'inactive']) ? $_POST['status'] : 'active';

    if (empty($year) || empty($title)) {
        $_SESSION['error_message'] = 'Year and Title are required.';
        if ($id) {
            header('Location: ' . SITE_URL . '/our_journey_form.php?id=' . $id);
        } else {
            header('Location: ' . SITE_URL . '/our_journey_form.php');
        }
        exit;
    }
    // Further validation for year format can be added here if needed

    if ($id) { // Update
        $stmt = $conn->prepare("UPDATE our_journey SET year = ?, title = ?, subtitle = ?, sort_order = ?, status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("sssiis", $year, $title, $subtitle, $sort_order, $status, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Journey entry updated successfully.";
            } else {
                $_SESSION['error_message'] = "Error updating journey entry: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare update): " . $conn->error;
        }
    } else { // Add new
        $stmt = $conn->prepare("INSERT INTO our_journey (year, title, subtitle, sort_order, status) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sssis", $year, $title, $subtitle, $sort_order, $status);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Journey entry added successfully.";
            } else {
                $_SESSION['error_message'] = "Error adding journey entry: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare insert): " . $conn->error;
        }
    }
} elseif ($action === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("DELETE FROM our_journey WHERE id = ?");
    if($stmt){
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Journey entry deleted successfully.";
        } else {
            $_SESSION['error_message'] = "Error deleting journey entry: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error (prepare delete): " . $conn->error;
    }
} elseif ($action === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $current_status_res = $conn->query("SELECT status FROM our_journey WHERE id = $id");
    if ($current_status_res && $current_status_row = $current_status_res->fetch_assoc()) {
        $new_status = $current_status_row['status'] === 'active' ? 'inactive' : 'active';
        $stmt = $conn->prepare("UPDATE our_journey SET status = ? WHERE id = ?");
        if($stmt){
            $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Journey entry status updated.";
            } else {
                $_SESSION['error_message'] = "Error updating status: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $_SESSION['error_message'] = "Database error (prepare toggle status): " . $conn->error;
        }
    } else {
        $_SESSION['error_message'] = "Journey entry not found for status toggle.";
    }
} else {
    $_SESSION['error_message'] = 'Invalid action specified for Our Journey.';
}

header('Location: ' . $redirect_url);
exit;
?>
