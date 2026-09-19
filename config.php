<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "galorem_auth_new";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
define('MISTRAL_API_KEY', 'COE44lUR8WR95rvozEaRl8JkSR9ocT6U');
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
