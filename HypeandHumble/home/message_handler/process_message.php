<?php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

header('Content-Type: application/json');

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$chat_id   = isset($data['chat_id']) ? (int)$data['chat_id'] : 0;
$sender_id = isset($data['sender_id']) ? (int)$data['sender_id'] : 0;
$content   = isset($data['content']) ? trim($data['content']) : '';

if ($chat_id === 0 || $sender_id === 0 || $content === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
}

// Database connection parameters.
$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$stmt = $conn->prepare("INSERT INTO messages (chat_id, sender_id, content, sent_at) VALUES (?, ?, ?, NOW())");
if (!$stmt) {
    echo json_encode(['success' => false, 'error' => "Prepare failed: " . $conn->error]);
    exit();
}
$stmt->bind_param("iis", $chat_id, $sender_id, $content);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'error' => $stmt->error]);
}
$stmt->close();
$conn->close();
?>
