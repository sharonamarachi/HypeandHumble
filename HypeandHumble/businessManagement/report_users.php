<?php
// report_users.php
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();


if (!isset($_SESSION['user_id'])) {
  header("Location: /profileManagement/login.php");
  exit();
}
$customer_user_id = (int) $_SESSION['user_id'];

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

$stmt = $conn->prepare("
    SELECT DISTINCT u.user_id, u.name
      FROM chat_participants cp
      JOIN chat_participants other
        ON cp.chat_id = other.chat_id
       AND other.user_id != cp.user_id
      JOIN users u
        ON other.user_id = u.user_id
     WHERE cp.user_id = ?
     ORDER BY u.name
");
$stmt->bind_param('i', $customer_user_id);
$stmt->execute();
$res = $stmt->get_result();

$contacts = [];
while ($row = $res->fetch_assoc()) {
  $contacts[] = $row;
}
$stmt->close();


$successMsg = $errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_POST['reported_user_id']) || !is_numeric($_POST['reported_user_id'])) {
  } else {
    $reported_user_id = (int) $_POST['reported_user_id'];
    $reason           = trim($_POST['reason'] ?? '');
    if ($reason === '') {
      $errorMsg = 'Please provide a reason for your report.';
    } else {
      $insert = $conn->prepare("
                INSERT INTO adminreports
                  (reporter_id, reported_user_id, service_id, report_type, reason, status, created_at)
                VALUES
                  (?,             ?,                NULL,        'r_user',    ?,     'pending', NOW())
            ");
      $insert->bind_param(
        'iis',
        $customer_user_id,
        $reported_user_id,
        $reason
      );
      if ($insert->execute()) {
        $successMsg = '✅ Report submitted successfully. We will review it shortly.';
      } else {
        $errorMsg = '❌ Failed to submit report. Please try again.';
        error_log('Report insert failed: ' . $insert->error);
      }
      $insert->close();
    }
  }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Report User | Hype & Humble</title>
  <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary: #6a5acd;
      --danger: #e53e3e;
      --light: #f8f9fa;
      --white: #ffffff;
      --dark: #333333;
    }

    body {
      font-family: 'Segoe UI', sans-serif;
      background-color: var(--light);
      color: var(--dark);
      margin: 0;
    }

    .container {
      max-width: 600px;
      margin: 40px auto;
      padding: 20px;
    }

    .card {
      background: var(--white);
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    h1 {
      color: var(--danger);
      text-align: center;
      margin-bottom: 20px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
    }

    select,
    textarea {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 6px;
      background: #fff;
      resize: vertical;
    }

    textarea {
      min-height: 120px;
    }

    .btn {
      display: inline-block;
      padding: 10px 20px;
      border-radius: 6px;
      cursor: pointer;
      text-align: center;
      text-decoration: none;
      border: none;
    }

    .btn-danger {
      background: var(--danger);
      color: var(--white);
    }

    .btn-secondary {
      background: #f0f0f0;
      color: var(--dark);
    }

    .text-center {
      text-align: center;
    }

    .alert {
      padding: 15px;
      border-radius: 6px;
      margin-bottom: 20px;
    }

    .alert-success {
      background: #d4edda;
      color: #155724;
    }

    .alert-error {
      background: #f8d7da;
      color: #721c24;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/../navbar.php'; ?>

  <div class="container">
    <div class="card">
      <h1><i class="fas fa-flag"></i> Report User</h1>

      <?php if ($errorMsg): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errorMsg, ENT_QUOTES) ?></div>
      <?php endif; ?>
      <?php if ($successMsg): ?>
        <div class="alert alert-success"><?= htmlspecialchars($successMsg, ENT_QUOTES) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <div class="form-group">
          <label for="reported_user_id">Select the person you want to report:</label>
          <select name="reported_user_id" id="reported_user_id" required>
            <option value="">— Select Contact —</option>
            <?php foreach ($contacts as $c): ?>
              <option
                value="<?= (int)$c['user_id'] ?>"
                <?= (isset($_POST['reported_user_id']) && (int)$_POST['reported_user_id'] === (int)$c['user_id'])
                  ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name'], ENT_QUOTES) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="reason">Reason for report:</label>
          <textarea
            name="reason"
            id="reason"
            required
            placeholder="Please provide details about your concern..."><?= htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES) ?></textarea>
        </div>

        <div class="text-center">
          <button type="button" class="btn btn-secondary back-btn" onclick="history.back()">
            <i class="fas fa-arrow-left"></i> Back
          </button>
          <button type="submit" class="btn btn-danger">
            <i class="fas fa-paper-plane"></i> Submit Report
          </button>
        </div>
      </form>

      <p class="text-center" style="color:#666; font-size:14px; margin-top:1rem;">
        <i class="fas fa-info-circle"></i> All reports are reviewed by our team.
      </p>
    </div>
  </div>

</body>

</html>