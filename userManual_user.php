<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

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

// Get the latest uploaded PDF file details
$latestPDFDetails = getLatestPDFDetails();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Manual | F2 Examination Hall</title>
    <style>
        body,
        html {
            font-family: Cambria, Georgia, serif;
        }

        .content-wrapper {
            margin-left: 240px;
            padding: 10px 20px;
            background-color: #fff;
            min-height: calc(100vh - 50px);
        }

        h1 {
            margin-top: 0;
            margin-bottom: 15px;
        }

        .border-wrapper {
            border: 1px solid #000000;
            padding: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_user.php'); ?>
    <?php require_once('breadcrumb.php'); ?>

    <div class="content-wrapper">
        <h1>User Manual</h1>
        <div class="border-wrapper">
        <embed src="<?php echo $latestPDFDetails['manual_filepath']; ?>" type="application/pdf" width="100%" height="800px" />
        </div>
    </div>
    <?php require_once('footer.php'); ?>
</body>

</html>