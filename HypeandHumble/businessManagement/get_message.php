<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit();
}

// Get chat_id from GET.
if (!isset($_GET['chat_id'])) {
    echo json_encode([]);
    exit();
}
$chat_id = (int) $_GET['chat_id'];

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
    echo json_encode([]);
    exit();
}

// Get messages from the messages table (joined with users to fetch the sender's name).
$sql = "
  SELECT 
    m.message_id,
    m.sender_id,
    m.content,
    m.sent_at,
    u.name AS sender_name
  FROM messages m
  JOIN users u ON m.sender_id = u.user_id
  WHERE m.chat_id = ?
  ORDER BY m.sent_at ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([]);
    exit();
}
$stmt->bind_param("i", $chat_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}
$stmt->close();
$conn->close();

echo json_encode($messages, JSON_PRETTY_PRINT);
