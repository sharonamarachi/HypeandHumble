<?php
declare(strict_types=1);

ini_set('display_errors','0');
ini_set('display_startup_errors','0');
error_reporting(E_ALL);
header('Content-Type: application/json');

session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Unauthorized']);
    exit;
}

$service_id          = filter_input(INPUT_POST, 'service_id',          FILTER_VALIDATE_INT);
$name                = trim($_POST['name']         ?? '');
$price               = filter_input(INPUT_POST, 'price',               FILTER_VALIDATE_FLOAT);
$delivery_time_days  = filter_input(INPUT_POST, 'delivery_time_days',  FILTER_VALIDATE_INT);
$description         = trim($_POST['description'] ?? '');

if (
    !$service_id ||
    $name === '' ||
    $price === false ||
    $delivery_time_days === false ||
    $description === ''
) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Invalid input']);
    exit;
}

$conn = new mysqli(
    "sql106.infinityfree.com",
    "if0_38503886",
    "StlFnsLkFkx",
    "if0_38503886_hypehumbledb",
    3306
);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Database connection failed']);
    exit;
}

$stmt = $conn->prepare("
    UPDATE services
    SET name               = ?,
        price              = ?,
        delivery_time_days = ?,
        description        = ?
    WHERE service_id = ?
");
$stmt->bind_param(
    'sdisi',
    $name,
    $price,
    $delivery_time_days,
    $description,
    $service_id
);

if ($stmt->execute()) {
    echo json_encode(['success'=>true,'message'=>'Service updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>'Update failed: '.$stmt->error]);
}

$stmt->close();
$conn->close();
exit;
