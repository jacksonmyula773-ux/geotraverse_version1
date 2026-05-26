<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/mail_error.log');

header('Content-Type: application/json');

require_once __DIR__ . '/mail_config.php';

$testEmail = 'jacksonmyula773@gmail.com';
$subject = "GeoTraverse ERP - SMTP Test";
$body = "<h2>SMTP Test</h2><p>If you see this, your SMTP is working!</p><p>Time: " . date('Y-m-d H:i:s') . "</p>";

// Try to send
$result = sendMail($testEmail, $subject, $body);

// Also try with php mail function as fallback
if (!$result) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: GeoTraverse ERP <noreply@geotraverse.com>\r\n";
    $result2 = mail($testEmail, $subject, $body, $headers);
    
    echo json_encode([
        'success' => false,
        'phpmailer_result' => $result,
        'mail_function_result' => $result2,
        'message' => 'Both PHPMailer and mail() failed. Check error logs.',
        'error_log_file' => __DIR__ . '/mail_error.log'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'message' => 'Email sent successfully via PHPMailer!'
    ]);
}
?>