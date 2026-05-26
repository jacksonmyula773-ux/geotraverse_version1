<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

require_once 'db_config.php';

$response = ['success' => false, 'message' => ''];

$input = json_decode(file_get_contents('php://input'), true);

$file_id = intval($input['file_id'] ?? 0);
$department_id = intval($input['department_id'] ?? 0);
$is_admin = intval($input['is_admin'] ?? 0);

if ($file_id <= 0) {
    $response['message'] = 'Invalid file ID';
    echo json_encode($response);
    exit;
}

// Get file info first
$sql = "SELECT * FROM department_files WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $file_id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();

if (!$file) {
    $response['message'] = 'File not found';
    echo json_encode($response);
    exit;
}

// Check permission
if (!$is_admin && $file['department_id'] != $department_id) {
    $response['message'] = 'Permission denied';
    echo json_encode($response);
    exit;
}

// Soft delete
$update_sql = "UPDATE department_files SET deleted_by_department = ?, deleted_by_admin = ? WHERE id = ?";
$update_stmt = $conn->prepare($update_sql);
$dept_val = $is_admin ? 0 : 1;
$admin_val = $is_admin ? 1 : 0;
$update_stmt->bind_param('iii', $dept_val, $admin_val, $file_id);

if ($update_stmt->execute()) {
    // Check if there are any remaining files for this related item
    $check_sql = "SELECT COUNT(*) as count FROM department_files 
                  WHERE related_type = ? AND related_id = ? 
                  AND deleted_by_department = 0 AND deleted_by_admin = 0";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('si', $file['related_type'], $file['related_id']);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $count = $check_result->fetch_assoc()['count'];
    
    if ($count == 0) {
        // Update parent table has_files flag
        if ($file['related_type'] === 'project') {
            $conn->query("UPDATE projects SET has_files = 0 WHERE id = " . $file['related_id']);
        } elseif ($file['related_type'] === 'report') {
            $conn->query("UPDATE reports SET has_files = 0 WHERE id = " . $file['related_id']);
        }
    }
    
    $response['success'] = true;
    $response['message'] = 'File deleted successfully';
} else {
    $response['message'] = 'Failed to delete file';
}

$stmt->close();
$conn->close();

echo json_encode($response);
?>