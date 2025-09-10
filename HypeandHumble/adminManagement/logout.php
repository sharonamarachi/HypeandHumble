<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $_SESSION = array();

    session_destroy();

    header("Location: ../profileManagement/login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Logout - Hype & Humble</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
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
            margin-left: 280px;
        }

        .container {
            width: 80%;
            padding: 20px;
            background-color: white;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            padding-bottom: 50px;
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
            margin-right: 10px;
        }

        .btn:hover {
            background-color: #a23bcf;
        }

        .approve-btn:hover,
        .reject-btn:hover {
            background-color: #a23bcf;
        }
    </style>
</head>

<body>

    <?php include __DIR__ . '/sidebar.php'; ?>

    <div class="main">
        <h1>Logout</h1>
        <div class="container">
            <p> Are you sure you want to logout?</p>
            <a href="logout.php?confirm=yes" class="btn">Yes</a>
            <a href="admin_dashboard.php" class="btn">No</a>
        </div>
    </div>
</body>

</html>