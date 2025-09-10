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
$user_name = "Hi There!";
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
  * {
    box-sizing: border-box;
    font-family: "Arial", sans-serif;
  }

  body {
    margin: 0;
    padding: 0;
  }

  .navbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background-color: white;
    padding: 10px 20px;
    width: 100%;
    height: 80px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
    flex-wrap: nowrap;
    position: relative;
  }

  .brand-name {
    display: none;

  }

  .nav-links {
    display: flex;
    list-style: none;
    gap: 20px;
    padding: 0;
    margin: 0;
  }

  .nav-links a {
    color: #5a3ea1;
    text-decoration: none;
    font-weight: bold;
  }

  .nav-links a:hover {
    background-color: #8c82cf;
    padding: 8px 10px;
    border-radius: 10px;
  }

  .mobile-menu {
    display: none;
    flex-direction: column;
    cursor: pointer;
  }

  .mobile-menu span {
    height: 3px;
    width: 25px;
    background-color: #5a3ea1;
    margin: 4px 0;
  }

  @media (max-width: 768px) {
    .brand-name {
      display: block;
      font-family: 'Georgia', serif;
      font-size: 24px;
      color: #6a5acd;
      margin: 0 10px;
      white-space: nowrap;
    }

    .nav-links {
      display: none;
      flex-direction: column;
      position: absolute;
      top: 80px;
      left: 0;
      width: 100%;
      background: white;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 10px;
      z-index: 999;
    }

    .nav-links.active {
      display: flex;
    }

    .mobile-menu {
      display: flex;

    }

    .search-container {
      display: none;
    }

  }

  @media (min-width: 769px) {
    .nav-links {
      margin-left: 8%;
    }
  }


  .nav-right {
    display: flex;
    align-items: center;
    gap: 25px;
    margin-right: 4%;
  }

  .account-photo img {
    background-color: #c9bbf2;
    border-radius: 50%;
    width: 35px;
    height: 35px;
    object-fit: cover;
    cursor: pointer;
  }

  .dropdown-content {
    display: none;
    position: absolute;
    right: 5px;
    background-color: white;
    min-width: 140px;
    box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
    border-radius: 8px;
    padding: 5px;
    z-index: 1000;
    margin-top: 13px;

  }

  .dropdown-content p {
    margin-left: 8px;
  }

  .dropdown-content a {
    display: block;
    padding: 8px;
    text-decoration: none;
    color: #333;
  }

  .dropdown-content a:hover {
    background-color: #f0ecfc;
  }

  .dropdown-content hr {
    margin: 10px 0;
    border: 0.5px solid #ccc;
  }

  .show {
    display: block;
  }
</style>

<nav class="navbar">
  <div class="mobile-menu" onclick="toggleMobileMenu()">
    <span></span>
    <span></span>
    <span></span>
  </div>

  <div class="brand-name">Hype & Humble</div>

  <ul class="nav-links" id="navLinks">
    <?php if ($is_logged_in): ?>
      <li><a href="/home/userDashboard.php">Dashboard</a></li>
    <?php else: ?>
      <li><a href="/index.html">Home</a></li>
    <?php endif; ?>
    <li><a href="/home/search/search.php">Browse</a></li>
    <li><a href="/home/review/review.php">Write a review</a></li>
    <?php if ($is_logged_in): ?>
      <li><a href="/home/message_handler/user_message_portal.php">Chat</a></li>
    <?php endif; ?>
  </ul>

  <div class="nav-right">

    <a href="/home/search/search.php" style="text-decoration: none;" class="search-icon">🔍</a>

    <div class="account-photo">
      <?php
      $defaultAvatar = 'https://cdn-icons-png.flaticon.com/512/3276/3276535.png';
      $rawPath = $user['avatar_path'] ?? '';
      $avatarPathClean = ltrim($rawPath, '/');
      $finalURL = (!empty($avatarPathClean))
        ? (str_starts_with($avatarPathClean, 'http') ? $avatarPathClean : 'http://' . $avatarPathClean)
        : $defaultAvatar;
      ?>
      <img src="<?= htmlspecialchars($finalURL, ENT_QUOTES, 'UTF-8') ?>"
        onclick="toggleDropdown()"
        alt="Profile picture">
      <div class="dropdown-content" id="dropdownMenu">
        <p><?php echo $user_name; ?></p>
        <hr />
        <?php if ($is_logged_in): ?>
          <a href="/profileManagement/user_profile.php">View Account</a>
          <a href="/profileManagement/logout.php">Sign Out</a>
        <?php else: ?>
          <a href="/profileManagement/login.php">Login</a>
          <a href="/profileManagement/registration.php">Register</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<script>
  function toggleDropdown() {
    document.getElementById("dropdownMenu").classList.toggle("show");
  }

  window.onclick = function(e) {
    if (!e.target.matches(".account-photo img")) {
      let dropdown = document.getElementById("dropdownMenu");
      if (dropdown && dropdown.classList.contains("show")) {
        dropdown.classList.remove("show");
      }
    }
  };

  function toggleMobileMenu() {
    document.getElementById("navLinks").classList.toggle("active");
  }
</script>