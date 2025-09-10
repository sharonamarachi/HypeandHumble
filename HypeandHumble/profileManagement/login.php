<?php
session_start();

$error = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        $servername = "sql106.infinityfree.com";
        $username = "if0_38503886";
        $password_db = "StlFnsLkFkx";
        $database = "if0_38503886_hypehumbledb";
        $port = 3306;

        $conn = new mysqli($servername, $username, $password_db, $database, $port);

        if ($conn->connect_error) {
            $error = "Connection failed. Please try again later.";
        } else {
            $sql = "SELECT user_id, name, email, password, role, verified FROM users WHERE email = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();

                    $password_valid = false;

                    if (password_verify($password, $row['password'])) {
                        $password_valid = true;

                        // Check if password needs rehashing
                        /*if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                            $new_hash = password_hash($password, PASSWORD_DEFAULT);
                            // Update database with new hash
                            $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
                            $update_stmt = $conn->prepare($update_sql);
                            $update_stmt->bind_param("si", $new_hash, $row['user_id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                        }
                        */
                    } elseif ($row['password'] === $password) {
                        // Fallback for plain text passwords (TEMPORARY)
                        $password_valid = true;

                        /*
                        // Hash the plain text password and update database
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
                        $update_stmt = $conn->prepare($update_sql);
                        $update_stmt->bind_param("si", $hashedPassword, $row['user_id']);
                        $update_stmt->execute();
                        $update_stmt->close(); 
                        */
                    }

                    if ($password_valid) {
                        // Check if account is verified
                        if (!$row['verified']) {
                            $error = "Account not verified. Please check your email.";
                        } else {
                            // Regenerate session ID
                            session_regenerate_id(true);

                            // Set session variables
                            $_SESSION['user_id'] = $row['user_id'];
                            $_SESSION['name'] = $row['name'];
                            $_SESSION['email'] = $row['email'];
                            $_SESSION['role'] = $row['role'];
                            $_SESSION['logged_in'] = true;

                            // Redirect based on role
                            if ($row['role'] === 'admin') {
                                header("Location: ../adminManagement/adminDashboard.php");
                            } elseif ($row['role'] === 'provider') {
                                header("Location: ../businessManagement/buisness_services.php");
                            } elseif ($row['role'] === 'user') {
                                header("Location: ../home/userDashboard.php");
                            }
                            exit();
                        }
                    } else {
                        $error = "Invalid email or password";
                    }
                } else {
                    $error = "Invalid email or password";
                }
                $stmt->close();
            } else {
                $error = "Database error. Please try again.";
            }
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hype & Humble</title>
    <link rel="icon" type="image/png" href="../images/H_and_H_Logo.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
            position: relative;
            overflow-x: hidden;
            min-height: 100vh;
        }


        .container {
            max-width: 500px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 1;
            text-align: center;
        }

        .container img {
            margin-bottom: 1.5rem;
        }

        h1 {
            color: #6a1b9a;
            margin-bottom: 1.5rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        label {
            text-align: left;
            font-weight: 500;
            color: #555;
        }

        input {
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            transition: border 0.3s;
        }

        input:focus {
            outline: none;
            border-color: #6a1b9a;
        }

        .forgot-password {
            text-align: right;
            color: #6a1b9a;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .forgot-password:hover {
            text-decoration: underline;
        }

        button {
            background-color: #6a1b9a;
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #4a148c;
        }

        .error-message {
            color: #d32f2f;
            background-color: #fce4e4;
            padding: 0.8rem;
            border-radius: 4px;
            margin-bottom: 1rem;
            text-align: center;
        }

        .background-svg {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            z-index: 0;
        }

        .background-svg:first-of-type {
            z-index: -1;
        }

        .register-link {
            margin-top: 1.5rem;
            text-align: center;
        }

        .register-link a {
            color: #6a1b9a;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        @media (max-width: 600px) {
            .container {
                margin: 1rem;
                padding: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php include __DIR__ . '/../navbar.php'; ?>


    <div class="container">
        <img src="../images/H_and_H_Logo.png" alt="Hype & Humble Logo" style="width:250px;height:250px;">
        <h1>Login</h1>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>

            <a href="forgot_password.php" class="forgot-password">Forgot password?</a>

            <button type="submit">Login</button>
        </form>

        <div class="register-link">
            Don't have an account? <a href="registration.php">Register here</a>
        </div>
    </div>
    <svg class="background-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
        <path fill="#b39ddb" fill-opacity="1" d="M0,224L48,213.3C96,203,192,181,288,186.7C384,192,480,224,576,213.3C672,203,768,149,864,138.7C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
    </svg>
    <svg class="background-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320">
        <path fill="#6a1b9a" fill-opacity="1" d="M0,288L48,272C96,256,192,224,288,213.3C384,203,480,213,576,213.3C672,213,768,203,864,192C960,181,1056,171,1152,176C1248,181,1344,203,1392,213.3L1440,224L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path>
    </svg>

    <!-- Footer -->
    <?php include __DIR__ . '/../footer.php'; ?>
</body>

</html>