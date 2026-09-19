<?php
include 'config.php';
$email = 'admin@galorem.com';
$password = 'admin123';

$query = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    echo "User found. Role: " . $row['role'] . "<br>";
    if (password_verify($password, $row['password'])) {
        echo "✅ Password verified!";
    } else {
        echo "❌ Password mismatch. Hash in DB: " . $row['password'];
    }
} else {
    echo "❌ Admin user not found.";
}
?>