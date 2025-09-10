<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$errors           = [];
$name             = '';
$email            = '';
$password         = '';
$confirm_password = '';
$role             = 'user';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $name             = trim($_POST['name']             ?? '');
  $email            = trim($_POST['email']            ?? '');
  $password         = trim($_POST['password']         ?? '');
  $confirm_password = trim($_POST['confirm_password'] ?? '');
  $role             = trim($_POST['role']             ?? 'user');

  if (empty($name))             $errors[] = "Name is required.";
  if (empty($email))            $errors[] = "Email is required.";
  if (empty($password))         $errors[] = "Password is required.";
  if (empty($confirm_password)) $errors[] = "Please confirm your password.";

  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format.";
  }
  if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match.";
  }
  if (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long.";
  }

  $allowed_roles = ['user', 'provider', 'admin'];
  if (!in_array($role, $allowed_roles)) {
    $errors[] = "Invalid role selection.";
  }


  if (empty($errors)) {
    $conn = new mysqli(
      "sql106.infinityfree.com",
      "if0_38503886",
      "StlFnsLkFkx",
      "if0_38503886_hypehumbledb",
      3306
    );
    if ($conn->connect_error) {
      $errors[] = "Connection failed: " . $conn->connect_error;
    } else {
      // Check if email already exists
      $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
      $check_email->bind_param("s", $email);
      $check_email->execute();
      $check_email->store_result();
      if ($check_email->num_rows > 0) {
        $errors[] = "Error: Email already registered.";
      }
      $check_email->close();
      $conn->close();
    }
  }

  if (empty($errors)) {
    $conn = new mysqli(
      "sql106.infinityfree.com",
      "if0_38503886",
      "StlFnsLkFkx",
      "if0_38503886_hypehumbledb",
      3306
    );
    if ($conn->connect_error) {
      $errors[] = "Connection failed: " . $conn->connect_error;
    } else {

      $hashed_password = password_hash($password, PASSWORD_BCRYPT);

      $stmt = $conn->prepare(
        "INSERT INTO users (name, email, password, role,verified)
                 VALUES (?, ?, ?, ?, 1)"
      );
      $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);

      if ($stmt->execute()) {
        $user_id = $stmt->insert_id;

        if ($role === 'provider') {
          $provider_stmt = $conn->prepare("INSERT INTO providers (user_id) VALUES (?)");
          $provider_stmt->bind_param("i", $user_id);
          $provider_stmt->execute();
          $provider_stmt->close();
        }

        $user_stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        $user_data   = $user_result->fetch_assoc();

        $_SESSION['user_id']   = $user_id;
        $_SESSION['name']      = $name;
        $_SESSION['email']     = $email;
        $_SESSION['role']      = $role;
        $_SESSION['user_data'] = $user_data;

        $stmt->close();
        $user_stmt->close();
        $conn->close();

        if ($role === 'provider') {
          header("Location: ../businessManagement/business_user_profile.php?new_registration=1");
        } else {
          header("Location: user_profile.php?new_registration=1");
        }
        exit();
      } else {
        $errors[] = "Error inserting user: " . $stmt->error;
      }

      $stmt->close();
      $conn->close();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Register - Hype &amp; Humble</title>
  <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
  <style>
    :root {
      --bodyBackgroundColor: #eff0f3;
      --headerBackgroundColor: #6a1b9a;
      --textColor: #000000;
      --buttonBackgroundColor: #8e24aa;
      --buttonTextColor: #ffffff;
      --font-stack: Madefor, "Helvetica Neue", Helvetica, Arial, "メイリオ", "meiryo", "ヒラギノ角ゴ pro w3", "hiragino kaku gothic pro", sans-serif;
      --font-weight-regular: 400;
      --font-weight-medium: 530;
      --font-weight-bold: 700;
      --lighterWaveColor: #b39ddb;
      --linkColor: #ffffff;
    }

    body {
      background-color: var(--bodyBackgroundColor);
      font-family: var(--font-stack);
      font-weight: var(--font-weight-regular);
      margin: 0;
      color: var(--textColor);
      min-height: 100vh;
    }

    .container {
      font-size: 14px;
      width: 60%;
      max-width: 500px;
      margin: 60px auto;
      padding: 30px;
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .container h1 {
      text-align: center;
      margin-bottom: 20px;
    }

    .container label,
    input,
    select {
      display: block;
      width: 100%;
      margin-bottom: 15px;
      font-size: 16px;
    }

    .container input,
    select {
      padding: 10px;
      border: 1px solid #ccc;
      border-radius: 4px;
    }

    .container button {
      width: 100%;
      padding: 10px;
      background: var(--buttonBackgroundColor);
      color: var(--buttonTextColor);
      border: none;
      border-radius: 4px;
      font-size: 16px;
      cursor: pointer;
      margin-top: 10px;
    }

    .logo-container {
      text-align: center;
      margin-top: 20px;
    }

    .logo-container img {
      width: 50%;
      max-width: 200px;
    }

    .error-message {
      color: #d32f2f;
      background-color: #fce4e4;
      padding: 0.8rem;
      border-radius: 4px;
      margin-bottom: 1rem;
      text-align: center;
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <?php include __DIR__ . '/../navbar.php'; ?>

  <div class="container">
    <h1>Register</h1>

    <!-- Error messages on same page, styled like update_profile.php -->
    <?php if (!empty($errors)): ?>
      <div class="error-message">
        <?php foreach ($errors as $err): ?>
          <div><?php echo htmlspecialchars($err, ENT_QUOTES); ?></div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form action="registration.php" method="POST">
      <label for="name">Name:</label>
      <input
        type="text"
        id="name"
        name="name"
        required
        value="<?php echo htmlspecialchars($name, ENT_QUOTES); ?>">

      <label for="email">Email:</label>
      <input
        type="email"
        id="email"
        name="email"
        required
        value="<?php echo htmlspecialchars($email, ENT_QUOTES); ?>">

      <label for="password">Password:</label>
      <input type="password" id="password" name="password" required>

      <label for="confirm_password">Confirm Password:</label>
      <input
        type="password"
        id="confirm_password"
        name="confirm_password"
        required>

      <label for="role">I am a:</label>
      <select id="role" name="role">
        <option value="user" <?php echo $role === 'user'     ? 'selected' : ''; ?>>Personal User</option>
        <option value="provider" <?php echo $role === 'provider' ? 'selected' : ''; ?>>Business User</option>
      </select>

      <button type="submit">Register</button>
    </form>
  </div>

  <div class="logo-container">
    <img src="../images/H_and_H_Logo.png" alt="Hype &amp; Humble Logo">
  </div>

  <!-- Footer -->
  <?php include __DIR__ . '/../footer.php'; ?>
</body>

</html>