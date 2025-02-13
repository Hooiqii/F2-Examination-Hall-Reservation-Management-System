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

// Fetch current hall capacities and remarks from the database
$sql = "SELECT * FROM hall WHERE hall_floor IN ('top', 'low')";
$result = mysqli_query($conn, $sql);
$hall_data = array();
while ($row = mysqli_fetch_assoc($result)) {
    $hall_data[$row['hall_floor']] = array(
        'capacity' => $row['hall_capacity'],
        'remark' => $row['hall_remark']
    );
}

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve the new capacities and remarks from the form
    $top_capacity = $_POST['top_capacity'];
    $top_remark = $_POST['top_remark'];
    $lower_capacity = $_POST['lower_capacity'];
    $lower_remark = $_POST['lower_remark'];

    // Update the hall capacities and remarks in the database
    $sql_update = "UPDATE hall SET 
                    hall_capacity = CASE hall_floor 
                        WHEN 'top' THEN $top_capacity 
                        WHEN 'low' THEN $lower_capacity 
                    END,
                    hall_remark = CASE hall_floor
                        WHEN 'top' THEN '$top_remark'
                        WHEN 'low' THEN '$lower_remark'
                    END
                    WHERE hall_floor IN ('top', 'low')";
    if (mysqli_query($conn, $sql_update)) {
        // Return a JSON response indicating success
        $response = [
            'success' => true,
            'message' => 'Settings updated successfully.'
        ];
        echo json_encode($response);
        exit(); // Terminate further execution
    } else {
        // If there's an error, return an error message
        $response = [
            'success' => false,
            'message' => 'Error updating settings: ' . mysqli_error($conn)
        ];
        echo json_encode($response);
        exit(); // Terminate further execution
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="styles/setting.css">
    <title>Settings | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Settings</h1>
        <div class="border-wrapper">
            <form id="updateForm" method="post">
                <h2>Top Level</h2>
                <label for="top_capacity">Hall Capacity:</label>
                <input type="number" id="top_capacity" name="top_capacity" value="<?php echo $hall_data['top']['capacity']; ?>" required min="0"><br>
                <label for="top_remark">Remark:</label>
                <input type="text" id="top_remark" name="top_remark" value="<?php echo $hall_data['top']['remark']; ?>" required><br><br>
                <h2>Lower Level</h2>
                <label for="lower_capacity">Hall Capacity:</label>
                <input type="number" id="lower_capacity" name="lower_capacity" value="<?php echo $hall_data['low']['capacity']; ?>" required min="0"><br>
                <label for="lower_remark">Remark:</label>
                <input type="text" id="lower_remark" name="lower_remark" value="<?php echo $hall_data['low']['remark']; ?>" required><br>

                <input type="submit" value="Save" onclick="return confirm('Are you sure you want to update the setting?');">
            </form>
            <div class="update_log">

            </div>
        </div>
    </div>
    <!-- Popup message for success -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <p>Settings updated successfully.</p>
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

                fetch("setting.php", {
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
