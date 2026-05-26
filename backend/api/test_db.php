<?php
require_once 'db_connection.php';
$conn = getConnection();
echo "Connected successfully!";
$conn->close();
?>