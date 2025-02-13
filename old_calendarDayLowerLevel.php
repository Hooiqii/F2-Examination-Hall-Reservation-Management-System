<?php
session_start();
include 'session_check.php';
require_once('db_connection.php');

// Check if user is an admin or user to load appropriate navbar
$isAdmin = isset($_SESSION['admin_id']);
$isUser = isset($_SESSION['user_id']);

// Fetch the selected date from the URL parameter
if (isset($_GET['selectedDate'])) {
    $selectedDate = $_GET['selectedDate'];
    // Convert the selected date to the appropriate format
    $date = DateTime::createFromFormat('Y-m-d', $selectedDate);
    if ($date) {
        $selectedDateFormatted = $date->format('Y-m-d');

        // Define time slots from 07:00 to 23:30
        $timeSlots = array();
        $startTime = strtotime('07:00');
        $endTime = strtotime('23:00');
        while ($startTime <= $endTime) {
            $timeSlotStart = date('H:i', $startTime);
            $timeSlotEnd = date('H:i', strtotime('+30 minutes', $startTime));
            $timeSlots[] = "$timeSlotStart-$timeSlotEnd";
            $startTime = strtotime('+30 minutes', $startTime);
        }

        // Query the database to get the total capacity of the top floor
        $sqlCapacity = "SELECT hall_capacity FROM hall WHERE hall_floor = 'low'";
        $resultCapacity = $conn->query($sqlCapacity);
        $hallCapacity = $resultCapacity->fetch_assoc()['hall_capacity'];

        // Initialize remaining capacity for all time slots
        $remainingCapacity = array_fill_keys($timeSlots, $hallCapacity);

        // Query the database to get the booked capacity for each time slot
        $sql_total_capacity = "SELECT bk_startTime, bk_endTime, SUM(bk_participantNo) as total_capacity 
        FROM booking 
        WHERE bk_floorSelection = ? 
        AND bk_startDate = ? 
        AND bk_endDate = ? 
        AND (bk_status='Approve' OR bk_status='Pending')
        GROUP BY bk_startTime, bk_endTime";
        $stmt_total_capacity = $conn->prepare($sql_total_capacity);
        $floorSelection = 'low';
        $stmt_total_capacity->bind_param("sss", $floorSelection, $selectedDateFormatted, $selectedDateFormatted);
        $stmt_total_capacity->execute();
        $result = $stmt_total_capacity->get_result();

        // Update remaining capacity based on booked capacity for each time slot
        while ($row = $result->fetch_assoc()) {
            $startTime = $row['bk_startTime'];
            $endTime = $row['bk_endTime'];
            $bookedCapacity = $row['total_capacity'];

            // Iterate through time slots and update remaining capacity accordingly
            foreach ($timeSlots as $timeSlot) {
                list($slotStart, $slotEnd) = explode('-', $timeSlot);
                $slotStartTimestamp = strtotime($slotStart);
                $slotEndTimestamp = strtotime($slotEnd);
                $bookingStartTimestamp = strtotime($startTime);
                $bookingEndTimestamp = strtotime($endTime);

                // Check if the booking overlaps with the current time slot
                if (
                    ($slotStartTimestamp >= $bookingStartTimestamp && $slotStartTimestamp < $bookingEndTimestamp) ||
                    ($slotEndTimestamp > $bookingStartTimestamp && $slotEndTimestamp <= $bookingEndTimestamp) ||
                    ($slotStartTimestamp <= $bookingStartTimestamp && $slotEndTimestamp >= $bookingEndTimestamp)
                ) {
                    // Reduce the remaining capacity by the booked capacity
                    $remainingCapacity[$timeSlot] -= $bookedCapacity;
                }
            }
        }
    } else {
        // Handle invalid date format
        echo "Error: Invalid date format.";
        exit;
    }
} else {
    // Handle case when selected date is not provided
    if (!isset($_GET['logout'])) {
        echo "Error: Selected date is missing.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles/calendarDay.css">
    <title>Lower Level | <?php echo date('d F Y', strtotime($selectedDateFormatted)); ?></title>
</head>

<body>
    <?php require_once('header.php'); ?>
    <!-- Load appropriate navbar based on user type -->
    <?php
    if ($isAdmin) {
        require_once('navbar_admin.php');
    } else if ($isUser) {
        require_once('navbar_user.php');
    }
    ?>
    <?php require_once('breadcrumb.php'); ?>
    <div class="content-wrapper">
    <div class="title">
            <h1>Lower Level | <?php echo date('d F Y', strtotime($selectedDateFormatted)); ?></h1>
            <?php
            if ($isUser && $date >= new DateTime('today')) {
                echo '<a href="newBooking.php?selectedDate=' . $selectedDateFormatted . '"><button id="addButton">Add</button></a>';
            }
            ?>
        </div>
        <div class="border-wrapper">
            <table>
                <tr>
                    <th>Time Slot</th>
                    <th>Remaining Capacity</th>
                </tr>
                <?php foreach ($timeSlots as $timeSlot) : ?>
                    <tr
                        <?php
                        // Initialize an empty style variable
                        $rowStyle = '';

                        // Determine the background color based on the remaining capacity
                        if ($remainingCapacity[$timeSlot] == 0) {
                            $rowStyle = 'style="background-color: #f7e1e3;"';
                        } elseif ($remainingCapacity[$timeSlot] != 0 && $remainingCapacity[$timeSlot] != $hallCapacity) {
                            $rowStyle = 'style="background-color: #e4f5e7;"';
                        }
                        echo $rowStyle; // Echo the style only if it has a value
                        ?>>
                        <td><?php echo $timeSlot; ?></td>
                        <td><?php echo $remainingCapacity[$timeSlot]; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
            <div class="legend">
                <div class="legend-item">
                    <strong>
                        <p>Legend:</p>
                    </strong>
                </div>
                <div class="legend-item">
                    <div class="color-box white-box"></div>
                    <span>Unoccupied</span>
                </div>
                <div class="legend-item">
                    <div class="color-box green-box"></div>
                    <span>Occupied But Not Fully-Booked</span>
                </div>
                <div class="legend-item">
                    <div class="color-box red-box"></div>
                    <span>Fully-Booked</span>
                </div>
            </div>
</body>

</html>