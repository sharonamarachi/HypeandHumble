<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About Us - Hype & Humble</title>
  <link rel="icon" type="image/png" href="../images/H_and_H_Logo.png">
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #eff0f3;
      font-family: Arial, sans-serif;
      color: #000;
    }

    .container {
      width: 80%;
      max-width: 850px;
      margin: 100px auto 20px auto;
      padding: 40px;
      background-color: white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
      border-radius: 20px;
      text-align: left;
    }


    .logo {
      display: block;
      margin: 20px auto;
      width: 100px;
    }

    h1 {
      text-align: center;
      color: #6a1b9a;
      margin-bottom: 20px;
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
    <h1>About Hype & Humble</h1>
    <div class="section">
      <h2>Our Story</h2>
      <p>Hype & Humble was born out of a simple idea: sometimes you need a boost, and sometimes you need a reality check. We've created a platform that caters to both needs, connecting people with expert motivators and professional roasters.</p>
    </div>
    <div class="section">
      <h2>Our Mission</h2>
      <p>At Hype & Humble, our mission is to provide a unique space where individuals can seek personalized motivation or constructive criticism. We believe in the power of words - both to uplift and to humble - and we're here to facilitate those transformative experiences.</p>
    </div>
    <div class="section">
      <h2>What We Offer</h2>
      <p><strong>Hype Services:</strong> Need a confidence boost? Our hype experts provide motivational messages, custom videos, and personalized theme songs to get you pumped up and ready to conquer your goals.</p>
      <p><strong>Humble Services:</strong> Sometimes, what you need is a dose of reality. Our professional roasters offer constructive criticism and humorous takedowns to help you stay grounded.</p>
    </div>
    <div class="section">
      <h2>Our Community</h2>
      <p>Hype & Humble is more than just a service marketplace—it's a community of individuals seeking growth, laughter, and authentic connections. Whether you're here for a boost or a roast, you're part of a supportive network that values personal development and humor.</p>
    </div>
    <div class="section">
      <h2>Join Us</h2>
      <p>Whether you're looking to get hyped up, humbled down, or to offer your services as a motivator or roaster, Hype & Humble welcomes you. Join our community today and experience the power of words in a whole new way.</p>
    </div>
  </div>

  <!-- Footer -->
  <?php include __DIR__ . '/footer.php'; ?>

</body>

</html>