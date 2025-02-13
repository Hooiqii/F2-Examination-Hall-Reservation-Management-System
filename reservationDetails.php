<?php
// Set the timezone
date_default_timezone_set('Asia/Kuala_Lumpur');
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if the logged-in user is an admin
if ($_SESSION['role'] !== 'admin') {
    // Redirect to login page or display an error page
    header('Location: login.php');
    exit();
}

$bookingId = $_GET['id']; // Get the booking ID from the URL

// Fetch details of the specific booking based on the ID
$sql = "SELECT * FROM booking WHERE bk_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $bookingId);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // Collect form input values
    $purpose = $_POST['purpose'];
    $courseCode = $_POST['courseCode'];
    $facDepartment = $_POST['facDepartment'];
    $name = $_POST['name'];
    $contact = $_POST['contact'];
    $participantNo = $_POST['participantNo'];
    $remark = $_POST['remark'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];
    $floorSelection = $_POST['floorSelect'];
    $bookingDuration = $_POST['bookingDuration'];



    if (!empty($participantNo) && is_numeric($participantNo)) {

        if ($endDate == $startDate) {
            $endDate = $startDate;
        }
        // Fetch the hall capacity for the selected floor
        $sql_capacity = "SELECT hall_capacity FROM hall WHERE hall_floor = ?";
        $stmt_capacity = $conn->prepare($sql_capacity);
        $stmt_capacity->bind_param("s", $floorSelection);
        $stmt_capacity->execute();
        $result_capacity = $stmt_capacity->get_result();
        $data_capacity = $result_capacity->fetch_assoc();
        $hall_capacity = $data_capacity['hall_capacity'];

        // Split the time range into half-hour intervals
        $startTimeObj = new DateTime($startTime);
        $endTimeObj = new DateTime($endTime);
        $interval = new DateInterval('PT30M'); // 30 minutes
        $period = new DatePeriod($startTimeObj, $interval, $endTimeObj);

        // Initialize variable to track the maximum occupancy at any given time
        $max_occupancy = 0;

        // Loop through each day in the date range
        if ($endDate > $startDate) {
            $startDateObj = new DateTime($startDate);
            $endDateObj = new DateTime($endDate);
            $dateInterval = $startDateObj->diff($endDateObj);
            $diffInDays = $dateInterval->days;

            for ($i = 0; $i < $diffInDays; $i++) {
                $currentDate = clone $startDateObj; // Clone to avoid modifying the original object
                $currentDate->modify("+$i day");
                $currentDateStr = $currentDate->format('Y-m-d');

                // For each half-hour interval, calculate the booked capacity
                foreach ($period as $timeSlot) {
                    $currentTimeStr = $timeSlot->format('H:i');

                    // Query to get total participants for the current interval
                    $sql_total_capacity = "
                    SELECT SUM(bk_participantNo) as total_capacity
                    FROM booking
                    WHERE bk_floorSelection = ?
                    AND bk_startDate <= ? AND bk_endDate >= ?
                    AND ((bk_startTime <= ? AND bk_endTime > ?) OR (bk_startTime = ? AND bk_endTime = ?))
                    AND (bk_status = 'Approve' OR bk_status = 'Pending')
                ";
                    $stmt_total_capacity = $conn->prepare($sql_total_capacity);
                    $stmt_total_capacity->bind_param("sssssss", $floorSelection, $currentDateStr, $currentDateStr, $currentTimeStr, $currentTimeStr, $startTime, $endTime);
                    $stmt_total_capacity->execute();
                    $result_total_capacity = $stmt_total_capacity->get_result();
                    $data_total_capacity = $result_total_capacity->fetch_assoc();

                    // Track the maximum occupancy across all intervals
                    if ($data_total_capacity['total_capacity'] > $max_occupancy) {
                        $max_occupancy = $data_total_capacity['total_capacity'];
                    }
                }
            }
        } else {
            // Single day case: Calculate booked capacity for the given time range
            foreach ($period as $timeSlot) {
                $currentTimeStr = $timeSlot->format('H:i');

                // Query to get total participants for the current interval
                $sql_total_capacity = "
                SELECT SUM(bk_participantNo) as total_capacity
                FROM booking
                WHERE bk_floorSelection = ?
                AND bk_startDate <= ? AND bk_endDate >= ?
                AND ((bk_startTime <= ? AND bk_endTime > ?) OR (bk_startTime = ? AND bk_endTime = ?))
                AND (bk_status = 'Approve' OR bk_status = 'Pending')
            ";
                $stmt_total_capacity = $conn->prepare($sql_total_capacity);
                $stmt_total_capacity->bind_param("sssssss", $floorSelection, $startDate, $startDate, $currentTimeStr, $currentTimeStr, $startTime, $endTime);
                $stmt_total_capacity->execute();
                $result_total_capacity = $stmt_total_capacity->get_result();
                $data_total_capacity = $result_total_capacity->fetch_assoc();

                // Track the maximum occupancy across all intervals
                if ($data_total_capacity['total_capacity'] > $max_occupancy) {
                    $max_occupancy = $data_total_capacity['total_capacity'];
                }
            }
        }

        // Calculate remaining capacity
        $remaining_capacity = $hall_capacity - $max_occupancy;

        // Check if remaining capacity is sufficient for the new booking
        if ($remaining_capacity >= $participantNo) {
            // Proceed with the booking
            // Fetch hall_id based on floorSelection
            $select_hall_query = "SELECT hall_id FROM hall WHERE hall_floor = ?";
            $stmt_select_hall = $conn->prepare($select_hall_query);
            $stmt_select_hall->bind_param("s", $floorSelection);
            $stmt_select_hall->execute();
            $stmt_select_hall->bind_result($hallId);
            $stmt_select_hall->fetch();
            $stmt_select_hall->close();

            // Check if hall_id is valid
            if ($hallId) {
                // Define the SQL statement for updating
                $updateSql = "UPDATE booking SET bk_purpose = ?, bk_courseCode = ?, bk_name = ?, bk_contactNo = ?, bk_facDepartment = ?, bk_floorSelection = ?,  bk_duration = ?, bk_startDate = ?, bk_endDate = ?, bk_startTime = ?, bk_endTime = ?, bk_participantNo = ?, bk_remark = ? WHERE bk_id = ?";
                $stmt_update = $conn->prepare($updateSql);

                if ($stmt_update) {
                    $stmt_update->bind_param("sssssssssssisi", $purpose, $courseCode, $name, $contact, $facDepartment, $floorSelection, $bookingDuration, $startDate, $endDate, $startTime, $endTime, $participantNo, $remark, $bookingId);

                    if ($stmt_update->execute()) {
                        echo "<script>alert('Booking information updated successfully!');</script>";
                        // Redirect after showing the alert
                        echo "<script>window.location.href = 'reservationDetails.php?id=" . $bookingId . "';</script>";
                        exit(); // Ensure no other code is executed after redirection
                    } else {
                        echo "<script>alert('Failed to update booking information!');</script>";
                    }
                } else {
                    echo "<script>alert('Failed to prepare update statement!');</script>";
                }
            }
        } else {
            // Display an error message indicating that the hall capacity is exceeded
            echo "<script>alert('The number of participants exceeds the hall capacity for the selected floor, date, and time slot. Please select a different time slot or reduce the number of participants.');</script>";
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
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="reservationDetails.js"></script>
    <link rel="stylesheet" href="styles/reservationDetails.css">
    <title>Booking Details | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_admin.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>Reservation Details</h1>
        <div class="border-wrapper">
            <h3>Booking ID -- <?php echo $booking['bk_id']; ?></h3>
            <form id="updateForm" action="reservationDetails.php?id=<?php echo $bookingId; ?>" method="POST">
                <div class=" form-group">
                    <label for="purpose">Reservation Purpose:</label>
                    <input type="text" id="purpose" name="purpose" value="<?php echo $booking['bk_purpose']; ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="courseCode">Course Code:</label>
                    <input type="text" id="courseCode" name="courseCode" value="<?php echo $booking['bk_courseCode']; ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="facDepartment">Faculty/Department:</label>
                    <input type="text" id="facDepartment" name="facDepartment" value="<?php echo $booking['bk_facDepartment']; ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="name">Applicant's Name:</label>
                    <input type="text" id="name" name="name" value="<?php echo $booking['bk_name']; ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="contact">Contact Number:</label>
                    <input type="tel" id="contact" name="contact" value="<?php echo $booking['bk_contactNo']; ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="floorSelect">Floor Selection:</label>
                    <select id="floorSelect" name="floorSelect" required>
                        <option value="">Please Select</option>
                        <option value="top" <?php if ($booking['bk_floorSelection'] == 'top') echo 'selected'; ?>>F2 Top Floor</option>
                        <option value="low" <?php if ($booking['bk_floorSelection'] == 'low') echo 'selected'; ?>>F2 Lower Floor</option>

                    </select>
                </div>
                <br>
                <div class="form-group">
                    <label for="bookingDuration">Booking Duration:</label><br>
                    <input type="radio" id="oneDay" name="bookingDuration" value="oneDay" <?php echo isset($booking['bk_duration']) && $booking['bk_duration'] === 'oneDay' ? 'checked' : ''; ?>>
                    <label for="oneDay">One Day</label><br>
                    <input type="radio" id="multipleDays" name="bookingDuration" value="multipleDays" <?php echo isset($booking['bk_duration']) && $booking['bk_duration'] === 'multipleDays' ? 'checked' : ''; ?>>
                    <label for="multipleDays">Multiple Days</label>
                </div>
                <br>
                <div class="form-group">
                    <label for="startDate">Start Date:</label>
                    <input type="date" id="startDate" name="startDate" value="<?php echo $booking['bk_startDate'] ?? ''; ?>" min="<?php echo date('Y-m-d'); ?>" required onchange="setEndDate()">
                </div>
                <br>
                <div class="form-group">
                    <label for="endDate">End Date:</label>
                    <input type="date" id="endDate" name="endDate" value="<?php echo isset($booking['bk_endDate']) ? $booking['bk_endDate'] : ''; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="startTime">Start Time:</label>
                    <select name="startTime" id="startTime" required>
                        <!-- Option for "Please Select" -->
                        <option value="" <?php if (empty($booking['bk_startTime'])) echo 'selected'; ?>>Please Select</option>
                        <!-- Options for start time -->
                        <?php
                        // Array of available start times
                        $startTimes = array(
                            "07:00:00", "07:30:00", "08:00:00", "08:30:00", "09:00:00", "09:30:00", "10:00:00", "10:30:00", "11:00:00", "11:30:00",
                            "12:00:00", "12:30:00", "13:00:00", "13:30:00", "14:00:00", "14:30:00", "15:00:00", "15:30:00", "16:00:00", "16:30:00",
                            "17:00:00", "17:30:00", "18:00:00", "18:30:00", "19:00:00", "19:30:00", "20:00:00", "20:30:00", "21:00:00", "21:30:00",
                            "22:00:00", "22:30:00", "23:00:00", "23:30:00"
                        );

                        // Loop through start times
                        foreach ($startTimes as $time) {
                            // Output each option
                            echo "<option value=\"$time\"";
                            // Check if this option is selected
                            if ($booking['bk_startTime'] == $time) {
                                echo " selected";
                            }
                            echo ">$time</option>";
                        }
                        ?>
                    </select>
                </div>
                <br>
                <div class="form-group">
                    <label for="endTime">End Time:</label>
                    <select name="endTime" id="endTime" required>
                        <!-- Option for "Please Select" -->
                        <option value="" <?php if (empty($booking['bk_endTime'])) echo 'selected'; ?>>Please Select</option>
                        <!-- Options for end time -->
                        <?php
                        // Array of available end times
                        $endTimes = array(
                            "07:00:00", "07:30:00", "08:00:00", "08:30:00", "09:00:00", "09:30:00", "10:00:00", "10:30:00", "11:00:00", "11:30:00",
                            "12:00:00", "12:30:00", "13:00:00", "13:30:00", "14:00:00", "14:30:00", "15:00:00", "15:30:00", "16:00:00", "16:30:00",
                            "17:00:00", "17:30:00", "18:00:00", "18:30:00", "19:00:00", "19:30:00", "20:00:00", "20:30:00", "21:00:00", "21:30:00",
                            "22:00:00", "22:30:00", "23:00:00", "23:30:00"
                        );
                        // Loop through end times
                        foreach ($endTimes as $time) {
                            // Output each option
                            echo "<option value=\"$time\"";
                            // Check if this option is selected
                            if ($booking['bk_endTime'] == $time) {
                                echo " selected";
                            }
                            echo ">$time</option>";
                        }
                        ?>
                    </select>
                </div>
                <br>
                <div class="form-group">
                    <label for="participantNo">Number of Participants:</label>
                    <input type="number" id="participantNo" name="participantNo" min=0 value="<?php echo $booking['bk_participantNo']; ?>" required>
                </div>
                <span class="remaining-capacity">Remaining Capacity for Selected Time Slot: <?php echo isset($remaining_capacity) ? $remaining_capacity : 'N/A'; ?></span>
                <br>
                <div class="form-group">
                    <label for="file">File Attachment:</label>
                    <?php if ($booking['bk_filePath'] !== null) : ?>
                        <span id="filePath"><?php echo $booking['bk_filePath']; ?></span>
                    <?php else : ?>
                        <span id="filePath">None</span>
                    <?php endif; ?>
                </div>

                <br>
                <div class="file-attachment-info">
                    <p id="note">For purposes other than examination, please attach the related documents</p>
                    <p id="support">*Supported Format PDF Only</p>
                </div>
                <br>
                <div class="form-group">
                    <label for="remark">Remark:</label>
                    <input type="text" id="remark" name="remark" value="<?php echo $booking['bk_remark']; ?>">
                </div>
                <br>
                <div class="button-group">
                    <a href="reservation.php" id="back">Back</a>
                    <input type="submit" value="Update">
                </div>
            </form>
        </div>
    </div>

    <?php require_once('footer.php'); ?>

</body>

</html>

<?php
// Close the database connection
$conn->close();
?>

<script>
    // JavaScript to handle form submission
    document.getElementById("updateForm").addEventListener("submit", function(event) {
        // Prevent the default form submission behavior
        event.preventDefault();

        // Optionally, you can add a confirmation here before submitting the form
        if (confirm('Are you sure you want to update the booking details?')) {
            // Submit the form using JavaScript
            this.submit();
        }
    });
</script>