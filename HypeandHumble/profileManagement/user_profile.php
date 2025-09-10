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

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit();
}

$user_id = $_SESSION['user_id'];

// Load user data into session (with avatar_path)
if (isset($_SESSION['user_data'])) {
  $user = $_SESSION['user_data'];
  if (empty($user['avatar_path'])) {
    $stmt = $conn->prepare("SELECT avatar_path FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($avatarPath);
    $stmt->fetch();
    $stmt->close();
    $user['avatar_path'] = $avatarPath;
    $_SESSION['user_data']['avatar_path'] = $avatarPath;
  }
} else {
  $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
  $stmt->bind_param("i", $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $_SESSION['user_data'] = $user;
  } else {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
  }
  $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Edit Profile</title>
  <link rel="icon" type="image/png" href="/images/H_and_H_Logo.png">
  <style>
    body {
      margin: 0;
      font-family: 'Arial', sans-serif;
      background-color: #f9f9f9;
      padding: 15px;
    }

    .edit-profile-container {
      max-width: 900px;
      width: 90%;
      margin: 30px auto;
      padding: 35px;
      background-color: #f8f5ff;
      border-radius: 18px;
      box-shadow: 0 6px 20px rgba(106, 90, 205, 0.1);
      border: 1px solid #eae4ff;
    }

    .edit-profile-container h1 {
      text-align: center;
      color: #5a4ab5;
      margin-bottom: 35px;
      font-size: 28px;
      font-weight: 600;
    }

    .profile-content {
      display: flex;
      flex-wrap: wrap;
      gap: 50px;
      justify-content: center;
    }

    .profile-left {
      flex: 1;
      min-width: 260px;
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 25px;
    }

    .profile-photo {
      width: 190px;
      height: 190px;
      border-radius: 50%;
      background-color: #f4f0fb;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      margin-bottom: 25px;
      border: 3px solid #d9d0ff;
      box-shadow: 0 4px 12px rgba(106, 90, 205, 0.15);
    }

    .profile-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .profile-photo svg {
      width: 90px;
      height: 90px;
      color: #6a5acd;
    }

    .btn {
      background-color: #6a5acd;
      color: white;
      border: none;
      border-radius: 28px;
      padding: 12px 22px;
      font-size: 15px;
      cursor: pointer;
      transition: all 0.3s;
      width: 100%;
      margin-top: 12px;
      text-align: center;
    }

    .btn:hover {
      background-color: #5a4ab5;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(106, 90, 205, 0.25);
    }

    #myFile {
      display: none;
    }

    .profile-right {
      flex: 2;
      min-width: 320px;
      background-color: white;
      padding: 35px;
      border-radius: 16px;
      box-shadow: 0 4px 15px rgba(106, 90, 205, 0.08);
    }

    .profile-right form {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }

    .profile-right label {
      font-weight: 500;
      color: #555;
      font-size: 15px;
      margin-bottom: 6px;
      display: block;
    }

    .profile-right input {
      padding: 12px 18px;
      border: 1px solid #e0e0ff;
      border-radius: 9px;
      font-size: 15px;
      width: 100%;
      background-color: #fbfaff;
    }

    .profile-right input:focus {
      outline: none;
      border-color: #a29bfe;
      box-shadow: 0 0 0 3px rgba(106, 90, 205, 0.15);
    }

    @media (max-width: 768px) {
      .edit-profile-container {
        padding: 25px;
        margin: 20px auto;
      }

      .profile-content {
        gap: 35px;
      }

      .profile-left,
      .profile-right {
        width: 100%;
        padding: 20px;
      }

      .profile-photo {
        width: 170px;
        height: 170px;
      }
    }
  </style>
</head>

<body>
  <?php include __DIR__ . '/../navbar.php'; ?>

  <div class="edit-profile-container">
    <h1>Edit profile</h1>
    <div class="profile-content">

      <!-- LEFT: Avatar -->
      <div class="profile-left">
        <div class="profile-photo">
          <?php if (!empty($user['avatar_path'])): ?>
            <?php
            $avatarPathClean = ltrim($user['avatar_path'], '/');
            $isFullUrl       = str_starts_with($avatarPathClean, 'http');
            $finalURL        = $isFullUrl ? $avatarPathClean : 'http://' . $avatarPathClean;
            ?>
            <img id="profilePreview" src="<?php echo htmlspecialchars($finalURL, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile Photo">
          <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
              <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
              <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8zm8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1z" />
            </svg>
          <?php endif; ?>
        </div>

        <form action="upload_photo.php" method="POST" enctype="multipart/form-data">
          <input type="file" id="myFile" name="filename" accept="image/*" onchange="previewImage(event)">
          <button type="button" class="btn" onclick="document.getElementById('myFile').click()">Upload Photo</button>
          <button type="submit" class="btn">Save Photo</button>
        </form>
        <form action="change_password.php" method="GET">
          <button class="btn">Change Password</button>
        </form>
      </div>

      <!-- RIGHT: Profile Form -->
      <div class="profile-right">
        <?php if (isset($_GET['updated'])): ?>
          <div style="margin-bottom:1rem; color:green;">Profile updated successfully!</div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['profile_errors'])): ?>
          <div style="margin-bottom:1rem; color:#b00;">
            <?php foreach ($_SESSION['profile_errors'] as $err): ?>
              <div><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
          </div>
          <?php unset($_SESSION['profile_errors']); ?>
        <?php endif; ?>

        <form action="update_profile.php" method="POST">
          <label>Name</label>
          <input
            type="text"
            name="name"
            value="<?php echo htmlspecialchars($_SESSION['profile_old']['name'] ?? $user['name'], ENT_QUOTES, 'UTF-8'); ?>">

          <label>Email</label>
          <input
            type="email"
            name="email"
            value="<?php echo htmlspecialchars($_SESSION['profile_old']['email'] ?? $user['email'], ENT_QUOTES, 'UTF-8'); ?>">

          <button type="submit" class="btn update-btn">Update</button>
        </form>
        <?php unset($_SESSION['profile_old']); ?>
      </div>

    </div>
  </div>

  <?php include __DIR__ . '/../footer.php'; ?>

  <script>
    function previewImage(event) {
      const input = event.target;
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
          const prev = document.getElementById('profilePreview');
          if (prev) {
            prev.src = e.target.result;
          } else {
            const svgEl = document.querySelector('.profile-photo svg');
            const img = document.createElement('img');
            img.id = 'profilePreview';
            img.src = e.target.result;
            svgEl.parentNode.replaceChild(img, svgEl);
          }
        };
        reader.readAsDataURL(input.files[0]);
      }
    }
  </script>
</body>

</html>