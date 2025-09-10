<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /profileManagement/login.php");
    exit();
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

// Main revenue summary
$sql = "
    SELECT p.amount AS price, b.status
    FROM payments p
    INNER JOIN bookings b ON p.booking_id = b.booking_id
    WHERE p.provider_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();
$payments = $result->fetch_all(MYSQLI_ASSOC);

$total_revenue = 0;
$completed_count = 0;
$accepted_count = 0;

foreach ($payments as $payment) {
    if ($payment['status'] === 'completed' || $payment['status'] === 'accepted') {
        $total_revenue += $payment['price'];
        if ($payment['status'] === 'completed') {
            $completed_count++;
        } else {
            $accepted_count++;
        }
    }
}
$stmt->close();

// Query top performing services
$top_services_sql = "
    SELECT s.name, COUNT(*) AS bookings_count, SUM(p.amount) AS total_revenue
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN payments p ON b.booking_id = p.booking_id
    WHERE (b.status = 'completed' OR b.status = 'accepted') AND p.provider_id = ?
    GROUP BY s.service_id
    ORDER BY total_revenue DESC
    LIMIT 3
";

$stmt = $conn->prepare($top_services_sql);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$top_result = $stmt->get_result();
$top_services = $top_result->fetch_all(MYSQLI_ASSOC);

$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Revenue Summary</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
    <link rel="stylesheet" href="businessStyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .revenue-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin: 2rem;
        }

        .revenue-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }

        .revenue-value {
            font-size: 2rem;
            font-weight: 700;
            color: #3a2a6e;
            margin: 0.5rem 0;
        }

        .revenue-label {
            color: #718096;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .revenue-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .revenue-icon.total {
            background-color: rgba(90, 62, 161, 0.1);
            color: #5a3ea1;
        }

        .revenue-icon.completed {
            background-color: rgba(56, 161, 105, 0.1);
            color: #38a169;
        }

        .revenue-icon.pending {
            background-color: rgba(234, 179, 8, 0.1);
            color: #b7791f;
        }

        .top-services {
            margin: 2rem;
        }

        .top-services h2 {
            font-size: 1.5rem;
            color: #3a2a6e;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/business_navbar.php'; ?>

    <div class="revenue-summary">
        <div class="revenue-card">
            <div class="revenue-icon total">
                <i class="fas fa-coins"></i>
            </div>
            <div class="revenue-value">€<?php echo number_format($total_revenue, 2); ?></div>
            <div class="revenue-label">
                <i class="fas fa-wallet"></i> Total Revenue
            </div>
        </div>

        <div class="revenue-card">
            <div class="revenue-icon completed">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="revenue-value"><?php echo $completed_count; ?></div>
            <div class="revenue-label">
                <i class="fas fa-check"></i> Completed Bookings
            </div>
        </div>

        <div class="revenue-card">
            <div class="revenue-icon pending">
                <i class="fas fa-clock"></i>
            </div>
            <div class="revenue-value"><?php echo $accepted_count; ?></div>
            <div class="revenue-label">
                <i class="fas fa-hourglass-half"></i> Accepted (Pending Completion)
            </div>
        </div>
    </div>

    <div class="top-services">
        <h2>Top Performing Services</h2>
        <?php if (count($top_services) > 0): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem;">
                <?php foreach ($top_services as $service): ?>
                    <div class="revenue-card">
                        <div class="revenue-value" style="font-size: 1.25rem;"><?php echo htmlspecialchars($service['name']); ?></div>
                        <div class="revenue-label">
                            <i class="fas fa-check"></i> <?php echo $service['bookings_count']; ?> Bookings
                        </div>
                        <div class="revenue-label">
                            <i class="fas fa-euro-sign"></i> €<?php echo number_format((float)$service['total_revenue'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="color: #718096;">No completed bookings yet.</p>
        <?php endif; ?>
    </div>

</body>

</html>