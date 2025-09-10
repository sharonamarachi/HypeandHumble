<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

if (!isset($_GET['id'])) {
  header("Location: /search/search.php");
  exit();
}

$service_id = (int)$_GET['id'];

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get service details
$stmt = $conn->prepare("
    SELECT 
        s.*,
        p.provider_id,
        u.name AS company_name,
        u.user_id AS company_id,
        COALESCE((
            SELECT AVG(r.rating)
            FROM reviews r
            JOIN bookings b ON r.booking_id = b.booking_id
            WHERE b.service_id = s.service_id
        ), 0) AS avg_rating
    FROM services s
    JOIN providers p ON s.provider_id = p.provider_id
    JOIN users u ON p.user_id = u.user_id
    WHERE s.service_id = ? AND s.status = 'active'
");
$stmt->bind_param("i", $service_id);
$stmt->execute();
if (!$stmt->execute()) {
  error_log("Error executing service query: " . $stmt->error);
  header("Location: /search/search.php");
  exit();
}
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: search/search.php");
  exit();
}

$service = $result->fetch_assoc();

$reviews_stmt = $conn->prepare("
    SELECT r.*, u.name AS user_name 
    FROM reviews r
    JOIN bookings b ON r.booking_id = b.booking_id
    JOIN users u ON r.user_id = u.user_id
    WHERE b.service_id = ?
    ORDER BY r.created_at DESC
    LIMIT 3
");
$reviews_stmt->bind_param("i", $service_id);
$reviews_stmt->execute();

$reviews_result = $reviews_stmt->get_result();
$reviews = $reviews_result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$reviews_stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($service['name']) ?> | Hype & Humble</title>
  <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
  <style>
    body {
      background-color: #f4f4f9;
      font-family: "Arial", sans-serif;
      color: #333;
      margin: 0;
      padding: 0;
    }

    .container {
      display: flex;
      gap: 40px;
      margin: 40px auto;
      width: 85%;
      margin-left: 8%;
    }

    .content {
      width: 60%;
      padding: 25px;
      background: #ffffff;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .button-group {
      margin-top: 15px;
    }

    .hype-btn,
    .humble-btn {
      display: inline-block;
      padding: 8px 15px;
      border-radius: 50px;
      font-weight: bold;
      text-align: center;
      background-color: #6a5acd;
      color: white;
      cursor: default;
      transition: background 0.3s;
    }

    .humble-btn {
      background-color: #ff6347;
    }

    .hype-btn:hover,
    .humble-btn:hover {
      cursor: default;
    }

    .review-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-top: 30px;
    }

    .reviews-btn {
      background-color: #6a5acd;
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 15px;
      font-weight: bold;
      transition: background 0.3s;
      height: max-content;
      cursor: default;
    }

    .review-cards {
      margin-top: 15px;
    }

    .review-card {
      display: flex;
      align-items: center;
      background: #e8e8ff;
      padding: 15px;
      border-radius: 15px;
      margin-top: 15px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    }

    .user-icon {
      width: 40px;
      height: 40px;
      background: #5a3ea1;
      color: white;
      text-align: center;
      line-height: 40px;
      font-weight: bold;
      border-radius: 50%;
      margin-right: 15px;
    }

    @media (max-width: 550px) {
      .user-icon {
        display: none;
      }
    }

    .service-card {
      width: 35%;
      height: max-content;
      padding: 20px;
      background: #ffffff;
      border-radius: 12px;
      text-align: center;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }

    .service-card img {
      width: 100%;
      height: auto;
      border-radius: 12px;
    }

    .provider-info {
      margin: 1rem 0;
      padding: 0.8rem;
      background: #f8f8ff;
      border-radius: 8px;
      text-align: center;
    }

    .provider-name {
      font-weight: bold;
      color: #5a3ea1;
      margin-top: 0.5rem;
      margin-bottom: 0.5rem;
    }

    .provider-label {
      font-size: 0.9rem;
      color: #666;
    }

    .pay-btn,
    .query-input,
    .chat-btn {
      width: 100%;
      margin: 10px 0;
      padding: 12px;
      border: none;
      border-radius: 12px;
    }

    .pay-btn {
      background-color: #6a5acd;
      color: white;
      cursor: pointer;
      font-weight: bold;
      transition: background 0.3s;
    }

    .pay-btn:hover {
      background-color: #5a3ea1;
    }

    .query-input {
      border: 1px solid #5a3ea1;
      min-height: 100px;
      padding: 10px;
      resize: vertical;
    }

    .chat-btn {
      background-color: #6a5acd;
      color: white;
      cursor: pointer;
      font-weight: bold;
    }

    .chat-btn:hover {
      background-color: #5a3ea1;
    }


    .request-details-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }


    .modal-content {
      background: white;
      padding: 2rem;
      border-radius: 12px;
      width: 90%;
      max-width: 500px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.5rem;
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #666;
    }

    .request-form textarea {
      width: 100%;
      min-height: 150px;
      padding: 1rem;
      border: 2px solid #e8e8ff;
      border-radius: 10px;
      margin-top: 1rem;
      margin-bottom: 1.5rem;
      resize: vertical;
      font-family: inherit;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .request-form label {
      display: block;
      text-align: left;
      margin-bottom: 0.8rem;
      color: #555;
      font-weight: 500;
      width: 100%;
    }

    .submit-request {
      background-color: #6a5acd;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      width: 100%;
    }

    .login-alert {
      color: #5a3ea1;
      background-color: #f3f0ff;
      padding: 1rem;
      border-radius: 8px;
      margin: 1.5rem 0;
      text-align: center;
      border-left: 2px solid #6a5acd;
      box-shadow: 0 2px 8px rgba(106, 90, 205, 0.1);
      gap: 10px;
    }

    .disabled-feature {
      opacity: 0.7;
      position: relative;
    }

    .disabled-feature::after {
      content: "";
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(255, 255, 255, 0.5);
      cursor: not-allowed;
    }

    @media (max-width: 768px) {
      .container {
        flex-direction: column;
        width: 95%;
        margin-left: 2.5%;
        gap: 20px;
      }

      .content,
      .service-card {
        width: auto;
      }

      .service-card {
        margin-top: 20px;
      }
    }


    @media (max-width: 450px) {
      .user-icon {
        display: none;
      }

      .review-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
      }

    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/../navbar.php'; ?>

  <?php if (isset($_GET['duplicate'])): ?>
    <div style="padding: 15px; background: #ffdddd; color: #900; border-radius: 8px; margin: 20px 8%; max-width: 85%;">
      ⚠️ You already have a booking for this service.
    </div>
  <?php endif; ?>

  <div class="container">
    <div class="content">
      <h1><?= htmlspecialchars($service['name']) ?></h1>

      <div class="button-group">
        <?php
        $serviceType = isset($service['service_type']) ? $service['service_type'] : 'Humble';
        if ($serviceType == 'Hype') {
          echo '<span class="hype-btn">Hype 🤪</span>';
        } else {
          echo '<span class="humble-btn">Humble 🌝</span>';
        }
        ?>
      </div>

      <h2>Description</h2>
      <p><?= nl2br(htmlspecialchars($service['description'])) ?></p>

      <h2>About This Company</h2>
      <p><?= htmlspecialchars($service['company_name']) ?> is a trusted provider on our platform.</p>

      <div class="review-header">
        <h2>Reviews</h2>
        <button class="reviews-btn">
          ⭐ <?= number_format((float)$service['avg_rating'], 1) ?> (<?= count($reviews) ?> reviews)
        </button>
      </div>

      <div class="review-cards">
        <?php if (!empty($reviews)): ?>
          <?php foreach ($reviews as $review): ?>
            <div class="review-card">
              <div class="user-icon"><?= strtoupper(substr($review['user_name'], 0, 1)) ?></div>
              <div class="review-content">
                <p><strong><?= htmlspecialchars($review['user_name']) ?></strong> – <?= (int)$review['rating'] ?>/5
                  <?php for ($i = 1; $i <= (int)$review['rating']; $i++): ?>
                    <span style="color: gold;">&#9733;</span>
                  <?php endfor; ?>
                </p>
                <p>"<?= htmlspecialchars($review['comment']) ?>"</p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p>No reviews yet. Be the first to review this service!</p>
        <?php endif; ?>
      </div>
    </div>

    <div class="service-card">

      <h2><?= htmlspecialchars($service['name']) ?></h2>

      <div class="provider-info">
        <div class="provider-label">Service Provided By</div>
        <div class="provider-name"><?= htmlspecialchars($service['company_name']) ?></div>
      </div>

      <?php if (isset($_SESSION['user_id'])): ?>
        <!-- Logged in user - all options -->
        <button id="openPaymentModal" class="pay-btn">
          Pay €<?= number_format((float)$service['price'], 2) ?>
        </button>

        <!-- Request Details Modal -->
        <div id="requestModal" class="request-details-modal">
          <div class="modal-content">
            <div class="modal-header">
              <h3>Customize Your Request</h3>
              <button class="close-modal">&times;</button>
            </div>
            <form id="requestForm" class="request-form" action="process_payment.php" method="POST">
              <input type="hidden" name="service_id" value="<?= $service['service_id'] ?>">
              <input type="hidden" name="provider_id" value="<?= $service['provider_id'] ?>">
              <input type="hidden" name="amount" value="<?= $service['price'] ?>">
              <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">

              <label for="request_details">Please provide details about your request:</label>
              <textarea name="request_details" id="request_details"
                placeholder="Example: I need this service on June 15th with specific requirements..."></textarea>

              <button type="submit" class="submit-request">Submit Request</button>
            </form>
          </div>
        </div>

        <form action="process_messages.php" method="POST">
          <input type="hidden" name="provider_id" value="<?= $service['provider_id'] ?>">
          <input type="hidden" name="service_id" value="<?= $service['service_id'] ?>">
          <textarea name="query" class="query-input" placeholder="Hi I have a query!"></textarea>
          <button type="submit" class="chat-btn">Contact via Chat</button>
        </form>

        <form action="past_users.php" method="POST">
          <input type="hidden" name="service_id" value="<?= $service['service_id'] ?>">
          <button type="submit" class="chat-btn">Chat with past customers</button>
        </form>

        <form action="report_service.php" method="POST">
          <input type="hidden" name="service_id" value="<?= $service['service_id'] ?>">
          <button type="submit" class="chat-btn">Report Service</button>
        </form>

      <?php else: ?>
        <!-- Not logged in -->
        <div class="login-alert">
          Please login to access payment and messaging features
        </div>

        <div class="disabled-feature">
          <form>
            <button type="button" class="pay-btn" disabled>
              Pay €<?= number_format((float)$service['price'], 2) ?>
            </button>
          </form>
        </div>

        <div class="disabled-feature">
          <form>
            <textarea class="query-input" placeholder="Login to contact provider" disabled></textarea>
            <button type="button" class="chat-btn" disabled>Contact via Chat</button>
          </form>
        </div>

        <div class="disabled-feature">
          <form>
            <button type="button" class="chat-btn" disabled>Chat with past customers</button>
          </form>
        </div>

        <div style="text-align: center; margin-top: 20px;">
          <a href="../profileManagement/login.php>"
            style="color: #6a5acd; font-weight: bold; text-decoration: underline;">
            Login to access all features
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <?php include __DIR__ . '/../footer.php'; ?>


  <script>
    // Modal 
    const modal = document.getElementById('requestModal');
    const openBtn = document.getElementById('openPaymentModal');
    const closeBtn = document.querySelector('.close-modal');

    openBtn.addEventListener('click', () => {
      modal.style.display = 'flex';
    });

    closeBtn.addEventListener('click', () => {
      modal.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.style.display = 'none';
      }
    });
  </script>

</body>

</html>