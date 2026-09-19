<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

include '../config.php';

$student_id = $_SESSION['user_id'];

$input = json_decode(file_get_contents("php://input"), true);
$notification_id = $input['notification_id'] ?? null;
$mark_all         = $input['mark_all'] ?? false;

if ($mark_all) {
    $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE recipient_id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $student_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['status' => 'success']);
    exit;
}

if ($notification_id) {
    // Only allow marking your own notifications as read
    $stmt = mysqli_prepare($conn, "UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND recipient_id = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $notification_id, $student_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'No notification specified']);
?>