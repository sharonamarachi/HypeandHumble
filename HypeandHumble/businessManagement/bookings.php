<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

session_start();

// Check if user is logged in
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

// Get provider_id for the logged-in user
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['booking_id'])) {
    $booking_id = (int)$_POST['booking_id'];
    $action = $_POST['action'];

    $valid_actions = ['accept', 'complete', 'reject'];
    if (!in_array($action, $valid_actions)) {
        die("Invalid action");
    }

    // verify booking belongs to this provider
    $verify_query = "SELECT b.status, b.request_details 
                     FROM bookings b
                     JOIN services s ON b.service_id = s.service_id
                     WHERE b.booking_id = ? AND s.provider_id = ?";
    $verify_stmt = $conn->prepare($verify_query);
    $verify_stmt->bind_param("ii", $booking_id, $provider_id);
    $verify_stmt->execute();
    $verify_result = $verify_stmt->get_result();

    if ($verify_result->num_rows === 0) {
        $error_message = "Booking not found or you don't have permission to modify it.";
    } else {
        $booking_data = $verify_result->fetch_assoc();
        $current_status = $booking_data['status'];

        // Validate status transition
        $valid_transitions = [
            'pending' => ['accept', 'reject'],
            'accepted' => ['complete'],
            'completed' => [],
            'rejected' => []
        ];

        if (!in_array($action, $valid_transitions[$current_status])) {
            $error_message = "Invalid status transition from $current_status to $action";
        } else {
            // Update booking status
            $update_query = "UPDATE bookings b
                            JOIN services s ON b.service_id = s.service_id
                            SET b.status = ?
                            WHERE b.booking_id = ? AND s.provider_id = ?";
            $stmt = $conn->prepare($update_query);

            // Map action to status
            $status_map = [
                'accept' => 'accepted',
                'complete' => 'completed',
                'reject' => 'rejected'
            ];
            $status = $status_map[$action];

            $stmt->bind_param("sii", $status, $booking_id, $provider_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $success_message = "Booking #$booking_id has been $status successfully!";

                // update completed_at 
                if ($action === 'complete') {
                    $timestamp_query = "UPDATE bookings b
                                      JOIN services s ON b.service_id = s.service_id
                                      SET b.completed_at = NOW()
                                      WHERE b.booking_id = ? AND s.provider_id = ?";
                    $ts_stmt = $conn->prepare($timestamp_query);
                    $ts_stmt->bind_param("ii", $booking_id, $provider_id);
                    $ts_stmt->execute();
                }
            } else {
                $error_message = "Failed to update booking status.";
            }
        }
    }
}

// Get bookings for this provider's services
$payments_query = "
    SELECT b.booking_id, b.status, b.created_at, b.completed_at, b.request_details,
           s.service_id, s.name as service_name, s.price, 
           u.user_id as customer_id, u.name as customer_name, u.email as customer_email
    FROM bookings b
    JOIN services s ON b.service_id = s.service_id
    JOIN users u ON b.user_id = u.user_id
    WHERE s.provider_id = ?
    ORDER BY 
        CASE 
            WHEN b.status = 'pending' THEN 1
            WHEN b.status = 'accepted' THEN 2
            WHEN b.status = 'completed' THEN 3
            ELSE 4
        END,
        b.created_at DESC
";

$stmt = $conn->prepare($payments_query);
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$payments_result = $stmt->get_result();

$payments = [];
while ($payment = $payments_result->fetch_assoc()) {
    $payment['price'] = (float)$payment['price'];
    $payments[] = $payment;
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payments Dashboard | Business Portal</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
    <link rel="stylesheet" href="businessStyle.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .page-header {
            margin-bottom: 2rem;
        }

        .page-title {
            font-size: 2rem;
            color: #3a2a6e;
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: #718096;
            font-size: 1rem;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background-color: rgba(56, 161, 105, 0.1);
            color: #38a169;
            border-left: 4px solid #38a169;
        }

        .alert-danger {
            background-color: rgba(229, 62, 62, 0.1);
            color: #e53e3e;
            border-left: 4px solid #e53e3e;
        }

        .payments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }

        .payment-card {
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .payment-card:hover {
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-3px);
        }

        .payment-header {
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
        }

        .payment-id {
            font-weight: 600;
            color: #3a2a6e;
        }

        .payment-status {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-pending {
            background-color: rgba(234, 179, 8, 0.1);
            color: #b7791f;
        }

        .status-accepted {
            background-color: rgba(72, 187, 120, 0.1);
            color: #38a169;
        }

        .status-completed {
            background-color: rgba(66, 153, 225, 0.1);
            color: #3182ce;
        }

        .status-rejected {
            background-color: rgba(245, 101, 101, 0.1);
            color: #e53e3e;
        }

        .payment-body {
            padding: 1.5rem;
        }

        .service-name {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .payment-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .detail-group {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.75rem;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
        }

        .detail-value {
            font-weight: 500;
        }

        .payment-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #3a2a6e;
            margin: 1rem 0;
            text-align: right;
        }

        .customer-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1.5rem;
            padding-top: 1.5rem;
            border-top: 1px solid #e2e8f0;
        }

        .customer-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e0d6ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #3a2a6e;
            font-weight: 600;
        }

        .customer-details {
            flex: 1;
        }

        .customer-name {
            font-weight: 600;
        }

        .customer-email {
            font-size: 0.875rem;
            color: #718096;
        }

        .payment-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            font-size: 0.875rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.8125rem;
        }

        .btn-primary {
            background-color: #5a3ea1;
            color: white;
        }

        .btn-primary:hover {
            background-color: #3a2a6e;
        }

        .btn-success {
            background-color: #38a169;
            color: white;
        }

        .btn-success:hover {
            background-color: #2f855a;
        }

        .btn-danger {
            background-color: #e53e3e;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c53030;
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #e2e8f0;
            color: #2d3748;
        }

        .btn-outline:hover {
            background-color: #f7fafc;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            grid-column: 1 / -1;
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            border: 1px dashed #e2e8f0;
        }

        .empty-state i {
            font-size: 3rem;
            color: #e0d6ff;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
            color: #2d3748;
        }

        .empty-state p {
            color: #718096;
        }

        @media (max-width: 768px) {
            .payments-grid {
                grid-template-columns: 1fr;
            }

            .container {
                padding: 1rem;
            }

            .payment-actions {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

</head>

<body>
    <?php include __DIR__ . '/business_navbar.php'; ?>

    <main class="container">
        <div class="page-header">
            <h1 class="page-title">Your Bookings</h1>
            <p class="page-subtitle">Manage bookings for your services</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="payments-grid">
            <?php if (empty($payments)): ?>
                <div class="empty-state">
                    <i class="fas fa-coins"></i>
                    <h3>No Payments Found</h3>
                    <p>You don't have any bookings for your services yet</p>
                </div>
            <?php else: ?>
                <?php foreach ($payments as $payment): ?>
                    <div class="payment-card">
                        <div class="payment-header">
                            <span class="payment-id">Booking #<?php echo $payment['booking_id']; ?></span>
                            <span class="payment-status status-<?php echo $payment['status']; ?>">
                                <?php echo $payment['status']; ?>
                            </span>
                        </div>

                        <div class="payment-body">
                            <h3 class="service-name"><?php echo htmlspecialchars($payment['service_name']); ?></h3>

                            <div class="payment-details">
                                <div class="detail-group">
                                    <span class="detail-label">Booking Date</span>
                                    <span class="detail-value">
                                        <?php echo date('M j, Y', strtotime($payment['created_at'])); ?>
                                    </span>
                                </div>

                                <div class="detail-group">
                                    <span class="detail-label">
                                        <?php echo $payment['status'] === 'completed' ? 'Completed On' : 'Status'; ?>
                                    </span>
                                    <span class="detail-value">
                                        <?php if ($payment['status'] === 'completed' && !empty($payment['completed_at'])): ?>
                                            <?php echo date('M j, Y', strtotime($payment['completed_at'])); ?>
                                        <?php else: ?>
                                            <?php echo ucfirst($payment['status']); ?>
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>

                            <?php if (!empty($payment['request_details'])): ?>
                                <div class="detail-group" style="margin-top: 1rem;">
                                    <span class="detail-label">Request Details</span>
                                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($payment['request_details'])); ?></span>
                                </div>
                            <?php endif; ?>

                            <div class="payment-price">€<?php echo number_format($payment['price'], 2); ?></div>

                            <div class="customer-info">
                                <div class="customer-avatar">
                                    <?php echo strtoupper(substr($payment['customer_name'], 0, 1)); ?>
                                </div>
                                <div class="customer-details">
                                    <div class="customer-name"><?php echo htmlspecialchars($payment['customer_name']); ?></div>
                                    <div class="customer-email"><?php echo htmlspecialchars($payment['customer_email']); ?></div>
                                </div>
                            </div>

                            <div class="payment-actions">
                                <?php if ($payment['status'] === 'pending'): ?>
                                    <form method="POST" style="flex:1">
                                        <input type="hidden" name="booking_id" value="<?php echo $payment['booking_id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fas fa-check"></i> Accept
                                        </button>
                                    </form>
                                    <form method="POST" style="flex:1">
                                        <input type="hidden" name="booking_id" value="<?php echo $payment['booking_id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger btn-sm">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                <?php elseif ($payment['status'] === 'accepted'): ?>
                                    <form method="POST" style="flex:1">
                                        <input type="hidden" name="booking_id" value="<?php echo $payment['booking_id']; ?>">
                                        <input type="hidden" name="action" value="complete">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-check-circle"></i> Complete
                                        </button>
                                    </form>
                                <?php elseif ($payment['status'] === 'completed'): ?>
                                    <button class="btn btn-outline btn-sm" disabled>
                                        <i class="fas fa-check-double"></i> Completed
                                    </button>
                                <?php else: ?>
                                    <button class="btn btn-outline btn-sm" disabled>
                                        <i class="fas fa-ban"></i> Rejected
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const rejectForms = document.querySelectorAll('form[action="reject"]');
            const completeForms = document.querySelectorAll('form[action="complete"]');

            rejectForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Are you sure you want to reject this booking? This cannot be undone.')) {
                        e.preventDefault();
                    }
                });
            });

            completeForms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    if (!confirm('Mark this booking as complete? Please ensure service was fully delivered.')) {
                        e.preventDefault();
                    }
                });
            });
        });
    </script>
</body>

</html>