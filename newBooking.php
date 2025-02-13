<?php
// Set the timezone
date_default_timezone_set('Asia/Kuala_Lumpur');

session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if the user is logged in and has a valid user ID
if (!isset($_SESSION['user_id'])) {
    die('User not logged in');
}

$userId = $_SESSION['user_id'];

// Retrieve user information from the database
$sql_user_info = "SELECT user_name, user_contact FROM users WHERE user_id = ?";
$stmt_user_info = $conn->prepare($sql_user_info);
$stmt_user_info->bind_param("i", $userId);
$stmt_user_info->execute();
$stmt_user_info->bind_result($name, $contact);
$stmt_user_info->fetch();
$stmt_user_info->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form input values
    $purpose = $_POST['purpose'];
    $courseCode = $_POST['courseCode'];
    $facDepartment = $_POST['facDepartment'];
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
                // Prepared statement for inserting booking
                $insert_query = "INSERT INTO booking (bk_purpose, bk_courseCode, bk_name, bk_contactNo, bk_facDepartment, bk_floorSelection, bk_startDate, bk_endDate, bk_startTime, bk_endTime, bk_participantNo, bk_duration, user_id, hall_id, bk_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($insert_query);
                $status = "Pending";
                $userId = $_SESSION['user_id'] ?? null;
                $adminId = $_SESSION['admin_id'] ?? null;

                $stmt->bind_param("sssssssssssssss", $purpose, $courseCode, $name, $contact, $facDepartment, $floorSelection, $startDate, $endDate, $startTime, $endTime, $participantNo, $bookingDuration, $userId, $hallId, $status);

                if ($stmt->execute()) {
                    // Only access insert_id if the execution was successful
                    $specificBookingId = $stmt->insert_id; // Assuming booking_id is auto-incremented
                    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
                        $targetDir = "uploads/";
                        $fileName = basename($_FILES['file']['name']);
                        $targetPath = $targetDir . $fileName;

                        if (move_uploaded_file($_FILES['file']['tmp_name'], $targetPath)) {
                            $sql = "UPDATE booking SET bk_fileAttachment = ?, bk_filePath = ? WHERE bk_id = ?";
                            $stmt_file = $conn->prepare($sql);
                            $stmt_file->bind_param("ssi", $fileName, $targetPath, $specificBookingId);

                            if ($stmt_file->execute()) {
                                echo "<script>alert('Booking successful!');</script>";
                            } else {
                                echo "<script>alert('Failed to update booking details with file information!');</script>";
                            }
                        } else {
                            echo "<script>alert('There was an error uploading the file.');</script>";
                        }
                    } else {
                        echo "<script>alert('Booking successful!'); window.location.href = 'bookingList.php';</script>";
                        exit();
                    }
                } else {
                    echo "<script>alert('Failed to make a booking!');</script>";
                }
            } else {
                echo "<script>alert('Invalid hall selection!');</script>";
            }
        } else {
            $_SESSION['booking_data'] = $_POST;
            // Display an error message indicating that the hall capacity is exceeded
            echo "<script>alert('The number of participants exceeds the hall capacity for the selected floor, date, and time slot. Please select a different time slot or reduce the number of participants.');</script>";
        }
    } else {
        echo "<script>alert('Invalid participant number. Please enter a numeric value.');</script>";
    }
} else {
    $_SESSION['booking_data'] = []; // Initialize empty session data
}

// Check if there's any session data to repopulate the form
$repopulatedData = $_SESSION['booking_data'] ?? [];

if (isset($_GET['selectedDate'])) {
    $selectedDate = $_GET['selectedDate'];
} else {
    $selectedDate = $repopulatedData['startDate'] ?? '';
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://kit.fontawesome.com/4bd38d7b8a.js" crossorigin="anonymous"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="newBooking.js"></script>
    <link rel="stylesheet" href="styles/newBooking.css">
    <title>New Booking | F2 Examination Hall</title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <?php require_once('navbar_user.php'); ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
        <h1>New Booking</h1>

        <!-- Display Guidelines or Agreement Here -->
        <div class="guideline-wrapper" id="guidelineWrapper">
            <h2>Reservation Guidelines</h2>
            <h3>Acknowledgements</h3>
            <div class="acknowledgement-content">
                <p>✔ Replace lost or damaged tools</p>
                <p>✔ Keep the hall clean</p>
                <p>✔ Keep property safe</p>
                <p>✔ Rearrange the tables and chairs</p>
            </div>
            <h3>Rules and Responsibilities of Applicants</h3>
            <div class="rules-content">
                <ul>
                    <li>Applicants must cancel the reservation a day before the reservation date if they wish to cancel the reservation.</li>
                    <li>Applicants are not allow to make hall reservation on Friday and Saturday.</li>
                    <li>Applicants must ensure that all light switches and air conditioners are turned off while the doors are closed and locked after finishing using the hall.</li>
                    <li>Applicants must collect and return the ACS access card at the PPA counter on working days and during office hours.</li>
                    <ul>
                        <li>ACS card collection must be done one day before the date of use of the Examination Hall</li>
                        <li>The return of the ACS card must be made one day after the date of use of the Examination Hall (working day)</li>
                    </ul>
                </ul>
                <div class="guideline-bottom">
                    <input type="checkbox" id="guidelineAgree" name="guidelineAgree">
                    <label for="guidelineAgree">I understand, clearly and will be responsible for the use of the space during the preparation, when the use after completion of the program in the space that has been approved.</label>
                    <br>
                    <button id="guideline-button" onclick="showBookingForm()">Submit</button>
                </div>
            </div>
        </div>
        <!-- Actual Booking Form Hidden by Default -->
        <div class="bookingForm" id="bookingForm" style="display:none;">
            <!-- Form for new booking -->
            <form action="<?= $_SERVER["PHP_SELF"] ?>" method="post" enctype="multipart/form-data">
                <h2>Reservation Details</h2>
                <div class=" form-group">
                    <label for="purpose">Reservation Purpose:</label>
                    <input type="text" id="purpose" name="purpose" value="<?php echo $repopulatedData['purpose'] ?? ''; ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="courseCode">Course Code:</label>
                    <input type="text" id="courseCode" name="courseCode" value="<?php echo $repopulatedData['courseCode'] ?? ''; ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="facDepartment">Faculty/Department:</label>
                    <input type="text" id="facDepartment" name="facDepartment" value="<?php echo $repopulatedData['facDepartment'] ?? ''; ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="floorSelect">Floor Selection:</label>
                    <select id="floorSelect" name="floorSelect" value="<?php echo $repopulatedData['floorSelect'] ?? ''; ?>" required>
                        <option value="">Please Select</option>
                        <option value="top">F2 Top Floor</option>
                        <option value="low">F2 Lower Floor</option>
                    </select>
                </div>
                <br>
                <div class="form-group">
                    <label for="bookingDuration">Booking Duration:</label><br>
                    <input type="radio" id="oneDay" name="bookingDuration" value="oneDay" <?php echo isset($repopulatedData['bookingDuration']) && $repopulatedData['bookingDuration'] === 'oneDay' ? 'checked' : ''; ?>>
                    <label for="oneDay">One Day</label><br>
                    <input type="radio" id="multipleDays" name="bookingDuration" value="multipleDays" <?php echo isset($repopulatedData['bookingDuration']) && $repopulatedData['bookingDuration'] === 'multipleDays' ? 'checked' : ''; ?>>
                    <label for="multipleDays">Multiple Days</label>
                </div>
                <br>
                <div class="form-group">
                    <label for="startDate">Start Date:</label>
                    <input type="date" id="startDate" name="startDate" value="<?php echo $selectedDate; ?>" min="<?php echo date('Y-m-d'); ?>" required onchange="setEndDate()">
                </div>
                <br>
                <div class="form-group">
                    <label for="endDate">End Date:</label>
                    <input type="date" id="endDate" name="endDate" value="<?php echo isset($repopulatedData['endDate']) ? $repopulatedData['endDate'] : ''; ?>" min="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <br>
                <div class="form-group">
                    <label for="startTime">Start Time:</label>
                    <select name="startTime" id="startTime" value="<?php echo $repopulatedData['startTime'] ?? ''; ?>" required>
                        <option value="">Please Select</option>
                        <option value="07:00">07:00</option>
                        <option value="07:30">07:30</option>
                        <option value="08:00">08:00</option>
                        <option value="08:30">08:30</option>
                        <option value="09:00">09:00</option>
                        <option value="09:30">09:30</option>
                        <option value="10:00">10:00</option>
                        <option value="10:30">10:30</option>
                        <option value="11:00">11:00</option>
                        <option value="11:30">11:30</option>
                        <option value="12:00">12:00</option>
                        <option value="12:30">12:30</option>
                        <option value="13:00">13:00</option>
                        <option value="13:30">13:30</option>
                        <option value="14:00">14:00</option>
                        <option value="14:30">14:30</option>
                        <option value="15:00">15:00</option>
                        <option value="15:30">15:30</option>
                        <option value="16:00">16:00</option>
                        <option value="16:30">16:30</option>
                        <option value="17:00">17:00</option>
                        <option value="17:30">17:30</option>
                        <option value="18:00">18:00</option>
                        <option value="18:30">18:30</option>
                        <option value="19:00">19:00</option>
                        <option value="19:30">19:30</option>
                        <option value="20:00">20:00</option>
                        <option value="20:30">20:30</option>
                        <option value="21:00">21:00</option>
                        <option value="21:30">21:30</option>
                        <option value="22:00">22:00</option>
                        <option value="22:30">22:30</option>
                        <option value="23:00">23:00</option>
                        <option value="23:30">23:30</option>
                    </select>
                </div>
                <br>
                <div class="form-group">
                    <label for="endTime">End Time:</label>
                    <select name="endTime" id="endTime" value="<?php echo $repopulatedData['endTime'] ?? ''; ?>" required>
                        <option value="">Please Select</option>
                        <option value="07:00">07:00</option>
                        <option value="07:30">07:30</option>
                        <option value="08:00">08:00</option>
                        <option value="08:30">08:30</option>
                        <option value="09:00">09:00</option>
                        <option value="09:30">09:30</option>
                        <option value="10:00">10:00</option>
                        <option value="10:30">10:30</option>
                        <option value="11:00">11:00</option>
                        <option value="11:30">11:30</option>
                        <option value="12:00">12:00</option>
                        <option value="12:30">12:30</option>
                        <option value="13:00">13:00</option>
                        <option value="13:30">13:30</option>
                        <option value="14:00">14:00</option>
                        <option value="14:30">14:30</option>
                        <option value="15:00">15:00</option>
                        <option value="15:30">15:30</option>
                        <option value="16:00">16:00</option>
                        <option value="16:30">16:30</option>
                        <option value="17:00">17:00</option>
                        <option value="17:30">17:30</option>
                        <option value="18:00">18:00</option>
                        <option value="18:30">18:30</option>
                        <option value="19:00">19:00</option>
                        <option value="19:30">19:30</option>
                        <option value="20:00">20:00</option>
                        <option value="20:30">20:30</option>
                        <option value="21:00">21:00</option>
                        <option value="21:30">21:30</option>
                        <option value="22:00">22:00</option>
                        <option value="22:30">22:30</option>
                        <option value="23:00">23:00</option>
                        <option value="23:30">23:30</option>
                    </select>
                </div>
                <br>
                <div class="form-group">
                    <label for="participantNo">Number of Participants:</label>
                    <input type="number" min=1 id="participantNo" name="participantNo" value="<?php echo $repopulatedData['participantNo'] ?? ''; ?>" required>
                </div>
                <span class="remaining-capacity">Remaining Capacity for Selected Time Slot: <?php echo isset($remaining_capacity) ? $remaining_capacity : 'N/A'; ?></span>
                <br>
                <div class="form-group">
                    <label for="file">File Attachment:</label>
                    <input type="file" id="file" name="file" accept=".pdf" multiple>
                </div>
                <div class="file-attachment-info">
                    <p id="note">For purposes other than examination, please attach the related documents</p>
                    <p id="support">*Supported Format PDF Only</p>
                </div>
                <br>
                <div class="form-group">
                    <label for="remark">Remark:</label>
                    <input type="text" id="remark" name="remark" value="<?php echo $repopulatedData['remark'] ?? ''; ?>">
                </div>
                <br>
                <div class="button-group">
                    <button id="back" onclick="showGuideline()">Back</button>
                    <input type="reset" id="resetButton" value="Reset" onclick="confirmReset()">
                    <input type="submit" value="Save">
                </div>
            </form>
            <?php
            // Clear session data after form is rendered
            unset($_SESSION['booking_data']);
            ?>
        </div>
    </div>
    <?php require_once('footer.php'); ?>
</body>

</html>