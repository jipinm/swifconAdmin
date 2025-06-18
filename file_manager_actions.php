<?php
require_once __DIR__ . '/config.php';
require_once INCLUDES_PATH . '/session.php';
require_login();
// Ensure db_connect is included if database operations are needed, though not for basic file listing/deletion from filesystem
// require_once INCLUDES_PATH . '/db_connect.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid action.'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list_files') {
    // Logic to scan UPLOADS_PATH and return file list will go here
    // Consider pagination: ?page=1, ?per_page=20
    $files = [];
    $upload_dir = UPLOADS_PATH . '/';
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

    if (is_dir($upload_dir)) {
        $scanned_files = scandir($upload_dir);
        foreach ($scanned_files as $file) {
            if ($file !== '.' && $file !== '..') {
                $file_path = $upload_dir . $file;
                $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (is_file($file_path) && in_array($file_extension, $allowed_extensions)) {
                    $files[] = [
                        'name' => $file,
                        'url' => SITE_URL . '/uploads/' . $file,
                        'size' => filesize($file_path),
                        'type' => $file_extension, // Basic type from extension
                        'modified' => filemtime($file_path)
                    ];
                }
            }
        }
        // Sort files by modification date, newest first
        usort($files, function($a, $b) {
            return $b['modified'] - $a['modified'];
        });
        $response = ['status' => 'success', 'files' => $files, 'count' => count($files)];
    } else {
        $response['message'] = 'Uploads directory not found.';
    }
} elseif ($action === 'delete_file' && isset($_POST['filename'])) {
    $filename = basename(trim($_POST['filename'])); // Sanitize
    $file_path = UPLOADS_PATH . '/' . $filename;

    if (empty($filename) || strpos($filename, '..') !== false) {
        $response['message'] = 'Invalid filename.';
    } elseif (file_exists($file_path) && is_writable($file_path)) {
        if (unlink($file_path)) {
            $response = ['status' => 'success', 'message' => 'File ' . htmlspecialchars($filename) . ' deleted successfully.'];
        } else {
            $response['message'] = 'Could not delete file ' . htmlspecialchars($filename) . '.';
        }
    } else {
        $response['message'] = 'File not found or not writable: ' . htmlspecialchars($filename);
    }
}

echo json_encode($response);
exit;
?>
