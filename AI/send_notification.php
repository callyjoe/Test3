<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'lecturer') {
    echo json_encode(['status' => 'error', 'message' => 'Not authorized.']);
    exit;
}

include '../config.php';

$lecturer_id = $_SESSION['user_id'];

$student_id  = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$resource_id = isset($_POST['resource_id']) && !empty($_POST['resource_id']) ? (int)$_POST['resource_id'] : null;
$title       = trim($_POST['title'] ?? '');
$message     = trim($_POST['message'] ?? '');

if ($student_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'No student specified.']);
    exit;
}

if ($title === '' || $message === '') {
    echo json_encode(['status' => 'error', 'message' => 'Title and message are required.']);
    exit;
}

// Verify the student exists and is actually a student
$studentCheck = mysqli_prepare($conn, "SELECT id FROM users WHERE id = ? AND role = 'student'");
mysqli_stmt_bind_param($studentCheck, 'i', $student_id);
mysqli_stmt_execute($studentCheck);
mysqli_stmt_store_result($studentCheck);

if (mysqli_stmt_num_rows($studentCheck) === 0) {
    mysqli_stmt_close($studentCheck);
    echo json_encode(['status' => 'error', 'message' => 'Student not found.']);
    exit;
}
mysqli_stmt_close($studentCheck);

// If a resource_id was provided, verify it belongs to this lecturer
if ($resource_id !== null) {
    $resCheck = mysqli_prepare($conn, "SELECT resource_id FROM learning_resources WHERE resource_id = ? AND uploaded_by = ?");
    mysqli_stmt_bind_param($resCheck, 'ii', $resource_id, $lecturer_id);
    mysqli_stmt_execute($resCheck);
    mysqli_stmt_store_result($resCheck);

    if (mysqli_stmt_num_rows($resCheck) === 0) {
        // Resource doesn't belong to this lecturer — don't attach it, but still allow sending
        $resource_id = null;
    }
    mysqli_stmt_close($resCheck);
}

// Insert the notification
$insert = mysqli_prepare($conn, "INSERT INTO notifications (sender_id, recipient_id, type, title, message, resource_id) 
                                  VALUES (?, ?, 'individual', ?, ?, ?)");
mysqli_stmt_bind_param($insert, 'iissi', $lecturer_id, $student_id, $title, $message, $resource_id);

if (mysqli_stmt_execute($insert)) {
    echo json_encode(['status' => 'success', 'notification_id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . mysqli_stmt_error($insert)]);
}

mysqli_stmt_close($insert);
?>