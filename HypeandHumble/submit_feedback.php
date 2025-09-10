<?php
session_start();

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'You must be logged in to submit a report.';
    exit;
}
$reporter_id = (int) $_SESSION['user_id'];

$reason = trim($_POST['feedback'] ?? '');
if ($reason === '') {
    http_response_code(400);
    echo 'Please write something before you submit.';
    exit;
}

$mysqli = new mysqli($servername, $username, $password_db, $database, $port);
if ($mysqli->connect_errno) {
    http_response_code(500);
    echo 'Connection failed.';
    error_log("DB connect error: {$mysqli->connect_error}");
    exit;
}

$stmt = $mysqli->prepare(
    "INSERT INTO adminreports (reporter_id, reason) VALUES (?, ?)"
);
$stmt->bind_param('is', $reporter_id, $reason);

if ($stmt->execute()) {
    http_response_code(200);
    echo 'Report submitted!';
} else {
    http_response_code(500);
    echo 'Sorry, something went wrong. Please try again later.';
    error_log('AdminReports insert error: ' . $stmt->error);
}

$stmt->close();
$mysqli->close();
?>
