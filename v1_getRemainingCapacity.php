<?php
// Include necessary files and database connection
require_once('db_connection.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $floorSelection = $_POST['floorSelection'];
    $startDate = $_POST['startDate'];
    $endDate = $_POST['endDate'];
    $startTime = $_POST['startTime'];
    $endTime = $_POST['endTime'];

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

    // Ensure remaining capacity is not negative
    if ($remaining_capacity < 0) {
        echo 0;
    } else {
        echo $remaining_capacity;
    }
}
