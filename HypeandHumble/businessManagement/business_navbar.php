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

if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM `users` WHERE `user_id` = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("User not found.");
}

$stmt->close();
$conn->close();
?>

<link rel="icon" type="image/png" href="../images/H_and_H_Logo.png">
<style>
    /* Navbar */
    .navbar {
        background-color: #ffffff;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        padding: 1rem 2rem;
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    .navbar-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 1400px;
        margin: 0 auto;
        width: 100%;
    }

    .nav-links {
        display: flex;
        gap: 1.5rem;
        list-style: none;
    }

    .nav-link {
        color: #4a0788;
        text-decoration: none;
        font-weight: 600;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .nav-link:hover {
        background-color: #f0e6ff;
        color: #4a0788;
    }

    /* Account Dropdown */
    .account-dropdown {
        position: relative;
    }

    .account-button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
    }

    .account-photo {
        border-radius: 50%;
        width: 35px;
        height: 35px;
        object-fit: cover;
        background-color: #c9bbf2;
    }

    .dropdown-content {
        position: absolute;
        top: 100%;
        right: 0;
        background-color: #fff;
        border: 1px solid #ccc;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 0.75rem;
        margin-top: 0.25rem;
        min-width: 160px;
        z-index: 1000;
    }

    .dropdown-content.show {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }

    .user-greeting {
        font-weight: 600;
        margin-bottom: 0.5rem;
    }

    .dropdown-divider {
        margin: 0.5rem 0;
        border: 0.5px solid #eee;
    }

    .dropdown-link {
        display: block;
        padding: 0.5rem 0;
        color: #2d2d2d;
        text-decoration: none;
        transition: all 0.3s ease;
    }

    .dropdown-content:hover {
        background-color: #fff;
    }

    .dropdown-content a:hover {
        background-color: #f0ecfc;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .navbar-container {
            padding: 0.5rem;
        }

        .nav-links {
            gap: 0.75rem;
        }
    }
</style>

<!-- Navbar -->
<nav class="navbar" aria-label="Main navigation">
    <div class="navbar-container container">
        <ul class="nav-links">
            <li><a href="buisness_services.php" class="nav-link">List of Services</a></li>
            <li><a href="addService.php" class="nav-link">Add New Service</a></li>
            <li><a href="buisness_review.php" class="nav-link">Reviews</a></li>
            <li><a href="bookings.php" class="nav-link">Bookings</a></li>
            <li><a href="revenue.php" class="nav-link">Revenue</a></li>
            <li><a href="test.php" class="nav-link">Chat</a></li>

        </ul>

        <div class="nav-right">
            <div class="account-dropdown">
                <button class="account-button" aria-expanded="false" aria-label="User menu" aria-controls="dropdownMenu">
                    <?php
                    $defaultAvatar = 'https://cdn-icons-png.flaticon.com/512/3276/3276535.png';
                    $rawPath = $user['avatar_path'] ?? '';
                    $avatarPathClean = ltrim($rawPath, '/');
                    $finalURL = (!empty($avatarPathClean))
                        ? (str_starts_with($avatarPathClean, 'http') ? $avatarPathClean : 'http://' . $avatarPathClean)
                        : $defaultAvatar;
                    ?>

                    <img src="<?= htmlspecialchars($finalURL, ENT_QUOTES, 'UTF-8') ?>"
                        alt="Profile picture"
                        class="account-photo"
                        width="35"
                        height="35">
                </button>
                <div class="dropdown-content" id="dropdownMenu">
                    <p class="user-greeting">Hi, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?> !</p>
                    <hr class="dropdown-divider">
                    <a href="business_user_profile.php" class="dropdown-link">View Account</a>
                    <a href="logout.php" class="dropdown-link">Sign Out</a>
                </div>
            </div>
        </div>
    </div>
</nav>


<script>
    // Initialize dropdown functionality
    document.addEventListener('DOMContentLoaded', function() {
        const accountButton = document.querySelector('.account-button');
        const dropdownMenu = document.getElementById('dropdownMenu');

        accountButton.addEventListener('click', function(e) {
            e.preventDefault();
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            this.setAttribute('aria-expanded', !isExpanded);
            dropdownMenu.classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (!accountButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                accountButton.setAttribute('aria-expanded', 'false');
                dropdownMenu.classList.remove('show');
            }
        });
    });
</script>