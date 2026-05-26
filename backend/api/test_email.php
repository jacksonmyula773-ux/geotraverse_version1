<?php
header('Content-Type: application/json');

require_once __DIR__ . '/mail_config.php';

$testEmail = 'jacksonmyula773@gmail.com';
$subject = "GeoTraverse ERP - SMTP Test";
$body = "
<h2>SMTP Configuration Test</h2>
<p>If you receive this email, your SMTP is working correctly!</p>
<p>Time: " . date('Y-m-d H:i:s') . "</p>
<p>This is a test email from GeoTraverse ERP System.</p>
";

$result = sendMail($testEmail, $subject, $body);

if ($result) {
    echo json_encode([
        'success' => true,
        'message' => 'Test email sent successfully to ' . $testEmail . '! Check your inbox.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to send test email. Please check your SMTP configuration.'
    ]);
}
?>