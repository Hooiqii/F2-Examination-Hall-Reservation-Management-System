<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Function to sanitize input data
function sanitizeInput($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize POST data
    $user_name = sanitizeInput($_POST['user_name']);
    $user_username = sanitizeInput($_POST['user_username']);
    $original_pw = $_POST['original_pw'];

    if (!empty($_POST['user_pw'])) {
        $user_pw = sanitizeInput($_POST['user_pw']);
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $user_pw)) {
            $response = [
                'success' => false,
                'message' => 'Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, one number, and one special character.'
            ];
            echo json_encode($response);
            exit();
        }
        $user_pw = password_hash($user_pw, PASSWORD_DEFAULT);
    } else {
        $user_pw = $original_pw;
    }

    $user_email = filter_var(sanitizeInput($_POST['user_email']), FILTER_VALIDATE_EMAIL);
    $user_contact = sanitizeInput($_POST['user_contact']);

    // Validate inputs
    if (!preg_match("/^[a-zA-Z\s]+$/", $user_name)) {
        $response = [
            'success' => false,
            'message' => 'Full Name should only contain letters and spaces'
        ];
        echo json_encode($response);
        exit();
    }

    if (!preg_match("/^[0-9]+$/", $user_contact)) {
        $response = [
            'success' => false,
            'message' => 'Contact Number should only contain numbers'
        ];
        echo json_encode($response);
        exit();
    }

    if (!$user_email) {
        $response = [
            'success' => false,
            'message' => 'Invalid email format'
        ];
        echo json_encode($response);
        exit();
    }

    // Check if username or email already exists
    $query = "SELECT * FROM users WHERE (user_username = ? OR user_email = ?) AND user_id != ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ssi', $user_username, $user_email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $existing_user = $result->fetch_assoc();
        if ($existing_user['user_username'] === $user_username) {
            $response = [
                'success' => false,
                'message' => 'Username already exists! Please choose a different username.'
            ];
            echo json_encode($response);
            exit();
        } elseif ($existing_user['user_email'] === $user_email) {
            $response = [
                'success' => false,
                'message' => 'Email address is already registered! Please use a different email address.'
            ];
            echo json_encode($response);
            exit();
        }
    }

    $query = "UPDATE users SET user_name=?, user_username=?, user_pw=?, user_email=?, user_contact=? WHERE user_id=?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('sssssi', $user_name, $user_username, $user_pw, $user_email, $user_contact, $user_id);

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Profile updated successfully.'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error updating profile: ' . $conn->error
        ];
    }

    echo json_encode($response);
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/profile.css">
    <title>Profile | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_user.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Profile Details</h1>
        <div class="border-wrapper">
            <form id="updateForm" method="POST">
                <label for="user_name">Full Name:</label>
                <input type="text" id="user_name" name="user_name" value="<?php echo $user['user_name']; ?>" required><br>

                <label for="user_username">Username:</label>
                <input type="text" id="user_username" name="user_username" value="<?php echo $user['user_username']; ?>" required><br>

                <input type="hidden" name="original_pw" value="<?php echo $user['user_pw']; ?>">

                <label for="user_pw">Password:</label>
                <input type="text" id="user_pw" name="user_pw" placeholder="Enter new password or leave blank to keep the current password"><br>

                <label for="user_email">Email:</label>
                <input type="email" id="user_email" name="user_email" value="<?php echo $user['user_email']; ?>" required><br>

                <label for="user_contact">Contact:</label>
                <input type="text" id="user_contact" name="user_contact" value="<?php echo $user['user_contact']; ?>"><br>

                <input type="submit" value="Save" onclick="return confirm('Are you sure you want to update your profile?');">
            </form>
        </div>
    </div>
    <!-- Popup message for success -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Profile updated successfully.</p>
        </div>
    </div>
    <?php require_once('footer.php'); ?>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("updateForm");
            const successModal = document.getElementById("successModal");
            const closeModal = document.querySelector(".close");

            form.addEventListener("submit", function(event) {
                event.preventDefault();

                const formData = new FormData(form);

                fetch("profile.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show the modal
                        successModal.style.display = "block";

                        // Close modal when the close button is clicked
                        closeModal.addEventListener("click", function() {
                            successModal.style.display = "none";
                        });

                        // Hide the modal after 3 seconds
                        setTimeout(() => {
                            successModal.style.display = "none";
                        }, 2000);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                });
            });
        });
    </script>
</body>
</html>
