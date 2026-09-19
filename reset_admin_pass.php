<?php
include 'config.php';

$email = 'admin@galorem.com';
$new_password = 'admin123';   // change this to any password you want

$hashed = password_hash($new_password, PASSWORD_DEFAULT);
$sql = "UPDATE users SET password = '$hashed' WHERE email = '$email'";

if (mysqli_query($conn, $sql)) {
    echo "✅ Password for $email has been updated to: <strong>$new_password</strong>";
} else {
    echo "❌ Error: " . mysqli_error($conn);
}

mysqli_close($conn);
?>