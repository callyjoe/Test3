<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
include '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$student_id = $_SESSION['user_id'];
$score = $_POST['score'] ?? 0;
$quiz_title = $_POST['quiz_title'] ?? 'Untitled Quiz';
$difficulty = $_POST['difficulty'] ?? 'medium';
$type = $_POST['type'] ?? 'quiz';
$answers_json = $_POST['answers_json'] ?? null;
$resource_id = isset($_POST['resource_id']) && !empty($_POST['resource_id']) ? (int)$_POST['resource_id'] : null;

if (!is_numeric($score) || $score < 0 || $score > 100) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid score']);
    exit;
}

// Prepare INSERT query with optional resource_id and answers_json
$query = "INSERT INTO performance_records (student_id, quiz_title, difficulty, type, score, answers_json, resource_id) 
          VALUES (?, ?, ?, ?, ?, ?, ?)";
$stmt = mysqli_prepare($conn, $query);

if (!$stmt) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to prepare statement: ' . mysqli_error($conn)]);
    exit;
}

// Bind parameters: student_id (int), quiz_title (string), difficulty (string), type (string), score (int), answers_json (string or null), resource_id (int or null)
mysqli_stmt_bind_param($stmt, 'isssisi', $student_id, $quiz_title, $difficulty, $type, $score, $answers_json, $resource_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['status' => 'success', 'record_id' => mysqli_insert_id($conn)]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database insert failed: ' . mysqli_stmt_error($stmt)]);
}

mysqli_stmt_close($stmt);
$conn->close();
?>