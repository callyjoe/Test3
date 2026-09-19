<?php
include '../config.php';
header('Content-Type: application/json');

$lecturer_id = $_GET['lecturer_id'] ?? '';

if (!$lecturer_id) {
    echo json_encode([]);
    exit;
}

$lecturer_id = (int)$lecturer_id; // sanitize to integer

$query = "SELECT resource_id, title, type, url 
          FROM learning_resources 
          WHERE uploaded_by = $lecturer_id 
          ORDER BY uploaded_at DESC";

$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode([]);
    exit;
}

$resources = [];
while ($row = mysqli_fetch_assoc($result)) {
    $resources[] = $row;
}

echo json_encode($resources);
?>