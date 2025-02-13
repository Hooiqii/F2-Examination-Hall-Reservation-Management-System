<?php
require_once('db_connection.php');

// Retrieve year and month parameters from URL query string
$currentYear = isset($_GET['year']) ? $_GET['year'] : date('Y');
$currentMonth = isset($_GET['month']) ? $_GET['month'] : date('n');

// Validate month range (1-12)
if ($currentMonth < 1) {
    $currentMonth = 1; // Set to January
    $currentYear--; // Move to the previous year
} elseif ($currentMonth > 12) {
    $currentMonth = 12; // Set to December
    $currentYear++; // Move to the next year
}

// Function to update remaining capacity by date
function updateRemainingCapacity($year, $month, $conn)
{
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
    $sqlCapacity = "SELECT hall_capacity FROM hall WHERE hall_floor = 'top'";
    $resultCapacity = $conn->query($sqlCapacity);
    $hallCapacity = $resultCapacity->fetch_assoc()['hall_capacity'];

    // Initialize remaining capacity for all dates
    $remainingCapacityByDate = array();

    // Iterate over each day of the month
    $numDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($currentDay = 1; $currentDay <= $numDaysInMonth; $currentDay++) {
        // Format current date
        $currentDateFormatted = sprintf("%04d-%02d-%02d", $year, $month, $currentDay);

        // Query the database to get the booked capacity for bookings that overlap with the current date
        $sql_total_capacity = "SELECT bk_startDate, bk_endDate, bk_startTime, bk_endTime, SUM(bk_participantNo) as total_capacity 
            FROM booking 
            WHERE bk_floorSelection = 'top' 
            AND bk_startDate <= ?
            AND bk_endDate >= ?
            AND (bk_status='Approve' OR bk_status='Pending')
            GROUP BY bk_startDate, bk_endDate, bk_startTime, bk_endTime";
        $stmt_total_capacity = $conn->prepare($sql_total_capacity);
        $stmt_total_capacity->bind_param("ss", $currentDateFormatted, $currentDateFormatted);
        $stmt_total_capacity->execute();
        $result = $stmt_total_capacity->get_result();

        // Initialize the remaining capacity for the current date
        $remainingCapacityByTimeSlot = array_fill_keys($timeSlots, $hallCapacity);

        // Update remaining capacity based on booked capacity for the current date and each time slot
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
                    // Reduce the remaining capacity for the current date and time slot by the booked capacity
                    $remainingCapacityByTimeSlot[$timeSlot] -= $bookedCapacity;
                }
            }
        }

        // Calculate the total remaining capacity for the current date
        $totalRemainingCapacityForDate = array_sum($remainingCapacityByTimeSlot);

        // Store the remaining capacity for the current date
        $remainingCapacityByDate[$currentDay] = $totalRemainingCapacityForDate;
    }

    return $remainingCapacityByDate;
}

// Update remaining capacity by date
$remainingCapacityByDate = updateRemainingCapacity($currentYear, $currentMonth, $conn);

$_SESSION['remaining_capacity'] = $remainingCapacityByDate;

// Function to calculate total booked capacity by date
function getTotalBookedCapacity($year, $month, $conn)
{
    // Initialize array to hold total booked capacity per date
    $bookedCapacityByDate = array();

    // Iterate over each day of the month
    $numDaysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    for ($currentDay = 1; $currentDay <= $numDaysInMonth; $currentDay++) {
        // Format current date
        $currentDateFormatted = sprintf("%04d-%02d-%02d", $year, $month, $currentDay);

        // Query the database to get the total booked capacity for bookings that overlap with the current date
        $sql_total_capacity = "SELECT SUM(bk_participantNo) as total_booked_capacity 
            FROM booking 
            WHERE bk_floorSelection = 'top' 
            AND bk_startDate <= ?
            AND bk_endDate >= ?
            AND (bk_status='Approve' OR bk_status='Pending')";
        
        $stmt_total_capacity = $conn->prepare($sql_total_capacity);
        $stmt_total_capacity->bind_param("ss", $currentDateFormatted, $currentDateFormatted);
        $stmt_total_capacity->execute();
        $result = $stmt_total_capacity->get_result();

        // Fetch the total booked capacity for the current date
        $row = $result->fetch_assoc();
        $totalBookedCapacity = $row['total_booked_capacity'] ? (int)$row['total_booked_capacity'] : 0;

        // Store the total booked capacity for the current date
        $bookedCapacityByDate[$currentDay] = $totalBookedCapacity;
    }

    return $bookedCapacityByDate;
}

// Get total booked capacity by date
$bookedCapacityByDate = getTotalBookedCapacity($currentYear, $currentMonth, $conn);

// Store in session to be used in the frontend
$_SESSION['booked_capacity'] = $bookedCapacityByDate;
