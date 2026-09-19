<?php
include 'config.php';
header('Content-Type: application/json');

$username = $_POST['username'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$password = $_POST['password'];
$school = $_POST['school'];
$department = $_POST['department'];

// Check duplicates
$check_user = "SELECT * FROM users WHERE username='$username'";
$check_email = "SELECT * FROM users WHERE email='$email'";

$result_user = mysqli_query($conn, $check_user);
$result_email = mysqli_query($conn, $check_email);

if (mysqli_num_rows($result_user) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username already exists']);
    exit;
}

if (mysqli_num_rows($result_email) > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists']);
    exit;
}

$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert with school and department
$sql = "INSERT INTO users (username, email, phone, password, school, department, role) 
        VALUES ('$username', '$email', '$phone', '$hashed_password', '$school', '$department', 'student')";

if (mysqli_query($conn, $sql)) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Registration failed: ' . mysqli_error($conn)]);
}

$conn->close();
?>