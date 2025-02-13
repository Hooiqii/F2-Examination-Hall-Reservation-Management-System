<?php
require_once('db_connection.php');

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email address";
    } else {
        $sql = "SELECT user_id FROM users WHERE user_email = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $message = "Database error: " . $conn->error;
        } else {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($user_id);
                $stmt->fetch();

                $token = bin2hex(random_bytes(16));
                $token_hash = hash("sha256", $token);
                $expiry = date("Y-m-d H:i:s", time() + 60 * 30);

                $sql = "UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE user_email = ?";
                $stmt = $conn->prepare($sql);
                if (!$stmt) {
                    $message = "Database error: " . $conn->error;
                } else {
                    $stmt->bind_param("sss", $token_hash, $expiry, $email);
                    $stmt->execute();

                    if ($conn->affected_rows) {
                        $mail = require __DIR__ . "/mailer.php";

                        $mail->setFrom("noreply@gmail.com", "noreply");
                        $mail->addAddress($email);
                        $mail->isHTML(true);
                        $mail->Subject = "Password Reset";
                        $mail->Body = 'Click <a href="http://localhost/FYP/resetPassword.php?token=' . urlencode($token) . '">here</a> to reset your password.';

                        try {
                            $mail->send();
                            $message = "Message sent, please check your inbox.";
                        } catch (Exception $e) {
                            $message = "Unable to send the email. Please try again later.";
                        }
                    }
                }
            } else {
                $message = "No account found with that email address.";
            }
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
    <link rel="stylesheet" href="styles/forgotPassword.css">
    <title>Forgot Password | F2 Examination Hall</title>
</head>

<body>
    <div class="logo">
        <img src="images/uthmLogo.png" alt="uthmLogo">
        <h2 id="system">F2 Examination Hall Reservation Management System</h2>
    </div>
    <form id="forgotPasswordForm" method="post" action="forgotPassword.php">
        <div class="input-group">
            <h2>Forgot Password</h2>
            <p>Enter the email address associated with your account and we'll send you a link to reset your password.</p>
            <br>
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" placeholder="Enter your email" required>
            <button type="submit">Submit</button>
            <br><br>
            <a href="login.php"><i class="fa-solid fa-arrow-left"></i>Back to Login</a>
        </div>
    </form>
    <?php require_once('footer_outSystem.php'); ?>
    <?php if ($message): ?>
    <script>
        alert('<?php echo $message; ?>');
    </script>
    <?php endif; ?>
</body>

</html>
