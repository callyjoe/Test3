<?php
include 'config.php';
header('Content-Type: application/json');

$school = $_GET['school'] ?? '';
if (!$school) {
    echo json_encode([]);
    exit;
}

$query = "SELECT DISTINCT department FROM learning_resources WHERE school = '$school' ORDER BY department";
$result = mysqli_query($conn, $query);
$departments = [];
while ($row = mysqli_fetch_assoc($result)) {
    $departments[] = $row['department'];
}
echo json_encode($departments);
?>