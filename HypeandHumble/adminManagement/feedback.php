<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please login to access this page.");
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

$adminCheck = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$adminCheck->bind_param("i", $_SESSION['user_id']);
$adminCheck->execute();
$adminResult = $adminCheck->get_result();

if ($adminResult->num_rows === 0) {
    die("User not found in database.");
}


$feedbacks = [];
$sql = "SELECT f.user_id, u.name, f.message, f.created_at 
        FROM feedback f
        LEFT JOIN users u ON f.user_id = u.user_id
        ORDER BY f.created_at DESC";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $feedbacks = $result->fetch_all(MYSQLI_ASSOC);
}


$conn->close();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Feedback - Hype & Humble</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eff0f3;
            margin: 0;
            padding: 0;
        }

        h1 {
            color: #6a1b9a;
        }

        .main {
            margin-left: 330px;
        }

        .container {
            width: 90%;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 20px;
        }

        .feedbacks {
            width: 100%;
            overflow-x: auto;
        }


        .feedbacks table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .feedbacks th,
        .feedbacks td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .feedbacks th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .feedbacks tr:hover {
            background-color: #f5f5f5;
        }

        .btn {
            background-color: #8e24aa;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #a23bcf;
        }



        /* Responsive styles */
        @media (max-width: 768px) {
            .main {
                margin-left: 0;
            }

            .container {
                width: 95%;
                margin: 0 auto;
                padding: 15px;
            }

            .review-controls {
                flex-direction: column;
                gap: 10px;
            }

            .feedbacks table {
                display: block;
                width: 100%;
            }

            .feedbacks thead {
                display: none;
            }

            .feedbacks tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
                padding: 10px;
            }

            .feedbacks td {
                display: flex;
                justify-content: space-between;
                padding: 12px 15px;
                border-bottom: 1px solid #eee;
                align-items: center;
            }

            .feedbacks td:last-child {
                border-bottom: none;
            }

            .feedbacks td::before {
                content: attr(data-label);
                font-weight: bold;
                width: 45%;
                padding-right: 15px;
            }

        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <h1>Manage Feedback</h1>

        <div class="container">
            <div class="feedbacks">
                <h2>Customer Feedback</h2>
                <div class="feedback-controls">
                    <input type="text" id="searchFeedback" placeholder="Search feedback..." style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <button id="refreshFeedback" class="btn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>

                <table id="feedbackTable" class="table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Name</th>
                            <th>Feedback</th>
                            <th>Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($feedbacks)) : ?>
                            <?php foreach ($feedbacks as $feedback): ?>
                                <tr>
                                    <td data-label="User ID">
                                        <?= htmlspecialchars((string)($feedback['user_id'] ?? 'Unknown'), ENT_QUOTES) ?>
                                    </td>

                                    <td data-label="Name">
                                        <?= htmlspecialchars($feedback['name'] ?? 'Unknown', ENT_QUOTES) ?>
                                    </td>

                                    <td data-label="Feedback">
                                        <?= htmlspecialchars($feedback['message'] ?? '', ENT_QUOTES) ?>
                                    </td>

                                    <td data-label="Created At">
                                        <?= htmlspecialchars($feedback['created_at'] ?? '', ENT_QUOTES) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                        <?php else : ?>
                            <tr>
                                <td colspan="4">No feedback available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('searchFeedback').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#feedbackTable tbody tr');

            rows.forEach(row => {
                const userId = row.cells[0].textContent.toLowerCase();
                const message = row.cells[1].textContent.toLowerCase();
                const date = row.cells[2].textContent.toLowerCase();

                if (userId.includes(searchValue) || message.includes(searchValue) || date.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        document.getElementById('refreshFeedback').addEventListener('click', function() {
            location.reload();
        });
    </script>

</body>

</html>