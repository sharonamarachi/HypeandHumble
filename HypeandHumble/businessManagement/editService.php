<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

session_start();

if (!isset($_SESSION['user_id'])) {
  die("Please login to access this page.");
}

$service_id = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);
if (!$service_id) {
  die("<p style='color:red;'>Invalid or missing service_id</p>");
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

$stmt = $conn->prepare("SELECT * FROM services WHERE service_id = ?");
$stmt->bind_param("i", $service_id);
$stmt->execute();
$service = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$service) {
  die("<p style='color:red;'>No service found with ID {$service_id}</p>");
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Edit Service #<?= htmlspecialchars($service_id) ?></title>
  <style>
    .form-container {
      max-width: 600px;
      margin: 2rem auto;
      padding: 1rem;
      background: #fff;
      border: 1px solid #ddd;
      border-radius: 8px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      font-family: sans-serif;
    }

    .form-container h1 {
      text-align: center;
      color: #4a0788;
      margin-bottom: 1.5rem;
    }

    .listing-form {
      display: flex;
      flex-direction: column;
      gap: 1rem;
    }

    .listing-form label {
      font-weight: 600;
      color: #4a0788;
    }

    .listing-form input,
    .listing-form textarea {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 1rem;
    }

    .listing-form button {
      align-self: flex-start;
      padding: 0.75rem 1.5rem;
      background-color: #6a0dad;
      color: #fff;
      border: none;
      border-radius: 4px;
      font-weight: 600;
      cursor: pointer;
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/business_navbar.php'; ?>
  <div class="form-container">
    <a href="buisness_services.php" class="back-link" style="display:inline-block;margin-bottom:1rem;color:#6a0dad;text-decoration:none;">
      ← Back to My Services
    </a>
    <h1>Edit Service</h1>


    <form method="POST" class="listing-form">
      <input type="hidden" name="service_id" value="<?= htmlspecialchars($service_id) ?>">

      <label for="name">Service Name</label>
      <input id="name" type="text" name="name"
        value="<?= htmlspecialchars($service['name']) ?>" required>

      <label for="price">Price (€)</label>
      <input id="price" type="number" step="0.01" name="price"
        value="<?= htmlspecialchars($service['price']) ?>" required>

      <label for="delivery_time_days">Delivery Time (days)</label>
      <input id="delivery_time_days" type="number" name="delivery_time_days"
        value="<?= htmlspecialchars($service['delivery_time_days']) ?>" required>

      <label for="description">Description</label>
      <textarea id="description" name="description" rows="4" required><?=
                                                                      htmlspecialchars($service['description'])
                                                                      ?></textarea>

      <button type="submit">Save Changes</button>
    </form>
  </div>
  <script>
    document.querySelector('.listing-form').addEventListener('submit', async e => {
      e.preventDefault();
      const form = e.target;
      const data = new FormData(form);

      try {
        const resp = await fetch('update_service.php', {
          method: 'POST',
          body: data,
          headers: {
            'Accept': 'application/json'
          }
        });

        if (!resp.ok) {
          throw new Error(`HTTP ${resp.status}`);
        }

        const json = await resp.json();
        if (json.success) {
          alert(json.message);
        } else {
          alert('Error: ' + json.message);
        }
      } catch (err) {
        alert('Request failed: ' + err.message);
      }
    });
  </script>
</body>

</html>