<?php

declare(strict_types=1);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['user_id'])) {
  die("Please login to access this page.");
}

$service_id = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
if (!$service_id) {
  die("Invalid service ID.");
}

$conn = new mysqli(
  "sql106.infinityfree.com",
  "if0_38503886",
  "StlFnsLkFkx",
  "if0_38503886_hypehumbledb",
  3306
);
if ($conn->connect_error) {
  die("DB Connection failed: " . $conn->connect_error);
}

$svcStmt = $conn->prepare("SELECT name FROM services WHERE service_id = ?");
$svcStmt->bind_param("i", $service_id);
$svcStmt->execute();
$svcResult = $svcStmt->get_result();
if ($svcRow = $svcResult->fetch_assoc()) {
  $serviceName = $svcRow['name'];
} else {
  $serviceName = "Unknown Service";
}
$svcStmt->close();

// Fetch reviews via reviews → bookings → users
$sql = "
  SELECT
    u.name        AS reviewer_name,
    r.rating      AS rating,
    r.comment     AS comment,
    r.created_at  AS created_at
  FROM reviews r
  JOIN bookings b
    ON r.booking_id = b.booking_id
  JOIN users u
    ON r.user_id = u.user_id
  WHERE b.service_id = ?
  ORDER BY r.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $service_id);
$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Reviews for <?= htmlspecialchars($serviceName) ?></title>
  <style>
   
  </style>
</head>

<body>
  <?php include __DIR__ . '/business_navbar.php'; ?>

  <div class="container">
    <a href="buisness_services.php" class="back-link">← Back to My Services</a>
    <h1>Reviews for “<?= htmlspecialchars($serviceName) ?>”</h1>

    <?php if (!empty($reviews)): ?>
      <div class="reviews-list">
        <?php foreach ($reviews as $rev): ?>
          <div class="review-card">
            <div class="review-header">
              <span class="review-author"><?= htmlspecialchars($rev['reviewer_name']) ?></span>
              <span class="review-rating">⭐ <?= htmlspecialchars($rev['rating']) ?></span>
              <span class="review-date">
                <?= date('M j, Y', strtotime($rev['created_at'])) ?>
              </span>
            </div>
            <div class="review-content">
              <?= nl2br(htmlspecialchars($rev['comment'])) ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p class="no-reviews">No reviews yet for this service.</p>
    <?php endif; ?>
  </div>
</body>

</html>