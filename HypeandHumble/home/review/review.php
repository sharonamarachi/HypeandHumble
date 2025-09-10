<?php
session_start();

// Database connection parameters
$servername = "sql106.infinityfree.com";
$username = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database = "if0_38503886_hypehumbledb";
$port = 3306;

// Create connection
$conn = new mysqli($servername, $username, $password_db, $database, $port);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'error' => 'Database connection failed']));
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_name = "Guest";

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $sql = "SELECT * FROM `users` WHERE `user_id` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_name = htmlspecialchars($user['name']);
    } else {
        $is_logged_in = false;
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_logged_in) {
    $user_id = $_SESSION['user_id'];
    $service_id = (int)$_POST['service'];
    $rating = (int)$_POST['rating'];
    $comment = trim($_POST['message']);
    $provider_id = null;

    if ($service_id <= 0 || $rating <= 0 || $rating > 5 || empty($comment)) {
        $error = "Please fill in all fields correctly.";
    } else {
        $stmt = $conn->prepare("
            SELECT s.provider_id 
            FROM services s 
            JOIN bookings b ON s.service_id = b.service_id
            WHERE s.service_id = ? 
            AND b.user_id = ?
            AND b.status = 'completed'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $service_id, $user_id);
        $stmt->execute();
        $stmt->bind_result($provider_id);
        $stmt->fetch();
        $stmt->close();

        if (!$provider_id) {
            $error = "No completed booking found for this service.";
        } else {
            $stmt = $conn->prepare("
                INSERT INTO reviews (booking_id, user_id, provider_id, rating, comment, created_at)
                VALUES (
                    (SELECT booking_id FROM bookings 
                     WHERE user_id = ? AND service_id = ? AND status = 'completed' 
                     ORDER BY created_at DESC LIMIT 1),
                    ?, ?, ?, ?, NOW()
                )
            ");
            $stmt->bind_param("iiiiss", $user_id, $service_id, $user_id, $provider_id, $rating, $comment);

            if ($stmt->execute()) {
                $success = true;
                header("Location: review.php?success=1");
                exit();
            } else {
                $error = "Could not save review.";
            }

            $stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Write a Review - Hype & Humble</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style> 
    :root {
      --bodyBackgroundColor: #f8f9fa;
      --headerBackgroundColor: #ffffff;
      --textColor: #333333;
      --buttonBackgroundColor: #5e35b1;
      --buttonHoverColor: #4527a0;
      --buttonTextColor: #ffffff;
      --formBackgroundColor: #ffffff;
      --formBorderColor: #e0e0e0;
      --accentColor: #673ab7;
      --lightAccent: #f3f1f9;
      --errorColor: #d32f2f;
      --successColor: #388e3c;
      --font-stack: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      --font-weight-regular: 400;
      --font-weight-medium: 500;
      --font-weight-bold: 600;
      --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
      --transition: all 0.3s ease;
    }

    body {
      font-family: var(--font-stack);
      background-color: var(--bodyBackgroundColor);
      color: var(--textColor);
      line-height: 1.6;
      margin: 0;
      padding: 0;
    }

    .container {
      width: 100%;
      max-width: 1200px;
      margin: 40px auto;
      padding: 0 20px;
      box-sizing: border-box;
    }

    .review-title {
      font-size: 2.2rem;
      color: var(--accentColor);
      text-align: center;
      margin-bottom: 30px;
      font-weight: var(--font-weight-medium);
      position: relative;
      padding-bottom: 15px;
    }

    .review-title:after {
      content: '';
      position: absolute;
      bottom: 0;
      left: 50%;
      transform: translateX(-50%);
      width: 80px;
      height: 3px;
      background-color: var(--accentColor);
    }

    .review-content {
      display: flex;
      flex-wrap: wrap;
      justify-content: space-between;
      gap: 40px;
      align-items: flex-start;
    }

    .review-image-section {
      flex: 1;
      min-width: 300px;
      background-color: var(--formBackgroundColor);
      padding: 25px;
      border-radius: 12px;
      box-shadow: var(--box-shadow);
      text-align: center;
    }

    .review-image {
      width: 100%;
      max-width: 320px;
      height: auto;
      border-radius: 8px;
      display: block;
      margin: 0 auto 20px;
      object-fit: cover;
      aspect-ratio: 16/9;
      border: 1px solid var(--formBorderColor);
    }

    .disclaimer {
      font-size: 0.9em;
      color: #666;
      max-width: 320px;
      margin: 20px auto 0;
      padding: 12px;
      background-color: var(--lightAccent);
      border-radius: 6px;
      border-left: 4px solid var(--accentColor);
    }

    .review-form {
      flex: 1;
      min-width: 300px;
      background-color: var(--formBackgroundColor);
      padding: 30px;
      border-radius: 12px;
      box-shadow: var(--box-shadow);
      text-align: left;
      border: 1px solid var(--formBorderColor);
    }

    .form-group {
      margin-bottom: 20px;
    }

    .review-form label {
      display: block;
      font-weight: var(--font-weight-medium);
      margin-bottom: 8px;
      font-size: 16px;
      color: #555;
    }

    .review-form input,
    .review-form textarea,
    .review-form select {
      width: 100%;
      padding: 12px 15px;
      margin-top: 5px;
      border: 1px solid var(--formBorderColor);
      border-radius: 6px;
      font-family: var(--font-stack);
      font-size: 15px;
      transition: var(--transition);
      box-sizing: border-box;
    }

    .review-form input:focus,
    .review-form textarea:focus,
    .review-form select:focus {
      outline: none;
      border-color: var(--accentColor);
      box-shadow: 0 0 0 2px rgba(103, 58, 183, 0.2);
    }

    .review-form textarea {
      resize: vertical;
      min-height: 120px;
    }

    .stars-container {
      margin: 20px 0;
      text-align: center;
    }

    .stars-title {
      font-size: 16px;
      margin-bottom: 15px;
      color: #555;
    }

    .stars {
      display: flex;
      justify-content: center;
      gap: 5px;
      margin-bottom: 15px;
    }

    .star {
      font-size: 32px;
      cursor: pointer;
      color: #ddd;
      transition: var(--transition);
    }

    .star:hover,
    .star.active {
      color: #ffc107;
      transform: scale(1.1);
    }

    .rating-text {
      font-size: 14px;
      color: #666;
      font-weight: var(--font-weight-medium);
    }

    .button {
      background-color: var(--buttonBackgroundColor);
      color: var(--buttonTextColor);
      padding: 12px 25px;
      border: none;
      border-radius: 6px;
      font-weight: var(--font-weight-medium);
      cursor: pointer;
      transition: var(--transition);
      font-size: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .button:hover {
      background-color: var(--buttonHoverColor);
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .button:active {
      transform: translateY(0);
    }

    .button i {
      font-size: 14px;
    }

    .review-buttons {
      display: flex;
      justify-content: space-between;
      margin-top: 30px;
    }

    .back-button {
      background-color: #f5f5f5;
      color: #333;
    }

    .back-button:hover {
      background-color: #e0e0e0;
    }

    .submit-button {
      background-color: var(--buttonBackgroundColor);
    }

    .submit-button:hover {
      background-color: var(--buttonHoverColor);
    }

    /* Form validation styles */
    .error-message {
      color: var(--errorColor);
      font-size: 13px;
      margin-top: 5px;
      display: none;
    }

    .input-error {
      border-color: var(--errorColor) !important;
    }

    .success-message {
      display: none;
      background-color: rgba(56, 142, 60, 0.1);
      color: var(--successColor);
      padding: 15px;
      border-radius: 6px;
      margin-top: 20px;
      text-align: center;
      border-left: 4px solid var(--successColor);
    }

    @media (max-width: 768px) {
      .review-content {
        flex-direction: column;
        gap: 30px;
      }

      .review-image-section,
      .review-form {
        width: 100%;
      }

      .review-buttons {
        flex-direction: column;
        gap: 12px;
      }

      .button {
        width: 100%;
      }
    }

    /* Loading spinner */
    .spinner {
      display: none;
      width: 20px;
      height: 20px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-radius: 50%;
      border-top-color: #fff;
      animation: spin 1s ease-in-out infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .guest-message {
    animation: fadeIn 0.5s ease-out;
}
@keyframes fadeIn {
    from { opacity: 0; transform: translateY(20px); }
    to { opacity: 1; transform: translateY(0); }
}
.review-icon {
  font-size: 60px;
  color: #5e35b1;
  margin-bottom: 20px;
  text-align: center;
}
    </style>
  </head>
  <body>
    <!-- Navbar -->
    <?php include __DIR__ . '/../../navbar.php'; ?>

    <!-- Review Content -->
    <div class="container">
        <?php if ($is_logged_in): ?>
            <h1 class="review-title">Share Your Experience</h1>
      <div class="review-content">
        <div class="review-image-section">
        <div class="review-icon">
            <i class="fas fa-edit"></i>
        </div>
        <p>Your honest feedback helps others make better decisions and helps us improve our services.</p>
        <p class="disclaimer">
            <i class="fas fa-info-circle"></i> By submitting this review, you confirm it's based on a genuine
            experience and you haven't received an incentive to write it.
        </p>
        </div>
        <form class="review-form" action="review.php"  method="POST" id="reviewForm">
          <div class="form-group">
            <label for="service">Service Name</label>
            <select id="service" name="service" required>
  <option value="" disabled selected>Select a service</option>
  <?php
    $conn = new mysqli($servername, $username, $password_db, $database, $port);
    $stmt = $conn->prepare("
        SELECT s.service_id, s.name, u.name AS provider_name
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN users u ON s.provider_id = u.user_id
        WHERE b.user_id = ? AND b.status = 'completed'
        GROUP BY s.service_id
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        echo "<option value='" . $row['service_id'] . "' data-company='" . htmlspecialchars($row['provider_name']) . "'>" . htmlspecialchars($row['name']) . "</option>";
    }
    $stmt->close();
    $conn->close();
  ?>
</select>

            <div class="error-message" id="service-error">Please select a service</div>
          </div>

          <div class="form-group">
            <label for="company">Company</label>
            <input
              type="text"
              id="company"
              name="company"
              placeholder="Company name"
              required readonly
            />
            <div class="error-message" id="company-error">Please enter a company name</div>
          </div>

          <div class="form-group">
            <div class="stars-container">
              <div class="stars-title">Your Rating</div>
              <div class="stars">
                <span data-rating="1" class="star"><i class="far fa-star"></i></span>
                <span data-rating="2" class="star"><i class="far fa-star"></i></span>
                <span data-rating="3" class="star"><i class="far fa-star"></i></span>
                <span data-rating="4" class="star"><i class="far fa-star"></i></span>
                <span data-rating="5" class="star"><i class="far fa-star"></i></span>
              </div>
              <div class="rating-text" id="rating-text">Select your rating</div>
              <input type="hidden" id="rating" name="rating" value="0" required>
              <div class="error-message" id="rating-error">Please select a rating</div>
            </div>
          </div>

          <div class="form-group">
            <label for="message">Your Review</label>
            <textarea
              id="message"
              name="message"
              placeholder="Share details of your experience..."
              required
            ></textarea>
            <div class="error-message" id="message-error">Please write your review</div>
          </div>

          <div class="success-message" id="success-message">
            <i class="fas fa-check-circle"></i> Thank you for your review! It has been submitted successfully.
          </div>

          <div class="review-buttons">
            <button type="button" class="button back-button">
              <i class="fas fa-arrow-left"></i> Back
            </button>
            <button type="submit" class="button submit-button">
              Submit Review
            </button>
          </div>
        </form>
        <?php if (isset($_GET['success']) || !empty($success)): ?>
    <div class="success-message" style="display: block;">
        <i class="fas fa-check-circle"></i> Thank you for your review! It has been submitted successfully.
    </div>
<?php endif; ?>

      </div>
        <?php else: ?>
            <!-- registration prompt for guests -->
            <div class="guest-message" style="text-align: center; padding: 40px; background: white; border-radius: 12px; box-shadow: var(--box-shadow); max-width: 600px; margin: 0 auto;">
                <h2 style="color: var(--accentColor); margin-bottom: 20px;">
                    <i class="fas fa-lock" style="margin-right: 10px;"></i>Members Only
                </h2>
                <p style="font-size: 18px; margin-bottom: 30px;">
                    You need to be a registered member to write reviews.
                </p>
                <div style="display: flex; gap: 20px; justify-content: center;">
                    <a href="/profileManagement/registration.php" 
                       style="background-color: var(--buttonBackgroundColor); color: white; padding: 12px 25px; border-radius: 6px; text-decoration: none; font-weight: 500; transition: var(--transition);">
                        <i class="fas fa-user-plus"></i> Register
                    </a>
                </div>
                <p style="margin-top: 30px; font-size: 14px; color: #666;">
                    Already have an account? <a href="/profileManagement/login.php" style="color: var(--accentColor);">Sign in</a> to share your experience.
                </p>
            </div>
        <?php endif; ?>
    </div>


    <!-- Footer -->
    <?php include __DIR__ . '/../../footer.php'; ?>




        <script>

                document.getElementById('service').addEventListener('change', function () {
  const selectedOption = this.options[this.selectedIndex];
  const companyName = selectedOption.getAttribute('data-company');
  document.getElementById('company').value = companyName;
  
});
// Star rating functionality
const stars = document.querySelectorAll('.star');
const ratingInput = document.getElementById('rating');
const ratingText = document.getElementById('rating-text');

stars.forEach(star => {
    star.addEventListener('click', function() {
        const rating = this.getAttribute('data-rating');
        ratingInput.value = rating;
        
        // Update star display
        stars.forEach((s, index) => {
            if (index < rating) {
                s.innerHTML = '<i class="fas fa-star"></i>';
                s.classList.add('active');
            } else {
                s.innerHTML = '<i class="far fa-star"></i>';
                s.classList.remove('active');
            }
        });
        
        // Update rating text
        const ratingTexts = [
            'Select your rating',
            '1/5 - Poor',
            '2/5 - Alright I Guess',
            '3/4 - Good',
            '4/5 - I See You',
            '5/5 - Hip Hip Hurray'
        ];
        ratingText.textContent = ratingTexts[rating];
    });
});


// Back button functionality
document.querySelector('.back-button').addEventListener('click', function() {
    window.history.back();
});

    </script>
  </body>
</html>




