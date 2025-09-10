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

// Verify admin status
$adminCheck = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$adminCheck->bind_param("i", $_SESSION['user_id']);
$adminCheck->execute();
$adminResult = $adminCheck->get_result();

if ($adminResult->num_rows === 0) {
    die("User not found in database.");
}

$userData = $adminResult->fetch_assoc();
if ($userData['role'] !== 'admin') {
    die("Access denied. Admin privileges required.");
}
$adminCheck->close();

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    $reviewIdToDelete = (int)$_POST['delete_review_id'];
    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    $stmt->bind_param("i", $reviewIdToDelete);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_reviews.php");
    exit();
}

// Fetch all reviews with user and service names
$reviews = [];
$query = "SELECT r.*, 
          u.name as user_name,
          s.name as service_name,
          b.service_id as service_id
          FROM reviews r
          JOIN users u ON r.user_id = u.user_id
          LEFT JOIN bookings b ON r.booking_id = b.booking_id
          LEFT JOIN services s ON b.service_id = s.service_id
          ORDER BY r.created_at DESC";
$result = $conn->query($query);
if ($result) {
    $reviews = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}
$conn->close();

function displayStars($rating)
{
    // Convert rating to float if it's a string
    $rating = (float)$rating;
    $fullStars = floor($rating);
    $halfStar = ($rating - $fullStars) >= 0.5 ? 1 : 0;
    $emptyStars = 5 - $fullStars - $halfStar;

    $output = '';
    for ($i = 0; $i < $fullStars; $i++) {
        $output .= '<i class="fas fa-star" style="color: gold;"></i>';
    }
    if ($halfStar) {
        $output .= '<i class="fas fa-star-half-alt" style="color: gold;"></i>';
    }
    for ($i = 0; $i < $emptyStars; $i++) {
        $output .= '<i class="far fa-star" style="color: gold;"></i>';
    }
    return $output;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reviews - Hype & Humble</title>
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

        .reviews {
            width: 100%;
            overflow-x: auto;
        }


        .reviews table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .reviews th,
        .reviews td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .reviews th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .reviews tr:hover {
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

        /* Star rating styling */
        .fa-star,
        .fa-star-half-alt {
            color: gold;
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

            .reviews table {
                display: block;
                width: 100%;
            }

            .reviews thead {
                display: none;
            }

            .reviews tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
                padding: 10px;
            }

            .reviews td {
                display: flex;
                justify-content: space-between;
                padding: 12px 15px;
                border-bottom: 1px solid #eee;
                align-items: center;
            }

            .reviews td:last-child {
                border-bottom: none;
            }

            .reviews td::before {
                content: attr(data-label);
                font-weight: bold;
                width: 45%;
                padding-right: 15px;
            }

        }

        footer {
            text-align: center;
            padding: 20px;
            background: #6a1b9a;
            color: white;
            margin-top: 5%;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <h1>Manage Reviews</h1>

        <div class="container">
            <div class="reviews">
                <h2>Customer Reviews</h2>
                <div class="review-controls">
                    <input type="text" id="searchReviews" placeholder="Search reviews..." style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <button id="refreshReviews" class="btn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>

                <table id="reviewTable" class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Service</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td data-label="ID"><?= htmlspecialchars($review['review_id']) ?></td>
                                <td data-label="User"><?= htmlspecialchars($review['user_name']) ?></td>
                                <td data-label="Service">
                                    <?= htmlspecialchars($review['service_name'] ?? 'N/A') ?>
                                </td>
                                <td data-label="Rating">
                                    <?= displayStars($review['rating']) ?>
                                </td>
                                <td data-label="Comment"><?= htmlspecialchars(substr($review['comment'] ?? '', 0, 100)) ?><?= isset($review['comment']) && strlen($review['comment']) > 100 ? '...' : '' ?></td>
                                <td data-label="Date"><?= date('M j, Y', strtotime($review['created_at'])) ?></td>
                                <td data-label="Action">
                                    <button onclick="confirmDelete(<?= (int)$review['review_id'] ?>)" class="btn" title="Delete Review" style="background: transparent; border: none; color: red; cursor: pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <script>
        function confirmDelete(reviewId) {
            if (confirm("Are you sure you want to delete this review?")) {
                const form = document.createElement("form");
                form.method = "POST";
                form.style.display = "none";

                const input = document.createElement("input");
                input.name = "delete_review_id";
                input.value = reviewId;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Live search
        document.getElementById('searchReviews').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#reviewTable tbody tr');

            rows.forEach(row => {
                const comment = row.children[4].textContent.toLowerCase();
                const user = row.children[1].textContent.toLowerCase();
                const service = row.children[2].textContent.toLowerCase();

                if (comment.includes(searchValue) || user.includes(searchValue) || service.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Refresh button
        document.getElementById('refreshReviews').addEventListener('click', function() {
            location.reload();
        });
    </script>

</body>

</html>