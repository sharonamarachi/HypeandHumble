<?php


$servername  = "sql106.infinityfree.com";
$username    = "if0_38503886";
$password_db = "StlFnsLkFkx";
$database    = "if0_38503886_hypehumbledb";
$port        = 3306;

$conn = new mysqli($servername, $username, $password_db, $database, $port);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    $user_id = $_SESSION['user_id'];

    $sql = "SELECT * FROM `users` WHERE `user_id` = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_name = htmlspecialchars($user['name']);
    } else {
        $is_logged_in = false;
    }

    $stmt->close();
}
$conn->close();

?>

<style>
    .dashboard-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 250px;
        background: #f8f9fa;
        color: #333;
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        z-index: 1000;
        transition: all 0.3s ease;
        overflow-y: auto;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .sidebar-header {
        padding: 20px;
        background: linear-gradient(to top, #6a5acd 0%, #7b68ee 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-header h3 {
        margin: 0;
        font-size: 1.2rem;
        font-weight: 600;
        color: #fff;
    }

    .sidebar-menu {
        padding: 15px 0;
    }

    .sidebar-menu ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .sidebar-menu li {
        position: relative;
        margin: 5px 0;
    }

    .sidebar-menu a {
        display: flex;
        align-items: center;
        padding: 12px 20px;
        color: #333;
        text-decoration: none;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .sidebar-menu a:hover {
        background: #e9ecef;
        color: #6a5acd;
    }

    .sidebar-menu a.active {
        background: linear-gradient(to right, #e0d6ff 0%, #d1c4ff 100%);
        color: #6a5acd;
        font-weight: 500;
    }

    .sidebar-menu a i {
        margin-right: 12px;
        font-size: 1.1rem;
        color: #6a5acd;
    }

    .sidebar-menu .menu-label {
        display: block;
        padding: 10px 20px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: #6c757d;
        font-weight: 600;
    }

    .sidebar-footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        padding: 15px;
        border-top: 1px solid #dee2e6;
        background: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .sidebar-footer .user-greeting {
        padding: 0 15px;
        font-weight: 500;
        color: #6a5acd;
    }

    .sidebar-footer a {
        color: #6a5acd;
        display: flex;
        align-items: center;
        padding: 8px 15px;
        border-radius: 4px;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .sidebar-footer a:hover {
        background: #e9ecef;
        color: #5a3ea1;
    }

    .sidebar-toggle {
        display: none;
        position: fixed;
        left: 10px;
        top: 10px;
        z-index: 1100;
        background: #6a5acd;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 4px;
        cursor: pointer;
    }



    /* Responsive behavior */
    @media (max-width: 992px) {
        .dashboard-sidebar {
            transform: translateX(-100%);
            width: 280px;
        }

        .dashboard-sidebar.active {
            transform: translateX(0);
        }

        .sidebar-toggle {
            display: block;
        }

        .main-content.sidebar-active {
            margin-left: 280px;
        }
    }
</style>

<div class="dashboard-sidebar">
    <div class="sidebar-header">
        <h3>H&H Admin Menu</h3>
    </div>

    <div class="sidebar-menu">
        <span class="menu-label">Management</span>
        <ul>
            <li>
                <a href="adminDashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'adminDashboard.php' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i>
                    <span>Dashboard</span>
                </a>
            </li>

            <li>
                <a href="manage_users.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_users.php' ? 'active' : '' ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>Manage Users</span>
                </a>
            </li>
            <li>
                <a href="manage_services.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_services.php' ? 'active' : '' ?>">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Manage Services</span>
                </a>
            </li>
            <li>
                <a href="manage_reviews.php" class="<?= basename($_SERVER['PHP_SELF']) === 'manage_reviews.php' ? 'active' : '' ?>">
                    <i class="fas fa-star"></i>
                    <span>Manage Reviews</span>
                </a>
            </li>
            <li>
                <a href="content_moderation.php" class="<?= basename($_SERVER['PHP_SELF']) === 'content_moderation.php' ? 'active' : '' ?>">
                    <i class="fas fa-flag"></i>
                    <span>Content Moderation</span>
                </a>
            </li>
            <li>
                <a href="user_moderation.php" class="<?= basename($_SERVER['PHP_SELF']) === 'user_moderation.php' ? 'active' : '' ?>">
                    <i class="fas fa-flag"></i>
                    <span>User Moderation</span>
                </a>
            </li>
            <li>
                <a href="feedback.php" class="<?= basename($_SERVER['PHP_SELF']) === 'feedback.php' ? 'active' : '' ?>">
                    <i class="fas fa-comment"></i>
                    <span>Feedback</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="user-greeting">Hi, <?= $user_name ?></div>
        <a href="logout.php" class="logout">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>
    </div>
</div>

<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<script>
    function toggleSidebar() {
        const sidebar = document.querySelector('.dashboard-sidebar');
        const mainContent = document.querySelector('.main-content');

        sidebar.classList.toggle('active');
        if (mainContent) {
            mainContent.classList.toggle('sidebar-active');
        }
    }
</script>