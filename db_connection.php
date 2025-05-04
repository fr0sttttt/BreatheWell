<?php
// MySQLi connection
$conn = new mysqli('localhost', 'root', '', 'sample_db');

// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);  // Handle connection failure
}
?>