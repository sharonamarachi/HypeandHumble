<?php

declare(strict_types=1);
session_start();

// Ensure the user is logged in.
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in";
    exit();
}

// Get the provider ID and the user-supplied query.
$providerId = isset($_POST['provider_id']) ? (int)$_POST['provider_id'] : 0;
$query      = isset($_POST['query']) ? trim($_POST['query']) : '';
$serviceId = isset($_POST['service_id']) ? (int)$_POST['service_id'] : 0;

if ($providerId === 0 || $query === '') {
    echo "Invalid input data.";
    exit();
}

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
    echo "Database connection failed";
    exit();
}

$userId = $_SESSION['user_id'];
$conn->begin_transaction();

try {
    // Retrieve the provider's user_id.
    $stmt = $conn->prepare("SELECT user_id FROM providers WHERE provider_id = ?");
    $stmt->bind_param("i", $providerId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("No provider found for provider_id = {$providerId}");
    }

    $row = $result->fetch_assoc();
    $providerUserId = (int)$row['user_id'];
    $stmt->close();

    // Retrieve the service name based on the service_id using a statement named $serviceStmt.
    $serviceStmt = $conn->prepare("SELECT name FROM services WHERE service_id = ?");
    if (!$serviceStmt) {
        die("Prepare failed: " . $conn->error);
    }
    $serviceStmt->bind_param("i", $serviceId);
    $serviceStmt->execute();
    $result = $serviceStmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $serviceName = $row['name'];
    } else {
        $serviceName = "Unknown Service";
    }
    $serviceStmt->close();

    $subject = "Query " . $serviceName;

    $stmtChat = $conn->prepare("
        INSERT INTO conversations (created_at, status, subject)
        VALUES (NOW(), 'pending', ?)
    ");
    $stmtChat->bind_param("s", $subject);
    if (!$stmtChat->execute()) {
        throw new Exception("Error inserting into conversations: " . $stmtChat->error);
    }
    $chatId = $stmtChat->insert_id;
    $stmtChat->close();

    // Insert the customer into chat_participants.
    $roleCustomer = "customer";
    $stmtPart1 = $conn->prepare("
        INSERT INTO chat_participants (chat_id, user_id, role, joined_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmtPart1->bind_param("iis", $chatId, $userId, $roleCustomer);
    if (!$stmtPart1->execute()) {
        throw new Exception("Error inserting chat participant (customer): " . $stmtPart1->error);
    }
    $stmtPart1->close();

    // Insert the provider into chat_participants.
    $roleBusiness = "business";
    $stmtPart2 = $conn->prepare("
        INSERT INTO chat_participants (chat_id, user_id, role, joined_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmtPart2->bind_param("iis", $chatId, $providerUserId, $roleBusiness);
    if (!$stmtPart2->execute()) {
        throw new Exception("Error inserting chat participant (provider): " . $stmtPart2->error);
    }
    $stmtPart2->close();

    $stmtMsg = $conn->prepare("
        INSERT INTO messages (chat_id, sender_id, content, sent_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmtMsg->bind_param("iis", $chatId, $userId, $query);
    if (!$stmtMsg->execute()) {
        throw new Exception("Error inserting message: " . $stmtMsg->error);
    }
    $stmtMsg->close();

    $conn->commit();
    $conn->close();

    header("Location: message_handler/user_message_portal.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $conn->close();
    error_log($e->getMessage());
    echo "An error occurred: " . $e->getMessage();
    exit();
}
