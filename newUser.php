<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if the logged-in user is an admin
if ($_SESSION['role'] !== 'admin') {
    // Redirect to login page or display an error page
    header('Location: login.php');
    exit();
}

// Function to sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Function to check if the username already exists
function usernameExists($username, $conn) {
    $sql = "SELECT user_username FROM users WHERE user_username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();
    return $num_rows > 0;
}

// Function to check if the email already exists
function emailExists($email, $conn) {
    $sql = "SELECT user_email FROM users WHERE user_email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $num_rows = $stmt->num_rows;
    $stmt->close();
    return $num_rows > 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize POST data
    $user_name = sanitizeInput($_POST['user_name']);
    $user_username = sanitizeInput($_POST['user_username']);
    $user_pw = sanitizeInput($_POST['user_pw']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);
    $user_email = filter_var(sanitizeInput($_POST['user_email']), FILTER_VALIDATE_EMAIL);
    $user_contact = sanitizeInput($_POST['user_contact']);

    // Validate inputs
    if (!preg_match("/^[a-zA-Z\s]+$/", $user_name)) {
        $_SESSION['form_data'] = $_POST;
        echo "<script>alert('Full Name should only contain letters and spaces');</script>";
        echo '<script>window.location.href = "newUser.php";</script>';
        exit();
    }

    if (!preg_match("/^[0-9]+$/", $user_contact)) {
        $_SESSION['form_data'] = $_POST;
        echo "<script>alert('Contact Number should only contain numbers');</script>";
        echo '<script>window.location.href = "newUser.php";</script>';
        exit();
    }

    if (!$user_email) {
        $_SESSION['form_data'] = $_POST;
        echo "<script>alert('Invalid email format');</script>";
        echo '<script>window.location.href = "newUser.php";</script>';
        exit();
    }

    if ($user_pw !== $confirm_password) {
        $_SESSION['form_data'] = $_POST;
        echo "<script>alert('Passwords do not match');</script>";
        echo '<script>window.location.href = "newUser.php";</script>';
        exit();
    }

    // Check password strength
    $pattern = "/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/";
    if (!preg_match($pattern, $user_pw)) {
        $_SESSION['form_data'] = $_POST;
        echo "<script>alert('Password must be at least 8 characters long, include at least one uppercase letter, one lowercase letter, one number, and one special character');</script>";
        echo '<script>window.location.href = "newUser.php";</script>';
        exit();
    }

    // Check if username already exists
    if (usernameExists($user_username, $conn)) {
        $_SESSION['form_data'] = $_POST;
        echo "<script>alert('Username already exists! Please choose a different username.');</script>";
        echo '<script>window.location.href = "newUser.php";</script>';
        exit();
    }

    // Check if email already exists
    if (emailExists($user_email, $conn)) {
        $_SESSION['form_data'] = $_POST;
        echo "<script>alert('Email address is already registered! Please use a different email address.');</script>";
        echo '<script>window.location.href = "newUser.php";</script>';
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($user_pw, PASSWORD_DEFAULT);

    // Insert data into users table
    $sql = "INSERT INTO users (user_name, user_username, user_pw, user_email, user_contact, user_isActive) VALUES (?, ?, ?, ?, ?, TRUE)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $user_name, $user_username, $hashed_password, $user_email, $user_contact);

    if ($stmt->execute()) {
        echo "<script>alert('User account created successfully');</script>";
        echo '<script>window.location.href = "user.php";</script>';
    } else {
        echo "<script>alert('Error creating user account');</script>";
        $_SESSION['form_data'] = $_POST;
    }

    $stmt->close();
    $conn->close();
}

// Check if form data exists in session and populate the form fields
$form_data = isset($_SESSION['form_data']) ? $_SESSION['form_data'] : [];
unset($_SESSION['form_data']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/newUser.css">
    <title>New User | F2 Examination Hall</title>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const userPw = document.getElementById('user_pw');
            const confirmUserPw = document.getElementById('confirm_password');

            togglePassword.addEventListener('click', function() {
                toggleVisibility(userPw, togglePassword);
            });

            toggleConfirmPassword.addEventListener('click', function() {
                toggleVisibility(confirmUserPw, toggleConfirmPassword);
            });

            function toggleVisibility(inputElement, eyeElement) {
                const type = inputElement.getAttribute('type') === 'password' ? 'text' : 'password';
                inputElement.setAttribute('type', type);

                if (eyeElement.classList.contains('fa-eye-slash')) {
                    eyeElement.classList.remove('fa-eye-slash');
                    eyeElement.classList.add('fa-eye');
                } else {
                    eyeElement.classList.remove('fa-eye');
                    eyeElement.classList.add('fa-eye-slash');
                }
            }
        });
    </script>
</head>
<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>New User Account</h1>
        <div class="border-wrapper">
            <form method="POST">
                <label for="user_name">Full Name:</label>
                <input type="text" id="user_name" name="user_name" value="<?php echo $form_data['user_name'] ?? ''; ?>" required><br>

                <label for="user_username">Username:</label>
                <input type="text" id="user_username" name="user_username" value="<?php echo $form_data['user_username'] ?? ''; ?>" required><br>

                <label for="user_pw">Password:</label>
                <div class="password-container">
                    <input type="password" id="user_pw" name="user_pw" required>
                    <i class="fa-solid fa-eye" id="togglePassword"></i>
                </div>

                <label for="confirm_password">Confirm Password:</label>
                <div class="password-container">
                    <input type="password" id="confirm_password" name="confirm_password" required>
                    <i class="fa-solid fa-eye" id="toggleConfirmPassword"></i>
                </div>

                <label for="user_email">Email:</label>
                <input type="email" id="user_email" name="user_email" value="<?php echo $form_data['user_email'] ?? ''; ?>" required><br>

                <label for="user_contact">Contact:</label>
                <input type="text" id="user_contact" name="user_contact" value="<?php echo $form_data['user_contact'] ?? ''; ?>" required><br>

                <input type="submit" value="Create">
            </form>
        </div>
    </div>
</body>
</html>
