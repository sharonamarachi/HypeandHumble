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

// Query to fetch active services for this provider
$services_query = "SELECT * FROM services WHERE provider_id = ? AND status = 'active'";
$stmt = $conn->prepare($services_query);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$services = $result->fetch_all(MYSQLI_ASSOC);

// Close statement
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en" class="no-js">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Edit your business profile and manage services">
    <title>My Services | Business Dashboard</title>
    <link rel="stylesheet" href="businessStyle.css">
</head>

<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/business_navbar.php'; ?>

    <!-- Main Content -->
    <main id="main-content" class="container">
        <header class="page-header">
            <h1 class="page-title">My Services</h1>
            <button class="btn btn-primary add-btn" aria-label="Add new service" onclick="location.href='addService.php';">
                <span class="btn-icon">+</span> Add Service
            </button>
        </header>

        <section class="services-list" aria-label="List of services">
            <?php if (count($services) > 0): ?>
                <?php foreach ($services as $service): ?>
                    <article class="service-card" data-service-id="<?= $service['service_id'] ?>">
                        <div class="service-content">
                            <h2 class="service-title"><?= htmlspecialchars($service['name']) ?></h2>
                            <div class="service-meta">
                                <span class="service-price">Price: €<?= $service['price'] ?></span>
                                <span class="service-payments">Delivery time: <?= $service['delivery_time_days'] ?> days</span>
                            </div>
                            <p class="service-description"><?= htmlspecialchars($service['description']) ?></p>
                        </div>
                        <div class="service-actions">
                            <button class="btn btn-secondary edit-button">Edit</button>
                            <button class="btn btn-outline review-button">Reviews</button>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-services">You haven't added any services yet. Click "Add Service" to get started!</p>
            <?php endif; ?>
        </section>
    </main>

    <script>
        document.querySelectorAll('.service-card').forEach(card => {
            const serviceId = card.dataset.serviceId;

            card.querySelector('.edit-button').addEventListener('click', () => {
                window.location.href = `editService.php?service_id=${serviceId}`;
            });

            card.querySelector('.review-button').addEventListener('click', () => {
                window.location.href = `serviceReviews.php?service_id=${serviceId}`;
            });
        });
    </script>
</body>

</html>