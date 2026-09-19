<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}
include '../config.php';
$department = $_GET['department'];
$school = $_SESSION['school'];
$query = "SELECT DISTINCT u.id as user_id, u.username FROM learning_resources lr 
          JOIN users u ON lr.uploaded_by = u.id 
          WHERE lr.school='$school' AND lr.department='$department'";
$result = mysqli_query($conn, $query);
$lecturers = [];
while ($row = mysqli_fetch_assoc($result)) {
    $lecturers[] = $row;
}
header('Content-Type: application/json');
echo json_encode($lecturers);
?>