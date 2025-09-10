<?php

declare(strict_types=1);
session_start();

$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (
  $_SERVER['REQUEST_METHOD'] === 'POST'
  && strpos($contentType, 'application/json') === 0
) {
  header('Content-Type: application/json');

  if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Login required']);
    exit();
  }
  $me = (int)$_SESSION['user_id'];

  $input = json_decode(file_get_contents('php://input'), true);
  $pastUserId = isset($input['past_user_id']) ? (int)$input['past_user_id'] : 0;
  $serviceId  = isset($input['service_id'])    ? (int)$input['service_id']   : 0;
  $query      = isset($input['query'])         ? trim($input['query'])       : '';

  if ($pastUserId <= 0 || $serviceId <= 0 || $query === '') {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
    exit();
  }

  $conn = new mysqli(
    "sql106.infinityfree.com",
    "if0_38503886",
    "StlFnsLkFkx",
    "if0_38503886_hypehumbledb",
    3306
  );
  if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'DB failure']);
    exit();
  }

  $conn->begin_transaction();
  try {
    // fetch service name for subject
    $svc = $conn->prepare("SELECT name FROM services WHERE service_id = ?");
    $svc->bind_param("i", $serviceId);
    $svc->execute();
    $name = $svc->get_result()->fetch_assoc()['name'] ?? 'Unknown Service';
    $svc->close();
    $subject = "Query " . $name;

    // create conversation
    $ins = $conn->prepare("
          INSERT INTO conversations (created_at,status,subject)
          VALUES (NOW(),'pending',?)
        ");
    $ins->bind_param("s", $subject);
    $ins->execute();
    $chatId = $ins->insert_id;
    $ins->close();

    // add me as customer
    $part = $conn->prepare("
          INSERT INTO chat_participants
            (chat_id,user_id,role,joined_at)
          VALUES (?,?,?,NOW())
        ");
    $roleCust = 'customer';
    $part->bind_param("iis", $chatId, $me, $roleCust);
    $part->execute();

    // add past user as customer_past
    $rolePast = 'customer_past';
    $part->bind_param("iis", $chatId, $pastUserId, $rolePast);
    $part->execute();
    $part->close();

    // initial message
    $msg = $conn->prepare("
          INSERT INTO messages
            (chat_id,sender_id,content,sent_at)
          VALUES (?,?,?,NOW())
        ");
    $msg->bind_param("iis", $chatId, $me, $query);
    $msg->execute();
    $msg->close();

    $conn->commit();
    echo json_encode(['success' => true]);
  } catch (Exception $e) {
    $conn->rollback();
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Failed to create chat']);
  }
  exit();
}
// ——— end AJAX handler ———



if (!isset($_SESSION['user_id'])) {
  die("Please log in to access this page.");
}
$currentUser = (int)$_SESSION['user_id'];
$service_id  = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;
if ($service_id <= 0) {
  die("Invalid service ID.");
}

$conn = new mysqli(
  "sql106.infinityfree.com",
  "if0_38503886",
  "StlFnsLkFkx",
  "if0_38503886_hypehumbledb",
  3306
);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// 1) Fetch past users…
$stmt = $conn->prepare("
  SELECT DISTINCT u.user_id, u.name
    FROM bookings b
    JOIN users u ON b.user_id = u.user_id
   WHERE b.service_id = ? AND b.status = 'completed'
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$pastUsers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 2) Which ones already requested?
$stmt2 = $conn->prepare("
  SELECT DISTINCT cp2.user_id AS past_user_id
    FROM chat_participants cp1
    JOIN chat_participants cp2 
      ON cp1.chat_id = cp2.chat_id
   WHERE cp1.user_id = ? 
     AND cp1.role = 'customer'
     AND cp2.role = 'customer_past'
");
$stmt2->bind_param("i", $currentUser);
$stmt2->execute();
$requestedUsers = [];
foreach ($stmt2->get_result() as $row) {
  $requestedUsers[] = (int)$row['past_user_id'];
}
$stmt2->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Chat with Past Customers</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      background: #f4f4f9;
      color: #333;
      margin: 0;
      padding: 20px;
    }

    .user-list {
      list-style: none;
      padding: 0;
    }

    .user-item {
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      margin-bottom: 15px;
      padding: 15px;
    }

    .user-item h3 {
      margin: 0 0 10px;
      color: #6a1b9a;
    }

    .user-item form {
      display: flex;
      flex-direction: column;
    }

    .user-item textarea {
      resize: vertical;
      min-height: 80px;
      margin-bottom: 8px;
      padding: 8px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    .user-item button {
      align-self: flex-start;
      padding: 8px 12px;
      background: #6a5acd;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.3s;
    }

    .user-item button:disabled {
      background: #aaa;
      cursor: default;
    }

    .user-item button:hover:not(:disabled) {
      background: #5a3ea1;
    }

    .back-btn {
      background: none;
      border: none;
      color: #6a5acd;
      font-size: 1rem;
      cursor: pointer;
      padding: 0;
      text-decoration: underline;
      transition: color 0.3s;
      margin-top: 20px;
    }

    .back-btn:hover {
      color: #5a3ea1;
    }
  </style>
</head>

<body>
  <h1>Chat with Past Customers</h1>
  <?php if (empty($pastUsers)): ?>
    <p>No past customers found.</p>
  <?php else: ?>
    <ul class="user-list">
      <?php foreach ($pastUsers as $user):
        $uid = (int)$user['user_id'];
        $already = in_array($uid, $requestedUsers, true);
      ?>
        <li class="user-item" data-user-id="<?= $uid ?>">
          <h3><?= htmlspecialchars($user['name']) ?></h3>
          <?php if ($already): ?>
            <button disabled>Request Sent</button>
          <?php else: ?>
            <form class="past-chat-form">
              <input type="hidden" name="past_user_id" value="<?= $uid ?>">
              <textarea name="query"
                placeholder="Question for <?= htmlspecialchars($user['name']) ?>…"
                required></textarea>
              <button type="submit">Send Request</button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <button type="button" class="back-btn" onclick="history.back()">
    ← Back
  </button>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelectorAll('.past-chat-form').forEach(form => {
        form.addEventListener('submit', async e => {
          e.preventDefault();
          const btn = form.querySelector('button');
          const txt = form.querySelector('textarea');
          const pid = form.querySelector('[name="past_user_id"]').value;
          const q = txt.value.trim();
          if (!q) return alert("Enter your question.");
          try {
            const res = await fetch(location.href, {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify({
                past_user_id: pid,
                service_id: <?= $service_id ?>,
                query: q
              })
            });
            const data = await res.json();
            if (data.success) {
              alert("Request sent");
              btn.disabled = true;
              btn.textContent = "Request Sent";
              txt.disabled = true;
            } else {
              alert("Error: " + data.error);
            }
          } catch (err) {
            console.error(err);
            alert("Unexpected error");
          }
        });
      });
    });
  </script>
</body>

</html>