<?php
session_start();

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
$user_name = '';
$user_query = $conn->prepare("SELECT name FROM users WHERE user_id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
if ($user = $user_result->fetch_assoc()) {
    $user_name = htmlspecialchars($user['name']);
}

$bookings = [
    'pending' => [],
    'accepted' => [],
    'completed' => [],
    'rejected' => []
];

$booking_query = $conn->prepare("
    SELECT 
        b.*, 
        s.service_id AS service_id, 
        s.name AS service_name, 
        s.price, 
        s.description, 
        s.delivery_time_days,
        u.name AS provider_name,
        u.user_id AS provider_user_id,
        s.provider_id AS service_provider_id
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN providers pr ON b.provider_id = pr.provider_id
    JOIN users u ON pr.user_id = u.user_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC
");

$booking_query->bind_param("i", $user_id);
$booking_query->execute();
$booking_result = $booking_query->get_result();

while ($booking = $booking_result->fetch_assoc()) {
    $status = $booking['status'];
    $bookings[$status][] = $booking;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings | HypeHumble</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/main.css">
    <style>
        /* Base Styles */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-greeting {
            font-size: 1.5rem;
            color: #6a5acd;
            margin-bottom: 1.5rem;
            font-weight: 600;
        }

        .page-title {
            font-size: 2rem;
            color: #4c1d95;
            margin-bottom: 0;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: #6a5acd;
            color: white;
        }

        .btn-primary:hover {
            background-color: #4c1d95;
            transform: translateY(-2px);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
        }

        .btn-success {
            background-color: #38a169;
            color: white;
        }

        .btn-danger {
            background-color: #e53e3e;
            color: white;
        }

        .btn-info {
            background-color: #3182ce;
            color: white;
        }

        .badge {
            display: inline-block;
            padding: 0.25em 0.6em;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.2);
        }

        /* Status Tabs */
        .status-tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 2rem;
            overflow-x: auto;
            padding-bottom: 0.5rem;
            scrollbar-width: none;
        }

        .status-tabs::-webkit-scrollbar {
            display: none;
        }

        .status-tab {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            white-space: nowrap;
            background: #f0f0f7;
            color: #333;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .status-tab.active {
            background: #6a5acd;
            color: white;
        }

        .status-tab:not(.active):hover {
            background: #e9ecef;
        }

        /* Status Sections */
        .status-section {
            display: none;
            animation: fadeIn 0.3s ease;
        }

        .status-section.active {
            display: block;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .bookings-container {
            width: 100%;
        }

        .booking-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .booking-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .booking-header {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .booking-header.pending {
            background: linear-gradient(135deg, #a78bfa, #6a5acd);
        }

        .booking-header.accepted {
            background: linear-gradient(135deg, #68d391, #38a169);
        }

        .booking-header.completed {
            background: linear-gradient(135deg, #63b3ed, #3182ce);
        }

        .booking-header.rejected {
            background: linear-gradient(135deg, #fc8181, #e53e3e);
        }

        .booking-id {
            font-weight: 600;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.875rem;
        }

        .booking-status {
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .booking-body {
            padding: 1.5rem;
        }

        .booking-title {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: #4c1d95;
            font-weight: 600;
        }

        .booking-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            font-size: 0.875rem;
        }

        .booking-price {
            font-weight: 600;
            color: #38a169;
        }

        .booking-delivery {
            color: #666;
        }

        .booking-description {
            color: #666;
            margin-bottom: 1.5rem;
            line-height: 1.6;
            font-size: 1rem;
        }

        .booking-provider {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e9ecef;
        }

        .provider-info {
            flex: 1;
        }

        .provider-name {
            font-weight: 500;
        }

        .provider-label {
            font-size: 0.875rem;
            color: #666;
        }

        .booking-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #666;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #e9ecef;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.25rem;
            color: #333;
        }

        /* Responsive  */
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .page-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .booking-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .status-tabs {
                gap: 0.25rem;
            }

            .status-tab {
                padding: 0.25rem 0.5rem;
            }

            .booking-body {
                padding: 1rem;
            }
        }
    </style>

</head>

<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/../navbar.php'; ?>

    <!-- Main Content -->
    <main class="container">
        <h1 class="page-greeting">Welcome back, <?php echo $user_name; ?></h1>

        <div class="page-header">
            <h1 class="page-title">My Bookings</h1>
            <button class="btn btn-primary" onclick="window.location.href='search/search.php'">
                <i class="fas fa-plus"></i> Book New Service
            </button>
        </div>

        <!-- Status Tabs -->
        <div class="status-tabs">
            <div class="status-tab active" data-tab="pending">
                Pending <span class="badge"><?php echo count($bookings['pending']); ?></span>
            </div>
            <div class="status-tab" data-tab="accepted">
                Accepted <span class="badge"><?php echo count($bookings['accepted']); ?></span>
            </div>
            <div class="status-tab" data-tab="completed">
                Completed <span class="badge"><?php echo count($bookings['completed']); ?></span>
            </div>
            <div class="status-tab" data-tab="rejected">
                Rejected <span class="badge"><?php echo count($bookings['rejected']); ?></span>
            </div>
        </div>

        <!-- Booking Sections -->
        <div class="bookings-container">
            <!-- Pending Bookings -->
            <div class="status-section active" id="pending-section">
                <?php if (!empty($bookings['pending'])): ?>
                    <?php foreach ($bookings['pending'] as $booking): ?>
                        <div class="booking-card">
                            <div class="booking-header pending">
                                <span class="booking-id">Booking #<?php echo $booking['booking_id']; ?></span>
                                <span class="booking-status"><?php echo $booking['status']; ?></span>
                            </div>
                            <div class="booking-body">
                                <h3 class="booking-title"><?php echo htmlspecialchars($booking['service_name']); ?></h3>
                                <div class="booking-meta">
                                    <span class="booking-price">€<?php echo number_format($booking['price'], 2); ?></span>
                                    <span class="booking-delivery">Est. <?php echo $booking['delivery_time_days'] ?? 'N/A'; ?> days</span>
                                </div>
                                <p class="booking-description"><?php echo htmlspecialchars($booking['description']); ?></p>

                                <div class="booking-provider">
                                    <div class="provider-info">
                                        <div class="provider-name"><?php echo htmlspecialchars($booking['provider_name']); ?></div>
                                        <div class="provider-label">Service Provider</div>
                                    </div>
                                </div>

                                <div class="booking-actions">
                                    <button class="btn btn-info" onclick="viewDetails(<?php echo $booking['service_id']; ?>)">
                                        <i class="fas fa-info-circle"></i> Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clock"></i>
                        <h3>No Pending Bookings</h3>
                        <p>You don't have any pending service bookings</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Accepted Bookings -->
            <div class="status-section" id="accepted-section">
                <?php if (!empty($bookings['accepted'])): ?>
                    <?php foreach ($bookings['accepted'] as $booking): ?>
                        <div class="booking-card">
                            <div class="booking-header accepted">
                                <span class="booking-id">Booking #<?php echo $booking['booking_id']; ?></span>
                                <span class="booking-status"><?php echo $booking['status']; ?></span>
                            </div>
                            <div class="booking-body">
                                <h3 class="booking-title"><?php echo htmlspecialchars($booking['service_name']); ?></h3>
                                <div class="booking-meta">
                                    <span class="booking-price">€<?php echo number_format($booking['price'], 2); ?></span>
                                    <span class="booking-delivery">Est. <?php echo $booking['delivery_time_days'] ?? 'N/A'; ?> days</span>
                                </div>
                                <p class="booking-description"><?php echo htmlspecialchars($booking['description']); ?></p>

                                <div class="booking-provider">
                                    <div class="provider-info">
                                        <div class="provider-name"><?php echo htmlspecialchars($booking['provider_name']); ?></div>
                                        <div class="provider-label">Service Provider</div>
                                    </div>
                                </div>

                                <div class="booking-actions">

                                    <button class="btn btn-info" onclick="viewDetails(<?php echo $booking['service_id']; ?>)">
                                        <i class="fas fa-info-circle"></i> Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h3>No Accepted Bookings</h3>
                        <p>You don't have any accepted service bookings</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Completed Bookings -->
            <div class="status-section" id="completed-section">
                <?php if (!empty($bookings['completed'])): ?>
                    <?php foreach ($bookings['completed'] as $booking): ?>
                        <div class="booking-card">
                            <div class="booking-header completed">
                                <span class="booking-id">Booking #<?php echo $booking['booking_id']; ?></span>
                                <span class="booking-status"><?php echo $booking['status']; ?></span>
                            </div>
                            <div class="booking-body">
                                <h3 class="booking-title"><?php echo htmlspecialchars($booking['service_name']); ?></h3>
                                <div class="booking-meta">
                                    <span class="booking-price">€<?php echo number_format($booking['price'], 2); ?></span>
                                    <span class="booking-delivery">Completed on <?php echo date('M j, Y', strtotime($booking['completed_at'])); ?></span>
                                </div>
                                <p class="booking-description"><?php echo htmlspecialchars($booking['description']); ?></p>

                                <div class="booking-provider">
                                    <div class="provider-info">
                                        <div class="provider-name"><?php echo htmlspecialchars($booking['provider_name']); ?></div>
                                        <div class="provider-label">Service Provider</div>
                                    </div>
                                </div>

                                <div class="booking-actions">
                                    <button class="btn btn-success" onclick="leaveReview(<?php echo $booking['service_id']; ?>)">
                                        <i class="fas fa-star"></i> Leave Review
                                    </button>
                                    <button class="btn btn-info" onclick="viewDetails(<?php echo $booking['service_id']; ?>)">
                                        <i class="fas fa-info-circle"></i> Details
                                    </button>
                                    <button class="btn btn-danger" onclick="window.location.href='report_service.php?id=<?php echo $booking['service_id']; ?>'">
                                        <i class="fas fa-flag"></i> Report
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-clipboard-check"></i>
                        <h3>No Completed Bookings</h3>
                        <p>You haven't completed any services yet</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Rejected Bookings -->
            <div class="status-section" id="rejected-section">
                <?php if (!empty($bookings['rejected'])): ?>
                    <?php foreach ($bookings['rejected'] as $booking): ?>
                        <div class="booking-card">
                            <div class="booking-header rejected">
                                <span class="booking-id">Booking #<?php echo $booking['booking_id']; ?></span>
                                <span class="booking-status"><?php echo $booking['status']; ?></span>
                            </div>
                            <div class="booking-body">
                                <h3 class="booking-title"><?php echo htmlspecialchars($booking['service_name']); ?></h3>
                                <div class="booking-meta">
                                    <span class="booking-price">€<?php echo number_format($booking['price'], 2); ?></span>
                                    <span class="booking-delivery">Rejected on <?php echo date('M j, Y', strtotime($booking['completed_at'])); ?></span>
                                </div>
                                <p class="booking-description"><?php echo htmlspecialchars($booking['description']); ?></p>

                                <div class="booking-provider">
                                    <div class="provider-info">
                                        <div class="provider-name"><?php echo htmlspecialchars($booking['provider_name']); ?></div>
                                        <div class="provider-label">Service Provider</div>
                                    </div>
                                </div>

                                <div class="booking-actions">
                                    <!-- <button class="btn btn-primary" onclick="rebookService(<?php echo $booking['service_id']; ?>)">
                                        <i class="fas fa-redo"></i> Rebook
                                    </button> -->
                                    <button class="btn btn-info" onclick="viewDetails(<?php echo $booking['service_id']; ?>)">
                                        <i class="fas fa-info-circle"></i> Details
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-ban"></i>
                        <h3>No Rejected Bookings</h3>
                        <p>You don't have any rejected service bookings</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include __DIR__ . '/../footer.php'; ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.status-tab');

            tabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    tabs.forEach(t => t.classList.remove('active'));

                    this.classList.add('active');

                    document.querySelectorAll('.status-section').forEach(section => {
                        section.classList.remove('active');
                    });

                    const tabId = this.getAttribute('data-tab');
                    const section = document.getElementById(`${tabId}-section`);
                    if (section) {
                        section.classList.add('active');
                    }
                });
            });

            if (!document.querySelector('.status-tab.active') && tabs.length > 0) {
                tabs[0].classList.add('active');
            }
        });



        function leaveReview(bookingId) {
            window.location.href = `review/review.php?booking_id=${bookingId}`;
        }

        function viewDetails(service_id) {
            window.location.href = `card.php?id=${service_id}`;
        }
    </script>
</body>

</html>