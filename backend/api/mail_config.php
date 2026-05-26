<?php
// ==================== MAIL CONFIGURATION - SIMPLE VERSION ====================
// This version works without PHPMailer
// For production server, we'll implement SMTP later

function sendMail($to, $subject, $body) {
    // For now, just log and return true
    // The reset link will be returned in the API response
    error_log("Password reset requested for: " . $to);
    return true;
}
?>