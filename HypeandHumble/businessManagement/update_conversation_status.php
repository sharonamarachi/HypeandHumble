<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

$chatId = isset($_POST['chat_id']) ? (int)$_POST['chat_id'] : 0;
$newStatus = isset($_POST['status']) ? trim($_POST['status']) : '';

$allowedStatuses = ['approved', 'rejected'];
if (!in_array($newStatus, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid status value']);
    exit();
}

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB connection failed']);
    exit();
}

// Update the conversations table
$stmt = $conn->prepare("UPDATE conversations SET status = ? WHERE chat_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => "Prepare failed: " . $conn->error]);
    exit();
}

$stmt->bind_param("si", $newStatus, $chatId);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>