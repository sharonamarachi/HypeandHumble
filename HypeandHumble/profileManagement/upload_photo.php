<?php

declare(strict_types=1);
session_start();

// make sure the user is logged in & is a provider
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['filename']) && $_FILES['filename']['error'] === UPLOAD_ERR_OK) {
    $tmp  = $_FILES['filename']['tmp_name'];
    $type = mime_content_type($tmp);

    // allow only common image types
    if (!in_array($type, ['image/jpeg', 'image/png', 'image/gif'], true)) {
        die('Only JPG, PNG, or GIF files are allowed.');
    }

    // a unique filename
    $ext       = strtolower(pathinfo($_FILES['filename']['name'], PATHINFO_EXTENSION));
    $newName   = sprintf('avatar_%d_%d.%s', $_SESSION['user_id'], time(), $ext);
    $uploadDir = __DIR__ . '/uploads/avatars/';

    // ensure upload directory exists
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        die('Failed to create upload directory.');
    }

    $dest = $uploadDir . $newName;
    if (!move_uploaded_file($tmp, $dest)) {
        die('Failed to move uploaded file.');
    }

    // store 
    $relativePath = 'hypeandhumble.wuaze.com/profileManagement/uploads/avatars/' . $newName;


    $conn = new mysqli(
        'sql106.infinityfree.com',
        'if0_38503886',
        'StlFnsLkFkx',
        'if0_38503886_hypehumbledb',
        3306
    );
    if ($conn->connect_error) {
        die('DB connection failed: ' . $conn->connect_error);
    }

    $stmt = $conn->prepare('UPDATE users SET avatar_path = ? WHERE user_id = ?');
    $stmt->bind_param('si', $relativePath, $_SESSION['user_id']);
    if (!$stmt->execute()) {
        error_log('Avatar update failed: ' . $stmt->error);
        die('Failed to update database.');
    }
    $stmt->close();
    $conn->close();
}

// redirect back to profile
header('Location: user_profile.php');
exit;
