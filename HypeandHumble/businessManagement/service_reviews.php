<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

if (!isset($_SESSION['user_id'])) {
    die("Please login to access this page.");
}

$service_id = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
if (!$service_id) {
    die("Invalid service ID.");
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

$user_id = $_SESSION['user_id'];
$verify_query = "SELECT s.service_id, s.name 
                 FROM services s
                 JOIN providers p ON s.provider_id = p.provider_id
                 WHERE s.service_id = ? AND p.user_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $service_id, $user_id);
$stmt->execute();
$verify_result = $stmt->get_result();

if ($verify_result->num_rows === 0) {
    die("Service not found or you don't have permission to view it.");
}

$service = $verify_result->fetch_assoc();

$reviews_query = "SELECT r.rating, r.comment, r.created_at, u.name as username
                  FROM reviews r
                  JOIN bookings b ON r.booking_id = b.booking_id
                  JOIN users u ON r.user_id = u.user_id
                  WHERE b.service_id = ?
                  ORDER BY r.created_at DESC";
$stmt = $conn->prepare($reviews_query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$reviews_result = $stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);

$avg_rating_query = "SELECT AVG(r.rating) as avg_rating, COUNT(r.review_id) as review_count
                     FROM reviews r
                     JOIN bookings b ON r.booking_id = b.booking_id
                     WHERE b.service_id = ?";
$stmt = $conn->prepare($avg_rating_query);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$avg_result = $stmt->get_result();
$rating_data = $avg_result->fetch_assoc();
$avg_rating = $rating_data['avg_rating'];
$review_count = $rating_data['review_count'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Reviews - <?= htmlspecialchars($service['name']) ?></title>
    <link rel="stylesheet" href="businessStyle.css">
</head>

<body>
    <?php include __DIR__ . '/business_navbar.php'; ?>

    <main class="container">
        <header class="page-header">
            <h1>Reviews for <?= htmlspecialchars($service['name']) ?></h1>
            <div class="average-rating">
                <?php if ($review_count > 0): ?>
                    Average Rating: <?= number_format((float)$avg_rating, 1) ?> ★ (<?= $review_count ?> reviews)
                <?php else: ?>
                    No ratings yet
                <?php endif; ?>
            </div>
        </header>

        <section class="reviews-list">
            <?php if (count($reviews) > 0): ?>
                <?php foreach ($reviews as $review): ?>
                    <article class="review-card">
                        <div class="review-header">
                            <span class="review-user"><?= htmlspecialchars($review['username']) ?></span>
                            <span class="review-rating"><?= $review['rating'] ?> ★</span>
                            <span class="review-date"><?= date('M j, Y', strtotime($review['created_at'])) ?></span>
                        </div>
                        <div class="review-comment">
                            <?= htmlspecialchars($review['comment']) ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-reviews">No reviews yet for this service.</p>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>