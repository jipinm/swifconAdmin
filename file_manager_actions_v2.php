<?php
// file_manager_actions_v2.php

@ini_set('display_errors', 0); // Suppress errors in output for JSON integrity
@error_reporting(0);

// Essential includes and session validation
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

if (!function_exists('require_login')) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Critical: require_login function not available.']);
    exit;
}
require_login(); // All actions in this file require login

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid action or missing parameters.'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// --- LIST FILES ACTION ---
if ($action === 'list_files') {
    if (!defined('UPLOADS_PATH') || !defined('SITE_URL')) {
        $response = ['status' => 'error', 'message' => 'Error: UPLOADS_PATH or SITE_URL is not defined in config.php.'];
        echo json_encode($response);
        exit;
    }

    $files_data = []; // Changed variable name from 'files' to avoid potential conflict if this script were included
    $upload_dir = rtrim(UPLOADS_PATH, '/') . '/';

    if (!is_dir($upload_dir)) {
        if (!@mkdir($upload_dir, 0755, true)) {
            $response = ['status' => 'error', 'message' => 'Uploads directory does not exist and could not be created. Please check server permissions. Path: ' . htmlspecialchars($upload_dir)];
            echo json_encode($response);
            exit;
        }
    }

    if (!is_readable($upload_dir)) {
        $response = ['status' => 'error', 'message' => 'Uploads directory is not readable. Please check server permissions. Path: ' . htmlspecialchars($upload_dir)];
        echo json_encode($response);
        exit;
    }

    $scanned_items = @scandir($upload_dir);

    if ($scanned_items === false) {
        $response = ['status' => 'error', 'message' => 'Could not scan uploads directory. Please check server permissions. Path: ' . htmlspecialchars($upload_dir)];
        echo json_encode($response);
        exit;
    }

    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf']; // Add more if needed

    foreach ($scanned_items as $item) {
        if ($item === '.' || $item === '..') continue;

        $item_path = $upload_dir . $item;

        // For V2, we only list files, no sub-directory navigation required by user.
        if (is_file($item_path)) {
            $file_extension = strtolower(pathinfo($item, PATHINFO_EXTENSION));
            if (in_array($file_extension, $allowed_extensions)) {
                $file_size = @filesize($item_path);
                $file_modified = @filemtime($item_path);

                $files_data[] = [
                    'name' => $item,
                    'url' => rtrim(SITE_URL, '/') . '/uploads/' . rawurlencode($item),
                    'type' => $file_extension,
                    'size' => $file_size === false ? 0 : $file_size,
                    'modified' => $file_modified === false ? 0 : $file_modified
                ];
            }
        }
    }

    // Sort files by modification date, newest first
    usort($files_data, function($a, $b) {
        return ($b['modified'] ?? 0) - ($a['modified'] ?? 0);
    });
    $response = ['status' => 'success', 'files' => $files_data, 'count' => count($files_data)];

// --- DELETE FILE ACTION ---
} elseif ($action === 'delete_file' && isset($_POST['filename'])) {
    if (!defined('UPLOADS_PATH')) {
        $response = ['status' => 'error', 'message' => 'Error: UPLOADS_PATH is not defined in config.php.'];
        echo json_encode($response);
        exit;
    }

    $filename = basename(trim($_POST['filename'])); // Sanitize: remove directory paths

    // Additional sanitization: ensure filename does not contain problematic characters after basename
    // This regex matches typical "safe" filename characters: letters, numbers, underscore, hyphen, dot.
    if (empty($filename) || !preg_match('/^[a-zA-Z0-9_.-]+$/', $filename) || strpos($filename, '..') !== false) {
        $response['message'] = 'Invalid filename format or characters.';
        echo json_encode($response);
        exit;
    }

    $file_path = rtrim(UPLOADS_PATH, '/') . '/' . $filename;

    if (file_exists($file_path) && is_file($file_path)) {
        if (!is_writable($file_path)) { // Check if the file itself is writable
             $response['message'] = 'File is not writable, cannot delete. Check permissions for: ' . htmlspecialchars($filename);
        } elseif (!is_writable(dirname($file_path))) { // Check if the directory is writable
            $response['message'] = 'Uploads directory is not writable, cannot delete files. Check directory permissions.';
        } elseif (@unlink($file_path)) {
            $response = ['status' => 'success', 'message' => 'File ' . htmlspecialchars($filename) . ' deleted successfully.'];
        } else {
            $response['message'] = 'Could not delete file ' . htmlspecialchars($filename) . '. Possible permission issue or file is in use.';
        }
    } else {
        $response['message'] = 'File not found or is not a regular file: ' . htmlspecialchars($filename);
    }
}
// No other actions in this V2 script for now. Upload is handled by file_manager/upload.php

echo json_encode($response);
exit;
?>
