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

// Create connection
$conn = new mysqli($servername, $username, $password_db, $database, $port);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Verify admin status
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

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_service_id'])) {
    $serviceIdToDelete = (int)$_POST['delete_service_id'];
    $stmt = $conn->prepare("DELETE FROM services WHERE service_id = ?");
    $stmt->bind_param("i", $serviceIdToDelete);
    $stmt->execute();
    $stmt->close();

    // Refresh to show updated list
    header("Location: manage_services.php");
    exit();
}

// Handle status change action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $serviceId = (int)$_POST['service_id'];
    $newStatus = $_POST['new_status'];

    $stmt = $conn->prepare("UPDATE services SET status = ? WHERE service_id = ?");
    $stmt->bind_param("si", $newStatus, $serviceId);
    $stmt->execute();
    $stmt->close();

    // Refresh to show updated list
    header("Location: manage_services.php");
    exit();
}

// Fetch all services with provider names
$services = [];
$query = "SELECT s.*, u.name as provider_name
          FROM services s 
          JOIN providers p ON s.provider_id = p.provider_id
          JOIN users u ON p.user_id = u.user_id
          ORDER BY s.service_id ASC";
$result = $conn->query($query);
if ($result) {
    $services = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Services - Hype & Humble</title>
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
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
        }

        .main {
            margin-left: 330px;
        }

        .container {
            width: 90%;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 20px;
        }

        .sections-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .section {
            flex: 1;
            margin: 10px;
            padding: 30px;
            background-color: #ffffff;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            border: 2px solid rgb(187, 184, 184);
        }

        .section h2 {
            color: #6a1b9a;
            margin-bottom: 20px;
        }

        .services {
            width: 100%;
            overflow-x: auto;
        }

        .services table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .services th,
        .services td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .services th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .services tr:hover {
            background-color: #f5f5f5;
        }

        .btn {
            background-color: #8e24aa;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .btn:hover {
            background-color: #a23bcf;
        }

        .status-btn {
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
        }

        .status-btn.active {
            background-color: #4CAF50;
        }

        .status-btn.inactive {
            background-color: #f44336;
        }

        /* Responsive styles */
        @media (max-width: 768px) {
            .main {
                margin-left: 0;
            }

            .container {
                width: 95%;
                margin: 0 auto;
                padding: 15px;
            }

            .service-controls {
                flex-direction: column;
                gap: 10px;
            }

            .services table {
                display: block;
                width: 100%;
            }

            .services thead {
                display: none;
            }

            .services tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
                padding: 10px;
            }

            .services td {
                display: flex;
                justify-content: space-between;
                padding: 12px 15px;
                border-bottom: 1px solid #eee;
                align-items: center;
            }

            .services td:last-child {
                border-bottom: none;
            }

            .services td::before {
                content: attr(data-label);
                font-weight: bold;
                width: 45%;
                padding-right: 15px;
            }
        }

        footer {
            text-align: center;
            padding: 20px;
            background: #6a1b9a;
            color: white;
            margin-top: 5%;
        }
    </style>

</head>

<body>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <h1>Manage Services</h1>

        <div class="container">
            <div class="services">
                <h2>Services</h2>
                <div class="service-controls">
                    <input type="text" id="searchServices" placeholder="Search service name..." style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <button id="refreshServices" class="btn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>

                <table id="serviceTable" class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Business</th>
                            <th>Service Type</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Delivery Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $row): ?>
                            <tr>
                                <td data-label="ID"><?= htmlspecialchars($row['service_id']) ?></td>
                                <td data-label="Business"><?= htmlspecialchars($row['provider_name']) ?></td>
                                <td data-label="Service Type"><?= htmlspecialchars($row['service_type']) ?></td>
                                <td data-label="Name"><?= htmlspecialchars($row['name']) ?></td>
                                <td data-label="Description"><?= htmlspecialchars($row['description']) ?></td>
                                <td data-label="Price">$<?= htmlspecialchars(number_format((float)$row['price'], 2)) ?></td>
                                <td data-label="Delivery Time"><?= htmlspecialchars($row['delivery_time_days']) ?> days</td>
                                <td data-label="Status">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="service_id" value="<?= $row['service_id'] ?>">
                                        <input type="hidden" name="new_status" value="<?= $row['status'] === 'active' ? 'inactive' : 'active' ?>">
                                        <button type="submit" name="toggle_status" class="status-btn <?= $row['status'] ?>">
                                            <?= ucfirst($row['status']) ?>
                                        </button>
                                    </form>
                                </td>
                                <td data-label="Action">
                                    <button onclick="confirmDelete(<?= (int)$row['service_id'] ?>)" class="btn" title="Delete Service" style="background: transparent; border: none; color: red; cursor: pointer;">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <script>
        function confirmDelete(serviceId) {
            if (confirm("Are you sure you want to delete this service?")) {
                const form = document.createElement("form");
                form.method = "POST";
                form.style.display = "none";

                const input = document.createElement("input");
                input.name = "delete_service_id";
                input.value = serviceId;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Live search by name
        document.getElementById('searchServices').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#serviceTable tbody tr');

            rows.forEach(row => {
                const name = row.children[3].textContent.toLowerCase(); // 4th column = Name
                if (name.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Refresh button reloads the page
        document.getElementById('refreshServices').addEventListener('click', function() {
            location.reload();
        });
    </script>

</body>

</html>