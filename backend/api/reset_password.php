<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

$token = isset($data['token']) ? $data['token'] : '';
$password = isset($data['password']) ? $data['password'] : '';

if (empty($token) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Token and password are required']);
    $conn->close();
    exit();
}

if (strlen($password) < 4) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 4 characters']);
    $conn->close();
    exit();
}

// First, check if token exists
$stmt = $conn->prepare("SELECT id, reset_token, reset_expires FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid reset token. Please request a new password reset.']);
    $stmt->close();
    $conn->close();
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// Check if token is expired
$now = new DateTime();
$expires = new DateTime($user['reset_expires']);

if ($now > $expires) {
    echo json_encode(['success' => false, 'message' => 'Reset token has expired. Please request a new password reset.']);
    $conn->close();
    exit();
}

// Hash new password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Update password and clear token
$stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
$stmt->bind_param("si", $hashed_password, $user['id']);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Password reset successfully! Redirecting to login page...']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to reset password. Please try again.']);
}

$stmt->close();
$conn->close();
?>