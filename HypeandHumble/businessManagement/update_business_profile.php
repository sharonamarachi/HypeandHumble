<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;
$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name      = trim($_POST['name']     ?? '');
$email     = trim($_POST['email']    ?? '');
$specialty = trim($_POST['specialty'] ?? '');
$bio       = trim($_POST['bio']      ?? '');

$errors = [];
if ($name === '')      $errors[] = "Business name cannot be empty.";
if ($email === '')     $errors[] = "Email cannot be empty.";
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
    $errors[] = "Please enter a valid email address.";
if ($specialty === '') $errors[] = "Please choose a specialty.";
if ($bio === '')       $errors[] = "Business bio cannot be empty.";

if ($errors) {
    $_SESSION['profile_errors'] = $errors;
    $_SESSION['profile_old']    = compact('name', 'email', 'specialty', 'bio');
    header("Location: business_user_profile.php");
    exit();
}

$stmt = $conn->prepare("UPDATE users SET name=?, email=? WHERE user_id=?");
$stmt->bind_param("ssi", $name, $email, $user_id);
if (!$stmt->execute()) {
    die("Error updating users: " . $stmt->error);
}
$stmt->close();

// check if exists
$stmt = $conn->prepare("SELECT provider_id FROM providers WHERE user_id=?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($pid);
$exists = $stmt->fetch();
$stmt->close();

if ($exists) {
    $up = $conn->prepare("UPDATE providers SET specialty=?, bio=? WHERE user_id=?");
    $up->bind_param("ssi", $specialty, $bio, $user_id);
    if (!$up->execute()) {
        die("Error updating provider: " . $up->error);
    }
    $up->close();
} else {
    $ins = $conn->prepare("
      INSERT INTO providers (user_id, specialty, bio, created_at)
      VALUES (?, ?, ?, NOW())
    ");
    $ins->bind_param("iss", $user_id, $specialty, $bio);
    if (!$ins->execute()) {
        die("Error inserting provider: " . $ins->error);
    }
    $ins->close();
}

$conn->close();

$_SESSION['user_data']['name']  = $name;
$_SESSION['user_data']['email'] = $email;

header("Location: business_user_profile.php?updated=1");
exit();
