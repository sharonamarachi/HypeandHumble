<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    die(json_encode(['success' => false, 'message' => 'Unauthorized access']));
}

if (!isset($_GET['service_id']) || !is_numeric($_GET['service_id'])) {
    http_response_code(400);
    die(json_encode(['success' => false, 'message' => 'Invalid service ID']));
}

$service_id = (int)$_GET['service_id'];
$user_id = $_SESSION['user_id'];

$conn = new mysqli(
    "sql106.infinityfree.com",
    "if0_38503886",
    "StlFnsLkFkx",
    "if0_38503886_hypehumbledb",
    3306
);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$stmt = $conn->prepare("
    SELECT s.* 
    FROM services s
    INNER JOIN providers p ON s.provider_id = p.provider_id
    WHERE s.service_id = ? AND p.user_id = ?
");
$stmt->bind_param("ii", $service_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    die(json_encode(['success' => false, 'message' => 'Service not found or access denied']));
}

$service = $result->fetch_assoc();
echo json_encode(['success' => true, 'data' => $service]);

$stmt->close();
$conn->close();
