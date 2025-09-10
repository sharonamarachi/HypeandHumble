<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

if (!isset($_SESSION['user_id'])) {
  die("Please login to access this page.");
}

$business_user_id = (int) $_SESSION['user_id'];

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}
$sql = "
  SELECT 
    c.chat_id,
    c.status,
    c.created_at,
    c.subject,
    u.name AS customer_name
  FROM chat_participants cp
  JOIN conversations c ON cp.chat_id = c.chat_id
  LEFT JOIN chat_participants cp_cust 
         ON c.chat_id = cp_cust.chat_id AND cp_cust.role = 'customer'
  LEFT JOIN users u 
         ON cp_cust.user_id = u.user_id
  WHERE cp.user_id = ? AND cp.role = 'business'
  ORDER BY c.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  die("SQL Prepare Error: " . $conn->error);
}
$stmt->bind_param("i", $business_user_id);
$stmt->execute();
$result = $stmt->get_result();

$chats = [];
while ($row = $result->fetch_assoc()) {
  if (empty($row['customer_name'])) {
    $row['customer_name'] = "Unknown";
  }
  $chats[] = $row;
}
$stmt->close();
$conn->close();

$pendingChats = [];
$acceptedChats = [];
foreach ($chats as $chat) {
  if ($chat['status'] === 'pending') {
    $pendingChats[] = $chat;
  } elseif ($chat['status'] === 'approved') {
    $acceptedChats[] = $chat;
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Business Chats Dashboard</title>
  <link rel="stylesheet" href="businessStyle.css">
  <style>
    /* Minimal inline styling for basic layout */
    body {
      background-color: #f4f4f9;
      font-family: Arial, sans-serif;
      margin: 0;
      padding: 0;
      color: #333;
    }

    .container {
      width: 85%;
      max-width: 1200px;
      margin: 40px auto;
    }

    .page-header h1 {
      color: #6a1b9a;
      margin-bottom: 20px;
    }

    .message-section {
      margin-bottom: 40px;
    }

    .message-section h2 {
      color: #6a1b9a;
      margin-bottom: 10px;
    }

    .message-list {
      border: 1px solid #ccc;
      background: #fff;
      border-radius: 8px;
      padding: 15px;
    }

    .message-item {
      padding: 10px;
      border-bottom: 1px solid #eee;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .message-item:last-child {
      border-bottom: none;
    }

    .action-buttons button {
      margin-left: 10px;
      padding: 8px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      color: #fff;
    }

    .accept-btn {
      background-color: #6a5acd;
    }

    .reject-btn {
      background-color: #ff6347;
    }

    a.open-message-link {
      text-decoration: none;
      color: #6a1b9a;
      font-weight: bold;
    }

    /* Debug section styling */
    .debug-data {
      background: #eef;
      border: 1px solid #99c;
      padding: 15px;
      margin: 40px auto;
      width: 85%;
      max-width: 1200px;
      font-size: 0.9em;
      white-space: pre-wrap;
    }

    .chat-btn {
      display: inline-block;
      background-color: #6a1b9a;
      color: #fff;
      padding: 8px 12px;
      font-size: 0.9rem;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background-color 0.2s ease-in-out;
    }

    .chat-btn:hover {
      background-color: #5e157d;
    }

    .chat-btn:active {
      background-color: #4b125f;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/business_navbar.php'; ?>
  <main class="container">
    <header class="page-header">
      <h1>Business Chats Dashboard</h1>
    </header>

    <!-- Pending Chats Section -->
    <section class="message-section" aria-labelledby="pending-messages-header">
      <h2 id="pending-messages-header">Pending Chats</h2>
      <div class="message-list">
        <?php if (count($pendingChats) > 0): ?>
          <?php foreach ($pendingChats as $chat): ?>
            <div class="message-item" id="message-<?= htmlspecialchars((string)$chat['chat_id']) ?>">
              <div>
                <strong>Subject: <?= htmlspecialchars($chat['subject']) ?></strong><br>
                <small>Customer: <?= htmlspecialchars($chat['customer_name']) ?></small>
              </div>
              <div class="action-buttons">
                <button type="button" class="accept-btn"
                  onclick="updateChatStatus(<?= (int)$chat['chat_id'] ?>, 'approved')">
                  Accept
                </button>
                <button type="button" class="reject-btn"
                  onclick="updateChatStatus(<?= (int)$chat['chat_id'] ?>, 'rejected')">
                  Reject
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No pending chats.</p>
        <?php endif; ?>
      </div>
    </section>

    <!-- Accepted Chats Section -->
    <section class="message-section" aria-labelledby="accepted-messages-header">
      <h2 id="accepted-messages-header">Accepted Chats</h2>
      <div class="message-list">
        <?php if (count($acceptedChats) > 0): ?>
          <?php foreach ($acceptedChats as $chat): ?>
            <div class="message-item">
              <div>
                <strong>Subject: <?= htmlspecialchars($chat['subject']) ?></strong><br>
                <small>Customer: <?= htmlspecialchars($chat['customer_name']) ?></small>
              </div>
              <div>
                <a href="messenger.php?chat_id=<?= (int)$chat['chat_id'] ?>" class="open-message-link">
                  Open Chat
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No accepted chats.</p>
        <?php endif; ?>
      </div>
    </section>
    <form action="report_users.php" method="POST">
      <input type="hidden" name="chat_id" value="<?= (int)$chat['chat_id'] ?>">
      <input type="hidden" name="reporter_user_id" value="<?= (int)$_SESSION['user_id'] ?>">
      <button type="submit" class="chat-btn">Report Users</button>
    </form>
  </main>

  <script>
    function updateChatStatus(chatId, newStatus) {
      if (!confirm("Are you sure you want to set this chat as " + newStatus + "?")) {
        return;
      }

      // Call the update_chat_status.php script using fetch.
      fetch("update_conversation_status.php", {
          method: "POST",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded"
          },
          // The body sends URL-encoded parameters.
          body: "chat_id=" + encodeURIComponent(chatId) + "&status=" + encodeURIComponent(newStatus)
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (newStatus === "approved") {
              window.location.href = "messenger.php?chat_id=" + chatId;
            } else if (newStatus === "rejected") {
              window.location.reload();
            }
          } else {
            alert("Error updating status: " + (data.error || "Unknown error"));
          }
        })
        .catch(err => {
          console.error("Error updating chat status:", err);
          alert("An error occurred while updating chat status.");
        });
    }
  </script>

</body>

</html>