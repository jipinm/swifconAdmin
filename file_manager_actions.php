<?php
// Attempt to suppress PHP errors from breaking JSON output if they occur *before* this point.
// However, the ideal is that such errors are fixed.
@ini_set('display_errors', 0);
@error_reporting(0); // Suppress errors in output, rely on logs or XHR response text for debugging

// Crucial first include
if (!file_exists(__DIR__ . '/config.php')) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Critical: config.php not found.']);
    exit;
}
require_once __DIR__ . '/config.php';

if (!defined('INCLUDES_PATH') || !file_exists(INCLUDES_PATH . '/session.php')) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Critical: session.php or INCLUDES_PATH not configured.']);
    exit;
}
require_once INCLUDES_PATH . '/session.php';

// Ensure login (session.php should handle actual session start)
// This check should ideally be inside each action if some actions were public.
// For file manager, all actions are protected.
if (!function_exists('require_login')) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Critical: require_login function not available.']);
    exit;
}
require_login(); // This will exit if not logged in.

header('Content-Type: application/json'); // Set content type early.

$response = ['status' => 'error', 'message' => 'Invalid action or missing parameters.'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list_files') {
    if (!defined('UPLOADS_PATH') || !defined('SITE_URL')) {
        echo json_encode(['status' => 'error', 'message' => 'Error: UPLOADS_PATH or SITE_URL is not defined in config.php.']);
        exit;
    }

    $files = [];
    $upload_dir = rtrim(UPLOADS_PATH, '/') . '/';

    if (!is_dir($upload_dir)) {
        if (!@mkdir($upload_dir, 0755, true)) { // Suppress mkdir warning if it fails, rely on JSON response
            $response['message'] = 'Uploads directory does not exist and could not be created. Please check server permissions. Path: ' . htmlspecialchars($upload_dir);
            echo json_encode($response);
            exit;
        }
    }

    if (!is_readable($upload_dir)) {
        $response['message'] = 'Uploads directory is not readable. Please check server permissions. Path: ' . htmlspecialchars($upload_dir);
        echo json_encode($response);
        exit;
    }

    $scanned_files = @scandir($upload_dir); // Suppress scandir warning

    if ($scanned_files === false) {
        $response['message'] = 'Could not scan uploads directory. Please check server permissions or if the path is valid. Path: ' . htmlspecialchars($upload_dir);
        echo json_encode($response);
        exit;
    }

    foreach ($scanned_files as $file) {
        if ($file === '.' || $file === '..') continue;

        $file_path = $upload_dir . $file;
        $file_extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

        if (is_file($file_path) && in_array($file_extension, $allowed_extensions)) {
            $file_size = @filesize($file_path); // Suppress warning
            $file_modified = @filemtime($file_path); // Suppress warning

            $files[] = [
                'name' => $file,
                'url' => rtrim(SITE_URL, '/') . '/uploads/' . rawurlencode($file), // Use rawurlencode for filenames in URLs
                'size' => $file_size === false ? 0 : $file_size,
                'type' => $file_extension,
                'modified' => $file_modified === false ? 0 : $file_modified
            ];
        }
    }

    usort($files, function($a, $b) {
        return ($b['modified'] ?? 0) - ($a['modified'] ?? 0);
    });
    $response = ['status' => 'success', 'files' => $files, 'count' => count($files)];

} elseif ($action === 'delete_file' && isset($_POST['filename'])) {
    if (!defined('UPLOADS_PATH')) {
        echo json_encode(['status' => 'error', 'message' => 'Error: UPLOADS_PATH is not defined in config.php.']);
        exit;
    }
    $filename = basename(trim($_POST['filename']));
    $file_path = rtrim(UPLOADS_PATH, '/') . '/' . $filename;

    if (empty($filename) || strpos($filename, '..') !== false || strpbrk($filename, "\\\/?%*:|\"<>") !== FALSE) {
        $response['message'] = 'Invalid filename provided.';
    } elseif (file_exists($file_path) && is_file($file_path)) {
        if (!is_writable($file_path)) {
             $response['message'] = 'File is not writable, cannot delete. Check permissions for: ' . htmlspecialchars($filename);
        } elseif (@unlink($file_path)) { // Suppress unlink warning
            $response = ['status' => 'success', 'message' => 'File ' . htmlspecialchars($filename) . ' deleted successfully.'];
        } else {
            $response['message'] = 'Could not delete file ' . htmlspecialchars($filename) . '. Check server logs or permissions.';
        }
    } else {
        $response['message'] = 'File not found or is not a regular file: ' . htmlspecialchars($filename);
    }
}
// Ensure $response is always set before this final echo
if (!isset($response['status'])) { // Fallback if somehow $response wasn't set in a branch
    $response = ['status' => 'error', 'message' => 'An unexpected error occurred in file manager actions.'];
}
echo json_encode($response);
exit;
?>
