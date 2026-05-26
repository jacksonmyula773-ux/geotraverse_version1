<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database connection
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'geotraverse_erp';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    $conn->close();
    exit();
}

$email = isset($data['email']) ? trim($data['email']) : '';

if ($email !== 'jacksonmyula773@gmail.com') {
    echo json_encode(['success' => false, 'message' => 'Only Super Admin can reset password']);
    $conn->close();
    exit();
}

// Check if user exists
$result = $conn->query("SELECT id, name FROM users WHERE email = 'jacksonmyula773@gmail.com' AND role = 'Super Administrator'");

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Admin account not found']);
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();

// Add columns if not exist
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL");
$conn->query("ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_expires DATETIME NULL");

// Clear ALL existing tokens for this user first
$conn->query("UPDATE users SET reset_token = NULL, reset_expires = NULL WHERE id = " . $user['id']);

// Generate new token
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

// Insert new token
$stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
$stmt->bind_param("ssi", $token, $expires, $user['id']);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Failed to save token: ' . $stmt->error]);
    $stmt->close();
    $conn->close();
    exit();
}

$stmt->close();
$conn->close();

$reset_link = "http://localhost/geotraverse/frontend/reset_password.html?token=" . $token;

echo json_encode([
    'success' => true,
    'message' => 'Reset link generated!',
    'reset_link' => $reset_link,
    'token' => $token
]);
?>