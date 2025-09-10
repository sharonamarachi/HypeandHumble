<?php

declare(strict_types=1);
session_start();
ini_set('display_errors', '1');
error_reporting(E_ALL);

$error = '';
$name = '';
$password = '';
$password_confirm = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name              = trim($_POST['name']             ?? '');
  $password          = trim($_POST['password']         ?? '');
  $password_confirm  = trim($_POST['password_confirm'] ?? '');

  if ($name === '' || $password === '' || $password_confirm === '') {
    $error = 'Please fill in all fields.';
  } elseif ($password !== $password_confirm) {
    $error = 'Passwords do not match.';
  } else {
    $conn = new mysqli(
      "sql106.infinityfree.com",
      "if0_38503886",
      "StlFnsLkFkx",
      "if0_38503886_hypehumbledb",
      3306
    );
    if ($conn->connect_error) {
      $error = 'Database connection failed.';
    } else {
      $stmt = $conn->prepare("SELECT user_id, email, role, verified FROM users WHERE name = ?");
      $stmt->bind_param("s", $name);
      $stmt->execute();
      $user = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      if (!$user) {
        $error = 'No account found with that name.';
      } elseif (!$user['verified']) {
        $error = 'Account not verified. Please check your email.';
      } else {
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $upd->bind_param("si", $password, $user['user_id']);
        if ($upd->execute()) {
          session_regenerate_id(true);
          $_SESSION['user_id']   = $user['user_id'];
          $_SESSION['name']      = $name;
          $_SESSION['email']     = $user['email'];
          $_SESSION['role']      = $user['role'];
          $_SESSION['logged_in'] = true;

          // 7) Redirect based on role
          if ($user['role'] === 'admin') {
            header("Location: ../adminManagement/adminDashboard.php");
          } elseif ($user['role'] === 'provider') {
            header("Location: ../businessManagement/business_user_profile.php");
          } else {
            header("Location: user_profile.php");
          }
          exit;
        } else {
          $error = 'Failed to update password. Please try again.';
        }
        $upd->close();
      }
      $conn->close();
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <style>
    body {
      background: #f5f5f5;
      font-family: sans-serif;
      margin: 0;
    }

    .container {
      max-width: 400px;
      margin: 5% auto;
      padding: 2rem;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
      text-align: center;
    }

    h1 {
      color: #6a1b9a;
      margin-bottom: 1.5rem;
    }

    form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    input {
      padding: 0.8rem;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    button {
      background: #6a1b9a;
      color: #fff;
      border: none;
      padding: 1rem;
      border-radius: 4px;
      cursor: pointer;
      transition: background 0.2s;
    }

    button:hover {
      background: #4a148c;
    }

    .error {
      background: #fce4e4;
      color: #d32f2f;
      margin-bottom: 1rem;
      padding: 0.8rem;
      border-radius: 4px;
    }

    .back-link {
      display: block;
      margin-top: 1rem;
      color: #6a1b9a;
      text-decoration: none;
    }

    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/../navbar.php'; ?>

  <div class="container">
    <h1>Reset Your Password</h1>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
      <input
        type="text"
        name="name"
        placeholder="Your Name"
        required
        value="<?= htmlspecialchars($name) ?>">
      <input
        type="password"
        name="password"
        placeholder="New Password"
        required>
      <input
        type="password"
        name="password_confirm"
        placeholder="Confirm New Password"
        required>
      <button type="submit">Set New Password &amp; Log In</button>
    </form>

    <a href="login.php" class="back-link">← Back to Login</a>
  </div>

  <?php include __DIR__ . '/../footer.php'; ?>
</body>

</html>