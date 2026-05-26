<?php
// test_direct_smtp.php - Test SMTP connection directly
$host = 'smtp.gmail.com';
$port = 587;
$username = 'jacksonmyula773@gmail.com';
$password = 'xkeg oiwh xwjx bnfp'; // App password yako

echo "<h2>Testing SMTP Connection</h2>";
echo "Host: $host<br>";
echo "Port: $port<br>";
echo "Username: $username<br>";
echo "Password: " . str_repeat('*', strlen($password)) . "<br><br>";

// Test fsockopen first
echo "<strong>1. Testing network connection...</strong><br>";
$fp = @fsockopen($host, $port, $errno, $errstr, 10);
if ($fp) {
    echo "✅ Network connection successful!<br>";
    fclose($fp);
} else {
    echo "❌ Network connection failed: $errstr ($errno)<br>";
    echo "This means your server cannot reach Gmail's SMTP server.<br>";
}

// Test with stream socket
echo "<br><strong>2. Testing SSL/TLS connection...</strong><br>";
$context = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
$fp = @stream_socket_client("tcp://$host:$port", $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
if ($fp) {
    echo "✅ TLS connection successful!<br>";
    fclose($fp);
} else {
    echo "❌ TLS connection failed: $errstr<br>";
}

// Test PHP mail configuration
echo "<br><strong>3. PHP Configuration:</strong><br>";
echo "OpenSSL enabled: " . (extension_loaded('openssl') ? '✅ Yes' : '❌ No') . "<br>";
echo "OpenSSL version: " . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Not available') . "<br>";
echo "allow_url_fopen: " . (ini_get('allow_url_fopen') ? '✅ On' : '❌ Off') . "<br>";
?>