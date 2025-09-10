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
    die("Connection failed: " . $conn->connect_error);
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

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $userIdToDelete = (int)$_POST['delete_user_id'];
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userIdToDelete);
    $stmt->execute();
    $stmt->close();
    header("Location: manage_users.php");
    exit();
}

// Handle verification toggle action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_verify_user_id'])) {
    $userIdToToggle = (int)$_POST['toggle_verify_user_id'];
    $currentStatus = (int)$_POST['current_status'];
    $newStatus = $currentStatus === 1 ? 0 : 1;

    $stmt = $conn->prepare("UPDATE users SET verified = ? WHERE user_id = ?");
    $stmt->bind_param("ii", $newStatus, $userIdToToggle);
    $stmt->execute();
    $stmt->close();

    header("Location: manage_users.php");
    exit();
}

$users = [];
$result = $conn->query("SELECT * FROM users ORDER BY user_id ASC");
if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Hype & Humble</title>
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

        .section p {
            margin-bottom: 20px;
        }

        .pendingSR,
        .users {
            width: 100%;
            overflow-x: auto;
        }

        .pendingSR table,
        .users table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .pendingSR th,
        .users th,
        .pendingSR td,
        .users td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .pendingSR th,
        .users th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .pendingSR tr:hover,
        .users tr:hover {
            background-color: #f5f5f5;
        }

        .btn,
        .approve-btn,
        .reject-btn {
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

        .approve-btn:hover,
        .reject-btn:hover {
            background-color: #a23bcf;
        }

        .notification-textbox {
            width: 100%;
            padding: 10px;
            margin-right: 10px;
            border: 1px solid #ddd;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 0.9rem;
            color: #333;
        }

        @media (max-width: 768px) {
            .main {
                margin-left: 0;
            }

            .container {
                width: 95%;
                margin: 0 auto;
                padding: 15px;
            }

            .user-controls {
                flex-direction: column;
            }

            .users table {
                display: block;
                width: 100%;
            }

            .users thead {
                display: none;
            }

            .users tr {
                display: block;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
                padding: 10px;
            }

            .users td {
                display: flex;
                justify-content: space-between;
                padding: 12px 15px;
                border-bottom: 1px solid #eee;
                align-items: center;
            }

            .users td:last-child {
                border-bottom: none;
            }

            .users td::before {
                content: attr(data-label);
                font-weight: bold;
                width: 45%;
                padding-right: 15px;
            }

        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <h1>Manage Users</h1>

        <div class="container">
            <div class="users">
                <h2>Users</h2>
                <div class="user-controls">
                    <input type="text" id="searchUsers" placeholder="Search user name..." style="padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <button id="refreshUsers" class="btn"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>

                <table id="userTable" class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>User Type</th>
                            <th>Registration Date</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $row): ?>
                            <tr>
                                <td data-label="ID"><?= htmlspecialchars($row['user_id']) ?></td>
                                <td data-label="Name"><?= htmlspecialchars($row['name']) ?></td>
                                <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
                                <td data-label="User Type">
                                    <?= $row['role'] === 'user' ? 'Customer' : ($row['role'] === 'provider' ? 'Business' : htmlspecialchars($row['role'])) ?>
                                </td>

                                <td data-label="Registration Date"><?= htmlspecialchars(date('Y-m-d', strtotime($row['created_at']))) ?></td>
                                <td data-label="Status">
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="toggle_verify_user_id" value="<?= (int)$row['user_id'] ?>">
                                        <input type="hidden" name="current_status" value="<?= (int)$row['verified'] ?>">
                                        <?php if ((int)$row['verified'] === 1): ?>
                                            <button type="submit" class="btn" title="Lock User" style="background: transparent; border: none; color: orange; cursor: pointer;">
                                                <i class="fas fa-lock"></i> Lock
                                            </button>
                                        <?php else: ?>
                                            <button type="submit" class="btn" title="Unlock & Verify User" style="background: transparent; border: none; color: green; cursor: pointer;">
                                                <i class="fas fa-unlock"></i> Unlock
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                                <td data-label="Action">
                                    <button onclick="confirmDelete(<?= (int)$row['user_id'] ?>)" class="btn" title="Delete User" style="background: transparent; border: none; color: red; cursor: pointer;">
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
        function confirmDelete(userId) {
            if (confirm("Are you sure you want to delete this user?")) {
                const form = document.createElement("form");
                form.method = "POST";
                form.style.display = "none";

                const input = document.createElement("input");
                input.name = "delete_user_id";
                input.value = userId;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Live search by name
        document.getElementById('searchUsers').addEventListener('input', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#userTable tbody tr');

            rows.forEach(row => {
                const name = row.children[1].textContent.toLowerCase();
                if (name.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        document.getElementById('refreshUsers').addEventListener('click', function() {
            location.reload();
        });
    </script>

</body>

</html>