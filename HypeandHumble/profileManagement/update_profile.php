<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];
$name  = trim($_POST['name']  ?? '');
$email = trim($_POST['email'] ?? '');
$errors = [];

if ($name === '') {
    $errors[] = "Name cannot be empty.";
}
if ($email === '') {
    $errors[] = "Email cannot be empty.";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Please enter a valid email address.";
}

if (!empty($errors)) {
    $_SESSION['profile_errors'] = $errors;
    $_SESSION['profile_old']    = ['name' => $name, 'email' => $email];
    header("Location: user_profile.php");
    exit();
}

//Update
$stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
$stmt->bind_param("ssi", $name, $email, $user_id);
if (!$stmt->execute()) {
    die("Error updating profile: " . $stmt->error);
}
$stmt->close();
$conn->close();

$_SESSION['user_data']['name']  = $name;
$_SESSION['user_data']['email'] = $email;
header("Location: user_profile.php?updated=1");
exit();
