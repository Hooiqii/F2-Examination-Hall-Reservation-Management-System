<?php
require_once('db_connection.php');

// Check if the token is set in the GET request
if (isset($_GET["token"])) {
    $token = $_GET["token"];

    // Hash the token using SHA-256
    $token_hash = hash("sha256", $token);

    // Prepare SQL query to find user with the matching reset token hash
    $sql = "SELECT * FROM users
            WHERE reset_token_hash = ?";

    $stmt = $conn->prepare($sql);

    // Bind the token hash to the prepared statement
    $stmt->bind_param("s", $token_hash);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Fetch user data
    $user = $result->fetch_assoc();

    // If no user is found, terminate with an error message
    if ($user === null) {
        die("token not found");
    }

    // Check if the token has expired
    if (strtotime($user["reset_token_expires_at"]) <= time()) {
        die("token has expired");
    }
}

// Check if the token is set in the POST request (for form submission)
if (isset($_POST["token"])) {
    $token = $_POST["token"];

    // Hash the token using SHA-256
    $token_hash = hash("sha256", $token);

    // Prepare SQL query to find user with the matching reset token hash
    $sql = "SELECT * FROM users
            WHERE reset_token_hash = ?";

    $stmt = $conn->prepare($sql);

    // Bind the token hash to the prepared statement
    $stmt->bind_param("s", $token_hash);

    // Execute the statement
    $stmt->execute();

    // Get the result
    $result = $stmt->get_result();

    // Fetch user data
    $user = $result->fetch_assoc();

    // If no user is found, terminate with an error message
    if ($user === null) {
        die("token not found");
    }

    // Check if the token has expired
    if (strtotime($user["reset_token_expires_at"]) <= time()) {
        die("token has expired");
    }

    // Validate the new password length
    if (strlen($_POST["password"]) < 8) {
        die("Password must be at least 8 characters");
    }

    // Validate the new password contains at least one letter
    if (!preg_match("/[a-z]/i", $_POST["password"])) {
        die("Password must contain at least one letter");
    }

    // Validate the new password contains at least one number
    if (!preg_match("/[0-9]/", $_POST["password"])) {
        die("Password must contain at least one number");
    }

    // Validate that the password and confirmation match
    if ($_POST["password"] !== $_POST["password_confirmation"]) {
        die("Passwords must match");
    }

    // Hash the new password
    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Prepare SQL query to update the user's password and clear the reset token
    $sql = "UPDATE users
            SET user_pw = ?,
                reset_token_hash = NULL,
                reset_token_expires_at = NULL
            WHERE user_id = ?";

    $stmt = $conn->prepare($sql);

    // Bind the new password hash and user ID to the prepared statement
    $stmt->bind_param("ss", $password_hash, $user["user_id"]);

    // Execute the statement
    $stmt->execute();

    // Output success message
    echo '<script>alert("Password updated. You can now login.");</script>';
}
?>

<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/resetPassword.css">
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css"> -->
    <title>Reset Password | F2 Examination Hall</title>
</head>

<body>
    <div class="logo">
        <img src="images/uthmLogo.png" alt="uthmLogo">
        <h2 id="system">F2 Examination Hall Reservation Management System</h2>
    </div>

    <form id="resetPasswordForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
        <div class="input-group">
            <h2>Reset Password</h2>
            <br>
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            
            <label for="password">New Password:</label>
            <div class="password-container">
            <input type="password" id="password" name="password" placeholder="Enter new password" required>
            <i class="fa-solid fa-eye" id="togglePassword"></i>
            </div>

            <label for="password_confirmation">Confirm Password:</label>
            <div class="password-container">
            <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Confirm your password" required>
            <i class="fa-solid fa-eye" id="toggleConfirmPassword"></i>
            </div>

            <button type="submit">Submit</button>
            <br><br>
            <a href="login.php"><i class="fa-solid fa-arrow-left"></i>Back to Login</a>
        </div>
    </form>
    <?php require_once('footer_outSystem.php'); ?>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('togglePassword');
            const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('password_confirmation');
            const resetPasswordForm = document.getElementById('resetPasswordForm');

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

            resetPasswordForm.addEventListener('submit', function(event) {
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