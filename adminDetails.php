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

$admin_id = $_GET['id']; // Get the admin_id from the URL parameter
$query = "SELECT * FROM admin WHERE admin_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $admin_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $admin_name = $_POST['admin_name'];
    $admin_username = $_POST['admin_username'];
    $admin_email = $_POST['admin_email'];
    $admin_contact = $_POST['admin_contact'];

    // Check if the admin wants to update the password
    $query = "UPDATE admin SET admin_name=?, admin_username=?, admin_email=?, admin_contact=?";
    $params = [$admin_name, $admin_username, $admin_email, $admin_contact];

    // Check if the password field is not empty
    if (!empty($_POST['admin_pw'])) {
        $query .= ", admin_pw=?";
        $hashed_admin_pw = password_hash($_POST['admin_pw'], PASSWORD_DEFAULT);
        $params[] = $hashed_admin_pw;
    }

    $query .= " WHERE admin_id=?";
    $params[] = $admin_id;

    // Prepare and bind parameters
    $stmt = $conn->prepare($query);
    
    // Dynamically bind parameters based on their types
    $types = str_repeat('s', count($params));
    $stmt->bind_param($types, ...$params);

    // Execute SQL statement
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Admin details updated successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating admin details: ' . $conn->error]);
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
    <link rel="stylesheet" href="styles/adminDetails.css">
    <title>Administrator Details | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Administrator Details</h1>
        <div class="border-wrapper">
        <h3>Admin ID -- <?php echo $admin_id; ?></h3>
            <form id="updateForm" method="POST">
                <label for="admin_name">Full Name:</label>
                <input type="text" id="admin_name" name="admin_name" value="<?php echo $admin['admin_name']; ?>" required><br>

                <label for="admin_username">Username:</label>
                <input type="text" id="admin_username" name="admin_username" value="<?php echo $admin['admin_username']; ?>" required><br>

                <label for="admin_pw">Password:</label>
                <input type="text" id="admin_pw" name="admin_pw" value="" placeholder="Enter new password or leave blank"><br>

                <label for="admin_email">Email:</label>
                <input type="email" id="admin_email" name="admin_email" value="<?php echo $admin['admin_email']; ?>" required><br>

                <label for="admin_contact">Contact:</label>
                <input type="text" id="admin_contact" name="admin_contact" value="<?php echo $admin['admin_contact']; ?>"><br>

                <input type="submit" value="Save" onclick="return confirm('Are you sure you want to update your details?');">
            </form>
        </div>
    </div>
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Admin details updated successfully!</p>
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

                fetch("adminDetails.php?id=<?php echo $admin_id; ?>", {
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