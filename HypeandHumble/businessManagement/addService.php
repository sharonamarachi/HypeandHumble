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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli($servername, $username, $password_db, $database, $port);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Get the provider_id for the logged-in user
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

    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category = $_POST['category'];
    $delivery_time = intval($_POST['delivery_time_days'] ?? 1);

    $insert_query = "INSERT INTO services (provider_id, name, description, price, service_type, delivery_time_days, status) 
                     VALUES (?, ?, ?, ?, ?, ?, 'active')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("issdsi", $provider_id, $name, $description, $price, $category, $delivery_time);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        header("Location: buisness_services.php");
        exit();
    } else {
        $error = "Error adding service: " . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Service</title>
    <link rel="stylesheet" href="businessStyle.css">
</head>

<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/business_navbar.php'; ?>

    <!-- New Listing -->
    <div class="container">
        <h1>Add New Service</h1>

        <?php if (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="listing-form" action="addService.php" method="POST">
            <label for="name">Service Name</label>
            <input type="text" id="name" name="name" required>

            <label for="description">Description</label>
            <textarea id="description" name="description" required></textarea>

            <label for="price">Price (€)</label>
            <input type="number" id="price" name="price" min="0" step="1" required>

            <label for="delivery_time_days">Delivery Time (days)</label>
            <input type="number" id="delivery_time_days" name="delivery_time_days" min="1" value="1" required>

            <label for="category">Category</label>
            <select id="category" name="category" required>
                <option value="Hype">Hype</option>
                <option value="Humble">Humble</option>
            </select>
            <button type="submit">Add Service</button>
        </form>
    </div>
</body>

</html>