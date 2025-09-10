<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Help/FAQ - Hype & Humble</title>
  <link rel="icon" type="image/png" href="../images/H_and_H_Logo.png">
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #eff0f3;
      font-family: Arial, sans-serif;
      color: #000;
    }

    h1 {
      color: #6a1b9a;
      text-align: center;
      margin-bottom: 20px;
    }

    .container {
      width: 80%;
      max-width: 800px;
      margin: 100px auto 20px auto;
      padding: 40px;
      background-color: white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      border-radius: 8px;
      text-align: left;
    }

    .logo {
      display: block;
      margin: 20px auto;
      width: 100px;
    }

    .section {
      margin-bottom: 40px;
    }

    .section h2 {
      color: #6a1b9a;
      margin-bottom: 20px;
    }

    .section p {
      margin-bottom: 10px;
    }
  </style>
</head>

<body>
  <!-- Navbar -->
  <?php include __DIR__ . '/navbar.php'; ?>

  <div class="container">
    <img src="../images/H_and_H_Logo.png" alt="Hype & Humble Logo" class="logo">
    <h1>Help/FAQ</h1>

    <div class="section">
      <h2>General Information</h2>
      <p><strong>What is Hype & Humble?</strong></p>
      <p>Hype & Humble is a dual-sided service marketplace where users can either get hyped up with motivational messages, custom videos, or theme songs OR get roasted mercilessly by professional roasters.</p>
      <p><strong>How does it work?</strong></p>
      <p>Users can sign up to request hype or roast services, and service providers (hype experts and roasters) can offer their services with various pricing tiers.</p>
    </div>

    <div class="section">
      <h2>Account Management</h2>
      <p><strong>How to create an account?</strong></p>
      <p>Click on the "Sign Up" button on the homepage and fill in the required information to create an account.</p>
      <p><strong>How to reset your password?</strong></p>
      <p>Click on the "Forgot Password" link on the login page and follow the instructions to reset your password.</p>
      <p><strong>How to update your profile information?</strong></p>
      <p>Go to your profile page and click on the "Edit Profile" button to update your information.</p>
    </div>

    <div class="section">
      <h2>Service Requests</h2>
      <p><strong>How to request a hype or roast service?</strong></p>
      <p>Browse the available services, select the one you want, and click on the "Request Service" button to submit your request.</p>
      <p><strong>How to communicate with service providers?</strong></p>
      <p>Once your request is accepted, you can use the messaging system to communicate with the service provider and clarify details.</p>
      <p><strong>How to leave a review?</strong></p>
      <p>After the service is completed, go to the service provider's profile page and click on the "Leave a Review" button to submit your feedback.</p>
    </div>

    <div class="section">
      <h2>Payments and Pricing</h2>
      <p><strong>What are the payment options?</strong></p>
      <p>We accept various payment methods, including credit/debit cards and PayPal.</p>
      <p><strong>How to view and manage your transactions?</strong></p>
      <p>Go to your account dashboard and click on the "Transactions" tab to view and manage your transactions.</p>
      <p><strong>How are prices determined?</strong></p>
      <p>Service providers set their own prices based on the type and complexity of the service they offer.</p>
    </div>

    <div class="section">
      <h2>Policies and Guidelines</h2>
      <p><strong>What are the community guidelines?</strong></p>
      <p>Our community guidelines ensure a safe and respectful environment for all users. Please review them on our guidelines page.</p>
      <p><strong>How to report inappropriate content?</strong></p>
      <p>If you encounter any inappropriate content, please use the "Report" button or contact our support team.</p>
      <p><strong>What is the refund policy?</strong></p>
      <p>Refunds are handled on a case-by-case basis. Please refer to our refund policy page for more details.</p>
    </div>
  </div>

  <!-- Footer -->
  <?php include __DIR__ . '/footer.php'; ?>
</body>

</html>