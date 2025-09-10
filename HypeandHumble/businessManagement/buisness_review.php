<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

// Check if user is logged in
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

// Get provider_id for the logged-in user
$user_id = $_SESSION['user_id'];
$provider_query = "SELECT provider_id FROM providers WHERE user_id = ?";
$stmt = $conn->prepare($provider_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$provider_result = $stmt->get_result();

if ($provider_result->num_rows === 0) {
    die("You are not registered as a provider.");
}

$provider = $provider_result->fetch_assoc();
$provider_id = $provider['provider_id'];

// Get overall review statistics
$stats_query = "SELECT 
                COUNT(r.review_id) as total_reviews,
                AVG(r.rating) as avg_rating,
                MIN(r.rating) as min_rating,
                MAX(r.rating) as max_rating,
                SUM(CASE WHEN r.rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN r.rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN r.rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN r.rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN r.rating = 1 THEN 1 ELSE 0 END) as one_star
                FROM reviews r
                JOIN bookings b ON r.booking_id = b.booking_id
                JOIN services s ON b.service_id = s.service_id
                WHERE s.provider_id = ?";
$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$stats_result = $stmt->get_result();
$stats = $stats_result->fetch_assoc();

// Get recent reviews
$recent_reviews_query = "SELECT r.rating, r.comment, r.created_at, u.name as username, s.name as service_name
                        FROM reviews r
                        JOIN bookings b ON r.booking_id = b.booking_id
                        JOIN services s ON b.service_id = s.service_id
                        JOIN users u ON r.user_id = u.user_id
                        WHERE s.provider_id = ?
                        ORDER BY r.created_at DESC
                        LIMIT 5";
$stmt = $conn->prepare($recent_reviews_query);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$recent_reviews_result = $stmt->get_result();
$recent_reviews = $recent_reviews_result->fetch_all(MYSQLI_ASSOC);

// Get rating distribution by service
$service_distribution_query = "SELECT s.name as service_name, 
                              AVG(r.rating) as avg_rating,
                              COUNT(r.review_id) as review_count
                              FROM reviews r
                              JOIN bookings b ON r.booking_id = b.booking_id
                              JOIN services s ON b.service_id = s.service_id
                              WHERE s.provider_id = ?
                              GROUP BY s.service_id
                              ORDER BY avg_rating DESC";
$stmt = $conn->prepare($service_distribution_query);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$service_distribution_result = $stmt->get_result();
$service_distribution = $service_distribution_result->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reviews Dashboard | Business Portal</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
    <link rel="stylesheet" href="businessStyle.css">
    <style>
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            padding: 20px;
        }

        .metric-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.5rem;
        }

        .rating-distribution {
            margin-top: 15px;
        }

        .rating-bar {
            height: 20px;
            background: #ecf0f1;
            border-radius: 10px;
            margin: 5px 0;
            overflow: hidden;
        }

        .rating-fill {
            height: 100%;
            background: #3498db;
        }


        .service-distribution {
            grid-column: 1 / -1;
        }

        .service-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .no-reviews {
            text-align: center;
            padding: 40px;
            color: #7f8c8d;
        }
    </style>
</head>

<body>
    <?php include __DIR__ . '/business_navbar.php'; ?>

    <main class="container">
        <header class="page-header">
            <h1>Reviews Dashboard</h1>
        </header>

        <?php if ($stats['total_reviews'] > 0): ?>
            <div class="dashboard-container">
                <!-- Overall Rating -->
                <div class="metric-card">
                    <h2>Overall Rating</h2>
                    <div class="metric-value">
                        <?= number_format((float)$stats['avg_rating'], 1) ?>
                        <span class="rating-stars">★</span>
                    </div>
                    <p>Based on <?= $stats['total_reviews'] ?> reviews</p>
                </div>

                <!-- Rating Card -->
                <div class="metric-card">
                    <h2>Rating Range</h2>
                    <div class="metric-value">
                        <?= number_format((float)$stats['min_rating'], 1) ?> - <?= number_format((float)$stats['max_rating'], 1) ?>
                    </div>
                    <p>Lowest to highest rating</p>
                </div>

                <!-- Rating Card -->
                <div class="metric-card">
                    <h2>Rating Distribution</h2>
                    <div class="rating-distribution">
                        <div>5 ★ (<?= $stats['five_star'] ?>)</div>
                        <div class="rating-bar">
                            <div class="rating-fill" style="width: <?= ($stats['five_star'] / $stats['total_reviews']) * 100 ?>%"></div>
                        </div>

                        <div>4 ★ (<?= $stats['four_star'] ?>)</div>
                        <div class="rating-bar">
                            <div class="rating-fill" style="width: <?= ($stats['four_star'] / $stats['total_reviews']) * 100 ?>%"></div>
                        </div>

                        <div>3 ★ (<?= $stats['three_star'] ?>)</div>
                        <div class="rating-bar">
                            <div class="rating-fill" style="width: <?= ($stats['three_star'] / $stats['total_reviews']) * 100 ?>%"></div>
                        </div>

                        <div>2 ★ (<?= $stats['two_star'] ?>)</div>
                        <div class="rating-bar">
                            <div class="rating-fill" style="width: <?= ($stats['two_star'] / $stats['total_reviews']) * 100 ?>%"></div>
                        </div>

                        <div>1 ★ (<?= $stats['one_star'] ?>)</div>
                        <div class="rating-bar">
                            <div class="rating-fill" style="width: <?= ($stats['one_star'] / $stats['total_reviews']) * 100 ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Service Section -->
                <div class="metric-card service-distribution">
                    <h2>Ratings by Service</h2>
                    <?php foreach ($service_distribution as $service): ?>
                        <div class="service-row">
                            <div><?= htmlspecialchars($service['service_name']) ?></div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="rating-stars"><?= number_format((float)$service['avg_rating'], 1) ?> ★</span>
                                <span>(<?= $service['review_count'] ?> reviews)</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="no-reviews">
                <h2>No Reviews Yet</h2>
                <p>You haven't received any reviews for your services yet.</p>
            </div>
        <?php endif; ?>
    </main>
</body>

</html>