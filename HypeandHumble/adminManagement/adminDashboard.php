<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();

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
    error_log("Database connection failed: " . $conn->connect_error);
}

$adminCheck = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
$adminCheck->bind_param("i", $_SESSION['user_id']);
$adminCheck->execute();
$adminResult = $adminCheck->get_result();

if ($adminResult->num_rows === 0) {
    die("User not found in database.");
}

$userData = $adminResult->fetch_assoc();
if ($userData['role'] !== 'admin') {
    die("Access denied. Admin privileges required.");
}
$adminCheck->close();


$userStats = ['total' => 0, 'verified' => 0, 'customers' => 0, 'providers' => 0];
$userResult = $conn->query("SELECT COUNT(*) as total, 
                       SUM(verified = 1) as verified,
                       SUM(role = 'user') as customers,
                       SUM(role = 'provider') as providers 
                       FROM users");
if ($userResult) {
    $userStats = $userResult->fetch_assoc();
}

$serviceStats = ['total' => 0, 'active' => 0, 'inactive' => 0];

$serviceResult = $conn->query("SELECT COUNT(*) as total,
                             SUM(status = 'active') as active,
                             SUM(status = 'inactive') as inactive
                             FROM services");
if ($serviceResult) {
    $serviceStats = $serviceResult->fetch_assoc();
}


$reviewStats = ['total' => 0, 'average' => 0, '5_star' => 0, '4_star' => 0, '3_star' => 0, '2_star' => 0, '1_star' => 0];

$reviewResult = $conn->query("SELECT 
    COUNT(*) as total, 
    AVG(rating) as average,
    SUM(rating >= 4.5) as 5_star,
    SUM(rating >= 3.5 AND rating < 4.5) as 4_star,
    SUM(rating >= 2.5 AND rating < 3.5) as 3_star,
    SUM(rating >= 1.5 AND rating < 2.5) as 2_star,
    SUM(rating < 1.5) as 1_star
    FROM reviews");
if ($reviewResult) {
    $reviewStats = $reviewResult->fetch_assoc();
    $reviewStats['average'] = round((float)$reviewStats['average'], 2);
}

// Get content moderation statistics
$moderationStats = ['reported' => 0, 'resolved' => 0];

$moderationResult = $conn->query("SELECT 
    SUM(status = 'pending') as reported,
    SUM(status = 'reviewed') as resolved
    FROM adminreports");
if ($moderationResult) {
    $moderationStats = $moderationResult->fetch_assoc();
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hype & Humble</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #eff0f3;
            margin: 0;
            padding: 0;
        }

        h1 {
            color: #6a1b9a;
            padding: 0 20px;
        }

        .main {
            margin-left: 280px;
            padding: 20px;
            transition: margin-left 0.3s;
        }

        .container {
            width: 95%;
            margin: 0 auto;
            padding: 20px;
        }

        .sections-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }

        .section {
            flex: 1;
            min-width: 300px;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }

        .section h2 {
            color: #6a1b9a;
            margin-top: 0;
            margin-bottom: 15px;
        }

        .chart-container {
            position: relative;
            height: 200px;
            width: 100%;
            margin-bottom: 15px;
        }

        .btn {
            display: inline-block;
            background-color: #8e24aa;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            margin-top: 15px;
            transition: background-color 0.3s;
        }

        .btn:hover {
            background-color: #a23bcf;
        }

        /* Responsive styles */
        @media (max-width: 1200px) {
            .section {
                min-width: calc(50% - 30px);
            }
        }

        @media (max-width: 768px) {
            .main {
                margin-left: 0;
                padding: 15px;
            }

            .container {
                padding: 10px;
            }

            .sections-row {
                flex-direction: column;
                gap: 15px;
            }

            .section {
                min-width: 100%;
            }

            .chart-container {
                height: 180px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.5rem;
            }

            .section h2 {
                font-size: 1.2rem;
            }

            .chart-container {
                height: 160px;
            }

            .btn {
                padding: 8px 15px;
                font-size: 0.9rem;
            }
        }
    </style>

</head>

<body>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <h1>Admin Dashboard</h1>

        <div class="container">
            <div class="sections-row">
                <div class="section">
                    <h2>User Management</h2>
                    <div class="chart-container">
                        <canvas id="userChart"></canvas>
                    </div>
                    <p>Total: <?= $userStats['total'] ?> (<?= $userStats['verified'] ?> verified)</p>
                    <p>Customers: <?= $userStats['customers'] ?> | Providers: <?= $userStats['providers'] ?></p>
                    <a href="manage_users.php" class="btn">Manage Users</a>
                </div>

                <div class="section">
                    <h2>Service Management</h2>
                    <div class="chart-container">
                        <canvas id="serviceChart"></canvas>
                    </div>
                    <p>Total: <?= $serviceStats['total'] ?></p>
                    <p>Active: <?= $serviceStats['active'] ?> | Inactive: <?= $serviceStats['inactive'] ?></p>
                    <a href="manage_services.php" class="btn">Manage Services</a>
                </div>
            </div>

            <div class="sections-row">
                <div class="section">
                    <h2>Review Management</h2>
                    <div class="chart-container">
                        <canvas id="reviewChart"></canvas>
                    </div>
                    <p>Total Reviews: <?= $reviewStats['total'] ?> (Avg: <?= $reviewStats['average'] ?>/5)</p>
                    <p>
                        5★: <?= $reviewStats['5_star'] ?> |
                        4★: <?= $reviewStats['4_star'] ?> |
                        3★: <?= $reviewStats['3_star'] ?> |
                        2★: <?= $reviewStats['2_star'] ?> |
                        1★: <?= $reviewStats['1_star'] ?>
                    </p>
                    <a href="manage_reviews.php" class="btn">Manage Reviews</a>
                </div>

                <div class="section">
                    <h2>Content Moderation</h2>
                    <div class="chart-container">
                        <canvas id="moderationChart"></canvas>
                    </div>
                    <p>Reported: <?= $moderationStats['reported'] ?> | Resolved: <?= $moderationStats['resolved'] ?></p>
                    <a href="content_moderation.php" class="btn">Moderate Content</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Updated color palette to match sidebar
            const sidebarColors = [
                '#6a5acd', // Main purple (matches sidebar header)
                '#7b68ee', // Lighter purple
                '#9370db', // Medium purple
                '#e6e6fa', // Very light lavender
                '#d8bfd8', // Thistle
                '#dda0dd', // Plum
                '#ba55d3', // Medium orchid
                '#9932cc', // Dark orchid
                '#8a2be2', // Blue violet
                '#9400d3' // Dark violet
            ];

            const chartsConfig = [{
                    id: 'userChart',
                    type: 'bar',
                    labels: ['Customers', 'Providers'],
                    data: [
                        <?= $userStats['customers'] ?>,
                        <?= $userStats['providers'] ?>
                    ],
                    backgroundColor: [sidebarColors[0], sidebarColors[2]],
                    borderColor: [sidebarColors[8], sidebarColors[9]],
                    label: 'User Types'
                },
                {
                    id: 'serviceChart',
                    type: 'pie',
                    labels: ['Active Services', 'Inactive Services'],
                    data: [<?= $serviceStats['active'] ?>, <?= $serviceStats['inactive'] ?>],
                    backgroundColor: [sidebarColors[1], sidebarColors[3]],
                    borderColor: [sidebarColors[8], sidebarColors[8]],
                    label: 'Service Status'
                },
                {
                    id: 'reviewChart',
                    type: 'bar',
                    labels: ['5★', '4★', '3★', '2★', '1★'],
                    data: [
                        <?= $reviewStats['5_star'] ?>,
                        <?= $reviewStats['4_star'] ?>,
                        <?= $reviewStats['3_star'] ?>,
                        <?= $reviewStats['2_star'] ?>,
                        <?= $reviewStats['1_star'] ?>
                    ],
                    backgroundColor: [
                        sidebarColors[1], // 5-star
                        sidebarColors[3], // 4-star
                        sidebarColors[5], // 3-star
                        sidebarColors[7], // 2-star
                        sidebarColors[9] // 1-star
                    ],
                    borderColor: sidebarColors[8],
                    label: 'Review Ratings'
                },
                {
                    id: 'moderationChart',
                    type: 'bar',
                    labels: ['Reported', 'Resolved'],
                    data: [
                        <?= $moderationStats['reported'] ?>,
                        <?= $moderationStats['resolved'] ?>
                    ],
                    backgroundColor: [sidebarColors[5], sidebarColors[2]],
                    borderColor: [sidebarColors[8], sidebarColors[9]],
                    label: 'Content Reports'
                }
            ];

            chartsConfig.forEach(config => {
                const ctx = document.getElementById(config.id);
                new Chart(ctx, {
                    type: config.type,
                    data: {
                        labels: config.labels,
                        datasets: [{
                            label: config.label,
                            data: config.data,
                            backgroundColor: config.backgroundColor,
                            borderColor: config.borderColor,
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                display: config.type === 'pie' ? true : false,
                                labels: {
                                    color: '#333'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        return `${context.dataset.label}: ${context.raw}`;
                                    }
                                }
                            }
                        },
                        scales: config.type === 'bar' ? {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    precision: 0,
                                    color: '#666'
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                ticks: {
                                    color: '#666'
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            }
                        } : {}
                    }
                });
            });
        });
    </script>
</body>

</html>