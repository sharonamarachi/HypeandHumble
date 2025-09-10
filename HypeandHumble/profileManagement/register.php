<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash(trim($_POST['password']), PASSWORD_BCRYPT);
    $role = trim($_POST['role']);

    $allowed_roles = ['user', 'provider', 'admin'];
    if (!in_array($role, $allowed_roles)) {
        die("Invalid role selection.");
    }

    $servername = "sql106.infinityfree.com";
    $username = "if0_38503886";
    $dbpassword = "StlFnsLkFkx";
    $dbname = "if0_38503886_hypehumbledb";

    $conn = new mysqli($servername, $username, $dbpassword, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    if (!$check_email) {
        die("Prepare failed: " . $conn->error);
    }

    $check_email->bind_param("s", $email);
    $check_email->execute();
    $check_email->store_result();

    if ($check_email->num_rows > 0) {
        die("Error: Email already registered.");
    }
    $check_email->close();

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssss", $name, $email, $password, $role);

    if ($stmt->execute()) {
        header("Location: user_profile.php");
        exit();
    } else {
        echo "Error inserting user: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
