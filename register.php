<?php
require_once('db_connection.php');

$name = $email = $contact = $username = $password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $contact = trim($_POST['contact']);
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Password will be hashed and validated, so no need to sanitize here

    // Validate inputs
    $namePattern = '/^[A-Za-z\s]+$/';
    $contactPattern = '/^\d{12}$/';
    $emailPattern = '/^([a-zA-Z0-9._%+-]+)@(uthm.edu.my|student.uthm.edu.my)$/';

    if (!preg_match($namePattern, $name)) {
        echo "<script>alert('Full name should only contain letters and spaces.');</script>";
    } elseif (!preg_match($contactPattern, $contact)) {
        echo "<script>alert('Contact number should be 12 digits long and contain number only.');</script>";
    } elseif (!preg_match($emailPattern, $email)) {
        echo "<script>alert('Email address must be a valid UTHM email');</script>";
    } else {
        // Regular expression for strong password
        $passwordPattern = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

        if (!preg_match($passwordPattern, $password)) {
            echo "<script>alert('Password does not meet the security requirements. It must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.');</script>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password for security

            // Check if username already exists
            $checkUsernameSql = "SELECT * FROM users WHERE user_username = ?";
            $checkUsernameStmt = $conn->prepare($checkUsernameSql);
            $checkUsernameStmt->bind_param("s", $username);
            $checkUsernameStmt->execute();
            $usernameResult = $checkUsernameStmt->get_result();

            // Check if email already exists
            $checkEmailSql = "SELECT * FROM users WHERE user_email = ?";
            $checkEmailStmt = $conn->prepare($checkEmailSql);
            $checkEmailStmt->bind_param("s", $email);
            $checkEmailStmt->execute();
            $emailResult = $checkEmailStmt->get_result();

            if ($usernameResult->num_rows > 0) {
                echo "<script>alert('Username already exists! Please choose a different username.');</script>";
            } elseif ($emailResult->num_rows > 0) {
                echo "<script>alert('Email address is already registered! Please use a different email address.');</script>";
            } else {
                // If username and email are unique, proceed with registration
                $sql = "INSERT INTO users (user_name, user_username, user_pw, user_email, user_contact, user_isActive) VALUES (?, ?, ?, ?, ?, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssss", $name, $username, $hashed_password, $email, $contact);

                if ($stmt->execute() === TRUE) {
                    // Show successful registration message
                    echo "<script>alert('Account registered successfully!');</script>";
                    // Redirect to login.php after showing the message
                    echo "<script>window.location.href='login.php';</script>";
                    exit;  // Ensure no further code is executed after redirection
                } else {
                    echo "<script>alert('Error: " . $stmt->error . "');</script>";
                }

                $stmt->close();
            }

            $checkUsernameStmt->close();
            $checkEmailStmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/register.css">
    <title>Register</title>
</head>

<body>

    <div class="logo">
        <img src="images/uthmLogo.png" alt="uthmLogo">
        <h2 id="system">F2 Examination Hall Reservation Management System</h2>
    </div>
    <form id="registerForm" action="register.php" method="post">
        <h2>Register</h2>
        <div class="input-group">
            <label for="name">Full Name:</label>
            <input type="text" id="name" name="name" placeholder="Enter your name" value="<?php echo htmlspecialchars($name); ?>" required pattern="[A-Za-z\s]+" title="Full name should only contain letters and spaces.">
            <br>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" placeholder="Enter your email" value="<?php echo htmlspecialchars($email); ?>" required>
            <br>
            <label for="contact">Contact Number:</label>
            <input type="tel" id="contact" name="contact" placeholder="Enter your contact number" value="<?php echo htmlspecialchars($contact); ?>" required pattern="\d{10,15}" title="Contact number should be 12 digits long and contain number only.">
            <br>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" value="<?php echo htmlspecialchars($username); ?>" required>
            <br>
            <label for="password">Password:</label>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <i class="fa-solid fa-eye" id="togglePassword"></i>
            </div>
            <label for="confirmPassword">Confirm Password:</label>
            <div class="password-container">
                <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirm your password" required>
                <i class="fa-solid fa-eye" id="toggleConfirmPassword"></i>
            </div>

            <button type="submit">Sign Up</button>
            <p>Already have an account? <a href="login.php">Login here</a>
        </div>
    </form>
    <?php require_once('footer_outSystem.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const registerForm = document.getElementById('registerForm');

            togglePassword.addEventListener('click', function() {
                toggleVisibility(passwordInput, togglePassword);
            });

            toggleConfirmPassword.addEventListener('click', function() {
                toggleVisibility(confirmPasswordInput, toggleConfirmPassword);
            });

            function toggleVisibility(inputElement, eyeElement) {
                const type = inputElement.getAttribute('type') === 'password' ? 'text' : 'password';
                inputElement.setAttribute('type', type);
                eyeElement.classList.toggle('fa-eye');
                eyeElement.classList.toggle('fa-eye-slash');
            }

            registerForm.addEventListener('submit', function(event) {
                if (passwordInput.value !== confirmPasswordInput.value) {
                    event.preventDefault();
                    alert("Passwords do not match! Please ensure your passwords match before registering.");
                } else if (!isValidPassword(passwordInput.value)) {
                    event.preventDefault();
                    alert("Password does not meet the security requirements. It must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.");
                }
            });

            function isValidPassword(password) {
                const pattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                return pattern.test(password);
            }
        });
    </script>

</body>
</html>
