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

// Function to get the latest uploaded PDF file details
function getLatestPDFDetails() {
    global $conn;
    $sql = "SELECT manual_filename, manual_filepath, update_at, admin_id FROM manual ORDER BY manual_id DESC LIMIT 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Check if user is logged in and get admin_id from session
    if (!isset($_SESSION["admin_id"])) {
        // Redirect or handle error if admin is not logged in
        exit("Error: Admin not logged in.");
    }

    // Check if file was uploaded successfully
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        // File details
        $targetDir = "manual/";
        $fileName = basename($_FILES['file']['name']);
        $targetPath = $targetDir . $fileName;

        // Move uploaded file to target directory
        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
            // Prepare SQL statement to insert manual details into database
            $sql = "INSERT INTO manual (manual_filename, manual_filepath, admin_id) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);

            // Bind parameters
            $stmt->bind_param("ssi", $fileName, $targetPath, $_SESSION["admin_id"]);

            // Execute SQL statement
            if ($stmt->execute()) {
                echo "<script>alert('File uploaded successfully!');</script>";
            } else {
                echo "<script>alert('Error uploading file to database.');</script>";
            }
        } else {
            echo "<script>alert('Error moving uploaded file.');</script>";
        }
    } else {
        echo "<script>alert('Error uploading file.');</script>";
    }
}

// Get the latest uploaded PDF file details
$latestPDFDetails = getLatestPDFDetails();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/manual_admin.css">
    <title>Manual | F2 Examination Hall</title>
</head>

<body>
    <!-- Include header, navbar, and breadcrumb if needed -->
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>

    <div class="content-wrapper">
        <h1>User Manual</h1>
        <div class="border-wrapper">
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                <h2 class="title">Manual Settings</h2>
                <label for="file"><b>Upload File:</b></label><br>
                <input type="file" id="file" name="file" accept=".pdf"><br>
                <input type="submit" value="Upload File">
            </form>
            <br><hr>
            <!-- Display the latest uploaded PDF file details -->
            <?php if ($latestPDFDetails): ?>
                <h2>Latest Uploaded File Details</h2>
                <p><b>Filename: </b><?php echo $latestPDFDetails['manual_filename']; ?></p>
                <p><b>Uploaded by Admin ID: </b><?php echo $latestPDFDetails['admin_id']; ?></p>
                <p><b>Last Updated: </b><?php echo $latestPDFDetails['update_at']; ?></p>

            <?php endif; ?>

            <!-- Display the latest uploaded PDF file if available -->
            <?php if ($latestPDFDetails): ?>
                <p><b>User View: </b></p>
                <embed src="<?php echo $latestPDFDetails['manual_filepath']; ?>" type="application/pdf" width="100%" height="1000px" />
            <?php endif; ?>
        </div>
    </div>

    <!-- Include footer if needed -->
    <?php require_once('footer.php'); ?>
</body>

</html>