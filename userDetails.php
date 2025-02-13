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

$user_id = $_GET['id']; // Get the user_id from the URL parameter
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $user_name = $_POST['user_name'];
    $user_username = $_POST['user_username'];
    $user_email = $_POST['user_email'];
    $user_contact = $_POST['user_contact'];

    // Check if the user wants to update the password
    $query = "UPDATE users SET user_name=?, user_username=?, user_email=?, user_contact=?";
    $params = [$user_name, $user_username, $user_email, $user_contact];

    // Check if the password field is not empty
    if (!empty($_POST['user_pw'])) {
        $query .= ", user_pw=?";
        $hashed_user_pw = password_hash($_POST['user_pw'], PASSWORD_DEFAULT);
        $params[] = $hashed_user_pw;
    }

    $query .= " WHERE user_id=?";
    $params[] = $user_id;

    // Prepare and bind parameters
    $stmt = $conn->prepare($query);
    
    // Dynamically bind parameters based on their types
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);

    // Execute SQL statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'User details updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating user details: ' . $conn->error]);
    }

    // Close statement and connection
    $stmt->close();
    $conn->close();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/userDetails.css">
    <title>User Details | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>User Details</h1>
        <div class="border-wrapper">
        <h3>User ID -- <?php echo $user_id; ?></h3>
            <form id="updateForm" method="POST">
                <label for="user_name">Full Name:</label>
                <input type="text" id="user_name" name="user_name" value="<?php echo $user['user_name']; ?>" required><br>

                <label for="user_username">Username:</label>
                <input type="text" id="user_username" name="user_username" value="<?php echo $user['user_username']; ?>" required><br>

                <label for="user_pw">Password:</label>
                <input type="text" id="user_pw" name="user_pw" value="" placeholder="Enter new password or leave blank"><br>

                <label for="user_email">Email:</label>
                <input type="email" id="user_email" name="user_email" value="<?php echo $user['user_email']; ?>" required><br>

                <label for="user_contact">Contact:</label>
                <input type="text" id="user_contact" name="user_contact" value="<?php echo $user['user_contact']; ?>"><br>

                <input type="submit" value="Save" onclick="return confirm('Are you sure you want to update your details?');">
            </form>
        </div>
    </div>
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>User details updated successfully!</p>
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

                fetch("userDetails.php?id=<?php echo $user_id; ?>", {
                        method: "POST",
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Show the success modal
                            successModal.style.display = "block";

                            // Close modal when the close button is clicked
                            closeModal.addEventListener("click", function() {
                                successModal.style.display = "none";
                            });

                            // Hide the modal after 3 seconds
                            setTimeout(() => {
                                successModal.style.display = "none";
                            }, 3000);

                            console.log(data.message);
                        } else {
                            console.error(data.message);
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