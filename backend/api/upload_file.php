<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'db_config.php';

$response = ['success' => false, 'message' => '', 'data' => null];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Only POST method allowed';
    echo json_encode($response);
    exit;
}

$related_type = $_POST['related_type'] ?? '';
$related_id = intval($_POST['related_id'] ?? 0);
$department_id = intval($_POST['department_id'] ?? 0);
$description = $_POST['description'] ?? '';

if (empty($related_type) || $related_id <= 0 || $department_id <= 0) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'File upload failed';
    echo json_encode($response);
    exit;
}

$file = $_FILES['file'];
$original_name = basename($file['name']);
$file_size = $file['size'];
$tmp_name = $file['tmp_name'];

// Get file extension and type
$file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip'];
$allowed_mimes = [
    'image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
    'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain', 'application/zip'
];

if (!in_array($file_extension, $allowed_extensions)) {
    $response['message'] = 'File type not allowed. Allowed: ' . implode(', ', $allowed_extensions);
    echo json_encode($response);
    exit;
}

// Create upload directory if not exists
$upload_dir = '../frontend/assets/uploads/department_files/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Generate unique filename
$stored_name = time() . '_' . uniqid() . '.' . $file_extension;
$file_path = $upload_dir . $stored_name;
$relative_path = 'assets/uploads/department_files/' . $stored_name;

if (!move_uploaded_file($tmp_name, $file_path)) {
    $response['message'] = 'Failed to save file';
    echo json_encode($response);
    exit;
}

// Get mime type
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime_type = finfo_file($finfo, $file_path);
finfo_close($finfo);

// Insert into database
$sql = "INSERT INTO department_files (original_name, stored_name, file_path, file_type, file_extension, file_size, mime_type, related_type, related_id, department_id, description) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param('sssssisiiis', $original_name, $stored_name, $relative_path, $file_type, $file_extension, $file_size, $mime_type, $related_type, $related_id, $department_id, $description);

if ($stmt->execute()) {
    $file_id = $stmt->insert_id;
    
    // Update has_files flag on parent table
    if ($related_type === 'project') {
        $conn->query("UPDATE projects SET has_files = 1 WHERE id = $related_id");
    } elseif ($related_type === 'report') {
        $conn->query("UPDATE reports SET has_files = 1 WHERE id = $related_id");
    }
    
    $response['success'] = true;
    $response['message'] = 'File uploaded successfully';
    $response['data'] = [
        'id' => $file_id,
        'original_name' => $original_name,
        'file_extension' => $file_extension,
        'file_size' => $file_size,
        'relative_path' => $relative_path
    ];
} else {
    $response['message'] = 'Database error: ' . $conn->error;
    // Delete uploaded file if DB insert fails
    unlink($file_path);
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>