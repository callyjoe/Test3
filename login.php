<?php
session_start();
include 'config.php';

header('Content-Type: application/json');

$email = $_POST['email'];
$password = $_POST['password'];

$query = "SELECT * FROM users WHERE email='$email'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    if (password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['school'] = $user['school'];        // add this
        $_SESSION['department'] = $user['department']; // add this
         echo json_encode(['status' => 'success']);
         exit; 
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Wrong password']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Email does not exist']);
}

$conn->close();
?>