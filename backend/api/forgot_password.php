<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../config/database.php';
require_once '../config/email_config.php'; // Your existing email config

$response = ['success' => false, 'message' => ''];

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$email = isset($input['email']) ? filter_var(trim($input['email']), FILTER_SANITIZE_EMAIL) : '';
$user_type = isset($input['user_type']) ? $input['user_type'] : 'admin';

if (empty($email)) {
    $response['message'] = 'Email address is required';
    echo json_encode($response);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response['message'] = 'Invalid email format';
    echo json_encode($response);
    exit;
}

try {
    // Check if user exists (admin or department)
    $user = null;
    $table = '';
    
    if ($user_type === 'admin') {
        // Check super_admin table or users table
        $stmt = $pdo->prepare("SELECT id, username, email, 'admin' as type FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $table = 'users';
    } else {
        // Check departments table
        $stmt = $pdo->prepare("SELECT id, name, email, 'department' as type FROM departments WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        $table = 'departments';
    }
    
    if (!$user) {
        // For security, don't reveal that email doesn't exist
        $response['success'] = true;
        $response['message'] = 'If your email exists in our system, you will receive a password reset link.';
        echo json_encode($response);
        exit;
    }
    
    // Delete old tokens for this email
    $stmt = $pdo->prepare("DELETE FROM password_resets WHERE email = ?");
    $stmt->execute([$email]);
    
    // Generate new token
    $token = bin2hex(random_bytes(32));
    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Save token to database
    $stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at, user_type) VALUES (?, ?, ?, ?)");
    $stmt->execute([$email, $token, $expires_at, $user_type]);
    
    // Send email with reset link
    $reset_link = "http://localhost/geotraverse/frontend/reset_password.html?token=" . urlencode($token) . "&email=" . urlencode($email);
    
    $subject = "Password Reset Request - GeoTraverse ERP";
    
    $html_body = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Password Reset</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px; }
            .header { background: #0f74ba; color: white; padding: 15px; text-align: center; border-radius: 10px 10px 0 0; }
            .btn { display: inline-block; background: #0f74ba; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .footer { text-align: center; font-size: 12px; color: #666; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>GeoTraverse ERP System</h2>
                <p>Password Reset Request</p>
            </div>
            <div class='body'>
                <p>Dear User,</p>
                <p>We received a request to reset your password for your GeoTraverse ERP account.</p>
                <p>Click the button below to reset your password:</p>
                <p style='text-align: center;'>
                    <a href='" . $reset_link . "' class='btn' style='color: white; background: #0f74ba; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a>
                </p>
                <p>If you didn't request this, please ignore this email. Your password will remain unchanged.</p>
                <p>This reset link will expire in <strong>1 hour</strong>.</p>
                <p><strong>⚠️ Security Tip:</strong> Never share this link with anyone.</p>
            </div>
            <div class='footer'>
                <p>© 2024 GeoTraverse ERP System. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $text_body = "Password Reset Request\n\n";
    $text_body .= "We received a request to reset your password.\n\n";
    $text_body .= "Click this link to reset your password: $reset_link\n\n";
    $text_body .= "This link expires in 1 hour.\n\n";
    $text_body .= "If you didn't request this, ignore this email.\n";
    
    // Send email using your existing sendEmail function
    $result = sendEmail($email, $user['name'] ?? $user['username'] ?? 'User', $subject, $html_body, $text_body);
    
    if ($result['success']) {
        $response['success'] = true;
        $response['message'] = 'Password reset link has been sent to your email.';
    } else {
        $response['success'] = true; // Still return success for security
        $response['message'] = 'If your email exists in our system, you will receive a password reset link.';
        error_log("Failed to send reset email: " . $result['message']);
    }
    
} catch (PDOException $e) {
    $response['message'] = 'Database error. Please try again later.';
    error_log("Password reset error: " . $e->getMessage());
}

echo json_encode($response);
?>