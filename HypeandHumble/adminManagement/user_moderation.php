<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

if (!isset($_SESSION['user_id'])) {
  die("Please login to access this page.");
}
$conn = new mysqli("sql106.infinityfree.com", "if0_38503886", "StlFnsLkFkx", "if0_38503886_hypehumbledb", 3306);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$adminCheck = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$adminCheck->bind_param("i", $_SESSION['user_id']);
$adminCheck->execute();
$userRes = $adminCheck->get_result();
if ($userRes->num_rows === 0) die("User not found.");
$user = $userRes->fetch_assoc();
if ($user['role'] !== 'admin') die("Access denied.");
$adminCheck->close();

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_id'], $_POST['action'])) {
  $reportId = (int) $_POST['report_id'];
  $action = $_POST['action'] === 'approve' ? 'resolved' : 'reviewed';
  $upd = $conn->prepare("UPDATE adminreports SET status = ? WHERE report_id = ?");
  $upd->bind_param('si', $action, $reportId);
  $upd->execute();
  $upd->close();
  header('Location: ' . $_SERVER['PHP_SELF']);
  exit();
}

// Fetch only pending reports (service, business, user)
$sql = "SELECT
            ar.report_id,
            u_rep.name   AS reporter_name,
            u_tar.name   AS reported_name,
            ar.reason,
            ar.created_at
        FROM adminreports ar
        JOIN users u_rep ON ar.reporter_id     = u_rep.user_id
        JOIN users u_tar ON ar.reported_user_id = u_tar.user_id
        WHERE ar.report_type IN ('r_business','r_user')
          AND ar.status = 'pending'
        ORDER BY ar.created_at DESC
";
$res = $conn->query($sql);
$reports = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$res?->free();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Content Moderation - Hype & Humble</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
  <style>
    body {
      font-family: Arial, sans-serif;
      background-color: #eff0f3;
      margin: 0;
      padding: 0;
    }

    h1 {
      color: #6a1b9a;
    }

    .main {
      margin-left: 330px;
    }

    .container {
      width: 90%;
      padding: 20px;
      background-color: white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      border-radius: 20px;
    }

    h1 {
      color: #6a1b9a;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    th,
    td {
      padding: 12px 15px;
      text-align: left;
      border-bottom: 1px solid #ddd;
    }

    th {
      background: #f8f9fa;
      font-weight: 600;
    }

    tr:hover {
      background: #f5f5f5;
    }

    .approve-btn,
    .reject-btn {
      padding: 8px 12px;
      margin-right: 5px;
      border: none;
      border-radius: 5px;
      color: #fff;
      cursor: pointer;
      font-weight: bold;
    }

    .approve-btn {
      background: #4CAF50;
    }

    .reject-btn {
      background: #f44336;
    }

    .approve-btn:hover {
      background: #45a049;
    }

    .reject-btn:hover {
      background: #d32f2f;
    }

    @media(max-width:768px) {
      .container {
        width: 95%;
        margin: 10px auto;
      }

      table,
      thead,
      tbody,
      tr,
      th,
      td {
        display: block;
        width: 100%;
      }

      thead {
        display: none;
      }

      tr {
        margin-bottom: 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        padding: 10px;
      }

      td {
        display: flex;
        justify-content: space-between;
        padding: 10px 15px;
      }

      td::before {
        content: attr(data-label);
        font-weight: bold;
        width: 45%;
        padding-right: 10px;
      }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/sidebar.php'; ?>
  <div class="main">
    <h1>User Moderation</h1>
    <div class="container">
      <h2>Pending Business & User Reports</h2>
      <table>
        <thead>
          <tr>
            <th>Report ID</th>
            <th>Repoted User</th>
            <th>Reported By</th>
            <th>Reason</th>
            <th>Reported At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($reports as $r): ?>
            <tr>
              <td data-label="Report ID"><?= htmlspecialchars($r['report_id'], ENT_QUOTES) ?></td>
              <td data-label="Reported User"><?= htmlspecialchars($r['reported_name'], ENT_QUOTES) ?></td>
              <td data-label="Reported By"><?= htmlspecialchars($r['reporter_name'], ENT_QUOTES) ?></td>
              <td data-label="Reason"><?= htmlspecialchars($r['reason'], ENT_QUOTES) ?></td>
              <td data-label="Reported At"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['created_at'])), ENT_QUOTES) ?></td>
              <td data-label="Actions">
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                  <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                </form>
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="report_id" value="<?= $r['report_id'] ?>">
                  <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($reports)): ?>
            <tr>
              <td colspan="6" style="text-align:center; padding:20px;">No pending reports.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</body>

</html>