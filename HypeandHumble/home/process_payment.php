<?php

declare(strict_types=1);
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'];
    $service_id  = (int)($_POST['service_id'] ?? 0);
    $provider_id = (int)($_POST['provider_id'] ?? 0);
    $amount      = (float)($_POST['amount'] ?? 0);
    $request_details = $_POST['request_details'] ?? '';

    if ($service_id <= 0 || $provider_id <= 0 || $amount <= 0) {
        header("Location: /search/search.php");
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

    $existing_stmt = $conn->prepare("
        SELECT booking_id FROM bookings
        WHERE user_id = ? AND service_id = ? AND status IN ('pending', 'accepted')
    ");
    $existing_stmt->bind_param("ii", $user_id, $service_id);
    $existing_stmt->execute();
    $existing_stmt->store_result();

    if ($existing_stmt->num_rows > 0) {
        $existing_stmt->close();
        $conn->close();
        header("Location: card.php?id=" . $service_id . "&duplicate=1");
        exit();
    }
    $existing_stmt->close();

    $booking_stmt = $conn->prepare("
        INSERT INTO bookings (user_id, provider_id, service_id, status, request_details, created_at)
        VALUES (?, ?, ?, 'pending', ?, NOW())
    ");
    $booking_stmt->bind_param("iiis", $user_id, $provider_id, $service_id, $request_details);
    if (!$booking_stmt->execute()) {
        die("Booking creation failed: " . $conn->error);
    }
    $booking_id = $conn->insert_id;
    $booking_stmt->close();

    $payment_stmt = $conn->prepare("
        INSERT INTO payments (booking_id, user_id, provider_id, amount, status, created_at)
        VALUES (?, ?, ?, ?, 'completed', NOW())
    ");
    $payment_stmt->bind_param("iiid", $booking_id, $user_id, $provider_id, $amount);
    if (!$payment_stmt->execute()) {
        die("Payment processing failed: " . $conn->error);
    }

    $payment_stmt->close();
    $conn->close();

    // Redirect to dashboard
    header("Location: userDashboard.php");
    exit();
}

header("Location: /search/search.php");
exit();
