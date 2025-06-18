<?php
require_once __DIR__ . '/config.php'; // Correct path after move to root
require_once INCLUDES_PATH . '/session.php';
require_login();
// Ensure db_connect is included if database operations are needed, though not for basic file listing/deletion from filesystem
// require_once INCLUDES_PATH . '/db_connect.php';

header('Content-Type: application/json');
$response = ['status' => 'error', 'message' => 'Invalid action.'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list_files') {
    $files = [];
    // UPLOADS_PATH is defined in config.php, e.g., define('UPLOADS_PATH', BASE_PATH . '/uploads');
    // It should not have a trailing slash from config to make path construction clean.
    $upload_dir = rtrim(UPLOADS_PATH, '/') . '/';

    // Check if uploads directory exists, if not try to create it
    if (!is_dir($upload_dir)) {
        // Attempt to create the directory
        // The true parameter enables recursive creation
        if (!mkdir($upload_dir, 0755, true)) {
            $response['message'] = 'Uploads directory does not exist and could not be created. Please check server permissions. Path: ' . $upload_dir;
            echo json_encode($response);
            exit;
        }
    }

    // Check if it's writable (important for uploads, less so for listing, but good to be aware)
    // For listing, we primarily need it to be readable. is_readable() is implicitly checked by scandir.
    if (!is_readable($upload_dir)) {
        $response['message'] = 'Uploads directory is not readable. Please check server permissions. Path: ' . $upload_dir;
        echo json_encode($response);
        exit;
    }


    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    $scanned_files = scandir($upload_dir);

    if ($scanned_files === false) {
        $response['message'] = 'Could not scan uploads directory. Please check server permissions. Path: ' . $upload_dir;
        echo json_encode($response);
        exit;
    }

    foreach ($scanned_files as $file) {
        if ($file !== '.' && $file !== '..') {
            $file_path = $upload_dir . $file;
            $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (is_file($file_path) && in_array($file_extension, $allowed_extensions)) {
                try {
                    $file_size = filesize($file_path);
                    $file_modified = filemtime($file_path);
                } catch (Exception $e) {
                    // Could not get filesize or filemtime, skip or log
                    continue;
                }
                $files[] = [
                    'name' => $file,
                    'url' => rtrim(SITE_URL, '/') . '/uploads/' . $file, // SITE_URL from config
                    'size' => $file_size,
                    'type' => $file_extension,
                    'modified' => $file_modified
                ];
            }
        }
    }
    // Sort files by modification date, newest first
    usort($files, function($a, $b) {
        return ($b['modified'] ?? 0) - ($a['modified'] ?? 0);
    });
    $response = ['status' => 'success', 'files' => $files, 'count' => count($files)];

} elseif ($action === 'delete_file' && isset($_POST['filename'])) {
    $filename = basename(trim($_POST['filename'])); // Sanitize
    // UPLOADS_PATH should not have a trailing slash for this construction.
    $file_path = rtrim(UPLOADS_PATH, '/') . '/' . $filename;

    if (empty($filename) || strpos($filename, '..') !== false || strpbrk($filename, "\/?%*:|"<>") !== FALSE) {
        $response['message'] = 'Invalid filename.';
    } elseif (file_exists($file_path) && is_file($file_path)) { // Ensure it's a file
        if (!is_writable($file_path)) {
             $response['message'] = 'File is not writable, cannot delete. Check permissions: ' . htmlspecialchars($filename);
        } elseif (unlink($file_path)) {
            $response = ['status' => 'success', 'message' => 'File ' . htmlspecialchars($filename) . ' deleted successfully.'];
        } else {
            $response['message'] = 'Could not delete file ' . htmlspecialchars($filename) . '. Check server logs.';
        }
    } else {
        $response['message'] = 'File not found: ' . htmlspecialchars($filename);
    }
}

echo json_encode($response);
exit;
?>
