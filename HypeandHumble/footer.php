<?php

$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

if (!isset($saveOK)) {
  $saveOK = false;
}
$statusMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (empty($_SESSION['user_id'])) {
  } else {
    $reporter_id = (int) $_SESSION['user_id'];
    $reason      = trim($_POST['feedback'] ?? '');

    if ($reason === '') {
    } else {
      $mysqli = new mysqli($servername, $username, $password_db, $database, $port);
      if ($mysqli->connect_errno) {
        $statusMsg = '❌ Database connection failed.';
        error_log("DB connect error: {$mysqli->connect_error}");
      } else {
        $stmt = $mysqli->prepare(
          "INSERT INTO feedback (user_id, message) VALUES (?, ?)"
        );
        $stmt->bind_param('is', $reporter_id, $reason);

        if ($stmt->execute()) {
          $statusMsg = '✅ Thank you! Your feedback has been sent.';
        } else {
          $statusMsg = '❌ Sorry, something went wrong. Try again later.';
          error_log('AdminReports insert error: ' . $stmt->error);
        }

        $stmt->close();
        $mysqli->close();
      }
    }
  }
}
?>
<style>
  /* Footer Styles */
  .footer {
    background: linear-gradient(135deg, #4a3a7a 0%, #2e234d 100%);
    color: #ffffff;
    padding: 40px 0 20px;
    font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
    position: relative;
    margin-top: 80px;
  }

  .footer-container {
    display: flex;
    justify-content: space-between;
    flex-wrap: wrap;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
    gap: 30px;
  }

  .footer-section {
    flex: 1;
    min-width: 220px;
    margin-bottom: 20px;
  }

  .footer-section h3 {
    margin-bottom: 20px;
    font-size: 18px;
    font-weight: 600;
    color: #ffffff;
    position: relative;
    padding-bottom: 10px;
  }

  .footer-section h3::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    width: 50px;
    height: 2px;
    background-color: #a78bfa;
  }

  .footer-section p {
    color: #d1d5db;
    line-height: 1.6;
    margin-bottom: 15px;
  }

  .footer-section ul {
    list-style: none;
    padding: 0;
    margin: 0;
  }

  .footer-section ul li {
    margin-bottom: 12px;
    position: relative;
    padding-left: 20px;
  }

  .footer-section ul li::before {
    content: ">";
    position: absolute;
    left: 0;
    color: #a78bfa;
    font-weight: bold;
  }

  .footer-section ul li a {
    text-decoration: none;
    color: #d1d5db;
    transition: all 0.3s ease;
  }

  .footer-section ul li a:hover {
    color: #a78bfa;
    padding-left: 5px;
  }

  .feedback-form {
    display: flex;
    flex-direction: column;
  }

  .feedback-input {
    width: 100%;
    min-height: 100px;
    padding: 12px;
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 6px;
    resize: vertical;
    margin-bottom: 12px;
    background-color: rgba(255, 255, 255, 0.05);
    color: #ffffff;
    transition: all 0.3s ease;
  }

  .feedback-input:focus {
    outline: none;
    border-color: #a78bfa;
    box-shadow: 0 0 0 2px rgba(167, 139, 250, 0.2);
  }

  .feedback-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
  }

  .feedback-btn {
    background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    align-self: flex-start;
  }

  .feedback-btn:hover {
    background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
  }

  /* Footer Bottom */
  .footer-bottom {
    text-align: center;
    margin-top: 40px;
    padding-top: 20px;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    font-size: 13px;
    color: rgba(255, 255, 255, 0.7);
  }

  @media (max-width: 768px) {
    .footer-container {
      flex-direction: column;
      gap: 30px;
    }

    .footer-section {
      min-width: 100%;
    }

    .feedback-btn {
      width: 100%;
    }
  }
</style>

<footer class="footer">
  <div class="footer-container">
    <div class="footer-section">
      <h3>Hype & Humble</h3>
      <p>Bringing innovation and engagement to digital experiences through creative solutions.</p>
    </div>

    <div class="footer-section">
      <h3>Quick Links</h3>
      <ul>
        <li><a href="/index.html">Homepage</a></li>
        <li><a href="/about.php">About Us</a></li>
        <li><a href="/terms_service.php">Terms of Service</a></li>
      </ul>
    </div>

    <div class="footer-section">
      <h3>Support</h3>
      <ul>
        <li><a href="/help_FAQ.php">FAQs</a></li>
        <li><a href="/home/message_handler/user_message_portal.php">Live Chat</a></li>
      </ul>
    </div>

    <div class="footer-section">
      <h3>Feedback</h3>
      <p>We value your input to help us improve our services.</p>

      <form id="feedback-form" method="POST" action="" class="feedback-form">
        <textarea
          id="feedback-input"
          name="feedback"
          placeholder="Your feedback helps us improve..."
          class="feedback-input"
          required><?php
                    if (isset($saveOK) && !$saveOK && $_SERVER['REQUEST_METHOD'] === 'POST')
                      echo htmlspecialchars($_POST['feedback'] ?? '', ENT_QUOTES);
                    ?></textarea>

        <button type="submit" class="feedback-btn">Submit Feedback</button>
      </form>

      <!-- status message -->
      <?php if ($statusMsg !== ''): ?>
        <p id="feedback-status" style="margin-top:.5em; color:#a78bfa;">
          <?= htmlspecialchars($statusMsg, ENT_QUOTES) ?>
        </p>
      <?php endif ?>

    </div>
  </div>


  <div class="footer-bottom">
    <p>&copy; <?php echo date("Y"); ?> Hype & Humble. All rights reserved. | Version 2.1.0</p>
  </div>
</footer>