<?php
require_once __DIR__ . '/../config.php';
require_once INCLUDES_PATH . '/session.php';

// Only logged-in admins can upload files
require_login();

header('Content-Type: application/json');

$response = [
    'status' => 'error',
    'message' => 'An unknown error occurred.',
    'url' => ''
];

if (!isset($_FILES['file_to_upload'])) {
    $response['message'] = 'No file selected for upload.';
    echo json_encode($response);
    exit;
}

$file = $_FILES['file_to_upload'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $upload_errors = [
        UPLOAD_ERR_INI_SIZE   => 'File exceeds upload_max_filesize directive in php.ini.',
        UPLOAD_ERR_FORM_SIZE  => 'File exceeds MAX_FILE_SIZE directive in HTML form.',
        UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.',
    ];
    $response['message'] = isset($upload_errors[$file['error']]) ? $upload_errors[$file['error']] : 'Unknown upload error.';
    echo json_encode($response);
    exit;
}

$target_dir = UPLOADS_PATH . "/"; // Defined in config.php
$original_file_name = basename($file["name"]);
$file_extension = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));
$sanitized_file_name_base = preg_replace("/[^a-zA-Z0-9_.-]/", "_", pathinfo($original_file_name, PATHINFO_FILENAME));
$unique_file_name = $sanitized_file_name_base . "_" . time() . "." . $file_extension;
$target_file = $target_dir . $unique_file_name;

// Check if uploads directory exists, if not create it
if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
        $response['message'] = 'Failed to create upload directory.';
        echo json_encode($response);
        exit;
    }
}

// Check file size (e.g., 5MB limit)
$max_file_size = 5 * 1024 * 1024; // 5 MB
if ($file["size"] > $max_file_size) {
    $response['message'] = "Sorry, your file is too large. Maximum size is 5MB.";
    echo json_encode($response);
    exit;
}

// Allow certain file formats
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
if (!in_array($file_extension, $allowed_extensions)) {
    $response['message'] = "Sorry, only JPG, JPEG, PNG, GIF, & PDF files are allowed. Your file type: " . $file_extension;
    echo json_encode($response);
    exit;
}

// Check MIME type to be more secure (optional but recommended)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file["tmp_name"]);
finfo_close($finfo);

$allowed_mime_types = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf'
];

if (!in_array($mime_type, $allowed_mime_types)) {
    $response['message'] = "Sorry, file type is not allowed based on MIME type. Detected: " . $mime_type;
    echo json_encode($response);
    exit;
}


// Try to move the uploaded file
if (move_uploaded_file($file["tmp_name"], $target_file)) {
    $response['status'] = 'success';
    $response['message'] = "The file ". htmlspecialchars($original_file_name). " has been uploaded.";
    // Ensure SITE_URL ends with a slash if UPLOADS_DIR does not start with one, or vice versa
    // Current config: SITE_URL (no trailing slash), UPLOADS_PATH (absolute path)
    // To get a URL, we need the relative path from the web root.
    $relative_upload_path = 'uploads/' . $unique_file_name; // Assuming 'uploads' is directly under SITE_URL
    $response['url'] = SITE_URL . '/' . $relative_upload_path;
    $response['filename'] = $unique_file_name;

} else {
    $response['message'] = "Sorry, there was an error uploading your file to the final destination.";
}

echo json_encode($response);
exit;
?>
