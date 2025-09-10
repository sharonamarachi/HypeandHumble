<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /profileManagement/login.php");
    exit();
}

$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($_GET['id']) || !is_numeric($_GET['id'])) {
        die('Invalid service ID.');
    }
    $service_id = (int) $_GET['id'];
} else {
    if (empty($_POST['service_id']) || !is_numeric($_POST['service_id'])) {
        die('Invalid service ID.');
    }
    $service_id = (int) $_POST['service_id'];
}

$conn = new mysqli(
    "sql106.infinityfree.com",
    "if0_38503886",
    "StlFnsLkFkx",
    "if0_38503886_hypehumbledb",
    3306
);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare(
    "SELECT s.service_id,
            s.name AS service_name,
            p.provider_id,
            u.user_id AS provider_user_id,
            u.name AS provider_name
     FROM services s
     JOIN providers p ON s.provider_id = p.provider_id
     JOIN users u     ON p.user_id    = u.user_id
     WHERE s.service_id = ? AND s.status = 'active'"
);
$stmt->bind_param('i', $service_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die('Service not found or inactive.');
}
$service = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') {
    } else {
        $reporter_id      = (int) $_SESSION['user_id'];
        $reported_user_id = (int) $service['provider_user_id'];
        $insert = $conn->prepare(
            "INSERT INTO adminreports
             (reporter_id, reported_user_id, service_id, report_type, reason, status, created_at)
             VALUES (?, ?, ?, 'r_service', ?, 'pending', NOW())"
        );
        $insert->bind_param(
            'iiis',
            $reporter_id,
            $reported_user_id,
            $service_id,
            $reason
        );
        if ($insert->execute()) {
            $successMsg = '✅ Report submitted successfully. We will review it shortly.';
        } else {
            $errorMsg = '❌ Failed to submit report. Please try again.';
            error_log('Report insert failed: ' . $insert->error);
        }
        $insert->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Service | Hype & Humble</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary: #6a5acd;
            --danger: #e53e3e;
            --light: #f8f9fa;
            --white: #ffffff;
            --dark: #333333;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--light);
            color: var(--dark);
            margin: 0;
        }

        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }

        .card {
            background: var(--white);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        h1 {
            color: var(--danger);
            text-align: center;
            margin-bottom: 20px;
        }

        .service-info {
            background: #f3f1f9;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        textarea {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            min-height: 150px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }

        .btn-danger {
            background: var(--danger);
            color: white;
            border: none;
        }

        .btn-secondary {
            background: #f0f0f0;
            color: var(--dark);
            border: none;
        }

        .text-center {
            text-align: center;
        }

        .mt-3 {
            margin-top: 1rem;
        }

        .alert {
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/../navbar.php'; ?>
    <div class="container">
        <div class="card">
            <h1><i class="fas fa-flag"></i> Report Service</h1>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $errorMsg !== ''): ?>
                <div class="alert alert-error"><?= htmlspecialchars($errorMsg, ENT_QUOTES) ?></div>
            <?php endif; ?>
            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $successMsg !== ''): ?>
                <div class="alert alert-success"><?= htmlspecialchars($successMsg, ENT_QUOTES) ?></div>
            <?php endif; ?>

            <div class="service-info">
                <p><strong>Service:</strong> <?= htmlspecialchars($service['service_name'], ENT_QUOTES) ?></p>
                <p><strong>Provider:</strong> <?= htmlspecialchars($service['provider_name'], ENT_QUOTES) ?></p>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="service_id" value="<?= $service_id ?>">
                <div class="form-group">
                    <label for="reason">Reason for report:</label>
                    <textarea name="reason" id="reason" required placeholder="Please provide details about your concern..."><?php echo htmlspecialchars($_POST['reason'] ?? '', ENT_QUOTES); ?></textarea>
                </div>
                <div class="text-center">
                    <button type="button" class="btn btn-secondary back-btn" onclick="history.back()">
                        <i class="fas fa-arrow-left"></i> Back
                    </button>

                    <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane"></i> Submit Report</button>
                </div>
            </form>

            <p class="mt-3 text-center" style="color: #666; font-size: 14px;"><i class="fas fa-info-circle"></i> All reports are reviewed by our team.</p>
        </div>
    </div>
    <?php include __DIR__ . '/../footer.php'; ?>
</body>

</html>