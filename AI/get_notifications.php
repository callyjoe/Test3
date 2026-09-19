<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    echo json_encode(['notifications' => [], 'unread_count' => 0]);
    exit;
}

include '../config.php';

$student_id = $_SESSION['user_id'];

$query = "SELECT n.notification_id, n.sender_id, n.title, n.message, 
                 n.resource_id, n.is_read, n.created_at,
                 u.username AS sender_name
          FROM notifications n
          LEFT JOIN users u ON u.id = n.sender_id
          WHERE n.recipient_id = ?
          ORDER BY n.created_at DESC
          LIMIT 30";

$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, 'i', $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$notifications = [];
$unread_count  = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
    if ((int)$row['is_read'] === 0) {
        $unread_count++;
    }
}

mysqli_stmt_close($stmt);

echo json_encode([
    'notifications' => $notifications,
    'unread_count'  => $unread_count
]);
?>