<?php
session_start();
require_once('db_connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if it's an admin or user login
    $stmt = $conn->prepare("SELECT * FROM admin WHERE admin_username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();

    if ($admin) {
        if (!$admin['admin_isActive']) {
            // If admin account is inactive, display message and prevent login
            echo "<script>
                    alert('This account is deactivated. Please contact the system administrator.');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        }
        
        if (password_verify($password, $admin['admin_pw'])) {
            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['username'] = $admin['admin_username'];
            $_SESSION['role'] = 'admin';
            header('Location: index.php');
            exit();
        } else {
            // Invalid password for admin
            echo "<script>
                    alert('Invalid username or password');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        }
    } else {
        // Check user table for login
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            if (!$user['user_isActive']) {
                // If admin account is inactive, display message and prevent login
                echo "<script>
                        alert('This account is deactivated. Please contact the system administrator.');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            }
            if (password_verify($password, $user['user_pw'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['user_username'];
                $_SESSION['role'] = 'user';
                header('Location: index.php');
                exit();
            } else {
                // Invalid password for user
                echo "<script>
                        alert('Invalid username or password');
                        window.location.href = 'login.php';
                      </script>";
                exit();
            }
        } else {
            // Username not found
            echo "<script>
                    alert('Account not found.');
                    window.location.href = 'login.php';
                  </script>";
            exit();
        }
    }
}
?>
<!-- filter: drop-shadow(5px 5px 2px rgb(255 255 255 / 0.4)); -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" /> -->
    <link rel="stylesheet" href="styles/login.css">
    <title>F2 Examination Hall</title>
</head>

<body>
    <div class="logo">
        <img src="images/uthmLogo.png" alt="uthmLogo">
        <h2 id="system">F2 Examination Hall Reservation Management System</h2>
    </div>
    <form id="loginForm" action="login.php" method="post">
        <h2>Login to your account</h2>
        <div class="input-group">
            <i class="fas fa-user" id="userIcon"></i>
            <input type="text" id="username" name="username" placeholder="Matric number or staff username..." required>
            <br><br>
            <div class="input-group password-group">
                <i class="fa-solid fa-lock" id="lockIcon"></i>
                <input type="password" id="password" name="password" placeholder="Password..." required>
                <i class="fa-solid fa-eye" id="togglePassword"></i>
            </div>
            <button type="submit">Login</button>
            <br><br>
            <hr>
            <h4>Forgot Your Password?</h4>
            <div class="loginForm-bottom">
                <p>Click <a href="forgotPassword.php">here</a> to retrieve your password</p>
                <a href="register.php">Create an account</a>
                <!-- <P id="note">*Use the same password as the SMAP account </P> -->
            </div>
        </div>
    </form>
    <?php require_once('footer_outSystem.php'); ?>
    <script>
        // Toggle password visibility
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle between eye and eye-slash icons
                if (togglePassword.classList.contains('fa-eye-slash')) {
                    togglePassword.classList.remove('fa-eye-slash');
                    togglePassword.classList.add('fa-eye');
                } else {
                    togglePassword.classList.remove('fa-eye');
                    togglePassword.classList.add('fa-eye-slash');
                }
            });
        });
    </script>
</body>

</html>
