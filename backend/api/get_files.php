<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once 'db_config.php';

$response = ['success' => false, 'message' => '', 'data' => []];

$related_type = $_GET['related_type'] ?? '';
$related_id = intval($_GET['related_id'] ?? 0);
$department_id = intval($_GET['department_id'] ?? 0);

if (empty($related_type) || $related_id <= 0) {
    $response['message'] = 'Missing required parameters';
    echo json_encode($response);
    exit;
}

$sql = "SELECT * FROM department_files 
        WHERE related_type = ? AND related_id = ? 
        AND deleted_by_department = 0 AND deleted_by_admin = 0
        ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('si', $related_type, $related_id);
$stmt->execute();
$result = $stmt->get_result();

$files = [];
while ($row = $result->fetch_assoc()) {
    $files[] = $row;
}

$response['success'] = true;
$response['data'] = $files;

$stmt->close();
$conn->close();

echo json_encode($response);
?>