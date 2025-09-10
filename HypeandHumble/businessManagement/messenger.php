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


$conversationSubject = "Live Chat Conversation";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($conversationSubject); ?></title>
  <link rel="stylesheet" href="businessStyle.css">
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      margin: 0;
      padding: 0;
    }

    .chat-container {
      max-width: 800px;
      margin: 80px auto;
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
  <?php include __DIR__ . '/business_navbar.php'; ?>

  <div class="chat-container">
    <div class="chat-header">
      <?php echo htmlspecialchars($conversationSubject); ?>
    </div>
    <div class="chat-messages" id="chatMessages">
      <!-- Messages will be loaded here -->
    </div>
    <div class="chat-input">
      <input type="text" id="chatInput" placeholder="Type your message...">
      <button onclick="sendMessage()">Send</button>
    </div>
  </div>

  <script>
    var messages = [];

    function pollMessages() {
      fetch('get_message.php?chat_id=<?php echo $chat_id; ?>')
        .then(response => response.json())
        .then(data => {
          // Assume data is an array of messages.
          messages = data;
          updateChatDisplay();
        })
        .catch(error => console.error('Error fetching messages:', error));
    }

    // Function to update the chat box with the latest messages.
    function updateChatDisplay() {
      var chatMessages = document.getElementById('chatMessages');
      chatMessages.innerHTML = '';
      messages.forEach(function(msg) {
        var msgDiv = document.createElement('div');
        msgDiv.classList.add('message');
        // Class based on sender.
        if (msg.sender_id === <?php echo $user_id; ?>) {
          msgDiv.classList.add('you');
          msgDiv.innerHTML = "<strong>You:</strong> " + msg.content + "<br><small>" + msg.sent_at + "</small>";
        } else {
          msgDiv.classList.add('other');
          msgDiv.innerHTML = "<strong>" + msg.sender_name + ":</strong> " + msg.content + "<br><small>" + msg.sent_at + "</small>";
        }
        chatMessages.appendChild(msgDiv);
      });
      chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    // Function to send a message via AJAX.
    function sendMessage() {
      var input = document.getElementById('chatInput');
      var text = input.value.trim();
      if (text === "") return;

      // Prepare the message data.
      var data = {
        chat_id: <?php echo $chat_id; ?>,
        sender_id: <?php echo $user_id; ?>,
        content: text
      };

      // Send via AJAX to process_message.php.
      fetch('process_message.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json'
          },
          body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(result => {
          if (result.success) {
            // Clear the input and update messages.
            input.value = "";
            pollMessages(); // Refresh chat messages.
          } else {
            alert("Error sending message: " + result.error);
          }
        })
        .catch(error => console.error('Error sending message:', error));
    }

    // Poll for new messages every 5 seconds.
    setInterval(pollMessages, 5000);
    // Initial poll
    pollMessages();
  </script>
</body>

</html>