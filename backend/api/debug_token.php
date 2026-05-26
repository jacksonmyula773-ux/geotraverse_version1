<?php
header('Content-Type: application/json');

$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'geotraverse_erp';

$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed']);
    exit();
}

// Get token from URL parameter
$token = isset($_GET['token']) ? $_GET['token'] : '';

if (empty($token)) {
    echo json_encode(['error' => 'No token provided']);
    exit();
}

echo "Token from URL: " . $token . "\n\n";

// Search for token in database
$stmt = $conn->prepare("SELECT id, email, reset_token, reset_expires FROM users WHERE reset_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    echo "Token FOUND in database!\n";
    echo "User ID: " . $user['id'] . "\n";
    echo "Email: " . $user['email'] . "\n";
    echo "Token in DB: " . $user['reset_token'] . "\n";
    echo "Expires: " . $user['reset_expires'] . "\n";
    
    // Check if expired
    $now = new DateTime();
    $expires = new DateTime($user['reset_expires']);
    
    if ($now > $expires) {
        echo "\n⚠️ TOKEN IS EXPIRED!\n";
        echo "Now: " . $now->format('Y-m-d H:i:s') . "\n";
        echo "Expires: " . $expires->format('Y-m-d H:i:s') . "\n";
    } else {
        echo "\n✅ Token is still valid!\n";
    }
} else {
    echo "Token NOT found in database!\n";
    
    // Show all tokens in database for debugging
    $result2 = $conn->query("SELECT id, email, reset_token FROM users WHERE reset_token IS NOT NULL");
    echo "\nAll tokens in database:\n";
    while ($row = $result2->fetch_assoc()) {
        echo "User: " . $row['email'] . " - Token: " . $row['reset_token'] . "\n";
    }
}

$conn->close();
?>