<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

if (!isset($_SESSION['user_id'])) {
  die("Please login to access this page.");
}

$user_id = (int) $_SESSION['user_id'];

if (!isset($_GET['chat_id'])) {
  die("No conversation specified.");
}
$chat_id = (int) $_GET['chat_id'];

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$checkSql = "SELECT 1 FROM chat_participants WHERE chat_id = ? AND user_id = ?";
$checkStmt = $conn->prepare($checkSql);
if (!$checkStmt) {
  die("Prepare error: " . $conn->error);
}
$checkStmt->bind_param("ii", $chat_id, $user_id);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();
if ($checkResult->num_rows === 0) {
  die("Access denied. You are not a participant in this conversation.");
}
$checkStmt->close();


$sql = "
  SELECT 
    c.subject,
    m.message,
    m.sender_id,
    m.created_at,
    u.name AS sender_name
  FROM conversations c
  JOIN messages m ON c.chat_id = m.chat_id
  JOIN users u ON m.sender_id = u.user_id
  WHERE c.chat_id = ?
  ORDER BY m.created_at ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("SQL Prepare Error: " . $conn->error);
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

$subject = "Chat Conversation";
if (!empty($messages) && isset($messages[0]['subject'])) {
  $subject = $messages[0]['subject'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($subject); ?></title>
  <link rel="stylesheet" href="businessStyle.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      margin: 20px;
    }

    .chat-container {
      max-width: 800px;
      margin: auto;
      background: #fff;
      border: 1px solid #ccc;
      border-radius: 8px;
      display: flex;
      flex-direction: column;
      height: 600px;
    }

    .chat-header {
      background: #6a5acd;
      color: #fff;
      padding: 10px;
      border-top-left-radius: 8px;
      border-top-right-radius: 8px;
      text-align: center;
      font-weight: bold;
    }

    .chat-messages {
      flex: 1;
      padding: 10px;
      overflow-y: auto;
      background: #fafafa;
      border-bottom: 1px solid #ccc;
    }

    .chat-input {
      display: flex;
      padding: 10px;
    }

    .chat-input input {
      flex: 1;
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
      outline: none;
    }

    .chat-input button {
      padding: 10px;
      margin-left: 10px;
      background: #6a5acd;
      color: #fff;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }

    .message {
      margin-bottom: 10px;
    }

    .message.you {
      text-align: right;
      color: #007bff;
    }

    .message.other {
      text-align: left;
      color: #333;
    }
  </style>
</head>

<body>
  <!-- Include your navbar -->
  <?php include __DIR__ . '/../../navbar.php'; ?>

  <div class="chat-container">
    <div class="chat-header">
      <?php echo htmlspecialchars($subject); ?>
    </div>
    <div class="chat-messages" id="chatMessages">
      <?php foreach ($messages as $msg):
        // Determine if the message is from the logged-in user
        $class = ($msg['sender_id'] === $user_id) ? "you" : "other";
      ?>
        <div class="message <?php echo $class; ?>">
          <strong><?php echo htmlspecialchars($msg['sender_name']); ?>:</strong>
          <?php echo htmlspecialchars($msg['message']); ?>
          <br>
          <small><?php echo htmlspecialchars($msg['created_at']); ?></small>
        </div>
      <?php endforeach; ?>
    </div>
    <div class="chat-input">
      <input type="text" id="chatInput" placeholder="Type your message...">
      <button onclick="sendMessage()">Send</button>
    </div>
  </div>
  <script>
    function sendMessage() {
      var input = document.getElementById('chatInput');
      var text = input.value.trim();
      if (text === "") return;

      var chatMessages = document.getElementById('chatMessages');
      var messageDiv = document.createElement('div');
      messageDiv.classList.add('message', 'you');
      messageDiv.innerHTML = "<strong>You:</strong> " + text + "<br><small>Just now</small>";
      chatMessages.appendChild(messageDiv);
      chatMessages.scrollTop = chatMessages.scrollHeight;
      input.value = "";

      // Here you would typically perform an AJAX request to save the message:
      // Example (using fetch):
      // fetch('process_message.php', { method: 'POST', headers: { 'Content-Type': 'application/json' },
      //    body: JSON.stringify({ chat_id: <?php echo $chat_id; ?>, sender_id: <?php echo $user_id; ?>, message: text })
      // }).then(...);
    }
  </script>
</body>

</html>