<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$servername = "sql106.infinityfree.com";
$username = "if0_38503886";
$password = "StlFnsLkFkx";
$dbname = "if0_38503886_hypehumbledb";
$port = 3306;

$conn = new mysqli($servername, $username, $password, $dbname, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$success = '';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$user_id = $_SESSION['user_id'];

if (empty($name) || empty($email)) {
    $_SESSION['error'] = "All fields are required";
    header("Location: business_user_profile.php");
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email format";
    header("Location: business_user_profile.php");
    exit();
}

// Check if email already exists (excluding current user)
$stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
$stmt->bind_param("si", $email, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $_SESSION['error'] = "Email already in use by another account";
    header("Location: business_user_profile.php");
    exit();
}

$stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE user_id = ?");
$stmt->bind_param("ssi", $name, $email, $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Profile updated successfully";
    
    $_SESSION['user_name'] = $name;
    $_SESSION['user_email'] = $email;
} else {
    $_SESSION['error'] = "Error updating profile: " . $conn->error;
}

$stmt->close();
$conn->close();

header("Location: business_user_profile.php");
exit();
?>